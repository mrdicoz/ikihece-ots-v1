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
        $userId = auth()->id();
        $user = auth()->user();

        $profileModel = new UserProfileModel();
        $cityModel = new CityModel();

        $profile = $profileModel->where('user_id', $userId)->first();

        if (!$profile) {
            $profileModel->insert(['user_id' => $userId]);
            $profile = $profileModel->where('user_id', $userId)->first();
        }

        $data = [
            'title'    => 'Profilim',
            'user'     => $user,
            'profile'  => $profile,
            'cities'   => $cityModel->findAll(),
            'branches' => [ // Öğretmenler için branş listesi
                'Fizyoterapist',
                'Dil ve Konuşma Bozuklukları Uzmanı',
                'Odyoloji ve Konuşma Bozuklukları Uzmanı',
                'Özel Eğitim Alanı Öğretmeni',
                'Uzman Öğretici',
                'Psikolog & PDR',
                'Okul Öncesi Öğretmeni',
                'Çocuk Gelişimi Öğretmeni'
            ]
        ];

        return view('profile/index', array_merge($this->data, $data));
    }

        /**
        * Profil bilgilerini günceller.
        */
 public function update()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $profileModel = new UserProfileModel();
        $profile = $profileModel->where('user_id', $userId)->first();

        // Validasyon Kuralları
        $rules = [
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'tc_kimlik_no' => 'permit_empty|numeric|exact_length[11]',
            'phone_number' => 'permit_empty|min_length[10]|max_length[20]',
            'city_id'      => 'permit_empty|integer',
            'district_id'  => 'permit_empty|integer',
            'address'      => 'permit_empty|string',
        ];

        if ($user->inGroup('ogretmen')) {
            $rules['branch'] = 'required|in_list[Fizyoterapist,Dil ve Konuşma Bozuklukları Uzmanı,Odyoloji ve Konuşma Bozuklukları Uzmanı,Özel Eğitim Alanı Öğretmeni,Uzman Öğretici,Psikolog & PDR,Okul Öncesi Öğretmeni,Çocuk Gelişimi Öğretmeni]';
        }

        if ($this->request->getPost('password')) {
            $rules['password'] = 'required|strong_password';
            $rules['password_confirm'] = 'required|matches[password]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Profil verilerini hazırla
        $cityId = $this->request->getPost('city_id');
        $districtId = $this->request->getPost('district_id');

        $profileData = [
            'first_name'   => $this->request->getPost('first_name'),
            'last_name'    => $this->request->getPost('last_name'),
            'tc_kimlik_no' => $this->request->getPost('tc_kimlik_no'),
            'phone_number' => $this->request->getPost('phone_number'),
            'city_id'      => !empty($cityId) ? $cityId : null,
            'district_id'  => !empty($districtId) ? $districtId : null,
            'address'      => $this->request->getPost('address'),
        ];
        
        if ($user->inGroup('ogretmen')) {
            $profileData['branch'] = $this->request->getPost('branch');
        } else {
            $profileData['branch'] = null;
        }

        // Fotoğraf yükleme ve güncelleme işlemleri
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
            $profileData['profile_photo'] = '/uploads/profile_photos/' . $imageName;
        }

        if ($profileModel->update($profile->id, $profileData)) {
            // Şifre değiştirildiyse Shield user'ı güncelle
            if ($this->request->getPost('password')) {
                $user->password = $this->request->getPost('password');
                auth()->getProvider()->save($user);
            }
            return redirect()->to('/profile')->with('success', 'Profiliniz başarıyla güncellendi.');
        }

        return redirect()->back()->withInput()->with('error', 'Profil güncellenirken bir hata oluştu.');
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

    
    /**
     * Kullanıcının aktif rolünü session'da değiştirir.
     * @param string $newRole Geçiş yapılmak istenen yeni rol (grup adı)
     */
    public function switchRole(string $newRole)
    {
        $user = auth()->user();

        // Güvenlik Kontrolü: Kullanıcı gerçekten o role sahip mi?
        // Shield'in inGroup metodu grupları zaten bilir, ekstra sorguya gerek yok.
        if (! $user->inGroup($newRole)) {
            return redirect()->back()->with('error', 'Bu role geçiş yapma yetkiniz bulunmamaktadır.');
        }

        // Session'daki aktif rolü güncelle
        session()->set('active_role', $newRole);

        // Kullanıcıyı yeni rolünün paneline (anasayfaya) yönlendir.
        // Anasayfa zaten rolü kontrol edip doğru yere yönlendirecek.
        return redirect()->to('/')->with('success', ucfirst($newRole) . ' görünümüne başarıyla geçiş yapıldı.');
    }
}