<?php

namespace App\Listeners;

use App\Models\PushSubscriptionModel;
use CodeIgniter\Shield\Models\UserModel; // Shield'in UserModel'ini kullanacağız
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Throwable;


class NotificationListener
{
    /**
     * Belirtilen kullanıcıya web push bildirimi gönderir.
     *
     * @param int    $userId      Bildirim gönderilecek kullanıcının ID'si
     * @param string $title       Bildirim başlığı
     * @param string $body        Bildirim içeriği
     * @param array $announcement Yayınlanan duyurunun veritabanı satırı (dizi olarak)

     */
    public function handleScheduleChange(int $userId, string $title, string $body)
    {
        $subscriptionModel = new PushSubscriptionModel();
        $subscriptions = $subscriptionModel->where('user_id', $userId)->findAll();

        if (empty($subscriptions)) {
            // Kullanıcının hiç aboneliği yoksa işlem yapma
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => 'mailto:info@mantaryazilim.tr', // Projenize uygun bir e-posta
                'publicKey' => env('vapid.publicKey'),      // .env dosyasından okunacak
                'privateKey' => env('vapid.privateKey'),   // .env dosyasından okunacak
            ],
        ];

        $webPush = new WebPush($auth);
        $payload = json_encode(['title' => $title, 'body' => $body]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
            ]);
            $webPush->queueNotification($subscription, $payload);
        }

        // Bildirimleri gönder ve sonuçları logla (opsiyonel)
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if (!$report->isSuccess()) {
                log_message('error', "Bildirim hatası [{$endpoint}]: {$report->getReason()}");
                // Süresi dolmuş abonelikleri sil
                if ($report->isSubscriptionExpired()) {
                    $subscriptionModel->where('endpoint', $endpoint)->delete();
                }
            }
        }
    }
    /**
     * Bir duyuru yayınlandığında bu metod çalışır ve bildirim gönderir.
     *
     * @param array $announcement Yayınlanan duyurunun veritabanı satırı (dizi olarak)
     */
    public function handleAnnouncementPublished(array $announcement)
    {
        $pushSubscriptionModel = new PushSubscriptionModel();
        $userModel = new UserModel();

        // 1. Hedef Kullanıcı ID'lerini Bul
        $targetUserIds = [];
        $targetGroup = $announcement['target_group'];

        if ($targetGroup === 'all') {
            // 'all' ise tüm aktif kullanıcıları al
            $allUsers = $userModel->select('id')->where('active', 1)->findAll();
            $targetUserIds = array_column($allUsers, 'id');
        } else {
            // Belirli bir gruba ait kullanıcıları bul
            $db = db_connect();
            $builder = $db->table('auth_groups_users');
            $usersInGroup = $builder->select('user_id')->where('group', $targetGroup)->get()->getResultArray();
            $targetUserIds = array_column($usersInGroup, 'user_id');
        }

        if (empty($targetUserIds)) {
            log_message('info', 'Duyuru bildirimi için hedef kullanıcı bulunamadı. Grup: ' . $targetGroup);
            return;
        }

        // 2. Bu kullanıcıların bildirim aboneliklerini al
        $subscriptions = $pushSubscriptionModel->whereIn('user_id', $targetUserIds)->findAll();

        if (empty($subscriptions)) {
            log_message('info', 'Duyuru bildirimi için kayıtlı abonelik bulunamadı. Grup: ' . $targetGroup);
            return;
        }

        // 3. Bildirim içeriğini hazırla
        // Not: Kullanıcıları duyurular listesine yönlendireceğiz, orada en son duyuruyu görebilirler.
        $payload = json_encode([
            'title' => 'Yeni Duyuru: ' . $announcement['title'],
            'body'  => mb_substr(strip_tags($announcement['body']), 0, 150) . '...',
            'icon'  => base_url('assets/images/favicon-192x192.png'),
            'data'  => ['url' => route_to('announcements.index')] 

        ]);

        try {
            $auth = [
                'VAPID' => [
                    'subject'    => 'mailto:' . env('app.fromEmail', 'info@ikihece.com'), // .env'den al, yoksa varsayılan kullan
                    'publicKey'  => env('vapid.publicKey'),
                    'privateKey' => env('vapid.privateKey'),
                ],
            ];

            $webPush = new WebPush($auth);
            $webPush->setReuseVAPIDHeaders(true); // Performans artışı için

            foreach ($subscriptions as $sub) {
                // Modelden gelen veri object ise array'e çevir
                $subArray = is_object($sub) ? (array)$sub : $sub;

                $subscription = Subscription::create([
                    'endpoint'  => $subArray['endpoint'],
                    'publicKey' => $subArray['p256dh'],
                    'authToken' => $subArray['auth'],
                ]);
                $webPush->queueNotification($subscription, $payload);
            }

            // Bildirimleri gönder
            $sentCount = 0;
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sentCount++;
                } else {
                    log_message('error', 'Duyuru Bildirim Hatası: ' . $report->getReason() . ' | Endpoint: ' . $report->getEndpoint());
                    if ($report->isSubscriptionExpired()) {
                        $pushSubscriptionModel->where('endpoint', $report->getEndpoint())->delete();
                    }
                }
            }
            log_message('info', $sentCount . ' kullanıcıya duyuru bildirimi başarıyla gönderildi.');

        } catch (Throwable $e) {
            log_message('error', '[NotificationListener] Kritik Hata: ' . $e->getFile() . ' Satır: ' . $e->getLine() . ' Mesaj: ' . $e->getMessage());
        }
    }
}