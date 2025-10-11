<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;

class LocationController extends BaseController
{
    public function saveLocation()
    {
        $db = \Config\Database::connect();
        $userId = auth()->id();
        
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Giriş gerekli']);
        }
        
        // 1. ESKİ KAYITLARI TEMİZLE (5 dakikadan eski)
        $db->query("
            DELETE FROM driver_locations 
            WHERE user_id = ? 
            AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ", [$userId]);
        
        // 2. YENİ KONUM KAYDET
        $data = [
            'user_id' => $userId,
            'latitude' => $this->request->getPost('latitude'),
            'longitude' => $this->request->getPost('longitude'),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $db->table('driver_locations')->insert($data);
        
        return $this->response->setJSON(['success' => true]);
    }

    public function getActiveDrivers()
    {
        $db = \Config\Database::connect();
        
        // SADECE HER KULLANICININ EN SON KONUMUNU AL
        $drivers = $db->query("
            SELECT 
                dl.user_id, 
                dl.latitude, 
                dl.longitude, 
                dl.created_at,
                up.first_name, 
                up.last_name
            FROM driver_locations dl
            INNER JOIN (
                SELECT user_id, MAX(created_at) as max_time
                FROM driver_locations
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                GROUP BY user_id
            ) latest ON dl.user_id = latest.user_id AND dl.created_at = latest.max_time
            INNER JOIN user_profiles up ON up.user_id = dl.user_id
            ORDER BY dl.created_at DESC
        ")->getResultArray();
        
        return $this->response->setJSON($drivers);
    }
}