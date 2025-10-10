<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\AuthGroupsUsersModel; // UserModel yerine grupları saymak için bu daha verimli
use App\Models\AnnouncementModel; // Duyurular için bu modelin oluşturulduğunu varsayıyoruz
use App\Models\UserProfileModel;

class DashboardController extends BaseController
{
    /**
     * Kullanıcıyı rolüne göre uygun dashboard'a yönlendirir.
     */
public function index()
    {
        // BaseController'da belirlenen aktif rolü session'dan alıyoruz.
        $activeRole = session()->get('active_role');

        // YÖNLENDİRME MANTIĞINI 'active_role' DEĞİŞKENİNE GÖRE YAPIYORUZ
        if (in_array($activeRole, ['admin'])) {
            return redirect()->to(route_to('dashboard.admin'));
        }

        if (in_array($activeRole, ['yonetici'])) {
            return redirect()->to(route_to('dashboard.yonetici'));
        }

        if (in_array($activeRole, ['mudur'])) {
            return redirect()->to(route_to('dashboard.mudur'));
        }

        if (in_array($activeRole, ['sekreter'])) {
            return redirect()->to(route_to('dashboard.sekreter'));
        }

        if ($activeRole === 'ogretmen') {
            return redirect()->to(route_to('dashboard.teacher'));
        }

        
        if (in_array($activeRole, ['servis'])) {
            return redirect()->to(route_to('dashboard.servis'));
        }


        if ($activeRole === 'veli') {
            return redirect()->to(route_to('dashboard.parent'));
        }
        
        // Hiçbir koşul eşleşmezse varsayılan panele gitsin.
        return redirect()->to(route_to('dashboard.parent'));
    }

    /**
     * Yönetici, Müdür, Sekreter gibi roller için varsayılan dashboard'u gösterir.
     * Bu metot, yonetici.php view'i için gerekli tüm verileri hazırlar.
     */
    public function admin()
    {
        // Gerekli Modelleri Yükle
        $studentModel = new StudentModel();
        $groupsModel = new AuthGroupsUsersModel();
        
        // AnnouncementModel'in projenizde olduğundan emin olun. Yoksa oluşturulması gerekir.
        // Şimdilik hata vermemesi için varlığını kontrol edelim.
        $announcementModel = class_exists(\App\Models\AnnouncementModel::class) ? new \App\Models\AnnouncementModel() : null;

        // 1. İstatistikleri Çek
        $stats = [
            'students' => $studentModel->where('deleted_at', null)->countAllResults(),
            'teachers' => $groupsModel->where('group', 'ogretmen')->countAllResults(),
            'parents'  => $groupsModel->where('group', 'veli')->countAllResults(),
            'services' => $groupsModel->where('group', 'servis')->countAllResults(),
        ];

        // 2. Son 5 Duyuruyu Çek
        $latestAnnouncements = [];
        if ($announcementModel) {
            $latestAnnouncements = $announcementModel->orderBy('created_at', 'DESC')->findAll(5);
        }

        // 3. Son Eklenen Öğrencileri Çek (Tasarımda tablo olduğu için)
        $students = $studentModel->orderBy('created_at', 'DESC')->findAll(10); // Son 10 öğrenci

        // 4. Verileri View'e Gönder
        $this->data['title'] = "Yönetim Paneli";
        $this->data['stats'] = $stats;
        $this->data['latestAnnouncements'] = $latestAnnouncements;
        $this->data['students'] = $students;

        return view('dashboard/admin', $this->data);
    }

