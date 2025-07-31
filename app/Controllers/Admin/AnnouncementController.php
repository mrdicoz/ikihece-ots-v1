<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use CodeIgniter\Events\Events;

class AnnouncementController extends BaseController
{
    /**
     * Tüm duyuruları listeler.
     */
    public function index()
    {
        $model = new AnnouncementModel();
        
        $data = [
            'title'         => 'Duyuru Yönetimi',
            'announcements' => $model->select('announcements.*, users.username as author_name')
                                     ->join('users', 'users.id = announcements.author_id', 'left')
                                     ->orderBy('created_at', 'DESC')
                                     ->paginate(15), // Sayfalama için
            'pager'         => $model->pager,
        ];

        // Bu view dosyasını bir sonraki adımda oluşturacağız.
        return view('admin/announcements/index', $data);
    }

    /**
     * Yeni duyuru ekleme formunu gösterir.
     */
    public function new()
    {
        $data = [
            'title' => 'Yeni Duyuru Oluştur',
        ];

        // Bu view dosyasını bir sonraki adımda oluşturacağız.
        return view('admin/announcements/new', $data);
    }

    /**
     * Formdan gelen yeni duyuruyu veritabanına kaydeder.
     */
    public function create()
    {
        $model = new AnnouncementModel();
        
        $data = [
            'title'        => $this->request->getPost('title'),
            'body'         => $this->request->getPost('body'),
            'target_group' => $this->request->getPost('target_group'),
            'status'       => $this->request->getPost('status'),
            'author_id'    => auth()->id(), // İşlemi yapan yöneticinin ID'si
        ];

        if ($model->insert($data)) {
            // Eğer duyuru "Yayınlandı" olarak kaydedildiyse bildirim olayını tetikle
            if ($data['status'] === 'published') {
                $announcementId = $model->getInsertID();
                $announcement = $model->find($announcementId);
                Events::trigger('announcement.published', $announcement);
            }
            return redirect()->to(route_to('admin.announcements.index'))->with('success', 'Duyuru başarıyla oluşturuldu.');
        }

        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    /**
     * Var olan bir duyuruyu düzenleme formunu gösterir.
     */
    public function edit($id = null)
    {
        $model = new AnnouncementModel();
        $announcement = $model->find($id);

        if (!$announcement) {
            return redirect()->to(route_to('admin.announcements.index'))->with('error', 'Duyuru bulunamadı.');
        }

        $data = [
            'title'        => 'Duyuruyu Düzenle',
            'announcement' => $announcement,
        ];

        // Bu view dosyasını bir sonraki adımda oluşturacağız.
        return view('admin/announcements/edit', $data);
    }

    /**
     * Düzenleme formundan gelen verilerle duyuruyu günceller.
     */
    public function update($id = null)
    {
        $model = new AnnouncementModel();
        $announcement = $model->find($id);

        if (!$announcement) {
            return redirect()->to(route_to('admin.announcements.index'))->with('error', 'Duyuru bulunamadı.');
        }

        // Güncelleme öncesi eski durumu saklayalım
        $oldStatus = $announcement['status'];

        $data = [
            'title'        => $this->request->getPost('title'),
            'body'         => $this->request->getPost('body'),
            'target_group' => $this->request->getPost('target_group'),
            'status'       => $this->request->getPost('status'),
        ];
        
        if ($model->update($id, $data)) {
            // Eğer duyuru 'taslak' durumundan 'yayınlandı' durumuna geçtiyse bildirim olayını tetikle
            if ($oldStatus === 'draft' && $data['status'] === 'published') {
                 Events::trigger('announcement.published', $model->find($id));
            }
            return redirect()->to(route_to('admin.announcements.index'))->with('success', 'Duyuru başarıyla güncellendi.');
        }

        return redirect()->back()->withInput()->with('errors', $model->errors());
    }

    /**
     * Belirtilen duyuruyu veritabanından siler.
     */
    public function delete($id = null)
    {
        $model = new AnnouncementModel();
        if ($model->delete($id)) {
            return redirect()->to(route_to('admin.announcements.index'))->with('success', 'Duyuru başarıyla silindi.');
        }

        return redirect()->back()->with('error', 'Duyuru silinirken bir hata oluştu.');
    }
}