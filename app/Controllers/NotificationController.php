<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PushSubscriptionModel;
use Minishlink\WebPush\Subscription;  // Bu satır var mı?
use Minishlink\WebPush\WebPush;       // Bu satır var mı?
use CodeIgniter\API\ResponseTrait; // ÖNEMLİ: ResponseTrait'i tekrar ekliyoruz.

class NotificationController extends BaseController
{
    use ResponseTrait; // ÖNEMLİ: Trait'i kullanıma alıyoruz.

    /**
     * .env dosyasındaki VAPID public key'i JSON olarak döndürür.
     */
    public function getVapidKey()
    {
        if (!auth()->loggedIn()) {
            return $this->failUnauthorized('Yetkisiz Erişim');
        }

        $publicKey = env('vapid.publicKey');

        if (empty($publicKey)) {
            log_message('error', 'VAPID public anahtarı .env dosyasında tanımlı değil.');
            return $this->failServerError('Sunucu yapılandırma hatası.');
        }

        return $this->respond(['publicKey' => $publicKey]);
    }

    /**
     * Kullanıcıdan gelen abonelik isteğini kaydeder.
     */
    public function saveSubscription()
    {
        // Güvenlik: Sadece giriş yapmış kullanıcılar abone olabilir.
        if (!auth()->loggedIn()) {
            return $this->failUnauthorized('Abonelik için giriş yapmalısınız.');
        }

        $subscriptionData = $this->request->getJSON(true);

        // Gelen veriyi doğrula
        if (empty($subscriptionData['endpoint'])) {
            return $this->fail('Abonelik bilgileri eksik.', 400);
        }

        $model = new PushSubscriptionModel();

        // Aynı endpoint ile daha önce kayıt olunmuş mu diye kontrol et
        $existing = $model->where('endpoint', $subscriptionData['endpoint'])->first();
        if ($existing) {
            // Eğer abonelik başka bir kullanıcıya aitse, sahibini güncelleyebiliriz.
            // Ama şimdilik sadece mevcut olduğunu belirtelim.
            return $this->respond(['success' => true, 'message' => 'Bu cihaz zaten abone.'], 200);
        }

        // Yeni aboneliği, GÜNCEL KULLANICI ID'Sİ ile kaydet
        $dataToSave = [
            'user_id'  => auth()->id(), // Dinamik olarak mevcut kullanıcı ID'sini alıyoruz
            'endpoint' => $subscriptionData['endpoint'],
            'p256dh'   => $subscriptionData['keys']['p256dh'],
            'auth'     => $subscriptionData['keys']['auth'],
        ];

        if ($model->save($dataToSave)) {
            return $this->respondCreated(['success' => true, 'message' => 'Abonelik başarıyla oluşturuldu.']);
        }

        return $this->fail('Abonelik veritabanına kaydedilemedi.', 500);
    }

public function sendManualNotification()
{
    if (! $this->request->isAJAX()) {
        return $this->response->setStatusCode(403, 'Forbidden');
    }

    $teacherIds = $this->request->getPost('teacher_ids');

    if (empty($teacherIds) || !is_array($teacherIds)) {
        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Geçersiz veya boş öğretmen seçimi yapıldı.']);
    }

    $payload = json_encode([
        'title' => 'Ders Programı Güncellemesi',
        'body'  => 'Yönetim tarafından programınızda bir güncelleme yapılmıştır. Lütfen kontrol ediniz.',
        'icon'  => base_url('assets/images/favicon-192x192.png'),
        'data'  => ['url' => route_to('schedule.my')] 
    ]);

    $pushSubscriptionModel = new PushSubscriptionModel();
    $subscriptions = $pushSubscriptionModel->whereIn('user_id', $teacherIds)->asArray()->findAll();

    if (empty($subscriptions)) {
         return $this->response->setJSON(['success' => true, 'message' => 'Seçilen öğretmen(ler) için kayıtlı bir bildirim aboneliği bulunamadı.']);
    }

    try {
        $auth = [
            'VAPID' => [
                'subject'    => 'mailto:' . env('app.fromEmail'), // getenv yerine env() kullanıldı
                'publicKey'  => env('vapid.publicKey'),    // getenv yerine env() kullanıldı
                'privateKey' => env('vapid.privateKey'),   // getenv yerine env() kullanıldı
            ],
        ];

        $webPush = new WebPush($auth);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub['endpoint'],
                'publicKey'       => $sub['p256dh'],
                'authToken'       => $sub['auth'],
                'contentEncoding' => 'aesgcm'
            ]);
            $webPush->queueNotification($subscription, $payload);
        }

        $sentCount = 0;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sentCount++;
            } else {
                log_message('error', 'Bildirim Gönderim Hatası: ' . $report->getReason() . ' | Endpoint: ' . $report->getEndpoint());
                
                // **** İŞTE DÜZELTİLEN KISIM ****
                // Hatalı getStatusCode() çağrısı kaldırıldı.
                if ($report->isSubscriptionExpired()) {
                    $pushSubscriptionModel->where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }
        
        return $this->response->setJSON([
            'success' => true, 
            'message' => $sentCount . ' öğretmene bildirim başarıyla gönderildi.'
        ]);

    } catch (\Throwable $e) {
        log_message('error', '[NotificationController] ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Error: ' . $e->getMessage());
        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Bildirimler gönderilirken kritik bir hata oluştu.']);
    }
}

    // app/Controllers/NotificationController.php

    public function unsubscribe()
    {
        $subscriptionData = $this->request->getJSON();
        if (empty($subscriptionData->endpoint)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Geçersiz endpoint.']);
        }

        $pushSubscriptionModel = new \App\Models\PushSubscriptionModel();

        // Endpoint'e göre aboneliği bul ve sil
        $deleted = $pushSubscriptionModel->where('endpoint', $subscriptionData->endpoint)->delete();

        if ($deleted) {
            return $this->response->setJSON(['message' => 'Abonelik başarıyla kaldırıldı.']);
        }

        return $this->response->setJSON(['message' => 'Abonelik bulunamadı veya zaten kaldırılmış.']);
    }
}