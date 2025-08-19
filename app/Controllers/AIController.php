<?php

namespace App\Controllers;

// Gerekli tüm sınıfları ve modelleri ekliyoruz
use App\Libraries\AIService;
use App\Models\AuthGroupsUsersModel;
use App\Models\InstitutionModel;
use App\Models\StudentModel;
use App\Models\UserProfileModel;
use App\Models\LessonModel;
use App\Models\FixedLessonModel;
use App\Models\ReportModel;
use App\Models\LessonHistoryModel;
use App\Models\LogModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Models\UserModel;
use Smalot\PdfParser\Parser;

class AIController extends BaseController
{
    /**
     * AI sohbet arayüzünü, geçmiş konuşmaları ve role özel örnek komutları gösterir.
     */
    public function assistantView(): string
    {
        $this->data['title']       = 'İkihece AI Asistanı';
        $this->data['chatHistory'] = session()->get('ai_chat_history') ?? [];

        $currentUser = auth()->user();
        $samplePrompts = [];

        if ($currentUser && $currentUser->inGroup('admin', 'yonetici', 'mudur', 'sekreter')) {
            $samplePrompts = [
                'Bu ay en çok ders veren öğretmen kim?',
                'Geçen ayki genel faaliyet raporunu özetler misin?',
                'Bu ay hiç ders almayan öğrencileri veli telefonlarıyla listele.',
                'Kurumda kaç tane aktif öğretmenimiz var?',
                'En son yapılan 10 sistem işlemini göster.',
                'Yarın ders programında boşluk var mı? Hangi hocalar müsait?',
                '[Öğretmen Adı Soyadı]\'nın sabit haftalık ders programı nasıl?',
                '[Öğretmen Adı Soyadı]\'nın yarınki boş saatleri için öğrenci öner.'
            ];
        } 
        elseif ($currentUser && $currentUser->inGroup('ogretmen')) {
            $samplePrompts = [
                'Bu haftaki ders programım nedir?',
                'Öğrencim [Öğrenci Adı Soyadı]\'nın kalan ders hakları ne kadar?',
                'Yarınki derslerimi listeler misin?',
                'Öğrencim [Öğrenci Adı Soyadı]\'nın veli telefon numarası nedir?',
                'Öğrencim [Öğrenci Adı Soyadı]\'nın RAM raporunda öne çıkanlar nelerdir?',
                'Sabit haftalık ders programımı göster.'
            ];
        }
        
        $this->data['samplePrompts'] = $samplePrompts;

        return view('ai/assistant_view', $this->data);
    }

