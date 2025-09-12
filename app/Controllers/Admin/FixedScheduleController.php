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
    if (! $this->request->isAJAX()) {
        return $this->response->setStatusCode(403);
    }

    $startTime = str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00:00';
    
    // 1. İlgili saat dilimindeki dersleri (öğrencileri) al
    $lessonsInSlot = $this->fixedLessonModel
        ->select('students.id as student_id, students.adi, students.soyadi')
        ->join('students', 'students.id = fixed_lessons.student_id')
        ->where('teacher_id', $teacherId)
        ->where('day_of_week', $day)
        ->where('start_time', $startTime)
        ->findAll();

    $conflicts = [];
    if (!empty($lessonsInSlot)) {
        $studentIds = array_column($lessonsInSlot, 'student_id');
        $dayNames = ['', 'Pzt', 'Salı', 'Çrş', 'Perş', 'Cuma', 'Cmt', 'Paz'];

        // 2. Bu öğrencilerin *tüm* sabit derslerini, öğretmen bilgisiyle birlikte al
        $allLessonsForStudents = $this->fixedLessonModel
            ->select('fixed_lessons.student_id, fixed_lessons.day_of_week, fixed_lessons.start_time, up.first_name, up.last_name')
            ->join('users', 'users.id = fixed_lessons.teacher_id')
            ->join('user_profiles up', 'up.user_id = users.id', 'left')
            ->whereIn('fixed_lessons.student_id', $studentIds)
            ->findAll();
        
        // 3. GÜNCELLENMİŞ ÇAKIŞMA TESPİT MANTIĞI
        foreach ($studentIds as $studentId) {
            $studentLessons = array_filter($allLessonsForStudents, fn($l) => $l['student_id'] == $studentId);
            
            // Öğrencinin dersi olan günleri unique (tekil) olarak bul
            $daysWithLessons = array_unique(array_column($studentLessons, 'day_of_week'));

            // Eğer öğrencinin 1'den fazla farklı günde dersi varsa, bu bir çakışmadır.
            if (count($daysWithLessons) > 1) {
                $conflictMessages = [];
                foreach ($studentLessons as $lesson) {
                    // Popover içeriği için, bakılan mevcut slot dışındaki tüm derslerini listele
                    if ($lesson['day_of_week'] != $day || $lesson['start_time'] != $startTime) {
                        $conflictMessages[] = "<b>" . ($dayNames[$lesson['day_of_week']] ?? '') . " " . substr($lesson['start_time'], 0, 5) . "</b><br><small>" . esc($lesson['first_name'] . ' ' . $lesson['last_name']) . "</small>";
                    }
                }
                if (!empty($conflictMessages)) {
                    $conflicts[$studentId] = implode('<hr class="my-1">', $conflictMessages);
                }
            }
        }
    }
    
    // 4. Veriyi view'e gönder
    return view('admin/fixed_schedule/_slot_content', [
        'lessons'   => $lessonsInSlot,
        'conflicts' => $conflicts
    ]);
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