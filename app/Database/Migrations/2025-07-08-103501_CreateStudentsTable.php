<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentsTable extends Migration
{
    public function up()
    {
       $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            
            // --- TEMEL BİLGİLER ---
            'adi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => false],
            'soyadi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => false],
            'tckn' => ['type' => 'VARCHAR', 'constraint' => '11', 'null' => true, 'unique' => true],
            'dogum_tarihi' => ['type' => 'DATE', 'null' => true],
            'cinsiyet' => ['type' => 'ENUM', 'constraint' => ['erkek', 'kadin'], 'null' => true],
            'iletisim' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'profile_image' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            
            // --- ADRES BİLGİLERİ ---
            'adres_detayi' => ['type' => 'TEXT', 'null' => true],
            'city_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'district_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'google_konum' => ['type' => 'TEXT', 'null' => true],

            // --- VELİ BİLGİLERİ ---
            'veli_baba' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'veli_baba_telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'veli_baba_tc' => ['type' => 'VARCHAR', 'constraint' => '11', 'null' => true],
            'veli_anne' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'veli_anne_telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'veli_anne_tc' => ['type' => 'VARCHAR', 'constraint' => '11', 'null' => true],

            // --- EĞİTİM BİLGİLERİ ---
            'servis' => ['type' => 'ENUM', 'constraint' => ['var', 'yok', 'arasira'], 'null' => true],
            'mesafe' => ['type' => 'ENUM', 'constraint' => ['civar', 'yakın', 'uzak'], 'null' => true], // GÜNCELLENDİ
            'orgun_egitim' => ['type' => 'ENUM', 'constraint' => ['evet', 'hayir'], 'null' => true],
            'egitim_sekli' => ['type' => 'ENUM', 'constraint' => ['tam gün', 'öğlenci', 'sabahcı'], 'null' => true], // GÜNCELLENDİ
            'egitim_programi' => [
                'type' => 'SET',
                'constraint' => [
                    'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
                    'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
                    'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
                    'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı',
                    'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
                ],
                'null' => true,
            ],

            // --- RAM BİLGİLERİ ---
            'ram' => ['type' => 'TEXT', 'null' => true],
            'ram_baslagic' => ['type' => 'DATE', 'null' => true],
            'ram_bitis' => ['type' => 'DATE', 'null' => true],
            'ram_raporu' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],

            // --- HASTANE BİLGİLERİ ---
            'hastane_adi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'hastane_raporu_baslama_tarihi' => ['type' => 'DATE', 'null' => true],
            'hastane_raporu_bitis_tarihi' => ['type' => 'DATE', 'null' => true],
            'hastane_randevu_tarihi' => ['type' => 'DATE', 'null' => true],
            'hastane_randevu_saati' => ['type' => 'TIME', 'null' => true],
            'hastane_aciklama' => ['type' => 'TEXT', 'null' => true],
            
            // --- DERS HAKLARI ---
            'normal_bireysel_hak' => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'normal_grup_hak' => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'telafi_bireysel_hak' => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'telafi_grup_hak' => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            
            // --- ZAMAN DAMGALARI ---
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('city_id', 'cities', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('district_id', 'districts', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('students');
    }

    public function down()
    {
        $this->forge->dropTable('students', true);
    }
}