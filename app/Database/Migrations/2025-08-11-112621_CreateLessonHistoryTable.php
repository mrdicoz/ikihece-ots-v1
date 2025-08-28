<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLessonHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'lesson_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'student_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            // REVİZE DOSYASINDAN GELEN YENİ ALAN
            'student_program' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'teacher_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            // REVİZE DOSYASINDAN GELEN YENİ ALAN
            'teacher_branch' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        // REVİZE DOSYASINDAN GELEN GÜNCEL ANAHTARLAR (INDEX'LER)
        $this->forge->addKey(['lesson_date', 'start_time']);
        $this->forge->addKey('teacher_branch');
        $this->forge->addKey('student_program');
        $this->forge->createTable('lesson_history');
    }

    public function down()
    {
        $this->forge->dropTable('lesson_history');
    }
}