<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Ots extends BaseConfig
{
    /**
     * Sistemde kullanılabilecek tüm kullanıcı grupları.
     * Anahtar => Görünen İsim
     */
    public array $availableGroups = [
        'admin'     => 'Admin',
        'yonetici'  => 'Yönetici',
        'mudur'     => 'Müdür',
        'sekreter'  => 'Sekreter',
        'ogretmen'  => 'Öğretmen',
        'servis'    => 'Servis',
        'veli'      => 'Veli',
    ];

    /**
     * Ders programı grid'inin başlangıç saati (24 saat formatında)
     */
    public int $scheduleStartHour = 10;

    /**
     * Ders programı grid'inin bitiş saati (24 saat formatında)
     * Döngü bu saatten küçük olana kadar çalışır, yani 18 ise son saat 17:00 olur.
     */
    public int $scheduleEndHour = 18;
}