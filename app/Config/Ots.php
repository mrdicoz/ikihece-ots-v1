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
}