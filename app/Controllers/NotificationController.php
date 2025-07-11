<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PushSubscriptionModel;

class NotificationController extends BaseController
{
    /**
     * Kullanıcının tarayıcısından gelen abonelik bilgisini kaydeder.
     */
    public function saveSubscription()
    {
        // PWA/Service Worker tarafından gönderilen abonelik bilgilerini
        // alıp PushSubscriptionModel aracılığıyla veritabanına kaydedecek.
    }
}