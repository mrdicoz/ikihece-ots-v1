<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterBranchToEnum extends Migration
{
    public function up()
    {
        $fields = [
            'branch' => [
                'type' => 'ENUM',
                'constraint' => ['Fizyoterapist', 'Dil ve Konuşma Bozuklukları Uzmanı', 'Odyoloji ve Konuşma Bozuklukları Uzmanı', 'Özel Eğitim Alanı Öğretmeni', 'Uzman Öğretici', 'Psikolog & PDR', 'Okul Öncesi Öğretmeni', 'Çocuk Gelişimi Öğretmeni'],
                'null' => true,
            ],
        ];
        $this->forge->modifyColumn('user_profiles', $fields);
    }

    public function down()
    {
        // Geri alma işlemi, mevcut veriyi kaybetmemek için dikkatli yapılmalıdır.
        // Basitçe VARCHAR olarak geri dönüyoruz.
        $fields = [
            'branch' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
        ];
        $this->forge->modifyColumn('user_profiles', $fields);
    }
}