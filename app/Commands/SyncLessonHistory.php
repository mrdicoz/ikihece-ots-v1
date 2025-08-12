<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\LessonHistoryModel;
use App\Models\LessonModel; // LessonModel'i kullanıyoruz

class SyncLessonHistory extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'lessons:sync-history';
    protected $description = 'Günlük dersleri, öneri sistemi için ders geçmişi tablosuna aktarır.';
    protected $usage = 'lessons:sync-history';
    
    public function run(array $params)
    {
        CLI::write('Ders geçmişi senkronizasyonu başlatıldı...', 'green');

        $lessonModel = new LessonModel();
        $lessonHistoryModel = new LessonHistoryModel();
        $db = \Config\Database::connect();

        // Bugünün tarihini al
        $today = date('Y-m-d');

        // Bugünün derslerini, ilişkili öğrenci ve öğretmen bilgileriyle birlikte çek.
        // Bu sorgu, pivot tablo (lesson_students) üzerinden doğru birleştirme yapar.
        $lessonsToSync = $db->table('lessons as l')
            ->select('
                l.lesson_date, 
                l.start_time,
                s.adi as student_adi, 
                s.soyadi as student_soyadi,
                up.first_name as teacher_first_name,
                up.last_name as teacher_last_name
            ')
            ->join('lesson_students as ls', 'ls.lesson_id = l.id')
            ->join('students as s', 's.id = ls.student_id')
            ->join('user_profiles as up', 'up.user_id = l.teacher_id', 'left')
            ->where('l.lesson_date', $today)
            ->get()->getResultArray();

        if (empty($lessonsToSync)) {
            CLI::write('Bugün için aktarılacak yeni ders bulunamadı.', 'yellow');
            return;
        }

        CLI::write(count($lessonsToSync) . ' adet ders/öğrenci kaydı bulundu. İşleniyor...', 'white');
        
        $historyData = [];

        foreach ($lessonsToSync as $lesson) {
            // Öğretmen veya öğrenci adı boşsa, bu kaydı atla.
            if (empty($lesson['student_adi']) || empty($lesson['teacher_first_name'])) {
                continue;
            }

            $historyData[] = [
                'teacher_name' => trim($lesson['teacher_first_name'] . ' ' . $lesson['teacher_last_name']),
                'student_name' => trim($lesson['student_adi'] . ' ' . $lesson['student_soyadi']),
                'lesson_date'  => $lesson['lesson_date'],
                'start_time'   => $lesson['start_time'],
            ];
        }

        if (!empty($historyData)) {
            $lessonHistoryModel->insertBatch($historyData);
        }

        CLI::write("İşlem tamamlandı. " . count($historyData) . " adet ders kaydı geçmişe başarıyla aktarıldı.", 'green');
    }
}