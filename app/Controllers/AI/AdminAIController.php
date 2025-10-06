<?php

namespace App\Controllers\AI;

use App\Libraries\DatabaseInspector;
use App\Models\ReportModel;
use App\Models\LogModel;

class AdminAIController extends BaseAIController
{
    public function process(string $userMessage, object $user): string
        {
            $userMessageLower = $this->turkish_strtolower($userMessage);
            
            // 1️⃣ HAVADAN SUDAN SOHBET (En önce!)
            $casualResponse = $this->checkCasualConversation($userMessageLower);
            if ($casualResponse !== null) {
                return $casualResponse;
            }
            
            // 2️⃣ SİSTEM KULLANIMI REHBERİ
            if ($this->containsKeywords($userMessageLower, ['sistem nasıl kullanılır', 'nasıl kullanırım', 'kullanım rehberi'])) {
                return $this->createReferenceMenu(
                    "Sistem Kullanımı",
                    [
                        "Öğrenci nasıl eklenir?",
                        "Ders nasıl eklenir?",
                        "RAM raporu nasıl yüklenir?",
                        "Toplu öğrenci nasıl yüklenir?",
                        "Duyuru nasıl yapılır?",
                        "Sabit program nedir ve nasıl kullanılır?",
                        "Ders hakkı nasıl güncellenir?",
                        "Gelişim notu nasıl yazılır?"
                    ]
                );
            }
            
            // Sistem kullanımı alt soruları için rehber göster
            if ($this->containsKeywords($userMessageLower, ['nasıl', 'nerede', 'nereden', 'kullanım', 'rehber', 'yardım'])) {
                $guideResponse = $this->handleSystemGuide($userMessageLower);
                if ($guideResponse !== null) {
                    return $guideResponse;
                }
            }
            
            // 3️⃣ DUYURU YAZMA YARDIMI
            if ($this->containsKeywords($userMessageLower, ['duyuru yaz', 'duyuru oluştur', 'duyuru taslağı'])) {
                return "Duyuru yazmak için size yardımcı olabilirim!\n\n" .
                    "✏️ Ne tür bir duyuru yazmak istersiniz?\n" .
                    "📝 Varolan bir metni düzenlememi ister misiniz?\n\n" .
                    "Lütfen detay verin, size yardımcı olayım.";
            }

                            // 6️⃣ ÖĞRETMEN İSMİ TESPİTİ → REFERANS MENU
                $teacherName = $this->extractTeacherName($userMessage);
                if ($teacherName && !$this->containsKeywords($userMessageLower, ['detaylı analiz', 'branş', 'ders sayı'])) {
                    return $this->createReferenceMenu(
                        "{$teacherName} hakkında ne öğrenmek istersiniz?",
                        [
                            "{$teacherName} hoca'nın detaylı analizini oluştur",
                            "{$teacherName} hoca'nın bu ayki ders sayısı",
                            "{$teacherName} hoca'nın iletişim bilgileri"
                        ]
                    );
                }

                // 7️⃣ ÖĞRETMEN DETAYLI ANALİZ
                if ($this->containsKeywords($userMessageLower, ['öğretmen', 'hoca', 'öğretmenin']) && 
                    $this->containsKeywords($userMessageLower, ['detaylı analiz', 'detayli analiz'])) {
                    
                    $context = "[BAĞLAM BAŞLANGICI]\n";
                    $this->buildUserContext($context, $user, 'Admin');
                    $this->buildTeacherDetailedAnalysis($context, $userMessage);
                    $context .= "[BAĞLAM SONU]\n";
                    
                    $systemPrompt = "Sen İkihece'nin yapay zeka asistanısın.

                Öğretmen detaylı analiz verilerini NET ve PROFESYONEL şekilde sun:

                **SUNUM STİLİ:**
                - Kategorilere göre başlıklar kullan (KİŞİSEL BİLGİLER, DERS İSTATİSTİKLERİ)
                - Önemli sayıları **kalın** yaz
                - Pozitif performansı vurgula
                - Geliştirme önerileri sun

                Profesyonel ve saygılı ol.";
                    
                    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
                    
                    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
                }
                    

            
            // 4️⃣ ÖĞRENCİ İSMİ TESPİTİ → REFERANS MENU
            $studentName = $this->extractStudentName($userMessage);
            if ($studentName && !$this->containsKeywords($userMessageLower, ['detaylı analiz', 'gelişim', 'ram rapor'])) {
                return $this->createReferenceMenu(
                    "{$studentName} hakkında ne öğrenmek istersiniz?",
                    [
                        "{$studentName}'nın detaylı analizini oluştur",
                        "{$studentName}'nın gelişim günlüğünü göster",
                        "{$studentName}'nın RAM raporu analizi"
                    ]
                );
            }
            
// 5️⃣ ÖĞRENCİ DETAYLI ANALİZ
if ($this->containsKeywords($userMessageLower, ['detaylı analiz', 'detayli analiz'])) {
    
    log_message('debug', '===== ÖĞRENCİ ANALİZİ TETİKLENDİ =====');
    
    $context = "[BAĞLAM BAŞLANGICI]\n";
    $this->buildUserContext($context, $user, 'Admin');
    $this->buildStudentDetailedAnalysis($context, $userMessage);
    $context .= "[BAĞLAM SONU]\n";
    
    // DEBUG: Context'i kontrol et
    log_message('debug', 'CONTEXT: ' . substr($context, 0, 1000));
    
    $systemPrompt = "Sen İkihece Özel Eğitim Kurumu'nun yapay zeka asistanısın.

**ÖNEMLİ:** Sana verilen BAĞLAM içindeki verileri AYNEN kullan. Hiçbir şeyi hayal etme veya uydurma!

Öğrenci detaylı analiz verilerini şu formatta sun:

## 👤 KİŞİSEL BİLGİLER
[Bağlamdaki verileri kullan]

## 📞 İLETİŞİM BİLGİLERİ
[Bağlamdaki verileri kullan]

## 🎓 DERS HAKKI
[Bağlamdaki verileri kullan]

## 📊 DERS İSTATİSTİKLERİ
[Bağlamdaki verileri kullan]

## 👨‍🏫 ÇALIŞILAN ÖĞRETMENLER
[Bağlamdaki verileri kullan]

## 📄 RAM RAPORU DURUMU
[Bağlamdaki verileri kullan]

## 📝 GELİŞİM NOTLARI
[Bağlamdaki verileri kullan]

## 💡 ÖNERİLER
[Verilere dayanarak önerilerde bulun]

**KURALLLAR:**
- Sadece bağlamdaki gerçek verileri kullan
- [...] işaretli yerler YASAK
- Tüm sayılar gerçek olmalı
- Eğer bir veri yoksa 'Yok' veya 'Belirtilmemiş' yaz";

    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
    
    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
}
            
        
// 7️⃣ SİSTEM İSTATİSTİKLERİ → REFERANS MENU
if ($this->containsKeywords($userMessageLower, ['sistem istatistik', 'genel istatistik', 'istatistikleri göster'])) {
    return $this->createReferenceMenu(
        "Sistem İstatistikleri",
        [
            "Toplam öğrenci ve öğretmen sayımız nedir?",
            "Mesafe dağılımı nasıl?",
            "Eğitim programlarına göre öğrenci dağılımı",
            "Branşlara göre öğretmen dağılımı"
        ]
    );
}

// 7️⃣-A SİSTEM İSTATİSTİKLERİ - DETAYLI SORGULAR
if ($this->containsKeywords($userMessageLower, ['toplam öğrenci', 'toplam öğretmen', 'kaç öğrenci', 'kaç öğretmen', 'mesafe dağılım', 'eğitim program', 'branş'])) {
    $context = "[BAĞLAM BAŞLANGICI]\n";
    $this->buildUserContext($context, $user, 'Admin');
    $this->buildSystemStatisticsContext($context);
    
    // Branş bilgisi istendiyse ekle
    if ($this->containsKeywords($userMessageLower, ['branş', 'branşlara göre'])) {
        $this->buildTeacherBranchDistribution($context);
    }
    
    $context .= "[BAĞLAM SONU]\n";
    
    $systemPrompt = "Sen İkihece'nin yapay zeka asistanısın. Sistem istatistiklerini net ve öz şekilde sun. Sayıları vurgula.";
    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
    
    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
}
            
// 8️⃣ SİSTEM RAPORLARI → REFERANS MENU
if ($this->containsKeywords($userMessageLower, ['sistem rapor', 'raporları göster', 'raporlar'])) {
    return $this->createReferenceMenu(
        "Sistem Raporları",
        [
            "Bu ayın genel raporunu ver",
            "Geçen ayın raporunu göster",
            "Son 10 sistem işlemini göster"
        ]
    );
}

// 8️⃣-A SİSTEM RAPORLARI - DETAYLI SORGULAR
if ($this->containsKeywords($userMessageLower, ['ayın rapor', 'aylık rapor', 'rapor'])) {
    $context = "[BAĞLAM BAŞLANGICI]\n";
    $this->buildUserContext($context, $user, 'Admin');
    $this->buildReportContext($context, $userMessageLower);
    $context .= "[BAĞLAM SONU]\n";
    
    $systemPrompt = "Sen İkihece'nin yapay zeka asistanısın. Aylık raporları özetli ve net şekilde sun. Önemli metrikleri vurgula.";
    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
    
    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
}

// 8️⃣-B SİSTEM LOGLARİ
if ($this->containsKeywords($userMessageLower, ['sistem işlem', 'log', 'son işlem'])) {
    $context = "[BAĞLAM BAŞLANGICI]\n";
    $this->buildUserContext($context, $user, 'Admin');
    $this->buildLogContext($context);
    $context .= "[BAĞLAM SONU]\n";
    
    $systemPrompt = "Sen İkihece'nin yapay zeka asistanısın. Sistem işlemlerini kronolojik sırayla özetle.";
    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
    
    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
}
            
// 9️⃣ VERİTABANI SORGULARI → REFERANS MENU
if ($this->containsKeywords($userMessageLower, ['veritabanı sorgula', 'database sorgu'])) {
    return $this->createReferenceMenu(
        "Veritabanı Sorguları",
        [
            "Veritabanı tablolarını göster",
            "Students tablosunda kaç kayıt var?",
            "Users tablosunda kaç kayıt var?",
            "Bu ay kaç ders yapıldı?"
        ]
    );
}

// 9️⃣-A VERİTABANI ŞEMASI VE SORGULAR
if ($this->containsKeywords($userMessageLower, ['veritabanı', 'database', 'tablo', 'sql', 'kaç kayıt', 'tablosunda'])) {
    // SQL sorgusu varsa çalıştır
    $sqlQuery = $this->extractSQLFromMessage($userMessage);
    
    $context = "[BAĞLAM BAŞLANGICI]\n";
    $this->buildUserContext($context, $user, 'Admin');
    $this->buildDatabaseSchemaContext($context);
    
    if ($sqlQuery) {
        $this->executeSQLQuery($sqlQuery, $context);
    }
    
    $context .= "[BAĞLAM SONU]\n";
    
    $systemPrompt = "Sen İkihece'nin yapay zeka asistanısın. Veritabanı bilgilerini teknik ama anlaşılır şekilde sun. SADECE SELECT sorguları kullanılabilir.";
    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
    
    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
}

// 🔟 SABİT PROGRAM → REFERANS MENU
if ($this->containsKeywords($userMessageLower, ['sabit program']) && 
    !$this->containsKeywords($userMessageLower, ['olmayan', 'düzenli', 'analiz'])) {
    return $this->createReferenceMenu(
        "Sabit Program Sorguları",
        [
            "Sabit programı olmayan ama düzenli gelen öğrenciler",
            "Sabit program analizi yap"
        ]
    );
}

// 🔟-A SABİT PROGRAM ANALİZİ - DETAYLI SORGULAR
if ($this->containsKeywords($userMessageLower, ['sabit program']) && 
    $this->containsKeywords($userMessageLower, ['olmayan', 'düzenli', 'analiz'])) {
    
    $context = "[BAĞLAM BAŞLANGICI]\n";
    $this->buildUserContext($context, $user, 'Admin');
    $this->buildFixedProgramAnalysisContext($context, $userMessage);
    $context .= "[BAĞLAM SONU]\n";
    
    $systemPrompt = "Sen İkihece'nin yapay zeka asistanısın. Sabit program analizini net şekilde sun. Önerileri somut olarak ver.";
    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
    
    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
}
            
// 1️⃣1️⃣ AKILLI ÖNERİ SİSTEMİ
if ($this->containsKeywords($userMessageLower, ['gelmesi muhtemel', 'gelecek öğrenci', 'kimler gelecek'])) {
    
    // Tarih kontrolü
    $hasDate = $this->containsKeywords($userMessageLower, ['bugün', 'yarın', 'pazartesi', 'salı', 'çarşamba', 'perşembe', 'cuma', 'cumartesi', 'pazar']);
    
    if (!$hasDate) {
        // Filtre seçeneklerini göster
        return "### 🎯 Akıllı Öneri Sistemi\n\n" .
            "Gelmesi muhtemel öğrencileri öğrenmek için **hangi gün** ve **hangi filtrelerle** sormak istersiniz?\n\n" .
            "**📅 Tarih Seçenekleri:**\n" .
            "• Bugün\n• Yarın\n• Pazartesi/Salı/Çarşamba/Perşembe/Cuma\n• Belirli bir tarih (gg.aa.yyyy)\n\n" .
            "**🎓 Eğitim Programı Filtreleri:**\n" .
            "• otizm tanılı\n• zihinsel yetersizlik\n• dil ve konuşma bozukluğu\n• öğrenme güçlüğü\n• bedensel yetersizlik\n\n" .
            "**📍 Mesafe Filtreleri:**\n" .
            "• civar mesafedeki (0-5 km)\n• yakın mesafedeki (5-15 km)\n• uzak mesafedeki (15+ km)\n\n" .
            "**⏰ Ders Hakkı Filtreleri:**\n" .
            "• ders hakkı azalan (10 saatten az)\n• ders hakkı çok az (5 saatten az)\n• ders hakkı kritik (2 saatten az)\n\n" .
            "**💡 Örnek Kullanımlar:**\n" .
            "• 'Yarın gelmesi muhtemel öğrenciler'\n" .
            "• 'Bugün gelmesi muhtemel otizm tanılı öğrenciler'\n" .
            "• 'Yarın civar mesafedeki ve ders hakkı azalan öğrenciler'\n" .
            "• 'Pazartesi gelmesi muhtemel zihinsel yetersizlikli ve yakın mesafedeki öğrenciler'";
    }
    
    // Tarih var, analiz yap
    $context = "[BAĞLAM BAŞLANGICI]\n";
    $this->buildUserContext($context, $user, 'Admin');
    $this->buildSmartSuggestions($context, $userMessageLower);
    $context .= "[BAĞLAM SONU]\n";
    
    $systemPrompt = "Sen İkihece'nin yapay zeka asistanısın. 

Gelmesi muhtemel öğrencileri şu formatta sun:

**🎯 [TARİH] İÇİN ÖNERİLEN ÖĞRENCİLER**

Her öğrenci için:
- **Öğrenci Adı Soyadı**
  - Geliş İhtimali: % [oran]
  - Son 90 günde bu günde: [X] kez geldi
  - Mesafe: [mesafe]
  - Ders Hakkı: [hak durumu]
  - Eğitim Programı: [program]

Öğrencileri ihtimal yüzdesine göre sırala (en yüksekten en düşüğe).";
    
    $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
    
    return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
}
            
            // 12 NORMAL CONTEXT - DİĞER TÜM SORGULAR
            $context = "[BAĞLAM BAŞLANGICI]\n";
            $this->buildUserContext($context, $user, 'Admin');
            $this->buildInstitutionContext($context);
            
            // SQL sorgusu varsa çalıştır
            $sqlQuery = $this->extractSQLFromMessage($userMessage);
            if ($sqlQuery) {
                $this->executeSQLQuery($sqlQuery, $context);
            }
            
            // Veritabanı şeması talebi
            if ($this->containsKeywords($userMessageLower, ['veritabanı', 'database', 'tablo', 'sql', 'şema'])) {
                $this->buildDatabaseSchemaContext($context);
            }
            
            // Rapor talebi
            if ($this->containsKeywords($userMessageLower, ['rapor', 'istatistik', 'özet'])) {
                $this->buildReportContext($context, $userMessageLower);
            }
            
            // Log talebi
            if ($this->containsKeywords($userMessageLower, ['log', 'kayıt', 'işlem'])) {
                $this->buildLogContext($context);
            }
            
            $context .= "[BAĞLAM SONU]\n";
            
            return $this->getAIResponse($context, $userMessage, 'general');
        }
    
