<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\DriverLocationModel;

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
            
            // Email'den user_id bul (auth_identities tablosundan)
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

            // Konum kaydet
            $locationModel = new DriverLocationModel();
            
            $data = [
                'user_id'   => $userId,
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];

            log_message('info', 'DB\'ye kayıt atılıyor: ' . json_encode($data));

            $inserted = $locationModel->insert($data);

            if ($inserted) {
                log_message('info', "✅ BAŞARILI! Konum kaydedildi. ID: {$inserted}");
                
                return $this->respond([
                    'status'  => 'success',
                    'message' => 'Konum kaydedildi',
                    'id'      => $inserted,
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
}