    /**
     * AJAX isteklerini işler ve JSON yanıtı döndürür.
     */
    public function processAjax(): ResponseInterface
    {
        $userMessage = trim($this->request->getPost('message'));
        if (empty($userMessage)) {
            return $this->response->setJSON(['error' => 'Mesaj boş olamaz.'])->setStatusCode(400);
        }
        try {
            $aiResponse = $this->generateAIResponse($userMessage);
            $chatHistory   = session()->get('ai_chat_history') ?? [];
            $chatHistory[] = ['user' => $userMessage, 'ai' => $aiResponse];
            session()->set('ai_chat_history', $chatHistory);
            return $this->response->setJSON(['status' => 'success', 'response' => $aiResponse]);
        } catch (\Exception $e) {
            log_message('error', '[AIController Hata] ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            return $this->response->setJSON(['status' => 'error', 'response' => 'Bir hata oluştu, lütfen tekrar deneyin.']);
        }
    }

    /**
     * FİNAL VERSİYON: Ana AI yanıt oluşturma mantığı.
     */
    private function generateAIResponse(string $userMessage): string
    {
        $currentUser = auth()->user();
        if ($currentUser === null) return 'Lütfen önce sisteme giriş yapın.';

        $userMessageLower = $this->turkish_strtolower($userMessage);
        $isTeacherOnly = $currentUser->inGroup('ogretmen') && !$currentUser->inGroup('admin', 'yonetici', 'mudur');
        
        // --- YETKİ KONTROLÜ ---
        $studentIdForAuthCheck = $this->findStudentIdInMessage($userMessageLower);
        if ($isTeacherOnly && $studentIdForAuthCheck && !(new StudentModel())->isStudentOfTeacher($studentIdForAuthCheck, $currentUser->id)) {
            return 'Sadece kendi ders programınızda kayıtlı öğrenciler hakkında bilgi alabilirsiniz.';
        }
        $systemUserIdForAuthCheck = $this->findSystemUserIdInMessage($userMessageLower);
        if ($isTeacherOnly && $systemUserIdForAuthCheck && $systemUserIdForAuthCheck !== $currentUser->id) {
            return 'Sadece kendi bilgileriniz hakkında soru sorabilirsiniz.';
        }
        
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context);
        $this->buildInstitutionContext($context);

        $this->buildComprehensiveContext($context, $userMessageLower, $currentUser, $isTeacherOnly);
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = "Sen İkihece AI asistanısın. Görevin, [BAĞLAM BAŞLANGICI] ve [BAĞLAM SONU] arasındaki bilgileri kullanarak kullanıcının sorusunu cevaplamaktır. Cevaplarını maddeler halinde veya kolay okunur paragraflar şeklinde, profesyonel bir dille sun. Bağlamdaki 'Aktif Kullanıcı' bilgisine göre kullanıcıya ismiyle hitap et. Bu bağlamın dışına KESİNLİKLE çıkma. Eğer sorunun cevabı bağlamda yoksa, sadece 'Bu konuda sistemde bir bilgi bulamadım.' yanıtını ver.";
        
        $aiService  = new AIService();
        $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
        return $aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    private function buildComprehensiveContext(string &$context, string $userMessageLower, object $currentUser, bool $isTeacherOnly): void
    {
        $intentProcessed = false;
        
        $availabilityKeywords = ['boşluk', 'boş saat', 'müsait', 'öneri', 'öner', 'tavsiye'];
        $reportKeywords = ['rapor', 'istatistik', 'özet', 'en çok', 'en az', 'ders almayanlar'];
        $fixedScheduleKeywords = ['sabit program', 'haftalık programı', 'sabit dersleri'];
        $scheduleKeywords = ['ders programı', 'takvim', 'dersi var mı', 'programı ne', 'derslerim'];
        $logKeywords = ['log', 'kayıtlar', 'hareketler', 'aktivite', 'kim ne yaptı', 'işlem geçmişi'];
        $teacherListKeywords = ['öğretmenleri listele', 'öğretmen listesi', 'öğretmenler kim', 'isimlerini listele'];
        $teacherCountKeywords = ['kaç öğretmen', 'öğretmen sayısı'];

        if ($this->containsKeywords($userMessageLower, $availabilityKeywords)) {
            $this->buildAvailabilityContext($context, $userMessageLower);
            $intentProcessed = true;
        } elseif (!$isTeacherOnly && $this->containsKeywords($userMessageLower, $reportKeywords)) {
            $this->buildReportContext($context, $userMessageLower);
            $intentProcessed = true;
        } elseif ($this->containsKeywords($userMessageLower, $fixedScheduleKeywords)) {
            $this->buildFixedScheduleContext($context, $userMessageLower);
            $intentProcessed = true;
        } elseif ($this->containsKeywords($userMessageLower, $scheduleKeywords)) {
            $this->buildLessonScheduleContext($context, $userMessageLower);
            $intentProcessed = true;
        } elseif (!$isTeacherOnly && $this->containsKeywords($userMessageLower, $logKeywords)) {
            $this->buildLogContext($context, $userMessageLower);
            $intentProcessed = true;
        } elseif (!$isTeacherOnly && $this->containsKeywords($userMessageLower, $teacherCountKeywords)) {
            $this->buildTeacherCountContext($context);
            $intentProcessed = true;
        } elseif (!$isTeacherOnly && $this->containsKeywords($userMessageLower, $teacherListKeywords)) {
            $this->buildTeacherListContext($context);
            $intentProcessed = true;
        }

        if (!$intentProcessed) {
            $studentId = $this->findStudentIdInMessage($userMessageLower);
            if ($studentId) {
                $this->buildStudentContext($context, $userMessageLower, $studentId);
            }
        }
    }
    
    // ===================================================================
    // TÜM build... ve yardımcı fonksiyonlar (EKSİKSİZ VE HATASIZ)
    // ===================================================================
    
    private function buildUserContext(string &$context): void
    {
        $currentUser = auth()->user();
        $activeRole  = session()->get('active_role') ?? ($currentUser->getGroups()[0] ?? 'tanımsız');
        $userProfile = (new UserProfileModel())->where('user_id', $currentUser->id)->first();
        $userName    = trim(($userProfile->first_name ?? '') . ' ' . ($userProfile->last_name ?? '')) ?: $currentUser->username;

        $context .= "== Aktif Kullanıcı Bilgileri ==\n";
        $context .= "Adı Soyadı: " . $userName . "\n";
        $context .= "Sistemdeki Rolü: " . $activeRole . "\n";
    }

    private function buildInstitutionContext(string &$context): void
    {
        $institution = (new InstitutionModel())->first();
        $context .= "== Kurum Bilgileri ==\n";
        if ($institution) {
            if (!empty($institution->kurum_adi)) $context .= "Kurum Adı: {$institution->kurum_adi}\n";
            if (!empty($institution->sabit_telefon)) $context .= "Telefon: {$institution->sabit_telefon}\n";
            if (!empty($institution->epostasi)) $context .= "E-posta: {$institution->epostasi}\n";
        } else {
            $context .= "Sistemde kayıtlı kurum bilgisi bulunamadı.\n";
        }
    }

    private function buildAvailabilityContext(string &$context, string $userMessageLower): void
    {
        $teacherId = $this->findSystemUserIdInMessage($userMessageLower);
        $date = date('Y-m-d');
        if (str_contains($userMessageLower, 'yarın')) $date = date('Y-m-d', strtotime('+1 day'));

        $context .= "\n== {$date} Tarihli Müsaitlik Durumu ==\n";
        
        $teachersToScan = [];
        if ($teacherId) {
            $user = (new UserModel())->find($teacherId);
            if ($user) $teachersToScan[] = $user;
        } else {
            $teachersToScan = (new UserModel())->select('users.id, first_name, last_name')
                ->join('auth_groups_users agu', 'agu.user_id = users.id')
                ->join('user_profiles up', 'up.user_id = users.id', 'left')
                ->where('agu.group', 'ogretmen')->where('users.deleted_at', null)->findAll();
        }

        if (empty($teachersToScan)) {
            $context .= "Sistemde sorgulanacak aktif öğretmen bulunamadı.\n";
            return;
        }

        $otsConfig = config('Ots');
        $allLessonsToday = (new LessonModel())->where('lesson_date', $date)->findAll();
        $historyModel = new LessonHistoryModel();
        $dayOfWeek = (int)date('N', strtotime($date));
        
        foreach ($teachersToScan as $teacher) {
            $userProfile = (new UserProfileModel())->where('user_id', $teacher->id)->first();
            $teacherFullName = "{$userProfile->first_name} {$userProfile->last_name}";
            $context .= "\n**{$teacherFullName}**:\n";
            
            $bookedSlots = [];
            foreach($allLessonsToday as $lesson) {
                if ($lesson['teacher_id'] == $teacher->id) {
                    $bookedSlots[] = substr($lesson['start_time'], 0, 5);
                }
            }

            $emptySlots = [];
            for ($hour = $otsConfig->scheduleStartHour; $hour < $otsConfig->scheduleEndHour; $hour++) {
                $timeSlot = str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00';
                if (!in_array($timeSlot, $bookedSlots)) $emptySlots[] = $timeSlot;
            }

            if (empty($emptySlots)) {
                $context .= "- Bu tarihte boş saati bulunmuyor.\n";
                continue;
            }
            
            $suggestionKeywords = ['öner', 'öneri', 'tavsiye et'];
            if ($this->containsKeywords($userMessageLower, $suggestionKeywords)) {
                $teacherSuggestionsFound = false;
                foreach ($emptySlots as $slot) {
                    $studentSuggestions = $historyModel->getStudentSuggestionsForSlot($dayOfWeek, $slot . ':00');
                    if (!empty($studentSuggestions)) {
                        $teacherSuggestionsFound = true;
                        $studentNames = array_column($studentSuggestions, 'student_name');
                        $context .= "- **{$slot}** için önerilenler: " . implode(', ', $studentNames) . "\n";
                    }
                }
                if (!$teacherSuggestionsFound) {
                    $context .= "- Boş saatleri (" . implode(', ', $emptySlots) . ") mevcut, ancak bu saatlere uygun öğrenci önerisi bulunamadı.\n";
                }
            } else {
                $context .= "- Müsait saatler: " . implode(', ', $emptySlots) . "\n";
            }
        }
    }
    
    private function buildReportContext(string &$context, string $userMessageLower): void
    {
        $reportModel = new ReportModel();
        $year = date('Y');
        $month = date('m');
        if(str_contains($userMessageLower, 'geçen ay')) {
            $date = strtotime('-1 month');
            $year = date('Y', $date);
            $month = date('m', $date);
        }
        $context .= "\n== Rapor ve İstatistik Bilgileri ({$year}-{$month}) ==\n";

        if ($this->containsKeywords($userMessageLower, ['ders almayan'])) {
            $inactiveStudents = $reportModel->getStudentsWithNoLessons($year, $month);
            $context .= "Bu Ay Ders Almayan Aktif Öğrenciler:\n";
            if (empty($inactiveStudents)) $context .= "- Bu ay ders almayan öğrenci bulunmamaktadır.\n";
            else foreach($inactiveStudents as $s) $context .= "- {$s['student_name']} (Veli Tel: {$s['veli_anne_telefon']} / {$s['veli_baba_telefon']})\n";
        } elseif ($this->containsKeywords($userMessageLower, ['en çok'])) {
            $teacherReport = $reportModel->getDetailedTeacherReport($year, $month);
            if(empty($teacherReport)) $context .= "Raporlanacak öğretmen verisi bulunamadı.\n";
            else {
                usort($teacherReport, fn($a, $b) => $b['total_hours'] <=> $a['total_hours']);
                $topTeacher = $teacherReport[0];
                $context .= "Bu Ay En Çok Ders Veren Öğretmen: {$topTeacher['first_name']} {$topTeacher['last_name']} ({$topTeacher['total_hours']} saat)\n";
            }
        } else {
             $summary = $reportModel->getMonthlySummary($year, $month);
            $context .= "Aylık Özet: {$summary['total_hours']} saat ders, {$summary['total_individual']} bireysel, {$summary['total_group']} grup, {$summary['total_students']} öğrenci.\n";
        }
    }
    
    private function buildFixedScheduleContext(string &$context, string $userMessageLower): void
    {
        $teacherId = $this->findSystemUserIdInMessage($userMessageLower);
        if (!$teacherId) {
            $currentUser = auth()->user();
            if ($currentUser->inGroup('ogretmen')) $teacherId = $currentUser->id;
        }

        $context .= "\n== Sabit Haftalık Ders Programı ==\n";
        if (!$teacherId) {
            $context .= "Lütfen programını görmek istediğiniz öğretmenin adını belirtin.\n";
            return;
        }
        
        $teacherProfile = (new UserProfileModel())->where('user_id', $teacherId)->first();
        $schedule = (new FixedLessonModel())->getFixedScheduleForTeacher($teacherId);

        if (empty($schedule)) {
            $context .= "{$teacherProfile->first_name} {$teacherProfile->last_name} için sabit program bulunmuyor.\n";
            return;
        }

        $daysOfWeek = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
        $context .= "{$teacherProfile->first_name} {$teacherProfile->last_name} için sabit program:\n";
        
        $groupedSchedule = [];
        foreach ($schedule as $lesson) {
            $dayName = $daysOfWeek[$lesson['day_of_week']] ?? 'Bilinmeyen Gün';
            $groupedSchedule[$dayName][] = "- " . substr($lesson['start_time'], 0, 5) . "-" . substr($lesson['end_time'], 0, 5) . ": {$lesson['adi']} {$lesson['soyadi']}";
        }

        foreach ($groupedSchedule as $day => $lessons) {
            $context .= "**{$day}**\n" . implode("\n", $lessons) . "\n";
        }
    }

    private function buildLessonScheduleContext(string &$context, string $userMessageLower): void
    {
        $lessonModel = new LessonModel();
        $date = date('Y-m-d');
        if (str_contains($userMessageLower, 'yarın')) $date = date('Y-m-d', strtotime('+1 day'));
        if (str_contains($userMessageLower, 'dün')) $date = date('Y-m-d', strtotime('-1 day'));

        $studentId = $this->findStudentIdInMessage($userMessageLower);
        $teacherId = $this->findSystemUserIdInMessage($userMessageLower);
        if (!$teacherId && !$studentId) {
             $currentUser = auth()->user();
            if ($currentUser->inGroup('ogretmen')) $teacherId = $currentUser->id;
        }

        $context .= "\n== {$date} Tarihli Ders Programı Bilgileri ==\n";
        
        if ($teacherId) {
            $teacherProfile = (new UserProfileModel())->where('user_id', $teacherId)->first();
            $lessons = $lessonModel->getLessonsForTeacherByDate($teacherId, $date);
            $context .= "**{$teacherProfile->first_name} {$teacherProfile->last_name}**:\n";
            if (empty($lessons)) $context .= "- Bu tarihte kayıtlı dersi bulunmuyor.\n";
            else foreach ($lessons as $l) $context .= "- {$l['start_time']}-{$l['end_time']}: {$l['adi']} {$l['soyadi']}\n";
        } elseif ($studentId) {
            $student = (new StudentModel())->find($studentId);
            $lessons = $lessonModel->getLessonsForStudentByDate($studentId, $date);
            $context .= "**{$student['adi']} {$student['soyadi']}**:\n";
            if (empty($lessons)) $context .= "- Bu tarihte kayıtlı dersi bulunmuyor.\n";
            else foreach ($lessons as $l) $context .= "- {$l['start_time']}-{$l['end_time']}: {$l['ogretmen_adi']} ile {$l['ders_tipi']}\n";
        } else {
            $context .= "Lütfen ders programını görmek istediğiniz öğrenci veya öğretmenin adını belirtin.\n";
        }
    }
    
    private function buildLogContext(string &$context, string $userMessageLower): void
    {
        $logModel = new LogModel();
        $context .= "\n== Son Sistem Hareketleri ==\n";
        
        $logs = $logModel->select('logs.*, users.username')
                         ->join('users', 'users.id = logs.user_id', 'left')
                         ->orderBy('logs.id', 'DESC')
                         ->limit(10)->findAll();
                         
        if (empty($logs)) {
            $context .= "Görüntülenecek sistem hareketi bulunamadı.\n";
        } else {
            foreach ($logs as $log) {
                $createdAt = $log['created_at'] ?? 'Tarih Yok';
                $message = $log['message'] ?? 'Mesaj Yok';
                $username = $log['username'] ?? 'Sistem';
                $ipAddress = $log['ip_address'] ?? 'IP Yok';
                $context .= "- [{$createdAt}] {$message} (Yapan: {$username}, IP: {$ipAddress})\n";
            }
        }
    }

    private function buildTeacherCountContext(string &$context): void
    {
        $count = (new AuthGroupsUsersModel())
            ->join('users', 'users.id = auth_groups_users.user_id')
            ->where('group', 'ogretmen')
            ->where('users.deleted_at', null)
            ->countAllResults();

        $context .= "\n== Öğretmen Sayısı Bilgisi ==\n";
        $context .= "Sistemde kayıtlı toplam aktif öğretmen sayısı: " . $count . "\n";
    }

    private function buildTeacherListContext(string &$context): void
    {
        $teachers = (new UserModel())->select('users.id, first_name, last_name, secret as email, phone_number')
            ->join('auth_groups_users agu', 'agu.user_id = users.id')
            ->join('user_profiles up', 'up.user_id = users.id', 'left')
            ->join('auth_identities ai', 'ai.user_id = users.id AND ai.type = "email_password"', 'left')
            ->where('agu.group', 'ogretmen')->where('users.deleted_at', null)->findAll();
        
        $context .= "\n== Sistemdeki Öğretmenler Listesi ==\n";
        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $context .= "- {$teacher->first_name} {$teacher->last_name} (E-posta: {$teacher->email}, Tel: {$teacher->phone_number})\n";
            }
        } else {
            $context .= "Sistemde 'öğretmen' grubuna atanmış aktif bir kullanıcı bulunmamaktadır.\n";
        }
    }
    
