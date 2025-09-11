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

        // --- KURAL TANIMLAMALARI BAŞLANGICI (Sizin verdiğiniz listeler) ---

        // Programların tam isimleri ve ID'leri
        $programs = [
            1 => 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
            2 => 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
            3 => 'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı',
            4 => 'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı',
            5 => 'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı',
        ];

        // Programları metin içinde aramak için anahtar kelimeler
        $programKeywords = [
            1 => 'Bedensel',
            2 => 'Dil ve Konuşma',
            3 => 'Zihinsel',
            4 => 'Öğrenme Güçlüğü',
            5 => 'Otizm',
        ];

        // Öğretmen Branşlarının yetkili olduğu program ID'leri
        $branchPermissions = [
            'Fizyoterapist' => [1],
            'Dil ve Konuşma Bozuklukları Uzmanı' => [2],
            'Odyoloji ve Konuşma Bozuklukları Uzmanı' => [2],
            'Özel Eğitim Alanı Öğretmeni' => [2, 3, 4, 5],
            'Uzman Öğretici' => [2, 3, 4, 5],
            'Psikolog & PDR' => [1, 2, 3, 4, 5],
            'Okul Öncesi Öğretmeni' => [2, 3, 4, 5],
            'Çocuk Gelişimi Öğretmeni' => [2, 3, 4, 5],
        ];

        // --- KURAL TANIMLAMALARI SONU ---

        $lessonHistoryModel = new LessonHistoryModel();
        $db = \Config\Database::connect();

        $yesterday = date('Y-m-d', strtotime('yesterday'));
        CLI::write("İşlenen Tarih: {$yesterday}", 'yellow');

        $lessonsToSync = $db->table('lessons as l')
            ->select('
                l.lesson_date, l.start_time,
                s.adi as student_adi, s.soyadi as student_soyadi,
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
                    continue; 
                }

                // --- YENİ AYRIŞTIRMA MANTIĞI ---
                
                $teacherBranch = $lesson['teacher_branch'];
                $studentProgramsStr = $lesson['student_program'];
                $chosenProgram = '';

                // 1. Öğrencinin kayıtlı olduğu program ID'lerini bulalım
                $studentEnrolledIds = [];
                foreach ($programKeywords as $id => $keyword) {
                    if (str_contains($studentProgramsStr, $keyword)) {
                        $studentEnrolledIds[] = $id;
                    }
                }

                // 2. Öğretmenin ders verebildiği program ID'lerini alalım
                $teacherAllowedIds = $branchPermissions[$teacherBranch] ?? [];

                // 3. İki listenin kesişimini (ortak elemanlarını) bulalım
                $possibleProgramIds = array_intersect($studentEnrolledIds, $teacherAllowedIds);

                // 4. Sonuç programı seçelim
                if (!empty($possibleProgramIds)) {
                    // Eğer ortak program varsa, ilkini seç (genelde tek olacaktır)
                    $firstMatchId = array_values($possibleProgramIds)[0];
                    $chosenProgram = $programs[$firstMatchId];
                } else {
                    // Eğer hiçbir kurala uymuyorsa, veritabanından gelen orijinal veriyi kullanalım (güvenli bir varsayım)
                    $programParts = explode(',', $studentProgramsStr);
                    $chosenProgram = trim($programParts[0]);
                }

                $historyData[] = [
                    'lesson_date'     => $lesson['lesson_date'],
                    'start_time'      => $lesson['start_time'],
                    'student_name'    => trim($lesson['student_adi'] . ' ' . $lesson['student_soyadi']),
                    'student_program' => $chosenProgram, // <-- Yeni mantıkla seçilen program
                    'teacher_name'    => trim($lesson['teacher_first_name'] . ' ' . $lesson['teacher_last_name']),
                    'teacher_branch'  => $lesson['teacher_branch'],
                ];
            }

            if (!empty($historyData)) {
                $lessonHistoryModel->insertBatch($historyData);
                CLI::write(count($historyData) . ' adet ders başarıyla geçmişe aktarıldı.', 'green');
            }
        }
        
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