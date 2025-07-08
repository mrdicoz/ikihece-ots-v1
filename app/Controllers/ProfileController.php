<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CityModel;
use App\Models\DistrictModel; // BU SATIRIN EKLENMESİ HATAYI ÇÖZECEKTİR
use App\Models\UserProfileModel;

class ProfileController extends BaseController
{
    public function index()
    {
        $userId = auth()->id(); // Giriş yapmış kullanıcının ID'si

        $profileModel = new UserProfileModel();
        $cityModel = new CityModel();

        // Kullanıcının profil verisini bul
        $profile = $profileModel->where('user_id', $userId)->first();

        // EĞER KULLANICI YENİ KAYIT OLDUYSA VE PROFİLİ HİÇ OLUŞMADIYSA...
        // ...onun için boş bir profil satırı oluşturalım. Bu, sistemi daha kararlı hale getirir.
        if (!$profile) {
            $profileModel->insert(['user_id' => $userId]);
            $profile = $profileModel->where('user_id', $userId)->first();
        }

        $data = [
            'title'   => 'Profilim',
            'user'    => auth()->user(), // Shield'in user nesnesi
            'profile' => $profile,      // Bizim user_profiles tablosundaki veri
            'cities'  => $cityModel->findAll(), // illeri view'e gönder
        ];

        return view('profile/index', $data);
    }

        /**
        * Profil bilgilerini günceller.
        */
    public function update()
{
    $userId = auth()->id();
    $profileModel = new UserProfileModel();
    $profile = $profileModel->where('user_id', $userId)->first();

    // DÜZELTME: Gelen ID'ler boş ise 'null' olarak ayarla.
    $cityId = $this->request->getPost('city_id');
    $districtId = $this->request->getPost('district_id');

    $data = [
        'first_name'   => $this->request->getPost('first_name'),
        'last_name'    => $this->request->getPost('last_name'),
        'phone_number' => $this->request->getPost('phone_number'),
        'city_id'      => !empty($cityId) ? $cityId : null,
        'district_id'  => !empty($districtId) ? $districtId : null,
        'address'      => $this->request->getPost('address'),
    ];

    $croppedImageData = $this->request->getPost('cropped_image_data');
    if (!empty($croppedImageData)) {
        list($type, $croppedImageData) = explode(';', $croppedImageData);
        list(, $croppedImageData)      = explode(',', $croppedImageData);
        $decodedImage = base64_decode($croppedImageData);

        $uploadPath = FCPATH . 'uploads/profile_photos/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $imageName = $userId . '_' . uniqid() . '.jpg';
        
        if ($profile->profile_photo && $profile->profile_photo !== '/assets/images/user.jpg' && file_exists(FCPATH . substr($profile->profile_photo, 1))) {
            unlink(FCPATH . substr($profile->profile_photo, 1));
        }

        file_put_contents($uploadPath . $imageName, $decodedImage);
        $data['profile_photo'] = '/uploads/profile_photos/' . $imageName;
    }

    if ($profileModel->update($profile->id, $data)) {
        return redirect()->to('/profile')->with('success', 'Profiliniz başarıyla güncellendi.');
    } else {
        return redirect()->back()->withInput()->with('error', 'Profil güncellenirken bir hata oluştu.');
    }
}

    /**
     * AJAX ile bir şehre ait ilçeleri getirir.
     */
    public function getDistricts($cityId)
    {
        $districtModel = new DistrictModel();
        $districts = $districtModel->where('city_id', $cityId)->orderBy('name', 'ASC')->findAll();
        return $this->response->setJSON($districts);
    }
}