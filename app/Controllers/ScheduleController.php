<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AssignmentModel;
use App\Models\FixedLessonModel;
use App\Models\LessonHistoryModel;
use App\Models\LessonModel;
use App\Models\LessonStudentModel;
use App\Models\StudentModel;
use App\Models\UserProfileModel;
use App\Models\DegerlendirmeModel;
use App\Models\StudentAbsenceModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Models\UserModel;

class ScheduleController extends BaseController
{
    /**
     * Ana takvim sayfasını yükler.
     */
    public function index()
    {
        $data = [
            'title' => 'Ders Programı Yönetimi',
        ];
        return view('schedule/index', array_merge($this->data, $data));
        

    }

    /**
     * FullCalendar için aylık dersleri JSON olarak döndürür.
     */
    // Sadece getLessonsForMonth metodunu güncelliyoruz

public function getLessonsForMonth()
    {
        $lessonModel = new LessonModel();
        $start = $this->request->getVar('start');
        $end   = $this->request->getVar('end');

        $userModel = new UserModel();
        $loggedInUser = auth()->user();

        $teacherQuery = $userModel->select('users.id')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->where('auth_groups_users.group', 'ogretmen')
            ->where('users.active', 1);

        if ($loggedInUser->inGroup('sekreter') && !$loggedInUser->inGroup('admin', 'mudur')) {
            $assignmentModel = new AssignmentModel();
            $assignedTeacherIds = $assignmentModel->where('manager_user_id', $loggedInUser->id)->findColumn('managed_user_id');
            if (empty($assignedTeacherIds)) {
                return $this->response->setJSON([]);
            }
            $teacherQuery->whereIn('users.id', $assignedTeacherIds);
        }

        $teachers = $teacherQuery->asArray()->findAll();
        $activeTeacherIds = array_column($teachers, 'id');

        if (empty($activeTeacherIds)) {
            return $this->response->setJSON([]);
        }

        $db_lessons = $lessonModel
            ->select("lesson_date, COUNT(id) as lesson_count")
            ->whereIn('teacher_id', $activeTeacherIds)
            ->where('lesson_date >=', date('Y-m-d', strtotime($start)))
            ->where('lesson_date <=', date('Y-m-d', strtotime($end)))
            ->groupBy("lesson_date")
            ->findAll();

        $events = [];
        foreach ($db_lessons as $day) {
            $events[] = [
                'title' => $day['lesson_count'], 
                'start' => $day['lesson_date'],
                'url'   => route_to('schedule.daily', $day['lesson_date']),
                'display' => 'list-item', 
            ];
        }
        return $this->response->setJSON($events);
    }
// app/Controllers/ScheduleController.php

public function dailyGrid($date = null)
    {
        if (is_null($date) || !strtotime($date)) {
            return redirect()->to(route_to('schedule.index'))->with('error', 'Geçersiz tarih.');
        }

        $userModel = new UserModel();
        $loggedInUser = auth()->user();
        
        $teacherQuery = $userModel
            ->select('users.id, user_profiles.first_name, user_profiles.last_name, user_profiles.profile_photo, user_profiles.branch')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('auth_groups_users.group', 'ogretmen')
            ->where('users.active', 1);

        if ($loggedInUser->inGroup('sekreter') && !$loggedInUser->inGroup('admin', 'mudur')) {
            $assignmentModel = new AssignmentModel();
            $assignedTeacherIds = $assignmentModel->where('manager_user_id', $loggedInUser->id)->findColumn('managed_user_id');
            if (empty($assignedTeacherIds)) { $teachers = []; } 
            else {
                $teacherQuery->whereIn('users.id', $assignedTeacherIds);
                $teachers = $teacherQuery->orderBy('user_profiles.display_order', 'ASC')->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
            }
        } else {
            $teachers = $teacherQuery->orderBy('user_profiles.display_order', 'ASC')->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
        }

        $teacherIds = array_map(fn($t) => $t->id, $teachers);

        $lessonModel = new LessonModel();
        $lessons = [];
        if (!empty($teacherIds)) {
            $lessons = $lessonModel
                ->select('lessons.*, GROUP_CONCAT(s.id) as student_ids, GROUP_CONCAT(CONCAT(s.adi, " ", s.soyadi) SEPARATOR "||") as student_names')
                ->join('lesson_students ls', 'ls.lesson_id = lessons.id', 'left')
                ->join('students s', 's.id = ls.student_id', 'left')
                ->where('lesson_date', $date)
                ->whereIn('lessons.teacher_id', $teacherIds)
                ->groupBy('lessons.id')
                ->findAll();
        }
            
        $allStudentIdsOnPage = [];
        foreach ($lessons as $lesson) {
            if (!empty($lesson['student_ids'])) {
                $allStudentIdsOnPage = array_merge($allStudentIdsOnPage, explode(',', $lesson['student_ids']));
            }
        }
        $allStudentIdsOnPage = array_unique(array_filter($allStudentIdsOnPage));

        // Çakışma kontrolü: Aynı öğrenci aynı saatte birden fazla öğretmende mi?
        $conflictMap = [];
        if (!empty($allStudentIdsOnPage) && !empty($teacherIds)) {
            $lessonStudentModel = new LessonStudentModel();
            $conflictData = $lessonStudentModel
                ->select('lesson_students.student_id, lessons.start_time, COUNT(DISTINCT lessons.teacher_id) as teacher_count')
                ->join('lessons', 'lessons.id = lesson_students.lesson_id')
                ->where('lessons.lesson_date', $date)
                ->whereIn('lessons.teacher_id', $teacherIds)
                ->whereIn('lesson_students.student_id', $allStudentIdsOnPage)
                ->groupBy('lesson_students.student_id, lessons.start_time')
                ->findAll();
            
            foreach ($conflictData as $conflict) {
                $conflictMap[$conflict['student_id']][$conflict['start_time']] = ($conflict['teacher_count'] > 1);
            }
        }

        $studentInfoMap = [];
        if (!empty($allStudentIdsOnPage)) {
            $fixedLessonModel = new FixedLessonModel();
            $studentModel = new StudentModel();
            $dayNames = ['', 'Pzt', 'Salı', 'Çrş', 'Perş', 'Cuma', 'Cmt', 'Paz'];

            $studentDetails = $studentModel->withDeleted()
                ->select('students.id, students.adi, students.soyadi, students.profile_image, c.name as city_name, d.name as district_name, students.telafi_bireysel_hak, students.telafi_grup_hak, students.servis, students.mesafe, students.egitim_programi, students.ram_bitis, students.hastane_raporu_bitis_tarihi')
                ->join('cities c', 'c.id = students.city_id', 'left')
                ->join('districts d', 'd.id = students.district_id', 'left')
                ->whereIn('students.id', $allStudentIdsOnPage)
                ->findAll();
            $studentDetailsMap = array_column($studentDetails, null, 'id');

            // Frequency fetch
            $historyModel = new LessonHistoryModel();
            $mysqlDayOfWeek = (date('N', strtotime($date)) % 7) + 1;
            $counts = $historyModel->builder()->select('student_name, COUNT(id) as freq')
                    ->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek)
                    ->groupBy('student_name')
                    ->get()
                    ->getResultArray();
            $freqMap = [];
            foreach($counts as $c) {
                $freqMap[$c['student_name']] = (int)$c['freq'];
            }

            $allFixedLessons = $fixedLessonModel
                ->select('fixed_lessons.student_id, fixed_lessons.day_of_week, fixed_lessons.start_time, up.first_name, up.last_name')
                ->join('users u', 'u.id = fixed_lessons.teacher_id')
                ->join('user_profiles up', 'up.user_id = u.id', 'left')
                ->whereIn('student_id', $allStudentIdsOnPage)
                ->findAll();

            $studentFixedLessons = [];
            foreach ($allFixedLessons as $fl) {
                $studentFixedLessons[$fl['student_id']][] = $fl;
            }
            
            foreach($allStudentIdsOnPage as $studentId) {
                $studentInfo = $studentDetailsMap[$studentId] ?? [];
                $fixedInfo = $studentFixedLessons[$studentId] ?? [];

                $messageParts = [];
                if (!empty($fixedInfo)) {
                    foreach($fixedInfo as $lessonInfo) {
                        $dayName = $dayNames[$lessonInfo['day_of_week']] ?? '?';
                        $time = substr($lessonInfo['start_time'], 0, 5);
                        $teacherName = esc(trim($lessonInfo['first_name'] . ' ' . $lessonInfo['last_name']));
                        $messageParts[] = "<b>{$dayName} {$time}</b> - {$teacherName}";
                    }
                }

                $fullName = trim(($studentInfo['adi'] ?? '') . ' ' . ($studentInfo['soyadi'] ?? ''));
                
                // Format programs
                $programs = [];
                if (!empty($studentInfo['egitim_programi'])) {
                    $progs = json_decode($studentInfo['egitim_programi'], true);
                    if (!is_array($progs)) $progs = explode(',', $studentInfo['egitim_programi']);
                    foreach($progs as $p) {
                        $p = trim($p);
                        if (empty($p)) continue;
                        $badgeClass = 'bg-secondary'; $badgeHarf = '?';
                        if (str_contains($p, 'Bedensel')) { $badgeClass = 'bg-danger'; $badgeHarf = 'F'; }
                        elseif (str_contains($p, 'Dil ve Konuşma')) { $badgeClass = 'bg-primary'; $badgeHarf = 'D'; }
                        elseif (str_contains($p, 'Zihinsel')) { $badgeClass = 'bg-success'; $badgeHarf = 'Z'; }
                        elseif (str_contains($p, 'Öğrenme') || str_contains($p, 'Özel Öğrenme')) { $badgeClass = 'bg-warning text-dark'; $badgeHarf = 'Ö'; }
                        elseif (str_contains($p, 'Otizm') || str_contains($p, 'Yaygın Gelişimsel') || str_contains($p, 'Spektrum')) { $badgeClass = 'bg-info text-dark'; $badgeHarf = 'O'; }
                        $programs[] = ['letter' => $badgeHarf, 'class' => $badgeClass, 'name' => $p];
                    }
                }

                $today = date('Y-m-d');
                $ramStatus = 'none'; $ramDate = '';
                if (!empty($studentInfo['ram_bitis'])) {
                    $ramDate = date('d.m.Y', strtotime($studentInfo['ram_bitis']));
                    $ramStatus = ($studentInfo['ram_bitis'] < $today) ? 'expired' : 'active';
                }

                $hasStatus = 'none'; $hasDate = '';
                if (!empty($studentInfo['hastane_raporu_bitis_tarihi'])) {
                    $hasDate = date('d.m.Y', strtotime($studentInfo['hastane_raporu_bitis_tarihi']));
                    $hasStatus = ($studentInfo['hastane_raporu_bitis_tarihi'] < $today) ? 'expired' : 'active';
                }

                $studentInfoMap[$studentId] = [
                    'photo'         => $studentInfo['profile_image'] ?? 'assets/images/user.jpg',
                    'city'          => $studentInfo['city_name'] ?? null,
                    'district'      => $studentInfo['district_name'] ?? null,
                    'message'       => !empty($messageParts) ? implode('<br>', $messageParts) : 'Bu öğrencinin sabit dersi yok.',
                    'fixed_lessons' => $fixedInfo,
                    'freq'          => $freqMap[$fullName] ?? 0,
                    'bireysel'      => (int)($studentInfo['telafi_bireysel_hak'] ?? 0),
                    'grup'          => (int)($studentInfo['telafi_grup_hak'] ?? 0),
                    'servis'        => in_array(strtolower($studentInfo['servis'] ?? ''), ['var', 'arasira']),
                    'mesafe'        => $studentInfo['mesafe'] ?? '',
                    'programs'      => $programs,
                    'ram_status'    => $ramStatus,
                    'ram_date'      => $ramDate,
                    'has_status'    => $hasStatus,
                    'has_date'      => $hasDate
                ];
            }
        }

