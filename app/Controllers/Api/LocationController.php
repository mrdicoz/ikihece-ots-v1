<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class LocationController extends BaseController
{
    public function index()
    {
        //
    }

    public function saveLocation()
{
    $db = \Config\Database::connect();
    
    $data = [
        'user_id' => auth()->id(),
        'latitude' => $this->request->getPost('latitude'),
        'longitude' => $this->request->getPost('longitude'),
        'created_at' => date('Y-m-d H:i:s'),
    ];
    
    $db->table('driver_locations')->insert($data);
    
    return $this->response->setJSON(['success' => true]);
}

public function getActiveDrivers()
{
    $db = \Config\Database::connect();
    
    // Son 5 dakika içinde konum gönderen söförler
    $drivers = $db->table('driver_locations dl')
        ->select('dl.user_id, dl.latitude, dl.longitude, dl.created_at, up.first_name, up.last_name')
        ->join('user_profiles up', 'up.user_id = dl.user_id')
        ->where('dl.created_at >', date('Y-m-d H:i:s', strtotime('-5 minutes')))
        ->orderBy('dl.created_at', 'DESC')
        ->get()
        ->getResultArray();
    
    return $this->response->setJSON($drivers);
}
}
