<?php

namespace App\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\StudentModel;
use App\Models\UserProfileModel;
use App\Models\LessonModel;
use App\Models\StudentEvaluationModel;
use App\Models\RamReportAnalysisModel;

class AIService
{
    private Client $client;
    private string $apiKey;
    private string $apiBaseUrl = 'https://api.deepseek.com/v1/';

    public function __construct()
    {
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
     * Ana sohbet fonksiyonu.
     * Kullanıcı mesajını alır, gerekirse araçları (tools) kullanır ve yanıt döner.
     */
    public function getChatResponse(string $userPrompt, string $systemPrompt = '', array $history = []): string
    {
        if (empty($this->apiKey)) {
            return "API anahtarı eksik. Lütfen sistem yöneticisiyle iletişime geçin.";
        }

        // 1. Araç Tanımları (Tools Definitions)
        $tools = $this->getToolsDefinition();

        // 2. Mesaj Geçmişini Hazırla
        $messages = $this->prepareMessages($userPrompt, $systemPrompt, $history);

        try {
            // 3. İlk API Çağrısı (Niyet Analizi ve Tool Seçimi)
            $response = $this->callApi($messages, $tools);
            
            // Eğer API bir araç çağırmak istiyorsa
            if (isset($response['tool_calls'])) {
                $toolCalls = $response['tool_calls'];
                
                // Tool çıktılarını mesajlara ekle
                $messages[] = $response; // Asistanın tool çağırma isteğini ekle

                foreach ($toolCalls as $toolCall) {
                    $functionName = $toolCall['function']['name'];
                    $arguments = json_decode($toolCall['function']['arguments'], true) ?? [];
                    
                    // Aracı çalıştır
                    $toolResult = $this->executeTool($functionName, $arguments);

                    // Sonucu mesajlara ekle
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE)
                    ];
                }

                // 4. İkinci API Çağrısı (Tool sonuçlarıyla final yanıtı üret)
                $finalResponse = $this->callApi($messages); // Tools göndermiyoruz, artık yoruma geçsin
                return $finalResponse['content'] ?? "Bir hata oluştu, yanıt üretilemedi.";
            }

            return $response['content'] ?? "Anlaşılamadı.";

        } catch (\Exception $e) {
            log_message('error', 'AI Service Error: ' . $e->getMessage());
            return "Sistem hatası: " . $e->getMessage();
        }
    }

    private function prepareMessages($userPrompt, $systemPrompt, $history)
    {
        $messages = [];
        
        // Sistem promptuna Tarih/Saat ve Veritabanı şemasını ekle
        $currentDate = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $dayName = date('l'); // Gün ismi (Monday, Tuesday...)
        
        // Gün isimlerini Türkçeleştir
        $days = [
            'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba', 
            'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'
        ];
        $dayNameTR = $days[$dayName] ?? $dayName;

        $schema = $this->getDatabaseSchema();
        $enhancedSystemPrompt = $systemPrompt . "\n\n" . 
            "ZAMAN BAĞLAMI:\n" .
            "- Şu anki Tarih ve Saat: $currentDate\n" .
            "- Bugün: $today ($dayNameTR)\n\n" .
            "VERİTABANI ŞEMASI (SQL Sorguları için):\n" . $schema . "\n\n" .
            "KURALLAR:\n" .
            "1. Karmaşık analizler veya veritabanında doğrudan karşılığı olmayan sorular için 'run_sql_query' aracını kullan.\n" .
            "2. Sadece SELECT sorguları yazabilirsin.\n" .
            "3. Tablo ve sütun isimlerini yukarıdaki şemadan kontrol et.";

        if (!empty($enhancedSystemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $enhancedSystemPrompt];
        }
        
        // Son 5 mesajı al (context window yönetimi)
        $recentHistory = array_slice($history, -5);
        foreach ($recentHistory as $turn) {
            if (!empty($turn['user'])) $messages[] = ['role' => 'user', 'content' => $turn['user']];
            if (!empty($turn['ai'])) $messages[] = ['role' => 'assistant', 'content' => $turn['ai']];
        }

        $messages[] = ['role' => 'user', 'content' => $userPrompt];
        return $messages;
    }

    private function callApi($messages, $tools = null)
    {
        $payload = [
            'model' => 'deepseek-chat',
            'messages' => $messages,
            'temperature' => 0.1
        ];

        if ($tools) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->client->post('chat/completions', ['json' => $payload]);
        $body = json_decode($response->getBody()->getContents(), true);

        return $body['choices'][0]['message'];
    }

    private function getToolsDefinition()
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'run_sql_query',
                    'description' => 'Veritabanında özel analizler yapmak için SQL SELECT sorgusu çalıştırır. Karmaşık sorular, sayımlar ve listelemeler için bunu kullan.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Çalıştırılacak SQL SELECT sorgusu']
                        ],
                        'required' => ['query']
                    ]
                ]
            ],
            // ... (other tools remain unchanged) ...
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_general_stats',
                    'description' => 'Kurumdaki toplam öğrenci, öğretmen ve ders sayılarını getirir.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[],
                        'required' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_student',
                    'description' => 'İsmi verilen öğrenci veya öğrencilerin temel bilgilerini (yaş, tanı, iletişim) getirir.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Öğrenci adı veya soyadı']
                        ],
                        'required' => ['query']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_student_details',
                    'description' => 'ID\'si verilen öğrencinin detaylı raporunu (RAM, gelişim, dersler) getirir.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'student_id' => ['type' => 'integer', 'description' => 'Öğrenci ID']
                        ],
                        'required' => ['student_id']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_teacher_report',
                    'description' => 'İsmi veya ID\'si verilen öğretmen hakkında bilgi verir.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'Öğretmen adı']
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_empty_lessons',
                    'description' => 'Bugün veya belirtilen tarihteki boş ders saatlerini listeler.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date' => ['type' => 'string', 'description' => 'Y-m-d formatında tarih (opsiyonel, boşsa bugün)']
                        ],
                        'required' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_student_absences',
                    'description' => 'Öğrencinin devamsızlık (gelmediği dersler) bilgisini getirir.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'student_id' => ['type' => 'integer', 'description' => 'Öğrenci ID']
                        ],
                        'required' => ['student_id']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'find_students_by_criteria',
                    'description' => 'Belirli bir tanıya, yaş grubuna veya RAM raporu içeriğine göre öğrenci önerir.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'diagnosis' => ['type' => 'string', 'description' => 'Tanı veya RAM raporunda geçen kelime'],
                            'min_age' => ['type' => 'integer', 'description' => 'Minimum yaş'],
                            'max_age' => ['type' => 'integer', 'description' => 'Maksimum yaş']
                        ],
                        'required' => ['diagnosis']
                    ]
                ]
            ]
        ];
    }

    private function executeTool($name, $args)
    {
        // 1. Öğrenci ID Çözümleme
        if (isset($args['student_name']) && !isset($args['student_id'])) {
            $resolvedId = $this->resolveStudentIdByName($args['student_name']);
            if ($resolvedId) {
                $args['student_id'] = $resolvedId;
            } else {
                return "Belirtilen isimde ('{$args['student_name']}') bir öğrenci bulunamadı veya birden fazla kayıt var. Lütfen tam isim belirtin.";
            }
        }

        // 2. Tool Yönlendirme
        switch ($name) {
            case 'run_sql_query':
                return $this->toolRunSqlQuery($args['query']);
                
            case 'get_general_stats':
                return $this->toolGetGeneralStats();
                
            case 'search_student':
                return $this->toolSearchStudent($args['query'] ?? ($args['student_name'] ?? ''));
                
            case 'get_student_details':
            case 'get_ram_report': 
                return $this->toolGetStudentDetails($args['student_id'] ?? 0);
                
            case 'get_teacher_report':
                return $this->toolGetTeacherReport($args['name'] ?? '');
                
            case 'get_empty_lessons':
                return $this->toolGetEmptyLessons($args['date'] ?? date('Y-m-d'));
                
            case 'get_student_absences':
            case 'get_attendance_history':
                return $this->toolGetStudentAbsences($args['student_id'] ?? 0);
                
            case 'find_students_by_criteria':
                return $this->toolFindStudentsByCriteria($args['diagnosis'] ?? '', $args['min_age'] ?? 0, $args['max_age'] ?? 99);
                
            default:
                return "Bilinmeyen işlem: $name";
        }
    }

    // ... (Other helper methods remain unchanged) ...

    private function toolRunSqlQuery($query)
    {
        // GÜVENLİK KONTROLÜ: Sadece SELECT sorgularına izin ver
        $query = trim($query);
        
        // Sadece SELECT ile başlamak zorunda değil, SELECT içeren güvenli bir sorgu olmalı
        // Örn: "SELECT ..." veya "(SELECT ...)"
        if (stripos($query, 'SELECT') === false) {
            return "GÜVENLİK UYARISI: Sadece veri okuma (SELECT) işlemleri yapabilirsiniz.";
        }
        
        // Tehlikeli komutları engelle (Daha katı kontrol)
        // UPDATE, DELETE, DROP, ALTER, TRUNCATE, INSERT, GRANT, REVOKE
        if (preg_match('/^\s*(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|REVOKE)\b/i', $query)) {
            return "GÜVENLİK UYARISI: Veri değiştirme veya silme komutları yasaktır.";
        }
        
        // İkinci katman: Sorgu içinde noktalı virgül (;) varsa ve sonrasında tehlikeli komut geliyorsa engelle
        if (preg_match('/;\s*(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|REVOKE)\b/i', $query)) {
             return "GÜVENLİK UYARISI: Çoklu sorgularda yasaklı komut tespit edildi.";
        }

        try {
            $db = db_connect();
            $result = $db->query($query)->getResultArray();
            
            if (empty($result)) {
                return "Sorgu çalıştı ancak sonuç dönmedi.";
            }
            
            // Çok fazla veri dönmesini engelle
            if (count($result) > 20) {
                $result = array_slice($result, 0, 20);
                $result[] = "... (Toplam " . count($result) . " satır, ilk 20 gösteriliyor)";
            }
            
            return $result;
        } catch (\Exception $e) {
            return "SQL Hatası: " . $e->getMessage();
        }
    }

    private function getDatabaseSchema()
    {
        return <<<SCHEMA
- students (id, adi, soyadi, tckn, dogum_tarihi, iletisim, ram_raporu, egitim_programi, created_at, deleted_at)
  * İPUCU: Yaş hesabı için MySQL fonksiyonu: TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE())
