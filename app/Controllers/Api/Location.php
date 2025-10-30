<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\DriverLocationModel;
use App\Models\ReportSettingsModel;
use App\Models\ServiceReportModel;

class Location extends ResourceController
{
    protected $format = 'json';

    public function save()
    {
        log_message('info', '=== MOBİL KONUM İSTEĞİ GELDİ ===');
        
        $latitude  = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $email     = $this->request->getPost('email');

        log_message('info', "Lat: {$latitude}, Lng: {$longitude}, Email: {$email}");

        if (empty($latitude) || empty($longitude)) {
            log_message('error', 'Konum bilgisi eksik!');
            return $this->fail('Konum bilgisi eksik', 400);
        }

        if (empty($email)) {
            log_message('error', 'Email eksik!');
            return $this->fail('Email bilgisi eksik', 400);
        }

        try {
            $db = \Config\Database::connect();
            
            $identity = $db->table('auth_identities')
                ->where('secret', $email)
                ->where('type', 'email_password')
                ->get()
                ->getRowArray();

            if (!$identity) {
                log_message('error', "Email bulunamadı: {$email}");
                return $this->fail('Kullanıcı bulunamadı', 404);
            }

            $userId = $identity['user_id'];
            log_message('info', "User bulundu: ID={$userId}");

            $locationModel = new DriverLocationModel();
            
            $data = [
                'user_id'   => $userId,
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];

            log_message('info', 'DB\'ye kayıt atılıyor: ' . json_encode($data));

            $insertedId = $locationModel->insert($data);

            if ($insertedId) {
                log_message('info', "✅ BAŞARILI! Konum kaydedildi. ID: {$insertedId}");

                // Process service report calculation
                try {
                    $this->updateServiceReport($userId);
                } catch (\Exception $e) {
                    log_message('error', '[ServiceReport] Rapor hesaplama hatası: ' . $e->getMessage());
                }

                // Keep only the last 5 locations
                $totalLocations = $locationModel->where('user_id', $userId)->countAllResults();
                if ($totalLocations > 5) {
                    $oldestLocation = $locationModel->where('user_id', $userId)->orderBy('created_at', 'ASC')->first();
                    if ($oldestLocation) {
                        $locationModel->delete($oldestLocation['id']);
                        log_message('info', "Limit aşıldı. En eski konum (ID: {$oldestLocation['id']}) silindi.");
                    }
                }
                
                return $this->respond([
                    'status'  => 'success',
                    'message' => 'Konum kaydedildi',
                    'id'      => $insertedId,
                    'user_id' => $userId,
                ], 200);
            }
            
            log_message('error', '❌ Insert başarısız');
            return $this->fail('Konum kaydedilemedi', 500);
            
        } catch (\Exception $e) {
            log_message('error', '❌ EXCEPTION: ' . $e->getMessage());
            return $this->fail('Sunucu hatası: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the service report with the latest location data.
     *
     * @param int $userId
     */
    private function updateServiceReport(int $userId)
    {
        helper('geo');
        $settingsModel = new ReportSettingsModel();
        $serviceReportModel = new ServiceReportModel();
        $locationModel = new DriverLocationModel();

        $settings = array_merge([
            'tracking_start_time' => '08:00',
            'tracking_end_time'   => '19:00',
        ], $settingsModel->getSettings());

        $currentTime = new \DateTime('now', new \DateTimeZone('Europe/Istanbul'));
        $startTime = new \DateTime($settings['tracking_start_time'], new \DateTimeZone('Europe/Istanbul'));
        $endTime = new \DateTime($settings['tracking_end_time'], new \DateTimeZone('Europe/Istanbul'));

        if ($currentTime < $startTime || $currentTime > $endTime) {
            log_message('info', '[ServiceReport] Takip saatleri dışında, hesaplama yapılmadı.');
            return;
        }

        $locations = $locationModel->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(2)->find();

        if (count($locations) < 2) {
            log_message('info', '[ServiceReport] Mesafe hesaplamak için yeterli konum verisi yok (en az 2 gerekli).');
            return;
        }

        $currentLocation = $locations[0];
        $previousLocation = $locations[1];

        $distance = haversineDistance(
            (float)$previousLocation['latitude'],
            (float)$previousLocation['longitude'],
            (float)$currentLocation['latitude'],
            (float)$currentLocation['longitude']
        );

        $timeDiffSeconds = strtotime($currentLocation['created_at']) - strtotime($previousLocation['created_at']);

        $idleThresholdKm = 0.01; // 10 meters
        $kmToAdd = 0;
        $idleSecondsToAdd = 0;

        if ($distance < $idleThresholdKm) {
            $idleSecondsToAdd = $timeDiffSeconds;
            log_message('info', "[ServiceReport] Araç duruyor. {$idleSecondsToAdd} saniye eklendi.");
        } else {
            $kmToAdd = $distance;
            log_message('info', "[ServiceReport] Mesafe hesaplandı: {$kmToAdd} km.");
        }

        $today = date('Y-m-d');
        $report = $serviceReportModel->where('user_id', $userId)->where('date', $today)->first();

        if (!$report) {
            $report = [
                'user_id' => $userId,
                'date'    => $today,
                'total_km' => 0,
                'total_idle_time_seconds' => 0,
            ];
        }

        $report['total_km'] += $kmToAdd;
        $report['total_idle_time_seconds'] += $idleSecondsToAdd;

        $serviceReportModel->save($report);
        log_message('info', "[ServiceReport] Rapor güncellendi: UserID: {$userId}, Tarih: {$today}");
    }
}
