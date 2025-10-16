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
    
    $query = $db->table('driver_last_locations dll')
        ->select('dll.*, up.first_name, up.last_name, u.id as user_id')
        ->join('users u', 'u.id = dll.user_id')
        ->join('user_profiles up', 'up.user_id = u.id', 'left')
        ->where('dll.updated_at >=', $fiveMinutesAgo);

    if ($selectedDrivers) {
        $query->whereIn('dll.user_id', explode(',', $selectedDrivers));
    }
    
    $drivers = $query->get()->getResultArray();
    
    // Kurum konumunu al
    $institutionModel = new InstitutionModel();
    $institution = $institutionModel->select('latitude, longitude')->first();
    
    $companyLat = (float)($institution->latitude ?? 0);
    $companyLng = (float)($institution->longitude ?? 0);

    foreach ($drivers as &$driver) {
        // Son güncelleme zamanını hesapla
        $updatedTime = strtotime($driver['updated_at']);
        $currentTime = time();
        $diffMinutes = round(($currentTime - $updatedTime) / 60);
        
        $driver['last_update_minutes'] = $diffMinutes;
        $driver['last_update_text'] = $diffMinutes < 1 ? 'Az önce' : $diffMinutes . ' dk önce';
        
        // ETA hesapla
        if ($companyLat !== 0.0 && $companyLng !== 0.0) {
            $driver['eta'] = $this->calculateETA(
                (float)$driver['latitude'], 
                (float)$driver['longitude'],
                $companyLat,
                $companyLng
            );
        }
    }
    
    return $this->response->setJSON($drivers);
}
    
    /**
     * İki koordinat arasındaki mesafeyi ve tahmini varış süresini hesaplar.
     */
    private function calculateETA(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        // Haversine formülü ile mesafe (km)
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        // Ortalama hız 40 km/s varsayımı (şehir içi)
        $avgSpeed = 40;
        $timeInHours = $distance / $avgSpeed;
        $timeInMinutes = round($timeInHours * 60);
        
        return [
            'distance' => round($distance, 2),
            'minutes'  => $timeInMinutes,
            'text'     => $timeInMinutes < 1 ? 'Yakında' : $timeInMinutes . ' dk'
        ];
    }
}