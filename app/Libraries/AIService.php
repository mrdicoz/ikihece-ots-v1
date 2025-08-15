<?php

namespace App\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AIService
{
    private Client $client;
    private string $apiKey;
    private string $apiBaseUrl = 'https://api.deepseek.com/v1/'; // DeepSeek API adresi

    public function __construct()
    {
        // .env dosyasından DeepSeek API anahtarını alacağız
        $this->apiKey = env('DEEPSEEK_API_KEY'); 
        $this->client = new Client([
            'base_uri' => $this->apiBaseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]
        ]);
    }

    /**
     * Verilen prompt'a göre DeepSeek API'sinden bir sohbet yanıtı alır.
     *
     * @param string $prompt Kullanıcının sorusu ve bağlam
     * @return string Yapay zeka tarafından üretilen yanıt
     */
   public function getChatResponse(string $userPrompt, string $systemPrompt = ''): string
{
    if (empty($this->apiKey)) {
        return "DeepSeek API anahtarı bulunamadı. Lütfen .env dosyasını kontrol edin.";
    }
    
    try {
        $messages = [];

        // Eğer bir sistem prompt'u varsa, onu ilk mesaj olarak ekle
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // Kullanıcının prompt'unu ekle
        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        $payload = [
            'model'    => 'deepseek-chat',
            'messages' => $messages
        ];

        $response = $this->client->post('chat/completions', [
            'json' => $payload
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if (isset($body['choices'][0]['message']['content'])) {
            return trim($body['choices'][0]['message']['content']);
        }

        log_message('error', 'Beklenmedik DeepSeek API yanıtı: ' . json_encode($body));
        return "Yapay zeka yanıtı alınamadı veya formatı anlaşılamadı.";

    } catch (GuzzleException $e) {
        log_message('error', 'DeepSeek API Hatası: ' . $e->getMessage());
        return "Üzgünüm, şu an yapay zeka servisine erişemiyorum. Lütfen daha sonra tekrar deneyin.";
    }
}
}