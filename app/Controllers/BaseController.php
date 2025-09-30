<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\UserProfileModel; // UserProfileModel'i dahil et

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 * class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $data = [];

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = ['auth', 'setting','text']; // auth ve setting helper'ları burada olmalı

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Eğer kullanıcı giriş yapmışsa, hem profil bilgilerini hazırla hem de lisansı kontrol et.
        if (auth()->loggedIn()) {
            // --- SENİN MEVCUT KODUN (DOĞRU VE KORUNUYOR) ---
            $profileModel = new UserProfileModel();
            $userProfile = $profileModel->where('user_id', auth()->id())->first();

            // Kullanıcının tam adını belirle (varsa Ad Soyad, yoksa username)
            $fullName = trim(($userProfile->first_name ?? '') . ' ' . ($userProfile->last_name ?? ''));
            $this->data['userDisplayName'] = !empty($fullName) ? $fullName : auth()->user()->username;

            // Avatar yolunu belirle
            $this->data['userAvatar'] = base_url($userProfile->profile_photo ?? 'assets/images/user.jpg');

            // --- YENİ EKLENEN ROL YÖNETİM KODU ---
            $userGroups = auth()->user()->getGroups();
            $activeRole = session()->get('active_role');

            // Eğer session'da rol yoksa veya sahip olmadığı bir rolse, varsayılanı ata
            if (!$activeRole || !in_array($activeRole, $userGroups)) {
                $activeRole = $userGroups[0] ?? 'veli'; // İlk rolü varsayılan yap, hiç rolü yoksa 'veli' olsun
                session()->set('active_role', $activeRole);
            }
            
            $this->data['userGroups'] = $userGroups;
            $this->data['activeRole'] = $activeRole;
            // --- YENİ KOD SONU ---

            // 🦇 MENÜ SİSTEMİ ENTEGRASYONU - YENİ EKLENEN
            $menuBuilder = new \App\Libraries\MenuBuilder();
            $this->data['menuData'] = $menuBuilder->buildMenu($activeRole);
            // 🦇 MENÜ SİSTEMİ SONU

            // --- LİSANS KONTROL MANTIĞI BURAYA EKLENDİ ---
            $this->checkLicenseStatus();
        }

        // --- BU SATIRI SİLİYORUZ ---
        // Bu satır, tüm controller'ların kendi view'larını döndürmesini engelliyordu.
        //$this->response->setBody(view('layouts/app', $this->data));
    }
    
    /**
     * Lisans durumunu kontrol eder ve kullanıcıyı rolüne göre yönlendirir.
     * Bu fonksiyon, "beyaz liste" dışındaki tüm sayfalarda çalışır.
     */
    private function checkLicenseStatus()
    {
        // Adım 1: Hangi sayfaların bu kontrolden muaf olacağını belirle.
        $allowedRoutes = [
            'login',
            'logout',
            'register',
            'auth/a/show',     // Shield'in e-posta aktivasyon, şifre sıfırlama gibi yolları
            'maintenance',     // Bakım sayfasının kendisi
            'admin/settings',  // Admin'in lisans gireceği sayfa (döngüyü önlemek için)
        ];

        $currentRoute = trim(uri_string(), '/');

        // Adım 2: Eğer mevcut sayfa muaf listesindeyse, hiçbir şey yapmadan çık.
        // Bu, sitenin her zaman login sayfasıyla başlayabilmesini sağlar.
        foreach ($allowedRoutes as $route) {
            if ($currentRoute === $route || str_starts_with($currentRoute, $route . '/')) {
                return; // Bu sayfa herkese açık, lisans kontrolü yapma.
            }
        }

        // --- Bu noktadan sonraki kodlar, sadece korunan sayfalar için çalışacaktır. ---

        // Adım 3: Lisans kontrolü yap. Geçerliyse yine hiçbir şey yapma.
        if ((new \App\Libraries\LicenseService())->checkLicense() === true) {
            return; // Lisans geçerli, sayfanın yüklenmesine devam et.
        }

        // --- Bu noktadan sonraki kodlar, sadece LİSANS GEÇERSİZSE ve KORUNAN BİR SAYFADAYSA çalışır. ---

        // Adım 4: Kullanıcının rolüne göre yönlendirme yap.
        
        // Önce giriş yapılmış mı diye kontrol et. Bu, olası bir "null user" hatasını önler.
        if (auth()->loggedIn()) {
            // Kullanıcı giriş yapmış.
            if (auth()->user()->inGroup('admin', 'yonetici')) {
                // Admin/Yönetici ise, lisans girmesi için ayarlar sayfasına yönlendir.
                // Not: Zaten ayarlar sayfasındaysa yönlendirme yapılmaz (beyaz listede olduğu için).
                return redirect()->to(route_to('admin.settings.index'))
                    ->with('error', 'Geçerli bir lisans anahtarı bulunamadı. Lütfen devam etmek için lisansınızı girin.')
                    ->send();
                exit();
            } else {
                // Admin/Yönetici değil ama giriş yapmış (örn: veli, öğretmen),
                // oturumunu güvenle kapat ve bakım sayfasına yönlendir.
                auth()->logout();
                return redirect()->to(route_to('maintenance'))->send();
                exit();
            }
        } else {
            // Ziyaretçi (giriş yapmamış) korumalı bir sayfaya girmeye çalışıyorsa,
            // Shield'in kendi "session" filtresi onu zaten login sayfasına yönlendirecektir.
            // Bu yüzden burada ekstra bir yönlendirme yapmaya gerek yok.
            return;
        }
    }
}