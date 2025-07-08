<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class CitiesSeeder extends Seeder
{
    public function run()
    {
        $jsonFilePath = APPPATH . 'Database/Seeds/cities.json';
        $cities = json_decode(file_get_contents($jsonFilePath), true);

        // truncate komutunu sildik
        $this->db->table('cities')->insertBatch($cities);
        
    }
}
