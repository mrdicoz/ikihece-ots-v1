<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LessonHistoryModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use DateTime;
use Exception;

class DataImportController extends BaseController
{

    public function history()
    {
        // Projenin sunucudaki tam yolunu al (sondaki / işaretini temizle)
        $projectPath = rtrim(ROOTPATH, '/');

        // Cron job komutunu bu yola göre dinamik olarak oluştur
        $cronCommand = "0 0 * * * /usr/bin/php {$projectPath}/spark lesson:manage-history >/dev/null 2>&1";

        $data = [
            'title'       => 'Yapay Zeka Modelini Eğitimi',
            'cronCommand' => $cronCommand, // Oluşturulan komutu view'e gönder
        ];
        
        return view('admin/data_import/ai_trainer', array_merge($this->data, $data));
    }

    /**
     * Yüklenen Excel dosyasını işler ve yeni lesson_history tablosuna kaydeder.
     */
    public function processUpload()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Dosya yüklenirken bir hata oluştu: ' . ($file ? $file->getErrorString() : 'Dosya bulunamadı.'));
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            $historyData = [];
            $skippedRows = [];

            // Başlık satırını atlamak için 2'den başlıyoruz
            for ($row = 2; $row <= $highestRow; $row++) {
                // Sütunları sizin belirttiğiniz sıraya göre okuyoruz
                $dateValue = $sheet->getCell('A' . $row)->getValue();
                $timeValue = $sheet->getCell('B' . $row)->getValue();
                $studentName = $sheet->getCell('C' . $row)->getValue();
                $studentProgram = $sheet->getCell('D' . $row)->getValue();
                $teacherName = $sheet->getCell('E' . $row)->getValue();
                $teacherBranch = $sheet->getCell('F' . $row)->getValue();
                
                // Temel alanlar boşsa satırı atla
                if (empty($dateValue) || empty($timeValue) || empty($studentName) || empty($teacherName)) {
                    $skippedRows[] = ['row' => $row, 'reason' => 'Eksik bilgi (Tarih, Saat, Öğrenci veya Öğretmen)'];
                    continue;
                }

                $processedDate = $this->parseDate($dateValue);
                if ($processedDate === null) {
                    $skippedRows[] = ['row' => $row, 'reason' => "Tanımlanamayan tarih formatı: '{$dateValue}'"];
                    continue;
                }
                
                $processedTime = $this->parseTime($timeValue);

                $historyData[] = [
                    'lesson_date'     => $processedDate,
                    'start_time'      => $processedTime,
                    'student_name'    => trim($studentName),
                    'student_program' => trim($studentProgram),
                    'teacher_name'    => trim($teacherName),
                    'teacher_branch'  => trim($teacherBranch),
                ];
            }

            if (empty($historyData)) {
                return redirect()->back()->with('error', 'Dosyada işlenecek geçerli veri bulunamadı.');
            }

            $model = new LessonHistoryModel();
            $model->insertBatch($historyData);
            
            $successMessage = count($historyData) . ' adet geçmiş ders kaydı başarıyla işlendi ve veri setine eklendi.';
            if (!empty($skippedRows)) {
                $successMessage .= " " . count($skippedRows) . " satır, geçersiz format veya eksik bilgi nedeniyle atlandı.";
                session()->setFlashdata('skipped_rows', $skippedRows);
            }

            return redirect()->to(route_to('admin.ai.trainer'))->with('success', $successMessage);

        } catch (Exception $e) {
            log_message('error', '[DataImport] ' . $e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Dosya işlenirken kritik bir hata oluştu. Lütfen dosyanın bozuk olmadığından ve Excel/CSV formatında olduğundan emin olun.');
        }
    }

    // Bu yardımcı fonksiyonlar aynı kalabilir, formatları doğru işliyorlar
    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) return null;

        if (is_numeric($dateValue)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } catch (Exception $e) { /* Diğer yöntemleri dene */ }
        }
        
        $formatsToTry = ['n/j/Y', 'd.m.Y', 'd-m-Y', 'Y-m-d'];
        foreach ($formatsToTry as $format) {
            $date = DateTime::createFromFormat($format, $dateValue);
            if ($date && $date->format($format) === $dateValue) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }

    private function parseTime($timeValue): string
    {
        $timeValue = (string)$timeValue;
        if (strpos($timeValue, '-') !== false) {
            $timeValue = explode('-', $timeValue)[0];
        }
        return date('H:i:s', strtotime(trim($timeValue)));
    }
}