        $lessonMap = [];
        foreach ($lessons as $lesson) {
            $lesson['type'] = 'lesson'; // Türü belirt
            $hourKey = date('H', strtotime($lesson['start_time']));
            $lessonMap[$lesson['teacher_id']][$hourKey] = $lesson;
        }

        // Değerlendirmeleri çek ve lessonMap'e ekle
        $degerlendirmeModel = new DegerlendirmeModel();
        $evaluations = $degerlendirmeModel->where('evaluation_date', $date)->findAll();

        foreach ($evaluations as $evaluation) {
            $evaluation['type'] = 'evaluation'; // Türü belirt
            $hourKey = date('H', strtotime($evaluation['start_time']));
            // Eğer o saatte zaten bir ders varsa, çakışma olabilir. Şimdilik üzerine yazıyoruz.
            // İdeal senaryoda çakışma kontrolü yapılmalı.
            $lessonMap[$evaluation['teacher_id']][$hourKey] = $evaluation;
        }

        // Öğretmenlerin izinlerini çek ve lessonMap'e ekle
        $leaveModel = new \App\Models\TeacherLeaveModel();
        $teacherIds = array_map(fn($t) => $t->id, $teachers);
        if (!empty($teacherIds)) {
            $leaves = $leaveModel
                ->whereIn('teacher_id', $teacherIds)
                ->where("DATE(start_date) <= ", $date)
                ->where("DATE(end_date) >= ", $date)
                ->findAll();

            foreach ($leaves as $leave) {
                if ($leave->leave_type === 'unpaid_daily' || $leave->leave_type === 'paid_daily') {
                    // Günlük izinler için tüm günü (08:00 - 19:59 arası) kapalı olarak işaretle
                    for ($hour = 8; $hour < 20; $hour++) {
                        $hourKey = str_pad($hour, 2, '0', STR_PAD_LEFT);
                        $lessonMap[$leave->teacher_id][$hourKey] = [
                            'type'       => 'leave',
                            'reason'     => $leave->reason,
                            'leave_type' => $leave->leave_type,
                        ];
                    }
                } else { // Saatlik izinler için
                    $start = new \DateTime($leave->start_date);
                    $end   = new \DateTime($leave->end_date);

                    // Sadece mevcut grid günü için saatlik izni işle
                    if ($start->format('Y-m-d') === $date) {
                        $current = clone $start;
                        while ($current < $end) {
                            $hourKey = $current->format('H');
                            $lessonMap[$leave->teacher_id][$hourKey] = [
                                'type'       => 'leave',
                                'reason'     => $leave->reason,
                                'leave_type' => $leave->leave_type,
                            ];
                            $current->modify('+1 hour');
                        }
                    }
                }
            }
        }

