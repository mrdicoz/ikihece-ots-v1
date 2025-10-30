<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceReports extends Migration
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
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'total_km' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,3',
                'default'    => '0.000',
            ],
            'total_idle_time_seconds' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
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
        $this->forge->addUniqueKey(['user_id', 'date']);
        $this->forge->createTable('service_reports');
    }

    public function down()
    {
        $this->forge->dropTable('service_reports');
    }
}