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
                // 🤖 Havadan Sudan Sohbet
                'Merhaba asistan, nasılsın?',
                'Bugün nasıl gidiyor?',
                
                // 📚 Sistem Kullanımı
                'Öğrenci nasıl eklenir?',
                'Ders nasıl eklenir?',
                'RAM raporu nasıl yüklenir?',
                'Toplu öğrenci nasıl yüklenir?',
                'Duyuru nasıl yapılır?',
                'Sabit program nedir ve nasıl kullanılır?',
                'Ders hakkı nasıl güncellenir?',
                'Gelişim notu nasıl yazılır?',
                
                // 📢 Duyuru Taslakları
                'Tatil duyurusu yaz',
                'Veli toplantısı duyurusu yaz',
                'Etkinlik duyurusu yaz',
                
                // 👨‍🏫 Öğretmen Detaylı Analizleri
                '[Öğretmen Adı Soyadı]\'nın detaylı analizini oluştur',
                'Branşlara göre öğretmen dağılımı',
                'Bu ay en çok ders veren öğretmenler',
                
                // 🎯 AKILLI ÖNERİ SİSTEMİ - Sadece Tarih
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel öğrenciler',
                
                // 🎯 AKILLI ÖNERİ SİSTEMİ - Eğitim Programı Filtreleri
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel otizm tanılı öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel zihinsel öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel dil ve konuşma öğrencileri',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel öğrenme güçlüğü öğrencileri',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel bedensel öğrenciler',
                
                // 🎯 AKILLI ÖNERİ SİSTEMİ - Mesafe Filtreleri
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel civar mesafedeki öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel yakın mesafedeki öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel uzak mesafedeki öğrenciler',
                
                // 🎯 AKILLI ÖNERİ SİSTEMİ - Ders Hakkı Filtreleri
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel ders hakkı azalan öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel 10 saatten az hakkı olan öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel 5 saatten az hakkı olan öğrenciler',
                
                // 🎯 AKILLI ÖNERİ SİSTEMİ - Kombine Örnekler
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel otizm tanılı ve civar mesafedeki öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel zihinsel ve ders hakkı azalan öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel yakın mesafedeki ve 5 saatten az hakkı olan öğrenciler',
                '[Bugün/Yarın/gg.aa.yyyy] gelmesi muhtemel dil ve konuşma öğrencileri ve civar mesafede olanlar',
                
                // 📅 Sabit Program ve Planlama
                'Sabit programı olan öğrenciler kimler?',
                '[Bugün/Yarın/gg.aa.yyyy] tarihinin sabit programını göster',
                '[Bugün/Yarın/gg.aa.yyyy] tarihinde boş saatler için öğrenci tavsiyesinde bulun',
                '[Öğretmen Adı Soyadı]\'nın [Bugün/Yarın/gg.aa.yyyy] tarihinde saat [14:00] dersi için alternatif öner',
                '[Bugün/Yarın/gg.aa.yyyy] tarihinde saat [15:00]\'de hangi öğrencileri çağırabilirim?',
                
                // 👨‍🎓 Öğrenci Detaylı Analizleri
                '[Öğrenci Adı Soyadı]\'nın detaylı analizini oluştur',
                '[Öğrenci Adı Soyadı]\'nın gelişim günlüğünü göster',
                '[Öğrenci Adı Soyadı]\'nın RAM raporu analizi',
                
                // 📊 Sistem İstatistikleri
                'Sistem istatistiklerini görüntüle',
                'Toplam öğrenci ve öğretmen sayımız nedir?',
                'Mesafe dağılımı nasıl?',
                'Eğitim programlarına göre öğrenci dağılımı',
                
                // 📄 RAM Raporu
                'RAM raporu olmayan öğrencileri listele',
                'RAM raporu eksik olan öğrenciler',
                
                // ⚠️ Ders Hakkı Uyarıları
                'Ders hakkı 10 saatin altında olan öğrenciler',
                'Ders hakkı 5 saatin altında olan öğrenciler (ACİL)',
                'Ders hakkı bitmek üzere olan öğrencileri listele',
                
                // 📈 Raporlar ve Loglar
                'Bu ayın genel raporunu ver',
                'Geçen ayın raporunu göster',
                'Son 10 sistem işlemini göster',
                
                // 🔍 Veritabanı Sorguları (İleri Seviye)
                'Veritabanı tablolarını göster',
                'Students tablosunda kaç kayıt var?',
                'Bu ay kaç ders yapıldı?',
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