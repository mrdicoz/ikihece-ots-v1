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
        $data = [
            'title' => 'Yapay Zeka Modelini Eğitimi',
        ];
        return view('admin/data_import/ai_trainer', array_merge($this->data, $data));
        

    }

    public function processUpload()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Dosya yüklenirken bir hata oluştu: ' . ($file ? $file->getErrorString() : 'Dosya bulunamadı.'));
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            // getHighestRow, boş satırları da sayabilir, bu yüzden getHighestDataRow kullanalım
            $highestRow = $sheet->getHighestDataRow();

            $historyData = [];
            $skippedRows = [];

            // Başlık satırını atlamak için 2'den başlıyoruz
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [
                    'date'    => $sheet->getCell('A' . $row)->getValue(),
                    'time'    => $sheet->getCell('B' . $row)->getValue(),
                    'teacher' => $sheet->getCell('C' . $row)->getValue(),
                    'student' => $sheet->getCell('D' . $row)->getValue(),
                ];

                // Satırda temel verilerden en az biri eksikse, o satırı tamamen atla
                if (empty($rowData['teacher']) || empty($rowData['student']) || empty($rowData['date']) || empty($rowData['time'])) {
                    $skippedRows[] = ['row' => $row, 'reason' => 'Eksik bilgi (Öğretmen, Öğrenci, Tarih veya Saat)'];
                    continue;
                }

                // --- Gelişmiş Saat ve Tarih İşleme ---
                $processedTime = $this->parseTime($rowData['time']);
                $processedDate = $this->parseDate($rowData['date']);

                if ($processedDate === null) {
                    $skippedRows[] = ['row' => $row, 'reason' => "Tanımlanamayan tarih formatı: '{$rowData['date']}'"];
                    continue; // Tarih işlenemezse bu satırı atla
                }

                $historyData[] = [
                    'teacher_name' => trim($rowData['teacher']),
                    'student_name' => trim($rowData['student']),
                    'lesson_date'  => $processedDate,
                    'start_time'   => $processedTime,
                ];
            }

            if (empty($historyData)) {
                $errorMessage = 'Dosyada işlenecek geçerli veri bulunamadı.';
                if(!empty($skippedRows)){
                    $errorMessage .= ' Atlanan ilk satır: ' . $skippedRows[0]['row'] . ' - Sebep: ' . $skippedRows[0]['reason'];
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            $model = new LessonHistoryModel();
            $model->insertBatch($historyData);
            
            $successMessage = count($historyData) . ' adet geçmiş ders kaydı başarıyla işlendi ve veri setine eklendi.';
            if (!empty($skippedRows)) {
                $successMessage .= " " . count($skippedRows) . " satır, geçersiz format veya eksik bilgi nedeniyle atlandı.";
                session()->setFlashdata('skipped_rows', $skippedRows); // Detaylı bilgi için
            }

            return redirect()->to(route_to('admin.ai.trainer'))->with('success', $successMessage);

        } catch (Exception $e) {
            log_message('error', '[DataImport] ' . $e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Dosya işlenirken kritik bir hata oluştu. Lütfen dosyanın bozuk olmadığından ve Excel/CSV formatında olduğundan emin olun.');
        }
    }

    /**
     * Gelen tarih verisini akıllıca işler ve Y-m-d formatına çevirir.
     * Hem Excel'in sayısal zaman damgasını hem de yaygın metin formatlarını anlar.
     * @param mixed $dateValue
     * @return string|null
     */
    private function parseDate($dateValue): ?string
    {
        if (empty($dateValue)) return null;

        // 1. Yöntem: Sayısal Excel Tarihi mi?
        if (is_numeric($dateValue)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } catch (Exception $e) {
                // Hata olursa diğer yöntemleri dene
            }
        }

        // 2. Yöntem: Yaygın metin formatlarını dene
        $formatsToTry = [
            'n/j/Y',  // 5/1/2024
            'd.m.Y',  // 05.01.2024
            'd-m-Y',  // 05-01-2024
            'Y-m-d',  // 2024-01-05
        ];

        foreach ($formatsToTry as $format) {
            $date = DateTime::createFromFormat($format, $dateValue);
            if ($date && $date->format($format) === $dateValue) {
                return $date->format('Y-m-d');
            }
        }

        return null; // Hiçbir format uymadı
    }

    /**
     * Gelen saat verisini işler. "10:00-10:50" gibi aralıklardan başlangıç saatini alır.
     * @param mixed $timeValue
     * @return string
     */
    private function parseTime($timeValue): string
    {
        $timeValue = (string)$timeValue;
        // Eğer "-" içeriyorsa, sadece ilk kısmı al
        if (strpos($timeValue, '-') !== false) {
            $timeValue = explode('-', $timeValue)[0];
        }
        return trim($timeValue);
    }
}