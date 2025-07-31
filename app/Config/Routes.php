<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --------------------------------------------------------------------
// 1. HERKESE AÇIK (PUBLIC) ROTALAR
// --------------------------------------------------------------------
service('auth')->routes($routes);
$routes->get('maintenance', 'Home::maintenance', ['as' => 'maintenance']);
$routes->post('notifications/unsubscribe', 'NotificationController::unsubscribe');


// --------------------------------------------------------------------
// 2. GİRİŞ YAPMIŞ KULLANICI GEREKTİREN TÜM ROTALAR
// --------------------------------------------------------------------
$routes->group('', ['filter' => 'session'], static function ($routes) {

    /**
     * Genel Rotalar (Tüm giriş yapmış kullanıcılar erişebilir)
     */
    $routes->get('/', 'Home::index', ['as' => 'home']);
    $routes->get('duyurular', 'AnnouncementController::index', ['as' => 'announcements.index']);

    // Profil Rotaları
    $routes->get('profile', 'ProfileController::index', ['as' => 'profile']);
    $routes->post('profile/update', 'ProfileController::update', ['as' => 'profile.update']);
    $routes->get('profile/get-districts/(:num)', 'ProfileController::getDistricts/$1', ['as' => 'profile.getDistricts']);
    
    // Bildirim Abonelik Rotaları
    $routes->post('notifications/subscribe', 'NotificationController::saveSubscription', ['as' => 'notifications.subscribe']);
    $routes->get('notifications/vapid-key', 'NotificationController::getVapidKey', ['as' => 'notifications.vapidKey']);

    /**
     * Özel Yetki Gerektiren Rota Grupları
     */

    // Öğrenci Yönetimi Rotaları
    $routes->group('', ['filter' => 'group:admin,yonetici,mudur,sekreter'], static function ($routes) {
        $routes->get('students/view-ram-report/(:num)', 'StudentController::viewRamReport/$1', ['as' => 'students.viewRamReport']);
        $routes->resource('students', ['controller' => 'StudentController']);
    });
    
    // Ders Programı Rotaları
    $routes->group('schedule', ['filter' => 'group:admin,mudur,sekreter'], static function ($routes) {
        $routes->get('/', 'ScheduleController::index', ['as' => 'schedule.index']);
        $routes->get('get-month-lessons', 'ScheduleController::getLessonsForMonth', ['as' => 'schedule.get_month_lessons']);
        $routes->get('daily/(:segment)', 'ScheduleController::dailyGrid/$1', ['as' => 'schedule.daily']);
        $routes->get('get-students', 'ScheduleController::getStudentsForSelect', ['as' => 'schedule.get_students']);
        $routes->post('create-lesson', 'ScheduleController::createLesson', ['as' => 'schedule.create']);
        $routes->get('get-lesson-details/(:num)', 'ScheduleController::getLessonDetails/$1', ['as' => 'schedule.get_details']);
        $routes->post('delete-lesson/(:num)', 'ScheduleController::deleteLesson/$1', ['as' => 'schedule.delete_lesson']);
        $routes->get('get-lesson-dates', 'ScheduleController::getLessonDates', ['as' => 'schedule.get_lesson_dates']);
    });
    
    // Duyuru Yönetimi Rotaları (YENİ VE DÜZENLENMİŞ BLOK)
    $routes->group('admin', ['filter' => 'group:admin,yonetici,mudur,sekreter'], static function ($routes) {
        $routes->get('announcements', 'Admin\AnnouncementController::index', ['as' => 'admin.announcements.index']);
        $routes->get('announcements/new', 'Admin\AnnouncementController::new', ['as' => 'admin.announcements.new']);
        $routes->post('announcements/create', 'Admin\AnnouncementController::create', ['as' => 'admin.announcements.create']);
        $routes->get('announcements/edit/(:num)', 'Admin\AnnouncementController::edit/$1', ['as' => 'admin.announcements.edit']);
        $routes->post('announcements/update/(:num)', 'Admin\AnnouncementController::update/$1', ['as' => 'admin.announcements.update']);
        $routes->post('announcements/delete/(:num)', 'Admin\AnnouncementController::delete/$1', ['as' => 'admin.announcements.delete']);
    });

    // Bildirim Gönderme Rotası
    $routes->post('notifications/send-manual', 'NotificationController::sendManualNotification', ['filter' => 'group:admin,mudur,sekreter', 'as' => 'notifications.sendManual']);
    
    // --------------------------------------------------------------------
    // ADMİN GRUBU (Sadece 'admin' grubundakiler erişebilir)
    // --------------------------------------------------------------------
    $routes->group('admin', ['filter' => 'group:admin'], static function ($routes) {
        
        // Kullanıcı Yönetimi
        $routes->get('users', 'Admin\UserController::index', ['as' => 'admin.users.index']);
        $routes->get('users/new', 'Admin\UserController::new', ['as' => 'admin.users.new']);
        $routes->post('users/create', 'Admin\UserController::create', ['as' => 'admin.users.create']);
        $routes->get('users/show/(:num)', 'Admin\UserController::show/$1', ['as' => 'admin.users.show']);
        $routes->get('users/edit/(:num)', 'Admin\UserController::edit/$1', ['as' => 'admin.users.edit']);
        $routes->post('users/update/(:num)', 'Admin\UserController::update/$1', ['as' => 'admin.users.update']);
        $routes->post('users/delete/(:num)', 'Admin\UserController::delete/$1', ['as' => 'admin.users.delete']);
        
        // Loglar
        $routes->get('logs', 'Admin\LogController::index', ['as' => 'admin.logs.index']);
        
        // Öğrenci İçe Aktarma
        $routes->get('students/import', 'Admin\StudentController::importView', ['as' => 'admin.students.importView']);
        $routes->post('students/import', 'Admin\StudentController::import', ['as' => 'admin.students.import']);
        
        // Kurum Ayarları
        $routes->get('institution', 'Admin\InstitutionController::index', ['as' => 'admin.institution.index']);
        $routes->post('institution/save', 'Admin\InstitutionController::save', ['as' => 'admin.institution.save']);
        
        // Atamalar
        $routes->get('assignments', 'Admin\AssignmentController::index', ['as' => 'admin.assignments.index']);
        $routes->post('assignments/save', 'Admin\AssignmentController::save', ['as' => 'admin.assignments.save']);
        $routes->get('assignments/get-assigned/(:num)', 'Admin\AssignmentController::getAssigned/$1', ['as' => 'admin.assignments.getAssigned']);
        
        // Genel Ayarlar
        $routes->get('settings', 'Admin\SettingsController::index', ['as' => 'admin.settings.index']);
        $routes->post('settings', 'Admin\SettingsController::save', ['as' => 'admin.settings.save']);
        
        // Web Push anahtar üretme
        $routes->get('generate-keys', 'VapidController::generateKeys', ['as' => 'admin.generateKeys']);
    });
    
});