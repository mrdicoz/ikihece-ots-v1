<?php

namespace App\Controllers;

use App\Models\AuthGroupsUsersModel;
use CodeIgniter\HTTP\ResponseInterface;

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

        if ($currentUser && $currentUser->inGroup('admin', 'yonetici', 'mudur')) {
            $samplePrompts = [
                // Havadan Sudan Sohbet
                'Merhaba asistan, nasılsın?',
                'Bugün nasıl gidiyor?',
                
                // Ana Menüler (Her biri alt menü gösterir)
                'Sistem nasıl kullanılır?',
                'Sistem istatistikleri',
                'Sistem raporları',
                'Veritabanı sorgula',
                'Sabit program',
                'Gelmesi muhtemel öğrenciler',
                
                // Duyuru Taslakları
                'Tatil duyurusu yaz',
                'Veli toplantısı duyurusu yaz',
                
                // İsim Tabanlı Sorgular
                '[Öğretmen ismi yazın - örn: Hilal Varol]',
                '[Öğrenci ismi yazın - örn: Bilal Akyıldız]',
                
                // Hızlı Sorgular (Direkt cevap, menü yok)
                'Ders hakkı 10 saatin altında olan öğrenciler',
                'RAM raporu olmayan öğrenciler',
            ];
        }
        elseif ($currentUser && $currentUser->inGroup('ogretmen')) {
            $samplePrompts = [
                'Bu haftaki ders programım nedir?',
                'Öğrencim [Öğrenci Adı Soyadı]\'nın kalan ders hakları ne kadar?',
                'Yarınki derslerimi listeler misin?',
                'Öğrencim [Öğrenci Adı Soyadı]\'nın veli telefon numarası nedir?',
                'Öğrencim [Öğrenci Adı Soyadı]\'nın RAM raporunda öne çıkanlar nelerdir?',
                'Sabit haftalık ders programımı göster.',
                '[Öğrenci Adı Soyadı] ile hangi konularda çalışmalıyım?',
                '[Öğrenci Adı Soyadı] hakkında diğer öğretmenler ne demiş?'
            ];
        }
        elseif ($currentUser && $currentUser->inGroup('veli')) {
            $samplePrompts = [
                'Çocuğum hangi öğretmenlerle çalışmış?',
                'Çocuğumun kalan ders hakkı ne kadar?',
                'Öğretmenler çocuğum hakkında ne düşünüyor?',
                'Son derslerde nasıl geçmişiz?'
            ];
        }
        elseif ($currentUser && $currentUser->inGroup('sekreter')) {
            $samplePrompts = [
                'Yarın hangi saatlerde boşluk var?',
                'Bu hafta hangi öğrencilerin ders hakkı bitiyor?',
                'Öğretmen [Adı Soyadı] yarın müsait mi?'
            ];
        }
        
        $this->data['samplePrompts'] = $samplePrompts;

        return view('ai/assistant_view', $this->data);
    }

    /**
     * AJAX isteklerini işler ve rol-bazlı controller'lara yönlendirir
     */
    public function processAjax(): ResponseInterface
    {
        $currentUser = auth()->user();
        if ($currentUser === null) {
            return $this->response->setJSON([
                'error' => 'Lütfen önce sisteme giriş yapın.'
            ])->setStatusCode(401);
        }

        $userMessage = trim($this->request->getPost('message'));
        if (empty($userMessage)) {
            return $this->response->setJSON([
                'error' => 'Mesaj boş olamaz.'
            ])->setStatusCode(400);
        }

        try {
            // Kullanıcının aktif rolünü belirle
            $activeRole = session()->get('active_role') ?? ($currentUser->getGroups()[0] ?? 'tanimsiz');
            
            // Role göre uygun controller'ı çağır
            $response = match($activeRole) {
                'admin' => (new \App\Controllers\AI\AdminAIController())->process($userMessage, $currentUser),
                'yonetici' => (new \App\Controllers\AI\YoneticiAIController())->process($userMessage, $currentUser),
                'mudur' => (new \App\Controllers\AI\MudurAIController())->process($userMessage, $currentUser),
                'ogretmen' => (new \App\Controllers\AI\OgretmenAIController())->process($userMessage, $currentUser),
                'veli' => (new \App\Controllers\AI\VeliAIController())->process($userMessage, $currentUser),
                'sekreter' => (new \App\Controllers\AI\SekreterAIController())->process($userMessage, $currentUser),
                default => 'Rolünüz için AI asistanı henüz yapılandırılmamış.'
            };
            
            // Chat history'yi kaydet
            $chatHistory = session()->get('ai_chat_history') ?? [];
            $chatHistory[] = ['user' => $userMessage, 'ai' => $response];
            session()->set('ai_chat_history', $chatHistory);
            
            return $this->response->setJSON([
                'status' => 'success',
                'response' => $response
            ]);
            
        } catch (\Exception $e) {
            log_message('error', '[AIController Hata] ' . $e->getMessage() . '\n' . $e->getTraceAsString());
            return $this->response->setJSON([
                'status' => 'error',
                'response' => 'Bir hata oluştu, lütfen tekrar deneyin.'
            ]);
        }
    }

    /**
     * Chat geçmişini temizler
     */
    public function clearChat(): ResponseInterface
    {
        session()->remove('ai_chat_history');
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Sohbet geçmişi temizlendi.'
        ]);
    }
}