- users (id, username, active)
- user_profiles (user_id, first_name, last_name, branch, phone_number) -> users.id ile user_id eşleşir
- lessons (id, teacher_id, lesson_date, start_time, end_time) -> teacher_id, users.id'dir
- lesson_students (lesson_id, student_id) -> lessons ve students tablolarını bağlar
- student_evaluations (id, student_id, teacher_id, evaluation, created_at) -> Gelişim raporları
- student_absences (id, student_id, lesson_date, reason) -> Devamsızlıklar
SCHEMA;
    }

    /**
     * Yardımcı Metod: İsimden ID bulur
     */
    private function resolveStudentIdByName($name)
    {
        $model = new StudentModel();
        $name = trim($name);
        
        // Tam eşleşme ara
        $student = $model->where("CONCAT(adi, ' ', soyadi)", $name)
                        ->where('deleted_at', null)
                        ->first();
                        
        if ($student) return $student['id'];
        
        // Benzerlik ara
        $students = $model->like("CONCAT(adi, ' ', soyadi)", $name)
                         ->where('deleted_at', null)
                         ->findAll(2);
                         
        // Eğer tek bir benzer sonuç varsa onu kabul et
        if (count($students) === 1) {
            return $students[0]['id'];
        }
        
        return null;
    }

    // -------------------------------------------------------------------------
    // TOOL IMPLEMENTATIONS
    // -------------------------------------------------------------------------

    private function toolGetGeneralStats()
    {
        $sModel = new StudentModel();
        $uModel = new UserProfileModel();
        
        $studentCount = $sModel->where('deleted_at', null)->countAllResults();
        $teacherCount = count($uModel->getTeachers());
        
        return [
            'total_students' => $studentCount,
            'total_teachers' => $teacherCount,
            'message' => "Kurumda toplam $studentCount öğrenci ve $teacherCount öğretmen bulunmaktadır."
        ];
    }

    private function toolSearchStudent($query)
    {
        $query = trim($query);
        $model = new StudentModel();
        
        // Gelişmiş arama: Ad, Soyad veya "Ad Soyad" kombinasyonu
        $students = $model->groupStart()
                ->like('adi', $query)
                ->orLike('soyadi', $query)
                ->orLike("CONCAT(adi, ' ', soyadi)", $query)
            ->groupEnd()
            ->where('deleted_at', null)
            ->findAll(5);
        
        if (empty($students)) {
            $parts = explode(' ', $query);
            if (count($parts) > 1) {
                $students = $model->groupStart()
                    ->groupStart()
                        ->like('adi', $parts[0])
                        ->like('soyadi', end($parts))
                    ->groupEnd()
                    ->orGroupStart()
                        ->like('adi', end($parts))
                        ->like('soyadi', $parts[0])
                    ->groupEnd()
                ->groupEnd()
                ->where('deleted_at', null)
                ->findAll(5);
            }
        }
        
        if (empty($students)) return "Aradığınız kriterlere uygun öğrenci bulunamadı.";
        
        $result = [];
        foreach ($students as $s) {
            $age = 'Bilinmiyor';
            if (!empty($s['dogum_tarihi'])) {
                $age = date_diff(date_create($s['dogum_tarihi']), date_create('today'))->y;
            }

            // RAM Raporu bir dosya yoluysa (pdf, jpg vs) kullanıcıya gösterme
            $diagnosis = $s['ram_raporu'] ?? '';
            if (preg_match('/\.(pdf|jpg|jpeg|png|doc|docx)$/i', $diagnosis)) {
                $diagnosis = "RAM Raporu Dosyası Mevcut";
            }
            if (empty($diagnosis) && !empty($s['egitim_programi'])) {
                $diagnosis = "Program: " . $s['egitim_programi'];
            }

            $result[] = [
                // ID'yi kaldırdık ki "Öğrenci No" sanmasın
                'name' => $s['adi'] . ' ' . $s['soyadi'],
                'age' => $age,
                'info' => $diagnosis ?: 'Tanı bilgisi girilmemiş',
                'parent_phone' => $s['iletisim'] ?? 'Telefon yok'
            ];
        }
        return $result;
    }

    private function toolGetStudentDetails($studentId)
    {
        $sModel = new StudentModel();
        $eModel = new StudentEvaluationModel();
        $rModel = new RamReportAnalysisModel();

        $student = $sModel->find($studentId);
        if (!$student) return "Öğrenci bulunamadı.";

        // Gelişim günlükleri
        $evaluations = $eModel->getEvaluationsForStudent($studentId);
        $logs = [];
        foreach (array_slice($evaluations, 0, 3) as $eval) {
            $logs[] = [
                'date' => $eval['created_at'],
                'teacher' => $eval['teacher_snapshot_name'],
                'note' => $eval['evaluation']
            ];
        }

        // RAM Analizi
        $ramAnalysis = $rModel->where('student_id', $studentId)->first();
        
        $age = 'Bilinmiyor';
        if (!empty($student['dogum_tarihi'])) {
            $age = date_diff(date_create($student['dogum_tarihi']), date_create('today'))->y;
        }

        // RAM Raporu dosya kontrolü
        $diagnosis = $student['ram_raporu'] ?? '';
        if (preg_match('/\.(pdf|jpg|jpeg|png|doc|docx)$/i', $diagnosis)) {
            $diagnosis = "RAM Raporu Dosyası Mevcut";
        }
        if (empty($diagnosis) && !empty($student['egitim_programi'])) {
            $diagnosis = "Program: " . $student['egitim_programi'];
        }

        return [
            'profile' => [
                'name' => $student['adi'] . ' ' . $student['soyadi'],
                'age' => $age,
                'diagnosis' => $diagnosis,
                'education_program' => $student['egitim_programi']
            ],
            'latest_logs' => $logs,
            'ram_analysis' => $ramAnalysis['ram_text_content'] ?? 'RAM analizi bulunamadı.'
        ];
    }

    private function toolGetTeacherReport($name)
    {
        $uModel = new UserProfileModel();
        $teachers = $uModel->like('first_name', $name)->orLike('last_name', $name)->findAll();
        
        if (empty($teachers)) return "Öğretmen bulunamadı.";

        $report = [];
        foreach ($teachers as $t) {
            // Object veya array kontrolü
            $firstName = is_object($t) ? $t->first_name : $t['first_name'];
            $lastName = is_object($t) ? $t->last_name : $t['last_name'];
            $branch = is_object($t) ? $t->branch : ($t['branch'] ?? '');
            $phone = is_object($t) ? $t->phone_number : ($t['phone_number'] ?? '');

            $report[] = [
                'name' => $firstName . ' ' . $lastName,
                'branch' => $branch,
                'phone' => $phone
            ];
        }
        return $report;
    }

    private function toolGetEmptyLessons($date)
    {
        $lModel = new LessonModel();
        $uModel = new UserProfileModel();
        
        $teachers = $uModel->getTeachers();
        $allSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        
        $emptySlots = [];

        foreach ($teachers as $teacher) {
            // getTeachers array döndürüyor
            $teacherId = $teacher['user_id'];
            $lessons = $lModel->getLessonsForTeacherByDate($teacherId, $date);
            $filledTimes = array_column($lessons, 'start_time');
            
            // Saat formatını eşle (09:00:00 -> 09:00)
            $filledTimes = array_map(function($t) { return substr($t, 0, 5); }, $filledTimes);
            
            $teacherEmpty = array_diff($allSlots, $filledTimes);
            
            if (!empty($teacherEmpty)) {
                $emptySlots[] = [
                    'teacher' => $teacher['first_name'] . ' ' . $teacher['last_name'],
                    'branch' => $teacher['branch'],
                    'available_times' => array_values($teacherEmpty)
                ];
            }
        }
        
        return $emptySlots;
    }

    private function toolFindStudentsByCriteria($diagnosis, $minAge, $maxAge)
    {
        $sModel = new StudentModel();
        
        // Basit LIKE araması ve yaş filtresi
        $students = $sModel->like('ram_raporu', $diagnosis)->where('deleted_at', null)->findAll();
        
        $matches = [];
        foreach ($students as $s) {
            if (empty($s['dogum_tarihi'])) continue;
            
            $age = date_diff(date_create($s['dogum_tarihi']), date_create('today'))->y;
            if ($age >= $minAge && $age <= $maxAge) {
                $matches[] = [
                    'id' => $s['id'],
                    'name' => $s['adi'] . ' ' . $s['soyadi'],
                    'age' => $age,
                    'diagnosis' => $s['ram_raporu']
                ];
            }
        }
        
        return empty($matches) ? "Kriterlere uygun öğrenci bulunamadı." : $matches;
    }

    private function toolGetStudentAbsences($studentId)
    {
        $db = db_connect();
        
        // Öğrenci adını al
        $student = (new StudentModel())->find($studentId);
        if (!$student) return "Öğrenci bulunamadı.";
        
        // Devamsızlıkları çek
        $absences = $db->table('student_absences')
            ->where('student_id', $studentId)
            ->orderBy('lesson_date', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
            
        if (empty($absences)) {
            return [
                'student' => $student['adi'] . ' ' . $student['soyadi'],
                'message' => "Bu öğrenciye ait kayıtlı bir devamsızlık bulunmamaktadır."
            ];
        }
        
        $result = [];
        foreach ($absences as $abs) {
            $result[] = [
                'date' => $abs['lesson_date'],
                'reason' => $abs['reason'] ?? 'Belirtilmemiş'
            ];
        }
        
        return [
            'student' => $student['adi'] . ' ' . $student['soyadi'],
            'total_absences' => count($absences),
            'details' => $result
        ];
    }
}