<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PushSubscriptionModel;
use CodeIgniter\API\ResponseTrait;

class NotificationController extends BaseController
{
    use ResponseTrait;

    public function __construct()
    {
        // Bu satır, her işlemden önce kimlik doğrulaması yapılmasını sağlar.
        // Şimdilik test için kapalı tutabiliriz, sonra açacağız.
        // $this->middleware('auth');
    }

    /**
     * Public VAPID anahtarını döndürür.
     */
    public function getVapidKey()
    {
        return $this->respond([
            'publicKey' => getenv('VAPID_PUBLIC_KEY')
        ]);
    }

    /**
     * Kullanıcıdan gelen abonelik isteğini kaydeder.
     */
    public function saveSubscription()
    {
        $subscriptionData = $this->request->getJSON(true);

        // Basit bir doğrulama
        if (empty($subscriptionData['endpoint'])) {
            return $this->fail('Abonelik bilgileri eksik.');
        }

        $model = new PushSubscriptionModel();

        // Aynı endpoint ile daha önce kayıt olunmuş mu diye kontrol et
        $existing = $model->where('endpoint', $subscriptionData['endpoint'])->first();

        if ($existing) {
            return $this->respond(['message' => 'Bu cihaz zaten abone.'], 200);
        }

        // Yeni aboneliği kaydet
        $dataToSave = [
            // Gerçek kullanıcı ID'sini session'dan alacağız. Şimdilik test için 1 yazıyoruz.
            'user_id'  => 1,
            'endpoint' => $subscriptionData['endpoint'],
            'p256dh'   => $subscriptionData['keys']['p256dh'],
            'auth'     => $subscriptionData['keys']['auth'],
        ];

        if ($model->save($dataToSave)) {
            return $this->respondCreated(['message' => 'Abonelik başarıyla oluşturuldu.']);
        }

        return $this->fail('Abonelik kaydedilemedi.', 500);
    }
    /**
     * Test amaçlı bildirim gönderir.
     * Bu metodu sadece admin kullanıcılar kullanabilir.
     */
    public function sendTestNotification()
    {
        $subscriptionModel = new \App\Models\PushSubscriptionModel();
        $subscriptions = $subscriptionModel->findAll();

        if (empty($subscriptions)) {
            return "Gönderilecek abone bulunamadı.";
        }

        // VAPID anahtarlarını .env dosyasından al
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@mantaryazilim.tr', // Buraya kendi e-posta adresinizi yazın
                'publicKey' => getenv('VAPID_PUBLIC_KEY'),
                'privateKey' => getenv('VAPID_PRIVATE_KEY'),
            ],
        ];

        // Kütüphaneyi kullanarak WebPush nesnesi oluştur
        $webPush = new \Minishlink\WebPush\WebPush($auth);

        // Gönderilecek bildirimin içeriği
        $payload = json_encode([
            'title' => 'Test Bildirimi',
            'body' => 'Bu bildirim, sistemin çalıştığını test etmek için gönderilmiştir.',
            'icon' => '/images/icon.png',
        ]);

        // Her bir aboneye bildirimi kuyruğa ekle
        foreach ($subscriptions as $sub) {
            $subscriptionObject = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $sub['endpoint'],
                'publicKey' => $sub['p256dh'],
                'authToken' => $sub['auth'],
            ]);
            $webPush->queueNotification($subscriptionObject, $payload);
        }

        echo "Bildirimler " . count($subscriptions) . " aboneye gönderilmek üzere kuyruğa eklendi.<br>";

        // Kuyruktaki tüm bildirimleri gönder
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                echo "[v] Mesaj başarıyla {$endpoint} adresine gönderildi.<br>";
            } else {
                echo "[x] Mesaj {$endpoint} adresine gönderilemedi: {$report->getReason()}<br>";
                // İsteğe bağlı: Geçersiz abonelikleri buradan silebilirsiniz
                // $subscriptionModel->where('endpoint', $endpoint)->delete();
            }
        }
        
        echo "İşlem tamamlandı.";
    }
}