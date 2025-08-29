<?php

namespace App\Controllers;

use App\Models\StudentModel;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\AnnouncementModel;

class Home extends BaseController
{
  /**
     * Anasayfa (/) rotasına gelen istekleri karşılar
     * ve kullanıcıyı rolüne uygun dashboard'a yönlendirir.
     */
    public function index(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Kullanıcıyı direkt olarak dashboard'a yönlendir.
        // Rota adını kullanarak yönlendirme yapmak daha sağlıklıdır.
        return redirect()->to(route_to('dashboard'));
    }

    public function maintenance(): string
    {
        $this->data['title'] = 'Sistem Bakımda';
        return view('maintenance', $this->data);
    }
}