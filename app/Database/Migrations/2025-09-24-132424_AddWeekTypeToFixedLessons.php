<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWeekTypeToFixedLessons extends Migration
{
    public function up()
    {
        $fields = [
            'week_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '1',
                'default'    => 'A',
                'null'       => false,
                'comment'    => 'Hafta şablonu tipi (A, B, C, D)',
                'after'      => 'student_id', // Bu sütunu student_id'den sonra ekle
            ],
        ];

        $this->forge->addColumn('fixed_lessons', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('fixed_lessons', 'week_type');
    }
}