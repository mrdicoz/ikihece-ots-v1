<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class DistrictsSeeder extends Seeder
{
    public function run()
    {
        $jsonFilePath = APPPATH . 'Database/Seeds/districts.json';
        $districts = json_decode(file_get_contents($jsonFilePath), true);

        // truncate komutunu sildik
        $this->db->table('districts')->insertBatch($districts);

    }
}

