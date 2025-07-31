<?php
namespace App\Filters;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LicenseFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Mevcut URL'yi alalım ve başındaki/sonundaki slash'ları temizleyelim.
        // Bu, 'login' ve '/login/' gibi durumların aynı şekilde ele alınmasını sağlar.
        $currentRoute = trim(uri_string(), '/');

        // LİSANS KONTROLÜNDEN MUAF OLACAK, HER KOŞULDA ERİŞİLEBİLİR SAYFALARIN LİSTESİ
        $allowedRoutes = [
            'login',
            'logout',
            'register',
            'auth/a/show', // Shield'in e-posta aktivasyon gibi aksiyonları için
            'maintenance', // Bakım sayfası
            'admin/settings' // Admin'in lisans gireceği sayfa
        ];

        // Mevcut rota, muaf listesindeki bir rotayla başlıyor mu?
        // Örnek: 'login/2fa' gibi alt sayfaları da kapsar.
        foreach ($allowedRoutes as $route) {
            if ($currentRoute === $route || str_starts_with($currentRoute, $route . '/')) {
                // Evet, bu sayfa muaf. Filtre hiçbir şey yapmadan devam etsin.
                return;
            }
        }
        
        // --- BU NOKTADAN SONRASI SADECE KORUNAN SAYFALAR İÇİN ÇALIŞIR ---

        // Lisans kontrolünü yapalım.
        if ((new \App\Libraries\LicenseService())->checkLicense() === false) {
            // Kullanıcı giriş yapmış mı ve admin mi?
            if (auth()->loggedIn() && auth()->user()->inGroup('admin', 'yonetici')) {
                // Admin veya Yönetici ise, lisans ayarları sayfasına yönlendir.
                // (Zaten o sayfada değilse yönlendir, sonsuz döngüyü engelle)
                if ($currentRoute !== 'admin/settings') {
                    return redirect()->to(route_to('admin.settings.index'))
                        ->with('error', 'Geçerli bir lisans anahtarı bulunamadı. Lütfen devam etmek için lisansınızı girin.');
                }
            } else {
                // Admin veya Yönetici değilse ya da hiç giriş yapmamışsa, bakım sayfasına yönlendir.
                
                // Eğer kullanıcı giriş yapmışsa, güvenlik için oturumunu kapatalım.
                if (auth()->loggedIn()) {
                    auth()->logout();
                }
                
                // (Zaten bakım sayfasında değilse yönlendir)
                if ($currentRoute !== 'maintenance') {
                    return redirect()->to(route_to('maintenance'));
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // after metodunda bir işlem yapmamıza gerek yok.
    }
}