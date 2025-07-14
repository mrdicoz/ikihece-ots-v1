<?php

namespace App\Listeners;

class NotificationListener
{
    /**
     * 'schedule.changed' olayı tetiklendiğinde bu metot çalışacak.
     *
     * @param array $lessonData Ders ile ilgili temel bilgiler
     * @param int   $teacherId  Dersi etkilenen öğretmenin ID'si
     */
    public function handleScheduleChange($lessonData, $teacherId)
    {
        // Şimdilik sadece log tutuyoruz.
        // İlerleyen adımlarda burayı dolduracağız:
        // 1. $teacherId'ye ait push abonelik bilgilerini veritabanından (PushSubscriptionModel) bul.
        // 2. Bildirim içeriğini oluştur (örn: "Yeni bir dersiniz var!").
        // 3. Bildirim gönderme kütüphanesini kullanarak bildirimi gönder.
        
        log_message('info', 'Schedule change event triggered for teacher ID: ' . $teacherId);
    }
}