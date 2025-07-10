<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserAssignmentsTable extends Migration
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
            'manager_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Sorumlu olan kullanıcı (Sekreter)',
            ],
            'managed_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Sorumluluğu atanan kullanıcı (Öğretmen)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        // Aynı sekreter-öğretmen ilişkisinin tekrar eklenmesini önlemek için birleşik anahtar
        $this->forge->addUniqueKey(['manager_user_id', 'managed_user_id']);
        
        // Foreign Key Kısıtlamaları
        // Bir kullanıcı silinirse, onunla ilgili tüm atama kayıtları da silinsin (CASCADE)
        $this->forge->addForeignKey('manager_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('managed_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('user_assignments');
    }

    public function down()
    {
        $this->forge->dropTable('user_assignments');
    }
}