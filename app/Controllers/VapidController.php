<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;

class VapidController extends BaseController
{
    public function generateKeys()
    {
        // Bu rotanın sadece geliştirme ortamında çalıştığından emin ol
        if (ENVIRONMENT !== 'development') {
            return redirect()->to('/')->with('error', 'Bu işlem sadece geliştirme ortamında yapılabilir.');
        }

        try {
            // VAPID anahtarlarını oluştur
            $algorithmManager = new AlgorithmManager([new ES256()]);
            $jwk = JWKFactory::createECKey('P-256', ['alg' => 'ES256', 'use' => 'sig']);
            
            // JWK'yi JSON formatına çevirip base64 ile kodla
            $publicKey = base64_encode(json_encode($jwk->toPublic()->all()));
            $privateKey = base64_encode(json_encode($jwk->all()));

            // Görüntüyü hazırla
            $output = "<h1>VAPID Anahtarları Başarıyla Oluşturuldu!</h1>";
            $output .= "<p>Aşağıdaki satırları kopyalayıp projenizin ana dizinindeki <strong>.env</strong> dosyanıza yapıştırın:</p>";
            $output .= "<pre style='background-color:#f0f0f0; padding:15px; border-radius:5px; border:1px solid #ccc;'>";
            $output .= "vapid.publicKey = \"{$publicKey}\"\n";
            $output .= "vapid.privateKey = \"{$privateKey}\"";
            $output .= "</pre>";
            $output .= "<p style='color:red;'><strong>ÖNEMLİ:</strong> Bu anahtarları .env dosyasına ekledikten sonra güvenlik için <strong>app/Config/Routes.php</strong> dosyasından <code>/generate-keys</code> rotasını ve bu controller dosyasını (VapidController.php) silin.</p>";
            
            return $output;

        } catch (\Throwable $e) {
            // Hata olursa, hatayı daha anlaşılır göster
            return "<h1>Hata Oluştu!</h1><p>VAPID anahtarları oluşturulurken bir hata meydana geldi:</p><pre>" . $e->getMessage() . "<br>" . $e->getTraceAsString() . "</pre>";
        }
    }
}