<?php

namespace App\Controllers\AI;

use App\Libraries\AIService;
use CodeIgniter\Controller;

abstract class BaseAIController extends Controller
{
    protected AIService $aiService;
    
    public function __construct()
    {
        $this->aiService = new AIService();
    }
    
    abstract public function process(string $userMessage, object $user): string;
    
    protected function turkish_strtolower(string $text): string
    {
        $search  = ['İ', 'I', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'];
        $replace = ['i', 'ı', 'ğ', 'ü', 'ş', 'ö', 'ç'];
        $text = str_replace($search, $replace, $text);
        return mb_strtolower($text, 'UTF-8');
    }
    
    protected function containsKeywords(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }
    
    protected function extractDateFromMessage(string $msg): string
    {
        $msg = $this->turkish_strtolower($msg);
        
        // 1. Bugün
        if (str_contains($msg, 'bugün')) {
            return date('Y-m-d');
        }
        
        // 2. Yarın
        if (str_contains($msg, 'yarın')) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        
        // 3. Gün isimleri (Pazartesi, Salı, vs.)
        $gunler = [
            'pazartesi' => 'monday',
            'salı' => 'tuesday',
            'çarşamba' => 'wednesday',
            'perşembe' => 'thursday',
            'cuma' => 'friday',
            'cumartesi' => 'saturday',
            'pazar' => 'sunday'
        ];
        
        foreach ($gunler as $tr => $en) {
            if (str_contains($msg, $tr)) {
                // Eğer bugün o gün ise, bir sonraki haftayı al
                $today = strtolower(date('l'));
                if ($today === $en) {
                    return date('Y-m-d', strtotime('next ' . $en));
                }
                // Değilse bu haftaki o günü al
                return date('Y-m-d', strtotime('next ' . $en));
            }
        }
        
        // 4. Tarih formatları: gg.aa.yyyy
        if (preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $msg, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        
        // 5. Tarih formatları: gg.aa.yy (kısa yıl)
        if (preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{2})/', $msg, $m)) {
            $year = 2000 + (int)$m[3];
            return sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]);
        }
        
