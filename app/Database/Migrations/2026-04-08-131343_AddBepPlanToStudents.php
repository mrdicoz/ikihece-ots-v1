<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBepPlanToStudents extends Migration
{
    public function up()
    {
        $this->forge->addColumn('students', [
            'bep_plani' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'ram_raporu'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('students', 'bep_plani');
    }
}
