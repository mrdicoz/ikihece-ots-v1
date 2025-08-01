<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTcknToUserProfiles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('user_profiles', [
            'tc_kimlik_no' => [
                'type'       => 'VARCHAR',
                'constraint' => '11',
                'null'       => true,
                'unique'     => true, // Her velinin TC'si benzersiz olmalı
                'after'      => 'last_name',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_profiles', 'tc_kimlik_no');
    }
}