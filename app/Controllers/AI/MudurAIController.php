<?php

namespace App\Controllers\AI;

class MudurAIController extends BaseAIController
{
    /**
     * Müdür mesajını işler ve gerekli istatistikleri toplayarak prompt oluşturur.
     */
    public function process(string $userMessage, object $user, array $history = []): string
    {
        $role = $this->getUserRole($user);
        
        // RAM Raporu Analizi
        $userMessageLower = $this->turkish_strtolower($userMessage);
        if (preg_match("/(.+?).*öğrencinin.*ram.*raporu.*(analizi|analizini).*ver/iu", $userMessage, $matches) || 
            preg_match("/(.+?).*ram.*raporu.*(analiz.*yap|analizi nedir|analizini ver)/iu", $userMessage, $matches)) {
            $studentName = trim($matches[1]);
            $studentId = $this->findStudentIdInMessage($studentName);
            return $this->handleSharedRamReportQuery($studentId, $studentName, $role);
        }

        // 1. Sistem Promptunu Hazırla
        $systemPrompt = $this->getSystemPrompt($role, $user);

        // 2. Eğer "boşluk", "boş saat", "müsait" gibi ifadeler varsa, boşluk analizi yap ve ekle
        $lowerMsg = mb_strtolower($userMessage);
        if (strpos($lowerMsg, 'boş') !== false || strpos($lowerMsg, 'müsait') !== false || strpos($lowerMsg, 'program') !== false) {
            $gapsContext = $this->calculateScheduleGaps();
            $systemPrompt .= "\n\nEKSTRA BAĞLAM (ÖĞRETMEN DERS BOŞLUKLARI):\n" . $gapsContext;
        }

        return $this->aiService->getChatResponse($userMessage, $systemPrompt, $history);
    }

