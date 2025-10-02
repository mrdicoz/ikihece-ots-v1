<?php

namespace App\Controllers\AI;

use App\Models\StudentModel;
use App\Models\LessonModel;
use App\Models\FixedLessonModel;
use App\Models\UserProfileModel;
use Smalot\PdfParser\Parser;

class OgretmenAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);
        
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context, $user, 'Öğretmen');
        $this->buildInstitutionContext($context);
        
        // Öğrenci adı geçiyorsa yetki kontrolü ve detay bilgi ekle
        $studentId = $this->findStudentIdInMessage($userMessageLower);
        if ($studentId) {
            // Yetki kontrolü - sadece kendi öğrencileri
            if (!(new StudentModel())->isStudentOfTeacher($studentId, $user->id)) {
                return "Sadece kendi ders programınızda kayıtlı öğrenciler hakkında bilgi alabilirsiniz.";
            }
            
            $this->buildTeacherStudentDetailContext($context, $studentId, $user->id);
        }
        
        // Ders programı sorguları
        if ($this->containsKeywords($userMessageLower, ['ders programım', 'derslerim', 'programım', 'takvimim'])) {
            $this->buildLessonScheduleContext($context, $userMessageLower, $user->id);
        }
        
        // Sabit program sorguları
        if ($this->containsKeywords($userMessageLower, ['sabit program', 'haftalık program', 'sabit dersler'])) {
            $this->buildFixedScheduleContext($context, $user->id);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = "Sen İkihece Özel Eğitim Kurumu'nun AI asistanısın.

**Şu an bir ÖĞRETMEN ile konuşuyorsun.**

Görevin öğretmene eğitim sürecinde maksimum destek olmak:

**RAM Raporu Analizi:**
- RAM raporlarını detaylı analiz et
- Öğrencinin güçlü ve zayıf yönlerini belirle
- Öğrenme stiline uygun stratejiler öner
- Dikkat edilmesi gereken özel durumları vurgula

**Eğitim Stratejileri:**
- Öğrencinin seviyesine uygun aktiviteler öner
- Yapılması ve yapılmaması gerekenleri açıkça belirt
- Diğer öğretmenlerin deneyimlerini dikkate al
- Pratik, uygulanabilir çözümler sun

**İletişim Rehberliği:**
- Veli ile iletişim için öneriler sun
- Öğrenci ile etkili iletişim yolları öner
- Motivasyon teknikleri paylaş

**Dikkat Edilmesi Gerekenler:**
- Her öğrenci benzersizdir, genelleme yapma
- Olumlu yaklaşımı ön planda tut
- Öğretmenin gözlemlerine değer ver
- Pratik ve uygulanabilir öneriler sun

Samimi, destekleyici ve profesyonel bir dil kullan. Öğretmeni isimlendirirken 'hocam' veya adını kullan.";
        
        $userPrompt = $context . "\n\nÖğretmenin Sorusu: '{$userMessage}'";
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }
    
    /**
     * Öğretmen için öğrenci detay bilgileri - RAM, dersler, değerlendirmeler
     */
    private function buildTeacherStudentDetailContext(string &$context, int $studentId, int $teacherId): void
    {
        $studentModel = new StudentModel();
        $student = $studentModel->find($studentId);
        
        if (!$student) {
            $context .= "\n=== ÖĞRENCİ DETAY ===\nÖğrenci bulunamadı.\n";
            return;
        }

        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "ÖĞRENCİ DETAYLI DOSYA\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        $context .= "Öğrenci: {$student['adi']} {$student['soyadi']}\n";
        $context .= "Doğum Tarihi: {$student['dogum_tarihi']}\n";
        
        // Eğitim programları
        if (!empty($student['egitim_programi'])) {
            $programs = is_string($student['egitim_programi']) 
                ? json_decode($student['egitim_programi'], true) 
                : $student['egitim_programi'];
            
            if (is_array($programs)) {
                $context .= "\nEğitim Programları:\n";
                foreach ($programs as $prog) {
                    $context .= "  ✓ {$prog}\n";
                }
            }
        }
        
        // Kalan ders hakları
        $context .= "\n--- KALAN DERS HAKLARI ---\n";
        $context .= "Normal Bireysel: " . ($student['normal_bireysel_hak'] ?? 0) . " saat\n";
        $context .= "Normal Grup: " . ($student['normal_grup_hak'] ?? 0) . " saat\n";
        $context .= "Telafi Bireysel: " . ($student['telafi_bireysel_hak'] ?? 0) . " saat\n";
        $context .= "Telafi Grup: " . ($student['telafi_grup_hak'] ?? 0) . " saat\n";
        
        $totalHak = ($student['normal_bireysel_hak'] ?? 0) + ($student['normal_grup_hak'] ?? 0) + 
                    ($student['telafi_bireysel_hak'] ?? 0) + ($student['telafi_grup_hak'] ?? 0);
        
        if ($totalHak < 10) {
            $context .= "\n⚠️ DİKKAT: Öğrencinin ders hakkı azalmış durumda! Veli ile iletişime geçilmesi önerilir.\n";
        }
        
        // Veli İletişim Bilgileri
        $context .= "\n--- VELİ İLETİŞİM BİLGİLERİ ---\n";
        if (!empty($student['veli_anne_adi_soyadi'])) {
            $context .= "Anne: {$student['veli_anne_adi_soyadi']}\n";
            $context .= "Anne Telefon: {$student['veli_anne_telefon']}\n";
        }
        if (!empty($student['veli_baba_adi_soyadi'])) {
            $context .= "Baba: {$student['veli_baba_adi_soyadi']}\n";
            $context .= "Baba Telefon: {$student['veli_baba_telefon']}\n";
        }

        // RAM DosyasÄ± Analizi - ÖĞRETMENLERİN EN ÖNEMLİ İHTİYACI
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "RAM RAPORU ANALİZİ (KRİTİK ÖNEM)\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        if (!empty($student['ram_raporu'])) {
            $ramPath = WRITEPATH . 'uploads/ram_reports/' . $student['ram_raporu'];
            
            if (file_exists($ramPath)) {
                $ramContent = $this->readPdfContent($ramPath);
                if (!empty(trim($ramContent))) {
                    // RAM içeriğini daha detaylı şekilde ekle (öğretmenler için çok önemli)
                    $ramSummary = mb_substr($ramContent, 0, 3000); // Daha uzun özet
                    $context .= "RAM Raporu İçeriği:\n\n";
                    $context .= $ramSummary;
                    
                    if (mb_strlen($ramContent) > 3000) {
                        $context .= "\n\n[NOT: RAM raporu daha fazla içerik barındırıyor. Yukarıdaki özet öğrencinin temel profilini yansıtmaktadır.]\n";
                    }
                    
                    $context .= "\n\n[ÖĞRETMENİMİZ İÇİN TAVSİYELER]\n";
                    $context .= "Bu RAM raporuna göre:\n";
                    $context .= "- Öğrencinin güçlü yanlarını destekleyin\n";
                    $context .= "- Zayıf alanlarda sabırlı ve teşvik edici olun\n";
                    $context .= "- Özel ihtiyaçlara dikkat edin\n";
                    $context .= "- Ailesiyle düzenli iletişim kurun\n\n";
                    
                } else {
                    $context .= "⚠️ RAM dosyası okunamadı. Dosya bozuk veya sadece görsel içeriyor olabilir.\n";
                    $context .= "Lütfen idare ile iletişime geçerek RAM raporunun yeniden yüklenmesini talep edin.\n\n";
                }
            } else {
                $context .= "⚠️ RAM dosyası sunucuda bulunamadı.\n";
                $context .= "Dosya yolu: {$student['ram_raporu']}\n";
                $context .= "Lütfen teknik destek ile iletişime geçin.\n\n";
            }
        } else {
            $context .= "⚠️ Bu öğrenci için henüz RAM raporu yüklenmemiş.\n";
            $context .= "RAM raporu olmadan öğrenciye optimal eğitim vermek zorlaşabilir.\n";
            $context .= "Lütfen yönetim ile iletişime geçerek RAM raporunun yüklenmesini talep edin.\n\n";
        }

        // Bu öğrenciyle yapılan son dersler (öğretmenin kendi dersleri)
        $lessonModel = new LessonModel();
        $myLessons = $lessonModel
            ->select('lessons.*')
            ->where('lessons.student_id', $studentId)
            ->where('lessons.teacher_id', $teacherId)
            ->orderBy('lessons.lesson_date', 'DESC')
            ->orderBy('lessons.lesson_time', 'DESC')
            ->findAll(10);

        if (!empty($myLessons)) {
            $context .= "\n--- SİZİN BU ÖĞRENCİYLE YAPTIĞINIZ SON DERSLER ---\n\n";
            foreach ($myLessons as $lesson) {
                $context .= "📅 {$lesson['lesson_date']} {$lesson['lesson_time']}";
                if (!empty($lesson['lesson_type'])) {
                    $context .= " [{$lesson['lesson_type']}]";
                }
                $context .= "\n";
                
                if (!empty($lesson['notes'])) {
                    $context .= "   📝 Notlarınız: {$lesson['notes']}\n";
                }
                $context .= "\n";
            }
        } else {
            $context .= "\n--- SİZİN BU ÖĞRENCİYLE YAPTIĞINIZ DERSLER ---\n";
            $context .= "Henüz bu öğrenci ile ders yapmamışsınız veya not girişi yapmamışsınız.\n\n";
        }

        // Diğer öğretmenlerin değerlendirmeleri - ÇOK ÖNEMLİ
        $otherTeacherLessons = $lessonModel
            ->select('lessons.notes, lessons.lesson_date, lessons.lesson_type, users.username, user_profiles.first_name, user_profiles.last_name')
            ->join('users', 'users.id = lessons.teacher_id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('lessons.student_id', $studentId)
            ->where('lessons.teacher_id !=', $teacherId)
            ->where('lessons.notes IS NOT NULL')
            ->where('lessons.notes !=', '')
            ->orderBy('lessons.lesson_date', 'DESC')
            ->findAll(15);

        if (!empty($otherTeacherLessons)) {
            $context .= "\n" . str_repeat("=", 70) . "\n";
            $context .= "DİĞER ÖĞRETMENLERİN DEĞERLENDİRMELERİ VE DENEYİMLERİ\n";
            $context .= str_repeat("=", 70) . "\n\n";
            $context .= "[NOT: Meslektaşlarınızın deneyimleri size yol gösterebilir]\n\n";
            
            foreach ($otherTeacherLessons as $tLesson) {
                $teacherName = trim(($tLesson['first_name'] ?? '') . ' ' . ($tLesson['last_name'] ?? '')) ?: $tLesson['username'];
                $context .= "👤 {$teacherName} ({$tLesson['lesson_date']})";
                if (!empty($tLesson['lesson_type'])) {
                    $context .= " [{$tLesson['lesson_type']}]";
                }
                $context .= ":\n";
                $context .= "   \"{$tLesson['notes']}\"\n\n";
            }
            
            $context .= "[TAVSİYE: Bu değerlendirmeleri dikkate alarak kendi stratejinizi geliştirebilirsiniz]\n\n";
        } else {
            $context .= "\n--- DİĞER ÖĞRETMENLERİN DEĞERLENDİRMELERİ ---\n";
            $context .= "Henüz başka öğretmenler tarafından not girişi yapılmamış.\n\n";
        }
    }

    /**
     * PDF içeriğini okur (RAM raporları için)
     */
    private function readPdfContent(string $filePath): ?string
    {
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            log_message('error', '[OgretmenAI] PDF dosyası bulunamadı: ' . $filePath);
            return null;
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            if (!empty(trim($text))) {
                return $text;
            }
        } catch (\Exception $e) {
            log_message('debug', '[OgretmenAI] PDF Parser başarısız: ' . $e->getMessage());
        }

        if (function_exists('shell_exec')) {
            try {
                $command = 'pdftotext -layout -enc UTF-8 ' . escapeshellarg($filePath) . ' -';
                $content = @shell_exec($command);

                if ($content !== null && trim($content) !== '') {
                    return $content;
                }
            } catch (\Exception $e) {
                log_message('error', '[OgretmenAI] pdftotext hatası: ' . $e->getMessage());
            }
        }

        return null;
    }
    
    /**
     * Öğretmenin ders programını gösterir
     */
    private function buildLessonScheduleContext(string &$context, string $userMessageLower, int $teacherId): void
    {
        $targetDate = $this->extractDateFromMessage($userMessageLower);
        $lessonModel = new LessonModel();

        $context .= "\n=== DERS PROGRAMINIZ ===\n";
        $context .= "Tarih: {$targetDate}\n\n";

        $lessons = $lessonModel
            ->select('lessons.*, students.adi, students.soyadi')
            ->join('students', 'students.id = lessons.student_id')
            ->where('lessons.teacher_id', $teacherId)
            ->where('lessons.lesson_date', $targetDate)
            ->orderBy('lessons.lesson_time', 'ASC')
            ->findAll();

        if (!empty($lessons)) {
            foreach ($lessons as $lesson) {
                $context .= "🕐 {$lesson['lesson_time']} - {$lesson['adi']} {$lesson['soyadi']} [{$lesson['lesson_type']}]\n";
                if (!empty($lesson['notes'])) {
                    $context .= "   Not: {$lesson['notes']}\n";
                }
                $context .= "\n";
            }
        } else {
            $context .= "Bu tarihte dersiniz bulunmamaktadır.\n";
        }
    }
    
    /**
     * Sabit haftalık program
     */
    private function buildFixedScheduleContext(string &$context, int $teacherId): void
    {
        $fixedLessonModel = new FixedLessonModel();
        
        $context .= "\n=== SABİT HAFTALIK PROGRAMINIZ ===\n\n";

        $fixedLessons = $fixedLessonModel
            ->select('fixed_lessons.*, students.adi, students.soyadi')
            ->join('students', 'students.id = fixed_lessons.student_id')
            ->where('fixed_lessons.teacher_id', $teacherId)
            ->orderBy('fixed_lessons.day_of_week', 'ASC')
            ->orderBy('fixed_lessons.lesson_time', 'ASC')
            ->findAll();

        if (!empty($fixedLessons)) {
            $days = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
            $grouped = [];
            
            foreach ($fixedLessons as $fl) {
                $day = $days[$fl['day_of_week']] ?? 'Bilinmeyen';
                if (!isset($grouped[$day])) {
                    $grouped[$day] = [];
                }
                $grouped[$day][] = $fl;
            }

            foreach ($grouped as $day => $lessons) {
                $context .= "**{$day}:**\n";
                foreach ($lessons as $lesson) {
                    $context .= "  🕐 {$lesson['lesson_time']} - {$lesson['adi']} {$lesson['soyadi']} [{$lesson['lesson_type']}]\n";
                }
                $context .= "\n";
            }
        } else {
            $context .= "Sabit haftalık programınız henüz oluşturulmamış.\n";
        }
    }
}