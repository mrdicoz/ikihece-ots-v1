<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserProfileModel;
use CodeIgniter\Shield\Models\UserModel;

class OnboardingController extends BaseController
{
    /**
     * Rol seçim ekranını gösterir.
     */
    public function showRoleSelection()
    {
        return view('onboarding/role_selection');
    }

    /**
     * Kullanıcının rol seçimini işler ve profil formuna yönlendirir.
     */
    public function processRoleSelection()
    {
        $role = $this->request->getPost('role');

        if (!$role || !in_array($role, ['veli', 'calisan'])) {
            return redirect()->back()->with('error', 'Lütfen geçerli bir rol seçin.');
        }

        // Rolü session'a kaydedip profil adımına geç
        session()->set('onboarding_role', $role);
        return redirect()->to('onboarding/profile');
    }

    /**
     * Seçilen role göre profil tamamlama formunu gösterir.
     */
    public function showProfileForm()
    {
        $role = session()->get('onboarding_role');
        if (!$role) {
            return redirect()->to('onboarding/role')->with('error', 'Lütfen önce rolünüzü seçin.');
        }
        
        $data['role'] = $role;
        return view('onboarding/profile_form', $data);
    }

    /**
     * Profil formunu işler, kullanıcı grubunu günceller ve süreci tamamlar.
     */
    public function processProfileForm()
    {
        $role = session()->get('onboarding_role');
        if (!$role) {
            return redirect()->to('onboarding/role')->with('error', 'Oturum süresi doldu, lütfen tekrar rol seçin.');
        }

        $rules = [
            'first_name' => 'required|string|min_length[2]',
            'last_name'  => 'required|string|min_length[2]',
            // Rol'e göre ek kurallar
        ];

        if ($role === 'veli') {
            $rules['tc_kimlik_no'] = 'required|exact_length[11]|numeric';
        } else { // calisan
            $rules['branch'] = 'required|string|min_length[2]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = auth()->user();
        $profileModel = new UserProfileModel();
        
        $profileData = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name'  => $this->request->getPost('last_name'),
        ];
        
        if ($role === 'veli') {
            // Veli için TC no'yu user_profiles'da özel bir alanda saklayabiliriz.
            // Şimdilik ana tabloda olmadığını varsayarak ilerliyorum.
            // Eğer eklenecekse migration ve model güncellenmeli.
        } else { // calisan
            $profileData['branch'] = $this->request->getPost('branch');
        }
        
        // Fotoğraf yükleme
        $croppedImageData = $this->request->getPost('cropped_image_data');
        if (!empty($croppedImageData)) {
            list(, $croppedImageData) = explode(',', $croppedImageData);
            $decodedImage = base64_decode($croppedImageData);
            $imageName = $user->id . '_' . uniqid() . '.jpg';
            $uploadPath = FCPATH . 'uploads/profile_photos/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            file_put_contents($uploadPath . $imageName, $decodedImage);
            $profileData['profile_photo'] = '/uploads/profile_photos/' . $imageName;
        }

        $profile = $profileModel->where('user_id', $user->id)->first();
        if ($profile) {
            $profileModel->update($profile->id, $profileData);
        } else {
            $profileData['user_id'] = $user->id;
            $profileModel->insert($profileData);
        }

        // Kullanıcının grubunu güncelle
        $user->removeGroup('user');
        $targetGroup = ($role === 'veli') ? 'veli' : 'ogretmen'; // Çalışanlar varsayılan öğretmen olsun
        $user->addGroup($targetGroup);

        // Onboarding session verisini temizle
        session()->remove('onboarding_role');

        return redirect()->to('/')->with('success', 'Profiliniz başarıyla tamamlandı. Sisteme hoş geldiniz!');
    }
}