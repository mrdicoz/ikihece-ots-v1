<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends BaseController
{
    public function importView()
    {
        return view('admin/students/import');
    }

    public function import()
    {
        $file = $this->request->getFile('file');
        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'Dosya yüklenirken bir hata oluştu: ' . $file->getErrorString());
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $dataToUpsert = [];
            // Veri 4. satırdan başlıyor
            for ($i = 4; $i <= count($sheetData); $i++) {
                $row = $sheetData[$i] ?? null;
                if ($row === null) continue;

                // Adı (D sütunu) veya Soyadı (E sütunu) boşsa o satırı atla
                if (empty(trim($row['D'])) || empty(trim($row['E']))) {
                    continue;
                }

                // --- KESİN EŞLEŞTİRME (VERİLEN ÇIKTIYA GÖRE) ---
                $ogrenci_tc = trim($row['F']);
                $anne_tc = trim($row['AN']);
                $baba_tc = trim($row['AU']);

                $dataToUpsert[] = [
                    // Öğrenci Bilgileri
                    'okul_no'                   => $row['C'] ?? null,
                    'adi'                       => $row['D'] ?? null,
                    'soyadi'                    => $row['E'] ?? null,
                    'tc_kimlik_no'              => (is_numeric($ogrenci_tc) && strlen($ogrenci_tc) === 11) ? $ogrenci_tc : null,
                    'cinsiyet'                  => $row['G'] ?? null,
                    'dogum_tarihi'              => $this->formatDate($row['J'] ?? null),
                    'kayit_tarihi'              => $this->formatDate($row['P'] ?? null),
                    'ayrilis_tarihi'            => $this->formatDate($row['Q'] ?? null),
                    'sinifi'                    => $row['AK'] ?? null,
                    'kan_grubu'                 => $row['I'] ?? null,
                    'engel_durumu'              => $row['H'] ?? null,
                    'adres_ilce'                => $row['AB'] ?? null,
                    'adres_mahalle'             => $row['AC'] ?? null,
                    'adres_detay'               => $row['O'] ?? null,

                    // Anne (Veli 1) Bilgileri
                    'veli_anne_tc'              => (is_numeric($anne_tc) && strlen($anne_tc) === 11) ? $anne_tc : null,
                    'veli_anne_adi_soyadi'      => $row['AO'] ?? null,
                    'veli_anne_telefon'         => $row['AP'] ?? null,
                    'veli_anne_is_adresi'       => $row['AT'] ?? null,

                    // Baba (Veli 2) Bilgileri
                    'veli_baba_tc'              => (is_numeric($baba_tc) && strlen($baba_tc) === 11) ? $baba_tc : null,
                    'veli_baba_adi_soyadi'      => $row['AV'] ?? null,
                    'veli_baba_telefon'         => $row['AW'] ?? null,
                    'veli_baba_is_adresi'       => $row['BA'] ?? null,
                ];
            }

            if (empty($dataToUpsert)) {
                return redirect()->back()->with('error', 'Dosyada işlenecek geçerli veri bulunamadı.');
            }

            $studentModel = new StudentModel();
            $studentModel->upsertBatch($dataToUpsert);
            return redirect()->to('/panel/students')->with('success', count($dataToUpsert) . ' öğrenci kaydı başarıyla işlendi.');
            
        } catch (\Exception $e) {
            // Hata mesajını daha anlaşılır hale getirelim
            return redirect()->back()->with('error', 'Dosya işlenirken kritik bir hata oluştu: ' . $e->getMessage() . ' (Dosya: ' . $e->getFile() . ' Satır: ' . $e->getLine() . ')');
        }
    }

    
    private function formatDate($dateString): ?string
    {
        if (empty($dateString)) { return null; }
        try {
            if (is_numeric($dateString)) {
                $unix_date = ($dateString - 25569) * 86400;
                return gmdate("Y-m-d", $unix_date);
            }
            $date = new \DateTime($dateString);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}