    /**
     * Öğretmenlerin ders sayıları - MODEL KULLANIMI
     */
    private function buildTeacherLessonStatsContext(string &$context, string $msg): void
    {
        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        $lessonModel = new \App\Models\LessonModel();
        
        $isWeekly = str_contains($msg, 'hafta');
        $period = $isWeekly ? "BU HAFTA" : "BU AY";
        
        // Öğretmenleri çek
        $teachers = $userModel
            ->select('users.id, up.first_name, up.last_name, up.branch')
            ->join('user_profiles up', 'up.user_id = users.id', 'left')
            ->join('auth_groups_users agu', 'agu.user_id = users.id')
            ->where('agu.group', 'ogretmen')
            ->where('users.deleted_at', null)
            ->asArray()
            ->findAll();
        
        $context .= "\n=== ÖĞRETMEN DERS İSTATİSTİKLERİ ({$period}) ===\n\n";
        
        if (empty($teachers)) {
            $context .= "Sistemde öğretmen bulunamadı.\n";
            return;
        }
        
        $teacherStats = [];
        $total = 0;
        
        foreach ($teachers as $teacher) {
            // Tarih filtresini belirle
            if ($isWeekly) {
                $startDate = date('Y-m-d', strtotime('-7 days'));
            } else {
                $startDate = date('Y-m-01'); // Ayın ilk günü
            }
            $endDate = date('Y-m-d');
            
            // Öğretmenin ders sayısını çek
            $lessonCount = $lessonModel
                ->where('teacher_id', $teacher['id'])
                ->where('lesson_date >=', $startDate)
                ->where('lesson_date <=', $endDate)
                ->countAllResults();
            
            if ($lessonCount > 0) {
                $teacherStats[] = [
                    'name' => $teacher['first_name'] . ' ' . $teacher['last_name'],
                    'branch' => $teacher['branch'],
                    'count' => $lessonCount
                ];
                $total += $lessonCount;
            }
        }
        
        // Ders sayısına göre sırala
        usort($teacherStats, fn($a, $b) => $b['count'] <=> $a['count']);
        
        if (!empty($teacherStats)) {
            foreach ($teacherStats as $stat) {
                $context .= "Öğretmen: {$stat['name']}";
                if (!empty($stat['branch'])) $context .= " ({$stat['branch']})";
                $context .= " - {$stat['count']} ders\n";
            }
            $avg = count($teacherStats) > 0 ? round($total / count($teacherStats), 2) : 0;
            $context .= "\nToplam: {$total} ders\n";
            $context .= "Ortalama: {$avg} ders/öğretmen\n";
        } else {
            $context .= "Bu dönem için ders verisi bulunamadı.\n";
        }
    }
    
    /**
     * Mesafe bazlı öğrenci önerileri + Geçmiş veri analizi
     */
    private function buildDistanceBasedStudentsContext(string &$context, string $msg): void
    {
        $studentModel = new \App\Models\StudentModel();
        $lessonModel = new \App\Models\LessonModel();
        $lessonStudentModel = new \App\Models\LessonStudentModel();
        $lessonHistoryModel = new \App\Models\LessonHistoryModel();
        
        // Mesafe belirleme
        $distance = 'Civar';
        if (str_contains($msg, 'yakın')) $distance = 'Yakın';
        if (str_contains($msg, 'uzak')) $distance = 'Uzak';
        
        $todayDayOfWeek = date('N'); // 1=Pazartesi, 7=Pazar
        $mysqlDayOfWeek = ($todayDayOfWeek % 7) + 1; // MySQL formatı: 1=Pazar
        
        $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        $bugun = $gunler[$todayDayOfWeek];
        
        // Mesafe filtresine göre öğrencileri çek
        $students = $studentModel
            ->select('students.id, students.adi, students.soyadi, students.mesafe, students.egitim_programi,
                    students.normal_bireysel_hak, students.normal_grup_hak, students.telafi_bireysel_hak, students.telafi_grup_hak,
                    students.veli_anne_telefon, students.veli_baba_telefon')
            ->where('students.mesafe', $distance)
            ->where('students.deleted_at', null)
            ->where('(students.normal_bireysel_hak + students.normal_grup_hak + students.telafi_bireysel_hak + students.telafi_grup_hak) >', 0)
            ->asArray()
            ->findAll();
        
        // Başlık
        if (str_contains($msg, 'bugün') || str_contains($msg, 'gelmesi muhtemel')) {
            $context .= "\n=== BUGÜN ({$bugun}) GELMESİ MUHTEMEL {$distance} MESAFE ÖĞRENCİLER ===\n";
        } else {
            $context .= "\n=== {$distance} MESAFE ÖĞRENCİLER (Geçmiş Verilere Göre) ===\n";
        }
        
        $context .= "(Son 90 günde bu günde en az 2 kez ders almış)\n";
        $context .= "(lessons + lesson_history verilerinden analiz)\n\n";
        
        if (empty($students)) {
            $context .= "Bu mesafe grubunda öğrenci bulunamadı.\n";
            return;
        }
        
        $suggestions = [];
        $startDate = date('Y-m-d', strtotime('-90 days'));
        
        foreach ($students as $student) {
            $studentFullName = $student['adi'] . ' ' . $student['soyadi'];
            
            // lessons tablosundan gelme sayısı
            $lessonCount = $lessonModel
                ->select('COUNT(DISTINCT lessons.id) as count')
                ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
                ->where('ls.student_id', $student['id'])
                ->where('DAYOFWEEK(lessons.lesson_date)', $mysqlDayOfWeek)
                ->where('lessons.lesson_date >=', $startDate)
                ->asArray()
                ->first();
            
            // lesson_history tablosundan gelme sayısı
            $historyCount = $lessonHistoryModel
                ->where('student_name', $studentFullName)
                ->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek)
                ->where('lesson_date >=', $startDate)
                ->countAllResults();
            
            $totalVisits = ($lessonCount['count'] ?? 0) + $historyCount;
            
            // En az 2 kez gelmiş olmalı
            if ($totalVisits >= 2) {
                // Son gelme tarihlerini çek
                $recentDates = $lessonModel
                    ->select('DISTINCT lessons.lesson_date')
                    ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
                    ->where('ls.student_id', $student['id'])
                    ->where('DAYOFWEEK(lessons.lesson_date)', $mysqlDayOfWeek)
                    ->where('lessons.lesson_date >=', $startDate)
                    ->orderBy('lessons.lesson_date', 'DESC')
                    ->limit(5)
                    ->asArray()
                    ->findAll();
                
                $datesList = array_map(fn($d) => date('d.m.Y', strtotime($d['lesson_date'])), $recentDates);
                
                $totalHak = $student['normal_bireysel_hak'] + $student['normal_grup_hak'] + 
                        $student['telafi_bireysel_hak'] + $student['telafi_grup_hak'];
                
                $suggestions[] = [
                    'name' => $studentFullName,
                    'visits' => $totalVisits,
                    'program' => $student['egitim_programi'],
                    'hak' => $totalHak,
                    'anne_tel' => $student['veli_anne_telefon'],
                    'baba_tel' => $student['veli_baba_telefon'],
                    'dates' => implode(', ', $datesList)
                ];
            }
        }
        
        // Gelme sayısına göre sırala
        usort($suggestions, fn($a, $b) => $b['visits'] <=> $a['visits']);
        
        if (!empty($suggestions)) {
            foreach (array_slice($suggestions, 0, 30) as $s) {
                $context .= "Öğrenci: {$s['name']}\n";
                $context .= "Bu günde geçmişte: {$s['visits']} kez gelmiş\n";
                $context .= "Program: {$s['program']}\n";
                $context .= "Kalan Hak: {$s['hak']} saat\n";
                $context .= "Geçmiş Tarihler: {$s['dates']}\n";
                $context .= "Tel: Anne {$s['anne_tel']}, Baba {$s['baba_tel']}\n\n";
            }
            $context .= "Toplam: " . count($suggestions) . " öğrenci\n";
        } else {
            $context .= "Bu mesafe grubunda bugün ({$bugun}) gelmesi muhtemel öğrenci bulunamadı.\n";
            $context .= "(Son 90 günde bu günde en az 2 kez gelmiş öğrenci yok)\n";
        }
    }
    
    /**
     * Eğitim programı istatistikleri - MODEL KULLANIMI
     */
    private function buildEducationProgramStatsContext(string &$context, string $msg): void
    {
        $studentModel = new \App\Models\StudentModel();
        
        // Tam program isimleri
        $programMap = [
            'bedensel' => 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
            'dil ve konuşma' => 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
            'zihinsel' => 'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
            'öğrenme güçlüğü' => 'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı',
            'otizm' => 'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı'
        ];
        
        $selectedProgram = null;
        foreach ($programMap as $keyword => $fullName) {
            if (str_contains($msg, $keyword)) {
                $selectedProgram = $fullName;
                break;
            }
        }
        
        if ($selectedProgram) {
            // Tek program analizi
            $context .= "\n=== EĞİTİM PROGRAMI ANALİZİ ===\n";
            $context .= "Program: {$selectedProgram}\n\n";
            
            // Program içeren öğrencileri çek
            $students = $studentModel
                ->select('cinsiyet, dogum_tarihi')
                ->where('deleted_at', null)
                ->like('egitim_programi', $selectedProgram)
                ->asArray()
                ->findAll();
            
            if (!empty($students)) {
                $stats = [
                    'erkek' => [],
                    'kız' => []
                ];
                
                foreach ($students as $student) {
                    $gender = $student['cinsiyet'] === 'erkek' ? 'erkek' : 'kız';
                    
                    // Yaş hesapla
                    $age = null;
                    if (!empty($student['dogum_tarihi'])) {
                        $birthDate = new \DateTime($student['dogum_tarihi']);
                        $today = new \DateTime();
                        $age = $today->diff($birthDate)->y;
                    }
                    
                    $stats[$gender][] = $age;
                }
                
                // İstatistikleri yazdır
                foreach (['erkek', 'kız'] as $gender) {
                    if (!empty($stats[$gender])) {
                        $count = count($stats[$gender]);
                        $validAges = array_filter($stats[$gender], fn($age) => $age !== null);
                        $avgAge = !empty($validAges) ? round(array_sum($validAges) / count($validAges), 1) : 'Bilinmiyor';
                        
                        $genderLabel = $gender === 'erkek' ? 'Erkek' : 'Kız';
                        $context .= "{$genderLabel}: {$count} öğrenci (Yaş Ort: {$avgAge})\n";
                    }
                }
                
                $totalStudents = count($students);
                $context .= "\nToplam: {$totalStudents} öğrenci\n";
                
                // Genel yaş ortalaması
                $allAges = array_merge($stats['erkek'], $stats['kız']);
                $validAges = array_filter($allAges, fn($age) => $age !== null);
                if (!empty($validAges)) {
                    $generalAvg = round(array_sum($validAges) / count($validAges), 1);
                    $context .= "Genel Yaş Ortalaması: {$generalAvg} yaş\n";
                }
            } else {
                $context .= "Bu programda kayıtlı öğrenci bulunamadı.\n";
            }
        } else {
            // Tüm programlar özeti
            $context .= "\n=== TÜM EĞİTİM PROGRAMLARI ÖZETİ ===\n\n";
            
            foreach ($programMap as $keyword => $fullName) {
                $count = $studentModel
                    ->where('deleted_at', null)
                    ->like('egitim_programi', $fullName)
                    ->countAllResults();
                
                $kisa = ucfirst($keyword);
                $context .= "{$kisa}: {$count} öğrenci\n";
            }
        }
    }
    
