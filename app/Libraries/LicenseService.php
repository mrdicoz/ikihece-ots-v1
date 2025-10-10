<?php
namespace App\Libraries;
use Config\Services;

class LicenseService
{
    /**
     * Lisans anahtarının geçerli olup olmadığını (status:3) kontrol eder.
     */
    public function checkLicense(): bool
    {
        // Cache'i kontrol et, varsa doğrudan sonucu dön.
        if (cache('license_status') !== null) {
            return cache('license_status');
        }

        // checkLicense sadece true/false döndürdüğü için, 
        // detaylı bilgi için getLicenseInfo'yu çağırıp sonucu yorumlayalım.
        $licenseInfo = $this->getLicenseInfo(true); // true parametresi cache'i atlayıp yeniden kontrol etmeye zorlar

        $free = 0; // ücretsiz sürüm kontrolü (1 ise ücretsiz sürüm, 0 ise değil)
        $isActive = ($free == 1) ||  ($licenseInfo !== null && isset($licenseInfo['status']) && $licenseInfo['status'] == 3);

        cache()->save('license_status', $isActive, 3600);
        return $isActive;
    }

    /**
     * Lisansla ilgili tüm detayları API'den alır ve önbelleğe kaydeder.
     * @param bool $forceRefresh Cache'i görmezden gelip API'den yeniden veri çekmeye zorlar.
     * @return array|null Lisans verisi varsa array, yoksa null döner.
     */
    public function getLicenseInfo(bool $forceRefresh = false): ?array
    {
        if (!$forceRefresh && $cachedData = cache('license_data')) {
            return $cachedData;
        }

        $db = db_connect();
        $licenseKey = $db->table('app_settings')->where('key', 'license_key')->get()->getRow()->value ?? null;
        if (empty($licenseKey)) {
            return null;
        }

        try {
            $client = Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
            $apiUrl = 'https://mantaryazilim.tr/wp-json/lmfwc/v2/licenses/' . $licenseKey;
            $apiKey = 'ck_190841e904fe8ed93c976f447cb8cacc178e523e';
            $apiSecret = 'cs_732e4fe8d890fa1a1bff50652369e6b072909e81';

            $response = $client->request('GET', $apiUrl, ['auth' => [$apiKey, $apiSecret, 'basic']]);
            
            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody(), true);
                if (isset($body['success']) && $body['success'] === true) {
                    cache()->save('license_data', $body['data'], 3600 * 24);
                    return $body['data'];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'WooCommerce Lisans API Hatası: ' . $e->getMessage());
        }
        
        cache()->delete('license_data');
        return null;
    }

    /**
     * Lisansın bitmesine kaç gün kaldığını hesaplar.
     */
    public function getDaysRemaining(): ?int
    {
        $info = $this->getLicenseInfo();
        if (isset($info['expiresAt'])) {
            $expireDate = new \DateTime($info['expiresAt']);
            $now = new \DateTime();
            if ($now > $expireDate) return 0;
            return $now->diff($expireDate)->days;
        }
        return null;
    }
}