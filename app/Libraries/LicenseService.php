<?php
namespace App\Libraries;
use Config\Services;

class LicenseService
{
    public function checkLicense(): bool
    {
        $cache = cache();
        // Cache'i kontrol et, varsa doğrudan sonucu dön.
        if ($cache->get('license_status') !== null) {
            return $cache->get('license_status');
        }

        $db = db_connect();
        $licenseKey = $db->table('app_settings')->where('key', 'license_key')->get()->getRow()->value ?? null;
        if (empty($licenseKey)) {
            return false;
        }

        try {
            $client = Services::curlrequest([
                'timeout'     => 10,
                'http_errors' => false,
            ]);
            
            // Sadece true/false döndüren, en basit ve çalışan API'yi kullanıyoruz.
            $response = $client->request('POST', 'https://app.mantaryazilim.tr/api/guest/serviceapikey/check', [
                'json' => [
                    'key' => $licenseKey 
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody());
                
                if (isset($body->result) && $body->result === true) {
                    $cache->save('license_status', true, 3600); // 1 saatliğine "geçerli" olarak cache'le
                    return true;
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'MantarYazılım API Hatası: ' . $e->getMessage());
            // Hata anında, varsa eski cache'e güven. Yoksa false dön.
            if ($cache->get('license_status') !== null) { return $cache->get('license_status'); }
            return false;
        }
        
        // Lisans geçerli değilse, cache'e "geçersiz" olarak kaydet.
        $cache->save('license_status', false, 3600);
        return false;
    }
}