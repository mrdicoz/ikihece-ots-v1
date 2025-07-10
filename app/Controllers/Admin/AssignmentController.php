<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AssignmentModel;
use CodeIgniter\Shield\Models\UserModel;

class AssignmentController extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();

        // Tüm sekreterleri ve öğretmenleri profil bilgileriyle birlikte çekelim.
        // 'group' sütununa göre filtreleme yapıyoruz.
        $secretaries = $userModel->select('users.id, up.first_name, up.last_name, users.username')
            ->join('user_profiles up', 'up.user_id = users.id', 'left')
            ->join('auth_groups_users agu', 'agu.user_id = users.id')
            ->where('agu.group', 'sekreter')
            ->orderBy('up.first_name', 'ASC')
            ->findAll();

        $teachers = $userModel->select('users.id, up.first_name, up.last_name')
            ->join('user_profiles up', 'up.user_id = users.id', 'left')
            ->join('auth_groups_users agu', 'agu.user_id = users.id')
            ->where('agu.group', 'ogretmen')
            ->orderBy('up.first_name', 'ASC')
            ->findAll();

        $data = [
            'title'       => 'Sekreter - Öğretmen Atama',
            'secretaries' => $secretaries,
            'teachers'    => $teachers,
        ];

        return view('admin/assignments/index', $data);
    }
    
    /**
     * AJAX ile çağrılacak ve bir sekreterin mevcut öğretmenlerini getirecek.
     */
    public function getAssigned($secretaryId)
    {
        $assignmentModel = new AssignmentModel();
        $assignedIds = $assignmentModel->getAssignedTeacherIds($secretaryId);
        return $this->response->setJSON($assignedIds);
    }
    
    /**
     * Formdan gelen veriyi işleyerek atamayı kaydedecek.
     */
    public function save()
    {
        $secretaryId = $this->request->getPost('secretary_id');
        $teacherIds  = $this->request->getPost('teacher_ids') ?? []; // Seçim boşsa boş dizi gelsin

        if (empty($secretaryId)) {
            return redirect()->back()->with('error', 'Lütfen bir sekreter seçin.');
        }

        $assignmentModel = new AssignmentModel();
        $assignmentModel->syncAssignments((int)$secretaryId, $teacherIds);

        return redirect()->to(route_to('admin.assignments.index'))
                         ->with('success', 'Atama işlemi başarıyla güncellendi.');
    }
}