private function buildStudentContext(string &$context, string $userMessageLower, ?int $studentId): void
    {
        $studentModel = new StudentModel();
        $targetStudent = $studentId ? $studentModel->find($studentId) : null;

        $context .= "\n== Öğrenci Bilgileri ==\n";
        if ($targetStudent) {
            $context .= "İstenen Öğrenci: {$targetStudent['adi']} {$targetStudent['soyadi']}\n";
            if (!empty($targetStudent['tc_kimlik_no'])) $context .= "- T.C. Kimlik No: {$targetStudent['tc_kimlik_no']}\n";
            if (!empty($targetStudent['veli_anne_adi_soyadi'])) $context .= "- Anne: {$targetStudent['veli_anne_adi_soyadi']} (Tel: {$targetStudent['veli_anne_telefon']})\n";
            if (!empty($targetStudent['veli_baba_adi_soyadi'])) $context .= "- Baba: {$targetStudent['veli_baba_adi_soyadi']} (Tel: {$targetStudent['veli_baba_telefon']})\n";
            $context .= "--- Kalan Ders Hakları ---\n";
            $context .= "- Normal (Bireysel/Grup): " . ($targetStudent['normal_bireysel_hak'] ?? 0) . "/" . ($targetStudent['normal_grup_hak'] ?? 0) . "\n";
            $context .= "- Telafi (Bireysel/Grup): " . ($targetStudent['telafi_bireysel_hak'] ?? 0) . "/" . ($targetStudent['telafi_grup_hak'] ?? 0) . "\n";

            // --- RAPOR KONTROL MEKANİZMASI ---
            if ($this->containsKeywords($userMessageLower, ['ram', 'rapor'])) {
                $context .= "\n--- RAM Raporu Analizi Başladı ---\n";

                // 1. Veritabanında rapor adı kayıtlı mı?
                if (empty($targetStudent['ram_raporu'])) {
                    $context .= "Problem: Veritabanında bu öğrenciye ait bir RAM raporu dosya adı bulunamadı.\n";
                } else {
                    $reportFileName = $targetStudent['ram_raporu'];
                    $context .= "Bilgi: Veritabanında bulunan dosya adı: {$reportFileName}\n";
                    
                    // 2. Dosya fiziksel olarak sunucuda var mı?
                    $reportPath = WRITEPATH . 'uploads/ram_reports/' . $reportFileName;
                    $context .= "Bilgi: Dosyanın sunucudaki tam yolu: {$reportPath}\n";

                    if (!file_exists($reportPath)) {
                        $context .= "Problem: Dosya sunucuda belirtilen yolda bulunamadı! Lütfen dosyanın varlığını ve yolun doğruluğunu kontrol edin.\n";
                    } else {
                        // 3. Dosya okunabiliyor mu ve içeriği var mı?
                        $reportContent = $this->readPdfContent($reportPath);
                        if (empty(trim($reportContent))) {
                             $context .= "Problem: Rapor dosyası bulundu ancak içeriği boş veya okunamadı. Dosya bozuk veya sadece resim içeriyor olabilir.\n";
                        } else {
                            $context .= "Başarılı: Rapor dosyası okundu. İçeriğin özeti aşağıdadır:\n";
                            $context .= substr($reportContent, 0, 750) . "...\n"; // Özet karakter sayısını artırdım
                        }
                    }
                }
            }
        } else {
            $context .= "Sorguda adı geçen öğrenci sistemde bulunamadı.\n";
        }
    }

