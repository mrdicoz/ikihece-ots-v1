<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class StatusCheck implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Eğer kullanıcı giriş yapmışsa
        if (auth()->loggedIn()) {
            // Ve bu kullanıcının durumu "aktif" DEĞİLSE
            if (! auth()->user()->active) {
                // Kullanıcıyı sistemden at
                auth()->logout();

                // Hata mesajı ile birlikte giriş sayfasına yönlendir
                return redirect()->to(url_to('login'))
                    ->with('error', 'Hesabınız pasif durumdadır. Lütfen yönetici ile iletişime geçin.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // after metodunda bir şey yapmamıza gerek yok
    }
}