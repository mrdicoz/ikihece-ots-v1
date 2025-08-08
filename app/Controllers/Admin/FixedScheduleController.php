<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AssignmentModel;
use App\Models\FixedLessonModel;
use App\Models\StudentModel;
use CodeIgniter\Shield\Models\UserModel;

class FixedScheduleController extends BaseController
{
    protected $fixedLessonModel;

    public function __construct()
    {
        // Modeli constructor'da yüklemek en iyi pratiktir.
        $this->fixedLessonModel = model(FixedLessonModel::class);
    }

    public function index()
    {
        $userModel = model(UserModel::class);
        $loggedInUser = auth()->user();

        $teacherQuery = $userModel
            ->select('users.id, user_profiles.first_name, user_profiles.last_name, user_profiles.profile_photo') // Profil fotoğrafını da alıyoruz
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('auth_groups_users.group', 'ogretmen')
            ->where('users.active', 1);

        if ($loggedInUser->inGroup('sekreter') && !$loggedInUser->inGroup('admin', 'yonetici', 'mudur')) {
            $assignmentModel = model(AssignmentModel::class);
            $assignedTeacherIds = $assignmentModel->getAssignedTeacherIds($loggedInUser->id);
            if (!empty($assignedTeacherIds)) {
                $teacherQuery->whereIn('users.id', $assignedTeacherIds);
            } else {
                $teacherQuery->where('users.id', 0); // Hiç öğretmen atanmamışsa sonuç boş gelsin
            }
        }
        
        $teachers = $teacherQuery->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
        
        $data = [
            'title'    => 'Sabit Ders Programı Yönetimi',
            'teachers' => $teachers,
        ];

        return view('admin/fixed_schedule/index', array_merge($this->data, $data));
    }
    
      /**
     * AJAX isteği ile modal içeriğini (mevcut dersler ve BOŞ form) yükler.
     */
        public function getDayDetails($teacherId, $dayOfWeek)
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }

        $studentModel = new StudentModel(); // StudentModel'i çağırıyoruz
        
        $data = [
            'teacher_id'    => $teacherId,
            'day_of_week'   => $dayOfWeek,
            'day_name'      => ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][$dayOfWeek - 1],
            'fixed_lessons' => $this->fixedLessonModel
                                ->select('fixed_lessons.*, students.adi, students.soyadi')
                                ->join('students', 'students.id = fixed_lessons.student_id')
                                ->where('teacher_id', $teacherId)
                                ->where('day_of_week', $dayOfWeek)
                                ->orderBy('start_time', 'ASC')
                                ->findAll(),
            // --- DÜZELTME BURADA: Tüm öğrenci listesini tekrar yolluyoruz ---
            'students'      => $studentModel->select('id, adi, soyadi')->orderBy('adi', 'ASC')->findAll()
        ];

        return view('admin/fixed_schedule/_modal_content', $data);
    }

    public function saveLesson()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        
        $data = $this->request->getPost();

        // Validation
        $validation = \Config\Services::validation();
        $validation->setRules([
            'teacher_id'  => 'required|is_natural_no_zero',
            'day_of_week' => 'required|is_natural_no_zero',
            'student_id'  => 'required|is_natural_no_zero',
            'start_time'  => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
        }
        
         // --- GÜNCELLENEN ÇAKIŞMA KONTROLÜ ---
    $existing = $this->fixedLessonModel->where([
        'teacher_id'  => $data['teacher_id'],
        'day_of_week' => $data['day_of_week'],
        'start_time'  => $data['start_time'],
        'student_id'  => $data['student_id'] // ÖNEMLİ: Artık öğrenciyi de kontrol ediyoruz.
    ])->first();

    if ($existing) {
        return $this->response->setJSON(['success' => false, 'message' => 'Bu öğrenci için belirtilen gün ve saatte zaten bir sabit ders mevcut.']);
    }
    // --- GÜNCELLEME SONU ---

        $data['end_time'] = date('H:i:s', strtotime($data['start_time'] . ' +1 hour'));

        if ($this->fixedLessonModel->insert($data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Sabit ders eklendi.']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu.']);
    }
    
    public function deleteLesson()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        
        $id = $this->request->getPost('id');
        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Geçersiz ID.']);
        }

        // Burada ek bir yetki kontrolü (bu dersin sekretere ait olup olmadığı) yapılabilir.
        // Şimdilik basit tutuyoruz.

        if ($this->fixedLessonModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Sabit ders silindi.']);
        }
        
        return $this->response->setJSON(['success' => false, 'message' => 'Silme işlemi sırasında bir hata oluştu.']);
    }

    /**
     * AJAX isteği ile belirli bir öğretmen ve gün için
     * kaydedilmiş sabit derslerin listesini döndürür.
     */
    public function getCellContent($teacherId, $dayOfWeek)
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }

        $fixedLessons = $this->fixedLessonModel
            ->select('students.adi, students.soyadi')
            ->join('students', 'students.id = fixed_lessons.student_id')
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->findAll();
        
        // Bu view dosyasını bir sonraki adımda oluşturacağız.
        return view('admin/fixed_schedule/_cell_content', ['lessons' => $fixedLessons]);
    }

    /**
     * YENİ METOT: Tom-Select için AJAX ile öğrenci arar.
     */
    public function searchStudents()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $term = $this->request->getGet('q');
        if (empty($term)) {
            return $this->response->setJSON([]);
        }

        $studentModel = new StudentModel();

        $students = $studentModel->select('id, adi, soyadi')
                                 ->like('adi', $term, 'after')
                                 ->orLike('soyadi', $term, 'after')
                                 ->findAll(20);

        $formattedStudents = array_map(
            fn($s) => ['value' => $s['id'], 'text' => $s['adi'] . ' ' . $s['soyadi']], 
            $students
        );

        return $this->response->setJSON($formattedStudents);
    }
    // ... saveLesson() ve deleteLesson() metotları aynı kalıyor ...
}