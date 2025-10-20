<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'document_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'Tam evrak numarası (örn: SRGM-2025-1001)'],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 500, 'comment' => 'Belge konusu'],
            'form_data' => ['type' => 'TEXT', 'comment' => 'Kullanıcının doldurduğu form verileri JSON'],
            'rendered_html' => ['type' => 'LONGTEXT', 'comment' => 'Render edilmiş son HTML'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('document_number');
        $this->forge->addForeignKey('template_id', 'document_templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('documents');
    }

    public function down()
    {
        $this->forge->dropTable('documents');
    }
}