    /**
     * Sabit program analizi - MODEL KULLANIMI
     */
    private function buildFixedProgramAnalysisContext(string &$context, string $msg): void
    {
        $studentModel = new \App\Models\StudentModel();
        $fixedLessonModel = new \App\Models\FixedLessonModel();
        $lessonModel = new \App\Models\LessonModel();
        $lessonStudentModel = new \App\Models\LessonStudentModel();
        
        if (str_contains($msg, 'olmayan') || str_contains($msg, 'düzenli')) {
            // Sabit programı olmayan ama son 30 günde düzenli gelen
            $context .= "\n=== SABİT PROGRAMI OLMAYAN AMA DÜZENLİ GELEN ÖĞRENCİLER ===\n";
            $context .= "(Son 30 günde 8+ gün gelmiş)\n\n";
            
            // Sabit programı olan öğrenci ID'lerini al
            $fixedStudentIds = $fixedLessonModel
                ->distinct()
                ->select('student_id')
                ->asArray()
                ->findColumn('student_id');
            
            // Tüm öğrencileri al
            $allStudents = $studentModel
                ->select('id, adi, soyadi')
                ->where('deleted_at', null)
                ->asArray()
                ->findAll();
            
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $suggestions = [];
            
            foreach ($allStudents as $student) {
                // Sabit programı varsa atla
                if (in_array($student['id'], $fixedStudentIds)) {
                    continue;
                }
                
                // Son 30 günde kaç farklı günde gelmiş?
                $lessonDates = $lessonModel
                    ->select('DISTINCT DATE(lessons.lesson_date) as lesson_date')
                    ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
                    ->where('ls.student_id', $student['id'])
                    ->where('lessons.lesson_date >=', $startDate)
                    ->asArray()
                    ->findAll();
                
                $uniqueDays = count($lessonDates);
                
                // Toplam ders sayısı
                $totalLessons = $lessonModel
                    ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
                    ->where('ls.student_id', $student['id'])
                    ->where('lessons.lesson_date >=', $startDate)
                    ->countAllResults();
                
                // 8+ gün gelmiş mi?
                if ($uniqueDays >= 8) {
                    $suggestions[] = [
                        'name' => $student['adi'] . ' ' . $student['soyadi'],
                        'days' => $uniqueDays,
                        'lessons' => $totalLessons
                    ];
                }
            }
            
            // Gelme gününe göre sırala
            usort($suggestions, fn($a, $b) => $b['days'] <=> $a['days']);
            
            if (!empty($suggestions)) {
                foreach (array_slice($suggestions, 0, 20) as $s) {
                    $context .= "Öğrenci: {$s['name']} - {$s['days']} gün, {$s['lessons']} ders\n";
                }
                $context .= "\nToplam: " . count($suggestions) . " öğrenci (ilk 20 gösteriliyor)\n";
            } else {
                $context .= "Kriterlere uyan öğrenci bulunamadı.\n";
            }
            
        } else {
            // Sabit programı olan öğrenciler
            $context .= "\n=== SABİT PROGRAMI OLAN ÖĞRENCİLER ===\n\n";
            
            // Sabit dersleri öğrenci bazında grupla
            $fixedLessons = $fixedLessonModel
                ->select('student_id, COUNT(*) as lesson_count')
                ->groupBy('student_id')
                ->asArray()
                ->findAll();
            
            if (empty($fixedLessons)) {
                $context .= "Sabit programı olan öğrenci bulunamadı.\n";
                return;
            }
            
            $results = [];
            foreach ($fixedLessons as $fl) {
                $student = $studentModel->find($fl['student_id']);
                
                if ($student) {
                    $results[] = [
                        'name' => $student['adi'] . ' ' . $student['soyadi'],
                        'count' => $fl['lesson_count']
                    ];
                }
            }
            
            // Ders sayısına göre sırala
            usort($results, fn($a, $b) => $b['count'] <=> $a['count']);
            
            foreach ($results as $r) {
                $context .= "Öğrenci: {$r['name']} - {$r['count']} sabit ders\n";
            }
            $context .= "\nToplam: " . count($results) . " öğrenci\n";
        }
    }
        
