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
 *     class Home extends BaseController
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

        // --- YENİ EKLENEN BÖLÜM ---
        // Eğer kullanıcı giriş yapmışsa, profil bilgilerini çek ve $this->data'ya ata
        if (auth()->loggedIn()) {
            $profileModel = new UserProfileModel();
            $userProfile = $profileModel->where('user_id', auth()->id())->first();

            // Kullanıcının tam adını belirle (varsa Ad Soyad, yoksa username)
            $fullName = trim(($userProfile->first_name ?? '') . ' ' . ($userProfile->last_name ?? ''));
            $this->data['userDisplayName'] = !empty($fullName) ? $fullName : auth()->user()->username;

            // Avatar yolunu belirle
            $this->data['userAvatar'] = base_url($userProfile->profile_photo ?? 'assets/images/user.jpg');
        }

        // Tüm view'larda $this->data'yı kullanılabilir yap
        $this->response->setBody(view('layouts/app', $this->data));
    }

    
}
