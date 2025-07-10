<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
                // $this->data dizisine bu sayfaya özel verileri ekleyebiliriz.
        $this->data['title'] = "Anasayfa";

        // $this->data dizisini view'a gönderiyoruz.
        return view('dashboard', $this->data);
    }
}
