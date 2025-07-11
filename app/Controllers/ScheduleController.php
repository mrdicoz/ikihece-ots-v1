<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LessonModel;
use App\Models\LessonStudentModel;
use App\Models\StudentModel;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\I18n\Time; // <-- EKLENECEK SATIR


class ScheduleController extends BaseController
{
    /**
     * Ders programı takviminin ana görünümünü yükler.
     */
    public function index()
    {
        $data = [
            'title' => 'Ders Programı Yönetimi',
        ];
        return view('schedule/index', $data);
    }

    /**
     * Belirli bir aydaki dersleri JSON formatında döndürür.
     */
    public function getLessonsForMonth()
    {
        $lessonModel = new LessonModel();
        $start = $this->request->getVar('start');
        $end   = $this->request->getVar('end');

        $db_lessons = $lessonModel
            ->select('lessons.id, lessons.lesson_date, lessons.start_time, lessons.end_time, teacher.username as teacher_name, student.adi as student_first_name, student.soyadi as student_last_name')
            ->join('users as teacher', 'teacher.id = lessons.teacher_id')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id', 'left')
            ->join('students as student', 'student.id = lesson_students.student_id', 'left')
            ->where('lessons.lesson_date >=', date('Y-m-d', strtotime($start)))
            ->where('lessons.lesson_date <=', date('Y-m-d', strtotime($end)))
            ->findAll();

        $grouped_lessons = [];
        foreach ($db_lessons as $lesson) {
            if (!isset($grouped_lessons[$lesson['id']])) {
                $grouped_lessons[$lesson['id']] = [
                    'id' => $lesson['id'],
                    'start' => $lesson['lesson_date'] . 'T' . $lesson['start_time'],
                    'end' => $lesson['lesson_date'] . 'T' . $lesson['end_time'],
                    'teacher_name' => $lesson['teacher_name'],
                    'students' => []
                ];
            }
            if ($lesson['student_first_name']) {
                $grouped_lessons[$lesson['id']]['students'][] = $lesson['student_first_name'] . ' ' . $lesson['student_last_name'];
            }
        }
        
        $events = [];
        foreach($grouped_lessons as $id => $lesson) {
            $student_count = count($lesson['students']);
            $title = "Öğrt: " . $lesson['teacher_name'];

            if ($student_count === 1) {
                $title .= " - Öğr: " . $lesson['students'][0];
            } elseif ($student_count > 1) {
                $title .= " - Grup Dersi (" . $student_count . " Öğrenci)";
            } else {
                $title .= " - (Öğrenci Atanmamış)";
            }

            $events[] = [
                'id'    => $id,
                'title' => $title,
                'start' => $lesson['start'],
                'end'   => $lesson['end'],
            ];
        }

        return $this->response->setJSON($events);
    }

    /**
     * Günlük program grid'ini gösteren sayfayı yükler.
     */
    public function dailyGrid($date = null)
    {
        if (is_null($date) || !strtotime($date)) {
            return redirect()->to(route_to('schedule.index'))->with('error', 'Geçersiz tarih.');
        }

        $userModel = new UserModel();
        $teachers = $userModel
            ->select('users.id, user_profiles.first_name, user_profiles.last_name, user_profiles.profile_photo')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('auth_groups_users.group', 'ogretmen')
            ->orderBy('user_profiles.first_name', 'ASC')
            ->asObject()
            ->findAll();

        $lessonModel = new LessonModel();
        $lessons = $lessonModel
            ->select('lessons.*, GROUP_CONCAT(CONCAT(s.adi, " ", s.soyadi) SEPARATOR ", ") as student_names')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id', 'left')
            ->join('students s', 's.id = ls.student_id', 'left')
            ->where('lesson_date', $date)
            ->groupBy('lessons.id')
            ->findAll();

        $lessonMap = [];
        foreach($lessons as $lesson) {
            $hourKey = date('H', strtotime($lesson['start_time']));
            $lessonMap[$lesson['teacher_id']][$hourKey] = $lesson;
        }

        $data = [
            'title'       => Time::parse($date)->toLocalizedString('d MMMM yyyy') . ' Ders Programı',
            'displayDate' => $date,
            'teachers'    => $teachers,
            'lessonMap'   => $lessonMap,
        ];
        
        return view('schedule/daily_grid', $data);
    }

