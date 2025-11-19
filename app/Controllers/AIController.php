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
        // Bu metod artık kullanılmıyor olabilir çünkü offcanvas layout'a taşındı, 
        // ancak eski linklerin kırılmaması için tutuyoruz.
        return view('ai/assistant_view', ['title' => 'İkihece AI Asistanı']);
    }

    /**
     * AJAX isteklerini işler ve role-bazlı controller'lara yönlendirir
     */
    public function processAjax(): ResponseInterface
    {
        try {
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

            // Kullanıcının rolünü belirle
            $role = $this->getUserRole($currentUser);
            
            // İlgili AI Controller'ı başlat
            $controller = $this->getAIControllerForRole($role);
            
            if ($controller === null) {
                return $this->response->setJSON([
                    'error' => 'Rolünüz için AI asistanı aktif değil.'
                ])->setStatusCode(403);
            }

            // Sohbet geçmişini al
            $session = session();
            $chatHistory = $session->get('ai_chat_history') ?? [];

            // AI Yanıtını Al (History ile birlikte)
            $aiResponse = $controller->process($userMessage, $currentUser, $chatHistory);

            // Geçmişi Güncelle
            $chatHistory[] = ['role' => 'user', 'content' => $userMessage];
            $chatHistory[] = ['role' => 'assistant', 'content' => $aiResponse];
            
            // Son 10 mesajı tut (Hafıza yönetimi)
            if (count($chatHistory) > 10) {
                $chatHistory = array_slice($chatHistory, -10);
            }

            $session->set('ai_chat_history', $chatHistory);

            return $this->response->setJSON([
                'status' => 'success',
                'response' => $aiResponse
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[AI Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                'status' => 'error',
                'error' => 'Sistem hatası: ' . $e->getMessage() . ' (Dosya: ' . $e->getFile() . ':' . $e->getLine() . ')'
            ])->setStatusCode(200); // JS tarafı hatayı görebilsin diye 200 dönüyoruz
        }
    }

    /**
     * Kullanıcının aktif rolünü döndürür.
     */
    private function getUserRole($user): string
    {
        // Session'da aktif rol varsa onu kullan
        $activeRole = session()->get('active_role');
        if ($activeRole) {
            return $activeRole;
        }

        // Yoksa kullanıcının ilk grubunu al
        $groups = $user->getGroups();
        return $groups[0] ?? 'tanimsiz';
    }

    /**
     * Role göre ilgili AI Controller sınıfını döndürür.
     */
    private function getAIControllerForRole(string $role): ?object
    {
        $role = strtolower($role);
        
        return match($role) {
            'admin' => new \App\Controllers\AI\AdminAIController(),
            'yonetici' => new \App\Controllers\AI\YoneticiAIController(),
            'mudur' => new \App\Controllers\AI\MudurAIController(),
            'ogretmen' => new \App\Controllers\AI\OgretmenAIController(),
            'veli' => new \App\Controllers\AI\VeliAIController(),
            'sekreter' => new \App\Controllers\AI\SekreterAIController(),
            default => null
        };
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