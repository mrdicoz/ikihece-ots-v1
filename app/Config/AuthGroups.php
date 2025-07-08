<?php

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Varsayılan Grup
     * --------------------------------------------------------------------
     * Sisteme yeni kaydolan bir kullanıcının otomatik olarak atanacağı grup.
     * Bizim projemizde bu 'veli' olacak.
     */
    public string $defaultGroup = 'veli';

    /**
     * --------------------------------------------------------------------
     * Gruplar (Projemizin Mimari Planı)
     * --------------------------------------------------------------------
     * Sistemde var olabilecek tüm grupları burada tanımlıyoruz.
     * Bu, veritabanına kayıt eklemez, sadece sisteme "bu isimde gruplar olabilir" der.
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Sistemin tam kontrolüne sahip olan kullanıcı.',
        ],
        'yonetici' => [
            'title'       => 'Yönetici',
            'description' => 'Kurum yöneticisi. Öğrenci, öğretmen ve veli bilgilerini yönetir.',
        ],
        'mudur' => [
            'title'       => 'Müdür',
            'description' => 'Okul müdürü. Raporları ve genel işleyişi görüntüler.',
        ],
        'sekreter' => [
            'title'       => 'Sekreter',
            'description' => 'Öğrenci kayıtları ve ders programı gibi ofis işlemlerini yönetir.',
        ],
        'ogretmen' => [
            'title'       => 'Öğretmen',
            'description' => 'Sadece kendi öğrencilerini ve ders programını yönetir.',
        ],
        'servis' => [
            'title'       => 'Servis',
            'description' => 'Sadece servis listesindeki öğrencilerin iletişim ve adres bilgilerini görür.',
        ],
        'veli' => [
            'title'       => 'Veli',
            'description' => 'Sadece kendi öğrencisiyle ilgili bilgileri görebilen standart kullanıcı.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * İzinler (Binadaki Odaların Anahtarları)
     * --------------------------------------------------------------------
     * Sistemde hangi işlemlerin yapılabileceğini burada tanımlıyoruz.
     * Her bir izin, bir kapının anahtarı gibidir.
     */
    public array $permissions = [
        'kullanicilar.yonet'    => 'Kullanıcı ekleme, silme ve düzenleme.',
        'ogrenciler.listele'    => 'Tüm öğrencileri listeleme.',
        'ogrenciler.detay'      => 'Öğrenci detaylarını görme.',
        'ogrenciler.ekle'       => 'Yeni öğrenci ekleme.',
        'ogrenciler.duzenle'    => 'Öğrenci bilgilerini düzenleme.',
        'ogrenciler.sil'        => 'Öğrenci kaydını silme.',
        'dersprogrami.yonet'    => 'Tüm ders programlarını yönetme.',
        'dersprogrami.goruntule' => 'Ders programlarını sadece görüntüleme.',
        'servis.listele'        => 'Servis listesini görüntüleme.',
        'ayarlar.yonet'         => 'Sistem ve kurum ayarlarını yönetme.',
    ];

    /**
     * --------------------------------------------------------------------
     * İzin Matrisi (Anahtarların Kimde Olduğu)
     * --------------------------------------------------------------------
     * Hangi grubun, yukarıda tanımlanan hangi izinlere (anahtarlara)
     * sahip olacağını burada belirliyoruz. '*' karakteri, o kategorideki tüm izinleri kapsar.
     */
    public array $matrix = [
        // Admin tüm anahtarlara sahiptir.
        'admin' => [
            'kullanicilar.*',
            'ogrenciler.*',
            'dersprogrami.*',
            'servis.*',
            'ayarlar.*',
        ],
        // Yönetici, sistem ayarları ve kullanıcı yönetimi hariç çoğu şeye erişebilir.
        'yonetici' => [
            'ogrenciler.*',
            'dersprogrami.yonet',
            'servis.listele',
        ],
        // Müdür, kritik işlemler yapamaz ama çoğu şeyi görür.
        'mudur' => [
            'ogrenciler.listele',
            'ogrenciler.detay',
            'dersprogrami.goruntule',
            'servis.listele',
        ],
        // Sekreter, öğrenci ve ders programı yönetiminden sorumlu.
        'sekreter' => [
            'ogrenciler.listele',
            'ogrenciler.detay',
            'ogrenciler.ekle',
            'ogrenciler.duzenle',
            'dersprogrami.yonet',
            'servis.listele',
        ],
        // Öğretmen, sadece kendi programını görür.
        'ogretmen' => [
            'dersprogrami.goruntule',
        ],
        // Servis, sadece servis listesini görür.
        'servis' => [
            'servis.listele',
        ],
        // Veli'nin şimdilik özel bir izni yok.
        'veli' => [],
    ];
}