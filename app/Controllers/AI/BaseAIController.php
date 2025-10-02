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
        if (str_contains($msg, 'bugün')) {
            return date('Y-m-d');
        }
        if (str_contains($msg, 'yarın')) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $msg, $m)) {
            return $m[0];
        }
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $msg, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
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
     * DÜZELTME: Hem object hem array desteği
     */
    protected function findStudentIdInMessage(string $userMessageLower): ?int
    {
        $students = (new \App\Models\StudentModel())->select('id, adi, soyadi')->findAll();
        foreach ($students as $student) {
            // Array veya object olabilir
            $adi = is_object($student) ? ($student->adi ?? '') : ($student['adi'] ?? '');
            $soyadi = is_object($student) ? ($student->soyadi ?? '') : ($student['soyadi'] ?? '');
            $id = is_object($student) ? ($student->id ?? 0) : ($student['id'] ?? 0);
            
            $fullNameLower = $this->turkish_strtolower(trim($adi . ' ' . $soyadi));
            if (!empty($fullNameLower) && str_contains($userMessageLower, $fullNameLower)) {
                return (int) $id;
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
}