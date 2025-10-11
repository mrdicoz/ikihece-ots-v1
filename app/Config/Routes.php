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
$routes->get('maintenance', 'Home::maintenance', ['as' => 'maintenance']);
$routes->post('notifications/unsubscribe', 'NotificationController::unsubscribe');




// ====================================================================
// GİRİŞ GEREKTİREN ALAN (Güvenlik Duvarımız)
// ====================================================================
$routes->group('', ['filter' => 'session'], static function ($routes) {


    // --- ANA GİRİŞ KAPISI ---
    $routes->get('/', 'DashboardController::index', ['as' => 'home']);
    

    // Servis takip rotaları
    $routes->post('api/location/save', 'Api\LocationController::saveLocation', ['filter' => 'group:servis']);
    $routes->get('api/location/drivers', 'Api\LocationController::getActiveDrivers', ['filter' => 'group:admin,servis,mudur']);
    $routes->get('tracking/map', 'TrackingController::map', ['as' => 'tracking.map', 'filter' => 'group:admin,mudur,sekreter,ogretmen,servis,veli']);
    

    // --- YÖNLENDİRME HEDEFLERİ ---
    $routes->group('dashboard', static function ($routes) {
        $routes->get('admin', 'DashboardController::admin', ['as' => 'dashboard.admin', 'filter' => 'group:admin']);
        $routes->get('yonetici', 'DashboardController::yonetici', ['as' => 'dashboard.yonetici', 'filter' => 'group:admin,yonetici']);
        $routes->get('mudur', 'DashboardController::mudur', ['as' => 'dashboard.mudur', 'filter' => 'group:admin,mudur']);
        $routes->get('sekreter', 'DashboardController::sekreter', ['as' => 'dashboard.sekreter', 'filter' => 'group:admin,sekreter']);
        $routes->get('teacher', 'DashboardController::teacher', ['as' => 'dashboard.teacher', 'filter' => 'group:admin,ogretmen']);
        $routes->get('parent', 'DashboardController::parent', ['as' => 'dashboard.parent', 'filter' => 'group:admin,veli']);
        $routes->get('servis', 'DashboardController::servis', ['as' => 'dashboard.servis', 'filter' => 'group:admin,servis,mudur,sekreter']);
        
        
        
        // Veli için özel rotalar
        $routes->post('set-active-child', 'DashboardController::setActiveChild', ['filter' => 'group:veli']);
    });


    // --- KULLANICI PROFİL YÖNETİMİ ---
    // Yeni kullanıcıların profil tamamlama adımları
    $routes->group('onboarding', static function ($routes) {
        $routes->get('role', 'OnboardingController::showRoleSelection', ['as' => 'onboarding.role']);
        $routes->post('role', 'OnboardingController::processRoleSelection');
        $routes->get('profile', 'OnboardingController::showProfileForm', ['as' => 'onboarding.profile']);
        $routes->post('profile', 'OnboardingController::processProfileForm');
    });

    // Mevcut kullanıcıların profil yönetimi
    $routes->group('profile', static function ($routes) {
        $routes->get('/', 'ProfileController::index', ['as' => 'profile']);
        $routes->post('update', 'ProfileController::update');
        $routes->get('get-districts/(:num)', 'ProfileController::getDistricts/$1');
    });
    
    // Rol değiştirme
    $routes->get('user/switch-role/(:segment)', 'ProfileController::switchRole/$1', ['as' => 'user.switchRole']);


    // --- BİLDİRİM YÖNETİMİ ---
    $routes->group('notifications', static function ($routes) {
        $routes->post('subscribe', 'NotificationController::saveSubscription', ['as' => 'notifications.subscribe']);
        $routes->get('vapid-key', 'NotificationController::getVapidKey', ['as' => 'notifications.vapidKey']);
        $routes->post('send-manual', 'NotificationController::sendManualNotification', 
            ['filter' => 'group:admin,yonetici,mudur,sekreter', 'as' => 'notifications.sendManual']);
    });


    // --- DUYURU SİSTEMİ ---
    // Herkese açık duyuru görüntüleme
    $routes->get('duyurular', 'AnnouncementController::index', ['as' => 'announcements.index']);
    

    // --- ÖĞRENCİ YÖNETİMİ ---
    // Bu grup, aşağıdaki tüm öğrenci rotalarının sadece yetkili roller tarafından erişilmesini sağlar.
    $routes->group('students', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen'], static function ($routes) {
        // Öğrenci listesi
        $routes->get('/', 'StudentController::index');
        // Yeni öğrenci ekleme formu
        $routes->get('new', 'StudentController::new');
        // Tek bir öğrencinin detay sayfası
        $routes->get('(:num)', 'StudentController::show/$1');
        // Yeni öğrenciyi veritabanına kaydetme
        $routes->post('/', 'StudentController::create');
        // Öğrenci düzenleme formu
        $routes->get('(:num)/edit', 'StudentController::edit/$1');
        // Öğrenci bilgilerini güncelleme (formdan _method ile PUT olarak gelir)
        $routes->post('(:num)', 'StudentController::update/$1');
        $routes->put('(:num)', 'StudentController::update/$1');
        // ✅ DÜZELTME: Öğrenci silme rotası
        $routes->delete('(:num)', 'StudentController::delete/$1');
        $routes->post('(:num)/delete', 'StudentController::delete/$1'); // Form compatibility için
        
        // RAM Raporu görüntüleme
        $routes->get('view-ram-report/(:num)', 'StudentController::viewRamReport/$1', ['as' => 'students.viewRamReport']);
        $routes->post('analyze-single-ram/(:num)', 'StudentController::analyzeSingleRam/$1');

    });

    // Öğretmenin kendi öğrencilerini göreceği sayfa
    $routes->get('my-students', 'StudentController::myStudents', ['filter' => 'group:admin,ogretmen', 'as' => 'students.my']);


    // --- DEĞERLENDİRME SİSTEMİ ---
    $routes->group('evaluations', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen'], static function ($routes) {
        $routes->post('create', 'EvaluationController::create', ['as' => 'evaluations.create']);
        $routes->post('delete/(:num)', 'EvaluationController::delete/$1', ['as' => 'evaluations.delete']);
        $routes->get('get/(:num)', 'EvaluationController::get/$1', ['as' => 'evaluations.get']);
        $routes->post('update/(:num)', 'EvaluationController::update/$1', ['as' => 'evaluations.update']);
    });


    // --- DERS PROGRAMI ---
    // Bu grup, ders programı ile ilgili tüm rotaları içerir ve yetki kontrolü yapar.
    $routes->group('schedule', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen,veli'], static function ($routes) {
        
        // Rol bazlı ana takvim sayfaları
        $routes->get('/', 'ScheduleController::index', ['as' => 'schedule.index', 'filter' => 'group:admin,yonetici,mudur,sekreter']);
        $routes->get('my-schedule', 'ScheduleController::mySchedule', ['filter' => 'group:admin,ogretmen', 'as' => 'schedule.my']);
        $routes->get('parent', 'ScheduleController::parentSchedule', ['as' => 'parent.schedule', 'filter' => 'group:veli']);
        
        // Günlük programı gösteren sayfa
        $routes->get('daily/(:segment)', 'ScheduleController::dailyGrid/$1', ['as' => 'schedule.daily']);

        // Takvimin arka planda (AJAX) kullandığı rotalar
        $routes->get('get-month-lessons', 'ScheduleController::getLessonsForMonth', ['as' => 'schedule.get_month_lessons']);
        $routes->get('get-students', 'ScheduleController::getStudentsForSelect', ['as' => 'schedule.get_students']);
        $routes->get('suggestions', 'ScheduleController::getStudentSuggestions', ['as' => 'schedule.suggestions']);
        $routes->post('create-lesson', 'ScheduleController::createLesson', ['as' => 'schedule.create']);
        $routes->get('get-lesson-details/(:num)', 'ScheduleController::getLessonDetails/$1', ['as' => 'schedule.get_details']);
        $routes->post('delete-lesson/(:num)', 'ScheduleController::deleteLesson/$1', ['as' => 'schedule.delete_lesson']);
        $routes->get('get-lesson-dates', 'ScheduleController::getLessonDates', ['as' => 'schedule.get_lesson_dates']);
        $routes->post('update-lesson/(:num)', 'ScheduleController::updateLesson/$1', ['as' => 'schedule.update']);
        
        // Toplu ders işlemleri
        $routes->post('add-fixed-lessons', 'ScheduleController::addFixedLessonsForDay', ['as' => 'schedule.addFixed']);
        $routes->post('delete-day-lessons', 'ScheduleController::deleteLessonsForDay', ['as' => 'schedule.deleteForDay']);
        $routes->post('add-all-fixed', 'ScheduleController::addAllFixedLessonsForDay', ['as' => 'schedule.addAllFixed']);
        $routes->post('delete-all-day', 'ScheduleController::deleteAllLessonsForDay', ['as' => 'schedule.deleteAllForDay']);
    });


    // --- YAPAY ZEKA ASİSTANI ---
    $routes->group('ai', ['filter' => 'group:admin,yonetici,mudur,sekreter,ogretmen'], static function ($routes) {
        $routes->get('assistant', 'AIController::assistantView', ['as' => 'ai.assistant']);
        $routes->post('assistant', 'AIController::processMessage', ['as' => 'ai.processMessage']);
        $routes->post('process', 'AIController::processAjax', ['as' => 'ai.process']);
    });


    // --- YÖNETİM GRUBU ROTALARI (admin, yonetici, mudur, sekreter) ---
    // Bu grup, birden fazla yönetimsel rolün erişebileceği özellikleri barındırır.
    $routes->group('admin', ['filter' => 'group:admin,yonetici,mudur,sekreter'], static function($routes) {
            
        // DUYURU YÖNETİMİ
        $routes->group('announcements', static function ($routes) {
            $routes->get('/', 'Admin\AnnouncementController::index', ['as' => 'admin.announcements.index']);
            $routes->get('new', 'Admin\AnnouncementController::new', ['as' => 'admin.announcements.new']);
            $routes->post('create', 'Admin\AnnouncementController::create', ['as' => 'admin.announcements.create']);
            $routes->get('edit/(:num)', 'Admin\AnnouncementController::edit/$1', ['as' => 'admin.announcements.edit']);
            $routes->post('update/(:num)', 'Admin\AnnouncementController::update/$1', ['as' => 'admin.announcements.update']);
            $routes->post('delete/(:num)', 'Admin\AnnouncementController::delete/$1', ['as' => 'admin.announcements.delete']);
        });

        // SABİT DERS PROGRAMI
        $routes->group('fixed-schedule', static function ($routes) {
            $routes->get('/', 'Admin\FixedScheduleController::index', ['as' => 'admin.fixed_schedule.index']);
            $routes->get('get-data', 'Admin\FixedScheduleController::getScheduleData', ['as' => 'admin.fixed_schedule.get_data']);
            $routes->post('save-slot', 'Admin\FixedScheduleController::saveSlot', ['as' => 'admin.fixed_schedule.save_slot']);
        });

        // RAPORLAR
        $routes->match(['get', 'post'], 'reports/monthly', 'Admin\ReportController::monthly', ['as' => 'admin.reports.monthly']);
    });


    // --- SADECE ADMİN ROTALARI ---
    // Bu bölüm sadece 'admin' rolüne sahip kullanıcılar için
    $routes->group('admin', ['filter' => 'group:admin'], static function ($routes) {

        // KULLANICI YÖNETİMİ
        $routes->group('users', static function ($routes) {
            $routes->get('/', 'Admin\UserController::index', ['as' => 'admin.users.index']);
            $routes->get('new', 'Admin\UserController::new', ['as' => 'admin.users.new']);
            $routes->post('create', 'Admin\UserController::create', ['as' => 'admin.users.create']);
            $routes->get('show/(:num)', 'Admin\UserController::show/$1', ['as' => 'admin.users.show']);
            $routes->get('edit/(:num)', 'Admin\UserController::edit/$1', ['as' => 'admin.users.edit']);
            $routes->post('update/(:num)', 'Admin\UserController::update/$1', ['as' => 'admin.users.update']);
            $routes->post('delete/(:num)', 'Admin\UserController::delete/$1', ['as' => 'admin.users.delete']);
            $routes->get('(:any)', 'Admin\UserController::index/$1', ['as' => 'admin.users.index.filtered']);
        });

        // İÇE AKTARMA İŞLEMLERİ
        $routes->group('import', static function ($routes) {
            // Öğrenci içe aktarma
            $routes->get('students', 'Admin\StudentController::importView', ['as' => 'admin.students.importView']);
            $routes->post('students-mapping', 'Admin\StudentController::importMapping', ['as' => 'admin.students.importMapping']);
            $routes->post('students-process', 'Admin\StudentController::importProcess', ['as' => 'admin.students.importProcess']);
            
            // Ders hakları içe aktarma
            $routes->get('entitlements', 'Admin\EntitlementController::importView', ['as' => 'admin.entitlements.import']);
            $routes->post('entitlements', 'Admin\EntitlementController::processImport', ['as' => 'admin.entitlements.process']);
        });

        // YAPAY ZEKA EĞİTİMİ
        $routes->group('ai-trainer', static function ($routes) {
            $routes->get('/', 'Admin\DataImportController::history', ['as' => 'admin.ai.trainer']);
            $routes->post('/', 'Admin\DataImportController::processUpload', ['as' => 'admin.ai.processUpload']);
        });

        // SİSTEM YÖNETİMİ
        $routes->get('logs', 'Admin\LogController::index', ['as' => 'admin.logs.index']);
        
        // KURUM AYARLARI
        $routes->group('institution', static function ($routes) {
            $routes->get('/', 'Admin\InstitutionController::index', ['as' => 'admin.institution.index']);
            $routes->post('save', 'Admin\InstitutionController::save', ['as' => 'admin.institution.save']);
        });
        
        // ATAMALAR
        $routes->group('assignments', static function ($routes) {
            $routes->get('/', 'Admin\AssignmentController::index', ['as' => 'admin.assignments.index']);
            $routes->post('save', 'Admin\AssignmentController::save', ['as' => 'admin.assignments.save']);
            $routes->get('get-assigned/(:num)', 'Admin\AssignmentController::getAssigned/$1', ['as' => 'admin.assignments.getAssigned']);
        });
        
        // GENEL AYARLAR
        $routes->group('settings', static function ($routes) {
            $routes->get('/', 'Admin\SettingsController::index', ['as' => 'admin.settings.index']);
            $routes->post('/', 'Admin\SettingsController::save', ['as' => 'admin.settings.save']);
        });

        // SİSTEM GÜNCELLEMELERİ
        $routes->group('update', static function ($routes) {
            $routes->get('/', 'Admin\UpdateController::index', ['as' => 'admin.update.index']);
            $routes->get('check', 'Admin\UpdateController::check', ['as' => 'admin.update.check']);
            $routes->get('run', 'Admin\UpdateController::runUpdate', ['as' => 'admin.update.run']);
        });
        
        // WEB PUSH ANAHTAR ÜRETİMİ
        $routes->get('generate-keys', 'VapidController::generateKeys', ['as' => 'admin.generateKeys']);

        // MENÜ YÖNETİMİ - DÜZELTİLMİŞ
        $routes->group('menu', static function ($routes) {
            $routes->get('/', 'Admin\MenuController::index', ['as' => 'admin.menu.index']);
            $routes->match(['GET', 'POST'], 'group/create', 'Admin\MenuController::createGroup', ['as' => 'admin.menu.group.create']);
            $routes->match(['GET', 'POST'], 'item/create', 'Admin\MenuController::createItem', ['as' => 'admin.menu.item.create']);
            $routes->match(['GET', 'POST'], 'item/(:num)', 'Admin\MenuController::updateItem/$1', ['as' => 'admin.menu.item.update']);
            $routes->delete('item/(:num)', 'Admin\MenuController::deleteItem/$1', ['as' => 'admin.menu.item.delete']);
        });
    });

});