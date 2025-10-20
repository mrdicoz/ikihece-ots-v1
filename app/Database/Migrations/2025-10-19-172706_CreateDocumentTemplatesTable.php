<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentTemplatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'content' => ['type' => 'LONGTEXT', 'comment' => 'HTML şablon içeriği'],
            'static_fields' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Sabit alanlar JSON: ["KURUM_ADI", "LOGO"]'],
            'dynamic_fields' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Dinamik alanlar JSON'],
            'has_number' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => 'Bu şablonda evrak numarası var mı?'],
            'allow_custom_number' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'Kullanıcı numarayı değiştirebilir mi?'],
            'fill_gaps' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => 'Boşlukları doldur mu?'],
            'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('category_id', 'document_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('document_templates');
    }

    public function down()
    {
        $this->forge->dropTable('document_templates');
    }
}