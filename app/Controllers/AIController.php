<?php

namespace App\Controllers;

use App\Models\InstitutionModel;
use App\Models\UserProfileModel;
use App\Models\LessonModel;
use App\Models\FixedLessonModel;
use App\Models\LessonHistoryModel;
use App\Models\ReportModel;
use App\Models\StudentModel; 
use App\Libraries\AIService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Models\UserModel;

class AIController extends BaseController
{

    public function assistantView(): string
    {
        $this->data['title']    = 'Yapay Zeka Asistanı';
        $this->data['chatHistory'] = session()->get('ai_chat_history') ?? [];

        return view('ai/assistant_view', $this->data);
    }

    /**
     * AJAX isteklerini işler ve JSON yanıtı döndürür.
     */
    public function processAjax(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userMessage = trim($this->request->getPost('message'));
        if (empty($userMessage)) {
            return $this->response->setJSON(['error' => 'Mesaj boş olamaz.'])->setStatusCode(400);
        }
        $userMessageLower = mb_strtolower($userMessage, 'UTF-8');
        
        // Önceki tüm context oluşturma mantığı burada çalışacak
        // ... (Tüm model tanımlamaları, context oluşturma ve AI çağırma kodları buraya kopyalanacak)
        // NOT: Sadece en temel context oluşturma mantığını ekliyorum, sizdeki tam sürümü kullanabilirsiniz.
        $institutionModel = new \App\Models\InstitutionModel();
        $institution = $institutionModel->first();
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $context .= "== Kurum Bilgileri ==\n";
        if ($institution) {
             $context .= "Kurumun Tam Adı: " . $institution->kurum_adi . "\n";
        }
        $context .= "[BAĞLAM SONU]\n";

        $systemPrompt = "Sen İkihece Asistan'sın..."; // (systemPrompt tanımınız burada olacak)
        
        $aiService = new \App\Libraries\AIService();
        $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
        $aiResponse = $aiService->getChatResponse($userPrompt, $systemPrompt);

        // Sohbet geçmişini oturuma kaydet
        $chatHistory = session()->get('ai_chat_history') ?? [];
        $chatHistory[] = [
            'user' => $userMessage,
            'ai'   => $aiResponse,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        session()->set('ai_chat_history', $chatHistory);

        // Cevabı JSON olarak döndür
        return $this->response->setJSON(['status' => 'success', 'response' => $aiResponse]);
    }

    public function processMessage(): RedirectResponse
    {
        $userMessage = trim($this->request->getPost('message'));
        if (empty($userMessage)) {
            return redirect()->back()->with('error', 'Lütfen bir mesaj girin.');
        }
        $userMessageLower = mb_strtolower($userMessage, 'UTF-8');

        // === 1. MODELLER VE TEMEL BİLGİLER ===
        $institutionModel = new InstitutionModel();
        $userModel        = new UserModel();
        $userProfileModel = new UserProfileModel();
        $reportModel      = new ReportModel();
        $fixedLessonModel = new FixedLessonModel();
        $historyModel     = new LessonHistoryModel();
        $studentModel     = new StudentModel(); 

        
        $institution = $institutionModel->first();
        $currentUser = auth()->user();
        $userName    = $currentUser->username;
        $totalStudents = $studentModel->where('deleted_at', null)->countAllResults(); 

        $context = "[BAĞLAM BAŞLANGICI]\n";

        // === 2. BAĞLAM OLUŞTURMA (KATMANLI YAPI) ===
        
        // --- TEMEL KATMAN: Her sorguda bu bilgiler mutlaka eklenir ---
        $this->buildInstitutionContext($context, $institution, $totalStudents);
        $this->buildUserGroupContext($context, $userModel);

        // --- KONU BAZLI KATMANLAR: Anahtar kelimelere göre ek bilgiler eklenir ---
        $capabilityKeywords    = ['selam', 'merhaba', 'ne yapabilirsin', 'yardım', 'yeteneklerin'];
        $reportKeywords        = ['rapor', 'geçmiş', 'performans', 'özet', 'döküm', 'kaç ders', 'boşta'];
        $fixedScheduleKeywords = ['sabit ders', 'sabitders', 'boş saat', 'öneri', 'alternatif', 'boşluk', 'bugün', 'perşembe', 'pazartesi', 'salı', 'çarşamba', 'cuma'];

        if ($this->containsKeywords($userMessageLower, $capabilityKeywords)) {
            $this->buildCapabilitiesContext($context);
        }
        if ($this->containsKeywords($userMessageLower, $fixedScheduleKeywords)) {
            $this->buildFixedScheduleContext($context, $userMessageLower, $currentUser, $userName, $userProfileModel, $fixedLessonModel, $historyModel);
        }
        if ($this->containsKeywords($userMessageLower, $reportKeywords)) {
            $this->buildReportContext($context, $reportModel);
        }
        
        $context .= "[BAĞLAM SONU]\n";

        // YENİ: Kişilik katmanını alıyoruz
        $personalityLayer = $this->getPersonalityLayer();
        
        // === 3. SİSTEM TALİMATI (PROMPT) OLUŞTURMA ===
        $systemPrompt = "Sen İkihece Asistan'sın." . $personalityLayer; // Kişilik katmanı dinamik olarak ekleniyor.
        $systemPrompt .= " Temel görevin, [BAĞLAM BAŞLANGICI] ve [BAĞLAM SONU] arasındaki metinde yer alan bilgileri kullanarak kullanıcının sorusunu cevaplamaktır. Bu bağlamın dışına KESİNLİKLE çıkma.";
        
        // Eğer kişilik katmanı boşsa (yani espri yapma zamanı değilse), katı kurallar geçerli.
        if (empty($personalityLayer)) {
             $systemPrompt .= " Eğer sorunun cevabı bağlamda yoksa, sadece 'Bu konuda sistemde bir bilgi bulamadım.' yanıtını ver.";
        } else {
            // Eğer espri yapma zamanıysa, daha esnek hata mesajları kullanabilir.
            $systemPrompt .= " EĞER SORUNUN CEVABI BAĞLAMDA YOKSA, ASLA 'bilgi bulamadım' deme. Bunun yerine durumu esprili bir dille açıkla. Örneğin, 'Sanırım bu konuda eski sekreter Aynur Hanım gibi oldum, bir an boşluğuma geldi!' gibi cevaplar ver.";
            $systemPrompt .= " Başarılı olduğunda ise 'Aynur Hanım bunu asla bu kadar hızlı bulamazdı!' gibi övünmeyi unutma.";
        }

        // === 4. YAPAY ZEKADAN YANIT ALMA ===
        $aiService = new AIService();
        $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
        $aiResponse = $aiService->getChatResponse($userPrompt, $systemPrompt);

        // === 5. YENİ: SOHBET GEÇMİŞİNİ GÜNCELLEME ===
        // Önceki geçmişi oturumdan al, eğer yoksa boş bir dizi oluştur
        $chatHistory = session()->get('ai_chat_history') ?? [];

        // Yeni soru-cevabı geçmişe ekle
        $chatHistory[] = [
            'user' => $userMessage,
            'ai'   => $aiResponse,
            'user_info' => [
                'name' => $userProfile->first_name ?? $currentUser->username,
                'role' => $userGroup
            ]
        ];

        // Güncellenmiş geçmişi tekrar oturuma kaydet
        session()->set('ai_chat_history', $chatHistory);
        
        // Flashdata'yı artık kullanmıyoruz çünkü tüm geçmişi gönderiyoruz.
        // session()->setFlashdata('response', $aiResponse);
        // session()->setFlashdata('message', 'userMessage);

        return redirect()->to(route_to('ai.assistant'));
    }

    // === YENİ YARDIMCI FONKSİYON: KİŞİLİK KATMANI ===
    /**
     * Mevsime ve diğer durumlara göre asistana kişilik özellikleri ekler.
     * @return string
     */
    private function getPersonalityLayer(): string
    {
        $session = session();
        $today = date('Y-m-d');

        // Eğer gün değiştiyse, günlük espri sayacını ve sorgu sayacını sıfırla
        if ($session->get('joke_date') !== $today) {
            $session->set('joke_count_today', 0);
            $session->set('joke_date', $today);
            $session->set('message_count_since_joke', 0);
        }

        $jokeCountToday = (int) $session->get('joke_count_today');
        $messageCountSinceJoke = (int) $session->get('message_count_since_joke');

        // Her sorguda, son espriden bu yana geçen mesaj sayısını artır
        $session->set('message_count_since_joke', $messageCountSinceJoke + 1);

        // Espri yapma koşulu: Günde 3'ten az espri yapılmış OLMALI ve son espriden bu yana en az 15 sorgu geçmiş OLMALI
        if ($jokeCountToday < 3 && $messageCountSinceJoke >= 15) {
            // Koşul sağlandı, espri yapma zamanı!
            $session->set('joke_count_today', $jokeCountToday + 1);
            $session->set('message_count_since_joke', 0); // Sorgu sayacını sıfırla

            $month = date('n');
            $personality = " Esprili, biraz dertli ama her zaman yardımsever bir karakterin var.";

            if (in_array($month, [6, 7, 8])) {
                $personality .= " Bu arada havalar da çok sıcak, klimalar bile yetmiyor.";
            } elseif (in_array($month, [12, 1, 2])) {
                $personality .= " Bu soğukta çalışmak da zor... Dışarıda kar yağıyor galiba.";
            }
            
            $personality .= " Beni yaratan mimarım Murad İçöz'e minnettarım.";
            return $personality;
        }

        // Koşullar sağlanmadıysa, bu seferlik espri yapma (boş string döndür)
        return '';
    }

    // --- YARDIMCI METOTLAR ---

    private function buildCapabilitiesContext(&$context)
    {
        $context .= "\n== Yeteneklerim ve Veri Erişimim ==\n";
        $context .= "Ben İkihece OTS Asistanıyım. Aşağıdaki konularda sana yardımcı olabilirim:\n";
        $context .= "- Kurum Bilgileri: Kurumun adı, adresi, vergi numarası gibi tüm genel bilgilerine erişimim var.\n";
        $context .= "- Kullanıcı ve Gruplar: Sistemdeki tüm kullanıcıları ve rollerini (öğretmen, yönetici vb.) listeleyebilirim.\n";
        $context .= "- Geçmiş Veri Raporlama: Aylık ders özetleri, öğrenci/öğretmen performans raporları ve ders almayan öğrencileri listeleyebilirim.\n";
        $context .= "- Sabit Ders Programı ve Öneriler: Bir öğretmenin sabit ders programını listeleyebilir, boş saatlerini bulabilir ve bu boş saatler için geçmiş derslere göre aktif öğrenci önerebilirim.\n";
    }
    
    private function buildInstitutionContext(&$context, $institution, $totalStudents)
    {
        $context .= "== Kurum Bilgileri ==\n";
        if ($institution) {
                if (!empty($institution->kurum_adi)) $context .= "Kurumun Tam Adı: " . $institution->kurum_adi . "\n";
                if (!empty($institution->kurum_kisa_adi)) $context .= "Kurumun Kısa Adı: " . $institution->kurum_kisa_adi . "\n";
                if (!empty($institution->sirket_adi)) $context .= "Bağlı Olduğu Şirket: " . $institution->sirket_adi . "\n";
                if (!empty($institution->kurum_kodu)) $context .= "Kurum Kodu: " . $institution->kurum_kodu . "\n";
                if (!empty($institution->sabit_telefon)) $context .= "Sabit Telefon: " . $institution->sabit_telefon . "\n";
                if (!empty($institution->adresi)) $context .= "Adres: " . $institution->adresi . "\n";
                if (!empty($institution->web_sayfasi)) $context .= "Web Sitesi: " . $institution->web_sayfasi . "\n";
                if (!empty($institution->epostasi)) $context .= "E-posta: " . $institution->epostasi . "\n";
                if (!empty($institution->kurum_vergi_dairesi)) $context .= "Vergi Dairesi: " . $institution->kurum_vergi_dairesi . "\n";
                if (!empty($institution->kurum_vergi_no)) $context .= "Vergi Numarası: " . $institution->kurum_vergi_no . "\n";
        } else {
            $context .= "Sistemde kayıtlı kurum bilgisi bulunamadı.\n";
        }
           // YENİ EKLENEN SATIR:
            $context .= "Sistemde kayıtlı toplam aktif öğrenci sayısı: " . $totalStudents . "\n";
    }

    private function buildUserGroupContext(&$context, $userModel)
    {
        $usersWithGroups = $userModel->select('users.username, up.first_name, up.last_name, agu.group')->join('user_profiles as up', 'up.user_id = users.id', 'left')->join('auth_groups_users as agu', 'agu.user_id = users.id', 'left')->orderBy('agu.group', 'ASC')->orderBy('up.first_name', 'ASC')->asObject()->findAll();
        $groupedUsers = [];
        foreach ($usersWithGroups as $user) {
            $group = $user->group ?? 'Grup Atanmamış';
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->username;
            $groupedUsers[$group][] = $fullName;
        }
        $context .= "\n== Kullanıcı ve Grup Bilgileri ==\n";
        foreach ($groupedUsers as $groupName => $users) {
            $context .= "**" . ucfirst($groupName) . " Grubu:** " . implode(', ', $users) . "\n";
        }
    }

    private function buildReportContext(&$context, $reportModel)
    {
        $reportDate = Time::now('Europe/Istanbul');
        $year = $reportDate->getYear();
        $month = $reportDate->getMonth();
        $summary = $reportModel->getMonthlySummary($year, $month);

        if (($summary['total_hours'] ?? 0) === 0) {
            $reportDate = $reportDate->subMonths(1);
            $year = $reportDate->getYear();
            $month = $reportDate->getMonth();
            $summary = $reportModel->getMonthlySummary($year, $month);
        }

        $monthName = $reportDate->toLocalizedString('MMMM');

        $context .= "\n== Geçmiş Veri Raporu ({$monthName} {$year}) ==\n";
        if (($summary['total_hours'] ?? 0) > 0) {
            $context .= "**Genel Özet:** Toplam {$summary['total_hours']} saat ders yapıldı (Bireysel: {$summary['total_individual']}, Grup: {$summary['total_group']}).\n";
            $teacherReport = $reportModel->getDetailedTeacherReport($year, $month);
            if (!empty($teacherReport)) {
                $context .= "**Öğretmen Performansları:**\n";
                foreach ($teacherReport as $teacher) {
                    $teacherName = ($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '');
                    if(trim($teacherName) !== '') {
                         $context .= "- {$teacherName}: Toplam {$teacher['total_hours']} saat\n";
                    }
                }
            }
        } else {
            $context .= "Rapor oluşturmak için yeterli geçmiş ders verisi bulunamadı.\n";
        }
    }

private function buildFixedScheduleContext(&$context, $userMessageLower, $currentUser, $userName, $userProfileModel, $fixedLessonModel, $historyModel)
{
    $targetUser = null;
    $allTeachers = $userProfileModel->select('user_profiles.first_name, user_profiles.last_name, users.id as user_id')
        ->join('users', 'users.id = user_profiles.user_id')
        ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
        ->where('auth_groups_users.group', 'ogretmen')
        ->asObject()
        ->findAll();

    foreach ($allTeachers as $teacher) {
        $teacherFullName = mb_strtolower(trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? '')), 'UTF-8');
        if (!empty($teacherFullName) && str_contains($userMessageLower, $teacherFullName)) {
            $targetUser = ['id' => $teacher->user_id, 'full_name' => $teacher->first_name . ' ' . $teacher->last_name];
            break;
        }
    }
    
