<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEntitlementsToStudents extends Migration
{
    public function up()
    {
        $fields = [
            'normal_bireysel_hak' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 0,
                'after' => 'profile_image', // Bu sütundan sonra eklenecek
            ],
            'normal_grup_hak' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 0,
                'after' => 'normal_bireysel_hak',
            ],
            'telafi_bireysel_hak' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 0,
                'after' => 'normal_grup_hak',
            ],
            'telafi_grup_hak' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 0,
                'after' => 'telafi_bireysel_hak',
            ],
        ];

        $this->forge->addColumn('students', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('students', [
            'normal_bireysel_hak',
            'normal_grup_hak',
            'telafi_bireysel_hak',
            'telafi_grup_hak',
        ]);
    }
}