    /**
     * Yarın için alternatifler - MODEL KULLANIMI
     */
    private function buildTomorrowAlternativesContext(string &$context, string $msg): void
    {
        $fixedLessonModel = new \App\Models\FixedLessonModel();
        $studentModel = new \App\Models\StudentModel();
        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $dayOfWeek = date('N', strtotime($tomorrow)); // 1=Pazartesi, 7=Pazar
        
        $context .= "\n=== YARININ SABİT PROGRAMI ({$tomorrow}) ===\n\n";
        
        // Yarının sabit programını çek
        $fixedLessons = $fixedLessonModel
            ->select('fixed_lessons.start_time, fixed_lessons.end_time, 
                    fixed_lessons.student_id, fixed_lessons.teacher_id')
            ->where('fixed_lessons.day_of_week', $dayOfWeek)
            ->orderBy('fixed_lessons.start_time', 'ASC')
            ->asArray()
            ->findAll();
        
        if (!empty($fixedLessons)) {
            foreach ($fixedLessons as $fl) {
                // Öğrenci bilgisi
                $student = $studentModel->find($fl['student_id']);
                if (!$student) continue;
                
                // Öğretmen bilgisi
                $teacher = $userModel
                    ->select('users.id, up.first_name, up.last_name')
                    ->join('user_profiles up', 'up.user_id = users.id', 'left')
                    ->where('users.id', $fl['teacher_id'])
                    ->where('users.deleted_at', null)
                    ->asArray()
                    ->first();
                
                if (!$teacher) continue;
                
                $context .= "Saat {$fl['start_time']}-{$fl['end_time']}: ";
                $context .= "{$student['adi']} {$student['soyadi']} - ";
                $context .= "Öğretmen: {$teacher['first_name']} {$teacher['last_name']}\n";
            }
        } else {
            $context .= "Yarın için sabit program bulunamadı.\n";
        }
        
        // Alternatif öğrenciler (Civar ve Yakın mesafe)
        $context .= "\n=== ALTERNATİF ÖĞRENCİ ÖNERİLERİ ===\n\n";
        
        $alternatives = $studentModel
            ->select('adi, soyadi, mesafe, egitim_programi, 
                    normal_bireysel_hak, normal_grup_hak, telafi_bireysel_hak, telafi_grup_hak,
                    veli_anne_telefon, veli_baba_telefon')
            ->whereIn('mesafe', ['Civar', 'Yakın'])
            ->where('deleted_at', null)
            ->where('(normal_bireysel_hak + normal_grup_hak + telafi_bireysel_hak + telafi_grup_hak) >', 5)
            ->orderBy("FIELD(mesafe, 'Civar', 'Yakın')", '', false)
            ->orderBy('adi', 'ASC')
            ->limit(15)
            ->asArray()
            ->findAll();
        
        if (!empty($alternatives)) {
            foreach ($alternatives as $alt) {
                $totalHak = $alt['normal_bireysel_hak'] + $alt['normal_grup_hak'] + 
                        $alt['telafi_bireysel_hak'] + $alt['telafi_grup_hak'];
                
                $context .= "Öğrenci: {$alt['adi']} {$alt['soyadi']} ({$alt['mesafe']})\n";
                $context .= "Program: {$alt['egitim_programi']}\n";
                $context .= "Kalan Hak: {$totalHak} saat\n";
                $context .= "Telefon - Anne: {$alt['veli_anne_telefon']}, Baba: {$alt['veli_baba_telefon']}\n\n";
            }
        } else {
            $context .= "Alternatif öğrenci bulunamadı.\n";
        }
    }
    
    /**
     * Ders hakkı analizi - MODEL KULLANIMI
     */
    private function buildEntitlementAnalysisContext(string &$context, string $msg): void
    {
        $studentModel = new \App\Models\StudentModel();
        
        // Limit belirleme
        $limit = 10;
        if (str_contains($msg, '5')) $limit = 5;
        if (str_contains($msg, '15')) $limit = 15;
        
        $context .= "\n=== DERS HAKKI {$limit} SAATİN ALTINDA OLAN ÖĞRENCİLER ===\n\n";
        
        // Tüm aktif öğrencileri çek
        $allStudents = $studentModel
            ->select('adi, soyadi, 
                    normal_bireysel_hak, normal_grup_hak, 
                    telafi_bireysel_hak, telafi_grup_hak,
                    veli_anne_telefon, veli_baba_telefon')
            ->where('deleted_at', null)
            ->asArray()
            ->findAll();
        
        $criticalStudents = [];
        
        foreach ($allStudents as $student) {
            $totalHak = ($student['normal_bireysel_hak'] ?? 0) + 
                        ($student['normal_grup_hak'] ?? 0) + 
                        ($student['telafi_bireysel_hak'] ?? 0) + 
                        ($student['telafi_grup_hak'] ?? 0);
            
            // Limit'in altında ve sıfırdan büyük olanları al
            if ($totalHak > 0 && $totalHak <= $limit) {
                $criticalStudents[] = [
                    'name' => $student['adi'] . ' ' . $student['soyadi'],
                    'total' => $totalHak,
                    'normal_bireysel' => $student['normal_bireysel_hak'] ?? 0,
                    'normal_grup' => $student['normal_grup_hak'] ?? 0,
                    'telafi_bireysel' => $student['telafi_bireysel_hak'] ?? 0,
                    'telafi_grup' => $student['telafi_grup_hak'] ?? 0,
                    'anne_tel' => $student['veli_anne_telefon'] ?? 'Yok',
                    'baba_tel' => $student['veli_baba_telefon'] ?? 'Yok'
                ];
            }
        }
        
        // Kalan saate göre sırala (az olandan çoğa)
        usort($criticalStudents, fn($a, $b) => $a['total'] <=> $b['total']);
        
        if (!empty($criticalStudents)) {
            foreach (array_slice($criticalStudents, 0, 30) as $s) {
                $urgency = $s['total'] <= 3 ? '🔴 ÇOK ACİL' : '⚠️ ACİL';
                
                $context .= "{$urgency} - {$s['name']}: {$s['total']} saat kaldı\n";
                $context .= "Normal: Bireysel {$s['normal_bireysel']} + Grup {$s['normal_grup']} | ";
                $context .= "Telafi: Bireysel {$s['telafi_bireysel']} + Grup {$s['telafi_grup']}\n";
                $context .= "Telefon - Anne: {$s['anne_tel']}, Baba: {$s['baba_tel']}\n\n";
            }
            $context .= "Toplam: " . count($criticalStudents) . " öğrenci\n";
        } else {
            $context .= "Ders hakkı {$limit} saatin altında olan öğrenci yok.\n";
        }
    }
        
    /**
     * Veritabanı şeması
     */
    private function buildDatabaseSchemaContext(string &$context): void
    {
        $inspector = new DatabaseInspector();
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "VERİTABANI ERİŞİMİ\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        $context .= $inspector->getDatabaseRelationships();
        
        $context .= "\n=== TABLO ÖZETLERİ ===\n\n";
        $tables = ['students', 'users', 'user_profiles', 'lessons', 'lesson_students', 
                   'fixed_lessons', 'reports', 'logs', 'auth_groups_users'];
        
        foreach ($tables as $table) {
            $stats = $inspector->getTableStats($table);
            if (!isset($stats['error'])) {
                $context .= "{$table}: {$stats['total_records']} kayıt\n";
            }
        }
    }

    /**
     * SQL çalıştırma
     */
    private function executeSQLQuery(string $sql, string &$context): void
    {
        $inspector = new DatabaseInspector();
        $result = $inspector->executeQuery($sql);
        
        $context .= "\n=== SQL SORGU SONUCU ===\n";
        $context .= "Sorgu: {$sql}\n\n";
        
        if ($result['error']) {
            $context .= "HATA: {$result['message']}\n";
        } else {
            $context .= "Başarılı: {$result['count']} kayıt\n\n";
            
            if (!empty($result['data'])) {
                $limit = min(20, count($result['data']));
                for ($i = 0; $i < $limit; $i++) {
                    $context .= "Kayıt " . ($i + 1) . ":\n";
                    foreach ($result['data'][$i] as $key => $value) {
                        $context .= "  {$key}: {$value}\n";
                    }
                    $context .= "\n";
                }
            }
        }
    }

    /**
     * SQL çıkarma
     */
    private function extractSQLFromMessage(string $message): ?string
    {
        if (preg_match('/SELECT\s+.+\s+FROM\s+\w+/is', $message, $m)) {
            return trim($m[0]);
        }
        return null;
    }
    
    /**
     * Rapor
     */
private function buildReportContext(string &$context, string $msg): void
{
    $reportModel = new ReportModel();
    
    // extractMonthFromMessage zaten BaseAIController'da mevcut, "2025-10" formatında döner
    $targetMonth = $this->extractMonthFromMessage($msg);
    
    // Yıl ve ay'ı ayrıştır
    list($year, $month) = explode('-', $targetMonth);
    
    $context .= "\n=== AYLIK RAPOR ({$targetMonth}) ===\n";
    
    try {
        // getMonthlySummary zaten var ve doğru çalışıyor
        $summary = $reportModel->getMonthlySummary((int)$year, (int)$month);
        
        $context .= "Toplam Ders Saati: " . ($summary['total_hours'] ?? 0) . "\n";
        $context .= "Bireysel Ders: " . ($summary['total_individual'] ?? 0) . "\n";
        $context .= "Grup Ders: " . ($summary['total_group'] ?? 0) . "\n";
        $context .= "Ders Alan Toplam Öğrenci: " . ($summary['total_students'] ?? 0) . "\n";
    } catch (\Exception $e) {
        $context .= "Bu ay için rapor hesaplanamadı: " . $e->getMessage() . "\n";
    }
}
    
    /**
     * Log
     */
    private function buildLogContext(string &$context): void
    {
        $logModel = new LogModel();
        $logs = $logModel->orderBy('id', 'DESC')->limit(10)->findAll();
                         
        $context .= "\n=== SON 10 SİSTEM İŞLEMİ ===\n\n";
        
        if (!empty($logs)) {
            foreach ($logs as $log) {
                $data = is_object($log) ? (array) $log : $log;
                $context .= "[{$data['created_at']}] {$data['message']}\n";
            }
        } else {
            $context .= "Log bulunamadı.\n";
        }
    }

    /**
     * Öğretmen + Saat + Gün bazlı öğrenci önerisi - MODEL KULLANIMI
     */
    private function buildTeacherTimeBasedSuggestions(string &$context, string $msg): void
    {
        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        $userProfileModel = new \App\Models\UserProfileModel();
        $lessonHistoryModel = new \App\Models\LessonHistoryModel();
        $studentModel = new \App\Models\StudentModel();
        
        // Öğretmen adını bul
        $teacherName = $this->findTeacherNameInMessage($msg);
        
        // Saat bilgisini bul (14:00, 14, vs)
        $timeSlot = null;
        if (preg_match('/(\d{1,2})[:.]?(\d{2})?/', $msg, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minute = $matches[2] ?? '00';
            $timeSlot = "{$hour}:{$minute}:00";
        }
        
        // Gün bilgisi (bugün mü, yarın mı, Pazartesi mi?)
        $dayOfWeek = null;
        $gunler = [
            'pazartesi' => 1, 
            'salı' => 2, 
            'çarşamba' => 3, 
            'perşembe' => 4, 
            'cuma' => 5, 
            'cumartesi' => 6, 
            'pazar' => 7
        ];
        
        foreach ($gunler as $gun => $phpDay) {
            if (str_contains($msg, $gun)) {
                $dayOfWeek = $phpDay;
                break;
            }
        }
        
        if (str_contains($msg, 'bugün')) {
            $dayOfWeek = date('N');
        }
        if (str_contains($msg, 'yarın')) {
            $dayOfWeek = date('N', strtotime('+1 day'));
        }
        
        if (!$teacherName) {
            $context .= "\n=== ÖĞRETMEN BAZLI ÖNERİ ===\n";
            $context .= "Öğretmen adı tespit edilemedi. Lütfen öğretmen adını belirtin.\n";
            return;
        }
        
        $context .= "\n=== ÖĞRETMEN BAZLI ÖĞRENCİ ÖNERİSİ ===\n";
        $context .= "Öğretmen: {$teacherName}\n";
        if ($timeSlot) $context .= "Saat: {$timeSlot}\n";
        if ($dayOfWeek) {
            $gunIsimleri = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
            $context .= "Gün: {$gunIsimleri[$dayOfWeek]}\n";
        }
        $context .= "\n";
        
        // Öğretmenin branşını bul
        $teacherProfile = $userProfileModel
            ->where('CONCAT(first_name, " ", last_name) LIKE', "%{$teacherName}%")
            ->asArray()
            ->first();
        
        $teacherBranch = $teacherProfile['branch'] ?? null;
        
        if ($teacherBranch) {
            $context .= "Öğretmen Branşı: {$teacherBranch}\n\n";
        }
        
        // lesson_history'den öneriler
        $query = $lessonHistoryModel
            ->select('student_name, student_program, COUNT(*) as gelme_sayisi')
            ->groupBy('student_name, student_program')
            ->orderBy('gelme_sayisi', 'DESC')
            ->limit(20);
        
        // Branş varsa branşa göre filtrele
        if ($teacherBranch) {
            $query->where('teacher_branch', $teacherBranch);
        } else {
            $query->like('teacher_name', $teacherName);
        }
        
        // Saat filtresi
        if ($timeSlot) {
            $query->where('start_time', $timeSlot);
        }
        
        // Gün filtresi
        if ($dayOfWeek) {
            $mysqlDayOfWeek = ($dayOfWeek % 7) + 1; // MySQL format
            $query->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek);
        }
        
        // Son 180 günü kontrol et
        $query->where('lesson_date >=', date('Y-m-d', strtotime('-180 days')));
        
        $suggestions = $query->asArray()->findAll();
        
        if (!empty($suggestions)) {
            $context .= "GEÇMİŞ VERİLERE GÖRE ÖNERİLEN ÖĞRENCİLER:\n\n";
            
            foreach ($suggestions as $sug) {
                $context .= "Öğrenci: {$sug['student_name']}\n";
                $context .= "Program: {$sug['student_program']}\n";
                $context .= "Bu koşullarda {$sug['gelme_sayisi']} kez ders almış\n";
                
                // Öğrencinin güncel ders hakkını kontrol et
                $studentParts = explode(' ', $sug['student_name']);
                if (count($studentParts) >= 2) {
                    $currentStudent = $studentModel
                        ->select('normal_bireysel_hak, normal_grup_hak, telafi_bireysel_hak, telafi_grup_hak,
                                veli_anne_telefon, veli_baba_telefon')
                        ->where('adi', $studentParts[0])
                        ->where('soyadi', $studentParts[1])
                        ->where('deleted_at', null)
                        ->asArray()
                        ->first();
                    
                    if ($currentStudent) {
                        $totalHak = ($currentStudent['normal_bireysel_hak'] ?? 0) + 
                                ($currentStudent['normal_grup_hak'] ?? 0) + 
                                ($currentStudent['telafi_bireysel_hak'] ?? 0) + 
                                ($currentStudent['telafi_grup_hak'] ?? 0);
                        
                        $context .= "Güncel Ders Hakkı: {$totalHak} saat\n";
                        $context .= "Tel: Anne {$currentStudent['veli_anne_telefon']}, Baba {$currentStudent['veli_baba_telefon']}\n";
                    }
                }
                $context .= "\n";
            }
        } else {
            $context .= "Bu kriterlere uygun geçmiş ders verisi bulunamadı.\n";
            $context .= "Öneriler oluşturulamadı.\n";
            
            // Alternatif öneriler
            $context .= "\n💡 ÖNERİ: Filtreleri gevşetebilirsiniz:\n";
            if ($timeSlot) {
                $context .= "  • Saat filtresi olmadan deneyin\n";
            }
            if ($dayOfWeek) {
                $context .= "  • Gün filtresi olmadan deneyin\n";
            }
            $context .= "  • Daha geniş bir zaman aralığında arayın\n";
        }
    }

    /**
     * Mesajdan öğretmen adını çıkarır
     */
    private function findTeacherNameInMessage(string $msg): ?string
    {
        $db = \Config\Database::connect();
        
        $teachers = $db->table('user_profiles')
            ->select('CONCAT(first_name, " ", last_name) as full_name')
            ->get()
            ->getResultArray();
        
        foreach ($teachers as $teacher) {
            $name = $this->turkish_strtolower($teacher['full_name']);
            if (str_contains($msg, $name)) {
                return $teacher['full_name'];
            }
        }
        
        return null;
    }

    /**
     * Yarın boş saatler için öğrenci tavsiyesi - MODEL KULLANIMI
     */
    private function buildTomorrowEmptySlotsSuggestions(string &$context, string $msg): void
    {
        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        $userProfileModel = new \App\Models\UserProfileModel();
        $lessonModel = new \App\Models\LessonModel();
        $lessonHistoryModel = new \App\Models\LessonHistoryModel();
        $studentModel = new \App\Models\StudentModel();
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $tomorrowDayOfWeek = date('N', strtotime($tomorrow)); // 1=Pazartesi, 7=Pazar
        $mysqlDayOfWeek = ($tomorrowDayOfWeek % 7) + 1; // MySQL format
        
        $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        $gun = $gunler[$tomorrowDayOfWeek];
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "YARIN ({$tomorrow} - {$gun}) BOŞ SAATLER İÇİN ÖĞRENCİ TAVSİYELERİ\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        // Tüm öğretmenleri çek
        $teachers = $userModel
            ->select('users.id')
            ->join('auth_groups_users agu', 'agu.user_id = users.id')
            ->where('agu.group', 'ogretmen')
            ->where('users.deleted_at', null)
            ->asArray()
            ->findAll();
        
        $allSlots = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'];
        
        foreach ($teachers as $teacher) {
            // Profil bilgisini al
            $profile = $userProfileModel
                ->where('user_id', $teacher['id'])
                ->asArray()
                ->first();
            
            if (!$profile) continue;
            
            $teacherFullName = $profile['first_name'] . ' ' . $profile['last_name'];
            $teacherBranch = $profile['branch'] ?? 'Belirtilmemiş';
            
            // Yarın bu öğretmenin dolu saatlerini bul
            $busySlots = $lessonModel
                ->select('start_time')
                ->where('teacher_id', $teacher['id'])
                ->where('lesson_date', $tomorrow)
                ->asArray()
                ->findAll();
            
            $busyTimes = array_column($busySlots, 'start_time');
            $emptySlots = array_diff($allSlots, $busyTimes);
            
            if (!empty($emptySlots)) {
                $context .= "Öğretmen: {$teacherFullName} ({$teacherBranch})\n";
                $context .= "Boş Saatler: " . implode(', ', array_map(fn($s) => substr($s, 0, 5), $emptySlots)) . "\n\n";
                
                // Her boş saat için lesson_history'den öneri
                foreach ($emptySlots as $slot) {
                    $suggestions = $lessonHistoryModel
                        ->select('student_name, student_program, COUNT(*) as gelme_sayisi')
                        ->like('teacher_name', $teacherFullName)
                        ->where('start_time', $slot)
                        ->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek)
                        ->where('lesson_date >=', date('Y-m-d', strtotime('-180 days')))
                        ->groupBy('student_name, student_program')
                        ->orderBy('gelme_sayisi', 'DESC')
                        ->limit(3)
                        ->asArray()
                        ->findAll();
                    
                    if (!empty($suggestions)) {
                        $context .= "  └─ Saat " . substr($slot, 0, 5) . " için öneriler:\n";
                        
                        foreach ($suggestions as $sug) {
                            // Öğrencinin güncel bilgilerini al
                            $studentParts = explode(' ', $sug['student_name']);
                            if (count($studentParts) >= 2) {
                                $currentStudent = $studentModel
                                    ->select('normal_bireysel_hak, normal_grup_hak, telafi_bireysel_hak, telafi_grup_hak,
                                            mesafe, veli_anne_telefon, veli_baba_telefon')
                                    ->where('adi', $studentParts[0])
                                    ->where('soyadi', $studentParts[1])
                                    ->where('deleted_at', null)
                                    ->asArray()
                                    ->first();
                                
                                if ($currentStudent) {
                                    $totalHak = ($currentStudent['normal_bireysel_hak'] ?? 0) + 
                                            ($currentStudent['normal_grup_hak'] ?? 0) + 
                                            ($currentStudent['telafi_bireysel_hak'] ?? 0) + 
                                            ($currentStudent['telafi_grup_hak'] ?? 0);
                                    
                                    if ($totalHak > 0) {
                                        $context .= "     • {$sug['student_name']} ({$sug['student_program']})\n";
                                        $context .= "       Geçmişte bu gün/saatte {$sug['gelme_sayisi']} kez gelmiş\n";
                                        $context .= "       Mesafe: {$currentStudent['mesafe']}, Hak: {$totalHak} saat\n";
                                        $context .= "       Tel: Anne {$currentStudent['veli_anne_telefon']}, Baba {$currentStudent['veli_baba_telefon']}\n";
                                    }
                                }
                            }
                        }
                        $context .= "\n";
                    }
                }
                $context .= str_repeat("-", 60) . "\n\n";
            }
        }
        
        $context .= "[NOT: Öneriler lesson_history tablosundaki geçmiş ders verilerine dayanmaktadır.]\n";
    }

    /**
     * Sistem istatistikleri
     */
    private function buildSystemStatisticsContext(string &$context): void
    {
        $db = \Config\Database::connect();
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "SİSTEM İSTATİSTİKLERİ\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        // Toplam öğrenci sayısı
        $totalStudents = $db->query("SELECT COUNT(*) as total FROM students WHERE deleted_at IS NULL")->getRowArray();
        $context .= "Toplam Öğrenci: {$totalStudents['total']}\n";
        
        // Toplam öğretmen sayısı
        $totalTeachers = $db->query("
            SELECT COUNT(DISTINCT u.id) as total 
            FROM users u
            INNER JOIN auth_groups_users agu ON u.id = agu.user_id
            WHERE agu.group = 'ogretmen' AND u.deleted_at IS NULL
        ")->getRowArray();
        $context .= "Toplam Öğretmen: {$totalTeachers['total']}\n";
        
        // Bu ay toplam ders sayısı
        $thisMonthLessons = $db->query("
            SELECT COUNT(*) as total 
            FROM lessons 
            WHERE MONTH(lesson_date) = MONTH(CURDATE()) 
                AND YEAR(lesson_date) = YEAR(CURDATE())
        ")->getRowArray();
        $context .= "Bu Ay Toplam Ders: {$thisMonthLessons['total']}\n";
        
        // Sabit programı olan öğrenci sayısı
        $fixedStudents = $db->query("
            SELECT COUNT(DISTINCT student_id) as total 
            FROM fixed_lessons
        ")->getRowArray();
        $context .= "Sabit Programı Olan Öğrenci: {$fixedStudents['total']}\n";
        
        // Mesafe dağılımı
        $distanceStats = $db->query("
            SELECT mesafe, COUNT(*) as total 
            FROM students 
            WHERE deleted_at IS NULL AND mesafe IS NOT NULL
            GROUP BY mesafe
        ")->getResultArray();
        
        $context .= "\nMesafe Dağılımı:\n";
        foreach ($distanceStats as $stat) {
            $context .= "  {$stat['mesafe']}: {$stat['total']} öğrenci\n";
        }
        
        // Eğitim programı dağılımı
        $programMap = [
            'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı' => 'Bedensel',
            'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı' => 'Dil ve Konuşma',
            'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı' => 'Zihinsel',
            'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı' => 'Öğrenme Güçlüğü',
            'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı' => 'Otizm'
        ];
        
        $context .= "\nEğitim Programı Dağılımı:\n";
        foreach ($programMap as $fullName => $shortName) {
            $result = $db->query("
                SELECT COUNT(*) as total 
                FROM students 
                WHERE FIND_IN_SET(?, REPLACE(egitim_programi, ',', ',')) > 0
                    AND deleted_at IS NULL
            ", [$fullName])->getRowArray();
            $context .= "  {$shortName}: {$result['total']} öğrenci\n";
        }
        
        // Ders hakkı istatistikleri
        $entitlementStats = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN (normal_bireysel_hak + normal_grup_hak + telafi_bireysel_hak + telafi_grup_hak) <= 5 THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN (normal_bireysel_hak + normal_grup_hak + telafi_bireysel_hak + telafi_grup_hak) <= 10 THEN 1 ELSE 0 END) as warning
            FROM students 
            WHERE deleted_at IS NULL
                AND (normal_bireysel_hak + normal_grup_hak + telafi_bireysel_hak + telafi_grup_hak) > 0
        ")->getRowArray();
        
        $context .= "\nDers Hakkı Uyarıları:\n";
        $context .= "  Kritik (≤5 saat): {$entitlementStats['critical']} öğrenci\n";
        $context .= "  Uyarı (≤10 saat): {$entitlementStats['warning']} öğrenci\n";
        
        $context .= "\n";
    }

    /**
     * RAM raporu analizi
     */
    private function buildRAMReportAnalysisContext(string &$context, string $msg): void
    {
        $db = \Config\Database::connect();
        
        // Belirli bir öğrenci sorulmuş mu? - ORİJİNAL mesajdan ara (lowercase değil)
        $studentName = null;
        
        // Tüm öğrencileri çek ve mesajda ara
        $students = $db->query("
            SELECT adi, soyadi 
            FROM students 
            WHERE deleted_at IS NULL
        ")->getResultArray();
        
        foreach ($students as $s) {
            $fullName = $s['adi'] . ' ' . $s['soyadi'];
            $lowerName = $this->turkish_strtolower($fullName);
            
            if (str_contains($msg, $lowerName)) {
                $studentName = $fullName;
                break;
            }
        }
        
        if ($studentName) {
            // Bireysel öğrenci RAM analizi
            $parts = explode(' ', $studentName);
            if (count($parts) >= 2) {
            $student = $db->query("
                SELECT 
                    id, adi, soyadi, ram, ram_baslagic, ram_bitis, ram_raporu,
                    hastane_adi, hastane_raporu_baslama_tarihi, hastane_raporu_bitis_tarihi
                FROM students 
                WHERE adi = ? AND soyadi = ? AND deleted_at IS NULL
            ", [$parts[0], $parts[1]])->getRowArray();
                
                if ($student) {
                    $context .= "\n=== {$student['adi']} {$student['soyadi']} - RAM RAPORU ANALİZİ ===\n\n";
                    
                    // YENİ: AI destekli RAM analiz verilerini çek
                        $analysisModel = new \App\Models\RamReportAnalysisModel();
                        $ramAnalysis = $analysisModel->where('student_id', $student['id'])->first();

                        if ($ramAnalysis) {
                            $context .= "🤖 YAPAY ZEKA DESTEKLİ ANALİZ:\n";
                            $context .= str_repeat("-", 70) . "\n";
                            
                            if (!empty($ramAnalysis['total_memory'])) {
                                $context .= "Toplam Bellek: {$ramAnalysis['total_memory']}\n";
                            }
                            if (!empty($ramAnalysis['available_memory'])) {
                                $context .= "Kullanılabilir Bellek: {$ramAnalysis['available_memory']}\n";
                            }
                            if (!empty($ramAnalysis['memory_info'])) {
                                $memInfo = json_decode($ramAnalysis['memory_info'], true);
                                if ($memInfo) {
                                    $context .= "Bellek Detayları:\n";
                                    foreach ($memInfo as $key => $value) {
                                        $context .= "  - " . ucfirst($key) . ": {$value}\n";
                                    }
                                }
                            }
                            if (!empty($ramAnalysis['ram_text_content'])) {
                                $context .= "\nRAM Rapor İçeriği (İlk 2000 karakter):\n";
                                $context .= mb_substr($ramAnalysis['ram_text_content'], 0, 2000) . "...\n";
                            }
                            $context .= "Analiz Tarihi: " . date('d.m.Y H:i', strtotime($ramAnalysis['analyzed_at'])) . "\n\n";
                        }

                    if (!empty($student['ram'])) {
                        $context .= "RAM Bilgisi: {$student['ram']}\n";
                        
                        if (!empty($student['ram_baslagic'])) {
                            $context .= "RAM Başlangıç: {$student['ram_baslagic']}\n";
                        }
                        if (!empty($student['ram_bitis'])) {
                            $context .= "RAM Bitiş: {$student['ram_bitis']}\n";
                        }
                        
                        if (!empty($student['ram_raporu'])) {
                            $context .= "RAM Raporu: Sistemde mevcut (Dosya: {$student['ram_raporu']})\n";
                        } else {
                            $context .= "RAM Raporu: Dosya yüklenmemiş\n";
                        }
                    } else {
                        $context .= "RAM Bilgisi: Kayıt bulunmuyor\n";
                    }
                    
                    if (!empty($student['hastane_adi'])) {
                        $context .= "\nHastane Bilgisi:\n";
                        $context .= "  Adı: {$student['hastane_adi']}\n";
                        if (!empty($student['hastane_raporu_baslama_tarihi'])) {
                            $context .= "  Başlangıç: {$student['hastane_raporu_baslama_tarihi']}\n";
                        }
                        if (!empty($student['hastane_raporu_bitis_tarihi'])) {
                            $context .= "  Bitiş: {$student['hastane_raporu_bitis_tarihi']}\n";
                        }
                    } else {
                        $context .= "\nHastane Bilgisi: Kayıt yok\n";
                    }
                } else {
                    $context .= "\n=== RAM RAPORU ANALİZİ ===\n";
                    $context .= "'{$studentName}' adlı öğrenci bulunamadı.\n";
                }
            }
        } else {
            // Genel RAM raporu listesi - "olmayan" veya "eksik" kelimesi geçiyorsa
            $context .= "\n=== RAM RAPORU OLMAYAN ÖĞRENCİLER ===\n\n";
            
            $noRAMStudents = $db->query("
                SELECT adi, soyadi, egitim_programi,
                    veli_anne_telefon, veli_baba_telefon
                FROM students 
                WHERE (ram IS NULL OR ram = '')
                    AND deleted_at IS NULL
                ORDER BY adi, soyadi
                LIMIT 50
            ")->getResultArray();
            
            if (!empty($noRAMStudents)) {
                foreach ($noRAMStudents as $s) {
                    $context .= "Öğrenci: {$s['adi']} {$s['soyadi']}\n";
                    $context .= "Program: {$s['egitim_programi']}\n";
                    $context .= "Tel: Anne {$s['veli_anne_telefon']}, Baba {$s['veli_baba_telefon']}\n\n";
                }
                $context .= "Toplam: " . count($noRAMStudents) . " öğrenci (ilk 50 gösteriliyor)\n";
            } else {
                $context .= "Tüm öğrencilerin RAM kaydı mevcut.\n";
            }
        }
    }

    /**
     * Sohbet kontrolü - Havadan sudan konuşmalar
     */
    private function checkCasualConversation(string $msg): ?string
    {
        $knowledgeBase = \App\Libraries\IkiheceKnowledgeBase::getCasualResponses();
        
        if (str_contains($msg, 'merhaba') || str_contains($msg, 'selam') || str_contains($msg, 'hey')) {
            $responses = $knowledgeBase['merhaba'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'günaydın') || str_contains($msg, 'gunaydin')) {
            if (isset($knowledgeBase['günaydın'])) {
                $responses = $knowledgeBase['günaydın'];
                return $responses[array_rand($responses)];
            }
        }
        
        if (str_contains($msg, 'iyi geceler') || str_contains($msg, 'iyi gece')) {
            if (isset($knowledgeBase['iyi geceler'])) {
                $responses = $knowledgeBase['iyi geceler'];
                return $responses[array_rand($responses)];
            }
        }
        
        if (str_contains($msg, 'nasılsın') || str_contains($msg, 'nasilsin') || str_contains($msg, 'n\'aber')) {
            $responses = $knowledgeBase['nasılsın'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'teşekkür') || str_contains($msg, 'tesekkur') || str_contains($msg, 'sağol') || str_contains($msg, 'sagol') || str_contains($msg, 'eyvallah')) {
            $responses = $knowledgeBase['teşekkür'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'şaka') || str_contains($msg, 'saka') || str_contains($msg, 'espri') || str_contains($msg, 'gülsene')) {
            $responses = $knowledgeBase['şaka'];
            return $responses[array_rand($responses)];
        }
        
        if (str_contains($msg, 'yoruldum') || str_contains($msg, 'bıktım') || str_contains($msg, 'biktim') || str_contains($msg, 'zor')) {
            $responses = $knowledgeBase['yoruldum'];
            return $responses[array_rand($responses)];
        }
        
        return null; // Casual değilse normal işleme devam et
    }

    /**
     * Sistem kullanım rehberi
     */
    private function handleSystemGuide(string $msg): ?string
    {
        $knowledgeBase = \App\Libraries\IkiheceKnowledgeBase::class;
        $faq = $knowledgeBase::getFAQ();
        
        // FAQ'de ara - Fuzzy matching ile
        foreach ($faq as $question => $answer) {
            if ($this->fuzzyMatch($msg, $question)) {
                return "📌 **" . ucfirst($question) . "?**\n\n" . $answer;
            }
        }
        
        // Genel rehber isteniyor
        if (str_contains($msg, 'rehber') || str_contains($msg, 'kullanım') || str_contains($msg, 'yardım') || str_contains($msg, 'nasıl kullanılır')) {
            return $knowledgeBase::getSystemGuide();
        }
        
        return null;
    }

    /**
     * Duyuru taslağı oluştur
     */
    private function handleAnnouncementDraft(string $msg): string
    {
        $knowledgeBase = \App\Libraries\IkiheceKnowledgeBase::class;
        $templates = $knowledgeBase::getAnnouncementTemplates();
        
        if (str_contains($msg, 'tatil')) {
            $template = $templates['tatil'];
            return "📢 **Tatil Duyuru Taslağı Hazırladım:**\n\n" .
                "**Başlık:** {$template['title']}\n\n" .
                "**İçerik:**\n{$template['content']}\n\n" .
                "💡 *Köşeli parantez içindeki yerleri kendi bilgilerinizle değiştirin.*";
        }
        
        if (str_contains($msg, 'toplantı') || str_contains($msg, 'toplanti')) {
            $template = $templates['toplanti'];
            return "📢 **Toplantı Duyuru Taslağı Hazırladım:**\n\n" .
                "**Başlık:** {$template['title']}\n\n" .
                "**İçerik:**\n{$template['content']}\n\n" .
                "💡 *Köşeli parantez içindeki yerleri kendi bilgilerinizle değiştirin.*";
        }
        
        if (str_contains($msg, 'etkinlik')) {
            $template = $templates['etkinlik'];
            return "📢 **Etkinlik Duyuru Taslağı Hazırladım:**\n\n" .
                "**Başlık:** {$template['title']}\n\n" .
                "**İçerik:**\n{$template['content']}\n\n" .
                "💡 *Köşeli parantez içindeki yerleri kendi bilgilerinizle değiştirin.*";
        }
        
        // Genel duyuru taslağı
        return "📢 **Genel Duyuru Taslağı:**\n\n" .
            "**Başlık:** [Duyuru Başlığı]\n\n" .
            "**İçerik:**\nSayın [Velilerimiz/Öğretmenlerimiz/Personelimiz],\n\n" .
            "[Duyuru içeriğinizi buraya yazın]\n\n" .
            "Bilgilerinize sunarız.\n\nİkihece Özel Eğitim Kurumu\n\n" .
            "💡 *Daha spesifik taslak için 'tatil duyurusu yaz' veya 'toplantı duyurusu yaz' diyebilirsiniz.*";
    }

    /**
     * Eğitim programı isimlerini kısalt
     */
    private function formatProgramNames(string $programs): string
    {
        $map = [
            'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı' => 'Otizm',
            'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı' => 'Zihinsel',
            'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı' => 'Öğrenme Güçlüğü',
            'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı' => 'Dil ve Konuşma',
            'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı' => 'Bedensel'
        ];
        
        $shortNames = [];
        foreach ($map as $long => $short) {
            if (str_contains($programs, $long)) {
                $shortNames[] = $short;
            }
        }
        
        return !empty($shortNames) ? implode(', ', $shortNames) : $programs;
    }

/**
 * AKILLI ÖNERİ SİSTEMİ - MODEL KULLANIMI
 * Eğitim programı + Mesafe + Ders Hakkı + Geçmiş Gelme Verisi
 */
private function buildSmartSuggestions(string &$context, string $msg): void
{
    $studentModel = new \App\Models\StudentModel();
    $lessonModel = new \App\Models\LessonModel();
    $lessonStudentModel = new \App\Models\LessonStudentModel();
    $lessonHistoryModel = new \App\Models\LessonHistoryModel();
    
    // Tarih belirleme
    $targetDate = $this->extractDateFromMessage($msg);
    
    if (str_contains($msg, 'yarın')) {
        $dateLabel = 'YARIN (' . date('d.m.Y', strtotime($targetDate)) . ')';
    } elseif (str_contains($msg, 'bugün')) {
        $dateLabel = 'BUGÜN (' . date('d.m.Y', strtotime($targetDate)) . ')';
    } else {
        $dateLabel = date('d.m.Y', strtotime($targetDate));
    }
    
    $dayOfWeek = date('N', strtotime($targetDate)); // 1=Pazartesi, 7=Pazar
    $mysqlDayOfWeek = ($dayOfWeek % 7) + 1; // MySQL format: 1=Pazar, 2=Pazartesi
    
    // Eğitim programı belirleme - FUZZY MATCHING İLE
    $programFilter = null;
    $programLabel = '';
    
    $programKeywords = [
        'otizm' => [
            'keywords' => ['otizm', 'otizim', 'otzm', 'spektrum'],
            'fullName' => 'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
            'label' => 'OTİZM TANILI'
        ],
        'zihinsel' => [
            'keywords' => ['zihinsel', 'zihin', 'mental'],
            'fullName' => 'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
            'label' => 'ZİHİNSEL'
        ],
        'öğrenme güçlüğü' => [
            'keywords' => ['öğrenme güçlüğü', 'öğrenme', 'güçlük', 'learning'],
            'fullName' => 'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı',
            'label' => 'ÖĞRENME GÜÇLÜĞÜ'
        ],
        'dil ve konuşma' => [
            'keywords' => ['dil ve konuşma', 'dil', 'konuşma', 'konusma'],
            'fullName' => 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
            'label' => 'DİL VE KONUŞMA'
        ],
        'bedensel' => [
            'keywords' => ['bedensel', 'beden', 'fiziksel'],
            'fullName' => 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
            'label' => 'BEDENSEL'
        ]
    ];
    
    foreach ($programKeywords as $key => $data) {
        if ($this->fuzzyContainsKeywords($msg, $data['keywords'])) {
            $programFilter = $data['fullName'];
            $programLabel = $data['label'];
            break;
        }
    }
    
    // Mesafe filtresi - FUZZY MATCHING İLE
    $distanceFilter = null;
    $distanceLabel = '';
    
    $distanceKeywords = [
        'Civar' => ['civar', 'civr', 'yakın civarda', 'çevrede'],
        'Yakın' => ['yakın', 'yakin', 'yakinda', 'yakn'],
        'Uzak' => ['uzak', 'uzakta', 'uzk']
    ];
    
    foreach ($distanceKeywords as $distance => $keywords) {
        if ($this->fuzzyContainsKeywords($msg, $keywords)) {
            $distanceFilter = $distance;
            $distanceLabel = ' + ' . strtoupper($distance) . ' MESAFE';
            break;
        }
    }
    
    // Ders hakkı filtresi
    $entitlementLimit = null;
    $entitlementLabel = '';
    
    if ($this->fuzzyContainsKeywords($msg, ['5 saat', '5saat', 'beş saat', 'acil'])) {
        $entitlementLimit = 5;
        $entitlementLabel = ' + ACİL DERS HAKKI (≤5 SAAT)';
    } elseif ($this->fuzzyContainsKeywords($msg, ['10 saat', '10saat', 'on saat', 'azalan'])) {
        $entitlementLimit = 10;
        $entitlementLabel = ' + DERS HAKKI AZALAN (≤10 SAAT)';
    }
    
    // Öğrencileri çek - filtrelerle
    $query = $studentModel
        ->select('students.id, students.adi, students.soyadi, students.egitim_programi, students.mesafe,
                  students.normal_bireysel_hak, students.normal_grup_hak, 
                  students.telafi_bireysel_hak, students.telafi_grup_hak,
                  students.veli_anne_telefon, students.veli_baba_telefon')
        ->where('students.deleted_at', null);
    
    if ($programFilter) {
        $query->like('students.egitim_programi', $programFilter);
    }
    
    if ($distanceFilter) {
        $query->where('students.mesafe', $distanceFilter);
    }
    
    $allStudents = $query->asArray()->findAll();
    
    $context .= "\n" . str_repeat("=", 70) . "\n";
    $context .= "🎯 AKILLI ÖNERİ SİSTEMİ\n";
    $context .= "{$dateLabel} GELMESİ MUHTEMEL";
    if (!empty($programLabel)) $context .= " {$programLabel}";
    $context .= " ÖĞRENCİLER{$distanceLabel}{$entitlementLabel}\n";
    $context .= str_repeat("=", 70) . "\n\n";
    
    $suggestions = [];
    $startDate = date('Y-m-d', strtotime('-90 days'));
    
    foreach ($allStudents as $student) {
        $studentFullName = $student['adi'] . ' ' . $student['soyadi'];
        
        // Toplam hak hesapla
        $totalHak = ($student['normal_bireysel_hak'] ?? 0) + 
                    ($student['normal_grup_hak'] ?? 0) + 
                    ($student['telafi_bireysel_hak'] ?? 0) + 
                    ($student['telafi_grup_hak'] ?? 0);
        
        // Ders hakkı filtresi varsa kontrol et
        if ($entitlementLimit && ($totalHak <= 0 || $totalHak > $entitlementLimit)) {
            continue;
        }
        
        // lessons tablosundan gelme sayısı
        $lessonCount = $lessonModel
            ->select('COUNT(DISTINCT lessons.id) as count')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
            ->where('ls.student_id', $student['id'])
            ->where('DAYOFWEEK(lessons.lesson_date)', $mysqlDayOfWeek)
            ->where('lessons.lesson_date >=', $startDate)
            ->asArray()
            ->first();
        
        // lesson_history tablosundan gelme sayısı
        $historyCount = $lessonHistoryModel
            ->where('student_name', $studentFullName)
            ->where('DAYOFWEEK(lesson_date)', $mysqlDayOfWeek)
            ->where('lesson_date >=', $startDate)
            ->countAllResults();
        
        $totalVisits = ($lessonCount['count'] ?? 0) + $historyCount;
        
        // Son gelme tarihlerini al
        $recentDates = $lessonModel
            ->select('lessons.lesson_date')
            ->join('lesson_students ls', 'ls.lesson_id = lessons.id')
            ->where('ls.student_id', $student['id'])
            ->where('DAYOFWEEK(lessons.lesson_date)', $mysqlDayOfWeek)
            ->where('lessons.lesson_date >=', $startDate)
            ->orderBy('lessons.lesson_date', 'DESC')
            ->limit(5)
            ->asArray()
            ->findAll();
        
        $datesList = array_map(fn($d) => date('d.m.Y', strtotime($d['lesson_date'])), $recentDates);
        
        $suggestions[] = [
            'name' => $studentFullName,
            'visits' => $totalVisits,
            'program' => $student['egitim_programi'],
            'mesafe' => $student['mesafe'],
            'hak' => $totalHak,
            'anne_tel' => $student['veli_anne_telefon'] ?? 'Yok',
            'baba_tel' => $student['veli_baba_telefon'] ?? 'Yok',
            'dates' => implode(', ', $datesList)
        ];
    }
    
    // Gelme sayısına göre sırala
    usort($suggestions, fn($a, $b) => $b['visits'] <=> $a['visits']);
    
    if (!empty($suggestions)) {
        $highProbability = array_filter($suggestions, fn($s) => $s['visits'] >= 3);
        $mediumProbability = array_filter($suggestions, fn($s) => $s['visits'] >= 1 && $s['visits'] < 3);
        $lowProbability = array_filter($suggestions, fn($s) => $s['visits'] == 0);
        
        // YÜKSEK OLASILIK
        if (!empty($highProbability)) {
            $context .= "🟢 YÜKSEK OLASILIK (Geçmişte bu günü 3+ kez tercih etmiş)\n";
            $context .= str_repeat("-", 70) . "\n";
            
            foreach ($highProbability as $s) {
                $urgency = $s['hak'] <= 5 ? '🔴 ACİL' : ($s['hak'] <= 10 ? '🟡 DİKKAT' : '');
                
                $context .= "\n{$urgency} {$s['name']}\n";
                $context .= "  📊 Geçmişte bu günde {$s['visits']} kez gelmiş\n";
                $context .= "  📚 Program: " . $this->formatProgramNames($s['program']) . "\n";
                $context .= "  📍 Mesafe: {$s['mesafe']}\n";
                $context .= "  ⏰ Kalan Hak: {$s['hak']} saat\n";
                $context .= "  📞 Anne: {$s['anne_tel']} | Baba: {$s['baba_tel']}\n";
                if (!empty($s['dates'])) {
                    $context .= "  📅 Son geldiği tarihler: {$s['dates']}\n";
                }
            }
            $context .= "\n";
        }
        
        // ORTA OLASILIK
        if (!empty($mediumProbability)) {
            $context .= "🟡 ORTA OLASILIK (Geçmişte bu günü 1-2 kez tercih etmiş)\n";
            $context .= str_repeat("-", 70) . "\n";
            
            foreach ($mediumProbability as $s) {
                $urgency = $s['hak'] <= 5 ? '🔴 ACİL' : ($s['hak'] <= 10 ? '🟡 DİKKAT' : '');
                
                $context .= "\n{$urgency} {$s['name']}\n";
                $context .= "  📊 Geçmişte bu günde {$s['visits']} kez gelmiş\n";
                $context .= "  📚 Program: " . $this->formatProgramNames($s['program']) . "\n";
                $context .= "  📍 Mesafe: {$s['mesafe']}\n";
                $context .= "  ⏰ Kalan Hak: {$s['hak']} saat\n";
                $context .= "  📞 Anne: {$s['anne_tel']} | Baba: {$s['baba_tel']}\n";
            }
            $context .= "\n";
        }
        
        // DÜŞÜK OLASILIK (sadece ders hakkı azalanlarda göster)
        if (!empty($lowProbability) && $entitlementLimit) {
            $context .= "🟠 DÜŞÜK OLASILIK (Daha önce bu günü tercih etmemiş ama ders hakkı azalan)\n";
            $context .= str_repeat("-", 70) . "\n";
            
            foreach (array_slice($lowProbability, 0, 5) as $s) {
                $urgency = $s['hak'] <= 5 ? '🔴 ACİL' : '🟡 DİKKAT';
                
                $context .= "\n{$urgency} {$s['name']}\n";
                $context .= "  📚 Program: " . $this->formatProgramNames($s['program']) . "\n";
                $context .= "  📍 Mesafe: {$s['mesafe']}\n";
                $context .= "  ⏰ Kalan Hak: {$s['hak']} saat\n";
                $context .= "  📞 Anne: {$s['anne_tel']} | Baba: {$s['baba_tel']}\n";
            }
            $context .= "\n";
        }
        
        $context .= "📊 TOPLAM: " . count($suggestions) . " öğrenci\n";
        $context .= "   • Yüksek Olasılık: " . count($highProbability) . "\n";
        $context .= "   • Orta Olasılık: " . count($mediumProbability) . "\n";
        if ($entitlementLimit) {
            $context .= "   • Düşük Olasılık (Acil): " . min(5, count($lowProbability)) . "\n";
        }
        
    } else {
        $context .= "Bu kriterlere uygun öğrenci bulunamadı.\n";
        $context .= "\n💡 ÖNERİ: Filtreleri gevşetebilirsiniz:\n";
        $context .= "   • Mesafe filtresi olmadan deneyin\n";
        $context .= "   • Ders hakkı filtresi olmadan deneyin\n";
        $context .= "   • Farklı bir eğitim programı ile deneyin\n";
    }
    
    $context .= "\n[NOT: Son 90 günün history verileri analiz edildi]\n";
}

    /**
     * Öğretmen Detaylı Analizi - MODEL KULLANIMI
     */
    private function buildTeacherDetailedAnalysis(string &$context, string $msg): void
    {
        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        $userProfileModel = new \App\Models\UserProfileModel();
        $lessonModel = new \App\Models\LessonModel();
        $lessonStudentModel = new \App\Models\LessonStudentModel();
        $evaluationModel = new \App\Models\StudentEvaluationModel();
        $authGroupsUsersModel = new \App\Models\AuthGroupsUsersModel();
        
        // Öğretmen adını bul
        $teacherId = $this->findSystemUserIdInMessage($this->turkish_strtolower($msg));
        
        if (!$teacherId) {
            $context .= "\n=== ÖĞRETMEN DETAYLI ANALİZİ ===\n";
            $context .= "Öğretmen adı tespit edilemedi. Lütfen '[Öğretmen Adı Soyadı]' formatında yazın.\n";
            return;
        }
        
        // Öğretmen kontrolü
        $isTeacher = $authGroupsUsersModel
            ->where('user_id', $teacherId)
            ->where('group', 'ogretmen')
            ->first();
        
        if (!$isTeacher) {
            $context .= "\n=== ÖĞRETMEN DETAYLI ANALİZİ ===\n";
            $context .= "Bu kullanıcı öğretmen rolünde değil.\n";
            return;
        }
        
        // Öğretmen bilgilerini al
        $teacher = $userModel
            ->select('users.id, users.username')
            ->where('users.id', $teacherId)
            ->where('users.deleted_at', null)
            ->asArray()
            ->first();
        
        if (!$teacher) {
            $context .= "\n=== ÖĞRETMEN DETAYLI ANALİZİ ===\n";
            $context .= "Öğretmen bilgileri bulunamadı.\n";
            return;
        }
        
        // Profil bilgilerini al
        $profile = $userProfileModel
            ->where('user_id', $teacherId)
            ->asArray()
            ->first();
        
        // Email bilgisini al (auth_identities tablosundan)
        $db = \Config\Database::connect();
        $identity = $db->table('auth_identities')
            ->select('secret')
            ->where('user_id', $teacherId)
            ->where('type', 'email_password')
            ->get()
            ->getRowArray();
        
        $fullName = ($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '');
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "📊 {$fullName} - DETAYLI PERFORMANS ANALİZİ\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        // KİŞİSEL BİLGİLER
        $context .= "👤 KİŞİSEL BİLGİLER\n";
        $context .= str_repeat("-", 70) . "\n";
        $context .= "Ad Soyad: {$fullName}\n";
        $context .= "Kullanıcı Adı: {$teacher['username']}\n";
        $context .= "Telefon: " . ($profile['phone_number'] ?? 'Belirtilmemiş') . "\n";
        $context .= "E-posta: " . ($identity['secret'] ?? 'Belirtilmemiş') . "\n";
        $context .= "Branş: " . ($profile['branch'] ?? 'Belirtilmemiş') . "\n";
        $context .= "Adres: " . ($profile['address'] ?? 'Belirtilmemiş') . "\n\n";
        
        // DERS İSTATİSTİKLERİ
        // Bu ay
        $thisMonth = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('MONTH(lesson_date)', date('m'))
            ->where('YEAR(lesson_date)', date('Y'))
            ->countAllResults();
        
        // Bu yıl
        $thisYear = $lessonModel
            ->where('teacher_id', $teacherId)
            ->where('YEAR(lesson_date)', date('Y'))
            ->countAllResults();
        
        // Bireysel ve grup ders sayıları
        $allLessons = $lessonModel
            ->select('lessons.id')
            ->where('lessons.teacher_id', $teacherId)
            ->where('YEAR(lessons.lesson_date)', date('Y'))
            ->asArray()
            ->findAll();
        
        $bireysel = 0;
        $grup = 0;
        
        foreach ($allLessons as $lesson) {
            $studentCount = $lessonStudentModel
                ->where('lesson_id', $lesson['id'])
                ->countAllResults();
            
            if ($studentCount == 1) {
                $bireysel++;
            } elseif ($studentCount > 1) {
                $grup++;
            }
        }
        
        $context .= "📚 DERS İSTATİSTİKLERİ\n";
        $context .= str_repeat("-", 70) . "\n";
        $context .= "Bu Ay Toplam Ders: {$thisMonth} ders\n";
        $context .= "Bu Yıl Toplam Ders: {$thisYear} ders\n";
        
        $total = $bireysel + $grup;
        if ($total > 0) {
            $bireysekOran = round(($bireysel / $total) * 100);
            $grupOran = round(($grup / $total) * 100);
            $context .= "Bireysel Ders: {$bireysel} ders (%{$bireysekOran})\n";
            $context .= "Grup Ders: {$grup} ders (%{$grupOran})\n";
        }
        $context .= "\n";
        
        // AYLIK DAĞILIM (Son 6 Ay)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $targetMonth = date('Y-m', strtotime("-{$i} months"));
            $count = $lessonModel
                ->where('teacher_id', $teacherId)
                ->where('DATE_FORMAT(lesson_date, "%Y-%m")', $targetMonth)
                ->countAllResults();
            
            if ($count > 0) {
                $monthlyStats[] = [
                    'month' => date('F Y', strtotime($targetMonth . '-01')),
                    'count' => $count
                ];
            }
        }
        
        if (!empty($monthlyStats)) {
            $context .= "📅 AYLIK DAĞILIM (Son 6 Ay)\n";
            $context .= str_repeat("-", 70) . "\n";
            foreach ($monthlyStats as $stat) {
                $context .= "{$stat['month']}: {$stat['count']} ders\n";
            }
            $context .= "\n";
        }
        
        // ÖĞRENCİ ANALİZİ
        $studentIds = $lessonStudentModel
            ->select('student_id')
            ->distinct()
            ->join('lessons', 'lessons.id = lesson_students.lesson_id')
            ->where('lessons.teacher_id', $teacherId)
            ->asArray()
            ->findColumn('student_id');
        
        $studentCount = count($studentIds);
        
        // En çok ders yapılan öğrenciler
        $topStudents = [];
        if (!empty($studentIds)) {
            $studentModel = new \App\Models\StudentModel();
            
            foreach ($studentIds as $studentId) {
                $lessonCount = $lessonStudentModel
                    ->join('lessons', 'lessons.id = lesson_students.lesson_id')
                    ->where('lessons.teacher_id', $teacherId)
                    ->where('lesson_students.student_id', $studentId)
                    ->countAllResults();
                
                $student = $studentModel->find($studentId);
                if ($student) {
                    $topStudents[] = [
                        'name' => $student['adi'] . ' ' . $student['soyadi'],
                        'count' => $lessonCount
                    ];
                }
            }
            
            usort($topStudents, fn($a, $b) => $b['count'] <=> $a['count']);
            $topStudents = array_slice($topStudents, 0, 3);
        }
        
        $context .= "👨‍🎓 ÖĞRENCİ ANALİZİ\n";
        $context .= str_repeat("-", 70) . "\n";
        $context .= "Toplam Farklı Öğrenci: {$studentCount} öğrenci\n";
        
        if (!empty($topStudents)) {
            foreach ($topStudents as $i => $s) {
                $rank = $i + 1;
                $label = $rank == 1 ? 'En Çok Ders Yapılan' : "{$rank}. Sırada";
                $context .= "{$label}: {$s['name']} ({$s['count']} ders)\n";
            }
        }
        $context .= "\n";
        
        // GELİŞİM GÜNLÜĞÜ KATKILARI
        $evaluationCount = $evaluationModel
            ->where('teacher_id', $teacherId)
            ->countAllResults();
        
        $thisMonthEval = $evaluationModel
            ->where('teacher_id', $teacherId)
            ->where('MONTH(created_at)', date('m'))
            ->where('YEAR(created_at)', date('Y'))
            ->countAllResults();
        
        $context .= "📝 GELİŞİM GÜNLÜĞÜ KATKILARI\n";
        $context .= str_repeat("-", 70) . "\n";
        $context .= "Toplam Not Sayısı: {$evaluationCount} gelişim notu\n";
        $context .= "Bu Ay Yazılan: {$thisMonthEval} not\n";
        
        if ($evaluationCount > 0 && $thisYear > 0) {
            $avgPerMonth = round($evaluationCount / 12, 1);
            $context .= "Ortalama Not Sıklığı: Ayda ~{$avgPerMonth} not\n";
        }
        $context .= "\n";
        
        // ÇALIŞMA DESENİ (Son 3 ay - en yoğun günler)
        $recentLessons = $lessonModel
            ->select('lesson_date')
            ->where('teacher_id', $teacherId)
            ->where('lesson_date >=', date('Y-m-d', strtotime('-3 months')))
            ->asArray()
            ->findAll();
        
        $dayPattern = [];
        foreach ($recentLessons as $lesson) {
            $dayName = date('l', strtotime($lesson['lesson_date']));
            $dayPattern[$dayName] = ($dayPattern[$dayName] ?? 0) + 1;
        }
        
        arsort($dayPattern);
        
        if (!empty($dayPattern)) {
            $gunIsimleri = [
                'Sunday' => 'Pazar', 'Monday' => 'Pazartesi', 'Tuesday' => 'Salı',
                'Wednesday' => 'Çarşamba', 'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi'
            ];
            
            $context .= "⏰ ÇALIŞMA DESENİ (Son 3 Ay)\n";
            $context .= str_repeat("-", 70) . "\n";
            
            $i = 0;
            foreach ($dayPattern as $day => $count) {
                if ($i >= 3) break;
                $gunAdi = $gunIsimleri[$day] ?? $day;
                $label = $i == 0 ? 'En Yoğun Gün' : ($i + 1) . ". Yoğun";
                $context .= "{$label}: {$gunAdi} ({$count} ders)\n";
                $i++;
            }
            $context .= "\n";
        }
        
        // ÖNERİLER
        $context .= "💡 ÖNERİLER\n";
        $context .= str_repeat("-", 70) . "\n";
        
        if (!empty($dayPattern)) {
            $enSakinGun = array_key_last($dayPattern);
            $gunAdi = $gunIsimleri[$enSakinGun] ?? $enSakinGun;
            $context .= "• {$gunAdi} günü daha fazla ders planlanabilir (şu an {$dayPattern[$enSakinGun]} ders)\n";
        }
        
        if ($grup == 0 && $bireysel > 10) {
            $context .= "• Grup dersi kapasitesi değerlendirilebilir\n";
        }
        
        if ($evaluationCount > 50) {
            $context .= "• Gelişim notu yazma performansı çok iyi seviyede\n";
        } elseif ($evaluationCount < 10 && $studentCount > 5) {
            $context .= "• Gelişim notu yazma sıklığı artırılabilir\n";
        }
        
        $context .= "\n";
    }
/**
     * Öğrenci detaylı analizi - GELİŞTİRİLMİŞ VERSİYON
     */
    private function buildStudentDetailedAnalysis(string &$context, string $msg): void
    {
        log_message('debug', 'buildStudentDetailedAnalysis ÇAĞRILDI: ' . $msg);
        
        $studentModel = new \App\Models\StudentModel();
        $lessonModel = new \App\Models\LessonModel();
        
        // Öğrenci ismini çıkar
        $studentName = $this->extractStudentName($msg);
        if (!$studentName) {
            $context .= "\n❌ Öğrenci ismi bulunamadı.\n";
            log_message('error', 'Öğrenci ismi çıkarılamadı: ' . $msg);
            return;
        }
        
        log_message('debug', 'Çıkarılan öğrenci ismi: ' . $studentName);
        
        // Öğrenciyi veritabanından bul
        $nameParts = explode(' ', $studentName);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';
        
        $student = $studentModel
            ->where('adi', $firstName)
            ->where('soyadi', $lastName)
            ->where('deleted_at', null)
            ->first();
        
        if (!$student) {
            $context .= "\n❌ '{$studentName}' isimli öğrenci sistemde bulunamadı.\n";
            log_message('error', 'Öğrenci bulunamadı: ' . $studentName);
            return;
        }
        
        log_message('debug', 'Öğrenci bulundu: ID=' . $student['id']);
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "ÖĞRENCİ DETAYLI ANALİZ: {$studentName}\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        // 1️⃣ KİŞİSEL BİLGİLER
        $context .= "=== KİŞİSEL BİLGİLER ===\n";
        $context .= "Ad Soyad: {$student['adi']} {$student['soyadi']}\n";
        $context .= "TC No: " . ($student['tckn'] ?? 'Belirtilmemiş') . "\n";
        $context .= "Doğum Tarihi: " . ($student['dogum_tarihi'] ? date('d.m.Y', strtotime($student['dogum_tarihi'])) : 'Yok') . "\n";
        
        if ($student['dogum_tarihi']) {
            $birthDate = new \DateTime($student['dogum_tarihi']);
            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;
            $context .= "Yaş: {$age}\n";
        }
        
        $context .= "Cinsiyet: " . ($student['cinsiyet'] ?? 'Belirtilmemiş') . "\n";
        $context .= "Eğitim Programı: " . ($student['egitim_programi'] ?? 'Yok') . "\n\n";
        
        // 2️⃣ İLETİŞİM BİLGİLERİ
        $context .= "=== İLETİŞİM BİLGİLERİ ===\n";
        $context .= "Anne Tel: " . ($student['veli_anne_telefon'] ?? 'Yok') . "\n";
        $context .= "Baba Tel: " . ($student['veli_baba_telefon'] ?? 'Yok') . "\n";
        $context .= "Mesafe: " . ($student['mesafe'] ?? 'Belirtilmemiş') . "\n";
        $context .= "Servis: " . ($student['servis'] ?? 'Belirtilmemiş') . "\n\n";
        
        // 3️⃣ DERS HAKKI BİLGİLERİ
        $context .= "=== DERS HAKKI ===\n";
        $totalHours = ($student['normal_bireysel_hak'] ?? 0) + 
                      ($student['normal_grup_hak'] ?? 0) + 
                      ($student['telafi_bireysel_hak'] ?? 0) + 
                      ($student['telafi_grup_hak'] ?? 0);
        
        $context .= "Toplam Kalan: {$totalHours} saat\n";
        $context .= "  • Normal Bireysel: " . ($student['normal_bireysel_hak'] ?? 0) . " saat\n";
        $context .= "  • Normal Grup: " . ($student['normal_grup_hak'] ?? 0) . " saat\n";
        $context .= "  • Telafi Bireysel: " . ($student['telafi_bireysel_hak'] ?? 0) . " saat\n";
        $context .= "  • Telafi Grup: " . ($student['telafi_grup_hak'] ?? 0) . " saat\n";
        
        if ($totalHours <= 5) {
            $context .= "\n⚠️ ACİL: Ders hakkı kritik seviyede!\n";
        } elseif ($totalHours <= 10) {
            $context .= "\n⚠️ DİKKAT: Ders hakkı azalıyor!\n";
        }
        $context .= "\n";
        
        // 4️⃣ DERS İSTATİSTİKLERİ
        $context .= "=== DERS İSTATİSTİKLERİ ===\n";
        
        // Bu ayki dersler
        $thisMonth = date('Y-m');
        $thisMonthLessons = $lessonModel
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->where('lesson_students.student_id', $student['id'])
            ->like('lessons.lesson_date', $thisMonth)
            ->countAllResults();
        
        $context .= "Bu Ay: {$thisMonthLessons} ders\n";
        
        // Toplam dersler
        $totalLessons = $lessonModel
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->where('lesson_students.student_id', $student['id'])
            ->countAllResults();
        
        $context .= "Toplam: {$totalLessons} ders\n";
        
        // Son ders tarihi
        $lastLesson = $lessonModel
            ->select('lessons.lesson_date')
            ->join('lesson_students', 'lesson_students.lesson_id = lessons.id')
            ->where('lesson_students.student_id', $student['id'])
            ->orderBy('lessons.lesson_date', 'DESC')
            ->first();
        
        if ($lastLesson) {
            $lastDate = date('d.m.Y', strtotime($lastLesson['lesson_date']));
            $context .= "Son Ders: {$lastDate}\n";
        }
        $context .= "\n";
        
        // 5️⃣ ÇALIŞILAN ÖĞRETMENLER
        $teachers = $studentModel->getTeachersForStudent($student['id']);
        
        if (!empty($teachers)) {
            $context .= "=== ÇALIŞILAN ÖĞRETMENLER ===\n";
            foreach ($teachers as $teacher) {
                $context .= "• {$teacher['first_name']} {$teacher['last_name']}\n";
            }
            $context .= "\n";
        }
        
        // 6️⃣ RAM RAPORU VE ANALİZ
        $context .= "=== RAM RAPORU VE ANALİZ ===\n";
        
        log_message('debug', 'RAM raporu kontrol ediliyor: ' . ($student['ram_raporu'] ?? 'NULL'));
        
        // RAM Dosya Kontrolü
        if (!empty($student['ram_raporu'])) {
            $ramPath = FCPATH . 'uploads/ram/' . $student['ram_raporu'];
            log_message('debug', 'RAM path: ' . $ramPath);
            log_message('debug', 'Dosya var mı: ' . (file_exists($ramPath) ? 'EVET' : 'HAYIR'));
            
            if (file_exists($ramPath)) {
                $fileSize = filesize($ramPath);
                $fileSizeKB = round($fileSize / 1024, 2);
                $context .= "✅ RAM Raporu Dosyası Mevcut\n";
                $context .= "Dosya Adı: {$student['ram_raporu']}\n";
                $context .= "Dosya Boyutu: {$fileSizeKB} KB\n\n";
            } else {
                $context .= "⚠️ RAM raporu kaydı var ama dosya bulunamadı\n";
                $context .= "Kayıtlı Dosya Adı: {$student['ram_raporu']}\n\n";
            }
        } else {
            $context .= "❌ RAM raporu yüklenmemiş\n\n";
        }
        
        // RAM Raporu Analiz Verileri
        $ramAnalysisModel = new \App\Models\RamReportAnalysisModel();
        $ramAnalysis = $ramAnalysisModel
            ->where('student_id', $student['id'])
            ->orderBy('analyzed_at', 'DESC')
            ->first();
        
        if ($ramAnalysis) {
            log_message('debug', 'RAM analizi bulundu: ID=' . $ramAnalysis['id']);
            
            $context .= "📊 RAM Raporu Analiz Verileri:\n";
            $context .= "Analiz Tarihi: " . date('d.m.Y H:i', strtotime($ramAnalysis['analyzed_at'])) . "\n\n";
            
            // Hafıza Bilgileri (eğer varsa)
            if (!empty($ramAnalysis['total_memory'])) {
                $context .= "🧠 Hafıza Bilgileri:\n";
                $context .= "Toplam Hafıza: {$ramAnalysis['total_memory']}\n";
                $context .= "Kullanılabilir Hafıza: {$ramAnalysis['available_memory']}\n";
                
                if (!empty($ramAnalysis['memory_info'])) {
                    $context .= "Ek Bilgiler: {$ramAnalysis['memory_info']}\n";
                }
                $context .= "\n";
            }
            
            // RAM Metin İçeriği (ilk 1000 karakter)
            if (!empty($ramAnalysis['ram_text_content'])) {
                $textLength = strlen($ramAnalysis['ram_text_content']);
                $displayText = substr($ramAnalysis['ram_text_content'], 0, 1000);
                
                $context .= "📄 RAM Raporu İçerik Özeti:\n";
                $context .= trim($displayText);
                
                if ($textLength > 1000) {
                    $context .= "...\n(Toplam {$textLength} karakter, ilk 1000 karakter gösteriliyor)\n";
                } else {
                    $context .= "\n";
                }
                $context .= "\n";
            }
        } else {
            log_message('debug', 'RAM analizi bulunamadı');
            $context .= "ℹ️ RAM raporu analizi henüz yapılmamış\n\n";
        }
        
// 7️⃣ GELİŞİM NOTLARI (EVALUATIONS)
        $evaluationModel = new \App\Models\StudentEvaluationModel();
        
        $context .= "=== GELİŞİM GÜNLÜĞÜ ===\n";
        
        // Toplam not sayısı
        $evalCount = $evaluationModel
            ->where('student_id', $student['id'])
            ->countAllResults();
        
        $context .= "Toplam Not Sayısı: {$evalCount} gelişim notu\n";
        
        // Son 3 notu göster
        if ($evalCount > 0) {
            $recentEvals = $evaluationModel
                ->where('student_id', $student['id'])
                ->orderBy('created_at', 'DESC')
                ->limit(3)
                ->findAll();
            
            $context .= "\nSon Gelişim Notları:\n";
            foreach ($recentEvals as $i => $eval) {
                $date = date('d.m.Y H:i', strtotime($eval['created_at']));
                $teacher = $eval['teacher_snapshot_name'] ?? 'Bilinmiyor';
                $notePreview = mb_substr($eval['evaluation'], 0, 150);
                
                $context .= "\n" . ($i + 1) . ". Not - {$date} ({$teacher}):\n";
                $context .= "\"{$notePreview}";
                if (mb_strlen($eval['evaluation']) > 150) {
                    $context .= "...\"";
                } else {
                    $context .= "\"";
                }
                $context .= "\n";
            }
        } else {
            $context .= "Henüz gelişim notu yazılmamış.\n";
        }
        $context .= "\n";
        
        log_message('debug', 'Gelişim notları eklendi: ' . $evalCount . ' not bulundu');
        
        log_message('debug', 'buildStudentDetailedAnalysis TAMAMLANDI');
    }

    /**
     * Öğretmen iletişim bilgilerini getir
     */
    private function buildTeacherContactInfo(string &$context, string $msg): void
    {
        $db = \Config\Database::connect();
        
        // Öğretmen adını bul
        $teacherId = $this->findSystemUserIdInMessage($this->turkish_strtolower($msg));
        
        if (!$teacherId) {
            $context .= "\n=== ÖĞRETMEN İLETİŞİM BİLGİLERİ ===\n";
            $context .= "Öğretmen adı tespit edilemedi. Lütfen tam ad ve soyad belirtin.\n";
            return;
        }
        
        // Öğretmen kontrolü
        $isTeacher = $db->query("
            SELECT COUNT(*) as count 
            FROM auth_groups_users 
            WHERE user_id = ? AND `group` = 'ogretmen'
        ", [$teacherId])->getRowArray();
        
        if ($isTeacher['count'] == 0) {
            $context .= "\n=== ÖĞRETMEN İLETİŞİM BİLGİLERİ ===\n";
            $context .= "Bu kullanıcı öğretmen rolünde değil.\n";
            return;
        }
        
        // Öğretmen bilgilerini çek - CodeIgniter Shield uyumlu
        $teacher = $db->query("
            SELECT 
                up.first_name,
                up.last_name,
                up.phone_number,
                up.branch,
                ai.secret as email,
                up.address
            FROM users u
            INNER JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN auth_identities ai ON u.id = ai.user_id AND ai.type = 'email_password'
            WHERE u.id = ? AND u.deleted_at IS NULL
        ", [$teacherId])->getRowArray();

        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "ÖĞRETMEN İLETİŞİM BİLGİLERİ\n";
        $context .= str_repeat("=", 70) . "\n\n";

        if ($teacher) {
            $context .= "Ad Soyad: {$teacher['first_name']} {$teacher['last_name']}\n";
            $context .= "Branş: " . ($teacher['branch'] ?? 'Belirtilmemiş') . "\n";
            $context .= "Telefon: " . ($teacher['phone_number'] ?? 'Belirtilmemiş') . "\n";
            $context .= "E-posta: " . ($teacher['email'] ?? 'Belirtilmemiş') . "\n";
            $context .= "Adres: " . ($teacher['address'] ?? 'Belirtilmemiş') . "\n";
        } else {
            $context .= "Öğretmen bilgileri bulunamadı.\n";
        }
    }

    /**
     * Öğretmenlerin branşlara göre dağılımını context'e ekler.
     */
    private function buildTeacherBranchDistribution(string &$context): void
    {
        $db = \Config\Database::connect();

        // user_profiles ve auth_groups_users tablolarını birleştirerek
        // sadece 'ogretmen' grubundaki kullanıcıların branşlarını sayıyoruz.
        $branchStats = $db->table('user_profiles up')
            ->select('up.branch, COUNT(up.user_id) as total')
            ->join('auth_groups_users agu', 'agu.user_id = up.user_id')
            ->where('agu.group', 'ogretmen')
            ->where('up.branch IS NOT NULL')
            ->where('up.branch !=', '')
            ->groupBy('up.branch')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "👨‍🏫 ÖĞRETMEN BRANŞ DAĞILIMI\n";
        $context .= str_repeat("=", 70) . "\n\n";

        if (!empty($branchStats)) {
            foreach ($branchStats as $stat) {
                $context .= "{$stat['branch']}: {$stat['total']} öğretmen\n";
            }
        } else {
            $context .= "Branşlara göre gruplandırılacak öğretmen verisi bulunamadı.\n";
        }

        // Branşı olmayan öğretmen sayısını da bulalım (ek bilgi olarak)
        $noBranchCount = $db->table('auth_groups_users agu')
            ->join('user_profiles up', 'agu.user_id = up.user_id', 'left')
            ->where('agu.group', 'ogretmen')
            ->where('(up.branch IS NULL OR up.branch = "")')
            ->countAllResults();

        if ($noBranchCount > 0) {
            $context .= "Branşı Belirtilmemiş: {$noBranchCount} öğretmen\n";
        }
    }

        /**
         * Öğrenci ismini mesajdan çıkarır
         */
        private function extractStudentName(string $message): ?string
        {
            $studentModel = new \App\Models\StudentModel();
            $students = $studentModel->select('adi, soyadi')->findAll();
            
            foreach ($students as $student) {
                $fullName = $student['adi'] . ' ' . $student['soyadi'];
                if (stripos($message, $fullName) !== false) {
                    return $fullName;
                }
            }
            
            return null;
        }
        
        /**
         * AI yanıtını tipine göre formatlar
         */
        private function getAIResponse(string $context, string $userMessage, string $type): string
        {
            $systemPrompts = [
                'student_analysis' => "Sen İkihece'nin yapay zeka asistanısın.

    Öğrenci detaylı analiz verilerini NET ve PROFESYONEL şekilde sun:

    **SUNUM STİLİ:**
    - Kategorilere göre başlıklar kullan
    - Önemli sayıları **kalın** yaz
    - Pozitif gelişmeleri vurgula
    - İyileştirme alanlarını yapıcı belirt
    - Öneriler bölümünü mutlaka ekle

    Profesyonel, net ve veri odaklı ol.",
                
                'teacher_analysis' => "Sen İkihece'nin yapay zeka asistanısın.

    Öğretmen detaylı analiz verilerini NET ve PROFESYONEL şekilde sun:

    **SUNUM STİLİ:**
    - Kategorilere göre başlıklar kullan (KİŞİSEL BİLGİLER, DERS İSTATİSTİKLERİ)
    - Önemli sayıları **kalın** yaz
    - Pozitif performansı vurgula
    - Geliştirme önerileri sun

    Profesyonel ve saygılı ol.",
                
                'general' => "Sen İkihece Özel Eğitim Kurumu'nun AI asistanısın.

    Admin ile konuşuyorsun. Yetkilerin:
    - Tam veritabanı erişimi
    - SQL sorguları (SADECE SELECT)
    - Detaylı analizler
    - Sistem logları

    Profesyonel, net ve aksiyona dönük cevaplar ver."
            ];
            
            $systemPrompt = $systemPrompts[$type] ?? $systemPrompts['general'];
            $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
            
            return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
        }

    /**
    * Öğretmen ismini mesajdan çıkarır
    */
        private function extractTeacherName(string $message): ?string
    {
        $db = \Config\Database::connect();
        
        // Öğretmen grubundaki kullanıcıları çek
        $teachers = $db->query("
            SELECT u.id, up.first_name, up.last_name
            FROM users u
            INNER JOIN user_profiles up ON u.id = up.user_id
            INNER JOIN auth_groups_users agu ON u.id = agu.user_id
            WHERE agu.group = 'ogretmen'
            AND u.deleted_at IS NULL
            ORDER BY up.first_name, up.last_name
        ")->getResultArray();
        
        log_message('debug', 'Öğretmen sayısı: ' . count($teachers));
        
        foreach ($teachers as $teacher) {
            $fullName = $teacher['first_name'] . ' ' . $teacher['last_name'];
            
            // Mesajda tam isim geçiyor mu kontrol et
            if (stripos($message, $fullName) !== false) {
                log_message('debug', 'Öğretmen bulundu: ' . $fullName);
                return $fullName;
            }
        }
        
        log_message('debug', 'Mesajda öğretmen ismi bulunamadı: ' . $message);
        return null;
    }
}
