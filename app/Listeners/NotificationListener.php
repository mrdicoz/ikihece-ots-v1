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
}