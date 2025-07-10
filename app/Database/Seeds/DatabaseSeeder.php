<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        $this->call(\App\Database\Seeds\CitiesSeeder::class);
        $this->call(\App\Database\Seeds\DistrictsSeeder::class);
        
        // Burası sadece ShieldSeeder'ı çağırır, onu tanımlamaz.
        $this->call(\App\Database\Seeds\ShieldSeeder::class); 

        $this->db->enableForeignKeyChecks();

        echo "Tüm seeder'lar başarıyla çalıştırıldı.\n";
    }
}