        $data = [
            'title'           => Time::parse($date)->toLocalizedString('d MMMM yyyy EEEE') . ' Ders Programı',
            'displayDate'     => $date,
            'dayOfWeekForGrid'=> date('N', strtotime($date)),
            'teachers'        => $teachers,
            'lessonMap'       => $lessonMap,
            'studentInfoMap'  => $studentInfoMap,
            'conflictMap'     => $conflictMap,
        ];
        return view('schedule/daily_grid', array_merge($this->data, $data));
    }
    
    /**
     * Tom-Select için tüm öğrencileri formatlanmış bir şekilde döndürür.
     */
    public function getStudentsForSelect()
        {
            if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }

            $studentModel = new \App\Models\StudentModel();
            $fixedLessonModel = new \App\Models\FixedLessonModel();

            // AJAX isteğinden gelen ek bilgileri al
            $teacherId = $this->request->getGet('teacher_id');
            $date = $this->request->getGet('date');
            
            // Gelen tarihten haftanın gününü hesapla (1=Pzt, ..., 7=Pazar)
            $dayOfWeek = date('N', strtotime($date));
            $time = $this->request->getGet('time');

            // 1. Tüm öğrencileri al
            $allStudents = $studentModel->select('id, adi, soyadi')->findAll();

            // 2. Bu saat dilimi için sabit bir ders var mı diye kontrol et
            $fixedLesson = $fixedLessonModel->where([
                'teacher_id'  => $teacherId,
                'day_of_week' => $dayOfWeek,
                'start_time'  => $time
            ])->findAll();
            
            $fixedStudentIds = array_column($fixedLesson, 'student_id');

            // 3. Öğrenci listesini formatla ve sabit olanları işaretle
            $formattedStudents = [];
            foreach ($allStudents as $student) {
                $formattedStudents[] = [
                    'value'    => $student['id'],
                    'text'     => $student['adi'] . ' ' . $student['soyadi'],
                    'is_fixed' => in_array($student['id'], $fixedStudentIds) // Sabit ise true, değilse false
                ];
            }

            // Sabit öğrencileri listenin en başına almak için sırala
            usort($formattedStudents, function ($a, $b) {
                return $b['is_fixed'] <=> $a['is_fixed'];
            });

            return $this->response->setJSON($formattedStudents);
        }
    
    public function getLessonDetails($lessonId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();

        // Dersin temel bilgilerini al
        $lesson = $lessonModel->find($lessonId);

        if (!$lesson) {
            return $this->response->setJSON(['success' => false, 'message' => 'Ders bulunamadı.']);
        }

        // Derse kayıtlı öğrencilerin ID ve Ad Soyad bilgilerini al
        $students = $lessonStudentModel
            ->select('students.id, CONCAT(students.adi, " ", students.soyadi) as name')
            ->join('students', 'students.id = lesson_students.student_id')
            ->where('lesson_id', $lessonId)
            ->findAll();

        $lesson['students'] = $students;

        return $this->response->setJSON(['success' => true, 'lesson' => $lesson]);
    }

    /**
     * Mevcut bir dersin öğrenci listesini günceller.
     * Eğer derste öğrenci kalmazsa, dersi siler.
     */
    public function updateLesson($lessonId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();

        $studentIds = array_unique($this->request->getPost('students') ?? []); // Düzeltme: Tekrar eden öğrenci ID'lerini kaldır.

        // Önce dersin var olup olmadığını kontrol et
        $lesson = $lessonModel->find($lessonId);
        if (!$lesson) {
            return $this->response->setJSON(['success' => false, 'message' => 'Güncellenecek ders bulunamadı.']);
        }

        // Eğer hiç öğrenci gönderilmediyse, dersi sil
        if (empty($studentIds)) {
            return $this->deleteLesson($lessonId);
        }

        $db->transStart();

        // Mevcut öğrenci kayıtlarını sil
        $lessonStudentModel->where('lesson_id', $lessonId)->delete();

        // Yeni öğrenci listesini ekle
        $studentData = array_map(fn($id) => ['lesson_id' => $lessonId, 'student_id' => $id], $studentIds);
        $lessonStudentModel->insertBatch($studentData);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Veritabanı hatası nedeniyle ders güncellenemedi.']);
        }

        $db->transCommit();
        
        // Olayı tetikle
        \CodeIgniter\Events\Events::trigger('schedule.changed', $lesson, $lesson['teacher_id']);

        return $this->response->setJSON(['success' => true, 'message' => 'Ders başarıyla güncellendi.']);
    }

    
    /**
     * Yeni bir ders oluşturur.
     */
    public function createLesson()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();

        $data = $this->request->getPost([
            'teacher_id', 'lesson_date', 'start_time', 'end_time'
        ]);
        $studentIds = array_unique($this->request->getPost('students') ?? []); // Düzeltme: Tekrar eden öğrenci ID'lerini kaldır ve null kontrolü ekle.

        if (empty($studentIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Lütfen en az bir öğrenci seçin.']);
        }

        $db->transStart();
        $lessonModel->insert($data);
        $lessonId = $lessonModel->getInsertID();

        $studentData = array_map(fn($id) => ['lesson_id' => $lessonId, 'student_id' => $id], $studentIds);
        $lessonStudentModel->insertBatch($studentData);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Veritabanı hatası.']);
        }
        $db->transCommit();
        
        \CodeIgniter\Events\Events::trigger('schedule.changed', $data, $data['teacher_id']);
        return $this->response->setJSON(['success' => true, 'message' => 'Ders başarıyla eklendi.']);
    }

    /**
     * Sürükle bırak ile öğrenciyi mevcut veya yeni derse taşır.
     * Öğrenci eski bir dersten geliyorsa oradan çıkarır, içi boşalırsa o dersi siler.
     */
    public function moveStudentDrop()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        
        $studentId = $this->request->getPost('student_id');
        $sourceLessonId = $this->request->getPost('source_lesson_id');
        $targetLessonId = $this->request->getPost('target_lesson_id');
        $targetTeacherId = $this->request->getPost('target_teacher_id');
        $lessonDate = $this->request->getPost('lesson_date');
        $startTime = $this->request->getPost('start_time');
        $endTime = $this->request->getPost('end_time');

        if (!$studentId || (!$targetLessonId && (!$targetTeacherId || !$lessonDate || !$startTime))) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gerekli bilgiler eksik.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $lessonStudentModel = new \App\Models\LessonStudentModel();
        $lessonModel = new \App\Models\LessonModel();

        // 1. Eski dersten çıkar (Sürükleme tablodan yapıldıysa)
        if ($sourceLessonId && $sourceLessonId !== $targetLessonId) {
            $lessonStudentModel->where('lesson_id', $sourceLessonId)
                               ->where('student_id', $studentId)
                               ->delete();
                               
            $remaining = $lessonStudentModel->where('lesson_id', $sourceLessonId)->countAllResults();
            if ($remaining == 0) {
                // Ders içindeki tüm öğrenciler çıktıysa dersin kendisini de sil
                $lessonModel->delete($sourceLessonId);
            }
        }

        // 2. Yeni hedefe ekle
        if ($targetLessonId && $sourceLessonId !== $targetLessonId) {
            // Hedef mevcut bir ders
            $exists = $lessonStudentModel->where('lesson_id', $targetLessonId)->where('student_id', $studentId)->countAllResults();
            if ($exists == 0) {
                $lessonStudentModel->insert(['lesson_id' => $targetLessonId, 'student_id' => $studentId]);
            }
        } elseif (!$targetLessonId) {
            // Hedef boş bir alan, ancak o saatte ders açılmış olabilir
            $existingLesson = $lessonModel->where('teacher_id', $targetTeacherId)
                                          ->where('lesson_date', $lessonDate)
                                          ->where('start_time', $startTime)
                                          ->first();
            if ($existingLesson) {
                // Ders zaten varmış, öğrenciyi içine ekle
                $exists = $lessonStudentModel->where('lesson_id', $existingLesson['id'])->where('student_id', $studentId)->countAllResults();
                if ($exists == 0) {
                    $lessonStudentModel->insert(['lesson_id' => $existingLesson['id'], 'student_id' => $studentId]);
                }
            } else {
                // O saat hiç ders açılmamış tamamen boş, dersi kur ve ekle
                $lessonModel->insert([
                    'teacher_id' => $targetTeacherId,
                    'lesson_date' => $lessonDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ]);
                $newLessonId = $lessonModel->getInsertID();
                $lessonStudentModel->insert(['lesson_id' => $newLessonId, 'student_id' => $studentId]);
            }
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Veritabanı hatası nedeniyle işlem tamamlanamadı.']);
        }
        
        $db->transCommit();
        \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $lessonDate], $targetTeacherId);
        
        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Mevcut bir dersi siler.
     */
    public function deleteLesson($lessonId)
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $lessonModel = new LessonModel();
        $lesson = $lessonModel->find($lessonId);
        if ($lesson && $lessonModel->delete($lessonId)) {
            \CodeIgniter\Events\Events::trigger('schedule.changed', $lesson, $lesson['teacher_id']);
            return $this->response->setJSON(['success' => true, 'message' => 'Ders başarıyla silindi.']);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Ders silinirken bir hata oluştu.']);
    }
    

    public function getLessonDates()
{
    $lessonModel = new LessonModel();
    // Veritabanındaki ders olan tüm benzersiz tarihleri çekiyoruz.
    $dates = $lessonModel->distinct()->findColumn('lesson_date');
    return $this->response->setJSON($dates ?? []);
    
}
    /**
     * Giriş yapmış öğretmenin haftalık ders programını gösterir.
     */
    public function mySchedule()
    {
        $lessonModel = new \App\Models\LessonModel();
        $teacherId = auth()->id();

        // 1. Haftanın Tarihlerini Hesapla (Pazar'dan Cumartesi'ye) - DÜZELTİLMİŞ YÖNTEM
        $requestedDate = $this->request->getGet('date') ?? 'now';
        try {
            $date = new \DateTime($requestedDate);
        } catch (\Exception $e) {
            $date = new \DateTime();
        }

        // Günün sayısal karşılığını al (Pazar=0, Pazartesi=1, ..., Cumartesi=6)
        $dayOfWeek = (int)$date->format('w'); 
        
        // Haftanın başlangıcını (Pazar) bul
        $startDate = (clone $date)->modify("-{$dayOfWeek} days");
        
        // Haftanın bitişini (Cumartesi) bul
        $endDate = (clone $startDate)->modify('+6 days');

        $weekDates = [];
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), (clone $endDate)->modify('+1 day'));
        foreach ($period as $day) {
            $weekDates[] = $day;
        }

        // 2. Modelden o haftanın derslerini çek (Bu kısım doğru çalışıyor)
        $lessonsRaw = $lessonModel->getLessonsForTeacherByWeek(
            $teacherId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        // 3. Veriyi View için işle: [gün][saat] formatında grupla (Bu kısım doğru çalışıyor)
        $scheduleData = [];
        foreach ($lessonsRaw as $lesson) {
            $dateKey = date('Y-m-d', strtotime($lesson['lesson_date']));
            $hourKey = date('H:i', strtotime($lesson['start_time']));
            $scheduleData[$dateKey][$hourKey][] = array_merge($lesson, ['type' => 'lesson']);
        }

        // Öğretmenin değerlendirmelerini çek ve scheduleData'ya ekle
        $degerlendirmeModel = new DegerlendirmeModel();
        $evaluationsRaw = $degerlendirmeModel
            ->where('teacher_id', $teacherId)
            ->where('evaluation_date >=', $startDate->format('Y-m-d'))
            ->where('evaluation_date <=', $endDate->format('Y-m-d'))
            ->findAll();

        foreach ($evaluationsRaw as $evaluation) {
            $dateKey = date('Y-m-d', strtotime($evaluation['evaluation_date']));
            $hourKey = date('H:i', strtotime($evaluation['start_time']));
            // Eğer o saatte zaten bir ders varsa, değerlendirme üzerine yazılır. Çakışma kontrolü frontend'de yapılmalı.
            $scheduleData[$dateKey][$hourKey][] = array_merge($evaluation, ['type' => 'evaluation']);
        }

        // 4. Veriyi View'e gönder
        $this->data['title'] = 'Haftalık Ders Programım';
        $this->data['weekDates'] = $weekDates;
        $this->data['scheduleData'] = $scheduleData;
        $this->data['currentDate'] = $date;

        return view('schedule/my_schedule', $this->data);
    }

    // Bu metodu ScheduleController.php içine ekle

    public function parentSchedule()
    {
        // Aktif olan çocuğu session'dan al
        $activeChildId = session('active_child_id');
        if (!$activeChildId) {
            // Henüz veli dashboard'ı olmadığı için anasayfaya yönlendirelim
            return redirect()->to('/')->with('error', 'Lütfen programı görmek için bir öğrenci seçin.');
        }

        $lessonModel = new \App\Models\LessonModel(); // DOĞRU KULLANIM
        
        // Filtre için GET parametrelerinden yıl ve ayı al
        $selectedYear = $this->request->getGet('year');
        $selectedMonth = $this->request->getGet('month');
        
        // Ders olan ayları ve yılları al
        $availableMonths = $lessonModel->getLessonMonthsForStudent($activeChildId);

        // Eğer formdan bir tarih gelmediyse ve dersi olan aylar varsa, en son ders olan ayı varsayılan yap
        if (empty($selectedYear) && !empty($availableMonths)) {
            $selectedYear = $availableMonths[0]['year'];
            $selectedMonth = $availableMonths[0]['month'];
        }

        // View için verileri hazırla
        $this->data['title'] = 'Aylık Ders Programı';
        $this->data['active_child_id'] = $activeChildId;
        $this->data['available_months'] = $availableMonths;
        // Sadece seçili bir yıl ve ay varsa dersleri getir
        $this->data['lessons'] = ($selectedYear && $selectedMonth) 
            ? $lessonModel->getLessonsForStudentByMonth($activeChildId, (int)$selectedYear, (int)$selectedMonth) 
            : [];
        $this->data['selected_year'] = $selectedYear;
        $this->data['selected_month'] = $selectedMonth;

        return view('schedule/parent_schedule', $this->data);
    }

