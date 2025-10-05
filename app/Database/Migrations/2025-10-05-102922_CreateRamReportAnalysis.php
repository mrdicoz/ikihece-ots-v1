<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRamReportAnalysis extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'student_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'ram_text_content' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'total_memory' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'available_memory' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'memory_info' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'analyzed_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ram_report_analysis');
    }

    public function down()
    {
        $this->forge->dropTable('ram_report_analysis');
    }
}