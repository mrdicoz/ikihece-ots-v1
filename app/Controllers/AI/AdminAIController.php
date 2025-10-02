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
        
        // Öğretmen analizleri
        if ($this->containsKeywords($userMessageLower, ['öğretmen', 'hoca']) && 
            $this->containsKeywords($userMessageLower, ['ders sayı', 'kaç ders', 'listele', 'en çok'])) {
            $this->buildTeacherLessonStatsContext($context, $userMessageLower);
        }
        
        // Mesafe bazlı öğrenci analizleri
        if ($this->containsKeywords($userMessageLower, ['mesafe', 'civar', 'yakın', 'uzak'])) {
            $this->buildDistanceBasedStudentsContext($context, $userMessageLower);
        }
        
        // Eğitim programı analizleri
        if ($this->containsKeywords($userMessageLower, ['program', 'bedensel', 'dil ve konuşma', 'zihinsel', 'öğrenme güçlüğü', 'otizm'])) {
            $this->buildEducationProgramStatsContext($context, $userMessageLower);
        }
        
        // Sabit program analizleri
        if ($this->containsKeywords($userMessageLower, ['sabit program', 'düzenli gelen', 'sabit ders'])) {
            $this->buildFixedProgramAnalysisContext($context, $userMessageLower);
        }
        
        // Yarın için alternatif öneriler
        if ($this->containsKeywords($userMessageLower, ['yarın', 'boşluk', 'alternatif', 'öner'])) {
            $this->buildTomorrowAlternativesContext($context, $userMessageLower);
        }

        // Yarın için boş saat analizi
        if ($this->containsKeywords($userMessageLower, ['yarın', 'boş saat', 'boş saatler', 'tavsiye'])) {
            $this->buildTomorrowEmptySlotsSuggestions($context, $userMessageLower);
        }

        // Öğretmen + saat bazlı öğrenci önerisi - DÜZELTME
        if ($this->containsKeywords($userMessageLower, ['saat', 'dersi için', 'için alternatif']) && 
            ($this->containsKeywords($userMessageLower, ['öğretmen', 'hoca', 'öğretmenin', 'hocanın']) || 
            $this->findTeacherNameInMessage($userMessageLower) !== null)) {
            $this->buildTeacherTimeBasedSuggestions($context, $userMessageLower);
        }
        
        // Ders hakkı analizleri
        if ($this->containsKeywords($userMessageLower, ['ders hak', 'hak azal', 'hak bit'])) {
            $this->buildEntitlementAnalysisContext($context, $userMessageLower);
        }
        
        // Rapor talebi
        if ($this->containsKeywords($userMessageLower, ['rapor', 'özet', 'faaliyet'])) {
            $this->buildReportContext($context, $userMessageLower);
        }
        
        // Sistem istatistikleri
        if ($this->containsKeywords($userMessageLower, ['sistem', 'istatistik', 'toplam', 'kaç', 'sayı'])) {
            $this->buildSystemStatisticsContext($context);
        }

        // RAM raporu analizi
        if ($this->containsKeywords($userMessageLower, ['ram', 'rapor'])) {
            $this->buildRAMReportAnalysisContext($context, $userMessageLower);
        }

        // Log talebi
        if ($this->containsKeywords($userMessageLower, ['log', 'işlem', 'sistem'])) {
            $this->buildLogContext($context);
        }
        
        $context .= "[BAĞLAM SONU]\n";
        
        $systemPrompt = "İkihece için tasarlanmış bir yapay zeka asistanısın.

**KRITIK KURAL: ASLA UYDURMA YAPMA!**
- Sadece [BAĞLAM BAŞLANGICI] ve [BAĞLAM SONU] arasındaki bilgileri kullan
- Eğer veri yoksa 'Bu bilgi bağlamda yok' de
- Sahte isim, sayı veya veri ÜRETME
- SQL sorgusu hata verirse gerçek hatayı söyle

