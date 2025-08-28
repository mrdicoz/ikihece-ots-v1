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
     * Toplu öğrenci aktarma formunu gösterir (Adım 0).
     */
    public function importView()
    {
        return view('admin/students/import');
    }

    /**
     * Yüklenen dosyayı okur, başlıkları alır ve eşleştirme görünümüne yönlendirir (Adım 1).
     */
       public function importMapping()
    {
        $file = $this->request->getFile('file');

        if (!$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Dosya yüklenirken bir hata oluştu: ' . $file->getErrorString());
        }

        $newName = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads', $newName);
        $filePath = WRITEPATH . 'uploads/' . $newName;

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            $headerRow = $sheet->getRowIterator(1, 1)->current();

            $fileColumns = [];
            $cellIterator = $headerRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            foreach ($cellIterator as $cell) {
                $fileColumns[] = $cell->getValue();
            }

            $studentModel = new StudentModel();
            $dbColumns = $studentModel->allowedFields;
            
            // DÜZELTME: Kafa karışıklığı yaratan 'city_id' ve 'district_id' kaldırıldı.
            $dbColumns = array_filter($dbColumns, function($field) {
                return !in_array($field, ['city_id', 'district_id']);
            });

            // Gerekli 'il' ve 'ilce' seçenekleri ekleniyor.
            $dbColumns[] = 'il';
            $dbColumns[] = 'ilce';
            sort($dbColumns);


            $data = [
                'fileName'    => $newName,
                'fileColumns' => $fileColumns,
                'dbColumns'   => $dbColumns,
            ];
            
            return view('admin/students/import_mapping', $data);

        } catch (\Exception $e) {
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return redirect()->back()->with('error', 'Dosya okunurken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Kullanıcının eşleştirdiği verileri veritabanına aktarır (Adım 2).
     */
  public function importProcess()
    {
        $fileName = $this->request->getPost('file_name');
        $mappings = $this->request->getPost('mappings');
        $filePath = WRITEPATH . 'uploads/' . $fileName;

        if (empty($fileName) || !file_exists($filePath)) {
            return redirect()->to(route_to('admin.students.importView'))->with('error', 'İşlenecek dosya bulunamadı. Lütfen dosyayı tekrar yükleyin.');
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $startRow = 2;
            $highestRow = $sheet->getHighestRow();

            $studentModel = new StudentModel();
            $cityModel = new CityModel();
            $districtModel = new DistrictModel();

            $cities = array_change_key_case(array_column($cityModel->findAll(), 'id', 'name'), CASE_UPPER);
            
            $districtsData = $districtModel->select('districts.id, districts.name, cities.name as city_name')
                                           ->join('cities', 'cities.id = districts.city_id')
                                           ->asArray()
                                           ->findAll();
            
            $districts = [];
            foreach ($districtsData as $d) {
                $cityKey = strtoupper(str_replace(['İ', 'I'], ['i', 'ı'], $d['city_name']));
                $districtKey = strtoupper(str_replace(['İ', 'I'], ['i', 'ı'], $d['name']));
                $districts[$cityKey . '_' . $districtKey] = $d['id'];
            }

            $processedCount = 0;
            $dbFields = $studentModel->allowedFields;
            
            // DÜZELTME: Hata ayıklama için TCKN, il ve ilçe eşleştirmelerini başta bulduk.
            $tcknDbKey = array_search('tckn', $mappings);
            $cityKeyIndex = array_search('il', $mappings);
            $districtKeyIndex = array_search('ilce', $mappings);

            for ($rowNum = $startRow; $rowNum <= $highestRow; $rowNum++) {
                $rowData = $sheet->rangeToArray('A' . $rowNum . ':' . $sheet->getHighestColumn($rowNum) . $rowNum, null, true, false)[0];
                
                if ($tcknDbKey === false || !isset($rowData[$tcknDbKey]) || empty(trim($rowData[$tcknDbKey])) || strlen(trim($rowData[$tcknDbKey])) !== 11) {
                    continue;
                }
                $tckn = trim($rowData[$tcknDbKey]);

                $data = [];
                $cityNameForDistrictLookup = null;
                
                // DÜZELTME: İl ve ilçe verileri, sütun sıralamasından bağımsız olarak burada işleniyor.
                // 1. İl bilgisini işle
                if ($cityKeyIndex !== false && isset($rowData[$cityKeyIndex])) {
                    $cityName = trim($rowData[$cityKeyIndex]);
                    $cityNameForDistrictLookup = strtoupper(str_replace(['İ', 'I'], ['i', 'ı'], $cityName));
                    if (isset($cities[$cityNameForDistrictLookup])) {
                        $data['city_id'] = $cities[$cityNameForDistrictLookup];
                    }
                }

                // 2. İlçe bilgisini işle
                if ($districtKeyIndex !== false && isset($rowData[$districtKeyIndex])) {
                    $districtName = trim($rowData[$districtKeyIndex]);
                    if ($cityNameForDistrictLookup) { // İl bilgisi varsa devam et
                        $upperDistrictName = strtoupper(str_replace(['İ', 'I'], ['i', 'ı'], $districtName));
                        $key = $cityNameForDistrictLookup . '_' . $upperDistrictName;
                        if (isset($districts[$key])) {
                            $data['district_id'] = $districts[$key];
                        }
                    }
                }

                // Kalan diğer alanları işle
                foreach ($mappings as $colIndex => $dbField) {
                    if (empty($dbField) || !isset($rowData[$colIndex])) {
                        continue;
                    }
                    
                    $cellValue = trim($rowData[$colIndex]);

                    if (!in_array($dbField, $dbFields)) {
                        continue;
                    }
                    
                    // İl ve ilçe alanlarını zaten yukarıda işlediğimiz için burada atla
                    if ($dbField === 'il' || $dbField === 'ilce') {
                        continue;
                    }

                    switch ($dbField) {
                        case 'dogum_tarihi':
                        case 'ram_baslagic':
                        case 'ram_bitis':
                        case 'hastane_raporu_baslama_tarihi':
                        case 'hastane_raporu_bitis_tarihi':
                        case 'hastane_randevu_tarihi':
                            $data[$dbField] = $this->formatDate($cellValue);
                            break;

                        case 'egitim_programi':
                            if (!empty($cellValue)) {
                                // 1. CSV'den gelen veriyi virgülle diziye ayır
                                $programs = explode(',', $cellValue);

                                // 2. Her programın başındaki/sonundaki boşlukları temizle ve boş olanları kaldır
                                $cleanedPrograms = array_filter(array_map('trim', $programs));
                                
                                // 3. DOĞRU KOD: Temizlenmiş diziyi SADECE virgül ile birleştir
                                $data[$dbField] = !empty($cleanedPrograms) ? implode(',', $cleanedPrograms) : null;
                            } else {
                                $data[$dbField] = null;
                            }
                            break;
                            
                        case 'cinsiyet':      $data[$dbField] = $this->mapCinsiyet($cellValue); break;
                        case 'servis':        $data[$dbField] = $this->mapServisDurumu($cellValue); break;
                        case 'mesafe':        $data[$dbField] = $this->mapMesafe($cellValue); break;
                        case 'orgun_egitim':  $data[$dbField] = in_array(strtolower($cellValue), ['evet', '1', 'true']) ? 1 : 0; break;
                        case 'egitim_sekli':  $data[$dbField] = $this->mapEgitimSekli($cellValue); break;
                        default:
                            if (in_array($dbField, $dbFields)) {
                                $data[$dbField] = !empty($cellValue) ? $cellValue : null;
                            }
                            break;
                    }
                }

                if (empty($data)) continue;

                $existingStudent = $studentModel->where('tckn', $tckn)->first();
                if ($existingStudent) {
                    $studentModel->update($existingStudent['id'], $data);
                } else {
                    $studentModel->insert($data);
                }
                $processedCount++;
            }

            unlink($filePath);

            return redirect()->to(route_to('admin.students.importView'))->with('success', $processedCount . ' öğrenci kaydı başarıyla işlendi.');

        } catch (\Exception $e) {
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return redirect()->to(route_to('admin.students.importView'))->with('error', 'Veri işlenirken kritik bir hata oluştu: ' . $e->getMessage() . ' (Satır: ' . $e->getLine() . ')');
        }
    }
    // Helper Functions
    private function formatDate($dateValue): ?string { if (empty($dateValue)) return null; try { if (is_numeric($dateValue)) { $unixDate = ($dateValue - 25569) * 86400; return gmdate("Y-m-d", $unixDate); } return (new \DateTime($dateValue))->format('Y-m-d'); } catch (\Exception $e) { return null; } }
    private function mapCinsiyet($value) { return strtolower(trim($value)) === 'erkek' ? 'erkek' : 'kadin'; }
    private function mapServisDurumu($value) { $v = strtolower(trim($value)); if(in_array($v, ['var','yok','arasira'])) return $v; return null; }
    private function mapMesafe($value) { $v = strtolower(trim($value)); if(in_array($v, ['civar','yakın','uzak'])) return $v; return null; }
    private function mapEgitimSekli($value) { $v = strtolower(trim($value)); if(in_array($v, ['tam gün','yarım gün'])) return $v; return null; }
}