// app/Controllers/ScheduleController.php İÇİNE YAPIŞTIRILACAK NİHAİ KOD

public function getStudentSuggestions()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403, 'Forbidden'); }

        try {
            $teacherId = $this->request->getGet('teacher_id');
            $date = $this->request->getGet('date');
            $startTime = $this->request->getGet('start_time');

            if (empty($teacherId) || empty($date) || empty($startTime)) {
                return $this->response->setStatusCode(400, 'Eksik parametre.');
            }

            // Gerekli Modeller
            $fixedLessonModel = new FixedLessonModel();
            $studentModel = new StudentModel();
            $lessonHistoryModel = new LessonHistoryModel();
            $teacherProfile = model(UserProfileModel::class)->where('user_id', $teacherId)->first();

            // Gerekli Değişkenler
            $dayOfWeek = date('N', strtotime($date));
            $dayNames = ['', 'Pzt', 'Salı', 'Çrş', 'Perş', 'Cuma', 'Cmt', 'Paz'];
            $suggestions = [];
            $existingStudentIds = [];
            
            // --- HAFTA MATEMATİĞİ ---
            $time = Time::parse($date);
            $weekOfMonth = (int)ceil($time->getDay() / 7);
            $weekMap = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'A'];
            $targetWeekType = $weekMap[$weekOfMonth];
            
            // --- UYARI MANTIĞI ---
            $allFixedForWarning = $fixedLessonModel->select('student_id, day_of_week')->findAll();
            $warningMap = [];
            foreach ($allFixedForWarning as $fl) {
                if ($fl['day_of_week'] != $dayOfWeek) {
                    $warningMap[$fl['student_id']][] = $dayNames[$fl['day_of_week']];
                }
            }
            foreach ($warningMap as $studentId => $days) {
                $warningMap[$studentId] = 'Sabit dersi: ' . implode(', ', array_unique($days));
            }

            // --- ÖNERİ KATMANLARI ---

            // KATMAN 0: SABİT DERSLER (A/B/C/D Matematiği ile)
            $allFixedLessonsRaw = $fixedLessonModel
                ->where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->findAll();

            $fixedLessonsByWeekType = [];
            foreach ($allFixedLessonsRaw as $fl) {
                $fixedLessonsByWeekType[$fl['week_type']][] = $fl['student_id'];
            }
            $fixedStudentIds = $fixedLessonsByWeekType[$targetWeekType] ?? $fixedLessonsByWeekType['A'] ?? [];
            
            if (!empty($fixedStudentIds)) {
                $fixedStudents = $studentModel->withDeleted()->find($fixedStudentIds);
                foreach ($fixedStudents as $student) {
                    $suggestions[] = ['id' => $student['id'], 'name' => trim($student['adi'] . ' ' . $student['soyadi']), 'type' => 'fixed', 'bireysel' => $student['telafi_bireysel_hak'] ?? 0, 'grup' => $student['telafi_grup_hak'] ?? 0, 'warning' => $warningMap[$student['id']] ?? null];
                    $existingStudentIds[] = $student['id'];
                }
            }

            // KATMAN 1 & 2: AKILLI ÖNERİLER (Ders Geçmişi)
            if ($teacherProfile) {
                $teacherFullName = trim($teacherProfile->first_name . ' ' . $teacherProfile->last_name);
                if (!empty($teacherFullName)) {
                    $historySuggestionsData = [];
                    if ($lessonHistoryModel->teacherHasHistory($teacherFullName)) {
                        $historySuggestionsData = $lessonHistoryModel->getSuggestionsByTeacherHistory($teacherFullName, (int)$dayOfWeek, $startTime);
                    } elseif (!empty($teacherProfile->branch)) {
                        $historySuggestionsData = $lessonHistoryModel->getSuggestionsByBranch($teacherProfile->branch, (int)$dayOfWeek, $startTime);
                    }
                    $this->_addHistorySuggestions($historySuggestionsData, $suggestions, $existingStudentIds, 'history', $warningMap);
                }
            }
            
            // KATMAN 3: DİĞER TÜM ÖĞRENCİLER
            $otherStudentsQuery = $studentModel->withDeleted()->select('id, adi, soyadi, telafi_bireysel_hak, telafi_grup_hak');
            if (!empty($existingStudentIds)) {
                $otherStudentsQuery->whereNotIn('id', $existingStudentIds);
            }
            $otherStudents = $otherStudentsQuery->orderBy('adi', 'ASC')->findAll();

            foreach ($otherStudents as $student) {
                $suggestions[] = ['id' => $student['id'], 'name' => trim($student['adi'] . ' ' . $student['soyadi']), 'type' => 'other', 'bireysel' => $student['telafi_bireysel_hak'] ?? 0, 'grup' => $student['telafi_grup_hak'] ?? 0, 'warning' => $warningMap[$student['id']] ?? null];
            }

            return $this->response->setJSON($suggestions);

        } catch (\Exception $e) {
            log_message('error', '[ScheduleController] HATA: ' . $e->getMessage() . ' | DOSYA: ' . $e->getFile() . ' | SATIR: ' . $e->getLine());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Sunucu hatası oluştu.']);
        }
    }

    public function getOffcanvasStudents()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403, 'Forbidden'); }

        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $dayOfWeek = date('N', strtotime($date));
        $mysqlDayOfWeek = ($dayOfWeek % 7) + 1; // 1 = Sunday in MySQL, PHP N: 1=Mon, 7=Sun
        
        $studentModel = new StudentModel();
        $fixedLessonModel = new \App\Models\FixedLessonModel();
        $lessonModel = new \App\Models\LessonModel();
        $lessonStudentModel = new \App\Models\LessonStudentModel();
        
        // We'll get all students + needed fields
        $students = $studentModel->select('id, adi, soyadi, profile_image, telafi_bireysel_hak, telafi_grup_hak, mesafe, servis, egitim_programi, ram_bitis, hastane_raporu_bitis_tarihi')
            ->orderBy('adi', 'ASC')
            ->findAll();
        
        $loggedInUser = auth()->user();
        $db = \Config\Database::connect();
        $teacherQuery = $db->table('users')
            ->select('users.id')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->where('auth_groups_users.group', 'ogretmen')
            ->where('users.active', 1);

        if ($loggedInUser->inGroup('sekreter') && !$loggedInUser->inGroup('admin', 'mudur')) {
            $assignmentModel = new \App\Models\AssignmentModel();
            $assignedTeacherIds = $assignmentModel->where('manager_user_id', $loggedInUser->id)->findColumn('managed_user_id');
            if (empty($assignedTeacherIds)) {
                $teacherQuery->where('0', 1); // Yield zero results
            } else {
                $teacherQuery->whereIn('users.id', $assignedTeacherIds);
            }
        }
        $validTeacherRows = $teacherQuery->get()->getResultArray();
        $teacherIds = array_column($validTeacherRows, 'id');

        // Check for lessons today to determine cell colors
        $todaysLessons = [];
        if (!empty($teacherIds)) {
            $todaysLessons = $lessonModel->select('id, start_time, teacher_id')
                ->where('lesson_date', $date)
                ->whereIn('teacher_id', $teacherIds)
                ->findAll();
        }
        
        $todaysLessonMap = [];
        $lessonIds = array_column($todaysLessons, 'id');
        $todaysLessonStudents = [];
        $conflictMapForOffcanvas = [];

        if (!empty($lessonIds)) {
            $todaysLessonStudentsRaw = $lessonStudentModel->whereIn('lesson_id', $lessonIds)->findAll();
            $lessonDetailMap = array_column($todaysLessons, null, 'id');
            
            foreach ($todaysLessonStudentsRaw as $ls) {
                if (isset($lessonDetailMap[$ls['lesson_id']])) {
                    $lessonDetail = $lessonDetailMap[$ls['lesson_id']];
                    $todaysLessonStudents[$ls['student_id']][] = $lessonDetail;
                }
            }

            // Conflict check map for offcanvas students
            // Only count teachers the user is allowed to see/are active
            $conflictDataRaw = $lessonStudentModel
                ->select('lesson_students.student_id, lessons.start_time, COUNT(DISTINCT lessons.teacher_id) as teacher_count')
                ->join('lessons', 'lessons.id = lesson_students.lesson_id')
                ->where('lessons.lesson_date', $date)
                ->whereIn('lessons.teacher_id', $teacherIds)
                ->groupBy('lesson_students.student_id, lessons.start_time')
                ->findAll();

            foreach ($conflictDataRaw as $conflict) {
                if ($conflict['teacher_count'] > 1) {
                    $conflictMapForOffcanvas[$conflict['student_id']][$conflict['start_time']] = true;
                }
            }
        }
            
        // Get lesson counts strictly for this day of week to sort by frequency
        $historyModel = new LessonHistoryModel();
        $builder = $historyModel->builder();
        $counts = $builder->select('student_name, COUNT(id) as freq')
                ->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek)
                ->groupBy('student_name')
                ->get()
                ->getResultArray();
                
        $freqMap = [];
        foreach($counts as $c) {
            $freqMap[$c['student_name']] = (int)$c['freq'];
        }

        // Get lesson counts by ANY teacher for filtering
        $teacherCountsDb = $historyModel->builder()
            ->select('student_name, teacher_name, COUNT(id) as t_freq')
            ->groupBy('student_name, teacher_name')
            ->get()
            ->getResultArray();
            
        $teacherFreqMap = [];
        foreach($teacherCountsDb as $tc) {
            if(!isset($teacherFreqMap[$tc['student_name']])) {
                $teacherFreqMap[$tc['student_name']] = [];
            }
            $teacherFreqMap[$tc['student_name']][$tc['teacher_name']] = (int)$tc['t_freq'];
        }

        // Get fixed lesson statuses for this day of week
        $fixedLessons = $fixedLessonModel->select('student_id')
            ->where('day_of_week', $dayOfWeek)
            ->groupBy('student_id')
            ->findAll();
        
        // Get ALL fixed lessons for these students to check if they match TODAY's assignments
        $allFixedLessonsDb = $fixedLessonModel->findAll();
        $allFixedLessonsMap = [];
        foreach($allFixedLessonsDb as $fl) {
            $allFixedLessonsMap[$fl['student_id']][] = $fl;
        }

        $fixedMap = [];
        foreach($fixedLessons as $fl) {
            $fixedMap[$fl['student_id']] = true;
        }

        $formatted = [];
        foreach($students as $s) {
            $fullName = trim($s['adi'] . ' ' . $s['soyadi']);
            $freq = $freqMap[$fullName] ?? 0;
            $isFixed = isset($fixedMap[$s['id']]);
            
            // Format programs (single letter styling)
            $programs = [];
            if (!empty($s['egitim_programi'])) {
                $progs = json_decode($s['egitim_programi'], true);
                if (!is_array($progs)) {
                    $progs = explode(',', $s['egitim_programi']);
                }
                foreach($progs as $p) {
                    $p = trim($p);
                    if (empty($p)) continue;
                    $badgeClass = 'bg-secondary'; $badgeHarf = '?';
                    if (str_contains($p, 'Bedensel')) { $badgeClass = 'bg-danger'; $badgeHarf = 'F'; }
                    elseif (str_contains($p, 'Dil ve Konuşma')) { $badgeClass = 'bg-primary'; $badgeHarf = 'D'; }
                    elseif (str_contains($p, 'Zihinsel')) { $badgeClass = 'bg-success'; $badgeHarf = 'Z'; }
                    elseif (str_contains($p, 'Öğrenme') || str_contains($p, 'Özel Öğrenme')) { $badgeClass = 'bg-warning text-dark'; $badgeHarf = 'Ö'; }
                    elseif (str_contains($p, 'Otizm') || str_contains($p, 'Yaygın Gelişimsel') || str_contains($p, 'Spektrum')) { $badgeClass = 'bg-info text-dark'; $badgeHarf = 'O'; }
                    
                    $programs[] = ['letter' => $badgeHarf, 'class' => $badgeClass, 'name' => $p];
                }
            }
            
            $today = date('Y-m-d');
            
            // RAM Status Logic
            $ramStatus = 'none';
            $ramDate = '';
            if (!empty($s['ram_bitis'])) {
                $ramDate = date('d.m.Y', strtotime($s['ram_bitis']));
                if ($s['ram_bitis'] < $today) {
                    $ramStatus = 'expired';
                } else {
                    $ramStatus = 'active';
                }
            }

            // Hastane Status Logic
            $hasStatus = 'none';
            $hasDate = '';
            if (!empty($s['hastane_raporu_bitis_tarihi'])) {
                $hasDate = date('d.m.Y', strtotime($s['hastane_raporu_bitis_tarihi']));
                if ($s['hastane_raporu_bitis_tarihi'] < $today) {
                    $hasStatus = 'expired';
                } else {
                    $hasStatus = 'active';
                }
            }

            // Color Class Logic for Offcanvas
            $studentAssignments = $todaysLessonStudents[$s['id']] ?? [];
            $scheduledColorClass = ''; // Empty means not scheduled today
            
            if (!empty($studentAssignments)) {
                $scheduledColorClass = 'bg-secondary-subtle'; // default if assigned
                
                // Let's check all assignments for this student today
                foreach ($studentAssignments as $assignment) {
                    $startTime = $assignment['start_time'];
                    
                    // Check Conflict first
                    if (!empty($conflictMapForOffcanvas[$s['id']][$startTime])) {
                        $scheduledColorClass = 'bg-danger-subtle';
                        break; // Highest priority, stop checking
                    }
                    
                    // Check Fixed Match for this specific time
                    $isExactFixedMatch = false;
                    $studentFixedAll = $allFixedLessonsMap[$s['id']] ?? [];
                    foreach ($studentFixedAll as $fixed) {
                        if ($fixed['day_of_week'] == $dayOfWeek && $fixed['start_time'] == $startTime) {
                            $isExactFixedMatch = true;
                            break;
                        }
                    }

                    if ($isExactFixedMatch) {
                        if ($scheduledColorClass !== 'bg-danger-subtle') {
                            $scheduledColorClass = 'bg-success-subtle';
                        }
                    } else {
                        // Is not matching time/day. Check if they have ANY fixed lesson (which would make them warning colored here if they aren't danger/success)
                        if (!empty($studentFixedAll) && $scheduledColorClass === 'bg-secondary-subtle') {
                            $scheduledColorClass = 'bg-warning-subtle';
                        }
                    }
                }
            }

            $formatted[] = [
                'id' => $s['id'],
                'name' => $fullName,
                'photo' => $s['profile_image'] ? base_url($s['profile_image']) : base_url('assets/images/user.jpg'),
                'freq' => $freq,
                'bireysel' => (int)($s['telafi_bireysel_hak'] ?? 0),
                'grup' => (int)($s['telafi_grup_hak'] ?? 0),
                'total_telafi' => (int)($s['telafi_bireysel_hak'] ?? 0) + (int)($s['telafi_grup_hak'] ?? 0),
                'mesafe' => $s['mesafe'] ?? '',
                'servis' => in_array(strtolower($s['servis'] ?? ''), ['var', 'arasira']),
                'programs' => $programs,
                'is_fixed' => $isFixed,
                'teachers' => $teacherFreqMap[$fullName] ?? [],
                'scheduled_color_class' => $scheduledColorClass,
                'ram_status' => $ramStatus,
                'ram_date' => $ramDate,
                'has_status' => $hasStatus,
                'has_date' => $hasDate
            ];
        }
        
        // Default Sort: Frequency DESC, Name ASC
        usort($formatted, function($a, $b) {
            if ($a['freq'] == $b['freq']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $b['freq'] <=> $a['freq'];
        });
        
        return $this->response->setJSON($formatted);
    }

    // --- BU YARDIMCI FONKSİYON DA GÜNCELLENDİ ---
    private function _addHistorySuggestions(array $historyData, array &$suggestions, array &$existingStudentIds, string $type, array $warningMap)
    {
        if (empty($historyData)) { return; }
        $studentModel = new StudentModel();
        $studentNames = array_column($historyData, 'student_name');
        if (empty($studentNames)) { return; }

        $studentDetails = $studentModel->withDeleted()
            ->select('id, adi, soyadi, telafi_bireysel_hak, telafi_grup_hak')
            ->whereIn('CONCAT(adi, " ", soyadi)', $studentNames)
            ->findAll();

        $nameToDetailsMap = [];
        foreach($studentDetails as $student) {
            $nameToDetailsMap[trim($student['adi'] . ' ' . $student['soyadi'])] = $student;
        }

        foreach ($historyData as $hist) {
            $studentDetail = $nameToDetailsMap[$hist['student_name']] ?? null;
            if ($studentDetail && !in_array($studentDetail['id'], $existingStudentIds)) {
                $suggestions[] = ['id' => $studentDetail['id'], 'name' => $hist['student_name'], 'type' => $type, 'bireysel' => $studentDetail['telafi_bireysel_hak'] ?? 0, 'grup' => $studentDetail['telafi_grup_hak'] ?? 0, 'warning' => $warningMap[$studentDetail['id']] ?? null];
                $existingStudentIds[] = $studentDetail['id'];
            }
        }
    }


