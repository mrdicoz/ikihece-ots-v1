<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;

class AnnouncementController extends BaseController
{
    /**
     * Kullanıcının grubuna göre uygun duyuruları listeler.
     */
    public function index()
    {
        $model = new AnnouncementModel();
        $user = auth()->user();

        // Kullanıcının gruplarını al
        $userGroups = $user->getGroups();

        // Veritabanı sorgu yapıcısını başlat
        $builder = $model->select('announcements.*, users.username as author_name')
                        ->join('users', 'users.id = announcements.author_id', 'left')
                        ->where('announcements.status', 'published'); // SORUN DÜZELTİLDİ


        // Kullanıcının grubuna göre filtrele
        // Örneğin, kullanıcı 'ogretmen' grubundaysa, hem 'ogretmen' hem de 'all' (tümü)
        // olarak hedeflenmiş duyuruları görmeli.
        $builder->groupStart()
                ->where('target_group', 'all')
                ->orWhereIn('target_group', $userGroups)
                ->groupEnd();

        $data = [
            'title'         => 'Duyurular',
            'announcements' => $builder->orderBy('created_at', 'DESC')->paginate(10),
            'pager'         => $model->pager,
        ];

        // Bu view dosyasını bir sonraki adımda oluşturacağız
        return view('announcements/index', $data);
    }
}