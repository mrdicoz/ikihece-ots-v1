<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMenuSystemTables extends Migration
{
    public function up()
    {
        // Menü Grupları Tablosu
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => '100', 'unique' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => '255'],
            'icon' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('menu_groups');

        // Menü Öğeleri Tablosu
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'group_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'parent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => '255'],
            'route_name' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'icon' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_dropdown' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('group_id', 'menu_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('parent_id', 'menu_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('menu_items');

        // Menü - Rol İlişki Tablosu
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'menu_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => '100'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('menu_item_id', 'menu_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey(['menu_item_id', 'role']);
        $this->forge->createTable('menu_permissions');
    }

    public function down()
    {
        $this->forge->dropTable('menu_permissions');
        $this->forge->dropTable('menu_items');
        $this->forge->dropTable('menu_groups');
    }
}