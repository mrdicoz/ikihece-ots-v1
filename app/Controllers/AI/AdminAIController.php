<?php

namespace App\Controllers\AI;

use App\Libraries\DatabaseInspector;
use App\Models\ReportModel;
use App\Models\LogModel;

class AdminAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);
        
        // 1️⃣ HAVADAN SUDAN SOHBET (En önce!)
        $casualResponse = $this->checkCasualConversation($userMessageLower);
        if ($casualResponse !== null) {
            return $casualResponse;
        }
        
        // 2️⃣ SİSTEM KULLANIMI REHBERİ
        if ($this->containsKeywords($userMessageLower, ['sistem nasıl kullanılır', 'nasıl kullanırım', 'kullanım rehberi'])) {
            return $this->createReferenceMenu(
                "Sistem Kullanımı",
                [
                    "Öğrenci nasıl eklenir?",
                    "RAM raporu nasıl yüklenir?",
                    "Toplu öğrenci nasıl yüklenir?",
                ]
            );
        }
        
        // Sistem kullanımı alt soruları için rehber göster
        if ($this->containsKeywords($userMessageLower, ['nasıl', 'nerede', 'nereden', 'kullanım', 'rehber', 'yardım'])) {
            $guideResponse = $this->handleSystemGuide($userMessageLower);
            if ($guideResponse !== null) {
                return $guideResponse;
            }
        }
        
        // 3️⃣ DUYURU YAZMA YARDIMI
        if ($this->containsKeywords($userMessageLower, ['duyuru yaz', 'duyuru oluştur', 'duyuru taslağı'])) {
            return "Duyuru yazmak için size yardımcı olabilirim!\n\n" .
                "✏️ Ne tür bir duyuru yazmak istersiniz?\n" .
                "📝 Varolan bir metni düzenlememi ister misiniz?\n\n" .
                "Lütfen detay verin, size yardımcı olayım.";
        }
        
        // ✨ DİNAMİK VERİTABANI SORGU SİSTEMİ
        return $this->processDynamicQuery($userMessage, $user);
    }

    /**
     * ✨ DİNAMİK VERİTABANI SORGU SİSTEMİ
     * AI kendisi veritabanından veri çeker ve analiz eder
     */
    private function processDynamicQuery(string $userMessage, object $user): string
    {
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context, $user, 'Admin');
        $this->buildInstitutionContext($context);
        
        // 🔥 Tüm veritabanı yapısını AI'ya öğret
        $this->buildCompleteDatabaseSchema($context);
        
        // 🔥 AI'dan SQL sorguları üretmesini iste
        $sqlQueries = $this->generateSQLQueries($userMessage, $context);
        
        // 🔥 Sorguları çalıştır ve sonuçları ekle
        if (!empty($sqlQueries)) {
            $context .= "\n" . str_repeat("=", 70) . "\n";
            $context .= "📊 SORGU SONUÇLARI\n";
            $context .= str_repeat("=", 70) . "\n\n";
            
            foreach ($sqlQueries as $i => $sql) {
                $queryNum = $i + 1;
                $context .= "--- SORGU {$queryNum} ---\n";
                $this->executeSQLQuery($sql, $context);
                $context .= "\n";
            }
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        // 🔥 AI'dan final cevabı al
        return $this->getIntelligentResponse($context, $userMessage);
    }

    /**
     * Tüm veritabanı yapısını projenizdeki GERÇEK şemaya göre AI'ya öğretir.
     */
    private function buildCompleteDatabaseSchema(string &$context): void
    {
        $db = \Config\Database::connect();
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "📊 VERİTABANI YAPISI - GERÇEK ŞEMA\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        // ✅ 1. STUDENTS TABLOSU (GERÇEK SÜTUNLAR)
        $context .= "📋 **students** - Öğrenci Bilgileri\n";
        $context .= "   Öğrenci aramak için: WHERE adi='Ad' AND soyadi='Soyad' AND deleted_at IS NULL\n";
        $context .= "   Ana Sütunlar:\n";
        $context .= "   - id (INT, PRIMARY KEY)\n";
        $context .= "   - adi (VARCHAR 100) - Öğrenci adı\n";
        $context .= "   - soyadi (VARCHAR 100) - Öğrenci soyadı\n";
        $context .= "   - tc_kimlik_no (VARCHAR 11) - TC kimlik no\n";
        $context .= "   - dogum_tarihi (DATE)\n";
        $context .= "   - cinsiyet (VARCHAR 10)\n";
        $context .= "   - veli_baba_adi_soyadi (VARCHAR 200)\n";
        $context .= "   - veli_baba_telefon (VARCHAR 20)\n";
        $context .= "   - veli_anne_adi_soyadi (VARCHAR 200)\n";
        $context .= "   - veli_anne_telefon (VARCHAR 20)\n";
        $context .= "   - servis_durumu (VARCHAR 20) - Değerler: 'var', 'yok', 'arasira'\n";
        $context .= "   - ram_raporu (VARCHAR 255) - Rapor dosyasının adı (varsa dolu, yoksa null)\n";
        $context .= "   - ram_baslangic_tarihi (DATE)\n";
        $context .= "   - ram_bitis_tarihi (DATE)\n";
        $context .= "   - deleted_at (DATETIME) - Silinmiş kayıtları sorgu dışı bırakmak için 'deleted_at IS NULL' koşulu kullanılmalıdır.\n";
        if ($db->tableExists('students')) {
            $count = $db->table('students')->where('deleted_at', null)->countAllResults();
            $context .= "   Toplam aktif öğrenci: {$count}\n";
        }
        $context .= "\n";

        // ✅ 2. USERS + USER_PROFILES (GERÇEK SÜTUNLAR)
        $context .= "📋 **users + user_profiles** - Kullanıcılar (Öğretmenler, Adminler vb.)\n";
        $context .= "   Sütunlar:\n";
        $context .= "   - users: id, username, deleted_at\n";
        $context .= "   - user_profiles: user_id, first_name, last_name, phone_number\n";
        $context .= "   Öğretmen filtresi: auth_groups_users.group = 'ogretmen'\n";
        if ($db->tableExists('users')) {
            $count = $db->table('users')->where('deleted_at', null)->countAllResults();
            $context .= "   Toplam aktif kullanıcı: {$count}\n";
        }
        $context .= "\n";
        
        // 🔗 İLİŞKİLER
        $context .= "🔗 TABLO İLİŞKİLERİ:\n";
        $context .= "- users.id ↔ user_profiles.user_id\n";
        $context .= "- users.id ↔ auth_groups_users.user_id (Kullanıcının grubunu bulmak için)\n";
        $context .= "\n";
        
        // ⚠️ KURALLAR
        $context .= "⚠️ SQL YAZMA KURALLARI:\n";
        $context .= "1. Öğrenci aramak için: students.adi ve students.soyadi kullanılmalı.\n";
        $context .= "2. Kullanıcı/Öğretmen aramak için: user_profiles.first_name ve user_profiles.last_name kullanılmalı.\n";
        $context .= "3. Silinmiş kayıtları görmemek için sorgulara MUTLAKA 'deleted_at IS NULL' koşulu eklenmeli.\n";
        $context .= "4. Bir öğrencinin yaşını hesaplamak için: TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE()) kullanılmalı.\n";
        $context .= "5. Bugünün tarihi: " . date('Y-m-d') . "\n";
        $context .= "\n";
        
        // 📝 ÖRNEK SORGULAR (GERÇEK ŞEMAYA UYGUN)
        $context .= "📝 ÖRNEK SORGULAR:\n\n";
        
        $context .= "Örnek 1 - Bilal Akyıldız'ın Temel Bilgileri:\n";
        $context .= "SELECT id, adi, soyadi, dogum_tarihi, servis_durumu, veli_anne_adi_soyadi, veli_anne_telefon, veli_baba_adi_soyadi, veli_baba_telefon, ram_baslangic_tarihi, ram_bitis_tarihi FROM students WHERE adi = 'Bilal' AND soyadi = 'Akyıldız' AND deleted_at IS NULL\n\n";
        
        $context .= "Örnek 2 - RAM Raporu olmayan öğrenciler:\n";
        $context .= "SELECT adi, soyadi FROM students WHERE ram_raporu IS NULL AND deleted_at IS NULL LIMIT 20\n\n";

        $context .= "Örnek 3 - 'admin' grubundaki kullanıcılar kimler?:\n";
        $context .= "SELECT u.username, up.first_name, up.last_name FROM users u JOIN user_profiles up ON u.id = up.user_id JOIN auth_groups_users agu ON u.id = agu.user_id WHERE agu.group = 'admin' AND u.deleted_at IS NULL\n\n";
    }

    /**
     * AI'dan kullanıcı sorusuna göre SQL sorguları üretmesini ister (Prompt güncellendi)
     */
    private function generateSQLQueries(string $userMessage, string $context): array
    {
        $systemPrompt = "Sen İkihece'nin veritabanı uzmanı AI asistanısın.

    **GÖREVİN:**
    Kullanıcının sorusunu analiz et ve gerekli verileri çekmek için MySQL SELECT sorguları üret.

    **KURALLAR:**
    1. SADECE SELECT sorguları üret (INSERT, UPDATE, DELETE YASAK).
    2. Her sorgu tek satır olmalı.
    3. Sorguları JSON array formatında döndür: [\"SELECT ...\", \"SELECT ...\"].
    4. Silinmiş kayıtları hariç tutmak için `deleted_at IS NULL` koşulunu UNUTMA.
    5. Gerekirse JOIN kullan.
    6. Öğretmenleri veya belirli bir roldeki kullanıcıları bulmak için `auth_groups_users` tablosuna JOIN yapmalısın. (Örn: `... JOIN auth_groups_users agu ON u.id = agu.user_id WHERE agu.group = 'ogretmen'`)
    7. Tarih filtreleri için YEAR(), MONTH(), DAYOFWEEK() fonksiyonlarını kullan.
    8. Sorgularına LIMIT ekle (genellikle 100 kayıt yeterli).

    **YAŞ HESAPLAMA:**
    TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE()) kullan.

    **ÖRNEK 1:**
    Soru: \"Ahmet Yılmaz'ın veli telefonları nedir?\"
    Çıktı: [\"SELECT veli_anne_telefon, veli_baba_telefon FROM students WHERE adi = 'Ahmet' AND soyadi = 'Yılmaz' AND deleted_at IS NULL\"]

    **ÖRNEK 2:**
    Soru: \"5-8 yaş arasındaki öğrenciler kimler?\"
    Çıktı: [\"SELECT adi, soyadi, dogum_tarihi FROM students WHERE TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE()) BETWEEN 5 AND 8 AND deleted_at IS NULL LIMIT 100\"]

    **ÖNEMLİ:**
    - Eğer soru için SQL gerekli değilse, boş array döndür: [].
    - Her sorgu maksimum 500 karakter olmalı.
    - Türkçe karakter içeren değerleri tam olarak yaz.

    Sadece JSON array döndür, başka açıklama yapma.";

        $userPrompt = $context . "\n\nKullanıcının Sorusu: \"{$userMessage}\"\n\nGerekli SQL sorguları (JSON array):";
        
        $aiResponse = $this->aiService->getChatResponse($userPrompt, $systemPrompt);
        
        log_message('debug', '🤖 AI SQL Response: ' . $aiResponse);
        
        $aiResponse = trim($aiResponse);
        $aiResponse = str_replace(['```json', '```'], '', $aiResponse);
        $aiResponse = trim($aiResponse);
        
        try {
            $queries = json_decode($aiResponse, true);
            
            if (!is_array($queries)) {
                log_message('error', '❌ AI geçersiz JSON döndürdü: ' . $aiResponse);
                return [];
            }
            
            $inspector = new \App\Libraries\DatabaseInspector();
            $validQueries = [];

            foreach ($queries as $query) {
                $query = trim($query);
                
                $testResult = $inspector->executeQuery($query);
                
                if ($testResult['error']) {
                    log_message('warning', '⛔ SQL Engellendi: ' . $testResult['message']);
                    log_message('warning', '   SQL: ' . substr($query, 0, 100));
                } else {
                    $validQueries[] = $query;
                    log_message('debug', '✅ Geçerli SQL (' . strlen($query) . ' karakter)');
                }
            }
            
            log_message('info', '📊 Üretilen geçerli SQL sayısı: ' . count($validQueries));
            return $validQueries;
            
        } catch (\Exception $e) {
            log_message('error', '💥 SQL parse hatası: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Context'teki tüm verileri analiz edip akıllı cevap üretir (Prompt güncellendi)
     */
    private function getIntelligentResponse(string $context, string $userMessage): string
    {
        $systemPrompt = "Ben İkihece Özel Eğitim Kurumu'nun AI asistanıyım.

    **GÖREVİN:**
    Sana verilen BAĞLAM içindeki veritabanı sorgu sonuçlarını analiz et ve kullanıcının sorusuna profesyonel, anlaşılır bir cevap ver.

    **CEVAP STİLİ:**
    ✅ Net ve öz ol.
    ✅ Sayıları ve önemli isimleri **kalın** yaz.
    ✅ Gerekirse madde imleri (-) veya numaralı liste kullan.
    ✅ Emoji kullanabilirsin (ama abartma).
    ✅ Mümkünse kısa bir öneri veya ek bilgi sun.

    ❌ Çok uzun paragraflar yazma.
    ❌ Teknik SQL terimleri veya sütun adları kullanma (örn: `veli_anne_adi_soyadi` yerine 'Anne Adı' de).
    ❌ Gereksiz detay verme.
    ❌ BAĞLAM'da olmayan bilgi uydurma.

    **ÖNEMLİ:**
    - Eğer BAĞLAM'da hiç veri yoksa, \"Bu konuda veritabanında bir bilgi bulamadım.\" gibi net bir cevap ver.
    - Sayısal verileri mutlaka belirt.
    - İsimler varsa mutlaka yaz.
    - Karşılaştırma yapılabilirse yap.

    **ÖRNEK 1:**
    Soru: \"Ahmet Yılmaz'ın veli telefonları nedir?\"
    BAĞLAM: [SQL SONUCU: veli_anne_telefon=05321234567, veli_baba_telefon=05421234567]
    Cevap: \"**Ahmet Yılmaz**'ın veli iletişim bilgileri:\n- Anne Telefonu: 0532 123 45 67\n- Baba Telefonu: 0542 123 45 67\"

    **ÖRNEK 2:**
    Soru: \"RAM raporu olmayan öğrenciler?\"
    BAĞLAM: [2 öğrenci bulundu: Ali Veli, Ayşe Fatma]
    Cevap: \"RAM raporu sisteme yüklenmemiş **2 öğrenci** bulunmaktadır:\n\n- **Ali Veli**\n- **Ayşe Fatma**\n\n💡 **Öneri:** Bu öğrencilerin velileriyle iletişime geçerek raporların en kısa sürede kuruma ulaştırılmasını talep edebilirsiniz.\"

    Profesyonel, yardımsever ve samimi bir dil kullan. Kurumu temsil ediyorsun!";

        $userPrompt = $context . "\n\nKullanıcının Sorusu: \"{$userMessage}\"\n\nCevabın:";
        
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }

    /**
     * Sohbet kontrolü - Havadan sudan konuşmalar
     */
    private function checkCasualConversation(string $msg): ?string
    {
        $knowledgeBase = \App\Libraries\IkiheceKnowledgeBase::getCasualResponses();
        
        if (str_contains($msg, 'merhaba') || str_contains($msg, 'selam') || str_contains($msg, 'hey')) {
            $responses = $knowledgeBase['merhaba'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'günaydın') || str_contains($msg, 'gunaydin')) {
            if (isset($knowledgeBase['günaydın'])) {
                $responses = $knowledgeBase['günaydın'];
                return $responses[array_rand($responses)];
            }
        }
        
        if (str_contains($msg, 'iyi geceler') || str_contains($msg, 'iyi gece')) {
            if (isset($knowledgeBase['iyi geceler'])) {
                $responses = $knowledgeBase['iyi geceler'];
                return $responses[array_rand($responses)];
            }
        }
        
        if (str_contains($msg, 'nasılsın') || str_contains($msg, 'nasilsin') || str_contains($msg, 'n\'aber')) {
            $responses = $knowledgeBase['nasılsın'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'teşekkür') || str_contains($msg, 'tesekkur') || str_contains($msg, 'sağol') || str_contains($msg, 'sagol') || str_contains($msg, 'eyvallah')) {
            $responses = $knowledgeBase['teşekkür'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'şaka') || str_contains($msg, 'saka') || str_contains($msg, 'espri') || str_contains($msg, 'gülsene')) {
            $responses = $knowledgeBase['şaka'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'yoruldum') || str_contains($msg, 'bıktım') || str_contains($msg, 'biktim') || str_contains($msg, 'zor')) {
            $responses = $knowledgeBase['yoruldum'];
            return $responses[array_rand($responses)];
        }
        
        return null; // Casual değilse normal işleme devam et
    }

    /**
     * Sistem kullanım rehberi
     */
    private function handleSystemGuide(string $msg): ?string
    {
        $knowledgeBase = \App\Libraries\IkiheceKnowledgeBase::class;
        $faq = $knowledgeBase::getFAQ();
        
        foreach ($faq as $question => $answer) {
            if ($this->fuzzyMatch($msg, $question)) {
                return "📌 **" . ucfirst($question) . "?**\n\n" . $answer;
            }
        }
        
        if (str_contains($msg, 'rehber') || str_contains($msg, 'kullanım') || str_contains($msg, 'yardım') || str_contains($msg, 'nasıl kullanılır')) {
            return $knowledgeBase::getSystemGuide();
        }
        
        return null;
    }

    /**
     * SQL çalıştırma ve sonuçları context'e ekleme
     */
    private function executeSQLQuery(string $sql, string &$context): void
    {
        log_message('debug', '🔍 SQL Çalıştırılıyor: ' . substr($sql, 0, 100) . '...');
        
        $inspector = new \App\Libraries\DatabaseInspector();
        $result = $inspector->executeQuery($sql);
        
        $context .= "\n" . str_repeat("-", 70) . "\n";
        $context .= "SQL SORGUSU:\n" . substr($sql, 0, 200) . (strlen($sql) > 200 ? '...' : '') . "\n\n";
        
        if ($result['error']) {
            $context .= "❌ HATA: {$result['message']}\n";
            log_message('error', '💥 SQL Hatası: ' . $result['message']);
            return;
        }
        
        $count = $result['count'];
        $results = $result['data'];
        
        $context .= "✅ Başarılı: {$count} kayıt bulundu\n\n";
        if ($count > 0) {
            $isAggregate = preg_match('/\b(COUNT|SUM|AVG|MAX|MIN)\s*\(/i', $sql);
            
            if ($isAggregate) {
                foreach ($results as $i => $row) {
                    $context .= "Sonuç " . ($i + 1) . ":\n";
                    foreach ($row as $key => $value) {
                        $displayValue = $value ?? 'NULL';
                        $context .= "  {$key}: {$displayValue}\n";
                    }
                    $context .= "\n";
                }
            } else {
                $limit = min(20, $count);
                
                $context .= "İlk {$limit} kayıt:\n\n";
                
                for ($i = 0; $i < $limit; $i++) {
                    $context .= "Kayıt " . ($i + 1) . ":\n";
                    foreach ($results[$i] as $key => $value) {
                        $displayValue = $value ?? 'NULL';
                        if (is_string($displayValue) && strlen($displayValue) > 500) {
                            $displayValue = substr($displayValue, 0, 500) . '... [' . strlen($displayValue) . ' karakter]';
                        }
                        $context .= "  {$key}: {$displayValue}\n";
                    }
                    $context .= "\n";
                }
                
                if ($count > 20) {
                    $context .= "⚠️ [Toplam {$count} kayıt var, ilk 20'si gösteriliyor]\n";
                    $context .= "⚠️ SADECE bu 20 kayıttaki bilgileri kullan, diğerleri için tahminde bulunma!\n\n";
                }
            }
        } else {
            $context .= "Bu sorgu için sonuç bulunamadı.\n";
        }
    }
}