<?php

namespace App\Controllers\AI;

class YoneticiAIController extends AdminAIController
{
    public function process(string $userMessage, object $user): string
    {
        $userMessageLower = $this->turkish_strtolower($userMessage);
        
        $context = "[BAĞLAM BAŞLANGICI]\n";
        $this->buildUserContext($context, $user, 'Yönetici');
        $this->buildInstitutionContext($context);
        
        // SQL sorgusu varsa çalıştır
        $sqlQuery = $this->extractSQLFromMessage($userMessage);
        if ($sqlQuery) {
            $this->executeSQLQuery($sqlQuery, $context);
        }
        
        // Veritabanı erişimi
        $dbKeywords = ['veritabanı', 'database', 'tablo', 'sql', 'sorgu', 'listele', 'kaç tane'];
        if ($this->containsKeywords($userMessageLower, $dbKeywords)) {
            $this->buildDatabaseSchemaContext($context);
        }
        
        // Rapor talebi
        if ($this->containsKeywords($userMessageLower, ['rapor', 'istatistik', 'özet'])) {
            $this->buildReportContext($context, $userMessageLower);
        }
        
        // Log talebi
        if ($this->containsKeywords($userMessageLower, ['log', 'kayıt', 'işlem', 'aktivite'])) {
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

**Şu an bir YÖNETİCİ ile konuşuyorsun.**

Kurum yönetimi ve operasyonel kararlar için detaylı raporlar sun. Yetkiler:
- VERİTABANINA TAM ERİŞİM
- SQL sorguları çalıştırabilirsin (SADECE SELECT)
- Kapsamlı analizler ve istatistikler oluşturabilirsin
- Sistem loglarını inceleyebilirsin
- Öğretmen ve öğrenci bazlı performans raporları sunabilirsin
- Bireysel dersleri toplamını bireysel ders ücreti ile çarpıp net geliri söyleyebilirsin

**SQL Kullanım Kuralları:**
1. Sadece SELECT sorgularına izin var
2. WHERE, GROUP BY, ORDER BY, LIMIT kullanabilirsin
3. JOIN ile tabloları birleştirebilirsin
4. Türkçe sütun isimleri kullan

Stratejik ve veri odaklı cevaplar ver. Profesyonel dil kullan.";
        
        $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }
}