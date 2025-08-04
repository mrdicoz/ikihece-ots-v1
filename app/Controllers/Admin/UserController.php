<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Models\UserModel; // Shield'in kendi UserModel'ini kullanacağız
use App\Models\AuthGroupsUsersModel;

class UserController extends BaseController
{
    /**
     * Display a list of all users with their profile information.
     */
    // app/Controllers/Admin/UserController.php


// app/Controllers/Admin/UserController.php

public function index()
{
    $userModel = new UserModel();

    // DÜZELTME: Sorguya 'users.deleted_at' sütunu eklendi.
    $users = $userModel->select('users.id, users.username, users.active, users.deleted_at, up.first_name, up.last_name, up.profile_photo, GROUP_CONCAT(agu.group SEPARATOR ", ") as user_groups')
        ->join('user_profiles as up', 'up.user_id = users.id', 'left')
        ->join('auth_groups_users as agu', 'agu.user_id = users.id', 'left')
        ->groupBy('users.id')
        ->orderBy('users.id', 'DESC')
        ->findAll(); // findAll() metodu soft delete olanları otomatik olarak filtreler. Tümünü görmek için withDeleted() gerekir. Şimdilik bu doğru.

    $data = [
        'title' => 'Kullanıcı Yönetimi',
        'users' => $users,
    ];

    return view('admin/users/index', array_merge($this->data, $data));

}

/**
 * Belirli bir kullanıcının detaylarını gösterir.
 */
// app/Controllers/Admin/UserController.php

/**
 * Belirli bir kullanıcının detaylarını gösterir.
 */
public function show($id)
{
    // Adım 1: Shield'in kendi metoduyla, tam ve doğru bir User nesnesi al.
    $user = auth()->getProvider()->findById($id);

    if (!$user) {
        return redirect()->to(route_to('admin.users.index'))->with('error', 'Kullanıcı bulunamadı.');
    }

    // Adım 2: Bizim UserProfileModel'imizle profil bilgilerini al.
    $profileModel = new \App\Models\UserProfileModel();
    $profile = $profileModel->where('user_id', $id)->first();

    // Adım 3: AuthGroupsUsersModel ile grupları al.
    $groupsModel = new \App\Models\AuthGroupsUsersModel();
    $groups = $groupsModel->where('user_id', $id)->findAll();

    $data = [
        'title'   => 'Kullanıcı Detayları',
        'user'    => $user,      // Tam Shield User Nesnesi
        'profile' => $profile,   // Profil Nesnesi (veya null)
        'groups'  => $groups,     // Grupların Dizisi
    ];

    return view('admin/users/show', array_merge($this->data, $data));
}

/**
 * Show the form to create a new user.
 */
public function new()
{
    $data = [
        'title'     => 'Yeni Kullanıcı Ekle',
        // Grupları konfigürasyon dosyasından alıyoruz
        'allGroups' => config('Ots')->availableGroups,
    ];

    return view('admin/users/new', array_merge($this->data, $data));
}

public function create()
{
    $users = auth()->getProvider();

    // Validasyon Kuralları
    $rules = [
        'username'         => 'required|string|is_unique[users.username]',
        'email'            => 'required|valid_email|is_unique[auth_identities.secret]',
        'password'         => 'required|strong_password',
        'password_confirm' => 'required|matches[password]',
        'first_name'       => 'required|string',
        'last_name'        => 'required|string',
    ];

    if (! $this->validate($rules)) {
        return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }

    // --- Tüm İşlemler Başlıyor ---

    // Adım 1: Kullanıcıyı ve kimliğini oluştur
    $user = new \CodeIgniter\Shield\Entities\User([
        'username' => $this->request->getPost('username'),
        'active'   => $this->request->getPost('active') ? 1 : 0,
    ]);
    $users->save($user);

    // Yeni oluşturulan kullanıcının tam nesnesini al
    $user = $users->findById($users->getInsertID());

    $user->createEmailIdentity([
        'email'    => $this->request->getPost('email'),
        'password' => $this->request->getPost('password'),
    ]);

    // Adım 2: Profil bilgilerini ve fotoğrafı hazırla
    $profileModel = new \App\Models\UserProfileModel();
    $profileData = [
        'user_id'    => $user->id,
        'first_name' => $this->request->getPost('first_name'),
        'last_name'  => $this->request->getPost('last_name'),
    ];

    // Kırpılmış fotoğraf verisini işle
    $croppedImageData = $this->request->getPost('cropped_image_data');
    if (!empty($croppedImageData)) {
        list(, $croppedImageData) = explode(',', $croppedImageData);
        $decodedImage = base64_decode($croppedImageData);
        $imageName = 'user_' . $user->id . '_' . uniqid() . '.jpg';
        $uploadPath = FCPATH . 'uploads/profile_photos/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        file_put_contents($uploadPath . $imageName, $decodedImage);
        $profileData['profile_photo'] = '/uploads/profile_photos/' . $imageName;
    }
    
    // Profil bilgilerini kaydet
    $profileModel->save($profileData);

    // Adım 3: Seçilen gruplara kullanıcıyı ata
    $groups = $this->request->getPost('groups') ?? [];
    $user->syncGroups(...$groups);

    // --- Tüm İşlemler Başarıyla Tamamlandı ---

    //====================================================================
    // LOGLAMA TETİKLEYİCİSİ
    // Tüm adımlar başarılı olduğunda log olayını burada tetikliyoruz.
    //====================================================================
    \CodeIgniter\Events\Events::trigger('user.created', $user, auth()->user());

    return redirect()->to(route_to('admin.users.index'))->with('success', 'Kullanıcı başarıyla oluşturuldu.');
}

