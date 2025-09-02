<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class EntitlementController extends BaseController
{
    public function importView()
    {

        $data = [
           'title'       => 'Öğrenci Ders Haklarını Yükle',
        ];

        return view('admin/entitlements/import', array_merge($this->data, $data));
    }

    public function processImport()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Dosya yüklenirken bir hata oluştu.');
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            $studentModel = new StudentModel();
            $updatedCount = 0;
            $notFound = [];

            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $studentFullNameRaw = $sheet->getCell('A' . $row)->getValue();
                    if (empty($studentFullNameRaw)) continue;
                    
                    $studentFullName = preg_replace('/\s+/', ' ', trim($studentFullNameRaw));

                    // --- ANA DÜZELTME: BÜYÜK-KÜÇÜK HARF DUYARSIZ SORGULAMA ---
                    // Hem veritabanındaki birleştirilmiş ismi hem de Excel'den gelen ismi küçük harfe çevirerek karşılaştırıyoruz.
                    $student = $studentModel
                        ->where('LOWER(REPLACE(CONCAT(adi, " ", soyadi), "  ", " "))', strtolower($studentFullName))
                        ->first();
                    
                    if ($student) {
                        $data = [
                            'normal_bireysel_hak' => (int)$sheet->getCell('B' . $row)->getValue() ?: 0,
                            'normal_grup_hak'     => (int)$sheet->getCell('C' . $row)->getValue() ?: 0,
                            'telafi_bireysel_hak' => (int)$sheet->getCell('D' . $row)->getValue() ?: 0,
                            'telafi_grup_hak'     => (int)$sheet->getCell('E' . $row)->getValue() ?: 0,
                        ];
                        $studentModel->update($student['id'], $data);
                        $updatedCount++;
                    } else {
                        $notFound[] = $studentFullName;
                    }

                } catch (Throwable $e) {
                    log_message('error', "[EntitlementImport] Satır {$row} işlenemedi: " . $e->getMessage());
                    $notFound[] = "Satır {$row} (Hatalı Veri)";
                    continue;
                }
            }

            $message = "{$updatedCount} öğrencinin ders hakları başarıyla güncellendi.";
            if (!empty($notFound)) {
                session()->setFlashdata('error', "Şu öğrenciler bulunamadı veya satırları işlenemedi: " . implode(', ', $notFound));
            }
            
            return redirect()->to(route_to('admin.entitlements.import'))->with('success', $message);

        } catch (Throwable $e) {
            log_message('error', '[EntitlementImport] Dosya okuma hatası: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Excel dosyası okunurken genel bir hata oluştu. Dosya formatını kontrol edin.');
        }
    }
}