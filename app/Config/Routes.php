<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/**
 * ----------------------------------------------------------
 * Temel ve Genel Rotalar
 * ----------------------------------------------------------
 */

// Web Push için anahtar üretme sayfası (ör: ilk kurulumda, developerlar kullanır)
$routes->get('/generate-keys', 'VapidController::generateKeys');

/**
 * Anasayfa (giriş yapılmadan ulaşılamaz)
 * "session" filtresiyle giriş kontrolü yapılıyor.
 */
$routes->get('/', 'Home::index', ['filter' => 'session']);

// Site bakım modu sayfası (herkes erişebilir)
// 'as' parametresi ile route ismi tanımlanıyor (helper'larla ulaşmak için)
$routes->get('/maintenance', 'Home::maintenance', ['as' => 'maintenance']);

/**
 * Shield kütüphanesinin (CodeIgniter Auth) default authentication rotalarını ekler
 * (giriş, kayıt, şifre sıfırlama, logout vs.)  
 * - Rotalar: /login, /register, /logout gibi...
 * - Ayrıntılı müdahale etmek istersen override edebilirsin!
 */
service('auth')->routes($routes);

/**
 * Profil görüntüleme sayfası (giriş yapılmadan ulaşılamaz)
 */
$routes->get('/profile', 'ProfileController::index', ['filter' => 'session']);

/**
 * Profil güncelleme için (form submit, AJAX) POST rotası
 * getDistricts: İlçe listesi çekmek için (AJAX, select değişince tetiklenir)
 */
$routes->post('/profile/update', 'ProfileController::update', ['filter' => 'session']);
$routes->get('/profile/get-districts/(:num)', 'ProfileController::getDistricts/$1', ['filter' => 'session']);

/**
 * ----------------------------------------------------------
 * Admin Rota Grubu (sadece adminler erişebilir)
 * ----------------------------------------------------------
 * group:admin -> Yalnızca admin olanlar
 * except:profile* -> profile ile başlayan rotalar bu filtreden hariç tutulur
 */
$routes->group('admin', ['filter' => 'group:admin,except:profile*'], static function ($routes) {
    /**
     * Kullanıcı Yönetimi (CRUD işlemleri)
     */
    $routes->get('users', 'Admin\UserController::index', ['as' => 'admin.users.index']);                // Kullanıcı listesi
    $routes->get('users/new', 'Admin\UserController::new', ['as' => 'admin.users.new']);                // Yeni kullanıcı formu
    $routes->post('users/create', 'Admin\UserController::create', ['as' => 'admin.users.create']);      // Kullanıcı oluşturma işlemi
    $routes->get('users/show/(:num)', 'Admin\UserController::show/$1', ['as' => 'admin.users.show']);   // Kullanıcı detayı
    $routes->get('users/edit/(:num)', 'Admin\UserController::edit/$1', ['as' => 'admin.users.edit']);   // Kullanıcı düzenleme formu
    $routes->post('users/update/(:num)', 'Admin\UserController::update/$1', ['as' => 'admin.users.update']); // Kullanıcı güncelleme
    $routes->post('users/delete/(:num)', 'Admin\UserController::delete/$1', ['as' => 'admin.users.delete']); // Kullanıcı silme

    $routes->get('logs', 'Admin\LogController::index', ['as' => 'admin.logs.index']);                  // Sistem logları
    $routes->get('students/import', 'Admin\StudentController::importView');                            // Toplu öğrenci ekleme sayfası
    $routes->post('students/import', 'Admin\StudentController::import');                               // Toplu öğrenci ekleme işlemi

    $routes->get('institution', 'Admin\InstitutionController::index', ['as' => 'admin.institution.index']); // Kurum bilgileri
    $routes->post('institution/save', 'Admin\InstitutionController::save', ['as' => 'admin.institution.save']); // Kurum bilgisi kaydet/güncelle

    $routes->get('assignments', 'Admin\AssignmentController::index', ['as' => 'admin.assignments.index']);     // Görev/atananlar listesi
    $routes->post('assignments/save', 'Admin\AssignmentController::save', ['as' => 'admin.assignments.save']); // Görev atama işlemi
    $routes->get('assignments/get-assigned/(:num)', 'Admin\AssignmentController::getAssigned/$1', ['as' => 'admin.assignments.getAssigned']); // Kullanıcıya atanmış görevleri çek

    $routes->get('settings', 'Admin\SettingsController::index', ['as' => 'admin.settings.index']);     // Genel ayarlar
    $routes->post('settings', 'Admin\SettingsController::save', ['as' => 'admin.settings.save']);      // Ayarları kaydet
});

