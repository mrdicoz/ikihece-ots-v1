<?php
namespace App\Filters;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LicenseFilter implements FilterInterface
{
   public function before(RequestInterface $request, $arguments = null)
{
    $uri = service('uri');

    // Bu sayfaların her zaman erişilebilir olmasını sağla
    if (in_array($uri->getPath(), ['login', 'logout', 'register', 'auth/a/show', 'maintenance']) ||
        str_starts_with($uri->getPath(), 'login/')) {
        return;
    }

    if ((new \App\Libraries\LicenseService())->checkLicense() === false) {
        // Kullanıcı giriş yapmış mı ve admin mi?
        if (auth()->loggedIn() && auth()->user()->inGroup('admin')) {
            // Admin ise, lisans ayarları sayfasına yönlendir.
            // Ama zaten o sayfadaysa bir şey yapma.
            if ($uri->getPath() !== 'admin/settings') {
                return redirect()->to(route_to('admin.settings.index'))->with('error', 'Geçerli bir lisans anahtarı bulunamadı.');
            }
        } else {
            // Admin değilse, bakım sayfasına yönlendir.
            // Ama zaten o sayfadaysa bir şey yapma.
            if ($uri->getPath() !== 'maintenance') {
                // Oturumdaki kullanıcıyı da güvenlik için çıkış yaptıralım.
                auth()->logout();
                return redirect()->to(route_to('maintenance'));
            }
        }
    }
}
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}