**Şu an bir ADMİN ile konuşuyorsun.**

Profesyonel, net ve veri odaklı cevaplar ver.";
        
        $userPrompt = $context . "\n\nKullanıcının Sorusu: '{$userMessage}'";
        return $this->aiService->getChatResponse($userPrompt, $systemPrompt);
    }
    
    /**
     * Öğretmenlerin ders sayıları - DOĞRU TABLO YAPISI
     */
    private function buildTeacherLessonStatsContext(string &$context, string $msg): void
    {
        $db = \Config\Database::connect();
        
        $isWeekly = str_contains($msg, 'hafta');
        $dateFilter = $isWeekly 
            ? "l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            : "MONTH(l.lesson_date) = MONTH(CURDATE()) AND YEAR(l.lesson_date) = YEAR(CURDATE())";
        
        $query = "SELECT 
            up.first_name, 
            up.last_name, 
            up.branch,
            COUNT(DISTINCT l.id) as ders_sayisi
        FROM users u
        INNER JOIN user_profiles up ON u.id = up.user_id
        INNER JOIN auth_groups_users agu ON u.id = agu.user_id
        INNER JOIN lessons l ON u.id = l.teacher_id
        WHERE agu.group = 'ogretmen' 
            AND u.deleted_at IS NULL
            AND {$dateFilter}
        GROUP BY u.id, up.first_name, up.last_name, up.branch
        ORDER BY ders_sayisi DESC";
        
        $teachers = $db->query($query)->getResultArray();
        
        $period = $isWeekly ? "BU HAFTA" : "BU AY";
        $context .= "\n=== ÖĞRETMEN DERS İSTATİSTİKLERİ ({$period}) ===\n\n";
        
        if (!empty($teachers)) {
            $total = 0;
            foreach ($teachers as $t) {
                $context .= "Öğretmen: {$t['first_name']} {$t['last_name']}";
                if (!empty($t['branch'])) $context .= " ({$t['branch']})";
                $context .= " - {$t['ders_sayisi']} ders\n";
                $total += $t['ders_sayisi'];
            }
            $avg = count($teachers) > 0 ? round($total / count($teachers), 2) : 0;
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
    $distance = 'Civar';
    if (str_contains($msg, 'yakın')) $distance = 'Yakın';
    if (str_contains($msg, 'uzak')) $distance = 'Uzak';
    
    $db = \Config\Database::connect();
    
    // ARTIK HER ZAMAN HISTORY TEMELLİ ANALİZ
    $todayDayOfWeek = date('N');
    
    // HEM lessons HEM lesson_history tablosundan veri çek
    $query = "SELECT 
        s.adi, 
        s.soyadi,
        s.mesafe,
        s.egitim_programi,
        COUNT(DISTINCT COALESCE(l.lesson_date, lh.lesson_date)) as bu_gun_gelme_sayisi,
        (s.normal_bireysel_hak + s.normal_grup_hak + s.telafi_bireysel_hak + s.telafi_grup_hak) as toplam_hak,
        s.veli_anne_telefon,
        s.veli_baba_telefon,
        GROUP_CONCAT(DISTINCT COALESCE(DATE_FORMAT(l.lesson_date, '%d.%m.%Y'), DATE_FORMAT(lh.lesson_date, '%d.%m.%Y')) ORDER BY COALESCE(l.lesson_date, lh.lesson_date) DESC SEPARATOR ', ') as son_gelme_tarihleri
    FROM students s
    LEFT JOIN lesson_students ls ON s.id = ls.student_id
    LEFT JOIN lessons l ON ls.lesson_id = l.id 
        AND DAYOFWEEK(l.lesson_date) = ?
        AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    LEFT JOIN lesson_history lh ON CONCAT(s.adi, ' ', s.soyadi) = lh.student_name
        AND DAYOFWEEK(lh.lesson_date) = ?
        AND lh.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    WHERE s.mesafe = ? 
        AND s.deleted_at IS NULL
        AND (s.normal_bireysel_hak + s.normal_grup_hak + s.telafi_bireysel_hak + s.telafi_grup_hak) > 0
    GROUP BY s.id
    HAVING bu_gun_gelme_sayisi >= 2
    ORDER BY bu_gun_gelme_sayisi DESC, s.adi
    LIMIT 30";
    
    $mysqlDayOfWeek = ($todayDayOfWeek % 7) + 1;
    $students = $db->query($query, [$mysqlDayOfWeek, $mysqlDayOfWeek, $distance])->getResultArray();
    
    $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
    $bugun = $gunler[$todayDayOfWeek];
    
    // Başlık - "bugün" kelimesi varsa özel başlık
    if (str_contains($msg, 'bugün') || str_contains($msg, 'gelmesi muhtemel')) {
        $context .= "\n=== BUGÜN ({$bugun}) GELMESİ MUHTEMEL {$distance} MESAFE ÖĞRENCİLER ===\n";
    } else {
        $context .= "\n=== {$distance} MESAFE ÖĞRENCİLER (Geçmiş Verilere Göre) ===\n";
    }
    
    $context .= "(Son 90 günde bu günde en az 2 kez ders almış)\n";
    $context .= "(lessons + lesson_history verilerinden analiz)\n\n";
    
    if (!empty($students)) {
        foreach ($students as $s) {
            $context .= "Öğrenci: {$s['adi']} {$s['soyadi']}\n";
            $context .= "Bu günde geçmişte: {$s['bu_gun_gelme_sayisi']} kez gelmiş\n";
            $context .= "Program: {$s['egitim_programi']}\n";
            $context .= "Kalan Hak: {$s['toplam_hak']} saat\n";
            $context .= "Geçmiş Tarihler: {$s['son_gelme_tarihleri']}\n";
            $context .= "Tel: Anne {$s['veli_anne_telefon']}, Baba {$s['veli_baba_telefon']}\n\n";
        }
        $context .= "Toplam: " . count($students) . " öğrenci\n";
    } else {
        $context .= "Bu mesafe grubunda bugün ({$bugun}) gelmesi muhtemel öğrenci bulunamadı.\n";
        $context .= "(Son 90 günde bu günde en az 2 kez gelmiş öğrenci yok)\n";
    }
}
    
    /**
     * Eğitim programı istatistikleri - DOĞRU PROGRAM İSİMLERİ
     */
    private function buildEducationProgramStatsContext(string &$context, string $msg): void
    {
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
        
        $db = \Config\Database::connect();
        
        if ($selectedProgram) {
            // Tek program analizi
            $query = "SELECT 
                s.cinsiyet,
                COUNT(*) as sayi,
                AVG(TIMESTAMPDIFF(YEAR, s.dogum_tarihi, CURDATE())) as yas_ortalama
            FROM students s
            WHERE FIND_IN_SET(?, REPLACE(s.egitim_programi, ',', ',')) > 0
                AND s.deleted_at IS NULL
            GROUP BY s.cinsiyet";
            
            $stats = $db->query($query, [$selectedProgram])->getResultArray();
            
            $context .= "\n=== EĞİTİM PROGRAMI ANALİZİ ===\n";
            $context .= "Program: {$selectedProgram}\n\n";
            
            if (!empty($stats)) {
                $totalStudents = 0;
                $totalAge = 0;
                foreach ($stats as $stat) {
                    $cinsiyet = $stat['cinsiyet'] === 'erkek' ? 'Erkek' : 'Kız';
                    $yas = $stat['yas_ortalama'] ? round($stat['yas_ortalama'], 1) : 'Bilinmiyor';
                    $context .= "{$cinsiyet}: {$stat['sayi']} öğrenci (Yaş Ort: {$yas})\n";
                    $totalStudents += $stat['sayi'];
                    if ($stat['yas_ortalama']) $totalAge += $stat['yas_ortalama'];
                }
                $context .= "\nToplam: {$totalStudents} öğrenci\n";
                if ($totalAge > 0 && count($stats) > 0) {
                    $context .= "Genel Yaş Ortalaması: " . round($totalAge / count($stats), 1) . " yaş\n";
                }
            } else {
                $context .= "Bu programda kayıtlı öğrenci bulunamadı.\n";
            }
        } else {
            // Tüm programlar özeti
            $context .= "\n=== TÜM EĞİTİM PROGRAMLARI ÖZETİ ===\n\n";
            
            foreach ($programMap as $keyword => $fullName) {
                $query = "SELECT COUNT(*) as sayi 
                FROM students 
                WHERE FIND_IN_SET(?, REPLACE(egitim_programi, ',', ',')) > 0
                    AND deleted_at IS NULL";
                
                $result = $db->query($query, [$fullName])->getRowArray();
                $kisa = ucfirst($keyword);
                $context .= "{$kisa}: {$result['sayi']} öğrenci\n";
            }
        }
    }
    
    /**
     * Sabit program analizi
     */
    private function buildFixedProgramAnalysisContext(string &$context, string $msg): void
    {
        $db = \Config\Database::connect();
        
        if (str_contains($msg, 'olmayan') || str_contains($msg, 'düzenli')) {
            // Sabit programı olmayan ama son 30 günde düzenli gelen
            $query = "SELECT 
                s.adi,
                s.soyadi,
                COUNT(DISTINCT DATE(l.lesson_date)) as gelme_gun_sayisi,
                COUNT(DISTINCT ls.id) as toplam_ders
            FROM students s
            LEFT JOIN fixed_lessons fl ON s.id = fl.student_id
            INNER JOIN lesson_students ls ON s.id = ls.student_id
            INNER JOIN lessons l ON ls.lesson_id = l.id
            WHERE fl.id IS NULL
                AND s.deleted_at IS NULL
                AND l.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY s.id
            HAVING gelme_gun_sayisi >= 8
            ORDER BY gelme_gun_sayisi DESC
            LIMIT 20";
            
            $students = $db->query($query)->getResultArray();
            
            $context .= "\n=== SABİT PROGRAMI OLMAYAN AMA DÜZENLİ GELEN ÖĞRENCİLER ===\n";
            $context .= "(Son 30 günde 8+ gün gelmiş)\n\n";
            
            if (!empty($students)) {
                foreach ($students as $s) {
                    $context .= "Öğrenci: {$s['adi']} {$s['soyadi']} - {$s['gelme_gun_sayisi']} gün, {$s['toplam_ders']} ders\n";
                }
            } else {
                $context .= "Kriterlere uyan öğrenci bulunamadı.\n";
            }
        } else {
            // Sabit programı olan öğrenciler
            $query = "SELECT 
                s.adi,
                s.soyadi,
                COUNT(DISTINCT fl.id) as sabit_ders_sayisi
            FROM students s
            INNER JOIN fixed_lessons fl ON s.id = fl.student_id
            WHERE s.deleted_at IS NULL
            GROUP BY s.id
            ORDER BY sabit_ders_sayisi DESC";
            
            $students = $db->query($query)->getResultArray();
            
            $context .= "\n=== SABİT PROGRAMI OLAN ÖĞRENCİLER ===\n\n";
            
            if (!empty($students)) {
                foreach ($students as $s) {
                    $context .= "Öğrenci: {$s['adi']} {$s['soyadi']} - {$s['sabit_ders_sayisi']} sabit ders\n";
                }
                $context .= "\nToplam: " . count($students) . " öğrenci\n";
            } else {
                $context .= "Sabit programı olan öğrenci bulunamadı.\n";
            }
        }
    }
    
    /**
     * Yarın için alternatifler
     */
    private function buildTomorrowAlternativesContext(string &$context, string $msg): void
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $dayOfWeek = date('N', strtotime($tomorrow));
        
        $db = \Config\Database::connect();
        
        // Yarının sabit programı
        $query = "SELECT 
            fl.start_time,
            fl.end_time,
            s.adi,
            s.soyadi,
            up.first_name,
            up.last_name
        FROM fixed_lessons fl
        INNER JOIN students s ON fl.student_id = s.id
        INNER JOIN users u ON fl.teacher_id = u.id
        INNER JOIN user_profiles up ON u.id = up.user_id
        WHERE fl.day_of_week = ?
            AND s.deleted_at IS NULL
            AND u.deleted_at IS NULL
        ORDER BY fl.start_time, up.first_name";
        
        $fixedLessons = $db->query($query, [$dayOfWeek])->getResultArray();
        
        $context .= "\n=== YARININ SABİT PROGRAMI ({$tomorrow}) ===\n\n";
        
        if (!empty($fixedLessons)) {
            foreach ($fixedLessons as $fl) {
                $context .= "Saat {$fl['start_time']}-{$fl['end_time']}: ";
                $context .= "{$fl['adi']} {$fl['soyadi']} - ";
                $context .= "Öğretmen: {$fl['first_name']} {$fl['last_name']}\n";
            }
        } else {
            $context .= "Yarın için sabit program bulunamadı.\n";
        }
        
        // Alternatif öğrenciler (Civar ve Yakın mesafe)
        $context .= "\n=== ALTERNATİF ÖĞRENCİ ÖNERİLERİ ===\n\n";
        
        $query = "SELECT 
            s.adi,
            s.soyadi,
            s.mesafe,
            s.egitim_programi,
            (s.normal_bireysel_hak + s.normal_grup_hak + s.telafi_bireysel_hak + s.telafi_grup_hak) as toplam_hak,
            s.veli_anne_telefon,
            s.veli_baba_telefon
        FROM students s
        WHERE s.mesafe IN ('Civar', 'Yakın')
            AND (s.normal_bireysel_hak + s.normal_grup_hak + s.telafi_bireysel_hak + s.telafi_grup_hak) > 5
            AND s.deleted_at IS NULL
        ORDER BY 
            FIELD(s.mesafe, 'Civar', 'Yakın'),
            s.adi
        LIMIT 15";
        
        $alternatives = $db->query($query)->getResultArray();
        
        if (!empty($alternatives)) {
            foreach ($alternatives as $alt) {
                $context .= "Öğrenci: {$alt['adi']} {$alt['soyadi']} ({$alt['mesafe']})\n";
                $context .= "Program: {$alt['egitim_programi']}\n";
                $context .= "Kalan Hak: {$alt['toplam_hak']} saat\n";
                $context .= "Telefon - Anne: {$alt['veli_anne_telefon']}, Baba: {$alt['veli_baba_telefon']}\n\n";
            }
        } else {
            $context .= "Alternatif öğrenci bulunamadı.\n";
        }
    }
    
    /**
     * Ders hakkı analizi
     */
    private function buildEntitlementAnalysisContext(string &$context, string $msg): void
    {
        $limit = 10;
        if (str_contains($msg, '5')) $limit = 5;
        if (str_contains($msg, '15')) $limit = 15;
        
        $db = \Config\Database::connect();
        
        $query = "SELECT 
            s.adi,
            s.soyadi,
            s.normal_bireysel_hak,
            s.normal_grup_hak,
            s.telafi_bireysel_hak,
            s.telafi_grup_hak,
            (s.normal_bireysel_hak + s.normal_grup_hak + s.telafi_bireysel_hak + s.telafi_grup_hak) as toplam_hak,
            s.veli_anne_telefon,
            s.veli_baba_telefon
        FROM students s
        WHERE s.deleted_at IS NULL
        HAVING toplam_hak <= {$limit} AND toplam_hak > 0
        ORDER BY toplam_hak ASC
        LIMIT 30";
        
        $students = $db->query($query)->getResultArray();
        
        $context .= "\n=== DERS HAKKI {$limit} SAATİN ALTINDA OLAN ÖĞRENCİLER ===\n\n";
        
        if (!empty($students)) {
            foreach ($students as $s) {
                $urgency = $s['toplam_hak'] <= 3 ? 'ÇOK ACİL' : 'ACİL';
                $context .= "{$urgency} - {$s['adi']} {$s['soyadi']}: {$s['toplam_hak']} saat kaldı\n";
                $context .= "Normal: Bireysel {$s['normal_bireysel_hak']} + Grup {$s['normal_grup_hak']} | ";
                $context .= "Telafi: Bireysel {$s['telafi_bireysel_hak']} + Grup {$s['telafi_grup_hak']}\n";
                $context .= "Telefon - Anne: {$s['veli_anne_telefon']}, Baba: {$s['veli_baba_telefon']}\n\n";
            }
            $context .= "Toplam: " . count($students) . " öğrenci\n";
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
        $targetMonth = $this->extractMonthFromMessage($msg);
        $reportModel = new ReportModel();
        $report = $reportModel->where('report_month', $targetMonth)->first();

        $context .= "\n=== AYLIK RAPOR ({$targetMonth}) ===\n";

        if ($report) {
            $data = is_object($report) ? (array) $report : $report;
            $context .= "Toplam Ders: " . ($data['total_lessons'] ?? 0) . "\n";
            $context .= "Bireysel: " . ($data['individual_lessons'] ?? 0) . "\n";
            $context .= "Grup: " . ($data['group_lessons'] ?? 0) . "\n";
        } else {
            $context .= "Bu ay için rapor bulunamadı.\n";
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
     * Öğretmen + Saat + Gün bazlı öğrenci önerisi (lesson_history kullanarak)
     */
    private function buildTeacherTimeBasedSuggestions(string &$context, string $msg): void
    {
               $context .= "\n=== DEBUG: Bu metod çalıştı! ===\n"; // ← Bu satırı ekleyin

        $db = \Config\Database::connect();
        
        // Öğretmen adını bul
        $teacherName = $this->findTeacherNameInMessage($msg);
        
        // Saat bilgisini bul (14:00, 14, vs)
        $timeSlot = null;
        if (preg_match('/(\d{1,2})[:.]?(\d{2})?/', $msg, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minute = $matches[2] ?? '00';
            $timeSlot = "{$hour}:{$minute}";
        }
        
        // Gün bilgisi (bugün mü, yarın mı, Pazartesi mi?)
        $dayOfWeek = null;
        $gunler = ['pazartesi' => 2, 'salı' => 3, 'çarşamba' => 4, 'perşembe' => 5, 'cuma' => 6, 'cumartesi' => 7, 'pazar' => 1];
        
        foreach ($gunler as $gun => $mysqlDay) {
            if (str_contains($msg, $gun)) {
                $dayOfWeek = $mysqlDay;
                break;
            }
        }
        
        if (str_contains($msg, 'bugün')) {
            $dayOfWeek = (date('N') % 7) + 1;
        }
        if (str_contains($msg, 'yarın')) {
            $dayOfWeek = ((date('N') + 1) % 7) + 1;
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
            $gunIsimleri = ['', 'Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
            $context .= "Gün: {$gunIsimleri[$dayOfWeek]}\n";
        }
        $context .= "\n";
        
        // Öğretmenin branşını bul
        $branchQuery = "SELECT up.branch 
            FROM user_profiles up 
            WHERE CONCAT(up.first_name, ' ', up.last_name) LIKE ?
            LIMIT 1";
        
        $branchResult = $db->query($branchQuery, ["%{$teacherName}%"])->getRowArray();
        $teacherBranch = $branchResult['branch'] ?? null;
        
        if ($teacherBranch) {
            $context .= "Öğretmen Branşı: {$teacherBranch}\n\n";
        }
        
        // lesson_history'den öneriler
        $query = "SELECT 
            lh.student_name,
            lh.student_program,
            COUNT(*) as gelme_sayisi,
            GROUP_CONCAT(DISTINCT DATE_FORMAT(lh.lesson_date, '%d.%m.%Y') ORDER BY lh.lesson_date DESC SEPARATOR ', ') as tarihler
        FROM lesson_history lh
        WHERE 1=1";
        
        $params = [];
        
        if ($teacherBranch) {
            $query .= " AND lh.teacher_branch = ?";
            $params[] = $teacherBranch;
        } else {
            $query .= " AND lh.teacher_name LIKE ?";
            $params[] = "%{$teacherName}%";
        }
        
        if ($timeSlot) {
            $query .= " AND lh.start_time = ?";
            $params[] = $timeSlot;
        }
        
        if ($dayOfWeek) {
            $query .= " AND DAYOFWEEK(lh.lesson_date) = ?";
            $params[] = $dayOfWeek;
        }
        
        $query .= " AND lh.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
            GROUP BY lh.student_name, lh.student_program
            HAVING gelme_sayisi >= 2
            ORDER BY gelme_sayisi DESC
            LIMIT 20";
        
        $suggestions = $db->query($query, $params)->getResultArray();
        
        if (!empty($suggestions)) {
            $context .= "GEÇMİŞ VERİLERE GÖRE ÖNERİLEN ÖĞRENCİLER:\n\n";
            
            foreach ($suggestions as $sug) {
                $context .= "Öğrenci: {$sug['student_name']}\n";
                $context .= "Program: {$sug['student_program']}\n";
                $context .= "Bu koşullarda {$sug['gelme_sayisi']} kez ders almış\n";
                $context .= "Son tarihler: {$sug['tarihler']}\n";
                
                // Öğrencinin güncel ders hakkını kontrol et
                $studentParts = explode(' ', $sug['student_name']);
                if (count($studentParts) >= 2) {
                    $currentInfoQuery = "SELECT 
                        (normal_bireysel_hak + normal_grup_hak + telafi_bireysel_hak + telafi_grup_hak) as toplam_hak,
                        veli_anne_telefon, veli_baba_telefon
                        FROM students 
                        WHERE adi = ? AND soyadi = ? AND deleted_at IS NULL";
                    
                    $currentInfo = $db->query($currentInfoQuery, [$studentParts[0], $studentParts[1]])->getRowArray();
                    
                    if ($currentInfo) {
                        $context .= "Güncel Ders Hakkı: {$currentInfo['toplam_hak']} saat\n";
                        $context .= "Tel: Anne {$currentInfo['veli_anne_telefon']}, Baba {$currentInfo['veli_baba_telefon']}\n";
                    }
                }
                $context .= "\n";
            }
        } else {
            $context .= "Bu kriterlere uygun geçmiş ders verisi bulunamadı.\n";
            $context .= "Öneriler oluşturulamadı.\n";
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
     * Yarın boş saatler için öğrenci tavsiyesi (lesson_history bazlı)
     */
    private function buildTomorrowEmptySlotsSuggestions(string &$context, string $msg): void
    {
        $db = \Config\Database::connect();
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $tomorrowDayOfWeek = date('N', strtotime($tomorrow));
        $mysqlDayOfWeek = ($tomorrowDayOfWeek % 7) + 1;
        
        $gunler = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        $gun = $gunler[$tomorrowDayOfWeek];
        
        $context .= "\n" . str_repeat("=", 70) . "\n";
        $context .= "YARIN ({$tomorrow} - {$gun}) BOŞ SAATLER İÇİN ÖĞRENCİ TAVSİYELERİ\n";
        $context .= str_repeat("=", 70) . "\n\n";
        
        // Tüm öğretmenleri al
        $teachers = $db->query("
            SELECT u.id, up.first_name, up.last_name, up.branch
            FROM users u
            INNER JOIN user_profiles up ON u.id = up.user_id
            INNER JOIN auth_groups_users agu ON u.id = agu.user_id
            WHERE agu.group = 'ogretmen' 
                AND u.deleted_at IS NULL
            ORDER BY up.first_name
        ")->getResultArray();
        
        $allSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        
        foreach ($teachers as $teacher) {
            $teacherFullName = "{$teacher['first_name']} {$teacher['last_name']}";
            $teacherBranch = $teacher['branch'] ?? 'Belirtilmemiş';
            
            // Yarın bu öğretmenin dolu saatlerini bul
            $busySlots = $db->query("
                SELECT l.start_time 
                FROM lessons l
                WHERE l.teacher_id = ?
                    AND l.lesson_date = ?
            ", [$teacher['id'], $tomorrow])->getResultArray();
            
            $busyTimes = array_column($busySlots, 'start_time');
            $emptySlots = array_diff($allSlots, $busyTimes);
            
            if (!empty($emptySlots)) {
                $context .= "Öğretmen: {$teacherFullName} ({$teacherBranch})\n";
                $context .= "Boş Saatler: " . implode(', ', $emptySlots) . "\n\n";
                
                // Her boş saat için lesson_history'den öneri
                foreach ($emptySlots as $slot) {
                    $suggestions = $db->query("
                        SELECT 
                            lh.student_name,
                            lh.student_program,
                            COUNT(*) as gelme_sayisi
                        FROM lesson_history lh
                        WHERE lh.teacher_name LIKE ?
                            AND lh.start_time = ?
                            AND DAYOFWEEK(lh.lesson_date) = ?
                            AND lh.lesson_date >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
                        GROUP BY lh.student_name, lh.student_program
                        HAVING gelme_sayisi >= 2
                        ORDER BY gelme_sayisi DESC
                        LIMIT 3
                    ", ["%{$teacher['first_name']}%{$teacher['last_name']}%", $slot, $mysqlDayOfWeek])->getResultArray();
                    
                    if (!empty($suggestions)) {
                        $context .= "  └─ Saat {$slot} için öneriler:\n";
                        
                        foreach ($suggestions as $sug) {
                            // Öğrencinin güncel bilgilerini al
                            $studentParts = explode(' ', $sug['student_name']);
                            if (count($studentParts) >= 2) {
                                $currentStudent = $db->query("
                                    SELECT 
                                        (normal_bireysel_hak + normal_grup_hak + telafi_bireysel_hak + telafi_grup_hak) as toplam_hak,
                                        mesafe,
                                        veli_anne_telefon,
                                        veli_baba_telefon
                                    FROM students 
                                    WHERE adi = ? AND soyadi = ? AND deleted_at IS NULL
                                ", [$studentParts[0], $studentParts[1]])->getRowArray();
                                
                                if ($currentStudent && $currentStudent['toplam_hak'] > 0) {
                                    $context .= "     • {$sug['student_name']} ({$sug['student_program']})\n";
                                    $context .= "       Geçmişte bu gün/saatte {$sug['gelme_sayisi']} kez gelmiş\n";
                                    $context .= "       Mesafe: {$currentStudent['mesafe']}, Hak: {$currentStudent['toplam_hak']} saat\n";
                                    $context .= "       Tel: Anne {$currentStudent['veli_anne_telefon']}, Baba {$currentStudent['veli_baba_telefon']}\n";
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
                    adi, soyadi, ram, ram_baslagic, ram_bitis, ram_raporu,
                    hastane_adi, hastane_raporu_baslama_tarihi, hastane_raporu_bitis_tarihi
                FROM students 
                WHERE adi = ? AND soyadi = ? AND deleted_at IS NULL
            ", [$parts[0], $parts[1]])->getRowArray();
            
            if ($student) {
                $context .= "\n=== {$student['adi']} {$student['soyadi']} - RAM RAPORU ANALİZİ ===\n\n";
                
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
}