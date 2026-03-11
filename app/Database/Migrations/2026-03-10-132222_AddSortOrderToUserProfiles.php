<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSortOrderToUserProfiles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('user_profiles', [
            'display_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'after' => 'last_name'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_profiles', 'display_order');
    }
}
