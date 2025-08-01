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
        $user = auth()->user();

        if ($user->inGroup('admin', 'yonetici', 'mudur', 'sekreter')) {
            return redirect()->to(route_to('dashboard.default'));
        }
        
        if ($user->inGroup('ogretmen')) {
            return redirect()->to(route_to('dashboard.teacher'));
        }

        if ($user->inGroup('veli')) {
            return redirect()->to(route_to('dashboard.parent'));
        }

        return redirect()->to(route_to('dashboard.default'));
    }

    /**
     * Yönetici, Müdür, Sekreter gibi roller için varsayılan dashboard'u gösterir.
     * Bu metot, yonetici.php view'i için gerekli tüm verileri hazırlar.
     */
    public function default()
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

        return view('dashboard/yonetici', $this->data);
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