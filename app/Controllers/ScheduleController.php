<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AssignmentModel;
use App\Models\LessonModel;
use App\Models\LessonStudentModel;
use App\Models\FixedLessonModel; // Bu modeli ekliyoruz
use App\Models\StudentModel;
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
                // DEĞİŞİKLİK: Başlık artık sadece sayı olacak, örn: "3"
                'title' => $day['lesson_count'], 
                'start' => $day['lesson_date'],
                'url'   => route_to('schedule.daily', $day['lesson_date']),
                // Bu event'in bir "arka plan olayı" olmadığını belirtiyoruz.
                'display' => 'list-item', 
            ];
        }

        return $this->response->setJSON($events);
    }

    /**
     * Günlük program grid sayfasını, yetkilendirme kontrolü yaparak yükler.
     */
    public function dailyGrid($date = null)
    {
        if (is_null($date) || !strtotime($date)) {
            return redirect()->to(route_to('schedule.index'))->with('error', 'Geçersiz tarih.');
        }

        $userModel = new UserModel();
        $loggedInUser = auth()->user();

        $teacherQuery = $userModel
            ->select('users.id, user_profiles.first_name, user_profiles.last_name, user_profiles.profile_photo')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('auth_groups_users.group', 'ogretmen')
            ->where('users.active', 1); // <-- EKLENEN SATIR
 ;

        if ($loggedInUser->inGroup('sekreter') && !$loggedInUser->inGroup('admin', 'mudur')) {
            $assignmentModel = new AssignmentModel();
            $assignedTeacherIds = $assignmentModel->where('manager_user_id', $loggedInUser->id)->findColumn('managed_user_id');

            if (empty($assignedTeacherIds)) {
                $teachers = [];
            } else {
                $teacherQuery->whereIn('users.id', $assignedTeacherIds);
                $teachers = $teacherQuery->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
            }
        } else {
            $teachers = $teacherQuery->orderBy('user_profiles.first_name', 'ASC')->asObject()->findAll();
        }

        $lessonModel = new LessonModel();
        $lessons = $lessonModel
            ->select('lessons.*, GROUP_CONCAT(CONCAT(s.adi, " ", s.soyadi) SEPARATOR ",") as student_names')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id', 'left')
            ->join('students s', 's.id = ls.student_id', 'left')
            ->where('lesson_date', $date)
            ->groupBy('lessons.id')
            ->findAll();

        $lessonMap = [];
        foreach ($lessons as $lesson) {
            $hourKey = date('H', strtotime($lesson['start_time']));
            $lessonMap[$lesson['teacher_id']][$hourKey] = $lesson;
        }

        $data = [
            'title'       => Time::parse($date)->toLocalizedString('d MMMM yyyy') . ' Ders Programı',
            'displayDate' => $date,
            'teachers'    => $teachers,
            'lessonMap'   => $lessonMap,
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
    
    /**
     * Ders detaylarını getirir.
     */
    public function getLessonDetails($lessonId)
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403); }
        $lessonModel = new LessonModel();
        $lesson = $lessonModel
            ->select('lessons.*, GROUP_CONCAT(CONCAT(s.adi, " ", s.soyadi) SEPARATOR ",") as student_names')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id', 'left')
            ->join('students s', 's.id = ls.student_id', 'left')
            ->where('lessons.id', $lessonId)->groupBy('lessons.id')->first();
        return $this->response->setJSON(['success' => (bool)$lesson, 'lesson' => $lesson]);
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

    // 1. Mevcut getStudentSuggestions metodunu bu kodla değiştirin.
    /**
     * AJAX ile çağrılır. Belirli bir öğretmen ve zaman dilimi için
     * akıllı öğrenci öneri listesi döndürür. (Pazartesi Geri Besleme Eklendi)
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getStudentSuggestions()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $teacherId = $this->request->getGet('teacher_id');
        $date = $this->request->getGet('date');
        $startTime = $this->request->getGet('start_time');

        if (empty($teacherId) || empty($date) || empty($startTime)) {
            return $this->response->setStatusCode(400, 'Eksik parametre.');
        }

        $fixedLessonModel = new \App\Models\FixedLessonModel();
        $lessonHistoryModel = new \App\Models\LessonHistoryModel();

        $dayOfWeek = date('N', strtotime($date));
        $suggestions = [];
        $existingStudentIds = [];

        // 1. Katman: Sabit Ders Önerileri
        // YENİ: telafi hakları eklendi
        $fixedStudents = $fixedLessonModel->select('s.id, s.adi, s.soyadi, s.telafi_bireysel_hak, s.telafi_grup_hak')
            ->join('students s', 's.id = fixed_lessons.student_id')
            ->where('fixed_lessons.teacher_id', $teacherId)
            ->where('fixed_lessons.day_of_week', $dayOfWeek)
            ->where('fixed_lessons.start_time', $startTime)
            ->findAll();

        foreach ($fixedStudents as $student) {
            // YENİ: telafi hakları yanıta eklendi
            $suggestions[] = [
                'id' => $student['id'], 
                'name' => trim($student['adi'] . ' ' . $student['soyadi']), 
                'type' => 'fixed',
                'bireysel' => $student['telafi_bireysel_hak'],
                'grup' => $student['telafi_grup_hak']
            ];
            $existingStudentIds[] = $student['id'];
        }

        // 2. Katman: Geçmiş Ders Önerileri
        $teacherProfile = model('UserProfileModel')->where('user_id', $teacherId)->first();
        $teacherFullName = $teacherProfile ? trim($teacherProfile->first_name . ' ' . $teacherProfile->last_name) : '';
        
        $historyStudents = [];
        if (!empty($teacherFullName)) {
            // A. Öncelik: Öğretmenin kendi geçmişine bak
            $historyStudents = $lessonHistoryModel
                ->select('student_name, COUNT(id) as lesson_count')
                ->where('LOWER(teacher_name)', strtolower($teacherFullName))
                ->where('start_time', $startTime)
                ->groupBy('student_name')
                ->orderBy('lesson_count', 'DESC')
                ->limit(10)->findAll();
        } 
        
        // B. Geri Besleme: Eğer öğretmenin geçmişi boşsa VE GÜN PAZARTESİ İSE,
        //    genel Pazartesi verilerine bak.
        if (empty($historyStudents) && in_array($dayOfWeek, [1, 2, 3, 4, 5, 6])) {
            $historyStudents = $lessonHistoryModel
                ->select('student_name, COUNT(id) as lesson_count')
                ->where('DAYOFWEEK(lesson_date) = 2') // MySQL'de Pazar=1, Pazartesi=2
                ->where('start_time', $startTime)
                ->groupBy('student_name')
                ->orderBy('lesson_count', 'DESC')
                ->limit(10)->findAll();
        }

        // Bulunan geçmiş verilerini önerilere ekle
        if (!empty($historyStudents)) {
            $this->_addHistorySuggestions($historyStudents, $suggestions, $existingStudentIds);
        }


        // 3. Katman: Diğer Tüm Öğrenciler
        $studentModel = new \App\Models\StudentModel();
        // YENİ: telafi hakları eklendi
        $otherStudentsBuilder = $studentModel->select('id, adi, soyadi, telafi_bireysel_hak, telafi_grup_hak');
        if (!empty($existingStudentIds)) {
            $otherStudentsBuilder->whereNotIn('id', $existingStudentIds);
        }
        $otherStudents = $otherStudentsBuilder->orderBy('adi ASC, soyadi ASC')->findAll();

        foreach ($otherStudents as $student) {
            // YENİ: telafi hakları yanıta eklendi
            $suggestions[] = [
                'id' => $student['id'], 
                'name' => trim($student['adi'] . ' ' . $student['soyadi']), 
                'type' => 'other',
                'bireysel' => $student['telafi_bireysel_hak'],
                'grup' => $student['telafi_grup_hak']
            ];
        }

        return $this->response->setJSON($suggestions);
    }

    // 2. Bu yeni yardımcı metodu Controller dosyanızın SONUNA ekleyin.
    /**
     * Geçmiş verilerinden gelen öğrenci isimlerini bulur ve ana öneri listesine ekler.
     * @param array $historyStudents
     * @param array $suggestions
     * @param array $existingStudentIds
     */
    private function _addHistorySuggestions(array $historyStudents, array &$suggestions, array &$existingStudentIds)
    {
        $studentModel = new \App\Models\StudentModel();
        $studentNames = array_column($historyStudents, 'student_name');

        if (empty($studentNames)) {
            return;
        }

        // YENİ: telafi hakları eklendi
        $builder = $studentModel->select('id, adi, soyadi, telafi_bireysel_hak, telafi_grup_hak');
        $builder->groupStart();
        foreach ($studentNames as $name) {
            $builder->orWhere('LOWER(CONCAT(adi, " ", soyadi))', strtolower(trim($name)));
        }
        $builder->groupEnd();
        $studentDetails = $builder->findAll();

        foreach ($studentDetails as $student) {
            if (!in_array($student['id'], $existingStudentIds)) {
                // YENİ: telafi hakları yanıta eklendi
                $suggestions[] = [
                    'id' => $student['id'], 
                    'name' => trim($student['adi'] . ' ' . $student['soyadi']), 
                    'type' => 'history',
                    'bireysel' => $student['telafi_bireysel_hak'],
                    'grup' => $student['telafi_grup_hak']
                ];
                $existingStudentIds[] = $student['id'];
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