<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFixedLessonsTable extends Migration
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
            'teacher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'day_of_week' => [
                'type'       => 'INT',
                'constraint' => 1,
                'comment'    => '1=Pazartesi, 2=Salı, ..., 7=Pazar',
            ],
            'start_time' => [
                'type' => 'TIME',
            ],
            'end_time' => [
                'type' => 'TIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('teacher_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('fixed_lessons');
    }

    public function down()
    {
        $this->forge->dropTable('fixed_lessons');
    }
}