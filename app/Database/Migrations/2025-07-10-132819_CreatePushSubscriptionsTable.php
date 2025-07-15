<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePushSubscriptionsTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false, // EKLENDİ: Boş olamaz.
            ],
            'endpoint' => [
                'type' => 'TEXT',
            ],
            'p256dh' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'auth' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [ // EKLENDİ: Timestamps için gerekli.
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id'); // EKLENDİ: Performans için index.
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('push_subscriptions');
    }

    public function down()
    {
        $this->forge->dropTable('push_subscriptions');
    }
}