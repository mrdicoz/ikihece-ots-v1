<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocationFieldsToInstitutions extends Migration
{
    public function up()
    {
        $fields = [
            'google_konum' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'kurum_vergi_no', // Bu alanın hangi sütundan sonra geleceğini belirtiyoruz
            ],
            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
                'after'      => 'google_konum',
            ],
            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
                'after'      => 'latitude',
            ],
        ];

        $this->forge->addColumn('institutions', $fields);
    }

    public function down()
    {
        // Geri alma işlemi için sütunları kaldırıyoruz
        $this->forge->dropColumn('institutions', ['google_konum', 'latitude', 'longitude']);
    }
}