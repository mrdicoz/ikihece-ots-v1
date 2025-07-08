<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            // TEMEL BİLGİLER
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'okul_no' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'tc_kimlik_no' => ['type' => 'VARCHAR', 'constraint' => '11', 'null' => true, 'unique' => true],
            'adi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => false],
            'soyadi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => false],
            'cinsiyet' => ['type' => 'VARCHAR', 'constraint' => '10', 'null' => true],
            'dogum_tarihi' => ['type' => 'DATE', 'null' => true],
            'kayit_tarihi' => ['type' => 'DATE', 'null' => true],
            'sinifi' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'subesi' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'gelis_donemi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'ayrilis_tarihi' => ['type' => 'DATE', 'null' => true],
            'ayrilis_nedeni' => ['type' => 'TEXT', 'null' => true],
            'servis_durumu' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'servis_plakasi' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],

            // VELİ BİLGİLERİ - ANNE
            'veli_anne_tc' => ['type' => 'VARCHAR', 'constraint' => '11', 'null' => true],
            'veli_anne_adi_soyadi' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'veli_anne_telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'veli_anne_eposta' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'veli_anne_is_adresi' => ['type' => 'TEXT', 'null' => true],
            'veli_anne_gorevi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'veli_anne_mezuniyet' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'veli_anne_sag_durumu' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true], // Sağ, Vefat

            // VELİ BİLGİLERİ - BABA
            'veli_baba_tc' => ['type' => 'VARCHAR', 'constraint' => '11', 'null' => true],
            'veli_baba_adi_soyadi' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'veli_baba_telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'veli_baba_eposta' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'veli_baba_is_adresi' => ['type' => 'TEXT', 'null' => true],
            'veli_baba_gorevi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'veli_baba_mezuniyet' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'veli_baba_sag_durumu' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true], // Sağ, Vefat

            // ACİL DURUM
            'acil_durum_aranacak_kisi_1_adi' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'acil_durum_aranacak_kisi_1_yakinlik' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'acil_durum_aranacak_kisi_1_telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'acil_durum_aranacak_kisi_2_adi' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'acil_durum_aranacak_kisi_2_yakinlik' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'acil_durum_aranacak_kisi_2_telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],

            // SAĞLIK BİLGİLERİ
            'kan_grubu' => ['type' => 'VARCHAR', 'constraint' => '10', 'null' => true],
            'gecirilen_hastaliklar' => ['type' => 'TEXT', 'null' => true],
            'alerjiler' => ['type' => 'TEXT', 'null' => true],
            'ameliyatlar' => ['type' => 'TEXT', 'null' => true],
            'ilaclar' => ['type' => 'TEXT', 'null' => true],
            'diyet_durumu' => ['type' => 'TEXT', 'null' => true],
            'engel_durumu' => ['type' => 'TEXT', 'null' => true],
            'boy' => ['type' => 'VARCHAR', 'constraint' => '10', 'null' => true],
            'kilo' => ['type' => 'VARCHAR', 'constraint' => '10', 'null' => true],
            'goz_sorunu' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'isitsel_sorun' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],

            // KARDEŞ BİLGİLERİ
            'kardes_var_mi' => ['type' => 'VARCHAR', 'constraint' => '10', 'null' => true],
            'kardes_okulumuzda_mi' => ['type' => 'VARCHAR', 'constraint' => '10', 'null' => true],
            'kardes_adi_1' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'kardes_dogum_tarihi_1' => ['type' => 'DATE', 'null' => true],
            'kardes_okulu_1' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'kardes_adi_2' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'kardes_dogum_tarihi_2' => ['type' => 'DATE', 'null' => true],
            'kardes_okulu_2' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],

            // ADRES BİLGİLERİ
            'adres_il' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'adres_ilce' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'adres_mahalle' => ['type' => 'VARCHAR', 'constraint' => '200', 'null' => true],
            'adres_detay' => ['type' => 'TEXT', 'null' => true],

            // MUHASEBE BİLGİLERİ
            'sozlesme_no' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'sozlesme_tarihi' => ['type' => 'DATE', 'null' => true],
            'sozlesme_tutari' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'odeme_sekli' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],

            // SONRADAN EKLENEN ALANLAR
            'google_konum' => ['type' => 'TEXT', 'null' => true],
            'profile_image' => ['type'=> 'VARCHAR', 'constraint' => '255', 'null'=> true, 'default'=> null, ],

            
            // Codeigniter Timestamps
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('students');
    }

    public function down()
    {
        $this->forge->dropTable('students');
    }
}