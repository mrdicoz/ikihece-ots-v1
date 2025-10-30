<?php

namespace App\Controllers\AI;

use App\Models\StudentModel;
use App\Models\LessonModel;
use App\Models\UserProfileModel;
use App\Models\InstitutionModel;
use App\Models\StudentEvaluationModel;
use App\Models\FixedLessonModel;
use App\Models\RamReportAnalysisModel;

class AdminAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);

        if ($this->isGreeting($userMessageLower)) {
            return $this->handleGreetingAndPresentMenu();
        }

        // 1. Kurumun Genel Raporu
        if ($this->containsKeywords($userMessageLower, ['kurumun genel raporu', 'genel rapor'])) {
            return $this->handleGeneralInstitutionReport();
        }

        // 2. Öğretmen Detay Raporu
        if (preg_match('/(.+?)\s+öğretmenin.*raporu/i', $userMessage, $matches)) {
            $teacherName = trim($matches[1]);
            return $this->handleTeacherDetailReport($teacherName);
        }

        // 3. Öğrenci Detay Raporu
        if (preg_match('/(.+?)\s+hakkında\s+rapor/i', $userMessage, $matches)) {
            $studentName = trim($matches[1]);
            return $this->handleStudentDetailReport($studentName);
        }

        // 4. Yeni Kayıt Olan Öğrenciler
        if ($this->containsKeywords($userMessageLower, ['yeni kayıt olan öğrenciler', 'yeni öğrenciler'])) {
            return $this->handleNewStudentsList();
        }

        // 5. Kurumdan Ayrılan Öğrenciler
        if ($this->containsKeywords($userMessageLower, ['kurumdan ayrılan öğrenciler', 'ayrılan öğrenciler'])) {
            return $this->handleChurnedStudentsList();
        }

        // 6. Kurumdaki Öğretmenleri Listele
        if ($this->containsKeywords($userMessageLower, ['öğretmenleri listele', 'öğretmenler kimler', 'kurumdaki öğretmenler'])) {
            return $this->handleListTeachers();
        }

        // 7. Öğretmenin Gelişim Günlüklerini Listele
        if (preg_match('/(.+?)\s+yazdığı\s+gelişim\s+günlüklerini\s+listele/i', $userMessage, $matches)) {
            $teacherName = trim($matches[1]);
            return $this->handleTeacherEvaluations($teacherName);
        }

        return "Bu isteği şu anda işleyemiyorum. Lütfen farklı bir şekilde sormayı deneyin.";
    }

    private function isGreeting(string $message): bool
    {
        return $this->fuzzyContainsKeywords($message, ['merhaba', 'selam', 'hey', 'iyi günler', 'kolay gelsin']);
    }

    private function handleGreetingAndPresentMenu(): string
    {
        $response = "Merhaba, ben Pusula. Kurumumuzun stratejik yönetimi için size nasıl yardımcı olabilirim? 🧭\n\n";
        $response .= "Aşağıdaki gibi spesifik raporlar isteyebilirsiniz:\n\n";
        $response .= "1. **`Kurumun genel raporunu oluştur.`**\n";
        $response .= "2. **`{Öğretmen Adı} öğretmenin raporunu oluştur.`**\n";
        $response .= "3. **`{Öğrenci Adı} hakkında rapor sun.`**\n";
        $response .= "4. **`Bu ay yeni kayıt olan öğrencileri listele.`**\n";
        $response .= "5. **`Bu ay kurumdan ayrılan öğrencileri listele.`**\n";
        $response .= "6. **`Kurumdaki öğretmenleri listele.`**\n";
        $response .= "7. **`{Öğretmen Adı} yazdığı gelişim günlüklerini listele.`**\n\n";
        $response .= "Ayrıca veritabanı hakkında `sql` komutuyla doğrudan sorgulama yapabilirsiniz.";
        return $response;
    }

    /**
     * Rapor 1: Kurumun bu ayki genel durum raporu
     */
    private function handleGeneralInstitutionReport(): string
    {
        $firstDay = date('Y-m-01');
        $lastDay = date('Y-m-t');
        $db = db_connect();

        $activeTeachers = $db->table('auth_groups_users')->where('group', 'ogretmen')->countAllResults();

        $activeStudentsCount = $db->table('lesson_students')
            ->select('lesson_students.student_id')
            ->join('lessons', 'lessons.id = lesson_students.lesson_id')
            ->where('lessons.lesson_date >=', $firstDay)
            ->where('lessons.lesson_date <=', $lastDay)
            ->distinct()
            ->get()->getNumRows();

        $newStudentsCount = $db->table('students')->where('created_at >=', $firstDay)->countAllResults();

        $firstDayLastMonth = date('Y-m-01', strtotime('-1 month'));
        $lastDayLastMonth = date('Y-m-t', strtotime('-1 month'));
        $churnedStudentsCountQuery = $db->query(" 
            SELECT COUNT(DISTINCT s.id) as count FROM students s
            WHERE
                (SELECT COUNT(l.id) FROM lessons l JOIN lesson_students ls ON l.id = ls.lesson_id WHERE ls.student_id = s.id AND l.lesson_date BETWEEN '{$firstDayLastMonth}' AND '{$lastDayLastMonth}') > 0
            AND
                (SELECT COUNT(l.id) FROM lessons l JOIN lesson_students ls ON l.id = ls.lesson_id WHERE ls.student_id = s.id AND l.lesson_date BETWEEN '{$firstDay}' AND '{$lastDay}') = 0
        ");
        $churnedRow = $churnedStudentsCountQuery->getRow();
        $churnedStudentsCount = $churnedRow ? $churnedRow->count : 0;

        $topTeacherQuery = $db->table('lessons')
            ->select('teacher_id, CONCAT(p.first_name, " ", p.last_name) as name, COUNT(lessons.id) as count')
            ->join('user_profiles p', 'p.user_id = lessons.teacher_id', 'left')
            ->where('lesson_date >=', $firstDay)->where('lesson_date <=', $lastDay)
            ->groupBy('teacher_id, name')->orderBy('count', 'DESC')->limit(1)->get()->getRow();

        $topStudentQuery = $db->table('lesson_students ls')
            ->select('ls.student_id, s.adi, s.soyadi, COUNT(ls.id) as count')
            ->join('students s', 's.id = ls.student_id')
            ->join('lessons l', 'l.id = ls.lesson_id')
            ->where('l.lesson_date >=', $firstDay)->where('l.lesson_date <=', $lastDay)
            ->groupBy('ls.student_id, s.adi, s.soyadi')->orderBy('count', 'DESC')->limit(1)->get()->getRow();

        $topEvaluatorQuery = $db->table('student_evaluations e')
            ->select('e.teacher_id, CONCAT(p.first_name, " ", p.last_name) as name, COUNT(e.id) as count')
            ->join('user_profiles p', 'p.user_id = e.teacher_id', 'left')
            ->where('e.created_at >=', $firstDay)->where('e.created_at <=', $lastDay . ' 23:59:59')
            ->groupBy('e.teacher_id, name')->orderBy('count', 'DESC')->limit(1)->get()->getRow();

        $report = "**KURUM GENEL RAPORU (" . date('F Y') . ")**\n\n";
        $report .= "- Aktif Öğretmen Sayısı: **{$activeTeachers}**\n";
        $report .= "- Bu Ay Ders Alan Öğrenci Sayısı: **{$activeStudentsCount}**\n";
        $report .= "- Yeni Kayıt Olan Öğrenci Sayısı: **{$newStudentsCount}**\n";
        $report .= "- Kurumdan Ayrılan (Pasif) Öğrenci Sayısı: **{$churnedStudentsCount}**\n\n";
        $report .= "**🏆 AYIN EN'LERİ:**\n";
        $report .= "- En Fazla Ders Veren Öğretmen: **" . ($topTeacherQuery ? $topTeacherQuery->name : 'N/A') . " (" . ($topTeacherQuery ? $topTeacherQuery->count : 0) . " ders)**\n";
        $report .= "- En Fazla Ders Alan Öğrenci: **" . ($topStudentQuery ? $topStudentQuery->adi : 'N/A') . " " . ($topStudentQuery ? $topStudentQuery->soyadi : '') . " (" . ($topStudentQuery ? $topStudentQuery->count : 0) . " ders)**\n";
        $report .= "- En Aktif Gelişim Yazarı: **" . ($topEvaluatorQuery ? $topEvaluatorQuery->name : 'N/A') . " (" . ($topEvaluatorQuery ? $topEvaluatorQuery->count : 0) . " rapor)**\n";

        return $report;
    }

    /**
     * Rapor 2: {ÖGRETMENADI} öğretmenin genel raporu
     */
    private function handleTeacherDetailReport(string $teacherName): string
    {
        $db = db_connect();
        $teacher = $this->findUserByName($teacherName, 'ogretmen');
        if (!$teacher) {
            return "`{$teacherName}` adında bir öğretmen bulunamadı.";
        }

        $firstDay = date('Y-m-01');
        $lastDay = date('Y-m-t');

        $totalLessons = $db->table('lessons')
            ->where('teacher_id', $teacher->id)
            ->where('lesson_date >=', $firstDay)
            ->where('lesson_date <=', $lastDay)
            ->countAllResults();

        $topStudentQuery = $db->table('lesson_students ls')
            ->select('s.adi, s.soyadi, COUNT(ls.id) as count')
            ->join('students s', 's.id = ls.student_id')
            ->join('lessons l', 'l.id = ls.lesson_id')
            ->where('l.teacher_id', $teacher->id)
            ->where('l.lesson_date >=', $firstDay)->where('l.lesson_date <=', $lastDay)
            ->groupBy('s.adi, s.soyadi')->orderBy('count', 'DESC')->limit(1)->get()->getRow();

        $evaluationCount = $db->table('student_evaluations')
            ->where('teacher_id', $teacher->id)
            ->where('created_at >=', $firstDay)
            ->countAllResults();

        $fixedLessonModel = new FixedLessonModel();
        $fixedLessons = $fixedLessonModel->where('teacher_id', $teacher->id)->findAll();
        $schedule = [];
        $allSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        $days = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
        foreach ($days as $dayNum => $dayName) {
            foreach ($allSlots as $slot) {
                $schedule[$dayNum][$slot] = 'BOŞ';
            }
        }
        foreach ($fixedLessons as $lesson) {
            $time = date('H:i', strtotime($lesson['start_time']));
            if (isset($schedule[$lesson['day_of_week']][$time])) {
                $schedule[$lesson['day_of_week']][$time] = 'DOLU';
            }
        }

        $report = "**" . strtoupper($teacherName) . " ÖĞRETMEN RAPORU (" . date('F Y') . ")**\n\n";
        $report .= "- Bu Ay Girdiği Toplam Ders: **{$totalLessons} saat**\n";
        $report .= "- En Çok Ders Yaptığı Öğrenci: **" . ($topStudentQuery ? $topStudentQuery->adi : 'N/A') . " " . ($topStudentQuery ? $topStudentQuery->soyadi : '') . " (" . ($topStudentQuery ? $topStudentQuery->count : 0) . " ders)**\n";
        $report .= "- Yazdığı Gelişim Raporu Sayısı: **{$evaluationCount} adet** " . ($evaluationCount > 0 ? '✅' : '❌') . "\n\n";
        $report .= "**HAFTALIK SABİT PROGRAM BOŞLUKLARI:**\n";
        foreach ($days as $dayNum => $dayName) {
            $freeSlots = array_keys($schedule[$dayNum], 'BOŞ');
            if (!empty($freeSlots)) {
                $report .= "- **{$dayName}:** " . implode(', ', $freeSlots) . "\n";
            }
        }

        return $report;
    }

    /**
     * Rapor 3: {ÖĞRENCİADI} öğrenci hakkında rapor
     */
    private function handleStudentDetailReport(string $studentName): string
    {
        $student = $this->findStudentByName($studentName);
        if (!$student) {
            return "`{$studentName}` adında bir öğrenci bulunamadı.";
        }

        $db = db_connect();

        $fixedLessons = $db->table('fixed_lessons f')
            ->select('f.day_of_week, f.start_time, CONCAT(p.first_name, " ", p.last_name) as teacher_name')
            ->join('user_profiles p', 'p.user_id = f.teacher_id', 'left')
            ->where('f.student_id', $student['id'])->orderBy('f.day_of_week')->get()->getResultArray();
        
        $evaluations = $db->table('student_evaluations')
            ->select('evaluation, teacher_snapshot_name, created_at')
            ->where('student_id', $student['id'])->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray();

        // RAM Raporu (DÜZELTİLMİŞ TABLO ADI)
        $ramReport = $db->table('ram_report_analysis')->where('student_id', $student['id'])->get()->getRow();

        $report = "**" . strtoupper($student['adi'] . ' ' . $student['soyadi']) . " ÖĞRENCİ RAPORU**\n\n";
        $report .= "**İletişim ve Servis:**\n";
        $report .= "- Veli (Anne): {$student['veli_anne_telefon']}\n";
        $report .= "- Veli (Baba): {$student['veli_baba_telefon']}\n";
        $servisDurumu = isset($student['servis']) ? ucfirst($student['servis']) : 'Belirtilmemiş';
        $report .= "- Servis Durumu: {$servisDurumu}\n\n";

        $report .= "**Ders Programı ve Öğretmenler:**\n";
        if (!empty($fixedLessons)) {
            $days = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
            foreach ($fixedLessons as $lesson) {
                $report .= "- **{$days[$lesson['day_of_week']]}, {$lesson['start_time']}**: {$lesson['teacher_name']}\n";
            }
        } else {
            $report .= "- Sabit ders programı bulunmuyor.\n";
        }
        $report .= "\n";

        $report .= "**Gelişim Günlüğü Özeti (Son 5 Rapor):**\n";
        if (!empty($evaluations)) {
            foreach ($evaluations as $eval) {
                $report .= "- **{$eval['teacher_snapshot_name']}** ({$eval['created_at']}): *{$eval['evaluation']}*\n";
            }
        } else {
            $report .= "- Henüz gelişim raporu girilmemiş.\n";
        }
        $report .= "\n";

        $report .= "**RAM Raporu Analizi:**\n";
        if ($ramReport && !empty($ramReport->summary)) {
            $report .= "- **Özet:** {$ramReport->summary}\n";
        } else {
            $report .= "- Öğrenci için RAM raporu analizi bulunmuyor.\n";
        }

        return $report;
    }

    /**
     * Rapor 4: Bu ay yeni kayıt olan öğrenciler
     */
    private function handleNewStudentsList(): string
    {
        $firstDay = date('Y-m-01');
        $newStudents = (new StudentModel())->where('created_at >=', $firstDay)->findAll();

        if (empty($newStudents)) {
            return "Bu ay henüz yeni öğrenci kaydı yapılmadı.";
        }

        $report = "**BU AY YENİ KAYIT OLAN ÖĞRENCİLER (" . date('F Y') . ")**\n\n";
        foreach ($newStudents as $student) {
            $report .= "- **{$student['adi']} {$student['soyadi']}** (Kayıt Tarihi: " . date('d.m.Y', strtotime($student['created_at'])) . ")\n";
        }
        return $report;
    }

    /**
     * Rapor 5: Bu ay kurumdan ayrılan öğrenciler
     */
    private function handleChurnedStudentsList(): string
    {
        $firstDay = date('Y-m-01');
        $lastDay = date('Y-m-t');
        $firstDayLastMonth = date('Y-m-01', strtotime('-1 month'));
        $lastDayLastMonth = date('Y-m-t', strtotime('-1 month'));
        $db = db_connect();

        // 1. Soft-deleted students this month
        $softDeletedStudents = $db->table('students')
            ->where('deleted_at >=', $firstDay . ' 00:00:00')
            ->where('deleted_at <=', $lastDay . ' 23:59:59')
            ->get()->getResultArray();

        // 2. Inactive students (active last month, not this month)
        $inactiveStudentsQuery = $db->query(" 
            SELECT s.adi, s.soyadi FROM students s
            WHERE s.deleted_at IS NULL AND
                (SELECT COUNT(l.id) FROM lessons l JOIN lesson_students ls ON l.id = ls.lesson_id WHERE ls.student_id = s.id AND l.lesson_date BETWEEN '{$firstDayLastMonth}' AND '{$lastDayLastMonth}') > 0
            AND
                (SELECT COUNT(l.id) FROM lessons l JOIN lesson_students ls ON l.id = ls.lesson_id WHERE ls.student_id = s.id AND l.lesson_date BETWEEN '{$firstDay}' AND '{$lastDay}') = 0
        ");
        $inactiveStudents = $inactiveStudentsQuery->getResultArray();

        if (empty($softDeletedStudents) && empty($inactiveStudents)) {
            return "Bu ay kurumdan ilişiği kesilen veya pasif duruma düşen bir öğrenci olmadı.";
        }

        $report = "**BU AY AYRILAN & PASİFLEŞEN ÖĞRENCİLER**\n\n";

        if (!empty($softDeletedStudents)) {
            $report .= "--- **İlişiği Kesilenler (Kaydı Silinenler)** ---\n";
            foreach ($softDeletedStudents as $student) {
                $deletedDate = date('d.m.Y', strtotime($student['deleted_at']));
                $report .= "- {$student['adi']} {$student['soyadi']} (Silinme Tarihi: {$deletedDate})\n";
            }
            $report .= "\n";
        }

        if (!empty($inactiveStudents)) {
            $report .= "--- **Pasifleşenler (Geçen Ay Aktif, Bu Ay Ders Almayan)** ---\n";
            foreach ($inactiveStudents as $student) {
                $report .= "- {$student['adi']} {$student['soyadi']}\n";
            }
            $report .= "\n";
        }

        return $report;
    }

    private function findUserByName(string $name, string $group): ?object
    {
        $db = db_connect();
        $builder = $db->table('users');
        $builder->select('users.id, user_profiles.first_name, user_profiles.last_name');
        $builder->join('user_profiles', 'user_profiles.user_id = users.id');
        $builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $builder->where('auth_groups_users.group', $group);

        $nameParts = explode(' ', trim($name));
        if (count($nameParts) > 1) {
            $firstName = $nameParts[0];
            $lastName = end($nameParts);
            $builder->where('user_profiles.first_name', $firstName);
            $builder->where('user_profiles.last_name', $lastName);
        } else {
            $builder->groupStart();
            $builder->like('user_profiles.first_name', $name);
            $builder->orLike('user_profiles.last_name', $name);
            $builder->groupEnd();
        }

        return $builder->get()->getRow();
    }

    private function findStudentByName(string $name): ?array
    {
        $db = db_connect();
        $builder = $db->table('students');

        $nameParts = explode(' ', trim($name));
        if (count($nameParts) > 1) {
            $firstName = $nameParts[0];
            $lastName = end($nameParts);
            $builder->where('adi', $firstName);
            $builder->where('soyadi', $lastName);
        } else {
            $builder->groupStart();
            $builder->like('adi', $name);
            $builder->orLike('soyadi', $name);
            $builder->groupEnd();
        }

        return $builder->get()->getRowArray();
    }

    private function handleListTeachers(): string
    {
        $db = db_connect();
        $teachers = $db->table('users')
            ->select('user_profiles.first_name, user_profiles.last_name')
            ->join('user_profiles', 'user_profiles.user_id = users.id')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->where('auth_groups_users.group', 'ogretmen')
            ->get()->getResultArray();

        if (empty($teachers)) {
            return "Kurumda kayıtlı öğretmen bulunmamaktadır.";
        }

        $report = "**KURUMDAKİ ÖĞRETMENLER**\n\n";
        foreach ($teachers as $teacher) {
            $report .= "- {$teacher['first_name']} {$teacher['last_name']}\n";
        }

        return $report;
    }

    private function handleTeacherEvaluations(string $teacherName): string
    {
        $teacher = $this->findUserByName($teacherName, 'ogretmen');
        if (!$teacher) {
            return "`{$teacherName}` adında bir öğretmen bulunamadı.";
        }

        $db = db_connect();
        $evaluations = $db->table('student_evaluations')
            ->select('students.adi, students.soyadi, COUNT(student_evaluations.id) as count')
            ->join('students', 'students.id = student_evaluations.student_id')
            ->where('student_evaluations.teacher_id', $teacher->id)
            ->groupBy('students.adi, students.soyadi')
            ->orderBy('count', 'DESC')
            ->get()
            ->getResultArray();

        if (empty($evaluations)) {
            return "`{$teacherName}` adlı öğretmen henüz hiç gelişim günlüğü yazmamış.";
        }

        $report = "**" . strtoupper($teacherName) . " TARAFINDAN YAZILAN GELİŞİM GÜNLÜKLERİ**\n\n";
        $report .= "Öğretmen, aşağıdaki öğrencilere toplam " . count($evaluations) . " farklı öğrenci için gelişim günlüğü yazmıştır:\n\n";
        foreach ($evaluations as $evaluation) {
            $report .= "- **{$evaluation['adi']} {$evaluation['soyadi']}** ({$evaluation['count']} adet)\n";
        }

        return $report;
    }
}