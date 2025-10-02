<?php

namespace App\Controllers\AI;

class MudurAIController extends AdminAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);
        
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context, $user, 'Müdür');
        $this->buildInstitutionContext($context);
        
        // SQL sorgusu varsa çalıştır
        $sqlQuery = $this->extractSQLFromMessage($userMessage);
        if ($sqlQuery) {
            $this->executeSQLQuery($sqlQuery, $context);
        }
        
        // Veritabanı erişimi
        if ($this->containsKeywords($userMessageLower, ['veritabanı', 'tablo', 'sql', 'sorgu'])) {
            $this->buildDatabaseSchemaContext($context);
        }
        
        // Rapor talebi
        if ($this->containsKeywords($userMessageLower, ['rapor', 'özet', 'istatistik'])) {
            $this->buildReportContext($context, $userMessageLower);
        }
        
        // Log talebi
        if ($this->containsKeywords($userMessageLower, ['log', 'işlem', 'kayıt'])) {
            $this->buildLogContext($context);
        }
        
        // Öğretmen bilgileri
        if ($this->containsKeywords($userMessageLower, ['kaç öğretmen', 'öğretmen sayısı'])) {
            $this->buildTeacherCountContext($context);
        }
        if ($this->containsKeywords($userMessageLower, ['öğretmenleri listele', 'öğretmen listesi'])) {
            $this->buildTeacherListContext($context);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = "Sen İkihece Özel Eğitim Kurumu'nun AI asistanısın.

**Şu an MÜDÜR ile konuşuyorsun.**

Stratejik kararlar için üst düzey özetler ve öneriler sun. Yetkiler:
- VERİTABANINA TAM ERİŞİM
- SQL sorguları çalıştırabilirsin (SADECE SELECT)
- Detaylı veritabanı analizleri yapabilirsin
- Kurumun genel performansını değerlendirebilirsin
- Stratejik önerilerde bulunabilirsin

**Odaklanman Gereken Alanlar:**
- Kurum geneli performans metrikleri
- Öğretmen ve öğrenci istatistikleri
- Finansal ve operasyonel verimlilik
- Stratejik planlama için veri analizi

Üst düzey yönetim dili kullan. Net, özet ve aksiyona dönük cevaplar ver.";
        
        $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }
}