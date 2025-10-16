<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InstitutionModel;
use App\Models\CityModel;

class InstitutionController extends BaseController
{
    public function index()
    {
        $institutionModel = new InstitutionModel();
        $cityModel = new CityModel();
        
        // Genellikle tek bir kurum bilgisi olacağı için ilk kaydı alıyoruz.
        $institution = $institutionModel->first();

        $data = [
            'title'       => 'Kurum Ayarları',
            'institution' => $institution,
            'cities'      => $cityModel->orderBy('name', 'ASC')->findAll(),
        ];

        return view('admin/institution/index', array_merge($this->data, $data));
    }

    public function save()
    {
        $institutionModel = new InstitutionModel();
        
        // Formdan gelen tüm verileri alalım.
        $data = $this->request->getPost();

        // --- YENİ EKLENEN AKILLI KONUM AYRIŞTIRMA BÖLÜMÜ ---
        if (!empty($data['google_konum'])) {
            // Google Haritalar linkinden enlem ve boylamı ayrıştırmak için bir regex deseni
            // Desen, @'den sonra gelen, virgülle ayrılmış iki ondalık sayıyı yakalar
            preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $data['google_konum'], $matches);

            if (isset($matches[1]) && isset($matches[2])) {
                $data['latitude'] = $matches[1];
                $data['longitude'] = $matches[2];
            } else {
                // Eğer linkten koordinat alınamazsa, bu alanları boşaltarak
                // geçersiz verinin kaydedilmesini önleyelim.
                $data['latitude'] = null;
                $data['longitude'] = null;
            }
        }
        // --- AKILLI KONUM AYRIŞTIRMA SONU ---

        // Gelen ID'ler boş ise 'null' olarak ayarla
        $data['city_id'] = !empty($data['city_id']) ? $data['city_id'] : null;
        $data['district_id'] = !empty($data['district_id']) ? $data['district_id'] : null;

        // Veritabanında kayıt var mı diye kontrol edelim.
        $existing = $institutionModel->first();

        if ($existing) {
            // Kayıt varsa güncelle
            $isSuccess = $institutionModel->update($existing->id, $data);
        } else {
            // Kayıt yoksa yeni ekle
            $isSuccess = $institutionModel->insert($data);
        }

        if ($isSuccess) {
            return redirect()->to(route_to('admin.institution.index'))->with('success', 'Kurum bilgileri başarıyla kaydedildi.');
        }

        return redirect()->back()->withInput()->with('error', 'Bilgiler kaydedilirken bir hata oluştu.');
    }
}