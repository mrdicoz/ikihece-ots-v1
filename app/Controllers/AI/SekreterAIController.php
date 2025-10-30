<?php

namespace App\Controllers\AI;

use App\Models\LessonModel;
use App\Models\FixedLessonModel;
use App\Models\StudentModel;
use App\Models\UserProfileModel;
use App\Models\AuthGroupsUsersModel;
use App\Models\LessonHistoryModel;

class SekreterAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);

        // 1. Selamlama ve yönlendirme
        if ($this->containsKeywords($userMessageLower, ['selam', 'merhaba', 'günaydın', 'iyi günler', 'kolay gelsin'])) {
            return "Merhaba! Size nasıl yardımcı olabilirim? İşte deneyebileceğiniz bazı yeni komutlar:\n\n" .
                   "• **Öğretmen Raporu:** `Ahmet öğretmen raporunu oluştur`\n" .
                   "• **Sabit Ders Devamsızlık:** `Ahmet Hoca için sabit ders devamsızlık listesi`\n" .
                   "• **Telafi Hakkı Listesi:** `Telafi ders hakkı 10'dan fazla olanları göster`\n" .
                   "• **Akıllı Program Önerisi:** `Ayşe hoca için yarınki ders programını öner`";
        }

        // 2. Doğrudan Raporlama Fonksiyonları (AI'a gitmeden önce)
        if ($this->containsKeywords($userMessageLower, ['raporunu oluştur', 'öğretmen raporu'])) {
            if ($targetUserId = $this->findSystemUserIdInMessage($userMessageLower)) {
                return $this->buildTeacherPerformanceReportContext($targetUserId);
            } else {
                return "Rapor oluşturmak için bir öğretmen ismi belirtmelisiniz. Örneğin: `Berfin öğretmen raporunu oluştur`";
            }
        } 

        // YENİ HALİ
        if ($this->containsKeywords($userMessageLower, ['sabit derse gelmeyen', 'sabit gününde gelmeyen', 'sabit devamsızlık', 'sabit ders devamsızlık'])) {

            if ($targetUserId = $this->findSystemUserIdInMessage($userMessageLower)) {
                return $this->buildFixedLessonAbsenceContext($targetUserId);
            } else {
                return "Devamsızlık raporu için lütfen bir öğretmen adı belirtin. Örneğin: `Ahmet öğretmenin bu ayki sabit ders devamsızlıklarını listele`.\n\n";
            }
        }

        // 3. AI için Bağlam Oluşturma
        $context = "";
        $isHandled = false;
        $context .= "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context, $user, 'Sekreter');
        $this->buildInstitutionContext($context);

        if ($this->containsKeywords($userMessageLower, ['telafi ders hakkı', 'telafisi çok olan', 'telafi hakkı 10' ] )) {
            $this->buildHighMakeUpLessonStudentsContext($context);
            $isHandled = true;
        } elseif (!$isHandled) {
            if ($this->containsKeywords($userMessageLower, ['boşluk', 'boş saat', 'müsait', 'öneri', 'öner', 'tavsiye', 'program', 'ders programı öner'])) {
                $this->buildAvailabilityWithHistoryContext($context, $userMessageLower);
            } elseif ($this->containsKeywords($userMessageLower, ['ders hakkı', 'bitiyor', 'azalan', 'kalan hak'])) {
                $this->buildLowEntitlementStudentsContext($context);
            } elseif ($this->containsKeywords($userMessageLower, ['hiç ders almayan', 'ders almayan', 'dersi yok'])) {
                $this->buildStudentsWithoutLessonsContext($context);
            }
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = "Sen İkihece Özel Eğitim Kurumu'nun AI asistanısın.\n\n**Şu an bir SEKRETER ile konuşuyorsun.**\n\nGörevin ders programını optimize etmek ve operasyonel verimliliği artırmak.\n\nProfesyonel, hızlı ve çözüm odaklı ol.";
        
        $userPrompt = $context . "\n\nSekreterin Sorusu: '{$userMessage}'";
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    /**
     * Belirli bir öğretmen için detaylı performans ve program raporu oluşturur.
     */
    private function buildTeacherPerformanceReportContext(int $teacherId): string
    {
        $userProfileModel = model(UserProfileModel::class);
        $fixedLessonModel = model(FixedLessonModel::class);
        $lessonModel = model(LessonModel::class);

        $teacherProfile = $userProfileModel->where('user_id', $teacherId)->first();
        if (!$teacherProfile) {
            return "Hata: Öğretmen profili bulunamadı.";
        }
        $teacherName = strtoupper($teacherProfile->first_name . ' ' . $teacherProfile->last_name);

        // --- Performans Metrikleri (Bu Ay) ---
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        $totalLessonsThisMonth = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('lesson_date >=', $startOfMonth)
            ->where('lesson_date <=', $endOfMonth)
            ->countAllResults();

        $activeStudentsThisMonth = $lessonModel
            ->select('COUNT(DISTINCT student_id) as count')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->where('teacher_id', $teacherId)
            ->where('lesson_date >=', $startOfMonth)
            ->where('lesson_date <=', $endOfMonth)
            ->first()['count'] ?? 0;

        $workDays = 0;
        $current = strtotime($startOfMonth);
        $end = strtotime($endOfMonth);
        while ($current <= $end) {
            $dayOfWeek = date('N', $current);
            if ($dayOfWeek >= 1 && $dayOfWeek <= 6) { // Pzt-Cmt
                $workDays++;
            }
            $current = strtotime('+1 day', $current);
        }
        $totalSlots = $workDays * 8; // Günde 8 saat
        $occupancyRate = $totalSlots > 0 ? round(($totalLessonsThisMonth / $totalSlots) * 100) : 0;

        // --- Sabit Ders Programı Analizi ---
        $allSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        $weekDays = range(1, 7); // Pazartesi - Pazar
        $schedule = [];

        $fixedLessons = $fixedLessonModel
            ->select('fixed_lessons.*, students.adi, students.soyadi')
            ->join('students', 'students.id = fixed_lessons.student_id')
            ->where('fixed_lessons.teacher_id', $teacherId)
            ->findAll();

        $fixedStudentIds = array_unique(array_column($fixedLessons, 'student_id'));

        foreach ($weekDays as $day) {
            foreach ($allSlots as $slot) {
                $schedule[$day][$slot] = null;
            }
        }
        
        foreach ($fixedLessons as $lesson) {
            $day = (int)$lesson['day_of_week'];
            $time = date('H:i', strtotime($lesson['start_time']));

            if (array_key_exists($day, $schedule) && array_key_exists($time, $schedule[$day])) {
                $weekType = $lesson['week_type'] ?? 'TÜM';
                $lessonText = $lesson['adi'] . ' ' . $lesson['soyadi'] . ' (Hafta: ' . $weekType . ')';
                
                if ($schedule[$day][$time] === null) {
                    $schedule[$day][$time] = $lessonText;
                } else {
                    $schedule[$day][$time] .= " | " . $lessonText;
                }
            }
        }

        // Raporu oluştur
        $report = "**{$teacherName} ÖĞRETMEN RAPORU**\n\n";
        $report .= "**📊 Performans (Bu Ay: " . date('F Y') . ")**\n";
        $report .= "- Aktif Öğrenci Sayısı: **{$activeStudentsThisMonth}**\n";
        $report .= "- Toplam Ders Saati: **{$totalLessonsThisMonth} saat**\n";
        $report .= "- Aylık Doluluk Oranı: **%{$occupancyRate}**\n\n";
        
        $report .= "**🗓️ Haftalık Sabit Ders Programı**\n";
        $report .= "- Toplam Sabit Öğrenci Sayısı: **" . count($fixedStudentIds) . "**\n\n";

        $hasAnyFixedLesson = false;
        foreach ($schedule as $day => $slots) {
            $dayHasLessons = false;
            foreach ($slots as $studentName) {
                if ($studentName !== null) {
                    $dayHasLessons = true;
                    $hasAnyFixedLesson = true;
                    break;
                }
            }

            if ($dayHasLessons) {
                $report .= "**" . $this->getDayName($day) . "**\n";
                foreach ($slots as $time => $studentName) {
                    if ($studentName) {
                        $report .= "  - `{$time}`: {$studentName}\n";
                    }
                }
                $report .= "\n";
            }
        }

        if (!$hasAnyFixedLesson) {
            $report .= "Bu öğretmene ait herhangi bir sabit ders programı bulunamadı.\n";
        }

        return $report;
    }

    /**
     * Telafi ders hakkı 10'dan fazla olan öğrencileri listeler.
     */
    private function buildHighMakeUpLessonStudentsContext(string & $context): void
    {
        $studentModel = model(StudentModel::class);
        $highMakeUp = $studentModel
            ->select('id, adi, soyadi, veli_anne_telefon, veli_baba_telefon, (telafi_bireysel_hak + telafi_grup_hak) as toplam_telafi_hak')
            ->having('toplam_telafi_hak >', 10)
            ->orderBy('toplam_telafi_hak', 'DESC')
            ->findAll();

        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "TELAFİ DERS HAKKI 10 SAATTEN FAZLA OLAN ÖĞRENCİLER\n";
        $context .= str_repeat("=", 70) . "\n\n";

        if (!empty($highMakeUp)) {
            $context .= "Bu öğrencilerin biriken telafi haklarını kullanmaları için programlarına ders eklenmesi önerilir.\n\n";
            foreach ($highMakeUp as $student) {
                $context .= "• **{$student['adi']} {$student['soyadi']}**: {$student['toplam_telafi_hak']} saat telafi hakkı\n";
                $context .= "  Tel: Anne: {$student['veli_anne_telefon']}, Baba: {$student['veli_baba_telefon']}\n\n";
            }
        } else {
            $context .= "✔️ Şu anda telafi ders hakkı 10 saatten fazla olan öğrenci bulunmamaktadır.\n";
        }
    }

    /**
     * Bu ay sabit ders gününde ders almamış öğrencileri ve telafi durumlarını listeleyen bir rapor oluşturur.
     */
    private function buildFixedLessonAbsenceContext(int $teacherId): string
    {
        $fixedLessonModel = model(FixedLessonModel::class);
        $lessonModel = model(LessonModel::class);
        $userProfileModel = model(UserProfileModel::class);

        $teacherProfile = $userProfileModel->where('user_id', $teacherId)->first();
        if (!$teacherProfile) {
            return "Hata: Öğretmen profili bulunamadı.";
        }
        $teacherName = $teacherProfile->first_name . ' ' . $teacherProfile->last_name;

        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        // 1. Adım: Gerekli tüm verileri öğretmene göre filtrelenmiş olarak çek.
        $allFixedLessons = $fixedLessonModel
            ->select('fixed_lessons.*, s.adi, s.soyadi, s.veli_anne_telefon, s.veli_baba_telefon')
            ->join('students s', 's.id = fixed_lessons.student_id')
            ->where('fixed_lessons.teacher_id', $teacherId)
            ->findAll();

        // O öğretmene ait bu ay yapılan tüm gerçek dersler
        $lessonsThisMonthRaw = $lessonModel
            ->select('ls.student_id, lessons.lesson_date, lessons.start_time')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
            ->where('lessons.teacher_id', $teacherId)
            ->where('lessons.lesson_date >=', $startOfMonth)
            ->where('lessons.lesson_date <=', $endOfMonth)
            ->findAll();
        
        $actualLessonsMap = [];
        foreach ($lessonsThisMonthRaw as $lesson) {
            $timeKey = date('H:i:s', strtotime($lesson['start_time']));
            $actualLessonsMap[$lesson['student_id']][$lesson['lesson_date']][$timeKey] = true;
        }
        
        $lessonsByWeek = [];
        foreach ($lessonsThisMonthRaw as $lesson) {
            $weekNumber = date('W', strtotime($lesson['lesson_date']));
            $lessonsByWeek[$lesson['student_id']][(int)$weekNumber][] = $lesson['lesson_date'];
        }

        // 2. Adım: Devamsızlıkları tespit et.
        $absences = [];
        $currentDate = new \DateTime($startOfMonth);
        $endDate = new \DateTime($endOfMonth);

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->format('N');
            $weekOfYear = (int)$currentDate->format('W');

            foreach ($allFixedLessons as $fixed) {
                if ((int)$fixed['day_of_week'] !== (int)$dayOfWeek) continue;

                $weekType = strtolower($fixed['week_type'] ?? 'tum');
                if (($weekType === 'tek' && $weekOfYear % 2 === 0) || ($weekType === 'cift' && $weekOfYear % 2 !== 0)) {
                    continue;
                }

                $startTimeKey = date('H:i:s', strtotime($fixed['start_time']));
                if (!isset($actualLessonsMap[$fixed['student_id']][$dateString][$startTimeKey])) {
                    if (!isset($absences[$fixed['student_id']])) {
                        $absences[$fixed['student_id']] = [
                            'student_info' => $fixed,
                            'missed_lessons' => []
                        ];
                    }
                    $absences[$fixed['student_id']]['missed_lessons'][] = [
                        'date' => $dateString,
                        'time' => $fixed['start_time']
                    ];
                }
            }
            $currentDate->modify('+1 day');
        }

        // 3. Adım: Raporu oluştur.
        $report = "**" . strtoupper($teacherName) . " - SABİT DERS DEVAMSIZLIK RAPORU** (" . date('F Y') . ")\n\n";

        if (!empty($absences)) {
            $report .= "Aşağıdaki öğrenciler bu ay sabit derslerine katılmamıştır. Telafi durumları belirtilmiştir.\n\n";
            
            foreach ($absences as $studentId => $data) {
                $student = $data['student_info'];
                $report .= "--- \n";
                $report .= "**Öğrenci:** {$student['adi']} {$student['soyadi']}\n";
                $report .= "**Veli Tel:** Anne: {$student['veli_anne_telefon']} | Baba: {$student['veli_baba_telefon']}\n\n";

                foreach ($data['missed_lessons'] as $missed) {
                    $missedDate = $missed['date'];
                    $missedTime = date('H:i', strtotime($missed['time']));
                    $missedWeek = (int)date('W', strtotime($missedDate));
                    $dayName = $this->getDayName(date('N', strtotime($missedDate)));

                    $report .= "- **Devamsızlık:** {$missedDate} ({$dayName}) {$missedTime}\n";

                    $makeUpLessonFound = null;
                    if (isset($lessonsByWeek[$studentId][$missedWeek])) {
                        foreach ($lessonsByWeek[$studentId][$missedWeek] as $attendedDate) {
                            if ($attendedDate !== $missedDate) {
                                $makeUpLessonFound = $attendedDate;
                                break;
                            }
                        }
                    }

                    if ($makeUpLessonFound) {
                        $makeUpDayName = $this->getDayName(date('N', strtotime($makeUpLessonFound)));
                        $report .= "  └ **Telafi:** Bu devamsızlık yerine **{$makeUpLessonFound} ({$makeUpDayName})** tarihinde derse katıldı.\n\n";
                    } else {
                        $report .= "  └ **Telafi:** Bu ders için hafta içinde telafi dersi yapılmadı.\n\n";
                    }
                }
            }
        } else {
            $report .= "✔️ Harika! {$teacherName} adlı öğretmenin tüm öğrencileri bu ayki sabit derslerine tam katılım göstermiştir.\n";
        }
        return $report;
    }
    
    /**
     * Geçmiş verilerle desteklenmiş müsaitlik analizi
     */
    private function buildAvailabilityWithHistoryContext(string & $context, string $userMessageLower): void
    {
        $targetDate = $this->extractDateFromMessage($userMessageLower);
        $dayOfWeek = date('N', strtotime($targetDate)); // 1=Pazartesi, 7=Pazar
        $targetUserId = $this->findSystemUserIdInMessage($userMessageLower);
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "AKILLI PROGRAM ÖNERİ SİSTEMİ (GEÇMİŞ VERİ ANALİZİ)\n";
        $context .= str_repeat("=", 70) . "\n\n";
        $context .= "Tarih: {$targetDate} (" . $this->getDayName($dayOfWeek) . ")\n\n";

        $lessonModel = model(LessonModel::class);
        $fixedLessonModel = model(FixedLessonModel::class);
        $authGroupsUsers = model(AuthGroupsUsersModel::class);
        $studentModel = model(StudentModel::class);

        // Tüm saatler
        $allSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        
        // Belirli bir öğretmen sorgulandıysa
        if ($targetUserId) {
            $userProfile = model(UserProfileModel::class)->where('user_id', $targetUserId)->first();
            $teacherName = $userProfile ? trim($userProfile->first_name . ' ' . $userProfile->last_name) : 'Bilinmeyen';
            $context .= "İstenen Öğretmen: {$teacherName}\n\n";

            $busySlots = $lessonModel
                ->select('start_time')
                ->where('teacher_id', $targetUserId)
                ->where('lesson_date', $targetDate)
                ->findAll();
            
            $busyTimes = array_column($busySlots, 'start_time');
            $availableSlots = array_diff($allSlots, $busyTimes);

            if (!empty($availableSlots)) {
                $context .= "Müsait Saatler: " . implode(', ', $availableSlots) . "\n\n";
                
                foreach ($availableSlots as $slot) {
                    $context .= str_repeat("-", 60) . "\n";
                    $context .= "SAAT {$slot} İÇİN ÖNERİLER:\n";
                    $context .= str_repeat("-", 60) . "\n\n";
                    
                    // 1. Sabit programda bu saatte dersi olan öğrenciler (EN YÜKSEK ÖNCELİK)
                    $fixedStudents = $fixedLessonModel
                        ->select('students.adi, students.soyadi, students.id, students.veli_anne_telefon, students.veli_baba_telefon,
                                 students.normal_bireysel_hak, students.normal_grup_hak, students.telafi_bireysel_hak, students.telafi_grup_hak')
                        ->join('students', 'students.id = fixed_lessons.student_id')
                        ->where('fixed_lessons.teacher_id', $targetUserId)
                        ->where('fixed_lessons.day_of_week', $dayOfWeek)
                        ->where('fixed_lessons.start_time', $slot)
                        ->findAll();
                    
                    if (!empty($fixedStudents)) {
                        $context .= "🔴 SABİT PROGRAMDA OLAN ÖĞRENCİLER (ÖNCELİKLİ):\n";
                        foreach ($fixedStudents as $fs) {
                            $totalHak = ($fs['normal_bireysel_hak'] ?? 0) + ($fs['normal_grup_hak'] ?? 0) +
                                       ($fs['telafi_bireysel_hak'] ?? 0) + ($fs['telafi_grup_hak'] ?? 0);
                            
                            $context .= "  • {$fs['adi']} {$fs['soyadi']} - Kalan Hak: {$totalHak} saat\n";
                            $context .= "    Tel: Anne: {$fs['veli_anne_telefon']}, Baba: {$fs['veli_baba_telefon']}\n";
                            
                            if ($totalHak < 5) {
                                $context .= "    ⚠️ DERS HAKKI ÇOK DÜŞÜK - ACİL ARAYIN!\n";
                            }
                        }
                        $context .= "\n";
                    }
                    
                    // 2. Geçmişte bu gün ve saatte ders almış öğrenciler
                    $historicalStudents = $this->getHistoricalStudentsForSlot($targetUserId, $dayOfWeek, $slot);
                    
                    if (!empty($historicalStudents)) {
                        $context .= "📊 GEÇMİŞTE BU GÜN/SAATTE DERS ALAN ÖĞRENCİLER:\n";
                        $context .= "(Son 3 ay içinde en az 2 kez bu gün/saatte ders almış)\n\n";
                        
                        foreach ($historicalStudents as $hs) {
                            $student = $studentModel->find($hs['student_id']);
                            if ($student) {
                                $totalHak = ($student['normal_bireysel_hak'] ?? 0) + ($student['normal_grup_hak'] ?? 0) +
                                           ($student['telafi_bireysel_hak'] ?? 0) + ($student['telafi_grup_hak'] ?? 0);
                                
                                $context .= "  • {$student['adi']} {$student['soyadi']} ";
                                $context .= "({$hs['lesson_count']} kez bu gün/saatte ders almış)\n";
                                $context .= "    Kalan Hak: {$totalHak} saat\n";
                                $context .= "    Tel: Anne: {$student['veli_anne_telefon']}, Baba: {$student['veli_baba_telefon']}\n";
                                
                                if ($totalHak < 10) {
                                    $context .= "    ⚠️ Ders hakkı azalıyor\n";
                                }
                                $context .= "\n";
                            }
                        }
                    }
                    
                    // 3. Bu öğretmenle en çok çalışan öğrenciler (ama yukarıdakilerde değilse)
                    $frequentStudents = $lessonModel
                        ->select([
                            'students.id', 'students.adi', 'students.soyadi', 'students.veli_anne_telefon', 'students.veli_baba_telefon',
                            'students.normal_bireysel_hak', 'students.normal_grup_hak', 'students.telafi_bireysel_hak', 'students.telafi_grup_hak',
                            'COUNT(lessons.id) as lesson_count'
                        ])
                        ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
                        ->join('students', 'students.id = ls.student_id')
                        ->where('lessons.teacher_id', $targetUserId)
                        ->where('lessons.lesson_date >=', date('Y-m-d', strtotime('-3 months')))
                        ->groupBy('students.id')
                        ->orderBy('lesson_count', 'DESC')
                        ->limit(5)
                        ->findAll();
                    
                    // Zaten önerilen öğrencileri filtrele
                    $alreadySuggested = array_merge(
                        array_column($fixedStudents, 'id'),
                        array_column($historicalStudents, 'student_id')
                    );
                    
                    $filteredFrequent = array_filter($frequentStudents, function($student) use ($alreadySuggested) {
                        return !in_array($student['id'], $alreadySuggested);
                    });
                    
                    if (!empty($filteredFrequent)) {
                        $context .= "💡 DİĞER UYGUN ÖĞRENCİLER (Bu öğretmenle sık çalışanlar):\n\n";
                        foreach ($filteredFrequent as $fs) {
                            $totalHak = ($fs['normal_bireysel_hak'] ?? 0) + ($fs['normal_grup_hak'] ?? 0) +
                                       ($fs['telafi_bireysel_hak'] ?? 0) + ($fs['telafi_grup_hak'] ?? 0);
                            
                            $context .= "  • {$fs['adi']} {$fs['soyadi']} - {$fs['lesson_count']} ders yapmış\n";
                            $context .= "    Kalan Hak: {$totalHak} saat\n";
                            $context .= "    Tel: Anne: {$fs['veli_anne_telefon']}, Baba: {$fs['veli_baba_telefon']}\n\n";
                        }
                    }
                    
                    $context .= "\n";
                }
            } else {
                $context .= "❌ Bu tarihte tüm saatler dolu.\n\n";
            }
            
        } else {
            // Tüm öğretmenler için genel durum
            $teachers = $authGroupsUsers
                ->select('users.id, user_profiles.first_name, user_profiles.last_name')
                ->join('users', 'users.id = auth_groups_users.user_id')
                ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
                ->where('group', 'ogretmen')
                ->where('users.deleted_at', null)
                ->findAll();

            $context .= "TÜM ÖĞRETMENLER İÇİN MÜSAİTLİK DURUMU:\n\n";
            
            foreach ($teachers as $teacher) {
                $teacherName = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));
                
                $busySlots = $lessonModel
                    ->select('start_time')
                    ->where('teacher_id', $teacher['id'])
                    ->where('lesson_date', $targetDate)
                    ->findAll();
                
                $busyTimes = array_column($busySlots, 'start_time');
                $availableSlots = array_diff($allSlots, $busyTimes);

                if (!empty($availableSlots)) {
                    $context .= "👤 {$teacherName}: " . implode(', ', $availableSlots) . "\n";
                } else {
                    $context .= "👤 {$teacherName}: Tüm saatler dolu\n";
                }
            }
        }
    }
    
    /**
     * Geçmişte belirli gün ve saatte ders alan öğrencileri bulur
     */
    private function getHistoricalStudentsForSlot(int $teacherId, int $dayOfWeek, string $slot): array
    {
        // Gerekli Modeller
        $lessonHistoryModel = model(LessonHistoryModel::class);
        $userProfileModel = model(UserProfileModel::class);
        $studentModel = model(StudentModel::class);

        // Öğretmen ID'sinden ismini bul
        $teacherProfile = $userProfileModel->where('user_id', $teacherId)->first();
        if (!$teacherProfile) {
            return [];
        }
        $teacherName = trim($teacherProfile->first_name . ' ' . $teacherProfile->last_name);

        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));

        // 1. Geçmiş dersleri doğru model ve öğretmen ismi ile sorgula
        $lessons = $lessonHistoryModel
            ->select('student_name, lesson_date')
            ->where('teacher_name', $teacherName)
            ->where('start_time', $slot)
            ->where('lesson_date >=', $threeMonthsAgo)
            ->findAll();

        // 2. Haftanın gününe göre filtrele ve öğrenci İSİMLERİNİ say
        $studentNameCounts = [];
        foreach ($lessons as $lesson) {
            $lessonDayOfWeek = date('N', strtotime($lesson['lesson_date']));
            if ($lessonDayOfWeek == $dayOfWeek) {
                $studentName = $lesson['student_name'];
                if (!isset($studentNameCounts[$studentName])) {
                    $studentNameCounts[$studentName] = 0;
                }
                $studentNameCounts[$studentName]++;
            }
        }

        // 3. En az 2 kez ders alanları filtrele ve sırala
        $frequentStudentNames = [];
        foreach ($studentNameCounts as $studentName => $count) {
            if ($count >= 2) {
                $frequentStudentNames[$studentName] = $count;
            }
        }
        arsort($frequentStudentNames); // Sayıya göre çoktan aza sırala
        $topStudentNames = array_slice($frequentStudentNames, 0, 5, true); // İlk 5'i al

        if (empty($topStudentNames)) {
            return [];
        }

        // 4. Öğrenci isimlerini tekrar student_id'ye çevir
        $studentDetails = $studentModel
            ->select('id, CONCAT(adi, " ", soyadi) as full_name')
            ->whereIn('CONCAT(adi, " ", soyadi)', array_keys($topStudentNames))
            ->findAll();

        $nameToIdMap = array_column($studentDetails, 'id', 'full_name');

        // 5. Sonucu student_id ile oluştur
        $result = [];
        foreach ($topStudentNames as $name => $count) {
            if (isset($nameToIdMap[$name])) {
                $result[] = [
                    'student_id' => $nameToIdMap[$name],
                    'lesson_count' => $count
                ];
            }
        }
        
        return $result;
    }

    
    /**
     * Ders hakkı azalan öğrenciler
     */
    private function buildLowEntitlementStudentsContext(string & $context): void
    {
        $studentModel = model(StudentModel::class);
        
        $lowEntitlements = $studentModel
            ->select('id, adi, soyadi, veli_anne_telefon, veli_baba_telefon,
                     (normal_bireysel_hak + normal_grup_hak + telafi_bireysel_hak + telafi_grup_hak) as toplam_hak')
            ->having('toplam_hak <', 10)
            ->having('toplam_hak >', 0)
            ->orderBy('toplam_hak', 'ASC')
            ->findAll(30);

        if (!empty($lowEntitlements)) {
            $context .= "\n=== DERS HAKKI AZALAN ÖĞRENCİLER (ACİL) ===\n\n";
            foreach ($lowEntitlements as $le) {
                $urgency = $le['toplam_hak'] < 5 ? '🔴 ÇOK ACİL' : '⚠️ ACİL';
                $context .= "{$urgency} {$le['adi']} {$le['soyadi']}: {$le['toplam_hak']} saat kaldı\n";
                $context .= "  Tel: Anne: {$le['veli_anne_telefon']}, Baba: {$le['veli_baba_telefon']}\n\n";
            }
        } else {
            $context .= "\n=== DERS HAKKI AZALAN ÖĞRENCİLER ===\n";
            $context .= "Şu an ders hakkı 10 saatin altında olan öğrenci bulunmamaktadır.\n\n";
        }
    }
    
    /**
     * Bu ay hiç ders almayan öğrenciler
     */
    private function buildStudentsWithoutLessonsContext(string & $context): void
    {
        $currentMonth = date('Y-m');
        $studentModel = model(StudentModel::class);
        $lessonModel = model(LessonModel::class);
        
        $allStudents = $studentModel->select('id, adi, soyadi, veli_anne_telefon, veli_baba_telefon')->findAll();
        $studentsWithoutLessons = [];
        
        foreach ($allStudents as $student) {
            $hasLesson = $lessonModel
                ->where('student_id', $student['id'])
                ->where('lesson_date >=', $currentMonth . '-01')
                ->where('lesson_date <=', date('Y-m-t', strtotime($currentMonth)))
                ->countAllResults();
            
            if ($hasLesson == 0) {
                $studentsWithoutLessons[] = $student;
            }
        }
        
        if (!empty($studentsWithoutLessons)) {
            $context .= "\n=== BU AY HİÇ DERS ALMAYAN ÖĞRENCİLER ===\n\n";
            foreach ($studentsWithoutLessons as $s) {
                $context .= "❌ {$s['adi']} {$s['soyadi']}\n";
                $context .= "   Tel: Anne: {$s['veli_anne_telefon']}, Baba: {$s['veli_baba_telefon']}\n\n";
            }
            $context .= "[NOT: Bu öğrencilerin aileleriyle iletişime geçilmesi önerilir.]\n\n";
        } else {
            $context .= "\n=== BU AY HİÇ DERS ALMAYAN ÖĞRENCİLER ===\n";
            $context .= "Harika! Bu ay tüm öğrenciler en az bir ders almış.\n\n";
        }
    }
    
    /**
     * Gün numarasını Türkçe isme çevirir
     */
    private function getDayName(int $dayOfWeek): string
    {
        $days = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        return $days[$dayOfWeek] ?? 'Bilinmeyen';
    }
}