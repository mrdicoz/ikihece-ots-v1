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
    protected $helpers = ['auth', 'setting']; // auth ve setting helper'ları burada olmalı

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

            // --- LİSANS KONTROL MANTIĞI BURAYA EKLENDİ ---
            $this->checkLicenseStatus();
        }

        // --- BU SATIRI SİLİYORUZ ---
        // Bu satır, tüm controller'ların kendi view'larını döndürmesini engelliyordu.
         $this->response->setBody(view('layouts/app', $this->data));
    }
    
    /**
     * Bu yeni metot, sadece giriş yapmış kullanıcılar için lisans durumunu kontrol eder.
     */
    private function checkLicenseStatus()
    {
        // Lisans geçerliyse, hiçbir şey yapma, normal işleyişe devam et.
        if ((new \App\Libraries\LicenseService())->checkLicense() === true) {
            return;
        }

        // --- Lisans Geçerli Değilse ---
        
        $currentRoute = trim(uri_string(), '/');
        
        // Giriş yapan kullanıcı admin veya yönetici mi?
        if (auth()->user()->inGroup('admin', 'yonetici')) {
            // Eğer zaten lisans/settings sayfasına gitmiyorsa, oraya yönlendir.
            if ($currentRoute !== 'admin/settings') {
                // Yönlendirme sonrası kodun çalışmasını durdurmak için send() ve exit() kullanılır.
                return redirect()->to(route_to('admin.settings.index'))
                    ->with('error', 'Geçerli bir lisans anahtarı bulunamadı. Lütfen devam etmek için lisansınızı girin.')
                    ->send();
                exit();
            }
        } else {
            // Kullanıcı admin/yönetici değilse, oturumunu kapatıp bakım sayfasına yönlendir.
            auth()->logout();
            return redirect()->to(route_to('maintenance'))->send();
            exit();
        }
    }
}