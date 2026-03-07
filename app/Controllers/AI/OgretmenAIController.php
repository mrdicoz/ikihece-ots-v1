<?php

namespace App\Controllers\AI;

class OgretmenAIController extends BaseAIController
{
    /**
     * Öğretmen mesajını işler ve gerekli verileri toplayarak prompt oluşturur.
     */
    public function process(string $userMessage, object $user, array $history = []): string
    {
        $role = $this->getUserRole($user);
        
        // 1. Öğretmenin Öğrencilerini Getir (İsim eşleşmesi için)
        $studentModel = new \App\Models\StudentModel();
        $myStudents = $studentModel->getStudentsForTeacher($user->id);
        
        $matchedStudent = null;
        $lowerMessage = mb_strtolower($userMessage);
        
        foreach ($myStudents as $student) {
            $fullName = mb_strtolower($student['adi'] . ' ' . $student['soyadi']);
            if (strpos($lowerMessage, $fullName) !== false) {
                $matchedStudent = $student;
                break; // İlk eşleşen öğrenciyi al
            }
        }

        // 2. Sistem Promptunu Hazırla
        $systemPrompt = $this->getSystemPrompt($role, $user, $matchedStudent);

        return $this->aiService->getChatResponse($userMessage, $systemPrompt, $history);
    }