private function readPdfContent(string $filePath): ?string
{
    // 1. Dosyanın var olup olmadığını ve boş olup olmadığını kontrol et
    if (!file_exists($filePath) || filesize($filePath) === 0) {
        log_message('error', '[AIController] PDF dosyası bulunamadı veya boş: ' . $filePath);
        return null;
    }

    // 2. Komutu oluştur ve çalıştır (Linux için yol belirtmeye gerek yok)
    // -layout: Orijinal PDF'in düzenini korumaya çalışır.
    // -enc UTF-8: Türkçe karakterlerin doğru çıkmasını sağlar.
    // -: Son tire, çıktının dosyaya yazılmak yerine doğrudan geri döndürülmesini sağlar.
    $command = 'pdftotext -layout -enc UTF-8 "' . $filePath . '" -';

    try {
        // shell_exec, komutun çıktısını bir string olarak döndürür.
        $content = shell_exec($command);

        // Eğer komut başarısız olursa veya çıktı boşsa, null döndür.
        if ($content === null || trim($content) === '') {
            log_message('warning', '[AIController] pdftotext aracı PDF içeriğini okuyamadı veya dosya boş. Dosya: ' . $filePath);
            return null;
        }

        return $content;

    } catch (\Exception $e) {
        log_message('error', '[AIController] pdftotext komutu çalıştırılırken bir istisna oluştu: ' . $e->getMessage());
        return null;
    }
}

    private function containsKeywords(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) return true;
        }
        return false;
    }

    private function findSystemUserIdInMessage(string $userMessageLower): ?int
    {
        $profiles = (new UserProfileModel())->select('user_id, first_name, last_name')->findAll();
        foreach ($profiles as $profile) {
            $fullNameLower = $this->turkish_strtolower(trim($profile->first_name . ' ' . $profile->last_name));
            if (!empty($fullNameLower) && str_contains($userMessageLower, $fullNameLower)) {
                return (int)$profile->user_id;
            }
        }
        return null;
    }

    private function findStudentIdInMessage(string $userMessageLower): ?int
    {
        $students = (new StudentModel())->select('id, adi, soyadi')->findAll();
        foreach ($students as $student) {
            $fullNameLower = $this->turkish_strtolower(trim($student['adi'] . ' ' . $student['soyadi']));
            if (!empty($fullNameLower) && str_contains($userMessageLower, $fullNameLower)) {
                return (int)$student['id'];
            }
        }
        return null;
    }

    private function turkish_strtolower(string $text): string
    {
        $search  = ['İ', 'I', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'];
        $replace = ['i', 'ı', 'ğ', 'ü', 'ş', 'ö', 'ç'];
        $text = str_replace($search, $replace, $text);
        return mb_strtolower($text, 'UTF-8');
    }
}