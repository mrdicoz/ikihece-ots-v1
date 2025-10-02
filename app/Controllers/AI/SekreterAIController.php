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
        
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context, $user, 'Sekreter');
        $this->buildInstitutionContext($context);
        
        // Program/müsaitlik sorguları
        if ($this->containsKeywords($userMessageLower, ['boşluk', 'boş saat', 'müsait', 'öneri', 'öner', 'tavsiye', 'program'])) {
            $this->buildAvailabilityWithHistoryContext($context, $userMessageLower);
        }
        
        // Ders hakkı azalan öğrenciler
        if ($this->containsKeywords($userMessageLower, ['ders hakkı', 'bitiyor', 'azalan', 'kalan hak'])) {
            $this->buildLowEntitlementStudentsContext($context);
        }
        
        // Hiç ders almayan öğrenciler
        if ($this->containsKeywords($userMessageLower, ['hiç ders almayan', 'ders almayan', 'dersi yok'])) {
            $this->buildStudentsWithoutLessonsContext($context);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = "Sen İkihece Özel Eğitim Kurumu'nun AI asistanısın.

**Şu an bir SEKRETER ile konuşuyorsun.**

Görevin ders programını optimize etmek ve operasyonel verimliliği artırmak:

**Program Optimizasyonu:**
- Boş saatleri tespit et ve doldurmak için öneriler sun
- Geçmiş verilere bakarak hangi öğrencilerin o gün/saatte ders aldığını analiz et
- Öğretmen-öğrenci uyumunu dikkate al
- Sabit programdaki öğrencilere öncelik ver

**Akıllı Öneri Sistemi:**
- Geçmişte aynı gün/saatte ders alan öğrencileri öner
- Her öğretmenin hangi öğrencilerle daha çok çalıştığını göz önünde bulundur
- Ders hakkı azalan öğrencilere öncelik ver
- Ailelerle iletişim için gerekli telefon numaralarını ekle

**Uyarı Sistemi:**
- Ders hakkı biten/bitmek üzere olan öğrencileri bildir
- Uzun süredir ders almayan öğrencileri tespit et
- Program çakışmalarını önceden fark et

**İletişim:**
- Net ve hızlı bilgi ver
- Actionable (eyleme dönülebilir) öneriler sun
- Veli telefon numaralarını ekle
- Pratik çözümler üret

Profesyonel, hızlı ve çözüm odaklı ol.";
        
        $userPrompt = $context . "\n\nSekreterin Sorusu: '{$userMessage}'";
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }
    
    /**
     * Geçmiş verilerle desteklenmiş müsaitlik analizi
     */
    private function buildAvailabilityWithHistoryContext(string &$context, string $userMessageLower): void
    {
        $targetDate = $this->extractDateFromMessage($userMessageLower);
        $dayOfWeek = date('N', strtotime($targetDate)); // 1=Pazartesi, 7=Pazar
        $targetUserId = $this->findSystemUserIdInMessage($userMessageLower);
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "AKILLI PROGRAM ÖNERİ SİSTEMİ (GEÇMİŞ VERİ ANALİZİ)\n";
        $context .= str_repeat("=", 70) . "\n\n";
        $context .= "Tarih: {$targetDate} (" . $this->getDayName($dayOfWeek) . ")\n\n";

        $lessonModel = new LessonModel();
        $fixedLessonModel = new FixedLessonModel();
        $authGroupsUsers = new AuthGroupsUsersModel();
        $studentModel = new StudentModel();

        // Tüm saatler
        $allSlots = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        
        // Belirli bir öğretmen sorgulandıysa
        if ($targetUserId) {
            $userProfile = (new UserProfileModel())->where('user_id', $targetUserId)->first();
            $teacherName = $userProfile ? trim($userProfile->first_name . ' ' . $userProfile->last_name) : 'Bilinmeyen';
            $context .= "İstenen Öğretmen: {$teacherName}\n\n";

            $busySlots = $lessonModel
                ->select('lesson_time')
                ->where('teacher_id', $targetUserId)
                ->where('lesson_date', $targetDate)
                ->findAll();
            
            $busyTimes = array_column($busySlots, 'lesson_time');
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
                        ->where('fixed_lessons.lesson_time', $slot)
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
                        ->select('students.id, students.adi, students.soyadi, students.veli_anne_telefon, students.veli_baba_telefon,
                                 students.normal_bireysel_hak, students.normal_grup_hak, students.telafi_bireysel_hak, students.telafi_grup_hak,
                                 COUNT(*) as lesson_count')
                        ->join('students', 'students.id = lessons.student_id')
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
                    ->select('lesson_time')
                    ->where('teacher_id', $teacher['id'])
                    ->where('lesson_date', $targetDate)
                    ->findAll();
                
                $busyTimes = array_column($busySlots, 'lesson_time');
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
        $lessonModel = new LessonModel();
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
        
        // Son 3 ay içinde bu gün ve saatte ders almış öğrenciler
        $lessons = $lessonModel
            ->select('student_id, lesson_date, lesson_time')
            ->where('teacher_id', $teacherId)
            ->where('lesson_time', $slot)
            ->where('lesson_date >=', $threeMonthsAgo)
            ->findAll();
        
        // Tarihlerin gününü kontrol et ve say
        $studentCounts = [];
        foreach ($lessons as $lesson) {
            $lessonDayOfWeek = date('N', strtotime($lesson['lesson_date']));
            if ($lessonDayOfWeek == $dayOfWeek) {
                $studentId = $lesson['student_id'];
                if (!isset($studentCounts[$studentId])) {
                    $studentCounts[$studentId] = 0;
                }
                $studentCounts[$studentId]++;
            }
        }
        
        // En az 2 kez bu gün/saatte ders alanları filtrele
        $result = [];
        foreach ($studentCounts as $studentId => $count) {
            if ($count >= 2) {
                $result[] = [
                    'student_id' => $studentId,
                    'lesson_count' => $count
                ];
            }
        }
        
        // Ders sayısına göre sırala
        usort($result, function($a, $b) {
            return $b['lesson_count'] - $a['lesson_count'];
        });
        
        return array_slice($result, 0, 5); // En fazla 5 öğrenci
    }
    
    /**
     * Ders hakkı azalan öğrenciler
     */
    private function buildLowEntitlementStudentsContext(string &$context): void
    {
        $studentModel = new StudentModel();
        
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
    private function buildStudentsWithoutLessonsContext(string &$context): void
    {
        $currentMonth = date('Y-m');
        $studentModel = new StudentModel();
        $lessonModel = new LessonModel();
        
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