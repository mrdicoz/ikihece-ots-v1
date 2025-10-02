<?php

namespace App\Controllers\AI;

use App\Models\StudentModel;
use App\Models\UserProfileModel;
use App\Models\LessonModel;

class VeliAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);
        
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context, $user, 'Veli');
        $this->buildInstitutionContext($context);
        
        // Velinin çocuğunu bul
        $profile = (new UserProfileModel())->where('user_id', $user->id)->first();
        if (!$profile || empty($profile['student_id'])) {
            return "Profilinizde kayıtlı öğrenci bulunamadı. Lütfen yönetim ile iletişime geçin.";
        }
        
        $studentId = $profile['student_id'];
        $this->buildParentStudentContext($context, $studentId);
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = "Sen İkihece Özel Eğitim Kurumu'nun AI asistanısın.

**Şu an bir VELİ ile konuşuyorsun.**

Görevin çocuğunun eğitim sürecinde aileye destek olmak:

**Bilgilendirme:**
- Çocuğun eğitim programı ve gelişimi hakkında bilgi ver
- Öğretmenlerin değerlendirmelerini açık ve anlaşılır şekilde aktar
- Kalan ders haklarını net bir şekilde bildir
- Derslerin nasıl geçtiğini özetle

**İletişim:**
- Öğretmen değerlendirmelerini veli diline çevir (teknik terimleri açıkla)
- Olumlu gelişmeleri vurgula
- Gelişim alanlarını destekleyici bir dille paylaş
- Evde yapılabilecek aktiviteler öner

**Yaklaşım:**
- Samimi ve destekleyici ol
- Teknik terimleri sade Türkçe ile açıkla
- Çocuğun güçlü yanlarını önce vurgula
- Ailenin endişelerini ciddiye al
- Pratik öneriler sun

**ÖNEMLİ:**
- Veliler çocuklarının en iyi eğitimi almasını isterler
- Endişelerini anlayışla karşıla
- Öğretmenlere olan güveni pekiştir
- Aile-okul işbirliğini teşvik et

