<?php

namespace App\Listeners;

use App\Models\PushSubscriptionModel;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

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
    public function handleAnnouncementPublished(array $announcement)
    {
        // LOGLAMA: Şimdilik olayın tetiklendiğini görmek için log tutalım.
        // Gerçek bildirim gönderme kodu PWA entegrasyonu aşamasında buraya eklenecek.
        log_message(
            'info',
            "[NotificationListener] Duyuru yayınlandı. Bildirim gönderilecek. ID: {id}, Başlık: {title}, Hedef: {target_group}",
            $announcement
        );

        // --- GELECEKTE EKLENECEK KOD ---
        // 1. $announcement['target_group']'a göre hedef kullanıcıları bul.
        // 2. Bu kullanıcıların PWA aboneliklerini veritabanından çek.
        // 3. Her bir aboneye Web Push kütüphanesi ile bildirim gönder.
        //    Payload (içerik) olarak duyurunun başlığını ve linkini içerecek.
    }
}