<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InstitutionModel;
use CodeIgniter\I18n\Time; // KRİTİK: Bu sınıfı mutlaka dahil etmeliyiz!

class TrackingController extends BaseController
{
    /**
     * Harita sayfasını görüntüler.
     */
    public function map()
    {
        $db = \Config\Database::connect();
        $institutionModel = new InstitutionModel();

        // Sadece 'servis' grubundaki aktif kullanıcıları (sürücüleri) getir
        $drivers = $db->table('users u')
            ->select('u.id, up.first_name, up.last_name')
            ->join('user_profiles up', 'up.user_id = u.id', 'left')
            ->join('auth_groups_users agu', 'agu.user_id = u.id', 'left')
            ->where('agu.group', 'servis')
            ->where('u.active', 1)
            ->get()
            ->getResultArray();

        // Kurum konumunu 'institutions' tablosundan al
        $institution = $institutionModel->select('kurum_adi, adresi, latitude, longitude')->first();

        $companyLocation = [];
        if ($institution && $institution->latitude && $institution->longitude) {
            $companyLocation = [
                'lat' => (float)$institution->latitude,
                'lng' => (float)$institution->longitude,
                'name' => $institution->kurum_adi,
                'address' => $institution->adresi
            ];
        }
        
        $data['drivers'] = $drivers;
        $data['driverCount'] = count($drivers);
        $data['companyLocation'] = $companyLocation;
        $data['title'] = 'Servis Takip Sistemi';
        
        return view('tracking/map', array_merge($this->data, $data));
    }
    
    /**
     * API: Seçili sürücülerin son konumlarını JSON formatında döndürür.
     */
    public function getDriverLocations()
    {
         if (!$this->request->isAJAX() && ENVIRONMENT === 'production') {
             return $this->response->setStatusCode(403, 'Forbidden');
         }

        $db = \Config\Database::connect();
        $selectedDrivers = $this->request->getGet('drivers');
        
        // Son 5 dakika içindeki kayıtları al
        $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        
        $query = $db->table('driver_locations dll')
            ->select('dll.*, up.first_name, up.last_name, u.id as user_id')
            ->join('users u', 'u.id = dll.user_id')
            ->join('user_profiles up', 'up.user_id = u.id', 'left')
            ->where('dll.created_at >=', $fiveMinutesAgo);  // updated_at → created_at

        if ($selectedDrivers) {
            $query->whereIn('dll.user_id', explode(',', $selectedDrivers));
        }
        
        $drivers = $query->get()->getResultArray();
        
        // Kurum konumunu al
        $institutionModel = new InstitutionModel();
        $institution = $institutionModel->select('latitude, longitude')->first();
        
        $companyLat = (float)($institution->latitude ?? 0);
        $companyLng = (float)($institution->longitude ?? 0);

        // Her sürücü için mesafe ve dakika hesapla
        foreach ($drivers as &$driver) {
            if ($companyLat && $companyLng && $driver['latitude'] && $driver['longitude']) {
                // Mesafe hesapla (km)
                $distance = $this->calculateDistance(
                    $companyLat,
                    $companyLng,
                    (float)$driver['latitude'],
                    (float)$driver['longitude']
                );
                
                // Ortalama 50 km/s hız ile dakika hesabı
                $estimatedMinutes = round(($distance / 50) * 60);
                
                // ETA objesi oluştur (1 dakikanın altı için "Yakında" kontrolü eklenmiştir)
                $driver['eta'] = [
                    'distance' => number_format($distance, 2),
                    'minutes' => $estimatedMinutes,
                    'text' => $estimatedMinutes < 1 ? 'Yakında' : $estimatedMinutes . ' dk' 
                ];
            } else {
                $driver['eta'] = null;
            }
            
            // Son güncelleme zamanını insan okunabilir yap
            if (!empty($driver['updated_at'])) {
                $updatedTime = Time::parse($driver['updated_at']);
                $driver['last_update_text'] = $updatedTime->humanize();
            } else {
                $driver['last_update_text'] = 'Bilinmiyor';
            }
        }
        
        return $this->response->setJSON($drivers);
    }

    /**
     * İki koordinat arasındaki mesafeyi hesaplar (Haversine formülü)
     * * @param float $lat1 Başlangıç enlem
     * @param float $lon1 Başlangıç boylam
     * @param float $lat2 Bitiş enlem
     * @param float $lon2 Bitiş boylam
     * @return float Mesafe (km)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
    
}