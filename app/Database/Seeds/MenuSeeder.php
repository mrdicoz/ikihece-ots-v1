<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // Önce grupları oluştur
        $groups = [
            ['name' => 'ogrenci_yonetimi', 'title' => 'Öğrenci Yönetimi', 'icon' => 'backpack2', 'order' => 1, 'active' => 1],
            ['name' => 'egitim_yonetimi', 'title' => 'Eğitim Yönetimi', 'icon' => 'calendar3', 'order' => 2, 'active' => 1],
            ['name' => 'yapay_zeka', 'title' => 'Yapay Zeka', 'icon' => 'robot', 'order' => 3, 'active' => 1],
            ['name' => 'duyurular', 'title' => 'Duyurular', 'icon' => 'megaphone', 'order' => 4, 'active' => 1],
            ['name' => 'sistem_yonetimi', 'title' => 'Sistem Yönetimi', 'icon' => 'sliders', 'order' => 5, 'active' => 1],
            ['name' => 'iletisim', 'title' => 'İletişim', 'icon' => 'chat-dots', 'order' => 6, 'active' => 1],
        ];

        $this->db->table('menu_groups')->insertBatch($groups);

        // Menü öğelerini oluştur
        $items = [
            // Öğrenci Yönetimi
            ['group_id' => 1, 'parent_id' => null, 'title' => 'Öğrenci Yönetimi', 'route_name' => 'students', 'icon' => 'backpack2', 'order' => 1, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 1, 'parent_id' => null, 'title' => 'Öğrencilerim', 'route_name' => 'students.my', 'icon' => 'people', 'order' => 2, 'active' => 1, 'is_dropdown' => 0],

            // Ders Programı (Dropdown)
            ['group_id' => 2, 'parent_id' => null, 'title' => 'Ders Programı', 'route_name' => null, 'icon' => 'calendar3', 'order' => 1, 'active' => 1, 'is_dropdown' => 1],
            ['group_id' => 2, 'parent_id' => 3, 'title' => 'Program Oluştur', 'route_name' => 'schedule.index', 'icon' => 'calendar-plus', 'order' => 1, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 2, 'parent_id' => 3, 'title' => 'Sabitler', 'route_name' => 'admin.fixed_schedule.index', 'icon' => 'pin-angle-fill', 'order' => 2, 'active' => 1, 'is_dropdown' => 0],
            
            ['group_id' => 2, 'parent_id' => null, 'title' => 'Ders Programım', 'route_name' => 'schedule.my', 'icon' => 'calendar-week', 'order' => 2, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 2, 'parent_id' => null, 'title' => 'Çocuğumun Programı', 'route_name' => 'parent.schedule', 'icon' => 'calendar-check', 'order' => 3, 'active' => 1, 'is_dropdown' => 0],

            // Yapay Zeka
            ['group_id' => 3, 'parent_id' => null, 'title' => 'Yapay Zeka Asistanı', 'route_name' => 'ai.assistant', 'icon' => 'robot', 'order' => 1, 'active' => 1, 'is_dropdown' => 0],

            // Duyurular
            ['group_id' => 4, 'parent_id' => null, 'title' => 'Duyurular', 'route_name' => 'announcements.index', 'icon' => 'megaphone', 'order' => 1, 'active' => 1, 'is_dropdown' => 0],

            // Sistem Yönetimi (Dropdown - Sadece Admin)
            ['group_id' => 5, 'parent_id' => null, 'title' => 'Sistem Yönetimi', 'route_name' => null, 'icon' => 'sliders', 'order' => 1, 'active' => 1, 'is_dropdown' => 1],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Kullanıcılar', 'route_name' => 'admin.users.index', 'icon' => 'people', 'order' => 1, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Kurum Ayarları', 'route_name' => 'admin.institution.index', 'icon' => 'building', 'order' => 2, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Aylık Raporlar', 'route_name' => 'admin.reports.monthly', 'icon' => 'bar-chart-fill', 'order' => 3, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Atamalar', 'route_name' => 'admin.assignments.index', 'icon' => 'person-rolodex', 'order' => 4, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Duyuru Yap', 'route_name' => 'admin.announcements.index', 'icon' => 'megaphone', 'order' => 5, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Menü Yönetimi', 'route_name' => 'admin.menu.index', 'icon' => 'list-nested', 'order' => 6, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Sistemi Güncelle', 'route_name' => 'admin.update.index', 'icon' => 'cloud-arrow-down-fill', 'order' => 7, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Log Kayıtları', 'route_name' => 'admin.logs.index', 'icon' => 'journal-text', 'order' => 8, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Model Eğitimi', 'route_name' => 'admin.ai.trainer', 'icon' => 'robot', 'order' => 9, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'İçe Aktar', 'route_name' => 'admin.students.importView', 'icon' => 'cloud-upload-fill', 'order' => 10, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Ders Hakları Yükle', 'route_name' => 'admin.entitlements.import', 'icon' => 'person-check', 'order' => 11, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 5, 'parent_id' => 10, 'title' => 'Genel Ayarlar', 'route_name' => 'admin.settings.index', 'icon' => 'gear-wide-connected', 'order' => 12, 'active' => 1, 'is_dropdown' => 0],

            // İletişim (Veli için)
            ['group_id' => 6, 'parent_id' => null, 'title' => 'Mesajlar', 'route_name' => null, 'url' => '#', 'icon' => 'chat-dots-fill', 'order' => 1, 'active' => 1, 'is_dropdown' => 0],
            ['group_id' => 6, 'parent_id' => null, 'title' => 'Servis', 'route_name' => null, 'url' => '#', 'icon' => 'bus-front', 'order' => 2, 'active' => 1, 'is_dropdown' => 0],
        ];

        $this->db->table('menu_items')->insertBatch($items);

        // Menü yetkilerini oluştur
        $permissions = [
            // Öğrenci Yönetimi - admin, yonetici, mudur, sekreter
            ['menu_item_id' => 1, 'role' => 'admin'],
            ['menu_item_id' => 1, 'role' => 'yonetici'],
            ['menu_item_id' => 1, 'role' => 'mudur'],
            ['menu_item_id' => 1, 'role' => 'sekreter'],

            // Öğrencilerim - ogretmen
            ['menu_item_id' => 2, 'role' => 'ogretmen'],

            // Ders Programı Dropdown - admin, yonetici, mudur, sekreter
            ['menu_item_id' => 3, 'role' => 'admin'],
            ['menu_item_id' => 3, 'role' => 'yonetici'],
            ['menu_item_id' => 3, 'role' => 'mudur'],
            ['menu_item_id' => 3, 'role' => 'sekreter'],
            
            // Program Oluştur - admin, yonetici, mudur, sekreter
            ['menu_item_id' => 4, 'role' => 'admin'],
            ['menu_item_id' => 4, 'role' => 'yonetici'],
            ['menu_item_id' => 4, 'role' => 'mudur'],
            ['menu_item_id' => 4, 'role' => 'sekreter'],

            // Sabitler - admin, yonetici, mudur, sekreter
            ['menu_item_id' => 5, 'role' => 'admin'],
            ['menu_item_id' => 5, 'role' => 'yonetici'],
            ['menu_item_id' => 5, 'role' => 'mudur'],
            ['menu_item_id' => 5, 'role' => 'sekreter'],

            // Ders Programım - ogretmen
            ['menu_item_id' => 6, 'role' => 'ogretmen'],

            // Çocuğumun Programı - veli
            ['menu_item_id' => 7, 'role' => 'veli'],

            // Yapay Zeka - admin, yonetici, mudur, sekreter, ogretmen
            ['menu_item_id' => 8, 'role' => 'admin'],
            ['menu_item_id' => 8, 'role' => 'yonetici'],
            ['menu_item_id' => 8, 'role' => 'mudur'],
            ['menu_item_id' => 8, 'role' => 'sekreter'],
            ['menu_item_id' => 8, 'role' => 'ogretmen'],

            // Duyurular - herkes
            ['menu_item_id' => 9, 'role' => 'admin'],
            ['menu_item_id' => 9, 'role' => 'yonetici'],
            ['menu_item_id' => 9, 'role' => 'mudur'],
            ['menu_item_id' => 9, 'role' => 'sekreter'],
            ['menu_item_id' => 9, 'role' => 'ogretmen'],
            ['menu_item_id' => 9, 'role' => 'veli'],

            // Sistem Yönetimi Dropdown - sadece admin
            ['menu_item_id' => 10, 'role' => 'admin'],
            
            // Tüm sistem yönetimi alt öğeleri - sadece admin
            ['menu_item_id' => 11, 'role' => 'admin'], // Kullanıcılar
            ['menu_item_id' => 12, 'role' => 'admin'], // Kurum Ayarları
            ['menu_item_id' => 13, 'role' => 'admin'], // Aylık Raporlar
            ['menu_item_id' => 14, 'role' => 'admin'], // Atamalar
            ['menu_item_id' => 15, 'role' => 'admin'], // Duyuru Yap
            ['menu_item_id' => 16, 'role' => 'admin'], // Menü Yönetimi
            ['menu_item_id' => 17, 'role' => 'admin'], // Sistemi Güncelle
            ['menu_item_id' => 18, 'role' => 'admin'], // Log Kayıtları
            ['menu_item_id' => 19, 'role' => 'admin'], // Model Eğitimi
            ['menu_item_id' => 20, 'role' => 'admin'], // İçe Aktar
            ['menu_item_id' => 21, 'role' => 'admin'], // Ders Hakları Yükle
            ['menu_item_id' => 22, 'role' => 'admin'], // Genel Ayarlar

            // Mesajlar - veli
            ['menu_item_id' => 23, 'role' => 'veli'],

            // Servis - veli
            ['menu_item_id' => 24, 'role' => 'veli'],
        ];

        $this->db->table('menu_permissions')->insertBatch($permissions);

        echo "Menü yapısı başarıyla oluşturuldu.\n";
    }
}