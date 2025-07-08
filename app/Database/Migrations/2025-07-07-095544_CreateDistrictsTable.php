<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateDistrictsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'city_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('city_id', 'cities', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('districts');
    }

    public function down()
    {
        $this->forge->dropTable('districts');
    }
}