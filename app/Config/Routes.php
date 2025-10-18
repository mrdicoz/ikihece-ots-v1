<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ====================================================================
// TEMEL KURALLAR (Herkese Açık Alan)
// ====================================================================
service('auth')->routes($routes);

// Herkese açık rotalar
$routes->GET('maintenance', 'Home::maintenance', ['as' => 'maintenance']);
$routes->POST('notifications/unsubscribe', 'NotificationController::unsubscribe');



// ====================================================================
// GİRİŞ GEREKTİREN ALAN (Güvenlik Duvarımız)
// ====================================================================
$routes->group('', ['filter' => 'session'], static function ($routes) {


    // --- ANA GİRİŞ KAPISI ---
    $routes->GET('/', 'DashboardController::index', ['as' => 'home']);
    

    // --- YÖNLENDİRME HEDEFLERİ ---
    $routes->group('dashboard', static function ($routes) {
        $routes->GET('admin', 'DashboardController::admin', ['as' => 'dashboard.admin', 'filter' => 'group:admin']);
        $routes->GET('yonetici', 'DashboardController::yonetici', ['as' => 'dashboard.yonetici', 'filter' => 'group:admin,yonetici']);
        $routes->GET('mudur', 'DashboardController::mudur', ['as' => 'dashboard.mudur', 'filter' => 'group:admin,mudur']);
        $routes->GET('sekreter', 'DashboardController::sekreter', ['as' => 'dashboard.sekreter', 'filter' => 'group:admin,sekreter']);
        $routes->GET('teacher', 'DashboardController::teacher', ['as' => 'dashboard.teacher', 'filter' => 'group:admin,ogretmen']);
        $routes->GET('parent', 'DashboardController::parent', ['as' => 'dashboard.parent', 'filter' => 'group:admin,veli']);
        $routes->GET('servis', 'DashboardController::servis', ['as' => 'dashboard.servis', 'filter' => 'group:admin,servis,mudur,sekreter']);
        
        
        
        // Veli için özel rotalar
        $routes->POST('set-active-child', 'DashboardController::setActiveChild', ['filter' => 'group:veli']);
    });


    // --- KULLANICI PROFİL YÖNETİMİ ---
    // Yeni kullanıcıların profil tamamlama adımları
    $routes->group('onboarding', static function ($routes) {
        $routes->GET('role', 'OnboardingController::showRoleSelection', ['as' => 'onboarding.role']);
        $routes->POST('role', 'OnboardingController::processRoleSelection');
        $routes->GET('profile', 'OnboardingController::showProfileForm', ['as' => 'onboarding.profile']);
        $routes->POST('profile', 'OnboardingController::processProfileForm');
    });

    // Mevcut kullanıcıların profil yönetimi
    $routes->group('profile', static function ($routes) {
        $routes->GET('/', 'ProfileController::index', ['as' => 'profile']);
        $routes->POST('update', 'ProfileController::update');
        $routes->GET('get-districts/(:num)', 'ProfileController::getDistricts/$1');
    });
    
    // Rol değiştirme
    $routes->GET('user/switch-role/(:segment)', 'ProfileController::switchRole/$1', ['as' => 'user.switchRole']);


    // --- BİLDİRİM YÖNETİMİ ---
    $routes->group('notifications', static function ($routes) {
        $routes->POST('subscribe', 'NotificationController::saveSubscription', ['as' => 'notifications.subscribe']);
        $routes->GET('vapid-key', 'NotificationController::getVapidKey', ['as' => 'notifications.vapidKey']);
        $routes->POST('send-manual', 'NotificationController::sendManualNotification', 
            ['filter' => 'group:admin,yonetici,mudur,sekreter', 'as' => 'notifications.sendManual']);
    });


    // --- DUYURU SİSTEMİ ---
    // Herkese açık duyuru görüntüleme
    $routes->GET('duyurular', 'AnnouncementController::index', ['as' => 'announcements.index']);
    

    // --- ÖĞRENCİ YÖNETİMİ ---
    // Bu grup, aşağıdaki tüm öğrenci rotalarının sadece yetkili roller tarafından erişilmesini sağlar.
    $routes->group('students', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen'], static function ($routes) {
        // Öğrenci listesi
        $routes->GET('/', 'StudentController::index');
        // Yeni öğrenci ekleme formu
        $routes->GET('new', 'StudentController::new');
        // Tek bir öğrencinin detay sayfası
        $routes->GET('(:num)', 'StudentController::show/$1');
        // Yeni öğrenciyi veritabanına kaydetme
        $routes->POST('/', 'StudentController::create');
        // Öğrenci düzenleme formu
        $routes->GET('(:num)/edit', 'StudentController::edit/$1');
        // Öğrenci bilgilerini güncelleme (formdan _method ile PUT olarak gelir)
        $routes->POST('(:num)', 'StudentController::update/$1');
        $routes->put('(:num)', 'StudentController::update/$1');
        // ✅ DÜZELTME: Öğrenci silme rotası
        $routes->delete('(:num)', 'StudentController::delete/$1');
        $routes->POST('(:num)/delete', 'StudentController::delete/$1'); // Form compatibility için
        
        // RAM Raporu görüntüleme
        $routes->GET('view-ram-report/(:num)', 'StudentController::viewRamReport/$1', ['as' => 'students.viewRamReport']);
        $routes->POST('analyze-single-ram/(:num)', 'StudentController::analyzeSingleRam/$1');

    });

    // Öğretmenin kendi öğrencilerini göreceği sayfa
    $routes->GET('my-students', 'StudentController::myStudents', ['filter' => 'group:admin,ogretmen', 'as' => 'students.my']);


    // --- DEĞERLENDİRME SİSTEMİ ---
    $routes->group('evaluations', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen'], static function ($routes) {
        $routes->POST('create', 'EvaluationController::create', ['as' => 'evaluations.create']);
        $routes->POST('delete/(:num)', 'EvaluationController::delete/$1', ['as' => 'evaluations.delete']);
        $routes->GET('get/(:num)', 'EvaluationController::get/$1', ['as' => 'evaluations.get']);
        $routes->POST('update/(:num)', 'EvaluationController::update/$1', ['as' => 'evaluations.update']);
    });


    // --- DERS PROGRAMI ---
    // Bu grup, ders programı ile ilgili tüm rotaları içerir ve yetki kontrolü yapar.
    $routes->group('schedule', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen,veli'], static function ($routes) {
        
        // Rol bazlı ana takvim sayfaları
        $routes->GET('/', 'ScheduleController::index', ['as' => 'schedule.index', 'filter' => 'group:admin,yonetici,mudur,sekreter']);
        $routes->GET('my-schedule', 'ScheduleController::mySchedule', ['filter' => 'group:admin,ogretmen', 'as' => 'schedule.my']);
        $routes->GET('parent', 'ScheduleController::parentSchedule', ['as' => 'parent.schedule', 'filter' => 'group:veli']);
        
        // Günlük programı gösteren sayfa
        $routes->GET('daily/(:segment)', 'ScheduleController::dailyGrid/$1', ['as' => 'schedule.daily']);

        // Takvimin arka planda (AJAX) kullandığı rotalar
        $routes->GET('get-month-lessons', 'ScheduleController::getLessonsForMonth', ['as' => 'schedule.get_month_lessons']);
        $routes->GET('get-students', 'ScheduleController::getStudentsForSelect', ['as' => 'schedule.get_students']);
        $routes->GET('suggestions', 'ScheduleController::getStudentSuggestions', ['as' => 'schedule.suggestions']);
        $routes->POST('create-lesson', 'ScheduleController::createLesson', ['as' => 'schedule.create']);
        $routes->GET('get-lesson-details/(:num)', 'ScheduleController::getLessonDetails/$1', ['as' => 'schedule.get_details']);
        $routes->POST('delete-lesson/(:num)', 'ScheduleController::deleteLesson/$1', ['as' => 'schedule.delete_lesson']);
        $routes->GET('get-lesson-dates', 'ScheduleController::getLessonDates', ['as' => 'schedule.get_lesson_dates']);
        $routes->POST('update-lesson/(:num)', 'ScheduleController::updateLesson/$1', ['as' => 'schedule.update']);
        
        // Toplu ders işlemleri
        $routes->POST('add-fixed-lessons', 'ScheduleController::addFixedLessonsForDay', ['as' => 'schedule.addFixed']);
        $routes->POST('delete-day-lessons', 'ScheduleController::deleteLessonsForDay', ['as' => 'schedule.deleteForDay']);
        $routes->POST('add-all-fixed', 'ScheduleController::addAllFixedLessonsForDay', ['as' => 'schedule.addAllFixed']);
        $routes->POST('delete-all-day', 'ScheduleController::deleteAllLessonsForDay', ['as' => 'schedule.deleteAllForDay']);
    });


    // --- YAPAY ZEKA ASİSTANI ---
    $routes->group('ai', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen,veli'], static function ($routes) {
        $routes->GET('assistant', 'AIController::assistantView', ['as' => 'ai.assistant']);
        $routes->POST('assistant', 'AIController::processMessage', ['as' => 'ai.processMessage']);
        $routes->POST('process', 'AIController::processAjax', ['as' => 'ai.process']);
    });


    // --- YÖNETİM GRUBU ROTALARI (admin, yonetici, mudur, sekreter) ---
    // Bu grup, birden fazla yönetimsel rolün erişebileceği özellikleri barındırır.
    $routes->group('admin', ['filter' => 'group:admin,yonetici,mudur,sekreter'], static function($routes) {
            
        // DUYURU YÖNETİMİ
        $routes->group('announcements', static function ($routes) {
            $routes->GET('/', 'Admin\AnnouncementController::index', ['as' => 'admin.announcements.index']);
            $routes->GET('new', 'Admin\AnnouncementController::new', ['as' => 'admin.announcements.new']);
            $routes->POST('create', 'Admin\AnnouncementController::create', ['as' => 'admin.announcements.create']);
            $routes->GET('edit/(:num)', 'Admin\AnnouncementController::edit/$1', ['as' => 'admin.announcements.edit']);
            $routes->POST('update/(:num)', 'Admin\AnnouncementController::update/$1', ['as' => 'admin.announcements.update']);
            $routes->POST('delete/(:num)', 'Admin\AnnouncementController::delete/$1', ['as' => 'admin.announcements.delete']);
        });

        // SABİT DERS PROGRAMI
        $routes->group('fixed-schedule', static function ($routes) {
            $routes->GET('/', 'Admin\FixedScheduleController::index', ['as' => 'admin.fixed_schedule.index']);
            $routes->GET('get-data', 'Admin\FixedScheduleController::getScheduleData', ['as' => 'admin.fixed_schedule.get_data']);
            $routes->POST('save-slot', 'Admin\FixedScheduleController::saveSlot', ['as' => 'admin.fixed_schedule.save_slot']);
        });

        // RAPORLAR
        $routes->match(['GET', 'POST'], 'reports/monthly', 'Admin\ReportController::monthly', ['as' => 'admin.reports.monthly']);
    });


    // --- SADECE ADMİN ROTALARI ---
    // Bu bölüm sadece 'admin' rolüne sahip kullanıcılar için
    $routes->group('admin', ['filter' => 'group:admin'], static function ($routes) {

        // KULLANICI YÖNETİMİ
        $routes->group('users', static function ($routes) {
            $routes->GET('/', 'Admin\UserController::index', ['as' => 'admin.users.index']);
            $routes->GET('new', 'Admin\UserController::new', ['as' => 'admin.users.new']);
            $routes->POST('create', 'Admin\UserController::create', ['as' => 'admin.users.create']);
            $routes->GET('show/(:num)', 'Admin\UserController::show/$1', ['as' => 'admin.users.show']);
            $routes->GET('edit/(:num)', 'Admin\UserController::edit/$1', ['as' => 'admin.users.edit']);
            $routes->POST('update/(:num)', 'Admin\UserController::update/$1', ['as' => 'admin.users.update']);
            $routes->POST('delete/(:num)', 'Admin\UserController::delete/$1', ['as' => 'admin.users.delete']);
            $routes->GET('(:any)', 'Admin\UserController::index/$1', ['as' => 'admin.users.index.filtered']);
        });

        // İÇE AKTARMA İŞLEMLERİ
        $routes->group('import', static function ($routes) {
            // Öğrenci içe aktarma
            $routes->GET('students', 'Admin\StudentController::importView', ['as' => 'admin.students.importView']);
            $routes->POST('students-mapping', 'Admin\StudentController::importMapping', ['as' => 'admin.students.importMapping']);
            $routes->POST('students-process', 'Admin\StudentController::importProcess', ['as' => 'admin.students.importProcess']);
            
            // Ders hakları içe aktarma
            $routes->GET('entitlements', 'Admin\EntitlementController::importView', ['as' => 'admin.entitlements.import']);
            $routes->POST('entitlements', 'Admin\EntitlementController::processImport', ['as' => 'admin.entitlements.process']);
        });

        // YAPAY ZEKA EĞİTİMİ
        $routes->group('ai-trainer', static function ($routes) {
            $routes->GET('/', 'Admin\DataImportController::history', ['as' => 'admin.ai.trainer']);
            $routes->POST('/', 'Admin\DataImportController::processUpload', ['as' => 'admin.ai.processUpload']);
        });

        // SİSTEM YÖNETİMİ
        $routes->GET('logs', 'Admin\LogController::index', ['as' => 'admin.logs.index']);
        
        // KURUM AYARLARI
        $routes->group('institution', static function ($routes) {
            $routes->GET('/', 'Admin\InstitutionController::index', ['as' => 'admin.institution.index']);
            $routes->POST('save', 'Admin\InstitutionController::save', ['as' => 'admin.institution.save']);
        });
        
        // ATAMALAR
        $routes->group('assignments', static function ($routes) {
            $routes->GET('/', 'Admin\AssignmentController::index', ['as' => 'admin.assignments.index']);
            $routes->POST('save', 'Admin\AssignmentController::save', ['as' => 'admin.assignments.save']);
            $routes->GET('get-assigned/(:num)', 'Admin\AssignmentController::getAssigned/$1', ['as' => 'admin.assignments.getAssigned']);
        });
        
        // GENEL AYARLAR
        $routes->group('settings', static function ($routes) {
            $routes->GET('/', 'Admin\SettingsController::index', ['as' => 'admin.settings.index']);
            $routes->POST('/', 'Admin\SettingsController::save', ['as' => 'admin.settings.save']);
        });

        // SİSTEM GÜNCELLEMELERİ
        $routes->group('update', static function ($routes) {
            $routes->GET('/', 'Admin\UpdateController::index', ['as' => 'admin.update.index']);
            $routes->GET('check', 'Admin\UpdateController::check', ['as' => 'admin.update.check']);
            $routes->GET('run', 'Admin\UpdateController::runUpdate', ['as' => 'admin.update.run']);
        });
        
        // WEB PUSH ANAHTAR ÜRETİMİ
        $routes->GET('generate-keys', 'VapidController::generateKeys', ['as' => 'admin.generateKeys']);

        // MENÜ YÖNETİMİ - DÜZELTİLMİŞ
        $routes->group('menu', static function ($routes) {
            $routes->GET('/', 'Admin\MenuController::index', ['as' => 'admin.menu.index']);
            $routes->match(['GET', 'POST'], 'group/create', 'Admin\MenuController::createGroup', ['as' => 'admin.menu.group.create']);
            $routes->match(['GET', 'POST'], 'item/create', 'Admin\MenuController::createItem', ['as' => 'admin.menu.item.create']);
            $routes->match(['GET', 'POST'], 'item/(:num)', 'Admin\MenuController::updateItem/$1', ['as' => 'admin.menu.item.update']);
            $routes->delete('item/(:num)', 'Admin\MenuController::deleteItem/$1', ['as' => 'admin.menu.item.delete']);
        });
    });

});

