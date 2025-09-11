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
        $this->fixedLessonModel = model(FixedLessonModel::class);
    }

    public function index()
    {
        $userModel = model(UserModel::class);
        $studentModel = new \App\Models\StudentModel();
        $loggedInUser = auth()->user();

        // 1. Tüm öğrencileri veritabanından çek
        $allStudents = $studentModel->select('id, adi, soyadi')->orderBy('adi', 'ASC')->findAll();

        // 2. TomSelect'in anlayacağı formata çevir ('value' ve 'text' olarak)
        $studentsForSelect = array_map(
            fn($student) => ['value' => $student['id'], 'text' => $student['adi'] . ' ' . $student['soyadi']],
            $allStudents
        );

        $teacherQuery = $userModel
            ->select('users.id, user_profiles.first_name, user_profiles.last_name, user_profiles.profile_photo, user_profiles.branch')
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
                $teacherQuery->where('users.id', 0);
            }
        }
        
        $teachers = $teacherQuery->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
        
        $data = [
            'title'    => 'Sabit Ders Programı Yönetimi',
            'studentsForSelect' => $studentsForSelect, // YENİ EKLENEN VERİ
            'teachers' => $teachers,
        ];

        return view('admin/fixed_schedule/index', array_merge($this->data, $data));
    }
    
    public function getSlotContent($teacherId, $day, $hour)
    {
        if (! $this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $startTime = str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00:00';
        $lessons = $this->fixedLessonModel
            ->select('students.adi, students.soyadi')
            ->join('students', 'students.id = fixed_lessons.student_id')
            ->where('teacher_id', $teacherId)->where('day_of_week', $day)->where('start_time', $startTime)
            ->findAll();
        return view('admin/fixed_schedule/_slot_content', ['lessons' => $lessons]);
    }

    public function getHourDetails($teacherId, $day, $hour)
    {
        if (! $this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $startTime = str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00:00';
        $data = [
            'teacher_id'    => $teacherId,
            'day_of_week'   => $day,
            'hour'          => $hour,
            'fixed_lessons' => $this->fixedLessonModel
                ->select('fixed_lessons.id, students.adi, students.soyadi')
                ->join('students', 'students.id = fixed_lessons.student_id')
                ->where('teacher_id', $teacherId)->where('day_of_week', $day)->where('start_time', $startTime)
                ->findAll(),
        ];
        return view('admin/fixed_schedule/_modal_hour_content', $data);
    }
    
    public function getDayDetails($teacherId, $day)
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $data = [
            'teacher_id'    => $teacherId,
            'day_of_week'   => $day,
            'fixed_lessons' => $this->fixedLessonModel
                ->select('fixed_lessons.id, students.adi, students.soyadi, fixed_lessons.start_time')
                ->join('students', 'students.id = fixed_lessons.student_id')
                ->where('teacher_id', $teacherId)->where('day_of_week', $day)
                ->orderBy('start_time', 'ASC')->findAll(),
        ];
        return view('admin/fixed_schedule/_modal_day_content', $data);
    }

    public function saveLesson()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $data = $this->request->getPost();
        $validation = \Config\Services::validation();
        $validation->setRules(['teacher_id'  => 'required|is_natural_no_zero', 'day_of_week' => 'required|is_natural_no_zero', 'student_id'  => 'required|is_natural_no_zero', 'start_time'  => 'required']);
        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
        }
        $existing = $this->fixedLessonModel->where(['teacher_id' => $data['teacher_id'], 'day_of_week' => $data['day_of_week'], 'start_time' => $data['start_time'], 'student_id' => $data['student_id']])->first();
        if ($existing) { return $this->response->setJSON(['success' => false, 'message' => 'Bu öğrenci için belirtilen gün ve saatte zaten bir sabit ders mevcut.']); }
        $data['end_time'] = date('H:i:s', strtotime($data['start_time'] . ' +1 hour'));
        if ($this->fixedLessonModel->insert($data)) { return $this->response->setJSON(['success' => true, 'message' => 'Sabit ders eklendi.']); }
        return $this->response->setJSON(['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu.']);
    }
    
    public function deleteLesson()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $id = $this->request->getPost('id');
        if (empty($id)) { return $this->response->setJSON(['success' => false, 'message' => 'Geçersiz ID.']);}
        if ($this->fixedLessonModel->delete($id)) { return $this->response->setJSON(['success' => true, 'message' => 'Sabit ders silindi.']);}
        return $this->response->setJSON(['success' => false, 'message' => 'Silme işlemi sırasında bir hata oluştu.']);
    }

    /**
     * NİHAİ DÜZELTME ve HATA AYIKLAMA KODU
     */
    public function searchStudents()
    {
        // Güvenlik kontrolünü geçici olarak devre dışı bırakıyoruz.
        // if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }

        $term = $this->request->getGet('q');
        log_message('error', '>>> FixedSchedule::searchStudents tetiklendi. Gelen arama terimi: "' . $term . '"');

        if (empty($term) || strlen($term) < 2) {
            return $this->response->setJSON([]);
        }

        try {
            $studentModel = new StudentModel();

            // Sorguyu oluşturuyoruz
            $students = $studentModel->select('id, adi, soyadi')
                                     ->groupStart()
                                         ->like('adi', $term, 'after') // 'term%' şeklinde arama
                                         ->orLike('soyadi', $term, 'after') // 'term%' şeklinde arama
                                     ->groupEnd()
                                     ->findAll(20);

            log_message('error', '>>> SQL Sorgusu: ' . $studentModel->getLastQuery());
            log_message('error', '>>> Bulunan öğrenci sayısı: ' . count($students));
            
            if (empty($students)) {
                log_message('error', '>>> HİÇ ÖĞRENCİ BULUNAMADI.');
            }

            $formattedStudents = array_map(
                fn($s) => ['value' => $s['id'], 'text' => $s['adi'] . ' ' . $s['soyadi']], 
                $students
            );

            return $this->response->setJSON($formattedStudents);

        } catch (\Throwable $e) {
            log_message('critical', '>>> searchStudents metodunda KRİTİK HATA: ' . $e->getMessage() . ' Dosya: ' . $e->getFile() . ' Satır: ' . $e->getLine());
            return $this->response->setJSON(['error' => 'Sunucu hatası. Log dosyasını kontrol edin.'])->setStatusCode(500);
        }
    }
}