<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

class DatabaseInspector
{
    private BaseConnection $db;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Tüm veritabanı şemasını AI için okunabilir formatta döndürür
     */
    public function getDatabaseSchema(): string
    {
        $schema = "=== VERİTABANI ŞEMASI ===\n\n";
        $tables = $this->db->listTables();
        
        foreach ($tables as $table) {
            $schema .= $this->getTableSchema($table);
        }
        
        return $schema;
    }

    /**
     * Belirli bir tablonun şemasını detaylı şekilde döndürür
     */
    private function getTableSchema(string $tableName): string
    {
        $schema = "📋 TABLO: {$tableName}\n";
        $schema .= str_repeat("-", 60) . "\n";
        
        // Sütun bilgileri
        $fields = $this->db->getFieldData($tableName);
        $schema .= "Sütunlar:\n";
        
        foreach ($fields as $field) {
            $schema .= "  • {$field->name} ({$field->type}";
            if ($field->max_length) {
                $schema .= "({$field->max_length})";
            }
            if ($field->primary_key) {
                $schema .= ", PRIMARY KEY";
            }
            if (!$field->nullable) {
                $schema .= ", NOT NULL";
            }
            $schema .= ")\n";
        }
        
        // Kayıt sayısı
        $count = $this->db->table($tableName)->countAll();
        $schema .= "\nToplam Kayıt: {$count}\n";
        
        // Örnek kayıt (ilk 1)
        if ($count > 0) {
            $sample = $this->db->table($tableName)->limit(1)->get()->getRowArray();
            $schema .= "\nÖrnek Kayıt:\n";
            foreach ($sample as $key => $value) {
                $displayValue = is_string($value) ? mb_substr($value, 0, 50) : $value;
                $schema .= "  {$key}: {$displayValue}\n";
            }
        }
        
        $schema .= "\n\n";
        return $schema;
    }

    /**
     * AI'ın sorguya göre hangi tabloları kullanması gerektiğini belirler
     */
    public function suggestTablesForQuery(string $userQuery): array
    {
        $queryLower = mb_strtolower($userQuery, 'UTF-8');
        $relevantTables = [];
        
        $tableKeywords = [
            'students' => ['öğrenci', 'student', 'ram', 'veli', 'anne', 'baba'],
            'users' => ['kullanıcı', 'user', 'öğretmen', 'admin', 'teacher', 'sekreter'],
            'user_profiles' => ['profil', 'profile', 'telefon', 'adres'],
            'lessons' => ['ders', 'lesson', 'takvim', 'program', 'saat'],
            'fixed_lessons' => ['sabit', 'fixed', 'haftalık'],
            'reports' => ['rapor', 'report', 'istatistik', 'aylık'],
            'logs' => ['log', 'kayıt', 'işlem', 'aktivite'],
            'announcements' => ['duyuru', 'announcement'],
            'lesson_history' => ['geçmiş', 'history', 'eski'],
            'institution' => ['kurum', 'institution'],
            'auth_groups_users' => ['rol', 'group', 'yetki'],
        ];
        
        foreach ($tableKeywords as $table => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($queryLower, $keyword)) {
                    $relevantTables[] = $table;
                    break;
                }
            }
        }
        
        return array_unique($relevantTables);
    }

    /**
     * Dinamik SQL sorgusu çalıştırır (SADECE SELECT)
     */
    public function executeQuery(string $sql): array
    {
        // Güvenlik: Sadece SELECT sorgularına izin ver
        $sqlLower = mb_strtolower(trim($sql), 'UTF-8');
        
        if (!str_starts_with($sqlLower, 'select')) {
            return [
                'error' => true,
                'message' => 'Güvenlik nedeniyle sadece SELECT sorguları çalıştırılabilir.'
            ];
        }
        
        // Tehlikeli anahtar kelimeleri engelle
        $dangerousKeywords = ['drop', 'delete', 'update', 'insert', 'alter', 'create', 'truncate', 'exec', 'execute'];
        foreach ($dangerousKeywords as $keyword) {
            if (str_contains($sqlLower, $keyword)) {
                return [
                    'error' => true,
                    'message' => "Güvenlik nedeniyle '{$keyword}' ifadesi kullanılamaz."
                ];
            }
        }
        
        try {
            $query = $this->db->query($sql);
            $results = $query->getResultArray();
            
            return [
                'error' => false,
                'data' => $results,
                'count' => count($results)
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Sorgu hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Veritabanı ilişkilerini açıklar
     */
    public function getDatabaseRelationships(): string
    {
        $relations = "=== VERİTABANI İLİŞKİLERİ ===\n\n";

        $relations .= "📌 users (Kullanıcılar)\n";
        $relations .= "  └─> user_profiles (1:1) - Profil bilgileri\n";
        $relations .= "  └─> auth_groups_users (1:N) - Rol atamaları\n";
        $relations .= "  └─> lessons (1:N, teacher_id) - Verdiği dersler\n";
        $relations .= "  └─> fixed_schedules (1:N, ogretmen_id) - Sabit ders programı\n";
        $relations .= "  └─> logs (1:N) - Yaptığı işlemler\n\n";

        $relations .= "📌 students (Öğrenciler)\n";
        $relations .= "  └─> lessons (1:N) - Aldığı dersler\n";
        $relations .= "  └─> fixed_schedules (1:N, ogrenci_id) - Sabit ders programı\n";
        $relations .= "  └─> entitlements (1:N) - Ders hakları\n";
        $relations .= "  └─> users (N:1, parent_id) - Bağlı veli hesabı\n\n";

        $relations .= "📌 lessons (Dersler)\n";
        $relations .= "  └─> users (N:1, teacher_id) - Dersi veren öğretmen\n";
        $relations .= "  └─> students (N:1, student_id) - Dersi alan öğrenci\n\n";

        $relations .= "📌 fixed_schedules (Sabit Program)\n";
        $relations .= "  └─> users (N:1, ogretmen_id) - Öğretmen\n";
        $relations .= "  └─> students (N:1, ogrenci_id) - Öğrenci\n\n";

        $relations .= "📌 announcements (Duyurular)\n";
        $relations .= "  └─> users (N:1, created_by) - Oluşturan kullanıcı\n\n";
        return $relations;
    }

    /**
     * Tablo hakkında özet istatistikler
     */
    public function getTableStats(string $tableName): array
    {
        if (!$this->db->tableExists($tableName)) {
            return ['error' => 'Tablo bulunamadı'];
        }
        
        $stats = [
            'table_name' => $tableName,
            'total_records' => $this->db->table($tableName)->countAll(),
            'columns' => []
        ];
        
        $fields = $this->db->getFieldData($tableName);
        foreach ($fields as $field) {
            $stats['columns'][] = [
                'name' => $field->name,
                'type' => $field->type,
                'nullable' => $field->nullable
            ];
        }
        
        return $stats;
    }
}