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
        $db_lessons = $lessonModel
            ->select("lesson_date, COUNT(id) as lesson_count")
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
                $teachers = $teacherQuery->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
            }
        } else {
            $teachers = $teacherQuery->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
        }

        $lessonModel = new LessonModel();
        $lessons = $lessonModel
            ->select('lessons.*, GROUP_CONCAT(s.id) as student_ids, GROUP_CONCAT(CONCAT(s.adi, " ", s.soyadi) SEPARATOR "||") as student_names')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id', 'left')
            ->join('students s', 's.id = ls.student_id', 'left')
            ->where('lesson_date', $date)
            ->groupBy('lessons.id')
            ->findAll();
            
        $allStudentIdsOnPage = [];
        foreach ($lessons as $lesson) {
            if (!empty($lesson['student_ids'])) {
                $allStudentIdsOnPage = array_merge($allStudentIdsOnPage, explode(',', $lesson['student_ids']));
            }
        }
        $allStudentIdsOnPage = array_unique(array_filter($allStudentIdsOnPage));

        $studentInfoMap = [];
        if (!empty($allStudentIdsOnPage)) {
            $fixedLessonModel = new FixedLessonModel();
            $studentModel = new StudentModel();
            $dayNames = ['', 'Pzt', 'Salı', 'Çrş', 'Perş', 'Cuma', 'Cmt', 'Paz'];

            $studentDetails = $studentModel
                ->select('students.id, students.profile_image, c.name as city_name, d.name as district_name')
                ->join('cities c', 'c.id = students.city_id', 'left')
                ->join('districts d', 'd.id = students.district_id', 'left')
                ->whereIn('students.id', $allStudentIdsOnPage)
                ->findAll();
            $studentDetailsMap = array_column($studentDetails, null, 'id');

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
                
                $studentInfoMap[$studentId] = [
                    'photo'         => $studentInfo['profile_image'] ?? 'assets/images/user.jpg',
                    'city'          => $studentInfo['city_name'],
                    'district'      => $studentInfo['district_name'],
                    'message'       => !empty($messageParts) ? implode('<br>', $messageParts) : 'Bu öğrencinin sabit dersi yok.',
                    'fixed_lessons' => $fixedInfo 
                ];
            }
        }

        $lessonMap = [];
        foreach ($lessons as $lesson) {
            $hourKey = date('H', strtotime($lesson['start_time']));
            $lessonMap[$lesson['teacher_id']][$hourKey] = $lesson;
        }

        $data = [
            'title'           => Time::parse($date)->toLocalizedString('d MMMM yyyy EEEE') . ' Ders Programı',
            'displayDate'     => $date,
            'dayOfWeekForGrid'=> date('N', strtotime($date)),
            'teachers'        => $teachers,
            'lessonMap'       => $lessonMap,
            'studentInfoMap'  => $studentInfoMap,
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

        $studentIds = $this->request->getPost('students') ?? [];

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
        $studentIds = $this->request->getPost('students');

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
            $scheduleData[$dateKey][$hourKey][] = $lesson;
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

        // 1. Gerekli Modelleri Yükle
        $fixedLessonModel = new FixedLessonModel();
        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();

        // 2. Haftanın gününü hesapla (1=Pzt, 2=Salı, ..., 7=Pazar)
        $dayOfWeek = date('N', strtotime($date));

        // 3. Öğretmenin o güne ait tüm sabit derslerini bul
        $fixedLessons = $fixedLessonModel
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->findAll();

        if (empty($fixedLessons)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Bu öğretmen için bugüne ait tanımlanmış sabit ders bulunmamaktadır.']);
        }

        // 4. O gün için öğretmenin mevcut ders saatlerini al
        $existingLessons = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('lesson_date', $date)
            ->findColumn('start_time') ?? [];

        // 5. Eklenecek dersleri hazırla ve çakışmaları kontrol et
        $lessonsToAdd = [];
        $lessonStudentsToAdd = [];
        $addedCount = 0;

        foreach ($fixedLessons as $fixed) {
            // Eğer bu saatte zaten bir ders varsa, bu sabit dersi atla
            if (in_array($fixed['start_time'], $existingLessons)) {
                continue;
            }

            $lessonsToAdd[] = [
                'teacher_id'  => $fixed['teacher_id'],
                'lesson_date' => $date,
                'start_time'  => $fixed['start_time'],
                'end_time'    => $fixed['end_time'],
            ];
            // Eklenecek öğrenciyi de geçici bir dizide tut
            $lessonStudentsToAdd[$fixed['start_time']] = $fixed['student_id'];
            $addedCount++;
        }

        // 6. Eklenecek yeni ders yoksa işlemi bitir
        if (empty($lessonsToAdd)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Tüm sabit dersler zaten programda mevcut. Yeni ders eklenmedi.']);
        }

        // 7. Veritabanı işlemini başlat (transaction)
        $db->transStart();

        // Dersleri toplu olarak ekle
        $lessonModel->insertBatch($lessonsToAdd);

        // Eklenen derslerin ID'lerini ve başlangıç saatlerini al
        $insertedLessons = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('lesson_date', $date)
            ->whereIn('start_time', array_keys($lessonStudentsToAdd))
            ->findAll();

        // Her derse ait öğrenciyi ekle
        $finalStudentData = [];
        foreach ($insertedLessons as $lesson) {
            $studentId = $lessonStudentsToAdd[$lesson['start_time']] ?? null;
            if ($studentId) {
                $finalStudentData[] = [
                    'lesson_id' => $lesson['id'],
                    'student_id' => $studentId,
                ];
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
        
        // Bildirim göndermek için olay tetikle
        \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $date], $teacherId);

        return $this->response->setJSON(['success' => true, 'message' => $addedCount . ' adet yeni sabit ders programa başarıyla eklendi.']);
    }
    

    /**
     * YENİ FONKSİYON: Belirli bir öğretmen ve tarihteki tüm dersleri siler.
     */
    public function deleteLessonsForDay()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $teacherId = $this->request->getPost('teacher_id');
        $date = $this->request->getPost('date');

        if (empty($teacherId) || empty($date)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Eksik parametre.']);
        }

        // 1. Gerekli Modelleri Yükle
        $lessonModel = new LessonModel();
        $lessonStudentModel = new LessonStudentModel();
        $db = \Config\Database::connect();

        // 2. Silinecek derslerin ID'lerini bul
        $lessonIdsToDelete = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('lesson_date', $date)
            ->findColumn('id');

        if (empty($lessonIdsToDelete)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Bu öğretmen için belirtilen tarihte silinecek ders bulunamadı.']);
        }
        
        $deletedCount = count($lessonIdsToDelete);

        // 3. Veritabanı işlemini başlat (transaction)
        $db->transStart();

        // Önce derslere bağlı öğrenci kayıtlarını sil
        $lessonStudentModel->whereIn('lesson_id', $lessonIdsToDelete)->delete();
        
        // Sonra derslerin kendisini sil
        $lessonModel->whereIn('id', $lessonIdsToDelete)->delete();

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Dersler silinirken bir veritabanı hatası oluştu.']);
        }

        $db->transCommit();

        // Bildirim göndermek için olay tetikle
        \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $date], $teacherId);

        return $this->response->setJSON(['success' => true, 'message' => $deletedCount . ' adet ders ve bağlı öğrenci kayıtları programdan başarıyla silindi.']);
    }

     /**
     * YENİ FONKSİYON: Listelenen tüm öğretmenler için o güne ait sabit dersleri
     * programa ekler, çakışanları atlar.
     */
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
        $totalAdded = 0;

        $db->transStart();

        foreach ($teacherIds as $teacherId) {
            $fixedLessons = $fixedLessonModel
                ->where('teacher_id', $teacherId)
                ->where('day_of_week', $dayOfWeek)
                ->findAll();

            if (empty($fixedLessons)) continue;

            $existingLessons = $lessonModel
                ->where('teacher_id', $teacherId)
                ->where('lesson_date', $date)
                ->findColumn('start_time') ?? [];

            foreach ($fixedLessons as $fixed) {
                if (in_array($fixed['start_time'], $existingLessons)) continue;
                
                $lessonData = [
                    'teacher_id'  => $fixed['teacher_id'],
                    'lesson_date' => $date,
                    'start_time'  => $fixed['start_time'],
                    'end_time'    => $fixed['end_time'],
                ];
                $lessonModel->insert($lessonData);
                $lessonId = $lessonModel->getInsertID();
                $lessonStudentModel->insert(['lesson_id' => $lessonId, 'student_id' => $fixed['student_id']]);
                
                $totalAdded++;
            }
        }
        
        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Toplu ders eklenirken bir veritabanı hatası oluştu.']);
        }

        $db->transCommit();
        
        if ($totalAdded === 0) {
            return $this->response->setJSON(['success' => true, 'message' => 'Tüm sabit dersler zaten programda mevcut. Yeni ders eklenmedi.']);
        }

        // Bildirim göndermek için olay tetikle (tüm öğretmenler için)
        \CodeIgniter\Events\Events::trigger('schedule.changed', ['date' => $date], $teacherIds);

        return $this->response->setJSON(['success' => true, 'message' => $totalAdded . ' adet yeni sabit ders programa başarıyla eklendi.']);
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

}