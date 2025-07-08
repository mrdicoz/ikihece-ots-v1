<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Bu ana seeder, 'php spark db:seed' komutu çalıştırıldığında tetiklenir
     * ve diğer tüm seeder'ları sırayla çağırır.
     */
    public function run()
    {
        // Yabancı anahtar (foreign key) kontrollerini geçici olarak devre dışı bırakıyoruz.
        // Bu, tabloları truncate ederken (içeriğini tamamen silerken) referans hataları almamızı engeller.
        $this->db->disableForeignKeyChecks();

        // --- TABLOLARI TEMİZLEME BÖLÜMÜ ---
        // Seeder'ları her çalıştırdığımızda verilerin tekrar tekrar eklenmesini önlemek için
        // ilgili tabloları önce temizliyoruz. Sıralama önemlidir, önce bağımlı tablolar temizlenir.

        // Önce bağımlı tabloyu temizle
        $this->db->table('districts')->truncate();
        // Sonra ana tabloyu temizle
        $this->db->table('cities')->truncate();


        // Yabancı anahtar kontrollerini tekrar aktif ediyoruz.
        $this->db->enableForeignKeyChecks();

        // --- SEEDER'LARI ÇAĞIRMA BÖLÜMÜ ---
        // Verileri tablolara eklemek için ilgili seeder dosyalarını çağırıyoruz.
        // this->call() metodu, belirtilen seeder sınıfının run() metodunu çalıştırır.

        $this->call('CitiesSeeder');         // Şehirleri ekler.
        $this->call('DistrictsSeeder');      // İlçeleri ekler.
    }
}