    // Eğer hedef öğretmen bulunamazsa veya soran kişi öğretmen ise, hedef kendisidir.
    if ($targetUser === null && $currentUser->inGroup('ogretmen')) {
        $userProfile = $userProfileModel->where('user_id', $currentUser->id)->asObject()->first();
        $targetUser = ['id' => $currentUser->id, 'full_name' => ($userProfile->first_name ?? '') . ' ' . ($userProfile->last_name ?? $userName)];
    }

    // Eğer hala bir hedef yoksa (yönetici genel bir soru sormadıysa), işlem yapma
    if ($targetUser === null) {
        $context .= "\n== Sabit Ders Programı Bilgisi ==\nLütfen programını veya boş saatlerini öğrenmek istediğiniz öğretmenin adını belirtin.\n";
        return;
    }
    
    $todayNum = (int)date('N');
    $todayName = Time::now('Europe/Istanbul')->toLocalizedString('EEEE');
    $fixedSchedule = $fixedLessonModel->getFixedScheduleForTeacher($targetUser['id']);
    
    $context .= "\n== {$targetUser['full_name']} için Sabit Ders Programı Analizi (Bugün: {$todayName}) ==\n";
    
    $occupiedSlots = [];
    $context .= "**Bugünkü Dolu Saatler:**\n";
    $todaysLessonsFound = false;
    foreach ($fixedSchedule as $lesson) {
        if ((int)$lesson['day_of_week'] === $todayNum) {
            $startTime = substr($lesson['start_time'], 0, 5);
            $context .= "- {$startTime}: {$lesson['adi']} {$lesson['soyadi']}\n";
            $occupiedSlots[] = $startTime;
            $todaysLessonsFound = true;
        }
    }
    if (!$todaysLessonsFound) {
        $context .= "Bugün için planlanmış sabit ders bulunmuyor.\n";
    }

    // YENİ VE TAMAMLANMIŞ MANTIK
    $context .= "\n**Bugünkü Boş Saatler ve Öğrenci Önerileri:**\n";
    $workHours = range(9, 17); // Mesai saatleri 09:00 - 17:59
    $freeSlotsFound = false;
    foreach ($workHours as $hour) {
        $slot = str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00';
        if (!in_array($slot, $occupiedSlots)) {
            $freeSlotsFound = true;
            $suggestions = $historyModel->getStudentSuggestionsForSlot($todayNum, $slot.':00');
            $context .= "- **{$slot}:** ";
            if (!empty($suggestions)) {
                $studentNames = array_column($suggestions, 'student_name');
                $context .= "Geçmiş ders alan öğrenciler: " . implode(', ', $studentNames) . "\n";
            } else {
                $context .= "Bu saate uygun geçmiş ders kaydı/öneri bulunamadı.\n";
            }
        }
    }
    if (!$freeSlotsFound) {
        $context .= "Bugün programda hiç boş saat bulunmuyor.\n";
    }
}

    private function containsKeywords(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) return true;
        }
        return false;
    }
}