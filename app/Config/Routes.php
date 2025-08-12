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


// Bu rota artık sadece yönlendirme yapacak olan Home controller'ını çağırır.
$routes->get('/', 'Home::index', ['filter' => 'session']);

// Dashboard için yeni bir rota tanımlıyoruz.
$routes->get('/dashboard', 'DashboardController::index', ['filter' => 'session', 'as' => 'dashboard']);

// Rol değiştirme rotası doğru yerde.
$routes->get('user/switch-role/(:segment)', 'ProfileController::switchRole/$1', ['filter' => 'session', 'as' => 'user.switchRole']);

// Rol seçim ve profil tamamlama adımlarını yöneten OnboardingController'ı kullanıyoruz
$routes->group('onboarding', ['filter' => 'session'], static function ($routes) {
    $routes->get('role', 'OnboardingController::showRoleSelection');
    $routes->post('role', 'OnboardingController::processRoleSelection');
    $routes->get('profile', 'OnboardingController::showProfileForm');
    $routes->post('profile', 'OnboardingController::processProfileForm');
});

// --------------------------------------------------------------------
// 2. GİRİŞ YAPMIŞ KULLANICI GEREKTİREN TÜM ROTALAR
// --------------------------------------------------------------------
$routes->group('', ['filter' => 'session'], static function ($routes) {

    /**
     * Ana Yönlendirme ve Genel Rotalar
     * Giriş yapan kullanıcıyı rolüne göre doğru dashboard'a yönlendirir.
     */
    $routes->get('/', 'DashboardController::index', ['as' => 'home']);
    $routes->get('duyurular', 'AnnouncementController::index', ['as' => 'announcements.index']);

    // Profil Rotaları
    $routes->get('profile', 'ProfileController::index', ['as' => 'profile']);
    $routes->post('profile/update', 'ProfileController::update', ['as' => 'profile.update']);
    $routes->get('profile/get-districts/(:num)', 'ProfileController::getDistricts/$1', ['as' => 'profile.getDistricts']);
    
    // Bildirim Abonelik Rotaları
    $routes->post('notifications/subscribe', 'NotificationController::saveSubscription', ['as' => 'notifications.subscribe']);
    $routes->get('notifications/vapid-key', 'NotificationController::getVapidKey', ['as' => 'notifications.vapidKey']);

    /**
     * Role Özel Dashboard Rotaları
     */
    $routes->group('dashboard', static function ($routes) {
        // Öğretmen Dashboard'ı (Admin de görebilir)
        $routes->get('teacher', 'DashboardController::teacher', ['filter' => 'group:admin,ogretmen', 'as' => 'dashboard.teacher']);
        // Veli Dashboard'ı (Admin de görebilir)
        $routes->get('parent', 'DashboardController::parent', ['filter' => 'group:admin,veli', 'as' => 'dashboard.parent']);
        // Yönetici ve diğer roller için varsayılan dashboard
        $routes->get('default', 'DashboardController::default', ['filter' => 'group:admin,yonetici,mudur,sekreter', 'as' => 'dashboard.default']);
    });


    /**
     * Özel Yetki Gerektiren Rota Grupları
     */

    // Öğrenci Yönetimi Rotaları (Genel)
    $routes->group('', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen'], static function ($routes) {
        $routes->get('students/view-ram-report/(:num)', 'StudentController::viewRamReport/$1', ['as' => 'students.viewRamReport']);
        $routes->resource('students', ['controller' => 'StudentController']);
    });

    // Veli Paneli Rotaları
    $routes->group('dashboard', ['filter' => 'group:veli'], static function ($routes) {
        $routes->get('parent', 'DashboardController::parent');
        $routes->post('set-active-child', 'DashboardController::setActiveChild');
        $routes->get('programim', 'ScheduleController::parentSchedule', ['as' => 'parent.schedule','filter' => 'group:veli']);
    });

    // Öğretmene Özel Öğrenci Listesi
    $routes->get('my-students', 'StudentController::myStudents', ['filter' => 'group:admin,ogretmen', 'as' => 'students.my']);
    
    // Ders Programı Rotaları
    $routes->group('schedule', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen'], static function ($routes) {
        $routes->get('/', 'ScheduleController::index', ['as' => 'schedule.index']);
        $routes->get('my-schedule', 'ScheduleController::mySchedule', ['filter' => 'group:admin,ogretmen', 'as' => 'schedule.my']);
        $routes->get('get-month-lessons', 'ScheduleController::getLessonsForMonth', ['as' => 'schedule.get_month_lessons']);
        $routes->get('daily/(:segment)', 'ScheduleController::dailyGrid/$1', ['as' => 'schedule.daily']);
        $routes->get('get-students', 'ScheduleController::getStudentsForSelect', ['as' => 'schedule.get_students']);
        $routes->post('create-lesson', 'ScheduleController::createLesson', ['as' => 'schedule.create']);
        $routes->get('get-lesson-details/(:num)', 'ScheduleController::getLessonDetails/$1', ['as' => 'schedule.get_details']);
        $routes->post('delete-lesson/(:num)', 'ScheduleController::deleteLesson/$1', ['as' => 'schedule.delete_lesson']);
        $routes->get('get-lesson-dates', 'ScheduleController::getLessonDates', ['as' => 'schedule.get_lesson_dates']);
        $routes->post('add-fixed-lessons', 'ScheduleController::addFixedLessonsForDay', ['as' => 'schedule.addFixed']);
        $routes->post('delete-day-lessons', 'ScheduleController::deleteLessonsForDay', ['as' => 'schedule.deleteForDay']);
        $routes->post('add-all-fixed', 'ScheduleController::addAllFixedLessonsForDay', ['as' => 'schedule.addAllFixed']);
        $routes->post('delete-all-day', 'ScheduleController::deleteAllLessonsForDay', ['as' => 'schedule.deleteAllForDay']);

        
    });
    
    // ...
    $routes->get('schedule/suggestions', 'ScheduleController::getStudentSuggestions', ['as' => 'schedule.suggestions']);
    // ...
    // Duyuru Yönetimi Rotaları
    $routes->group('admin/announcements', ['filter' => 'group:admin,yonetici,mudur,sekreter'], static function ($routes) {
        $routes->get('/', 'Admin\AnnouncementController::index', ['as' => 'admin.announcements.index']);
        $routes->get('new', 'Admin\AnnouncementController::new', ['as' => 'admin.announcements.new']);
        $routes->post('create', 'Admin\AnnouncementController::create', ['as' => 'admin.announcements.create']);
        $routes->get('edit/(:num)', 'Admin\AnnouncementController::edit/$1', ['as' => 'admin.announcements.edit']);
        $routes->post('update/(:num)', 'Admin\AnnouncementController::update/$1', ['as' => 'admin.announcements.update']);
        $routes->post('delete/(:num)', 'Admin\AnnouncementController::delete/$1', ['as' => 'admin.announcements.delete']);
    });

    // Bildirim Gönderme Rotası
    $routes->post('notifications/send-manual', 'NotificationController::sendManualNotification', ['filter' => 'group:admin,yonetici,mudur,sekreter', 'as' => 'notifications.sendManual']);
    
    // --------------------------------------------------------------------
    // ADMİN GRUBU (Sadece 'admin' grubundakiler erişebilir)
    // --------------------------------------------------------------------
    $routes->group('admin', ['filter' => 'group:admin'], static function ($routes) {

        $routes->get('ai-trainer', 'Admin\DataImportController::history', ['as' => 'admin.ai.trainer']);
        $routes->post('ai-trainer', 'Admin\DataImportController::processUpload', ['as' => 'admin.ai.processUpload']);

        // Ders Hakları Yönetimi
    $routes->get('entitlements/import', 'Admin\EntitlementController::importView', ['as' => 'admin.entitlements.import']);
    $routes->post('entitlements/import', 'Admin\EntitlementController::processImport', ['as' => 'admin.entitlements.process']);

  // --- SABİT DERS PROGRAMI ROTLARI ---
        $routes->group('fixed-schedule', ['namespace' => 'App\Controllers\Admin'], static function ($routes) {
        $routes->get('/', 'FixedScheduleController::index', ['as' => 'admin.fixed_schedule.index']);
        // AJAX işlemleri için rotaları daha sonra buraya ekleyeceğiz

 
        // --- YENİ EKLENEN AJAX ROTALARI ---
        $routes->get('get-day-details/(:num)/(:num)', 'FixedScheduleController::getDayDetails/$1/$2', ['as' => 'admin.fixed_schedule.get_details']);
        $routes->post('save', 'FixedScheduleController::saveLesson', ['as' => 'admin.fixed_schedule.save']);
        $routes->post('delete', 'FixedScheduleController::deleteLesson', ['as' => 'admin.fixed_schedule.delete']);
        $routes->get('get-cell-content/(:num)/(:num)', 'FixedScheduleController::getCellContent/$1/$2', ['as' => 'admin.fixed_schedule.get_cell']);


    });
        
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

        // Raporlar
        $routes->match(['get', 'post'], 'reports/monthly', 'Admin\ReportController::monthly', ['as' => 'admin.reports.monthly']);

    });
    
});