// ============================================
// MOBİL UYGULAMA API'LERİ (DOKUNULMAYACAK)
// ============================================
$routes->group('api/mobile', static function ($routes) {
    // Not: Bu grup içindeki rotalarınız olduğu gibi kalacak.
    // Harita için gerekli olan rota artık bu grupta değil.
    $routes->get('test-connection', 'Api\TestConnection::index');
    $routes->post('login', 'Api\Auth::login');
    $routes->post('location/save', 'Api\Location::save');
    $routes->get('location/user/(:num)', 'Api\Location::getByUserId/$1');
    $routes->get('location/email/(:segment)', 'Api\Location::getByEmail/$1');
    $routes->get('students/daily', 'Api\StudentController::dailyList');
    // Diğer mobil API rotalarınız...
});


// ============================================
// WEB UYGULAMASI - SERVİS TAKİP (YENİ DÜZENLEME)
// ============================================
// Bu grup, hem harita sayfasını hem de veri çekeceği API'yi içerir.
// 'session' filtresi ile sadece giriş yapmış kullanıcıların erişimi sağlanır.
$routes->group('tracking', ['filter' => 'session'], static function ($routes) {
    
    // Harita sayfasını görüntüleyen rota
    // URL: http://localhost/tracking/map
    $routes->get('map', 'TrackingController::map', [
        'as' => 'tracking.map', // Rota ismi
        'filter' => 'group:admin,mudur,sekreter,ogretmen,servis,veli'
    ]);

    // Haritanın veri çekeceği API rotası (SADECE WEB İÇİN)
    // URL: http://localhost/tracking/locations
    $routes->get('locations', 'TrackingController::getDriverLocations', [
        'as' => 'tracking.locations', // Rota ismi
        'filter' => 'group:admin,mudur,sekreter,ogretmen,servis,veli'
    ]);
});