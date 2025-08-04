<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends BaseController
{
    /**
     * Excel/CSV dosyasından toplu öğrenci aktarma formunu gösterir.
     */
    public function importView()
    {
        $data['title'] = 'Öğrenci Verilerini İçeri Aktar';
        // View dosyasının yolu aynı kalabilir veya admin altına taşınabilir.
        // Mevcut yapıyı korumak adına aynı bırakıyorum.
        return view('admin/students/import', array_merge($this->data, $data));

    }

    /**
     * Yüklenen Excel/CSV dosyasını işleyerek öğrencileri veritabanına aktarır.
     */
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
            for ($i = 4; $i <= count($sheetData); $i++) {
                $row = $sheetData[$i] ?? null;
                if ($row === null || (empty(trim($row['D'])) || empty(trim($row['E'])))) {
                    continue;
                }

                $ogrenci_tc = trim($row['F']);
                $anne_tc = trim($row['AN']);
                $baba_tc = trim($row['AU']);

                $dataToUpsert[] = [
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
                    'veli_baba_tc'              => (is_numeric($anne_tc) && strlen($anne_tc) === 11) ? $anne_tc : null,
                    'veli_baba_adi_soyadi'      => $row['AO'] ?? null,
                    'veli_baba_telefon'         => $row['AP'] ?? null,
                    'veli_baba_is_adresi'       => $row['AT'] ?? null,
                    'veli_anne_tc'              => (is_numeric($baba_tc) && strlen($baba_tc) === 11) ? $baba_tc : null,
                    'veli_anne_adi_soyadi'      => $row['AV'] ?? null,
                    'veli_anne_telefon'         => $row['AW'] ?? null,
                    'veli_anne_is_adresi'       => $row['BA'] ?? null,
                ];
            }

            if (empty($dataToUpsert)) {
                return redirect()->back()->with('error', 'Dosyada işlenecek geçerli veri bulunamadı.');
            }

            $studentModel = new StudentModel();
            $studentModel->upsertBatch($dataToUpsert);
            // Başarılı aktarma sonrası ana öğrenci listesine yönlendiriyoruz.
            return redirect()->to('/students')->with('success', count($dataToUpsert) . ' öğrenci kaydı başarıyla işlendi.');
            
        } catch (\Exception $e) {
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