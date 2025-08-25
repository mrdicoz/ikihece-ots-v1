<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CityModel;
use App\Models\DistrictModel;
use App\Models\StudentModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends BaseController
{
    /**
     * Toplu öğrenci aktarma formunu gösterir.
     */
    public function importView()
    {
        return view('admin/students/import');
    }

    /**
     * Yüklenen CSV/Excel dosyasını işleyerek öğrencileri veritabanına aktarır.
     */
    public function import()
    {
        $file = $this->request->getFile('file');

        if (!$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Dosya yüklenirken bir hata oluştu: ' . $file->getErrorString());
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $startRow = 2; // Verilerin başladığı satır (başlık satırını atla)
            $highestRow = $sheet->getHighestRow();

            $studentModel = new StudentModel();
            $cityModel = new CityModel();
            $districtModel = new DistrictModel();

            // Performans için il ve ilçeleri önbelleğe al (Büyük harfe çevirerek)
            $cities = array_change_key_case(array_column($cityModel->findAll(), 'id', 'name'), CASE_UPPER);
            $districts = array_change_key_case(array_column($districtModel->findAll(), 'id', 'name'), CASE_UPPER);

            $processedCount = 0;
            
            for ($rowNum = $startRow; $rowNum <= $highestRow; $rowNum++) {
                $tckn = trim($sheet->getCell('D' . $rowNum)->getValue());
                
                // TCKN boş ise veya geçerli değilse bu satırı atla
                if (empty($tckn) || strlen($tckn) !== 11) {
                    continue;
                }

                $ilAdi = strtoupper(trim($sheet->getCell('J' . $rowNum)->getValue()));
                $ilceAdi = strtoupper(trim($sheet->getCell('I' . $rowNum)->getValue()));

                // CSV dosyasındaki sütunlarla veritabanı alanlarını eşleştir
                $data = [
                    'adi'               => trim($sheet->getCell('B' . $rowNum)->getValue()),
                    'soyadi'            => trim($sheet->getCell('C' . $rowNum)->getValue()),
                    'tckn'              => $tckn,
                    'cinsiyet'          => $this->mapCinsiyet(trim($sheet->getCell('E' . $rowNum)->getValue())),
                    'dogum_tarihi'      => $this->formatDate($sheet->getCell('F' . $rowNum)->getValue()),
                    'iletisim'          => trim($sheet->getCell('G' . $rowNum)->getValue()),
                    'adres_detayi'      => trim($sheet->getCell('H' . $rowNum)->getValue()),
                    'city_id'           => $cities[$ilAdi] ?? null,
                    'district_id'       => $districts[$ilceAdi] ?? null,
                    'servis'            => $this->mapServisDurumu(trim($sheet->getCell('K' . $rowNum)->getValue())),
                    'mesafe'            => $this->mapMesafe(trim($sheet->getCell('L' . $rowNum)->getValue())),
                    'orgun_egitim'      => strtolower(trim($sheet->getCell('M' . $rowNum)->getValue())) === 'evet' ? 'evet' : 'hayir',
                    'egitim_sekli'      => $this->mapEgitimSekli(trim($sheet->getCell('N' . $rowNum)->getValue())),
                    'veli_baba'         => trim($sheet->getCell('O' . $rowNum)->getValue()),
                    'veli_baba_telefon' => trim($sheet->getCell('P' . $rowNum)->getValue()),
                    'veli_baba_tc'      => trim($sheet->getCell('Q' . $rowNum)->getValue()),
                    'veli_anne'         => trim($sheet->getCell('R' . $rowNum)->getValue()),
                    'veli_anne_telefon' => trim($sheet->getCell('S' . $rowNum)->getValue()),
                    'veli_anne_tc'      => trim($sheet->getCell('T' . $rowNum)->getValue()),
                    'ram'               => trim($sheet->getCell('U' . $rowNum)->getValue()),
                    'ram_baslagic'      => $this->formatDate($sheet->getCell('V' . $rowNum)->getValue()),
                    'ram_bitis'         => $this->formatDate($sheet->getCell('W' . $rowNum)->getValue()),
                    'ram_raporu'        => trim($sheet->getCell('X' . $rowNum)->getValue()), // DÜZELTME BURADA
                    'egitim_programi'   => $this->mapEgitimProgrami(trim($sheet->getCell('Z' . $rowNum)->getValue())),
                    'hastane_adi'       => trim($sheet->getCell('AA' . $rowNum)->getValue()),
                    'hastane_raporu_baslama_tarihi' => $this->formatDate($sheet->getCell('AB' . $rowNum)->getValue()),
                    'hastane_raporu_bitis_tarihi' => $this->formatDate($sheet->getCell('AC' . $rowNum)->getValue()),
                    'google_konum'      => trim($sheet->getCell('AF' . $rowNum)->getValue()), // **DÜZELTME BURADA**

                ];

                // Upsert logic: Kayıt varsa güncelle, yoksa ekle
                $existing = $studentModel->where('tckn', $data['tckn'])->first();
                if ($existing) {
                    $studentModel->update($existing['id'], $data);
                } else {
                    $studentModel->insert($data);
                }
                $processedCount++;
            }

            return redirect()->to(route_to('admin.students.importView'))->with('success', $processedCount . ' öğrenci kaydı başarıyla işlendi (eklendi/güncellendi).');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Dosya işlenirken kritik bir hata oluştu: ' . $e->getMessage() . ' (Satır: ' . $e->getLine() . ')');
        }
    }

    private function formatDate($dateValue): ?string { if (empty($dateValue)) return null; try { if (is_numeric($dateValue)) { $unixDate = ($dateValue - 25569) * 86400; return gmdate("Y-m-d", $unixDate); } return (new \DateTime($dateValue))->format('Y-m-d'); } catch (\Exception $e) { return null; } }
    private function mapCinsiyet($value) { return strtolower(trim($value)) === 'erkek' ? 'erkek' : 'kadin'; }
    private function mapServisDurumu($value) { $v = strtolower(trim($value)); if(in_array($v, ['var','yok','arasira'])) return $v; return null; }
    private function mapMesafe($value) { $v = strtolower(trim($value)); if(in_array($v, ['civar','yakın','uzak'])) return $v; return null; }
    private function mapEgitimSekli($value) { $v = strtolower(trim($value)); if(in_array($v, ['tam gün','öğlenci','sabahcı'])) return $v; return null; }
    private function mapEgitimProgrami($value) { $programs = array_map('trim', explode(',', $value)); return implode(',', $programs); }
}