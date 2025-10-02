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
                // Öğretmen Analizleri
                'Öğretmenlerin bu ayki ders sayılarını listele',
                'Bu hafta en az ders veren öğretmenler',
                
                // Mesafe Bazlı Analiz
                'Bugün gelmesi muhtemel civar mesafedeki öğrenciler',
                'Yakın mesafeden gelen öğrenciler',
                
                // Eğitim Programı İstatistikleri
                'Otizm programı öğrencileri',
                'Zihinsel programı istatistikleri',
                'Öğrenme güçlüğü programı analizi',
                'Dil ve konuşma programı öğrencileri',
                
                // Sabit Program
                'Sabit programı olan öğrenciler',
                'Sabit programı olmayan ama düzenli gelen öğrenciler',
                
                // Yarın Planlama
                'Yarının sabit programını göster',
                'Yarın boş saatler için öğrenci tavsiyesinde bulun',
                'Kübra Beyter\'in yarın saat 14:00\'deki dersi için alternatif öner',

                // Sistem İstatistikleri
                'Sistem istatistiklerini görüntülemek istiyorum',
                'Toplam öğrenci ve öğretmen sayımız nedir?',
                
                // RAM Raporu
                'RAM raporu olmayan öğrencileri listele',
                'NİSA GÜLENER adlı öğrencinin RAM raporu analizi',
                
                // Ders Hakkı Uyarıları
                'Ders hakkı 10 saatin altında olan öğrenciler',
                'Ders hakkı 5 saatin altında olan öğrenciler',
                
                // Raporlar
                'Bu ayın genel raporunu ver',
                'Son 10 sistem işlemini göster'
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