/**
 * ----------------------------------------------------------
 * Öğrenci Rota Grubu (admin, yönetici, müdür, sekreter erişimi)
 * ----------------------------------------------------------
 * Bu grup sadece ilgili kullanıcı tiplerine açık!
 */
$studentAccessGroups = 'group:admin,yonetici,mudur,sekreter';
$routes->group('', ['filter' => $studentAccessGroups], static function ($routes) {
    /**
     * view-ram-report: Belirli bir öğrencinin RAM raporunu (PDF veya görüntü) gösterir.
     * students resource: CRUD işlemlerini tek satırda tanımlar (CodeIgniter'ın otomatik resource rotaları).
     *
     * $routes->resource('students') ile aşağıdaki rotalar otomatik oluşur:
     * - GET      /students           => index (listele)
     * - GET      /students/new       => new (ekle formu)
     * - POST     /students           => create (kaydet)
     * - GET      /students/(:num)    => show (detay)
     * - GET      /students/(:num)/edit => edit (düzenle)
     * - PUT      /students/(:num)    => update (güncelle)
     * - DELETE   /students/(:num)    => delete (sil)
     */
    $routes->get('students/view-ram-report/(:num)', 'StudentController::viewRamReport/$1', ['as' => 'students.viewRamReport']);
    $routes->resource('students', ['controller' => 'StudentController']);
});

/**
 * ----------------------------------------------------------
 * Bildirim ve Web Push Rotaları
 * ----------------------------------------------------------
 */

// Kullanıcıdan bildirim izni ve abonelik kaydı alır (Web Push için)
$routes->post('/notifications/subscribe', 'NotificationController::saveSubscription', ['filter' => 'session', 'as' => 'notifications.subscribe']);

// VAPID public key (push notification için gerekli public anahtar) bilgisini döner
$routes->get('/notifications/vapid-key', 'NotificationController::getVapidKey', ['filter' => 'session']);

// Manuel bildirim göndermek için (admin, müdür, sekreter erişebilir)
$routes->post('/notifications/send-manual', 'NotificationController::sendManualNotification', ['filter' => 'group:admin,mudur,sekreter']);

// Kullanıcının push aboneliğini iptal etmesi için rota
$routes->post('/notifications/unsubscribe', 'NotificationController::unsubscribe');

/**
 * ----------------------------------------------------------
 * Ders Programı (Schedule) Rotaları
 * ----------------------------------------------------------
 * Sadece admin, müdür ve sekreter erişebilir!
 */
$routes->group('schedule', ['filter' => 'group:admin,mudur,sekreter'], static function ($routes) {
    $routes->get('/', 'ScheduleController::index', ['as' => 'schedule.index']); // Takvim ana sayfası
    $routes->get('get-month-lessons', 'ScheduleController::getLessonsForMonth', ['as' => 'schedule.get_month_lessons']); // Ay bazında dersleri JSON döner
    $routes->get('daily/(:segment)', 'ScheduleController::dailyGrid/$1', ['as' => 'schedule.daily']); // Belirli bir günün detay programı
    $routes->get('get-students', 'ScheduleController::getStudentsForSelect', ['as' => 'schedule.get_students']); // Öğrenci listesini AJAX ile döner
    $routes->post('create-lesson', 'ScheduleController::createLesson', ['as' => 'schedule.create']); // Yeni dersi ekler
    $routes->get('get-lesson-details/(:num)', 'ScheduleController::getLessonDetails/$1', ['as' => 'schedule.get_details']); // Ders detayını AJAX ile döner
    $routes->post('delete-lesson/(:num)', 'ScheduleController::deleteLesson/$1', ['as' => 'schedule.delete_lesson']); // Dersi siler
    $routes->get('get-lesson-dates', 'ScheduleController::getLessonDates', ['as' => 'schedule.get_lesson_dates']); // Mevcut derslerin tüm tarihleri (kopyalama, ileriye taşıma vs. işlemleri için)
});
