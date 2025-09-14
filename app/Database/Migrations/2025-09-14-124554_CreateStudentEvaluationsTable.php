<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentEvaluationsTable extends Migration
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
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'teacher_id' => [ // Öğretmen silinince bu alan NULL olacak
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'teacher_snapshot_name' => [ // Öğretmenin adı silinmemesi için burada saklanacak
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'evaluation' => [
                'type' => 'TEXT',
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
        // ÖNEMLİ DEĞİŞİKLİK: Öğretmen silinince yorumlar silinmesin, sadece bağlantı kopsun.
        $this->forge->addForeignKey('teacher_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('student_evaluations');
    }

    public function down()
    {
        $this->forge->dropTable('student_evaluations');
    }
}