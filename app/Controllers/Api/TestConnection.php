<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class TestConnection extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        log_message('info', '=== TEST CONNECTION İSTEĞİ GELDİ ===');
        
        return $this->respond([
            'status'      => 'success',
            'message'     => 'API çalışıyor!',
            'server_time' => date('Y-m-d H:i:s'),
        ], 200);
    }
}