<?php

namespace App\Controllers\AI;

use App\Libraries\AIService;
use App\Models\FixedLessonModel;
use App\Models\LessonModel;
use App\Models\RamReportAnalysisModel;
use App\Models\StudentEvaluationModel;
use App\Models\StudentModel;

class OgretmenAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);

        if ($this->isGreeting($userMessageLower)) {
            return $this->handleGreetingAndPresentMenu();
        }

        // Intent 1: RAM Raporu Analizi
        if (preg_match("/(.+?) adlı öğrencinin ram raporu analizi nedir/i", $userMessage, $matches)) {
            $studentName = trim($matches[1]);
            $studentId = $this->findStudentIdInMessage($studentName);
            return $this->handleRamReportQuery($user, $studentId);
        }

        // Intent 2: Muhtemel Ders Programı
        if (preg_match("/(.+?)(?:'de| de) muhtemel ders programın nedir/i", $userMessage, $matches)) {
            $dateStr = trim($matches[1]);
            return $this->handleProbableScheduleQuery($user, $dateStr);
        }

        // Intent 3: Diğer Öğretmenlerin Yorumları
        if (preg_match("/diğer öğretmenlerin (.+?) hakkında yorumları nedir/i", $userMessage, $matches)) {
            $studentName = trim($matches[1]);
            $studentId = $this->findStudentIdInMessage($studentName);
            return $this->handleOtherTeacherCommentsQuery($user, $studentId);
        }

        // Intent 4: Öğretmen Raporu
        if ($this->containsKeywords($userMessageLower, ['hakkımda rapor oluştur'])) {
            return $this->handleTeacherReportQuery($user);
        }

        return $this->generateRedirection();
    }

    private function handleGreetingAndPresentMenu(): string
    {
        $response = "Merhaba hocam, ben yapay zeka asistanınız Pusula. Size nasıl daha verimli yardımcı olabilirim? 🤓\n\n";
        $response .= "Aşağıdaki gibi sorular sorabilirsiniz:\n\n";
        $response .= "1. **`{Öğrenci Adı}` adlı öğrencinin ram raporu analizi nedir?**\n";
        $response .= "2. **`{Tarih}`'de muhtemel ders programın nedir?** (Örn: 'yarın', '25.10.2025')\n";
        $response .= "3. **Diğer öğretmenlerin `{Öğrenci Adı}` hakkında yorumları nedir?**\n";
        $response .= "4. **Hakkımda rapor oluştur.**\n";
        return $response;
    }

    private function handleRamReportQuery(object $user, ?int $studentId): string
    {
        if (!$studentId) {
            return "Analiz için lütfen geçerli bir öğrenci adı belirtin.";
        }
        if (!(new StudentModel())->isStudentOfTeacher($studentId, $user->id)) {
            return "Hocam, sadece kendi ders verdiğiniz öğrencilerin RAM raporu analizlerine erişebilirsiniz.";
        }

        $analysisModel = new RamReportAnalysisModel();
        $analysis = $analysisModel->where('student_id', $studentId)->first();

        if (!$analysis || empty($analysis['ram_text_content'])) {
            return "Bu öğrenci için henüz bir RAM raporu analizi bulunmuyor. Lütfen RAM raporunun yüklendiğinden ve analiz edildiğinden emin olun.";
        }

        $student = (new StudentModel())->find($studentId);
        $ramReportText = $analysis['ram_text_content'];

        $systemPrompt = "Sen özel eğitim alanında uzman bir yapay zeka asistanısın. Sana verilen RAM (Rehberlik ve Araştırma Merkezi) raporu metnini analiz et. Bu metinden yola çıkarak, öğrencinin tanısı, bilişsel, sosyal, duygusal ve fiziksel gelişim özelliklerini, eğitimsel performansını, güçlü ve desteklenmesi gereken yönlerini belirle. Bu bilgileri bir özel eğitim öğretmeninin kolayca anlayabileceği teknik ve pedagojik bir dille, başlıklar halinde (örn: Tanı, Bilişsel Gelişim, Güçlü Yönler, Öneriler vb.) özetle. Cevabın doğrudan analiz olsun, selamlama veya giriş cümlesi kullanma. Çıktıyı Markdown formatında yapılandır.";
        
        $userPrompt = "Lütfen aşağıdaki RAM raporu metnini analiz ederek {$student['adi']} {$student['soyadi']} adlı öğrenci için bir özet oluştur:\n\n{$ramReportText}";

        $aiService = new AIService();
        $summary = $aiService->getChatResponse($userPrompt, $systemPrompt);

        $response = "**{$student['adi']} {$student['soyadi']} için Yorumlanmış RAM Raporu Analizi:**\n\n";
        $response .= $summary;
        $response .= "\n\n---\n";
        $response .= "Bu özet, RAM raporunun bir yorumudur. Detaylı ders stratejileri üzerine konuşabiliriz. Ne dersiniz? 🧠";

        return $response;
    }

    private function handleProbableScheduleQuery(object $user, string $dateStr): string
    {
        try {
            $date = new \DateTime($this->normalizeDate($dateStr));
            $dayOfWeek = $date->format('N'); // 1 (Pazartesi) - 7 (Pazar)
        } catch (\Exception $e) {
            return "Lütfen geçerli bir tarih belirtin (örneğin, 'yarın', '25.10.2025').";
        }

        $fixedLessonModel = new FixedLessonModel();
        $lessons = $fixedLessonModel
            ->select('fixed_lessons.start_time, fixed_lessons.end_time, students.adi, students.soyadi')
            ->join('students', 'students.id = fixed_lessons.student_id')
            ->where('fixed_lessons.teacher_id', $user->id)
            ->where('fixed_lessons.day_of_week', $dayOfWeek)
            ->orderBy('fixed_lessons.start_time', 'ASC')
            ->findAll();

        if (empty($lessons)) {
            return $date->format('d.m.Y') . " tarihi için sabit programınızda bir ders bulunmuyor hocam.";
        }

        $response = "**" . $date->format('d.m.Y D') . " için Muhtemel Ders Programınız (Sabit Programa Göre):**\n\n";
        foreach ($lessons as $lesson) {
            $response .= "- **{$lesson['start_time']} - {$lesson['end_time']}:** {$lesson['adi']} {$lesson['soyadi']}\n";
        }

        return $response;
    }

    private function handleOtherTeacherCommentsQuery(object $user, ?int $studentId): string
    {
        if (!$studentId) {
            return "Yorumları görmek için lütfen geçerli bir öğrenci adı belirtin.";
        }

        $evaluationModel = new StudentEvaluationModel();
        $comments = $evaluationModel
            ->where('student_id', $studentId)
            ->where('teacher_id !=', $user->id)
            ->orderBy('created_at', 'DESC')
            ->findAll(15);

        if (empty($comments)) {
            return "Bu öğrenci için diğer öğretmenler tarafından henüz bir yorum yapılmamış.";
        }

        $student = (new StudentModel())->find($studentId);
        
        $commentsText = "";
        foreach ($comments as $comment) {
            $date = date('d.m.Y', strtotime($comment['created_at']));
            $commentsText .= "Öğretmen: {$comment['teacher_snapshot_name']}, Tarih: {$date}, Yorum: \"{$comment['evaluation']}\"\n---\n";
        }

        $systemPrompt = "Sen bir öğretmen asistanısın. Sana verilen, bir öğrenci hakkındaki öğretmen yorumlarını analiz et. Bu yorumlardan yola çıkarak, öğrencinin genel durumu (akademik, davranışsal vb.), güçlü yönleri ve zayıf yönleri hakkında teknik ve pedagojik bir dille bir özet çıkar. Cevabın doğrudan analiz olsun, giriş veya selamlama cümlesi kullanma. Çıktıyı Markdown formatında, başlıklar ve listeler kullanarak yapılandır.";
        
        $userPrompt = "Lütfen aşağıdaki yorumları analiz ederek {$student['adi']} {$student['soyadi']} adlı öğrenci için bir özet oluştur:\n\n{$commentsText}";

        $aiService = new AIService();
        $summary = $aiService->getChatResponse($userPrompt, $systemPrompt);

        $response = "**{$student['adi']} {$student['soyadi']} Hakkındaki Diğer Öğretmen Yorumlarının Özeti:**\n\n";
        $response .= $summary;

        return $response;
    }

    private function handleTeacherReportQuery(object $user): string
    {
        $firstDay = date('Y-m-01');
        $lastDay = date('Y-m-t');

        // 1. Toplam ders saati
        $lessonModel = new LessonModel();
        $lessons = $lessonModel
            ->select('start_time, end_time')
            ->where('teacher_id', $user->id)
            ->where('lesson_date >=', $firstDay)
            ->where('lesson_date <=', $lastDay)
            ->findAll();
        
        $totalMinutes = 0;
        foreach ($lessons as $lesson) {
            $start = new \DateTime($lesson['start_time']);
            $end = new \DateTime($lesson['end_time']);
            $totalMinutes += ($end->getTimestamp() - $start->getTimestamp()) / 60;
        }
        $totalHours = round($totalMinutes / 60, 1);

        // 2. Gelişim raporu katkısı
        $evaluationModel = new StudentEvaluationModel();
        $evaluationCount = $evaluationModel
            ->where('teacher_id', $user->id)
            ->where('created_at >=', $firstDay . ' 00:00:00')
            ->where('created_at <=', $lastDay . ' 23:59:59')
            ->countAllResults();

        // 3. En çok ders yapılan öğrenci
        $mostFrequentStudentQuery = $lessonModel
            ->select('ls.student_id, s.adi, s.soyadi, COUNT(ls.lesson_id) as lesson_count')
            ->from('lesson_students ls')
            ->join('lessons l', 'l.id = ls.lesson_id')
            ->join('students s', 's.id = ls.student_id')
            ->where('l.teacher_id', $user->id)
            ->where('l.lesson_date >=', $firstDay)
            ->where('l.lesson_date <=', $lastDay)
            ->groupBy('ls.student_id, s.adi, s.soyadi')
            ->orderBy('lesson_count', 'DESC')
            ->first();

        $report = "**Bu Ayki Performans Raporunuz (" . date('F Y') . "):**\n\n";
        $report .= "- **Toplam Ders Saati:** Yaklaşık **{$totalHours}** saat derse girdiniz.\n";
        $report .= "- **Gelişim Notu Katkısı:** Bu ay **{$evaluationCount}** adet öğrenci gelişim notu yazdınız.\n";

        if ($mostFrequentStudentQuery) {
            $studentId = $mostFrequentStudentQuery['student_id'];
            $studentName = $mostFrequentStudentQuery['adi'] . ' ' . $mostFrequentStudentQuery['soyadi'];
            $report .= "- **En Çok Ders Yaptığınız Öğrenci:** {$studentName} ({$mostFrequentStudentQuery['lesson_count']} ders)\n";

            $hasEvaluated = $evaluationModel
                ->where('teacher_id', $user->id)
                ->where('student_id', $studentId)
                ->where('created_at >=', $firstDay . ' 00:00:00')
                ->countAllResults() > 0;

            if (!$hasEvaluated) {
                $report .= "\n💡 **Tavsiye:** Bu ay en çok {$studentName} ile ders yapmışsınız ancak henüz onun için bir gelişim notu girmemişsiniz. Öğrencinin ilerlemesini kayıt altına almak için bir not eklemeyi düşünebilirsiniz.";
            }
        }

        return $report;
    }

    private function isGreeting(string $message): bool
    {
        return $this->fuzzyContainsKeywords($message, ['merhaba', 'selam', 'hey', 'iyi günler']);
    }

    private function generateRedirection(): string
    {
        return "Anlıyorum hocam, ancak bu isteğinizi tam olarak nasıl işleyeceğimden emin olamadım. Menüdeki seçenekleri deneyebilir veya sorunuzu farklı bir şekilde sorabilirsiniz.";
    }
    
    private function normalizeDate(string $dateStr): string
    {
        $dateStr = str_replace(['bugün'], ['today'], $dateStr);
        $dateStr = str_replace(['yarın'], ['tomorrow'], $dateStr);
        $dateStr = str_replace(['dün'], ['yesterday'], $dateStr);
        // Replace both dots and slashes with dashes to handle d.m.Y and d/m/Y
        $dateStr = str_replace(['.', '/'], '-', $dateStr);
        return $dateStr;
    }

    protected function findStudentIdInMessage(string $studentName): ?int
    {
        $parts = array_filter(explode(' ', trim($studentName)));
        if (empty($parts)) {
            return null;
        }

        $studentModel = new StudentModel();
        $student = null;
        
        if (count($parts) >= 2) {
            $lastName = array_pop($parts);
            $firstName = implode(' ', $parts);

            // Assuming DB collation is case-insensitive for Turkish (e.g., utf8mb4_turkish_ci)
            $student = $studentModel->where('adi', $firstName)
                                    ->where('soyadi', $lastName)
                                    ->first();
        } else {
            $name = $parts[0];
            $student = $studentModel->where('adi', $name)
                                    ->orWhere('soyadi', $name)
                                    ->first();
        }

        return $student ? (int)$student['id'] : null;
    }
}