        // 6. ISO format: yyyy-mm-dd
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $msg, $m)) {
            return $m[0];
        }
        
        // Varsayılan: Bugün
        return date('Y-m-d');
    }
    
    protected function extractMonthFromMessage(string $msg): string
    {
        if (str_contains($msg, 'geçen ay')) {
            return date('Y-m', strtotime('-1 month'));
        }
        if (str_contains($msg, 'bu ay')) {
            return date('Y-m');
        }
        if (preg_match('/(\d{4})-(\d{2})/', $msg, $m)) {
            return $m[0];
        }
        return date('Y-m', strtotime('-1 month'));
    }
    
    /**
     * Mesajdan öğrenci ID'sini bulur (STUDENTS TABLOSUNDAN)
     */
    protected function findStudentIdInMessage(string $msg): ?int
{
    $studentModel = new \App\Models\StudentModel();
    
    // Tüm öğrencileri çek
    $students = $studentModel
        ->select('id, adi, soyadi')
        ->where('deleted_at', null)
        ->asArray()
        ->findAll();
    
    // RAM raporu analizi ile AYNI MANTIK
    foreach ($students as $s) {
        $fullName = $s['adi'] . ' ' . $s['soyadi'];
        $lowerName = $this->turkish_strtolower($fullName);
        $msgLower = $this->turkish_strtolower($msg);
        
        if (str_contains($msgLower, $lowerName)) {
            return $s['id'];
        }
    }
    
    return null;
}
    
    /**
     * DÜZELTME: Hem object hem array desteği
     */
    protected function findSystemUserIdInMessage(string $userMessageLower): ?int
    {
        $profiles = (new \App\Models\UserProfileModel())->select('user_id, first_name, last_name')->findAll();
        foreach ($profiles as $profile) {
            // Array veya object olabilir
            $firstName = is_object($profile) ? ($profile->first_name ?? '') : ($profile['first_name'] ?? '');
            $lastName = is_object($profile) ? ($profile->last_name ?? '') : ($profile['last_name'] ?? '');
            $userId = is_object($profile) ? ($profile->user_id ?? 0) : ($profile['user_id'] ?? 0);
            
            $fullNameLower = $this->turkish_strtolower(trim($firstName . ' ' . $lastName));
            if (!empty($fullNameLower) && str_contains($userMessageLower, $fullNameLower)) {
                return (int) $userId;
            }
        }
        return null;
    }
    
    /**
     * DÜZELTME: Kurum bilgileri - object kullanımı, doğru sütun isimleri
     */
    protected function buildInstitutionContext(string &$context): void
    {
        $institution = (new \App\Models\InstitutionModel())->first();
        if ($institution) {
            $context .= "\n=== KURUM BİLGİLERİ ===\n";
            $context .= "Kurum Kodu: " . ($institution->kurum_kodu ?? '-') . "\n";
            $context .= "Kurum Adı: " . ($institution->kurum_adi ?? '-') . "\n";
            $context .= "Kısa Adı: " . ($institution->kurum_kisa_adi ?? '-') . "\n";
            $context .= "Adres: " . ($institution->adresi ?? '-') . "\n";
            $context .= "Açılış Tarihi: " . ($institution->acilis_tarihi ?? '-') . "\n";
            $context .= "Web Sayfası: " . ($institution->web_sayfasi ?? '-') . "\n";
            $context .= "E-posta: " . ($institution->epostasi ?? '-') . "\n";
            $context .= "Sabit Telefon: " . ($institution->sabit_telefon ?? '-') . "\n";
            $context .= "Telefon: " . ($institution->telefon ?? '-') . "\n";
            $context .= "Kurucu Tipi: " . ($institution->kurucu_tipi ?? '-') . "\n";
            $context .= "Şirket Adı: " . ($institution->sirket_adi ?? '-') . "\n";
            $context .= "Kurucu Temsilci TCKN: " . ($institution->kurucu_temsilci_tckn ?? '-') . "\n";
            $context .= "Vergi Dairesi: " . ($institution->kurum_vergi_dairesi ?? '-') . "\n";
            $context .= "Vergi No: " . ($institution->kurum_vergi_no ?? '-') . "\n";
        }
    }

    /**
     * Referans menü formatı oluşturur (AI yanıtlarında kullanılır)
     */
    protected function createReferenceMenu(string $title, array $options): string
    {
        $menu = "\n### 📌 {$title}\n\n";
        $menu .= "Aşağıdaki sorulardan birini seçebilir veya benzer şekilde sorabilirsiniz:\n\n";
        
        foreach ($options as $i => $option) {
            $menu .= ($i + 1) . ". {$option}\n";
        }
        
        return $menu;
    }
    
    /**
     * DÜZELTME: Kullanıcı profil - object/array desteği
     */
    protected function buildUserContext(string &$context, object $user, string $roleName): void
    {
        $userProfile = (new \App\Models\UserProfileModel())->where('user_id', $user->id)->first();
        
        $firstName = '';
        $lastName = '';
        if ($userProfile) {
            if (is_object($userProfile)) {
                $firstName = $userProfile->first_name ?? '';
                $lastName = $userProfile->last_name ?? '';
            } else {
                $firstName = $userProfile['first_name'] ?? '';
                $lastName = $userProfile['last_name'] ?? '';
            }
        }
        
        $userName = trim($firstName . ' ' . $lastName) ?: $user->username;

        $context .= "=== AKTİF KULLANICI ===\n";
        $context .= "Adı Soyadı: {$userName}\n";
        $context .= "Sistemdeki Rolü: {$roleName}\n";
    }

    /**
     * Fuzzy keyword matching - Yazım hatalarına toleranslı arama
     * 
     * @param string $text Aranacak metin
     * @param array $keywords Anahtar kelimeler ve eş anlamlıları
     * @return bool
     */
    protected function fuzzyContainsKeywords(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            // Eğer array ise (eş anlamlılar varsa)
            if (is_array($keyword)) {
                foreach ($keyword as $synonym) {
                    if ($this->fuzzyMatch($text, $synonym)) {
                        return true;
                    }
                }
            } else {
                // Tek kelime
                if ($this->fuzzyMatch($text, $keyword)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * İki string arasında fuzzy matching
     * Levenshtein distance kullanarak benzerlik kontrolü
     */
    protected function fuzzyMatch(string $haystack, string $needle): bool
    {
        // 1. Tam eşleşme (en hızlı)
        if (str_contains($haystack, $needle)) {
            return true;
        }
        
        // 2. Kısmi eşleşme - kelimeleri parçalara böl
        $words = explode(' ', $haystack);
        foreach ($words as $word) {
            // Benzerlik oranı hesapla (Levenshtein distance)
            $similarity = $this->calculateSimilarity($word, $needle);
            
            // %80 ve üzeri benzerlik varsa kabul et
            if ($similarity >= 0.80) {
                return true;
            }
            
            // Kısa kelimeler için daha toleranslı ol
            if (strlen($needle) <= 5 && $similarity >= 0.70) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * İki kelime arasındaki benzerlik oranını hesapla (0-1 arası)
     */
    protected function calculateSimilarity(string $word1, string $word2): float
    {
        // Boş kontrolü
        if (empty($word1) || empty($word2)) {
            return 0.0;
        }
        
        $word1 = $this->turkish_strtolower($word1);
        $word2 = $this->turkish_strtolower($word2);
        
        // Tam eşleşme
        if ($word1 === $word2) {
            return 1.0;
        }
        
        // Uzunluk farkı çok büyükse direk düşük skor ver
        $lenDiff = abs(strlen($word1) - strlen($word2));
        $maxLen = max(strlen($word1), strlen($word2));
        
        if ($lenDiff > $maxLen * 0.5) {
            return 0.0;
        }
        
        // Levenshtein distance hesapla
        $distance = levenshtein($word1, $word2);
        
        // Benzerlik oranına çevir (0-1 arası)
        $similarity = 1 - ($distance / $maxLen);
        
        return max(0.0, $similarity);
    }
}