<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class OnboardingFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Kullanıcı giriş yapmamışsa veya zaten user grubunda değilse bir şey yapma
        if (!auth()->loggedIn() || !auth()->user()->inGroup('user')) {
            return;
        }

        // Kullanıcı zaten onboarding sürecindeyse tekrar yönlendirme
        if (strpos((string)current_url(), 'onboarding') !== false) {
            return;
        }

        // user grubundaysa ve onboarding sayfasında değilse, yönlendir
        return redirect()->to('onboarding/role');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Bir şey yapmaya gerek yok
    }
}