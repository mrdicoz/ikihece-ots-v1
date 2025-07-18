<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateAppSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'key'   => ['type' => 'VARCHAR', 'constraint' => '255', 'unique' => true],
            'value' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('app_settings');
    }

    public function down()
    {
        $this->forge->dropTable('app_settings');
    }
}