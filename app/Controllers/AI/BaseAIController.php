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
    
    /**
     * AI işlemini gerçekleştirir.
     * @param string $userMessage Kullanıcı mesajı
     * @param object $user Aktif kullanıcı
     * @param array $history Sohbet geçmişi
     */
    public function process(string $userMessage, object $user, array $history = []): string
    {
        // Kullanıcı rolüne göre sistem mesajını özelleştir
        $role = $this->getUserRole($user);
        $systemPrompt = $this->getSystemPrompt($role, $user);

        return $this->aiService->getChatResponse($userMessage, $systemPrompt, $history);
    }

    protected function getUserRole($user): string
    {
        // Bu kısım projenin auth yapısına göre değişebilir, 
        // şimdilik basitçe varsayalım veya DB'den çekelim.
        // AuthGroupsUsersModel kullanılabilir ama basitlik adına:
        return 'Kullanıcı'; 
    }

    protected function getSystemPrompt(string $role, object $user): string
    {
        $userName = $user->username ?? 'Kullanıcı';
        
        return "Sen 'Pusula' adında, İkihece Okul Takip Sistemi (OTS) için çalışan zeki ve yardımsever bir yapay zeka asistanısın.
        Şu an '$userName' isimli bir '$role' ile konuşuyorsun.
        
        GÖREVLERİN:
        1. Kullanıcının sorularına MEVCUT VERİLERİ kullanarak net ve doğru cevaplar vermek.
        2. Asla veri tabanında olmayan bir bilgiyi uydurmamak (Halüsinasyon görme).
        3. Öğrenci, öğretmen, ders programı, gelişim raporları ve RAM raporları hakkında detaylı analizler sunmak.
        4. Eğer sorunun cevabını bilmiyorsan veya veri yoksa, bunu dürüstçe belirtmek.
        
        ÜSLUBUN:
        - Profesyonel, nazik ve çözüm odaklı ol.
        - Türkçe dil kurallarına dikkat et.
        - Cevaplarını maddeler halinde veya tablolarla düzenleyerek okunabilirliği artır.
        
        ÖNEMLİ:
        - Kullanıcı 'bugün boş dersler' dediğinde, mevcut tarih ve saati dikkate al.
        - 'Potansiyel öğrenci' önerirken yaş grubu ve tanı benzerliğine dikkat et.
        ";
    }
}