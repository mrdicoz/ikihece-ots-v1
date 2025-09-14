<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentEvaluationModel;
use App\Models\UserProfileModel;

class EvaluationController extends BaseController
{
    protected $evaluationModel;

    public function __construct()
    {
        $this->evaluationModel = new StudentEvaluationModel();
    }

    public function create()
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        $user = auth()->user();
        if (! $user->inGroup('admin', 'yonetici', 'mudur', 'sekreter', 'ogretmen')) {
             return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Yetkisiz erişim.']);
        }

        $studentId = $this->request->getPost('student_id');
        $evaluationText = $this->request->getPost('evaluation');

        if (empty($studentId) || empty($evaluationText)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Değerlendirme metni boş olamaz.']);
        }
        
        $profileModel = new UserProfileModel();
        $teacherProfile = $profileModel->where('user_id', $user->id)->first();
        $teacherFullName = trim(($teacherProfile->first_name ?? '') . ' ' . ($teacherProfile->last_name ?? $user->username));

        $data = [
            'student_id'            => $studentId,
            'teacher_id'            => $user->id,
            'teacher_snapshot_name' => $teacherFullName,
            'evaluation'            => $evaluationText,
        ];

        if ($this->evaluationModel->insert($data)) {
            session()->setFlashdata('success', 'Değerlendirme başarıyla eklendi.');
            return $this->response->setJSON(['success' => true, 'message' => 'Değerlendirme başarıyla kaydedildi.']);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu.']);
    }

    /**
     * YENİ: Bir değerlendirmeyi getirir (düzenleme için)
     */
    public function get($id = null)
    {
        if (! $this->request->is('get')) {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'ID gerekli.']);
        }
        
        $evaluation = $this->evaluationModel->find($id);
        if (!$evaluation) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Değerlendirme bulunamadı.']);
        }

        $user = auth()->user();
        
        // Yetki Kontrolü: Sadece yazan kişi veya yetkili kişiler görüntüleyebilir
        $canView = false;
        
        // Eğer admin, yönetici, müdür ise tüm değerlendirmeleri görebilir
        if ($user->inGroup('admin', 'yonetici', 'mudur')) {
            $canView = true;
        }
        // Eğer değerlendirmeyi yazan kişi ise görüntüleyebilir
        elseif ($evaluation['teacher_id'] == $user->id) {
            $canView = true;
        }
        // Eğer sekreter ise görüntüleyebilir
        elseif ($user->inGroup('sekreter')) {
            $canView = true;
        }

        if (!$canView) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Bu işlemi yapma yetkiniz yok.']);
        }
        
        return $this->response->setJSON([
            'success' => true, 
            'data' => [
                'id' => $evaluation['id'],
                'evaluation' => $evaluation['evaluation'],
                'teacher_snapshot_name' => $evaluation['teacher_snapshot_name']
            ]
        ]);
    }

    /**
     * YENİ: Bir değerlendirmeyi günceller
     */
    public function update($id = null)
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'ID gerekli.']);
        }
        
        $evaluation = $this->evaluationModel->find($id);
        if (!$evaluation) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Değerlendirme bulunamadı.']);
        }

        $user = auth()->user();
        
        // Yetki Kontrolü: Sadece yazan kişi veya admin/yönetici düzenleyebilir
        $canEdit = false;
        
        // Eğer admin, yönetici, müdür ise tüm değerlendirmeleri düzenleyebilir
        if ($user->inGroup('admin', 'yonetici', 'mudur')) {
            $canEdit = true;
        }
        // Eğer değerlendirmeyi yazan kişi ise düzenleyebilir
        elseif ($evaluation['teacher_id'] == $user->id) {
            $canEdit = true;
        }

        if (!$canEdit) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Bu işlemi yapma yetkiniz yok.']);
        }

        $evaluationText = $this->request->getPost('evaluation');
        
        if (empty($evaluationText)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Değerlendirme metni boş olamaz.']);
        }

        $updateData = [
            'evaluation' => $evaluationText,
            'updated_at' => date('Y-m-d H:i:s')  // Güncelleme tarihini kaydet
        ];
        
        if ($this->evaluationModel->update($id, $updateData)) {
            session()->setFlashdata('success', 'Değerlendirme başarıyla güncellendi.');
            return $this->response->setJSON(['success' => true, 'message' => 'Değerlendirme başarıyla güncellendi.']);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Güncelleme sırasında bir hata oluştu.']);
    }

    /**
     * Bir değerlendirmeyi siler.
     */
    public function delete($id = null)
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'ID gerekli.']);
        }
        
        $evaluation = $this->evaluationModel->find($id);
        if (!$evaluation) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Değerlendirme bulunamadı.']);
        }

        $user = auth()->user();
        
        // Yetki Kontrolü: Sadece yazan kişi veya admin/yönetici silebilir
        $canDelete = false;
        
        // Eğer admin, yönetici, müdür ise tüm değerlendirmeleri silebilir
        if ($user->inGroup('admin', 'yonetici', 'mudur')) {
            $canDelete = true;
        }
        // Eğer değerlendirmeyi yazan kişi ise silebilir
        elseif ($evaluation['teacher_id'] == $user->id) {
            $canDelete = true;
        }

        if (!$canDelete) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Bu işlemi yapma yetkiniz yok.']);
        }
        
        if ($this->evaluationModel->delete($id)) {
            session()->setFlashdata('success', 'Değerlendirme başarıyla silindi.');
            return $this->response->setJSON(['success' => true, 'message' => 'Değerlendirme başarıyla silindi.']);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Silme işlemi sırasında bir hata oluştu.']);
    }
}