public function addFixedLessonsForDay()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $teacherId = $this->request->getPost('teacher_id');
        $date = $this->request->getPost('date');

        if (empty($teacherId) || empty($date)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametre.']);
        }

        // Öğretmenin izinlerini kontrol et
        $leaveModel = new \App\Models\TeacherLeaveModel();
        $leaves = $leaveModel
            ->where('teacher_id', $teacherId)
            ->where("DATE(start_date) <= ", $date)
            ->where("DATE(end_date) >= ", $date)
            ->findAll();

        $blockedHours = [];
        if (!empty($leaves)) {
            foreach ($leaves as $leave) {
                if ($leave->leave_type === 'unpaid_daily' || $leave->leave_type === 'paid_daily') {
                    return $this->response->setJSON(['success' => true, 'message' => 'Öğretmen tüm gün izinli olduğu için sabit dersler eklenmedi.']);
                }
                
                // Saatlik izin varsa, izinli saatleri topla
                $start = new \DateTime($leave->start_date);
                $end   = new \DateTime($leave->end_date);
                if ($start->format('Y-m-d') === $date) {
                    $current = clone $start;
                    while ($current < $end) {
                        $blockedHours[] = $current->format('H:i:s');
                        $current->modify('+1 hour');
                    }
                }
            }
        }

        $fixedLessonModel = new FixedLessonModel();
        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();

        $dayOfWeek = date('N', strtotime($date));
        $time = Time::parse($date);
        $weekOfMonth = (int)ceil($time->getDay() / 7);
        $weekMap = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'A'];
        $targetWeekType = $weekMap[$weekOfMonth];

        $fixedLessons = $fixedLessonModel
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->groupStart()
                ->where('week_type', $targetWeekType)
                ->orWhere('week_type', 'A')
            ->groupEnd()
            ->findAll();

        if (empty($fixedLessons)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Bu öğretmen için bugüne ait tanımlanmış sabit ders bulunmamaktadır.']);
        }

        $groupedFixedLessons = [];
        foreach ($fixedLessons as $fixed) {
            $groupedFixedLessons[$fixed['start_time']][] = $fixed;
        }

        $existingLessons = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('lesson_date', $date)
            ->findColumn('start_time') ?? [];

        $lessonsToAdd = [];
        $lessonStudentsToAdd = [];
        $skippedCount = 0;
        
        foreach ($groupedFixedLessons as $startTime => $lessonsInSlot) {
            if (in_array($startTime, $existingLessons)) {
                continue;
            }

            if (in_array($startTime, $blockedHours)) {
                $skippedCount++;
                continue;
            }

            $firstLessonInSlot = $lessonsInSlot[0];
            $lessonsToAdd[] = [
                'teacher_id'  => $firstLessonInSlot['teacher_id'],
                'lesson_date' => $date,
                'start_time'  => $firstLessonInSlot['start_time'],
                'end_time'    => $firstLessonInSlot['end_time'],
            ];
            $lessonStudentsToAdd[$startTime] = array_column($lessonsInSlot, 'student_id');
        }

        if (empty($lessonsToAdd) && $skippedCount === 0) {
            return $this->response->setJSON(['success' => true, 'message' => 'Tüm sabit dersler zaten programda mevcut. Yeni ders eklenmedi.']);
        }

        if (!empty($lessonsToAdd)) {
            $db->transStart();
            $lessonModel->insertBatch($lessonsToAdd);
            $insertedLessons = $lessonModel
                ->where('teacher_id', $teacherId)
                ->where('lesson_date', $date)
                ->whereIn('start_time', array_keys($lessonStudentsToAdd))
                ->findAll();
            $finalStudentData = [];
            foreach ($insertedLessons as $lesson) {
                $studentIds = $lessonStudentsToAdd[$lesson['start_time']] ?? [];
                if (!empty($studentIds)) {
                    foreach($studentIds as $studentId) {
                        $finalStudentData[] = ['lesson_id'  => $lesson['id'], 'student_id' => $studentId];
                    }
                }
            }
            if (!empty($finalStudentData)) {
                $lessonStudentModel->insertBatch($finalStudentData);
            }
            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => 'Dersler eklenirken bir veritabanı hatası oluştu.']);
            }
            $db->transCommit();
            \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $date], $teacherId);
        }

        $message = '';
        if (count($lessonsToAdd) > 0) {
            $message .= count($lessonsToAdd) . ' adet yeni sabit ders programa başarıyla eklendi.';
        }
        if ($skippedCount > 0) {
            $message .= ($message ? ' ' : '') . $skippedCount . ' ders, saatlik izinle çakıştığı için atlandı.';
        }
        if (empty($message)) {
            $message = 'Tüm sabit dersler zaten programda mevcut veya izin saatleriyle çakışıyor.';
        }

        return $this->response->setJSON(['success' => true, 'message' => $message]);
    }

    public function deleteLessonsForDay()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $teacherId = $this->request->getPost('teacher_id');
        $date = $this->request->getPost('date');

        if (empty($teacherId) || empty($date)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametre.']);
        }

        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();

        $lessonIdsToDelete = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('lesson_date', $date)
            ->findColumn('id');

        if (empty($lessonIdsToDelete)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Bu öğretmen için belirtilen tarihte silinecek ders bulunamadı.']);
        }
        
        $deletedCount = count($lessonIdsToDelete);

        $db->transStart();

        $lessonStudentModel->whereIn('lesson_id', $lessonIdsToDelete)->delete();
        
        $lessonModel->whereIn('id', $lessonIdsToDelete)->delete();

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Dersler silinirken bir veritabanı hatası oluştu.']);
        }

        $db->transCommit();

        \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $date], $teacherId);

        return $this->response->setJSON(['success' => true, 'message' => $deletedCount . ' adet ders ve bağlı öğrenci kayıtları programdan başarıyla silindi.']);
    }

    public function addAllFixedLessonsForDay()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $teacherIds = $this->request->getPost('teacher_ids');
        $date = $this->request->getPost('date');

        if (empty($teacherIds) || empty($date)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametre.']);
        }

        $fixedLessonModel = new FixedLessonModel();
        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();
        
        $dayOfWeek = date('N', strtotime($date));
        $time = Time::parse($date);
        $weekOfMonth = (int)ceil($time->getDay() / 7);
        $weekMap = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'A'];
        $targetWeekType = $weekMap[$weekOfMonth];
        
        $totalAddedCount = 0;
        $dailyLeaveSkippedTeacherCount = 0;
        $hourlyLeaveSkippedLessonCount = 0;

        // Tüm öğretmenler için tüm izinleri tek seferde çek
        $leaveModel = new \App\Models\TeacherLeaveModel();
        $allLeaves = $leaveModel
            ->whereIn('teacher_id', $teacherIds)
            ->where("DATE(start_date) <= ", $date)
            ->where("DATE(end_date) >= ", $date)
            ->findAll();

        // İzinleri öğretmen ID'sine göre grupla
        $leavesByTeacher = [];
        foreach ($allLeaves as $leave) {
            $leavesByTeacher[$leave->teacher_id][] = $leave;
        }

        $db->transStart();

        foreach ($teacherIds as $teacherId) {
            $teacherLeaves = $leavesByTeacher[$teacherId] ?? [];
            $blockedHours = [];
            $hasDailyLeave = false;

            if (!empty($teacherLeaves)) {
                foreach ($teacherLeaves as $leave) {
                    if ($leave->leave_type === 'unpaid_daily' || $leave->leave_type === 'paid_daily') {
                        $hasDailyLeave = true;
                        break; // Günlük izin bulundu, diğerlerini kontrol etmeye gerek yok
                    }
                    // Saatlik izinleri işle
                    $start = new \DateTime($leave->start_date);
                    $end   = new \DateTime($leave->end_date);
                    if ($start->format('Y-m-d') === $date) {
                        $current = clone $start;
                        while ($current < $end) {
                            $blockedHours[] = $current->format('H:i:s');
                            $current->modify('+1 hour');
                        }
                    }
                }
            }

            if ($hasDailyLeave) {
                $dailyLeaveSkippedTeacherCount++;
                continue; // Öğretmeni tamamen atla
            }

            $fixedLessons = $fixedLessonModel
                ->where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->groupStart()
                    ->where('week_type', $targetWeekType)
                    ->orWhere('week_type', 'A')
                ->groupEnd()
                ->findAll();

            if (empty($fixedLessons)) continue;

            $groupedFixedLessons = [];
            foreach ($fixedLessons as $fixed) {
                $groupedFixedLessons[$fixed['start_time']][] = $fixed;
            }

            $existingLessons = $lessonModel
                ->where('teacher_id', $teacherId)
                ->where('lesson_date', $date)
                ->findColumn('start_time') ?? [];

            foreach ($groupedFixedLessons as $startTime => $lessonsInSlot) {
                if (in_array($startTime, $existingLessons)) continue;

                if (!empty($blockedHours) && in_array($startTime, $blockedHours)) {
                    $hourlyLeaveSkippedLessonCount++;
                    continue;
                }

                $firstLesson = $lessonsInSlot[0];
                $lessonData = [
                    'teacher_id'  => $firstLesson['teacher_id'],
                    'lesson_date' => $date,
                    'start_time'  => $firstLesson['start_time'],
                    'end_time'    => $firstLesson['end_time'],
                ];
                
                $lessonModel->insert($lessonData);
                $lessonId = $lessonModel->getInsertID();
                $totalAddedCount++;

                $studentData = [];
                foreach ($lessonsInSlot as $lesson) {
                    $studentData[] = [
                        'lesson_id' => $lessonId,
                        'student_id' => $lesson['student_id']
                    ];
                }
                if (!empty($studentData)) {
                    $lessonStudentModel->insertBatch($studentData);
                }
            }
        }
        
        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Toplu ders eklenirken bir veritabanı hatası oluştu.']);
        }

        $db->transCommit();

        $message = '';
        if ($totalAddedCount > 0) {
            $message .= $totalAddedCount . ' adet yeni sabit ders eklendi.';
        }
        if ($dailyLeaveSkippedTeacherCount > 0) {
            $message .= ($message ? ' ' : '') . $dailyLeaveSkippedTeacherCount . ' öğretmen tüm gün izinli olduğu için atlandı.';
        }
        if ($hourlyLeaveSkippedLessonCount > 0) {
            $message .= ($message ? ' ' : '') . $hourlyLeaveSkippedLessonCount . ' ders, saatlik izinle çakıştığı için atlandı.';
        }
        if (empty($message)) {
            $message = 'Tüm sabit dersler zaten programda mevcut veya öğretmenler izinli. Yeni ders eklenmedi.';
        }

        if ($totalAddedCount > 0) {
            \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $date], $teacherIds);
        }

        return $this->response->setJSON(['success' => true, 'message' => $message]);
    }

    /**
     * YENİ FONKSİYON: Listelenen tüm öğretmenler için o güne ait tüm dersleri siler.
     */
    public function deleteAllLessonsForDay()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $teacherIds = $this->request->getPost('teacher_ids');
        $date = $this->request->getPost('date');

        if (empty($teacherIds) || empty($date)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametre.']);
        }

        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();

        $lessonIdsToDelete = $lessonModel
            ->whereIn('teacher_id', $teacherIds)
            ->where('lesson_date', $date)
            ->findColumn('id');

        if (empty($lessonIdsToDelete)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Belirtilen tarihte silinecek ders bulunamadı.']);
        }

        $deletedCount = count($lessonIdsToDelete);
        $db->transStart();

        $lessonStudentModel->whereIn('lesson_id', $lessonIdsToDelete)->delete();
        $lessonModel->whereIn('id', $lessonIdsToDelete)->delete();

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Toplu ders silinirken bir veritabanı hatası oluştu.']);
        }

        $db->transCommit();
        
        \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $date], $teacherIds);

        return $this->response->setJSON(['success' => true, 'message' => $deletedCount . ' adet ders programdan başarıyla silindi.']);
    }

    // --- DEĞERLENDİRME FONKSİYONLARI ---

    public function createEvaluation()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $data = $this->request->getPost([
            'teacher_id',
            'evaluation_date',
            'start_time',
            'end_time'
        ]);

        // Basit bir doğrulama
        if (empty($data['teacher_id']) || empty($data['evaluation_date']) || empty($data['start_time'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametreler.']);
        }

        $degerlendirmeModel = new DegerlendirmeModel();

        if ($degerlendirmeModel->insert($data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Değerlendirme başarıyla eklendi.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Değerlendirme eklenirken bir hata oluştu.']);
        }
    }

    public function getEvaluationDetails($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $degerlendirmeModel = new DegerlendirmeModel();
        $evaluation = $degerlendirmeModel->find($id);

        if ($evaluation) {
            return $this->response->setJSON(['success' => true, 'data' => $evaluation]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Değerlendirme bulunamadı.']);
        }
    }

    public function updateEvaluation($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $notes = $this->request->getPost('notes');

        $degerlendirmeModel = new DegerlendirmeModel();

        if ($degerlendirmeModel->update($id, ['notes' => $notes])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Değerlendirme notu başarıyla güncellendi.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Güncelleme sırasında bir hata oluştu.']);
        }
    }

    public function deleteEvaluation($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $degerlendirmeModel = new DegerlendirmeModel();

        if ($degerlendirmeModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Değerlendirme başarıyla silindi.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Değerlendirme silinirken bir hata oluştu.']);
        }
    }

    public function reportAbsenceAnddeleteLesson()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $lessonId  = $this->request->getPost('lesson_id');
        $studentIds = $this->request->getPost('student_ids'); // Can be an array
        $reason    = $this->request->getPost('reason');
        $loggedInUserId = auth()->id();

        if (empty($lessonId) || empty($studentIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametreler: Ders veya Öğrenci ID\'si eksik.']);
        }
        
        if (!is_array($studentIds)) {
            $studentIds = [$studentIds];
        }

        $lessonModel = new LessonModel();
        $lesson = $lessonModel->find($lessonId);

        if (!$lesson) {
            return $this->response->setJSON(['success' => false, 'message' => 'İşlem yapılacak ders bulunamadı.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Devamsızlığı kaydet for each student
        $absenceModel = new StudentAbsenceModel();
        foreach ($studentIds as $studentId) {
            $absenceData = [
                'student_id'  => $studentId,
                'teacher_id'  => $lesson['teacher_id'],
                'lesson_date' => $lesson['lesson_date'],
                'start_time'  => $lesson['start_time'],
                'end_time'    => $lesson['end_time'],
                'reason'      => $reason,
                'created_by'  => $loggedInUserId,
            ];
            $absenceModel->insert($absenceData);
        }

        // 2. Dersi sil
        $lessonModel->delete($lessonId); // This should also trigger deletion of lesson_students via DB constraints or model callbacks

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Veritabanı hatası nedeniyle işlem tamamlanamadı.']);
        }

        $db->transCommit();
        
        // Olayı tetikle
        \CodeIgniter\Events\Events::trigger('schedule.changed', $lesson, $lesson['teacher_id']);

        return $this->response->setJSON(['success' => true, 'message' => 'Ders silindi ve devamsızlık(lar) başarıyla kaydedildi.']);
    }

    public function updateTeacherOrder()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $order = $this->request->getPost('order');
        if (empty($order)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sıralama verisi alınamadı.']);
        }

        $userProfileModel = new UserProfileModel();
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        foreach ($order as $index => $teacherId) {
            $userProfileModel->where('user_id', $teacherId)
                ->set(['display_order' => $index + 1])
                ->update();
        }
        
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sıralama güncellenirken bir hata oluştu.']);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Sıralama başarıyla güncellendi.']);
    }
}