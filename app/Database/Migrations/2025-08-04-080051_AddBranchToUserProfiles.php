<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBranchToUserProfiles extends Migration
{
    public function up()
    {
        $fields = [
            'branch' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'last_name', // 'last_name' sütunundan sonra gelsin
            ],
        ];
        $this->forge->addColumn('user_profiles', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('user_profiles', 'branch');
    }
}