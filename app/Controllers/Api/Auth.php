<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Auth extends ResourceController
{
    protected $format = 'json';

    public function login()
    {
        log_message('info', '=== LOGIN İSTEĞİ GELDİ ===');
        
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        log_message('info', "Email: {$email}");

        if (empty($email) || empty($password)) {
            return $this->fail('Email ve şifre gerekli', 400);
        }

        try {
            $db = \Config\Database::connect();
            
            // 1. auth_identities'den email ile user bul
            $identity = $db->table('auth_identities')
                ->where('secret', $email)
                ->where('type', 'email_password')
                ->get()
                ->getRowArray();

            if (!$identity) {
                log_message('error', "Email bulunamadı: {$email}");
                return $this->fail('Email veya şifre hatalı', 401);
            }

            log_message('info', "Identity bulundu, user_id: {$identity['user_id']}");

            // 2. Şifre kontrolü
            $hasher = service('passwords');
            
            if (!$hasher->verify($password, $identity['secret2'])) {
                log_message('error', 'Şifre YANLIŞ');
                return $this->fail('Email veya şifre hatalı', 401);
            }

            // 3. User bilgilerini al
            $user = $db->table('users')
                ->select('users.id, users.username, users.active, up.first_name, up.last_name')
                ->join('user_profiles up', 'up.user_id = users.id', 'left')
                ->where('users.id', $identity['user_id'])
                ->where('users.deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$user) {
                log_message('error', 'User bulunamadı veya silinmiş');
                return $this->fail('Kullanıcı bulunamadı', 404);
            }

            if (!$user['active']) {
                log_message('error', 'User aktif değil');
                return $this->fail('Hesap aktif değil', 403);
            }

            log_message('info', '✅ Login BAŞARILI: ' . $email);

            // ---> YENİ BAŞLANGIÇ <---
            $userGroups = $db->table('auth_groups_users')
                ->select('group')
                ->where('user_id', $user['id'])
                ->get()
                ->getResultArray();

            // Grupları tek boyutlu bir diziye çevir (örn: ['servis', 'admin'])
            $groups = array_column($userGroups, 'group');
            // ---> YENİ BİTİŞ <---

            // Token oluştur
            $token = bin2hex(random_bytes(32));

            return $this->respond([
                'status'  => 'success',
                'message' => 'Giriş başarılı',
                'token'   => $token,
                'user'    => [
                    'id'         => $user['id'],
                    'username'   => $user['username'],
                    'first_name' => $user['first_name'] ?? '',
                    'last_name'  => $user['last_name'] ?? '',
                    'email'      => $email,
                    'groups'     => $groups, // <-- İŞTE PUSULADAKİ HEDEFİMİZ!
                ],
            ], 200);
            
        } catch (\Exception $e) {
            log_message('error', '❌ Login EXCEPTION: ' . $e->getMessage());
            return $this->fail('Sunucu hatası', 500);
        }
    }
}