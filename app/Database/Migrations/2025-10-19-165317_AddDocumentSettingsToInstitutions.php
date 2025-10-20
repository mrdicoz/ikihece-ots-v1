<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocumentSettingsToInstitutions extends Migration
{
    public function up()
    {
        $fields = [
            // DOKÜMAN NUMARALANDIRMA ALANLARI
            'evrak_prefix' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Evrak numarası öneki (örn: SRGM-2025-)',
                'after' => 'longitude' // Mevcut tablonun sonuna eklemek için
            ],
            'evrak_baslangic_no' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1000,
                'comment' => 'Evrak numarası başlangıç değeri',
                'after' => 'evrak_prefix'
            ],
            // YENİ SABİT ALANLAR
            'kurum_muduru_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Kurum müdürü olarak seçilen kullanıcının IDsi',
                'after' => 'evrak_baslangic_no'
            ],
            'kurum_muduru_adi' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'Manuel girilen kurum müdürü adı',
                'after' => 'kurum_muduru_user_id'
            ],
            'kurucu_mudur_adi' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'Manuel girilen kurucu müdür adı',
                'after' => 'kurum_muduru_adi'
            ],
            'kurum_logo_path' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'Kurum logosunun dosya yolu',
                'after' => 'kurucu_mudur_adi'
            ],
            'kurum_qr_kod_path' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'Kurum QR kodunun dosya yolu',
                'after' => 'kurum_logo_path'
            ],
        ];

        $this->forge->addColumn('institutions', $fields);

        // Foreign key'i de bu migration içinde ekleyelim
        $this->forge->addForeignKey('kurum_muduru_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        // Not: process() metodunu çağırmak addForeignKey'den sonra genellikle gerekli değildir,
        // ancak bazı veritabanı sürücülerinde işlemi hemen uygulamak için kullanılabilir.
        // Genellikle CodeIgniter bunu kendi yönetir.
    }

    public function down()
    {

        // Geri alma işleminde foreign key'i de kaldırmalıyız.
        $this->forge->dropForeignKey('institutions', 'institutions_kurum_muduru_user_id_foreign');
        
        // Yabancı anahtarı silmek için (veritabanı sürücünüze göre tablo adını ve constraint adını kontrol etmeniz gerekebilir)
        // CodeIgniter'ın oluşturduğu constraint adı genellikle 'tablo_adi_foreign_key_sutun_adi' şeklindedir.
        // $this->forge->dropForeignKey('institutions', 'institutions_kurum_muduru_user_id_foreign');
        
        $this->forge->dropColumn('institutions', [
            'evrak_prefix',
            'evrak_baslangic_no',
            'kurum_muduru_user_id',
            'kurum_muduru_adi',
            'kurucu_mudur_adi',
            'kurum_logo_path',
            'kurum_qr_kod_path'
        ]);
    }
}