    /**
     * Tom-Select için tüm öğrencileri formatlanmış bir şekilde döndürür.
     */
    public function getStudentsForSelect()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $studentModel = new StudentModel();
        $students = $studentModel->select('id, adi, soyadi')->findAll();

        $formattedStudents = [];
        foreach ($students as $student) {
            $formattedStudents[] = [
                'value' => $student['id'],
                'text'  => $student['adi'] . ' ' . $student['soyadi'],
            ];
        }

        return $this->response->setJSON($formattedStudents);
    }

    /**
     * AJAX ile gönderilen yeni dersi veritabanına kaydeder.
     */
        public function createLesson()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        
        // Veritabanı bağlantısını başlatıyoruz.
        $db = \Config\Database::connect();

        $data = [
            'teacher_id'  => $this->request->getPost('teacher_id'),
            'lesson_date' => $this->request->getPost('lesson_date'),
            'start_time'  => $this->request->getPost('start_time'),
            'end_time'    => $this->request->getPost('end_time'),
        ];
        
        $studentIds = $this->request->getPost('students');

        if(empty($studentIds)){
             return $this->response->setJSON(['success' => false, 'message' => 'Lütfen en az bir öğrenci seçin.']);
        }

        // Transaction'ı $db değişkeni üzerinden başlatıyoruz.
        $db->transStart();

        $lessonModel->insert($data);
        $lessonId = $lessonModel->getInsertID();

        if (!empty($studentIds) && $lessonId) {
            $studentData = [];
            foreach ($studentIds as $studentId) {
                $studentData[] = [
                    'lesson_id'  => $lessonId,
                    'student_id' => $studentId,
                ];
            }
            $lessonStudentModel->insertBatch($studentData);
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Ders kaydedilirken bir veritabanı hatası oluştu.']);
        } 
        
        $db->transCommit();
        
        \CodeIgniter\Events\Events::trigger('schedule.changed', $data, $data['teacher_id']);

        return $this->response->setJSON(['success' => true, 'message' => 'Ders başarıyla eklendi.']);
    }

    public function getLessonDetails($lessonId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $lessonModel = new LessonModel();
        
        // Dersi ve ilişkili öğrencileri çekiyoruz
        $lesson = $lessonModel
            ->select('lessons.*, GROUP_CONCAT(CONCAT(s.adi, " ", s.soyadi) SEPARATOR "<br>") as student_names')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id', 'left')
            ->join('students s', 's.id = ls.student_id', 'left')
            ->where('lessons.id', $lessonId)
            ->groupBy('lessons.id')
            ->first();

        if (!$lesson) {
            return $this->response->setJSON(['success' => false, 'message' => 'Ders bulunamadı.']);
        }

        return $this->response->setJSON(['success' => true, 'lesson' => $lesson]);
    }

    /**
     * ID'si verilen dersi ve ilişkili öğrenci kayıtlarını siler.
     */
    public function deleteLesson($lessonId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $lessonModel = new LessonModel();
        $lesson = $lessonModel->find($lessonId);

        if ($lesson) {
            // LessonModel, ilişkili lesson_students kayıtlarını
            // veritabanı foreign key (CASCADE) ayarı sayesinde otomatik siler.
            if ($lessonModel->delete($lessonId)) {
                // Event'i tetikle
                \CodeIgniter\Events\Events::trigger('schedule.changed', $lesson, $lesson['teacher_id']);
                return $this->response->setJSON(['success' => true, 'message' => 'Ders başarıyla silindi.']);
            }
        }
        
        return $this->response->setJSON(['success' => false, 'message' => 'Ders silinirken bir hata oluştu.']);
    }
}