    public function yonetici()
    {
        // Gerekli modelleri yükle
        $studentModel = new StudentModel();
        $reportModel = new \App\Models\ReportModel();
        $announcementModel = class_exists(\App\Models\AnnouncementModel::class) ? new \App\Models\AnnouncementModel() : null;
        
        // Mevcut ay ve yıl
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // 1. Ana İstatistikler (Kart Alanları)
        $monthlyStats = [
            'total_lesson_hours' => $reportModel->getMonthlySummary($currentYear, $currentMonth)['total_hours'],
            'students_with_lessons' => $reportModel->getMonthlySummary($currentYear, $currentMonth)['total_students'],
            'new_students' => count($studentModel->getNewStudentsThisMonth($currentYear, $currentMonth)),
            'deleted_students' => count($studentModel->getDeletedStudentsThisMonth($currentYear, $currentMonth)),
        ];
        
        // 2. Detaylı Listeler
        $detailedData = [
            'new_students_list' => $studentModel->getNewStudentsThisMonth($currentYear, $currentMonth),
            'deleted_students_list' => $studentModel->getDeletedStudentsThisMonth($currentYear, $currentMonth),
            'teachers_report' => array_reverse($reportModel->getDetailedTeacherReport($currentYear, $currentMonth)),
            'students_no_lessons' => $reportModel->getStudentsWithNoLessons($currentYear, $currentMonth),
            'top_students' => $reportModel->getTopStudentsThisMonth($currentYear, $currentMonth, 6), // YENİ SATIR
        ];
        
        // 3. Duyurular
        $latestAnnouncements = [];
        if ($announcementModel) {
            $latestAnnouncements = $announcementModel->orderBy('created_at', 'DESC')->findAll(5);
        }

        // 4. Grafik verileri (YENİ)
        $chartData = $reportModel->getMonthlyLessonChart(6);
        
        // Verileri view'e gönder
        $this->data['title'] = 'Yönetici Paneli - Aylık Raporlar';
        $this->data['monthlyStats'] = $monthlyStats;
        $this->data['detailedData'] = $detailedData;
        $this->data['latestAnnouncements'] = $latestAnnouncements;
        $this->data['chartData'] = $chartData; // YENİ SATIR
        $this->data['currentMonth'] = $currentMonth;
        $this->data['currentYear'] = $currentYear;
        
        return view('dashboard/yonetici', $this->data);
    }

    public function mudur()
    {
        $studentModel = new StudentModel();
        $reportModel = new \App\Models\ReportModel();
        
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Aylık özet
        $summary = $reportModel->getMonthlySummary($currentYear, $currentMonth);
        
        // Widget verileri
        $this->data['monthlyStats'] = [
            'total_lesson_hours' => $summary['total_hours'],
            'students_with_lessons' => $summary['total_students'],
            'students_no_lessons' => count($reportModel->getStudentsWithNoLessons($currentYear, $currentMonth)),
            'new_students' => count($studentModel->getNewStudentsThisMonth($currentYear, $currentMonth)),
            'deleted_students' => count($studentModel->getDeletedStudentsThisMonth($currentYear, $currentMonth)),
        ];
        
        // Öğrenci detay raporu (ders saatine göre)
        $this->data['studentReport'] = $reportModel->getDetailedStudentReport($currentYear, $currentMonth);
        $this->data['title'] = 'Müdür Paneli';
        
        return view('dashboard/mudur', $this->data);
    }
    