    /**
     * Show the form to edit a specific user.
     */
// app/Controllers/Admin/UserController.php

public function edit($id)
{
    $userModel = new UserModel();
    $user = $userModel->findById($id);

    if (!$user) {
        return redirect()->back()->with('error', 'Kullanıcı bulunamadı.');
    }

    $profileModel = new \App\Models\UserProfileModel();
    $groupsModel = new \App\Models\AuthGroupsUsersModel();
    
    // Bu kullanıcının mevcut gruplarını al
    $userGroupsRaw = $groupsModel->where('user_id', $id)->findAll();
    $userGroups = array_column($userGroupsRaw, 'group');

    $data = [
        'title'       => 'Kullanıcıyı Düzenle',
        'user'        => $user,
        'profile'     => $profileModel->where('user_id', $id)->first(),
        // DÜZELTME: Tüm grupları veritabanı yerine konfigürasyon dosyasından alıyoruz.
        'allGroups'   => config('Ots')->availableGroups,
        'userGroups'  => $userGroups,
    ];

    return view('admin/users/edit', array_merge($this->data, $data));
}

    /**
     * Process the update of a specific user.
     */
public function update($id)
{
    $users = auth()->getProvider();
    $user = $users->findById($id);

    // Validasyon kuralları
    $rules = [
        'username'   => "required|string|is_unique[users.username,id,{$id}]",
        'email'      => "required|valid_email|is_unique[auth_identities.secret,user_id,{$id}]",
        'first_name' => 'required|string',
        'last_name'  => 'required|string',
    ];
    if ($this->request->getPost('password')) {
        $rules['password']         = 'required|strong_password';
        $rules['password_confirm'] = 'required|matches[password]';
    }

    if (! $this->validate($rules)) {
        return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }

    // --- Tüm Güncelleme İşlemleri Başlıyor ---

    // Adım 1: Shield User ve Identity bilgilerini doldur
    $user->fill([
        'username' => $this->request->getPost('username'),
        'active'   => $this->request->getPost('active') ? 1 : 0,
    ]);
    
    // Sadece email değiştiyse identity'i güncelle
    $identity = $user->getEmailIdentity();
    if ($identity->secret !== $this->request->getPost('email')) {
        $user->email = $this->request->getPost('email');
    }

    // Yeni şifre girildiyse güncelle
    if ($this->request->getPost('password')) {
        $user->password = $this->request->getPost('password');
    }

    // Değişiklik varsa kullanıcıyı kaydet
    if ($user->hasChanged()) {
        $users->save($user);
    }

    // Adım 2: Profil bilgilerini ve fotoğrafı güncelle
    $profileModel = new \App\Models\UserProfileModel();
    $profile = $profileModel->where('user_id', $id)->first();

    $profileData = [
        'first_name' => $this->request->getPost('first_name'),
        'last_name'  => $this->request->getPost('last_name'),
    ];

    // Kırpılmış fotoğraf verisini işle
    $croppedImageData = $this->request->getPost('cropped_image_data');
    if (!empty($croppedImageData)) {
        // Varsa eski fotoğrafı sil
        if ($profile && !empty($profile->profile_photo) && file_exists(FCPATH . ltrim($profile->profile_photo, '/'))) {
             unlink(FCPATH . ltrim($profile->profile_photo, '/'));
        }

        // Yeni fotoğrafı kaydet
        list(, $croppedImageData) = explode(',', $croppedImageData);
        $decodedImage = base64_decode($croppedImageData);
        $imageName = 'user_' . $id . '_' . uniqid() . '.jpg';
        $uploadPath = FCPATH . 'uploads/profile_photos/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        file_put_contents($uploadPath . $imageName, $decodedImage);
        $profileData['profile_photo'] = '/uploads/profile_photos/' . $imageName;
    }

    // Profil kaydını güncelle veya oluştur
    if ($profile) {
        $profileModel->update($profile->id, $profileData);
    } else {
        $profileData['user_id'] = $id;
        $profileModel->insert($profileData);
    }

    // Adım 3: Grup bilgilerini senkronize et
    $groups = $this->request->getPost('groups') ?? [];
    $user->syncGroups(...$groups);

    // --- Tüm Güncelleme İşlemleri Başarıyla Tamamlandı ---

    //====================================================================
    // LOGLAMA TETİKLEYİCİSİ
    //====================================================================
    \CodeIgniter\Events\Events::trigger('user.updated', $user, auth()->user());

    return redirect()->to(route_to('admin.users.show', $id))->with('success', 'Kullanıcı bilgileri başarıyla güncellendi.');
}

/**
 * Delete a specific user.
 * With Shield, this is a "soft delete" by default.
 */
public function delete($id)
{
    // Güvenlik Kontrolü: Admin (ID 1) ve mevcut kullanıcı silinemez.
    if ($id == 1 || $id == auth()->id()) {
        return redirect()->back()->with('error', 'Bu kullanıcı silinemez.');
    }

    $users = auth()->getProvider();
    $user = $users->findById($id);

    if (!$user) {
        return redirect()->to(route_to('admin.users.index'))->with('error', 'Kullanıcı bulunamadı.');
    }

    // Shield'in delete metodu 'soft delete' yapar (veriyi `deleted_at` ile işaretler)
    if ($users->delete($id)) {
        
        //====================================================================
        // LOGLAMA TETİKLEYİCİSİ
        // Silme işlemi başarılı olduktan sonra log olayını burada tetikliyoruz.
        // Silinen kullanıcının bilgisini ($user) ve işlemi yapanı (auth()->user()) iletiyoruz.
        //====================================================================
        \CodeIgniter\Events\Events::trigger('user.deleted', $user, auth()->user());

        return redirect()->to(route_to('admin.users.index'))->with('success', 'Kullanıcı başarıyla silindi (arşivlendi).');
    }

    return redirect()->back()->with('error', 'Kullanıcı silinirken bir hata oluştu.');
}
}