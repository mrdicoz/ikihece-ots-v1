<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstitutionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // KURUM BİLGİLERİ
            'kurum_kodu' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'kurum_adi' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'kurum_kisa_adi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'adresi' => ['type' => 'TEXT', 'null' => true],
            'city_id' => [ // DEĞİŞTİ: `ili` yerine `city_id`
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'district_id' => [ // DEĞİŞTİ: `ilcesi` yerine `district_id`
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'acilis_tarihi' => ['type' => 'DATE', 'null' => true],
            'web_sayfasi' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'epostasi' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'sabit_telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'telefon' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            
            // KURUCU-TEMSİLCİ BİLGİLERİ
            'kurucu_tipi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'sirket_adi' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'kurucu_temsilci_tckn' => ['type' => 'VARCHAR', 'constraint' => '11', 'null' => true],
            'kurum_vergi_dairesi' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'kurum_vergi_no' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],

            // Timestamps
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // YENİ: Foreign Key Kısıtlamaları
        // Bir il veya ilçe silinirse bu tablodaki ilgili alan 'NULL' olarak set edilsin.
        $this->forge->addForeignKey('city_id', 'cities', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('district_id', 'districts', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('institutions');
    }

    public function down()
    {
        $this->forge->dropTable('institutions');
    }
}