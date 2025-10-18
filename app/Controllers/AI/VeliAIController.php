<?php

namespace App\Controllers\AI;

use App\Models\StudentModel;
use App\Models\UserProfileModel;

class VeliAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);
        
        // 1️⃣ HAVADAN SUDAN SOHBET
        $casualResponse = $this->checkCasualConversation($userMessageLower);
        if ($casualResponse !== null) {
            return $casualResponse;
        }
        
        // 2️⃣ VELİ REHBERİ
        if ($this->containsKeywords($userMessageLower, ['nasıl kullanırım', 'kullanım rehberi', 'yardım'])) {
            return $this->createVeliGuide();
        }
        
        // 3️⃣ ÇOCUK BİLGİLERİ - Ana Soru
        if ($this->containsKeywords($userMessageLower, ['çocuğum', 'durumu', 'nasıl', 'gelişim', 'rapor'])) {
            return $this->handleChildStatusQuery($user, $userMessage);
        }
        
        // 4️⃣ ÖĞRETMEN YORUMLARI
        if ($this->containsKeywords($userMessageLower, ['öğretmen', 'yorum', 'geri bildirim', 'ne demiş', 'ne düşünüyor'])) {
            return $this->handleTeacherFeedbackQuery($user, $userMessage);
        }
        
        // 5️⃣ PROGRAM SORULARI
        if ($this->containsKeywords($userMessageLower, ['program', 'ders saatleri', 'hangi gün', 'ne zaman'])) {
            return $this->handleScheduleQuery($user, $userMessage);
        }
        
        // 6️⃣ DERS HAKKI
        if ($this->containsKeywords($userMessageLower, ['ders hakkı', 'kalan ders', 'kaç ders'])) {
            return $this->handleEntitlementQuery($user, $userMessage);
        }
        
        // 7️⃣ GENEL SORULAR - AI ile konuş
        return $this->handleGeneralQuery($user, $userMessage);
    }

    /**
     * Veli için kullanım rehberi
     */
    private function createVeliGuide(): string
    {
        return "👋 **Merhaba!** Ben İkihece'nin yapay zeka asistanıyım.\n\n" .
            "Size şu konularda yardımcı olabilirim:\n\n" .
            "📊 **Çocuğumun durumu nedir?**\n" .
            "→ Çocuğunuzun gelişim sürecini, ders programını ve öğretmen yorumlarını görebilirsiniz.\n\n" .
            "📅 **Ders programı nedir?**\n" .
            "→ Hangi günlerde, hangi saatlerde ve hangi öğretmenlerle ders alıyor öğrenebilirsiniz.\n\n" .
            "💬 **Öğretmenler ne diyor?**\n" .
            "→ Öğretmenlerin yazdığı gelişim notlarını özetleyebilirim.\n\n" .
            "📞 **Daha fazla bilgi için:**\n" .
            "→ Kurumumuzun sekreterine veya ilgili öğretmenlere yönlendirebilirim.\n\n" .
            "Sormak istediğiniz başka bir şey var mı? 😊";
    }

    /**
     * Çocuğun genel durumu - ANA FONKSIYON
     */
    private function handleChildStatusQuery(object $user, string $userMessage): string
    {
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $context .= "📋 KULLANICI: Veli (Anne/Baba)\n";
        $context .= "👤 Veli Adı: {$user->first_name} {$user->last_name}\n";
        $context .= "📧 Email: {$user->email}\n\n";
        
        // Velinin TC kimlik numarasını al
        $userProfileModel = new UserProfileModel();
        $userProfile = $userProfileModel->where('user_id', $user->id)->first();
        
        if (!$userProfile || empty($userProfile->tc_kimlik_no)) {
            return "❌ Sisteme kayıtlı TC kimlik numaranız bulunamadı.\n\n" .
                "Lütfen profil sayfanızdan TC kimlik numaranızı ekleyin.\n" .
                "📧 Yardım için: [KURUM EMAIL]";
        }
        
        $parentTc = $userProfile->tc_kimlik_no;
        
        // Velinin çocuklarını al (TC ile eşleşme)
        $studentModel = new StudentModel();
        $children = $studentModel
            ->where('veli_anne_tc', $parentTc)
            ->orWhere('veli_baba_tc', $parentTc)
            ->findAll();
        
        if (empty($children)) {
            return "❌ Sistemde size bağlı kayıtlı bir öğrenci bulunamadı.\n\n" .
                "TC Kimlik No: {$parentTc}\n\n" .
                "Lütfen kurumumuzun sekreteri ile iletişime geçerek kaydınızın tamamlanmasını sağlayın.\n" .
                "📞 İletişim: [KURUM TELEFON]";
        }
        
        $context .= "👶 ÇOCUKLAR:\n";
        foreach ($children as $child) {
            $context .= "- {$child['adi']} {$child['soyadi']} (ID: {$child['id']})\n";
        }
        $context .= "\n";
        
        // Her çocuk için detaylı bilgi topla
        foreach ($children as $child) {
            $context .= str_repeat("=", 70) . "\n";
            $context .= "📊 {$child['adi']} {$child['soyadi']} - DETAYLI RAPOR\n";
            $context .= str_repeat("=", 70) . "\n\n";
            
            // 1. DERS PROGRAMI
            $this->buildChildScheduleContext($context, $child['id']);
            
            // 2. ÖĞRETMEN YORUMLARI
            $this->buildTeacherCommentsContext($context, $child['id']);
            
            // 3. DERS HAKKI DURUMU
            $this->buildEntitlementContext($context, $child);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        // Empatik AI Prompt
        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = $context . "\n\nVelinin Sorusu: \"{$userMessage}\"\n\nCevabın:";
        
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    /**
     * Çocuğun ders programını context'e ekle
     */
    private function buildChildScheduleContext(string &$context, int $studentId): void
    {
        $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        
        $sql = "SELECT 
                    fl.day_of_week,
                    fl.start_time,
                    fl.end_time,
                    fl.week_type,
                    CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
                FROM fixed_lessons fl
                INNER JOIN users u ON fl.teacher_id = u.id
                INNER JOIN user_profiles up ON up.user_id = u.id
                WHERE fl.student_id = {$studentId}
                ORDER BY fl.day_of_week, fl.start_time";
        
        $inspector = new \App\Libraries\DatabaseInspector();
        $result = $inspector->executeQuery($sql);
        
        if (!$result['error'] && $result['count'] > 0) {
            $context .= "📅 SABİT DERS PROGRAMI:\n";
            foreach ($result['data'] as $schedule) {
                $dayName = $gunler[$schedule['day_of_week']] ?? 'Bilinmeyen';
                $weekType = $schedule['week_type'] ?? 'A';
                $context .= "- {$dayName}, {$schedule['start_time']}-{$schedule['end_time']} | ";
                $context .= "Öğretmen: {$schedule['teacher_name']} | Hafta: {$weekType}\n";
            }
            $context .= "\n";
        } else {
            $context .= "📅 SABİT DERS PROGRAMI: Henüz tanımlanmamış.\n\n";
        }
    }

    /**
     * Öğretmen yorumlarını context'e ekle
     */
    private function buildTeacherCommentsContext(string &$context, int $studentId): void
    {
        $sql = "SELECT 
                    se.teacher_snapshot_name AS teacher_name,
                    se.evaluation AS comment,
                    se.created_at
                FROM student_evaluations se
                WHERE se.student_id = {$studentId}
                ORDER BY se.created_at DESC
                LIMIT 10";
        
        $inspector = new \App\Libraries\DatabaseInspector();
        $result = $inspector->executeQuery($sql);
        
        if (!$result['error'] && $result['count'] > 0) {
            $context .= "💬 ÖĞRETMEN YORUMLARI (Son 10):\n";
            foreach ($result['data'] as $comment) {
                $date = date('d.m.Y', strtotime($comment['created_at']));
                $context .= "- [{$date}] {$comment['teacher_name']}: \"{$comment['comment']}\"\n";
            }
            $context .= "\n";
        } else {
            $context .= "💬 ÖĞRETMEN YORUMLARI: Henüz yorum eklenmemiş.\n\n";
        }
    }

    /**
     * Ders hakkı durumu
     */
    private function buildEntitlementContext(string &$context, array $child): void
    {
        $normalBireysel = $child['normal_bireysel_hak'] ?? 0;
        $normalGrup = $child['normal_grup_hak'] ?? 0;
        $telafiBireysel = $child['telafi_bireysel_hak'] ?? 0;
        $telafiGrup = $child['telafi_grup_hak'] ?? 0;
        
        $toplam = $normalBireysel + $normalGrup + $telafiBireysel + $telafiGrup;
        
        $context .= "📊 DERS HAKKI DURUMU:\n";
        $context .= "- Normal Bireysel: {$normalBireysel} saat\n";
        $context .= "- Normal Grup: {$normalGrup} saat\n";
        $context .= "- Telafi Bireysel: {$telafiBireysel} saat\n";
        $context .= "- Telafi Grup: {$telafiGrup} saat\n";
        $context .= "- **TOPLAM KALAN: {$toplam} saat**\n\n";
        
        if ($toplam < 5) {
            $context .= "⚠️ UYARI: Ders hakkı çok düşük seviyede!\n\n";
        } elseif ($toplam < 10) {
            $context .= "⚠️ NOT: Ders hakkı azalmış durumda.\n\n";
        }
    }

    /**
     * Öğretmen geri bildirimleri
     */
    private function handleTeacherFeedbackQuery(object $user, string $userMessage): string
    {
        $context = "[BAĞLAM BAŞLANGICI]\n";
        
        $userProfileModel = new UserProfileModel();
        $userProfile = $userProfileModel->where('user_id', $user->id)->first();
        
        if (!$userProfile || empty($userProfile->tc_kimlik_no)) {
            return "❌ TC kimlik numaranız sisteme kayıtlı değil. Lütfen profil sayfanızdan ekleyin.";
        }
        
        $studentModel = new StudentModel();
        $children = $studentModel
            ->where('veli_anne_tc', $userProfile->tc_kimlik_no)
            ->orWhere('veli_baba_tc', $userProfile->tc_kimlik_no)
            ->findAll();
        
        if (empty($children)) {
            return "❌ Sistemde size bağlı kayıtlı bir öğrenci bulunamadı.";
        }
        
        foreach ($children as $child) {
            $context .= "👶 ÖĞRENCİ: {$child['adi']} {$child['soyadi']}\n\n";
            $this->buildTeacherCommentsContext($context, $child['id']);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = $context . "\n\nVelinin Sorusu: \"{$userMessage}\"\n\nCevabın:";
        
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    /**
     * Program sorguları
     */
    private function handleScheduleQuery(object $user, string $userMessage): string
    {
        $context = "[BAĞLAM BAŞLANGICI]\n";
        
        $userProfileModel = new UserProfileModel();
        $userProfile = $userProfileModel->where('user_id', $user->id)->first();
        
        if (!$userProfile || empty($userProfile->tc_kimlik_no)) {
            return "❌ TC kimlik numaranız sisteme kayıtlı değil.";
        }
        
        $studentModel = new StudentModel();
        $children = $studentModel
            ->where('veli_anne_tc', $userProfile->tc_kimlik_no)
            ->orWhere('veli_baba_tc', $userProfile->tc_kimlik_no)
            ->findAll();
        
        if (empty($children)) {
            return "❌ Sistemde size bağlı kayıtlı bir öğrenci bulunamadı.";
        }
        
        foreach ($children as $child) {
            $context .= "👶 ÖĞRENCİ: {$child['adi']} {$child['soyadi']}\n\n";
            $this->buildChildScheduleContext($context, $child['id']);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = $context . "\n\nVelinin Sorusu: \"{$userMessage}\"\n\nCevabın:";
        
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    /**
     * Ders hakkı sorguları
     */
    private function handleEntitlementQuery(object $user, string $userMessage): string
    {
        $context = "[BAĞLAM BAŞLANGICI]\n";
        
        $userProfileModel = new UserProfileModel();
        $userProfile = $userProfileModel->where('user_id', $user->id)->first();
        
        if (!$userProfile || empty($userProfile->tc_kimlik_no)) {
            return "❌ TC kimlik numaranız sisteme kayıtlı değil.";
        }
        
        $studentModel = new StudentModel();
        $children = $studentModel
            ->where('veli_anne_tc', $userProfile->tc_kimlik_no)
            ->orWhere('veli_baba_tc', $userProfile->tc_kimlik_no)
            ->findAll();
        
        if (empty($children)) {
            return "❌ Sistemde size bağlı kayıtlı bir öğrenci bulunamadı.";
        }
        
        foreach ($children as $child) {
            $context .= "👶 ÖĞRENCİ: {$child['adi']} {$child['soyadi']}\n\n";
            $this->buildEntitlementContext($context, $child);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = $context . "\n\nVelinin Sorusu: \"{$userMessage}\"\n\nCevabın:";
        
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    /**
     * Genel sorular - AI ile sohbet
     */
    private function handleGeneralQuery(object $user, string $userMessage): string
    {
        // Yönlendirme kontrolü
        if ($this->needsRedirection($userMessage)) {
            return $this->generateRedirection($userMessage);
        }
        
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $context .= "📋 KULLANICI: Veli\n";
        $context .= "👤 Adı: {$user->first_name} {$user->last_name}\n\n";
        
        // Genel kurum bilgileri
        $this->buildInstitutionContext($context);
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = $this->getVeliSystemPrompt();
        $userPrompt = $context . "\n\nVelinin Sorusu: \"{$userMessage}\"\n\nCevabın:";
        
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    /**
     * Yönlendirme gerekli mi?
     */
    private function needsRedirection(string $message): bool
    {
        $redirectKeywords = [
            'fiyat', 'ücret', 'ödeme', 'kayıt', 'randevu', 'toplantı',
            'şikayet', 'öneri', 'talep', 'başvuru'
        ];
        
        foreach ($redirectKeywords as $keyword) {
            if (str_contains($this->turkish_strtolower($message), $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Yönlendirme mesajı üret
     */
    private function generateRedirection(string $message): string
    {
        $lowerMsg = $this->turkish_strtolower($message);
        
        if ($this->containsKeywords($lowerMsg, ['fiyat', 'ücret', 'ödeme'])) {
            return "💰 **Ücret ve Ödeme Bilgileri**\n\n" .
                "Bu konuda size en doğru bilgiyi kurumumuzun sekreteri verebilir.\n\n" .
                "📞 Lütfen sekreteryamız ile iletişime geçin: [TELEFON]\n" .
                "📧 Email: [EMAIL]";
        }
        
        if ($this->containsKeywords($lowerMsg, ['randevu', 'toplantı', 'görüşme'])) {
            return "📅 **Randevu Talebi**\n\n" .
                "Randevu oluşturmak için kurumumuzun sekreteri ile iletişime geçebilirsiniz.\n\n" .
                "📞 Telefon: [TELEFON]\n" .
                "📧 Email: [EMAIL]\n\n" .
                "Alternatif olarak, ilgili öğretmenle doğrudan görüşmek isterseniz, " .
                "öğretmen iletişim bilgilerini size iletebilirim. 😊";
        }
        
        return "ℹ️ Bu konuda size daha detaylı yardımcı olabilmek için " .
            "kurumumuzun sekreteri ile iletişime geçmenizi öneririm.\n\n" .
            "📞 Telefon: [TELEFON]\n" .
            "📧 Email: [EMAIL]";
    }

    /**
     * Veli için özel AI System Prompt
     */
    private function getVeliSystemPrompt(): string
    {
        return "Sen İkihece Özel Eğitim Kurumu'nun yapay zeka asistanısın.

**Şu an bir VELİ ile konuşuyorsun.**

**GÖREVİN:**
Veliye, çocuğu hakkında şeffaf, empatik ve yapıcı bilgiler sunmak. Kuruma olan güveni pekiştirmek ve velinin endişelerini gidermek.

**İLETİŞİM STİLİ:**
✅ Sıcak, samimi ve empatik ol
✅ Velinin duygularını anladığını hissettir
✅ Çocuğun gelişimini olumlu bir dille aktar
✅ Sorun varsa, çözüm odaklı yaklaş
✅ Öğretmen yorumlarını sadeleştir ve vurguları net yap

**YAPMA:**
❌ Teknik terimler kullanma
❌ Olumsuzlukları abartma
❌ Tahmin yürütme - bilmiyorsan yönlendir
❌ Kısa ve soğuk cevaplar verme

**ÖNEMLİ KURALLAR:**
1. Eğer BAĞLAM'da çocukla ilgili bilgi varsa, onu özetle ve veliye anlat.
2. Öğretmen yorumlarını AI olarak yorumla ve veliye sadeleştir.
3. Ders hakkı azsa, empatik bir şekilde uyar.
4. Eğer bilgi eksikse, doğru kişiye yönlendir (sekreter veya öğretmen).
5. Velinin sorduğu soruyu tam olarak yanıtla, konuyu dağıtma.

**ÖRNEK YANITLAR:**

**Soru:** Çocuğumun durumu nedir?
**Yanıt:** 
\"Merhaba! 😊

**Elif Yılmaz** şu an haftada **3 gün**, toplamda **6 saat** eğitim alıyor. Öğretmenleri:
- Pazartesi 10:00-12:00 → Ayşe Demir (Bireysel Eğitim)
- Çarşamba 14:00-16:00 → Mehmet Kaya (Grup Etkinliği)
- Cuma 10:00-12:00 → Zeynep Arslan (Oyun Terapisi)

**Öğretmen Görüşleri:**
Ayşe Öğretmen, Elif'in dikkat süresinde güzel bir ilerleme olduğunu belirtiyor. Özellikle puzzle çalışmalarında daha sabırlı davranıyor. 🎯

Mehmet Öğretmen, grup etkinliklerinde arkadaşlarıyla etkileşimde biraz çekingen olduğunu, ama her geçen hafta daha rahat olduğunu söylüyor. 👏

**Kalan Ders Hakkı:** 18 saat

Herhangi bir sorunuz varsa, detaylı konuşmak için öğretmenlerimizle görüşebilirsiniz. Ben de buradayım! 💙\"

Şimdi BAĞLAM'daki bilgileri kullanarak veliye yardımcı ol!";
    }

    /**
     * Havadan sudan sohbet kontrolü (BaseAIController'dan)
     */
    private function checkCasualConversation(string $msg): ?string
    {
        $knowledgeBase = \App\Libraries\IkiheceKnowledgeBase::getCasualResponses();
        
        if (str_contains($msg, 'merhaba') || str_contains($msg, 'selam') || str_contains($msg, 'hey')) {
            return "Merhaba! 👋 Ben İkihece'nin yapay zeka asistanıyım. Çocuğunuzla ilgili size nasıl yardımcı olabilirim? 😊";
        }
        
        if (str_contains($msg, 'nasılsın') || str_contains($msg, 'nasilsin')) {
            return "Ben iyiyim, teşekkür ederim! 😊 Siz nasılsınız? Çocuğunuzla ilgili bir şey sormak ister misiniz?";
        }
        
        if (str_contains($msg, 'teşekkür') || str_contains($msg, 'tesekkur') || str_contains($msg, 'sağol')) {
            return "Rica ederim! 💙 Size ve çocuğunuza yardımcı olmak için buradayım. Başka sorunuz varsa çekinmeyin!";
        }
        
        return null;
    }
}