    protected function getSystemPrompt(string $role, object $user, ?array $matchedStudent = null): string
    {
        $context = "";
        $teacherId = $user->id;

        // --- A. EŞLEŞEN ÖĞRENCİ VARSA DETAYLI BİLGİ ---
        if ($matchedStudent) {
            $studentId = $matchedStudent['id'];
            $context .= "\n--- HAKKINDA KONUŞULAN ÖĞRENCİ: {$matchedStudent['adi']} {$matchedStudent['soyadi']} ---\n";
            
            // 1. Genel Bilgiler
            $context .= "- Yaş/Doğum: {$matchedStudent['dogum_tarihi']}\n";
            $context .= "- Tanı/Eğitim Programı: {$matchedStudent['egitim_programi']}\n";

            // 2. Devamsızlık
            $absenceModel = new \App\Models\StudentAbsenceModel();
            $absences = $absenceModel->getAbsencesByMonthYear(date('m'), date('Y'), $studentId);
            $context .= "- Bu Ay Devamsızlık: " . count($absences) . " ders saati.\n";

            // 3. RAM Raporu
            $ramModel = new \App\Models\RamReportAnalysisModel();
            $ramReport = $ramModel->where('student_id', $studentId)->first();
            if ($ramReport) {
                $context .= "- RAM Raporu Durumu: Mevcut.\n";
                $context .= "- RAM Raporu Özeti (Veritabanından): " . mb_substr($ramReport['ram_text_content'] ?? '', 0, 1000) . "...\n";
            } else {
                $context .= "- RAM Raporu Durumu: Sistemde analiz edilmiş bir RAM raporu bulunmuyor.\n";
            }

            // 4. Gelişim Raporu Kontrolü (Tatlı Baskı İçin)
            $evaluationModel = new \App\Models\StudentEvaluationModel();
            $lastEval = $evaluationModel->where('student_id', $studentId)->where('teacher_id', $teacherId)->orderBy('created_at', 'DESC')->first();
            if ($lastEval) {
                $context .= "- Gelişim Raporu: En son " . date('d.m.Y', strtotime($lastEval['created_at'])) . " tarihinde girilmiş.\n";
            } else {
                $context .= "- Gelişim Raporu: UYARI! Bu öğrenci için henüz hiç gelişim raporu girmemişsin.\n";
            }
        }

        // --- B. ÖĞRETMENİN KENDİ İSTATİSTİKLERİ ---
        $context .= "\n--- SENİN (ÖĞRETMEN) İSTATİSTİKLERİN ---\n";
        
        // 1. Ders Saati (Bu Ay)
        $lessonModel = new \App\Models\LessonModel();
        $lessons = $lessonModel->getLessonsForTeacherByMonth($teacherId, date('Y'), date('m')); // Bu metod LessonModel'de yoksa eklenmeli veya benzeri kullanılmalı. 
        // LessonModel::getLessonsForTeacherByWeek var, biz manuel sayalım veya getLessonsForTeacherByDate range ile.
        // Basitlik adına LessonModel'e güvenelim veya range sorgusu yapalım.
        // Şimdilik LessonHistoryModel veya LessonModel üzerinden manuel hesaplayalım:
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        $lessonsThisMonth = $lessonModel->where('teacher_id', $teacherId)
                                        ->where('lesson_date >=', $startOfMonth)
                                        ->where('lesson_date <=', $endOfMonth)
                                        ->findAll();
        
        $totalMinutes = 0;
        $studentCounts = [];
        foreach ($lessonsThisMonth as $l) {
            $start = strtotime($l['start_time']);
            $end = strtotime($l['end_time'] ?? $l['start_time'] . ' +40 minutes');
            $totalMinutes += ($end - $start) / 60;
            
            // En çok ders yapılan öğrenciyi bulmak için (LessonStudents tablosuna bakmak lazım ama burada basitçe geçelim veya join yapalım)
            // Performans için şimdilik detaylı öğrenci analizini atlıyorum, sadece saat veriyorum.
        }
        $hours = floor($totalMinutes / 60);
        $context .= "- Bu Ay Toplam Ders Saati: $hours saat.\n";

        // 2. İzin Durumu
        $leaveModel = new \App\Models\TeacherLeaveModel();
        $leaves = $leaveModel->getLeavesByTeacherId($teacherId);
        if (!empty($leaves)) {
            $context .= "- İzin Kayıtları:\n";
            foreach ($leaves as $leave) {
                $context .= "  * {$leave->start_date} - {$leave->end_date} ({$leave->leave_type})\n";
            }
        } else {
            $context .= "- İzin Kayıtları: Kayıtlı izin bulunmuyor.\n";
        }

        // 3. Sabit Öğrenciler (Basit Liste)
        $fixedLessonModel = new \App\Models\FixedLessonModel();
        // getStructuredSchedule metodunu kullanabiliriz ama parametreleri array istiyor.
        $fixedSchedule = $fixedLessonModel->getStructuredSchedule([$teacherId], [1,2,3,4,5,6,7]);
        $fixedStudents = [];
        foreach ($fixedSchedule as $slot => $weeks) {
            foreach ($weeks as $weekType => $students) {
                foreach ($students as $s) {
                    $fixedStudents[$s['name']] = true;
                }
            }
        }
        $context .= "- Sabit Öğrencilerin: " . implode(', ', array_keys($fixedStudents)) . "\n";

        // 4. Yarınki Ders Programı
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $tomorrowLessons = $lessonModel->getLessonsForTeacherByDate($teacherId, $tomorrow);
        
        if (!empty($tomorrowLessons)) {
            $context .= "\n--- YARINKİ DERS PROGRAMIN ($tomorrow) ---\n";
            foreach ($tomorrowLessons as $lesson) {
                $time = date('H:i', strtotime($lesson['start_time']));
                $studentName = $lesson['adi'] . ' ' . $lesson['soyadi'];
                $context .= "- $time: $studentName\n";
            }
        } else {
            $context .= "\n- Yarın ($tomorrow) için sistemde kayıtlı dersin görünmüyor.\n";
        }


        return "Sen İkihece OTS'nin 'Pusula' adındaki öğretmen asistanısın.
        Şu an bir meslektaşınla (Öğretmen) konuşuyorsun.

        GÖREVİN:
        Öğretmenin sorularına aşağıdaki KURALLARA ve VERİLERE dayanarak cevap vermek.

        MEVCUT VERİLER:
        $context

        KURALLAR VE DAVRANIŞ MODELLERİ:
        1. **BİLİMSEL VE PROFESYONEL:** Alanınla ilgili (Eğitim, Özel Eğitim, Rehabilitasyon) sorularda bilimsel, pedagojik ve mantıklı cevaplar ver. Hurafelerden uzak dur.
        2. **ÖĞRENCİ ANALİZİ:** Eğer öğretmen bir öğrenci adı verdiyse (Yukarıdaki verilerde eşleşen varsa):
           - Önce genel durumunu özetle (Devamsızlık, Tanı).
           - **RAM RAPORU:** Eğer öğretmen RAM raporu detayını sorarsa, yukarıdaki RAM özetini kullanarak detaylı bilgi ver.
           - **ETKİNLİK TAVSİYESİ:** Öğrencinin tanısına ve RAM raporundaki eksiklerine göre somut, uygulanabilir sınıf içi veya bireysel etkinlik önerileri sun.
        3. **TATLI BASKI (GELİŞİM RAPORLARI):** Eğer yukarıdaki verilerde öğrencinin gelişim raporunun girilmediği veya çok eski olduğu yazıyorsa; öğretmene nazikçe ama ısrarla rapor tutmanın önemini hatırlat. (Örn: 'Hocam, Ali'nin gelişimi harika gidiyor ama sisteme not düşmemişsiniz, unutmayalım ki veriler uçmasın :)')
        4. **İSTATİSTİKLER:** Ders saati, izinler vb. sorulduğunda net verilerle cevap ver.
        5. **SABİT ÖĞRENCİLER:** 'Sabitlerim kim?' gibi sorularda listeyi say.

        ÜSLUBUN:
        - Meslektaş dayanışması içinde, samimi ama saygılı.
        - Motive edici.
        - Çözüm odaklı.
        ";
    }
}
