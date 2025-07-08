<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LogModel;

class LogController extends BaseController
{
    public function index()
    {
        $logModel = new LogModel();

        // Logları, ilişkili kullanıcı adıyla birlikte almak için join kullanıyoruz.
        // En yeni loglar en üstte olacak şekilde sıralıyoruz.
        // Sayfalama (Pagination) ekliyoruz.
        $data = [
            'logs'      => $logModel
                            ->select('logs.*, users.username')
                            ->join('users', 'users.id = logs.user_id', 'left')
                            ->orderBy('logs.id', 'DESC')
                            ->paginate(25), // Her sayfada 25 log göster
            'pager'     => $logModel->pager,
            'pageTitle' => 'Sistem Log Kayıtları'
        ];

        return view('admin/logs/index', $data);
    }
}