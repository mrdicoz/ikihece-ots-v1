<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index', ['filter' => 'session']);

service('auth')->routes($routes);
$routes->get('/profile', 'ProfileController::index', ['filter' => 'session']);
// Formun post edileceği ve AJAX isteğinin yapılacağı rotalar
$routes->post('/profile/update', 'ProfileController::update', ['filter' => 'session']);
$routes->get('/profile/get-districts/(:num)', 'ProfileController::getDistricts/$1', ['filter' => 'session']);

/**
 * --------------------------------------------------------------------
 * Admin Routes
 * --------------------------------------------------------------------
 */
$routes->group('admin', ['filter' => 'group:admin,except:profile*'], static function ($routes) {
    /**
     * User Management Routes
     */
    $routes->get('users', 'Admin\UserController::index', ['as' => 'admin.users.index']);
    $routes->get('users/new', 'Admin\UserController::new', ['as' => 'admin.users.new']);
    $routes->post('users/create', 'Admin\UserController::create', ['as' => 'admin.users.create']);
    $routes->get('users/show/(:num)', 'Admin\UserController::show/$1', ['as' => 'admin.users.show']); // Bu satırı ekle
    $routes->get('users/edit/(:num)', 'Admin\UserController::edit/$1', ['as' => 'admin.users.edit']);
    $routes->post('users/update/(:num)', 'Admin\UserController::update/$1', ['as' => 'admin.users.update']);
    $routes->post('users/delete/(:num)', 'Admin\UserController::delete/$1', ['as' => 'admin.users.delete']);
    $routes->get('logs', 'Admin\LogController::index', ['as' => 'admin.logs.index']);
    $routes->get('students/import', 'Admin\StudentController::importView');
    $routes->post('students/import', 'Admin\StudentController::import');
    $routes->get('institution', 'Admin\InstitutionController::index', ['as' => 'admin.institution.index']);
    $routes->post('institution/save', 'Admin\InstitutionController::save', ['as' => 'admin.institution.save']);
});

// --- YENİ ÖĞRENCİ ROTLARI ---
// Bu rotalara sadece belirtilen gruplardaki giriş yapmış kullanıcılar erişebilir.
$studentAccessGroups = 'group:admin,yonetici,mudur,sekreter';
$routes->group('', ['filter' => $studentAccessGroups], static function ($routes) {
    /**
     * $routes->resource('students') satırı, bizim için aşağıdaki tüm rotaları
     * TEK BAŞINA otomatik olarak oluşturur:
     *
     * - GET    /students           -> StudentController::index()     (Listeleme sayfası)
     * - GET    /students/new       -> StudentController::new()       (Yeni ekleme formu)
     * - POST   /students           -> StudentController::create()    (Yeni öğrenciyi kaydeder)
     * - GET    /students/(:num)    -> StudentController::show($1)    (Detay sayfası)
     * - GET    /students/(:num)/edit -> StudentController::edit($1)    (Düzenleme formu)
     * - PUT    /students/(:num)    -> StudentController::update($1)  (Güncelleme işlemini yapar)
     * - DELETE /students/(:num)    -> StudentController::delete($1)  (Silme işlemini yapar)
     *
     * Bu sayede tüm sayfalarımız doğru URL'ler ile çalışır.
     */
    $routes->get('students/view-ram-report/(:num)', 'StudentController::viewRamReport/$1', ['as' => 'students.viewRamReport']);
    $routes->resource('students', ['controller' => 'StudentController']);


});