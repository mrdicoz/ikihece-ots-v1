<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\LessonHistoryModel;


class LessonHistoryManagerCommand extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'lesson:manage-history'; // Komut adını koruyoruz
    protected $description = 'Günlük dersleri, öneri sistemi için ders geçmişi tablosuna aktarır ve eski verileri temizler.';
    
    public function run(array $params)
    {
        CLI::write('Ders geçmişi senkronizasyonu başlatıldı...', 'green');

        $lessonHistoryModel = new LessonHistoryModel();
        $db = \Config\Database::connect();

        // Dünün tarihini alıyoruz, çünkü gün bitmeden dersleri aktarmak yanlış olabilir.
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        CLI::write("İşlenen Tarih: {$yesterday}", 'yellow');

        // Dünün derslerini, ilişkili öğrenci ve öğretmen bilgileriyle birlikte çek.
        $lessonsToSync = $db->table('lessons as l')
            ->select('
                l.lesson_date, 
                l.start_time,
                s.adi as student_adi, 
                s.soyadi as student_soyadi,
                s.egitim_programi as student_program,
                up.first_name as teacher_first_name,
                up.last_name as teacher_last_name,
                up.branch as teacher_branch
            ')
            ->join('lesson_students as ls', 'ls.lesson_id = l.id')
            ->join('students as s', 's.id = ls.student_id')
            ->join('user_profiles as up', 'up.user_id = l.teacher_id', 'left')
            ->where('l.lesson_date', $yesterday)
            ->get()->getResultArray();

        if (empty($lessonsToSync)) {
            CLI::write('Aktarılacak yeni ders bulunamadı.', 'light_blue');
        } else {
            CLI::write(count($lessonsToSync) . ' adet ders/öğrenci kaydı bulundu. İşleniyor...', 'white');
            
            $historyData = [];
            foreach ($lessonsToSync as $lesson) {
                if (empty($lesson['student_adi']) || empty($lesson['teacher_first_name'])) {
                    continue; // Gerekli ad soyad bilgisi yoksa atla
                }

                $historyData[] = [
                    'lesson_date'     => $lesson['lesson_date'],
                    'start_time'      => $lesson['start_time'],
                    'student_name'    => trim($lesson['student_adi'] . ' ' . $lesson['student_soyadi']),
                    'student_program' => $lesson['student_program'],
                    'teacher_name'    => trim($lesson['teacher_first_name'] . ' ' . $lesson['teacher_last_name']),
                    'teacher_branch'  => $lesson['teacher_branch'],
                ];
            }

            if (!empty($historyData)) {
                $lessonHistoryModel->insertBatch($historyData);
                CLI::write(count($historyData) . ' adet ders başarıyla geçmişe aktarıldı.', 'green');
            }
        }
        
        // 3 aydan eski verileri temizle
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
        CLI::write("3 aydan eski kayıtlar ({$threeMonthsAgo} öncesi) siliniyor...");

        $deletedCount = $lessonHistoryModel->where('lesson_date <', $threeMonthsAgo)->delete();

        if ($deletedCount !== false) {
            CLI::write($deletedCount . ' adet eski ders kaydı başarıyla silindi.', 'green');
        } else {
            CLI::error('Eski kayıtlar silinirken bir hata oluştu.');
        }

        CLI::write('Ders geçmişi yönetimi başarıyla tamamlandı!', 'cyan');
    }
}