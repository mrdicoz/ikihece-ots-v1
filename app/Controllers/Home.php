<?php

namespace App\Controllers;

use App\Models\StudentModel;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\AnnouncementModel;

class Home extends BaseController
{
    public function index(): string
    {
        
        
        // Modelleri yüklüyoruz
        $studentModel = new StudentModel();
        $userModel = new UserModel();

        // Toplam sayıları hesaplıyoruz
        // Not: countAllResults() soft delete olanları saymaz, bu bizim için doğru.
        $totalStudents = $studentModel->countAllResults();

        // Gruplara göre kullanıcı sayılarını alıyoruz.
        // Bu sorgu, belirli bir gruba ait aktif kullanıcıları sayar.
        $totalTeachers = $userModel->whereIn('id', function($builder) {
            return $builder->select('user_id')->from('auth_groups_users')->where('group', 'ogretmen');
        })->countAllResults();

        $totalParents = $userModel->whereIn('id', function($builder) {
            return $builder->select('user_id')->from('auth_groups_users')->where('group', 'veli');
        })->countAllResults();
        
        $totalServices = $userModel->whereIn('id', function($builder) {
            return $builder->select('user_id')->from('auth_groups_users')->where('group', 'servis');
        })->countAllResults();

        // Duyuru modelini başlat
        $announcementModel = new AnnouncementModel();

        // Son 5 duyuruyu al
        $this->data['latestAnnouncements'] = $announcementModel
            ->orderBy('created_at', 'DESC')
            ->findAll(5);

        // $this->data dizisine bu sayfaya özel verileri ekliyoruz.
        $this->data['title'] = "Anasayfa";
        $this->data['stats'] = [
            'students' => $totalStudents,
            'teachers' => $totalTeachers,
            'parents'  => $totalParents,
            'services' => $totalServices,
        ];
        
        // Sağ taraftaki tablo için öğrenci listesini alıyoruz
        $this->data['students'] = $studentModel->orderBy('adi', 'ASC')->findAll();


        // $this->data dizisini view'a gönderiyoruz.
        return view('dashboard', $this->data);
    }

    // Home.php içine ekleyin
public function maintenance()
{
    return view('maintenance');
}
}