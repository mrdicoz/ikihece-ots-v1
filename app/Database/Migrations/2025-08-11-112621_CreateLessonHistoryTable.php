<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLessonHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'teacher_name'=> ['type' => 'VARCHAR', 'constraint' => '255'],
            'student_name'=> ['type' => 'VARCHAR', 'constraint' => '255'],
            'lesson_date' => ['type' => 'DATE'],
            'start_time'  => ['type' => 'TIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['teacher_name', 'lesson_date']); // Sorgu performansı için index
        $this->forge->createTable('lesson_history');
    }

    public function down()
    {
        $this->forge->dropTable('lesson_history');
    }
}