    public function sekreter()
    {
        $studentModel = new StudentModel();
        $lessonModel = new \App\Models\LessonModel();
        
        $today = date('Y-m-d');
        
        // 1. Üst Widget Verileri
        $todayLessons = $lessonModel->where('lesson_date', $today)->countAllResults();
        
        // Bugün ders alan benzersiz öğrenci sayısı
        $studentsWithLessonsToday = $lessonModel
            ->select('COUNT(DISTINCT lesson_students.student_id) as count')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->where('lessons.lesson_date', $today)
            ->get()
            ->getRow()
            ->count ?? 0;
        
        // Eksik bilgili öğrenciler
        $incompleteStudents = $studentModel
            ->where('deleted_at', null)
            ->groupStart()
                ->where('ram_raporu', null)
                ->orWhere('ram_raporu', '')
                ->orWhere('veli_anne_telefon', null)
                ->orWhere('veli_anne_telefon', '')
                ->orGroupStart()
                    ->where('veli_baba_telefon', null)
                    ->orWhere('veli_baba_telefon', '')
                ->groupEnd()
            ->groupEnd()
            ->countAllResults();
        
        // Bugün doğum günü olanlar
        $birthdaysToday = $studentModel
            ->where('deleted_at', null)
            ->where('DAY(dogum_tarihi)', date('d'))
            ->where('MONTH(dogum_tarihi)', date('m'))
            ->countAllResults();
        
        $this->data['widgetStats'] = [
            'today_lessons' => $todayLessons,
            'today_students' => $studentsWithLessonsToday,
            'incomplete_students' => $incompleteStudents,
            'birthdays_today' => $birthdaysToday,
        ];
        
        // 2. Bugünkü Ders Programı (saat bazlı gruplu)
        $this->data['todaySchedule'] = $lessonModel
            ->select('lessons.*, students.adi, students.soyadi, user_profiles.first_name, user_profiles.last_name')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->join('students', 'students.id = lesson_students.student_id')
            ->join('users', 'users.id = lessons.teacher_id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('lessons.lesson_date', $today)
            ->orderBy('lessons.start_time', 'ASC')
            ->findAll();
        
        // 3. Eksik Bilgiler Listesi
        $this->data['incompleteList'] = $studentModel
            ->select('students.id, students.adi, students.soyadi, students.ram_raporu, students.veli_anne_telefon, students.veli_baba_telefon')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('ram_raporu', null)
                ->orWhere('ram_raporu', '')
                ->orWhere('veli_anne_telefon', null)
                ->orWhere('veli_anne_telefon', '')
                ->orGroupStart()
                    ->where('veli_baba_telefon', null)
                    ->orWhere('veli_baba_telefon', '')
                ->groupEnd()
            ->groupEnd()
            ->orderBy('adi', 'ASC')
            ->findAll();
        
        // 4. Doğum Günü Listesi
        $this->data['birthdayList'] = $studentModel
            ->select('id, adi, soyadi, dogum_tarihi, veli_anne_telefon, veli_baba_telefon')
            ->where('deleted_at', null)
            ->where('DAY(dogum_tarihi)', date('d'))
            ->where('MONTH(dogum_tarihi)', date('m'))
            ->orderBy('adi', 'ASC')
            ->findAll();
        
        $this->data['title'] = 'Sekreter Paneli';
        
        return view('dashboard/sekreter', $this->data);
    }

    /**
     * Öğretmen Dashboard'ını gösterir.
     */
    public function teacher()
    {
        // Gerekli modelleri çağır
        $lessonModel = new \App\Models\LessonModel();
        $announcementModel = new \App\Models\AnnouncementModel();
        
        $teacherId = auth()->id();
        $today = date('Y-m-d');

        // Verileri view'e göndermek için hazırla
        $this->data['title'] = 'Öğretmen Paneli';
        
        // 1. Günün derslerini modelden çek
        $this->data['gununDersleri'] = $lessonModel->getLessonsForTeacherByDate($teacherId, $today);
        
        // 2. Duyuruları modelden çek ('all' ve 'ogretmen' hedeflileri)
        $this->data['duyurular'] = $announcementModel->getLatestAnnouncementsForGroups(['all', 'ogretmen'], 5);
        
        return view('dashboard/teacher', $this->data);
    }

public function servis()
{
    $lessonModel = new \App\Models\LessonModel();
    $studentModel = new StudentModel();
    
    $today = date('Y-m-d');
    
    // SADECE SERVİS KULLANAN öğrencileri çek ve SAATE GÖRE SIRALA
    $todayStudents = $studentModel
        ->select('students.id, students.adi, students.soyadi, students.profile_image, students.servis, students.mesafe, students.adres_detayi as adres, students.veli_anne_telefon, students.veli_baba_telefon, students.google_konum, students.iletisim, cities.name as city_name, districts.name as district_name, lessons.start_time, lessons.end_time')
        ->distinct()
        ->join('lesson_students', 'lesson_students.student_id = students.id')
        ->join('lessons', 'lessons.id = lesson_students.lesson_id')
        ->join('cities', 'cities.id = students.city_id', 'left')
        ->join('districts', 'districts.id = students.district_id', 'left')
        ->where('lessons.lesson_date', $today)
        ->whereIn('students.servis', ['var', 'arasira'])
        ->where('students.deleted_at', null)
        ->orderBy('lessons.start_time', 'ASC')
        ->orderBy('students.adi', 'ASC')
        ->findAll();
    
    // SAATE GÖRE GRUPLA
    $groupedByTime = [];
    foreach ($todayStudents as $student) {
        $time = $student['start_time'];
        if (!isset($groupedByTime[$time])) {
            $groupedByTime[$time] = [];
        }
        $groupedByTime[$time][] = $student;
    }
    
    // İstatistikler
    $stats = [
        'total_students' => count($todayStudents),
        'total_groups' => count($groupedByTime),
        'with_service' => 0,
        'civar' => 0,
        'yakin' => 0,
        'uzak' => 0,
    ];
    
    foreach ($todayStudents as $student) {
        if ($student['servis'] === 'var') $stats['with_service']++;
        if ($student['mesafe'] === 'civar') $stats['civar']++;
        elseif ($student['mesafe'] === 'yakın') $stats['yakin']++;
        elseif ($student['mesafe'] === 'uzak') $stats['uzak']++;
    }
    
    $this->data['stats'] = $stats;
    $this->data['groupedByTime'] = $groupedByTime;
    $this->data['title'] = 'Servis Paneli';
    
    return view('dashboard/servis', $this->data);
}

    /**
     * Veli Dashboard'ını gösterir.
     */
public function parent()
{
    $userProfileModel = new UserProfileModel();
    $studentModel = new StudentModel();
    // GEREKLİ MODELLERİ BURAYA EKLİYORUZ
    $lessonModel = new \App\Models\LessonModel(); 
    $announcementModel = new \App\Models\AnnouncementModel();

    // 1. Giriş yapmış velinin profilini ve TC'sini al
    $parentProfile = $userProfileModel->where('user_id', auth()->id())->first();
    if (!$parentProfile || empty($parentProfile->tc_kimlik_no)) {
        return redirect()->to('/profile')->with('error', 'Lütfen öğrenci bilgilerinizi görebilmek için T.C. Kimlik Numaranızı profilinize ekleyin.');
    }

    // 2. Veliye ait tüm çocukları bul
    $children = $studentModel->getChildrenOfParent($parentProfile->tc_kimlik_no);

    if (empty($children)) {
        $this->data['title'] = 'Veli Paneli';
        $this->data['no_student_found'] = true;
        return view('dashboard/parent', $this->data);
    }

    // 3. Aktif öğrenciyi belirle (Bu kısım zaten doğru çalışıyor)
    $activeChildId = session('active_child_id');
    $activeChild = null;

    if ($activeChildId) {
        foreach ($children as $child) {
            if ($child['id'] == $activeChildId) {
                $activeChild = $child;
                break;
            }
        }
    }

    if ($activeChild === null) {
        $activeChild = $children[0];
        session()->set('active_child_id', $activeChild['id']);
    }

    // --- YENİ EKLENEN WIDGET VERİLERİ ---
    $today = date('Y-m-d');

    // 4. Aktif çocuğun bugünkü derslerini çek
    $gununDersleri = $lessonModel->getLessonsForStudentByDate($activeChild['id'], $today);

    // 5. Velilere yönelik duyuruları çek
    $duyurular = $announcementModel->getLatestAnnouncementsForGroups(['all', 'veli'], 5);

    // 6. Aktif çocuğun öğretmenlerini çek
    $ogretmenler = $studentModel->getTeachersForStudent($activeChild['id']);
    // --- BİTİŞ ---


    // 7. Tüm verileri View'e gönder
    $this->data['title'] = 'Veli Paneli - ' . esc($activeChild['adi']);
    $this->data['parent_children'] = $children;
    $this->data['active_child'] = $activeChild;
    $this->data['gununDersleri'] = $gununDersleri; // Yeni veri
    $this->data['duyurular'] = $duyurular;         // Yeni veri
    $this->data['ogretmenler'] = $ogretmenler;       // Yeni veri

    return view('dashboard/parent', $this->data);
}

    /**
     * Veli panelinde gösterilecek aktif çocuğu session'a kaydeder.
     */
    public function setActiveChild()
    {
        $childId = $this->request->getPost('child_id');
        if ($childId) {
            // Güvenlik için bu çocuğun gerçekten bu veliye ait olup olmadığı kontrol edilebilir.
            // Şimdilik basitçe session'a set ediyoruz.
            session()->set('active_child_id', $childId);
        }
        return redirect()->to('/dashboard/parent');
    }
}