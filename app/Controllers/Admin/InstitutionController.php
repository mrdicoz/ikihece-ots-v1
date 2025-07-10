<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InstitutionModel;
use App\Models\CityModel; // YENİ: CityModel'i ekledik

class InstitutionController extends BaseController
{
    public function index()
    {
        $institutionModel = new InstitutionModel();
        $cityModel = new CityModel(); // YENİ: CityModel'i başlattık
        
        // Genellikle tek bir kurum bilgisi olacağı için ilk kaydı alıyoruz.
        $institution = $institutionModel->first();


        $data = [
            'title'       => 'Kurum Ayarları',
            'institution' => $institution,
            'cities'      => $cityModel->orderBy('name', 'ASC')->findAll(), // YENİ: İl listesini view'e gönder

        ];

        return view('admin/institution/index', $data);
    }

    public function save()
    {
        $institutionModel = new InstitutionModel();
        
        // Formdan gelen tüm verileri alalım.
        $data = $this->request->getPost();

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