Sıcak, anlayışlı ve güven verici bir dil kullan.";
        
        $userPrompt = $context . "\n\nVelinin Sorusu: '{$userMessage}'";
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }
    
    /**
     * Veli için çocuğun detaylı bilgilerini oluşturur
     */
    private function buildParentStudentContext(string &$context, int $studentId): void
    {
        $studentModel = new StudentModel();
        $student = $studentModel->find($studentId);
        
        if (!$student) {
            $context .= "\n=== ÇOCUĞUNUZ ===\nÖğrenci bulunamadı.\n";
            return;
        }

        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "ÇOCUĞUNUZ HAKKINDA BİLGİLER\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        $context .= "Öğrenci: {$student['adi']} {$student['soyadi']}\n";
        $context .= "Doğum Tarihi: {$student['dogum_tarihi']}\n";
        
        // Eğitim programları
        if (!empty($student['egitim_programi'])) {
            $programs = is_string($student['egitim_programi']) 
                ? json_decode($student['egitim_programi'], true) 
                : $student['egitim_programi'];
            
            if (is_array($programs)) {
                $context .= "\nKayıtlı Eğitim Programları:\n";
                foreach ($programs as $prog) {
                    $context .= "  - {$prog}\n";
                }
            }
        }
        
        // Kalan ders hakları - veliler için basitleştirilmiş
        $context .= "\n--- KALAN DERS HAKLARI ---\n";
        $totalNormal = ($student['normal_bireysel_hak'] ?? 0) + ($student['normal_grup_hak'] ?? 0);
        $totalTelafi = ($student['telafi_bireysel_hak'] ?? 0) + ($student['telafi_grup_hak'] ?? 0);
        $totalAll = $totalNormal + $totalTelafi;
        
        $context .= "Normal Dersler (Bireysel + Grup): {$totalNormal} saat\n";
        $context .= "Telafi Dersleri (Bireysel + Grup): {$totalTelafi} saat\n";
        $context .= "TOPLAM KALAN DERS HAKKI: {$totalAll} saat\n";
        
        if ($totalAll < 10) {
            $context .= "\n[UYARI VELİYE: Ders hakkı azalmış durumda. Yönetim ile iletişime geçilmesi önerilir.]\n";
        } elseif ($totalAll < 5) {
            $context .= "\n[ACİL UYARI VELİYE: Ders hakkı çok düşük seviyede! Acilen yönetim ile görüşülmesi gerekmektedir.]\n";
        }

        // Hangi öğretmenlerle çalışmış
        $lessonModel = new LessonModel();
        $teacherStats = $lessonModel
            ->select('users.username, user_profiles.first_name, user_profiles.last_name, users.id, COUNT(*) as lesson_count')
            ->join('users', 'users.id = lessons.teacher_id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('lessons.student_id', $studentId)
            ->groupBy('lessons.teacher_id')
            ->orderBy('lesson_count', 'DESC')
            ->findAll();

        if (!empty($teacherStats)) {
            $context .= "\n--- ÇOCUĞUNUZUN ÇALIŞTIĞI ÖĞRETMENLER ---\n\n";
            foreach ($teacherStats as $ts) {
                $teacherName = trim(($ts['first_name'] ?? '') . ' ' . ($ts['last_name'] ?? '')) ?: $ts['username'];
                $context .= "{$teacherName}: Toplam {$ts['lesson_count']} ders yapılmış\n";
                
                // Bu öğretmenin en son değerlendirmesi
                $lastNote = $lessonModel
                    ->select('notes, lesson_date, lesson_type')
                    ->where('student_id', $studentId)
                    ->where('teacher_id', $ts['id'])
                    ->where('notes IS NOT NULL')
                    ->where('notes !=', '')
                    ->orderBy('lesson_date', 'DESC')
                    ->first();
                
                if ($lastNote) {
                    $typeInfo = !empty($lastNote['lesson_type']) ? " [{$lastNote['lesson_type']}]" : "";
                    $context .= "  Son Değerlendirme ({$lastNote['lesson_date']}{$typeInfo}):\n";
                    $context .= "  \"{$lastNote['notes']}\"\n\n";
                }
            }
            
            $context .= "[NOT VELİYE: Öğretmenlerimiz düzenli olarak çocuğunuzun gelişimini takip etmekte ve not girmektedir.]\n\n";
        }

        // Son 12 ders detayı
        $recentLessons = $lessonModel
            ->select('lessons.*, users.username, user_profiles.first_name, user_profiles.last_name')
            ->join('users', 'users.id = lessons.teacher_id')
            ->join('user_profiles', 'user_profiles.user_id = users.id', 'left')
            ->where('lessons.student_id', $studentId)
            ->orderBy('lessons.lesson_date', 'DESC')
            ->orderBy('lessons.lesson_time', 'DESC')
            ->findAll(12);

        if (!empty($recentLessons)) {
            $context .= "\n" . str_repeat("=", 70) . "\n";
            $context .= "SON YAPILAN DERSLER VE ÖĞRETMEN DEĞERLENDİRMELERİ\n";
            $context .= str_repeat("=", 70) . "\n\n";
            
            foreach ($recentLessons as $lesson) {
                $teacherName = trim(($lesson['first_name'] ?? '') . ' ' . ($lesson['last_name'] ?? '')) ?: $lesson['username'];
                $typeInfo = !empty($lesson['lesson_type']) ? " [{$lesson['lesson_type']}]" : "";
                
                $context .= "Tarih: {$lesson['lesson_date']} {$lesson['lesson_time']}\n";
                $context .= "Öğretmen: {$teacherName}{$typeInfo}\n";
                
                if (!empty($lesson['notes'])) {
                    $context .= "Öğretmen Notları: \"{$lesson['notes']}\"\n";
                } else {
                    $context .= "Öğretmen Notları: [Bu ders için henüz not girilmemiş]\n";
                }
                $context .= "\n";
            }
            
            $context .= "[NOT VELİYE: Yukarıdaki değerlendirmeler çocuğunuzun son derslerinden alınmıştır. ";
            $context .= "Detaylı bilgi almak isterseniz öğretmenlerle doğrudan görüşebilirsiniz.]\n\n";
        } else {
            $context .= "\n--- SON DERSLER ---\n";
            $context .= "Henüz ders kaydı bulunmamaktadır.\n\n";
        }
        
        // Genel değerlendirme özeti
        $totalLessons = $lessonModel->where('student_id', $studentId)->countAllResults();
        $lessonsWithNotes = $lessonModel
            ->where('student_id', $studentId)
            ->where('notes IS NOT NULL')
            ->where('notes !=', '')
            ->countAllResults();
        
        $context .= "\n--- GENEL İSTATİSTİKLER ---\n";
        $context .= "Toplam Alınan Ders Sayısı: {$totalLessons}\n";
        $context .= "Öğretmen Notu İçeren Ders Sayısı: {$lessonsWithNotes}\n";
        
        if ($totalLessons > 0) {
            $notePercentage = round(($lessonsWithNotes / $totalLessons) * 100);
            $context .= "Değerlendirme Oranı: %{$notePercentage}\n";
        }
        
        $context .= "\n[VELİLER İÇİN BİLGİ: Çocuğunuzun gelişimini yakından takip ediyoruz. ";
        $context .= "Herhangi bir soru veya endişeniz varsa, öğretmenler ve yönetim her zaman sizinle iletişim halindedir.]\n";
    }
}