    protected function getSystemPrompt(string $role, object $user): string
    {
        $studentModel = new \App\Models\StudentModel();
        $userProfileModel = new \App\Models\UserProfileModel();

        // --- A. ANLIK İSTATİSTİKLER (DASHBOARD) ---
        
        // 1. Genel Sayılar
        $totalStudents = $studentModel->where('deleted_at', null)->countAllResults();
        $totalTeachers = count($userProfileModel->getTeachers()); // getTeachers array döner

        // 2. Cinsiyet Dağılımı
        $girls = $studentModel->where('cinsiyet', 'Kadın')->where('deleted_at', null)->countAllResults();
        $boys = $studentModel->where('cinsiyet', 'Erkek')->where('deleted_at', null)->countAllResults();

        // 3. Eğitim Programları (Basit LIKE sorguları ile)
        $programs = [
            'Fizik Tedavi (Bedensel)' => $studentModel->like('egitim_programi', 'Bedensel')->where('deleted_at', null)->countAllResults(),
            'Dil ve Konuşma' => $studentModel->like('egitim_programi', 'Dil')->where('deleted_at', null)->countAllResults(),
            'Zihinsel Engelliler' => $studentModel->like('egitim_programi', 'Zihinsel')->where('deleted_at', null)->countAllResults(),
            'Öğrenme Güçlüğü' => $studentModel->like('egitim_programi', 'Öğrenme')->where('deleted_at', null)->countAllResults(),
            'Otizm (OSB)' => $studentModel->like('egitim_programi', 'Otizm')->where('deleted_at', null)->countAllResults(),
        ];
        $programStats = "";
        foreach ($programs as $name => $count) {
            $programStats .= "- $name: $count öğrenci\n";
        }

        // 4. Yaş İstatistikleri
        $db = db_connect();
        $ageStats = $db->query("
            SELECT 
                AVG(TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE())) as avg_age,
                MIN(TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE())) as min_age,
                MAX(TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE())) as max_age
            FROM students 
            WHERE deleted_at IS NULL AND dogum_tarihi IS NOT NULL
        ")->getRow();
        
        $avgAge = number_format($ageStats->avg_age ?? 0, 1);
        $minAge = $ageStats->min_age ?? 0;
        $maxAge = $ageStats->max_age ?? 0;

        // --- B. PROMPT OLUŞTURMA ---
        return "Sen İkihece OTS'nin 'Pusula' adındaki yönetici asistanısın (Müdür Modu).
        Şu an kurum müdürü ile konuşuyorsun.

        GÖREVİN:
        Kurumla ilgili istatistiksel sorulara, listeleme isteklerine ve eksik bilgi kontrollerine HIZLI ve DOĞRU cevap vermek.
        Müdür için stratejik öngörülerde bulunmak ve genel işleyişi raporlamak.

        MEVCUT ANLIK DURUM (DASHBOARD):
        - Toplam Öğrenci: $totalStudents
        - Toplam Öğretmen: $totalTeachers
        - Cinsiyet Dağılımı: $girls Kız, $boys Erkek
        - Yaş Ortalaması: $avgAge (En küçük: $minAge, En büyük: $maxAge)
        - Eğitim Programlarına Göre Dağılım:
        $programStats

        YETENEKLERİN VE İPUÇLARI:
        1. **GENEL SORULAR:** Yukarıdaki 'MEVCUT ANLIK DURUM' verilerini kullanarak sayısal soruları (Kaç öğrenci var? Kaç otizmli var? vb.) hemen cevapla.
        
        2. **LİSTELEME VE DETAYLI ANALİZ (SQL KULLAN):**
           Kullanıcı detaylı bir liste isterse (Örn: 'RAM raporu bitenleri listele'), `run_sql_query` aracını kullanarak veritabanından çek.
           
           Aşağıdaki SQL İPUÇLARINI kullan:
           - **RAM Raporu Bitenler:** `SELECT adi, soyadi, ram_bitis FROM students WHERE ram_bitis < CURDATE() AND deleted_at IS NULL`
           - **RAM Raporu Olmayanlar:** `SELECT adi, soyadi FROM students WHERE (ram_raporu IS NULL OR ram_raporu = '') AND deleted_at IS NULL`
           - **Konum Bilgisi Eksik:** `SELECT adi, soyadi FROM students WHERE (google_konum IS NULL OR google_konum = '') AND deleted_at IS NULL`
           - **İletişim Bilgisi Eksik:** `SELECT adi, soyadi FROM students WHERE (iletisim IS NULL OR iletisim = '') AND deleted_at IS NULL`
           - **Veli Bilgisi Eksik:** `SELECT adi, soyadi FROM students WHERE (veli_anne IS NULL AND veli_baba IS NULL) AND deleted_at IS NULL`
           - **Yaş Analizi (En Büyük/Küçük):** `SELECT adi, soyadi, dogum_tarihi FROM students WHERE deleted_at IS NULL ORDER BY dogum_tarihi ASC (veya DESC) LIMIT 10`
           - **Devamsızlık (En Çok):** `SELECT s.adi, s.soyadi, COUNT(sa.id) as devamsizlik_sayisi FROM students s JOIN student_absences sa ON s.id = sa.student_id GROUP BY s.id ORDER BY devamsizlik_sayisi DESC LIMIT 10`
           - **Gelişim Günlüğü Yazılmamış:** `SELECT s.adi, s.soyadi FROM students s LEFT JOIN student_evaluations se ON s.id = se.student_id WHERE se.id IS NULL AND s.deleted_at IS NULL`
           - **Sabit Dersi Olmayanlar:** `SELECT s.adi, s.soyadi FROM students s LEFT JOIN fixed_lessons fl ON s.id = fl.student_id WHERE fl.id IS NULL AND s.deleted_at IS NULL`
           - **Aynı Yaş ve Tanı:** `SELECT adi, soyadi, egitim_programi, TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE()) as yas FROM students WHERE deleted_at IS NULL ORDER BY egitim_programi, yas`

        3. **ÜSLUP:**
           - Net, profesyonel, saygılı ve sonuç odaklı ol.
           - Listeleri maddeler halinde sun.
           - Eğer SQL sorgusu çalıştıracaksan, kullanıcıya 'Veritabanını kontrol ediyorum...' gibi bir geri bildirim ver.
        ";
    }

    protected function getUserRole($user): string
    {
        return 'Müdür';
    }

    /**
     * Öğretmenlerin sabit ders programındaki boşlukları hesaplar.
     */
    private function calculateScheduleGaps(): string
    {
        $userProfileModel = new \App\Models\UserProfileModel();
        $fixedLessonModel = new \App\Models\FixedLessonModel();

        // 1. Tüm Öğretmenleri Getir
        $teachers = $userProfileModel->getTeachers();
        
        // 2. Tüm Sabit Dersleri Getir
        $allFixedLessons = $fixedLessonModel->findAll();
        
        // 3. Dersleri Öğretmen ve Güne Göre Grupla
        $scheduleMap = [];
        foreach ($allFixedLessons as $fl) {
            $tId = $fl['teacher_id'];
            $day = $fl['day_of_week'];
            // start_time '09:00:00' formatında gelebilir, ilk 5 karakteri al
            $time = substr($fl['start_time'], 0, 5); 
            $scheduleMap[$tId][$day][$time] = true;
        }

        // 4. Boşlukları Analiz Et
        $days = [
            1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 
            4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi'
        ];
        $slots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

        $output = "";
        $count = 0;

        foreach ($teachers as $teacher) {
            $tId = $teacher['user_id']; // getTeachers array'inde user_id var
            $tName = $teacher['first_name'] . ' ' . $teacher['last_name'];
            
            $teacherGaps = [];
            
            foreach ($days as $dayNum => $dayName) {
                $emptySlots = [];
                foreach ($slots as $slot) {
                    if (!isset($scheduleMap[$tId][$dayNum][$slot])) {
                        $emptySlots[] = $slot;
                    }
                }
                
                // Eğer o gün hiç ders yoksa "Tüm Gün Boş" de, yoksa saatleri yaz
                if (count($emptySlots) == count($slots)) {
                    $teacherGaps[] = "$dayName: TÜM GÜN";
                } elseif (!empty($emptySlots)) {
                    // Çok fazla saat varsa özetle
                    if (count($emptySlots) > 5) {
                        $teacherGaps[] = "$dayName: " . count($emptySlots) . " saat boş";
                    } else {
                        $teacherGaps[] = "$dayName: " . implode(', ', $emptySlots);
                    }
                }
            }

            if (!empty($teacherGaps)) {
                $output .= "- $tName: " . implode(' | ', $teacherGaps) . "\n";
                $count++;
            }
            
            // Token limitini korumak için max 15 öğretmen listele
            if ($count >= 15) {
                $output .= "... (ve diğerleri)";
                break;
            }
        }

        return empty($output) ? "Tüm öğretmenlerin programı tamamen dolu." : $output;
    }
}