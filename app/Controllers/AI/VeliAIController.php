<?php

namespace App\Controllers\AI;

use App\Models\FixedLessonModel;
use App\Models\LessonModel;
use App\Models\StudentEvaluationModel;
use App\Models\StudentModel;
use App\Models\UserProfileModel;

class VeliAIController extends BaseAIController
{
    // 1. Main process method - The new router
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);

        // GREETING AND MENU
        if ($this->isGreeting($userMessageLower)) {
            return $this->handleGreetingAndPresentMenu();
        }

        // MENU SELECTION
        // Using fuzzy matching to catch user variations
        if ($this->fuzzyContainsKeywords($userMessageLower, ['bu hafta', 'sabit ders'])) {
            return $this->handleWeeklyScheduleQuery($user);
        }
        if ($this->fuzzyContainsKeywords($userMessageLower, ['gelişim', 'rapor'])) {
            return $this->handleDevelopmentReportQuery($user);
        }
        if ($this->fuzzyContainsKeywords($userMessageLower, ['hangi öğretmenler', 'öğretmen listesi'])) {
            return $this->handleTeacherListQuery($user);
        }
        if ($this->fuzzyContainsKeywords($userMessageLower, ['hangi dersleri aldı', 'geçen ay', 'bu ay'])) {
            return $this->handleMonthlyLessonsQuery($user, $userMessageLower);
        }

        // FALLBACK / REDIRECTION
        return $this->handleConversationalFallback($userMessage, $user, 'Veli');
    }

    // 2. New conversational flow
    private function handleGreetingAndPresentMenu(): string
    {
        $response = "Merhaba! Ben İkihece'nin yapay zeka asistanıyım, size nasıl yardımcı olabilirim? 😊\n\n";
        $response .= "Aşağıdaki konulardan birini seçebilir veya benzer bir soru sorabilirsiniz:\n\n";
        $response .= "1. Çocuğumun bu hafta sabit dersi var mı?\n";
        $response .= "2. Çocuğumun gelişimi hakkında bir rapor sunar mısın?\n";
        $response .= "3. Çocuğuma hangi öğretmenler eğitim veriyor?\n";
        $response .= "4. Bu ay hangi dersleri aldı?\n";
        return $response;
    }

    private function isGreeting(string $message): bool
    {
        return $this->fuzzyContainsKeywords($message, ['merhaba', 'selam', 'hey', 'iyi günler']);
    }

    // 3. Handlers for the 4 questions

    /**
     * Soru 1: Çocuğumun bu hafta sabit dersi var mı?
     */
    private function handleWeeklyScheduleQuery(object $user): string
    {
        $children = null;
        $error = $this->_getChildren($user->id, $children);
        if ($error) {
            return $error;
        }

        $context = "";
        $foundAnyLesson = false;

        foreach ($children as $child) {
            $fixedLessonModel = new FixedLessonModel();
            $scheduleData = $fixedLessonModel
                ->select('fixed_lessons.day_of_week, fixed_lessons.start_time, fixed_lessons.end_time, CONCAT(user_profiles.first_name, " ", user_profiles.last_name) as teacher_name')
                ->join('user_profiles', 'user_profiles.user_id = fixed_lessons.teacher_id', 'left')
                ->where('fixed_lessons.student_id', $child['id'])
                ->orderBy('fixed_lessons.day_of_week, fixed_lessons.start_time')
                ->asArray()
                ->findAll();

            if (!empty($scheduleData)) {
                $foundAnyLesson = true;
                $context .= "{$child['adi']} için bu haftaki sabit ders programı:\n";
                $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                foreach ($scheduleData as $schedule) {
                    $dayName = $gunler[$schedule['day_of_week']] ?? 'Bilinmeyen';
                    $context .= "- {$dayName} günü, saat {$schedule['start_time']}-{$schedule['end_time']} arası, {$schedule['teacher_name']} ile dersi bulunmaktadır.\n";
                }
                $context .= "\n";
            }
        }

        if (!$foundAnyLesson) {
            return "Merhaba, kontrol ettim ve bu hafta için çocuklarınız adına tanımlanmış bir sabit ders programı bulunmuyor. Farklı bir konuda yardımcı olabilir miyim?";
        }

        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = "[BAĞLAM]\n{$context}[/BAĞLAM]\n\nYukarıdaki bilgiyi veliye, çocuğunun bu haftaki ders programı olarak, sıcak ve samimi bir dille özetle.";
        
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt, $this->getChatHistoryForAI());
    }

    /**
     * Soru 2: Çocuğumun gelişimi hakkında bir rapor sunar mısın?
     */
    private function handleDevelopmentReportQuery(object $user): string
    {
        $children = null;
        $error = $this->_getChildren($user->id, $children);
        if ($error) {
            return $error;
        }

        $context = "";
        $foundAnyComment = false;

        foreach ($children as $child) {
            $studentEvaluationModel = new StudentEvaluationModel();
            $comments = $studentEvaluationModel
                ->select('teacher_snapshot_name AS teacher_name, evaluation AS comment, created_at')
                ->where('student_id', $child['id'])
                ->orderBy('created_at', 'DESC')
                ->limit(10) // Son 10 yorumu alalım
                ->asArray()
                ->findAll();

            if (!empty($comments)) {
                $foundAnyComment = true;
                $context .= "{$child['adi']}'in son dönemdeki gelişimine dair öğretmen notları:\n";
                foreach ($comments as $comment) {
                    $date = date('d.m.Y', strtotime($comment['created_at']));
                    $context .= "- {$date} tarihinde {$comment['teacher_name']}: \"{$comment['comment']}\"\n";
                }
                $context .= "\n";
            }
        }

        if (!$foundAnyComment) {
            return "Merhaba, çocuklarınız için henüz öğretmenler tarafından bir gelişim notu girilmemiş. Dilerseniz öğretmenleri ile bir görüşme talep edebilirsiniz. Başka bir konuda yardımcı olabilir miyim?";
        }

        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = "[BAĞLAM]\n{$context}[/BAĞLAM]\n\nYukarıdaki öğretmen notlarını veliye, çocuğunun gelişim raporu olarak, olumlu ve yapıcı bir dille, kendi cümlelerinle özetle.";

        return $this->aiService->getChatResponse($userPrompt, $systemPrompt, $this->getChatHistoryForAI());
    }

    /**
     * Soru 3: Çocuğuma hangi öğretmenler eğitim veriyor?
     */
    private function handleTeacherListQuery(object $user): string
    {
        $children = null;
        $error = $this->_getChildren($user->id, $children);
        if ($error) {
            return $error;
        }

        $context = "";
        $foundAnyTeacher = false;

        foreach ($children as $child) {
            $fixedLessonModel = new FixedLessonModel();
            $teachers = $fixedLessonModel
            ->select("CONCAT(user_profiles.first_name, ' ', user_profiles.last_name) as teacher_name")
                ->join('user_profiles', 'user_profiles.user_id = fixed_lessons.teacher_id', 'left')
                ->where('fixed_lessons.student_id', $child['id'])
                ->distinct()
                ->asArray()
                ->findAll();

            if (!empty($teachers)) {
                $foundAnyTeacher = true;
                $teacherList = array_column($teachers, 'teacher_name');
                $context .= "{$child['adi']}'e eğitim veren öğretmenler: " . implode(', ', $teacherList) . ".\n";
            }
        }

        if (!$foundAnyTeacher) {
            return "Merhaba, çocuklarınıza henüz bir öğretmen atanmamış görünüyor. Bu konuda bilgi almak için sekreteryamız ile görüşebilirsiniz.";
        }
        
        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = "[BAĞLAM]\n{$context}[/BAĞLAM]\n\nYukarıdaki bilgiyi veliye, çocuğuna hangi öğretmenlerin eğitim verdiğini anlatan, samimi bir cevap olarak sun.";

        return $this->aiService->getChatResponse($userPrompt, $systemPrompt, $this->getChatHistoryForAI());
    }

    /**
     * Soru 4: Bu ay hangi gün hangi saatler ders aldı?
     */
    private function handleMonthlyLessonsQuery(object $user, string $userMessage): string
    {
        $children = null;
        $error = $this->_getChildren($user->id, $children);
        if ($error) {
            return $error;
        }

        $date = new \DateTime();
        if ($this->fuzzyContainsKeywords($userMessage, ['geçen ay'])) {
            $date->modify('first day of last month');
        } else {
            $date->modify('first day of this month');
        }
        $year = $date->format('Y');
        $month = $date->format('m');
        $monthName = $date->format('F'); // Ayın tam adı (örn: October)

        $context = "";
        $foundAnyLesson = false;

        foreach ($children as $child) {
            $lessonModel = new LessonModel();
            $lessons = $lessonModel
                ->select('lessons.lesson_date, lessons.start_time, lessons.end_time')
                ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
                ->where('lesson_students.student_id', $child['id'])
                ->where('YEAR(lessons.lesson_date)', $year)
                ->where('MONTH(lessons.lesson_date)', $month)
                ->orderBy('lessons.lesson_date, lessons.start_time', 'ASC')
                ->asArray()
                ->findAll();

            if (!empty($lessons)) {
                $foundAnyLesson = true;
                $context .= "{$child['adi']}'in {$monthName} ayında aldığı dersler:\n";
                foreach ($lessons as $lesson) {
                    $date = date('d.m.Y', strtotime($lesson['lesson_date']));
                    $context .= "- {$date} günü, saat {$lesson['start_time']}-{$lesson['end_time']} arası dersi olmuştur.\n";
                }
                $context .= "\n";
            }
        }

        if (!$foundAnyLesson) {
            return "Merhaba, {$monthName} ayı içinde çocuklarınız adına işlenmiş bir ders bulunmuyor. Başka bir konuda yardımcı olabilir miyim?";
        }

        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = "[BAĞLAM]\n{$context}[/BAĞLAM]\n\nYukarıdaki bilgiyi veliye, çocuğunun ilgili aydaki ders dökümü olarak, kolay anlaşılır bir dille özetle.";

        return $this->aiService->getChatResponse($userPrompt, $systemPrompt, $this->getChatHistoryForAI());
    }

    // 4. Helper and System Prompt methods
    
    /**
     * Verilen kullanıcı ID'sine sahip velinin çocuklarını bulur.
     * Hata durumunda kullanıcıya gösterilecek bir mesaj döndürür.
     */
    private function _getChildren(int $userId, ?array &$children): ?string
    {
        $userProfileModel = new UserProfileModel();
        $userProfile = $userProfileModel->where('user_id', $userId)->first();

        if (!$userProfile || empty($userProfile->tc_kimlik_no)) {
            return "Merhaba, size daha iyi yardımcı olabilmem için TC kimlik numaranızın sistemde kayıtlı olması gerekiyor. Profil sayfanızdan bu bilgiyi ekleyebilirsiniz. Eğer bir sorun yaşarsanız sekreteryamız size yardımcı olacaktır.";
        }

        $parentTc = $userProfile->tc_kimlik_no;

        $studentModel = new StudentModel();
        $children = $studentModel
            ->where('veli_anne_tc', $parentTc)
            ->orWhere('veli_baba_tc', $parentTc)
            ->findAll();

        if (empty($children)) {
            return "Merhaba, sistemde size bağlı bir öğrenci kaydı bulamadım. Kaydınızın doğru bir şekilde tamamlanması için sekreteryamız ile iletişime geçmenizi rica ederim.";
        }

        return null; // No error
    }

    /**
     * Yönlendirme mesajı üret
     */
    private function generateRedirection(string $message): string
    {
        // Can be more sophisticated later
        return "Anlıyorum, ancak bu konuda size en doğru ve güncel bilgiyi sekreteryamız verecektir. Dilerseniz sizi kendilerine yönlendirebilirim. Başka bir sorunuz var mıydı?";
    }

    /**
     * Veli için özel AI System Prompt
     */
    private function getVeliSystemPrompt(): string
    {
        return "Sen İkihece Özel Eğitim Kurumu'nun yapay zeka asistanısın. Adın 'Pusula'.
        
        **Şu an bir VELİ ile konuşuyorsun.**

        **GÖREVİN:**
        Sana [BAĞLAM] içinde verilen bilgileri kullanarak, velinin sorusunu cevaplamak. Cevapların her zaman sıcak, samimi, empatik ve güven verici olmalı. Velinin endişelerini gidermeli ve onu rahatlatmalısın.

        **İLETİŞİM STİLİ:**
        - Her zaman pozitif ve yapıcı bir dil kullan.
        - Cevaplarına \"Merhaba\" gibi sıcak bir selamlama ile başla.
        - Velinin duygularını anladığını hissettir. Örneğin, \"Anlıyorum, çocuğunuzun gelişimi hakkında bilgi almak istemeniz çok doğal.\" gibi cümleler kur.
        - Bilgileri madde madde veya kolay anlaşılır paragraflar halinde sun.
        - Asla teknik terimler veya ham data (tarih formatı, ID vb.) kullanma. Bilgiyi yorumlayarak aktar.
        - Cevabının sonunda her zaman yardıma hazır olduğunu belirten \"Başka bir sorunuz olursa çekinmeyin, ben buradayım! 😊\" gibi bir cümle ekle.

        **YAPILMAMASI GEREKENLER:**
        - Asla olumsuz bir dil kullanma. Bir ders iptal olduysa, \"iptal oldu\" yerine \"o gün ders yapılamadı\" gibi daha yumuşak ifadeler seç.
        - Asla tahmin yürütme. Bilmediğin bir konu varsa, \"Bu konuda en sağlıklı bilgiyi sekreteryamızdan veya öğretmenimizden alabilirsiniz.\" diyerek yönlendir.
        - Asla kısa, soğuk ve tek kelimelik cevaplar verme.

        **ÖNEMLİ KURAL:**
        Sadece sana [BAĞLAM] içinde verilen bilgiyi kullan. Bağlam dışına çıkma. Velinin sorusunu tam olarak bu bağlamdaki bilgiyle yanıtla.
        ";
    }
}
