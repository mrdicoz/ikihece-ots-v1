<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentAbsences extends Migration
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
        'student_id'  => [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
        ],
        'teacher_id'  => [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
        ],
        'lesson_date' => [
            'type' => 'DATE',
        ],
        'start_time'  => [
            'type' => 'TIME',
        ],
        'end_time'    => [
            'type' => 'TIME',
        ],
        'reason'      => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'created_by' => [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
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
        $this->forge->addForeignKey('teacher_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('student_absences');
    }

    public function down()
    {
        $this->forge->dropTable('student_absences');
    }
}