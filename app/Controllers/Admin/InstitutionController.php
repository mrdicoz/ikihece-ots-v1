<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InstitutionModel;
use App\Models\CityModel;
use CodeIgniter\Shield\Models\UserModel; // Shield UserModel'ı eklendi

class InstitutionController extends BaseController
{
    public function index()
    {
        $institutionModel = new InstitutionModel();
        $cityModel = new CityModel();
        $userModel = new UserModel(); // UserModel başlatıldı

        $institution = $institutionModel->first();

        $data = [
            'title'       => 'Kurum Ayarları',
            'institution' => $institution,
            'cities'      => $cityModel->orderBy('name', 'ASC')->findAll(),
            'users'       => $userModel->findAll() // Tüm kullanıcılar view'e gönderildi
        ];

        return view('admin/institution/index', array_merge($this->data, $data));
    }

    public function save()
    {
        $institutionModel = new InstitutionModel();
        $existing = $institutionModel->first();

        // 1. Metin ve seçim verilerini al
        $data = $this->request->getPost();

        // --- AKILLI KONUM AYRIŞTIRMA (SİZİN KODUNUZ KORUNDU) ---
        if (!empty($data['google_konum'])) {
            preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $data['google_konum'], $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $data['latitude'] = $matches[1];
                $data['longitude'] = $matches[2];
            } else {
                $data['latitude'] = null;
                $data['longitude'] = null;
            }
        }

        // 2. Gelen ID'ler boş ise 'null' olarak ayarla
        $data['city_id'] = !empty($data['city_id']) ? $data['city_id'] : null;
        $data['district_id'] = !empty($data['district_id']) ? $data['district_id'] : null;
        $data['kurum_muduru_user_id'] = !empty($data['kurum_muduru_user_id']) ? $data['kurum_muduru_user_id'] : null;

        // 3. Dosya Yükleme İşlemleri EKLENDİ
        $this->handleFileUpload('kurum_logo_path', 'kurum_logo_path', $data, $existing);
        $this->handleFileUpload('kurum_qr_kod_path', 'kurum_qr_kod_path', $data, $existing);

        // 4. Veritabanını Güncelle/Ekle
        if ($existing) {
            $isSuccess = $institutionModel->update($existing->id, $data);
        } else {
            $isSuccess = $institutionModel->insert($data);
        }

        if ($isSuccess) {
            return redirect()->to(route_to('admin.institution.index'))->with('success', 'Kurum bilgileri başarıyla kaydedildi.');
        }

        return redirect()->back()->withInput()->with('error', 'Bilgiler kaydedilirken bir hata oluştu: ' . json_encode($institutionModel->errors()));
    }

    /**
     * Dosya yükleme işlemini yöneten yardımcı fonksiyon. EKLENDİ
     * @param string $fileInputName Formdaki dosya input'unun adı
     * @param string $dbColumnName Veritabanındaki dosya yolu sütununun adı
     * @param array &$data Kaydedilecek veri dizisi
     * @param object|null $existingRecord Mevcut veritabanı kaydı (varsa)
     */
    private function handleFileUpload(string $fileInputName, string $dbColumnName, array &$data, $existingRecord = null)
    {
        $file = $this->request->getFile($fileInputName);

        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Varsa eski dosyayı sil
            if ($existingRecord && !empty($existingRecord->$dbColumnName) && file_exists(FCPATH . $existingRecord->$dbColumnName)) {
                @unlink(FCPATH . $existingRecord->$dbColumnName);
            }

            // Yeni dosyayı public/uploads/institution klasörüne taşı
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/institution', $newName);
            
            // Veritabanına kaydedilecek dosya yolunu $data dizisine ekle
            $data[$dbColumnName] = 'uploads/institution/' . $newName;
        }
    }
}