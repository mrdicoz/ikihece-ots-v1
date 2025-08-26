<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\AuthGroupsUsersModel;
use App\Models\UserProfileModel;

class UserController extends BaseController
{
    /**
     * Tüm kullanıcıları listeler.
     */
    public function index($group = null)
    {
        $userModel = new UserModel();

        $query = $userModel->select('users.id, users.username, users.active, users.deleted_at, up.first_name, up.last_name, up.profile_photo, up.branch, GROUP_CONCAT(agu.group SEPARATOR ", ") as user_groups')
            ->join('user_profiles as up', 'up.user_id = users.id', 'left')
            ->join('auth_groups_users as agu', 'agu.user_id = users.id', 'left');

        // Belirli bir grup için filtreleme yapalım
        if ($group === 'veli') {
            $query->where('agu.group', 'veli');
        } elseif ($group === 'calisan') {
            // 'calisan' grubunu diğer tüm gruplar olarak kabul edelim
            $calisanGroups = ['admin', 'yonetici', 'mudur', 'sekreter', 'ogretmen', 'servis'];
            $query->whereIn('agu.group', $calisanGroups);
        }

        $users = $query->groupBy('users.id')
            ->orderBy('users.active', 'DESC')
            ->orderBy('users.deleted_at', 'ASC')
            ->findAll();

        $data = [
            'title' => 'Kullanıcı Yönetimi',
            'users' => $users,
            'currentGroup' => $group, // Hangi listede olduğumuzu view'e gönderiyoruz
        ];
        
        return view('admin/users/index', array_merge($this->data, $data));
    }

    /**
     * Belirli bir kullanıcının detaylarını gösterir.
     */
    public function show($id)
    {
        $user = auth()->getProvider()->findById($id);

        if (!$user) {
            return redirect()->to(route_to('admin.users.index'))->with('error', 'Kullanıcı bulunamadı.');
        }

        $profileModel = new UserProfileModel();
        $groupsModel  = new AuthGroupsUsersModel();
        $profile      = $profileModel->where('user_id', $id)->first();
        $groups       = $groupsModel->where('user_id', $id)->findAll();

        $data = [
            'title'   => 'Kullanıcı Detayları',
            'user'    => $user,
            'profile' => $profile,
            'groups'  => $groups,
        ];

        return view('admin/users/show', array_merge($this->data, $data));
    }

    /**
     * Yeni kullanıcı oluşturma formunu gösterir.
     */
    public function new()
    {
        $data = [
            'title'     => 'Yeni Kullanıcı Ekle',
            'allGroups' => config('Ots')->availableGroups,
            'branches' => [
                'Fizyoterapist', 'Dil ve Konuşma Bozuklukları Uzmanı', 'Odyoloji ve Konuşma Bozuklukları Uzmanı',
                'Özel Eğitim Alanı Öğretmeni', 'Uzman Öğretici', 'Psikolog & PDR',
                'Okul Öncesi Öğretmeni', 'Çocuk Gelişimi Öğretmeni'
            ],
            'profile' => new \stdClass(),
        ];

        return view('admin/users/new', array_merge($this->data, $data));
    }

    /**
     * Yeni kullanıcıyı veritabanına kaydeder.
     */
    public function create()
    {
        $users = auth()->getProvider();

        $rules = [
            'username'         => 'required|string|is_unique[users.username]',
            'email'            => 'required|valid_email|is_unique[auth_identities.secret]',
            'password'         => 'required|strong_password',
            'password_confirm' => 'required|matches[password]',
            'first_name'       => 'required|string',
            'last_name'        => 'required|string',
        ];

        $groups = $this->request->getPost('groups') ?? [];
        if (in_array('ogretmen', $groups)) {
            $rules['branch'] = 'required|in_list[Fizyoterapist,Dil ve Konuşma Bozuklukları Uzmanı,Odyoloji ve Konuşma Bozuklukları Uzmanı,Özel Eğitim Alanı Öğretmeni,Uzman Öğretici,Psikolog & PDR,Okul Öncesi Öğretmeni,Çocuk Gelişimi Öğretmeni]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = new \CodeIgniter\Shield\Entities\User([
            'username' => $this->request->getPost('username'),
            'active'   => $this->request->getPost('active') ? 1 : 0,
        ]);
        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $user->createEmailIdentity([
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ]);

        $profileModel = new UserProfileModel();
        $profileData = [
            'user_id'       => $user->id,
            'first_name'    => $this->request->getPost('first_name'),
            'last_name'     => $this->request->getPost('last_name'),
            'tc_kimlik_no'  => $this->request->getPost('tc_kimlik_no')
        ];
        if (in_array('ogretmen', $groups)) {
            $profileData['branch'] = $this->request->getPost('branch');
        }
        
        $croppedImageData = $this->request->getPost('cropped_image_data');
        if (!empty($croppedImageData)) {
            list(, $croppedImageData) = explode(',', $croppedImageData);
            $decodedImage = base64_decode($croppedImageData);
            $imageName = 'user_' . $user->id . '_' . uniqid() . '.jpg';
            $uploadPath = FCPATH . 'uploads/profile_photos/';
            if (!is_dir($uploadPath)) { mkdir($uploadPath, 0777, true); }
            file_put_contents($uploadPath . $imageName, $decodedImage);
            $profileData['profile_photo'] = '/uploads/profile_photos/' . $imageName;
        }
        
        $profileModel->save($profileData);
        $user->syncGroups(...$groups);
        
        \CodeIgniter\Events\Events::trigger('user.created', $user, auth()->user());
        return redirect()->to(route_to('admin.users.index'))->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    /**
     * Kullanıcı düzenleme formunu gösterir.
     */
    public function edit($id)
    {
        $userModel = new UserModel();
        $user = $userModel->findById($id);

        if (!$user) {
            return redirect()->back()->with('error', 'Kullanıcı bulunamadı.');
        }

        $profileModel = new UserProfileModel();
        $groupsModel = new AuthGroupsUsersModel();
        
        $userGroupsRaw = $groupsModel->where('user_id', $id)->findAll();
        $userGroups = array_column($userGroupsRaw, 'group');

        $data = [
            'title'       => 'Kullanıcıyı Düzenle',
            'user'        => $user,
            'profile'     => $profileModel->where('user_id', $id)->first(),
            'allGroups'   => config('Ots')->availableGroups,
            'userGroups'  => $userGroups,
            'branches'    => [
                'Fizyoterapist', 'Dil ve Konuşma Bozuklukları Uzmanı', 'Odyoloji ve Konuşma Bozuklukları Uzmanı',
                'Özel Eğitim Alanı Öğretmeni', 'Uzman Öğretici', 'Psikolog & PDR',
                'Okul Öncesi Öğretmeni', 'Çocuk Gelişimi Öğretmeni'
            ],
        ];

        return view('admin/users/edit', array_merge($this->data, $data));
    }

    /**
     * Kullanıcıyı günceller.
     */
    public function update($id)
    {
        $users = auth()->getProvider();
        $user = $users->findById($id);

        $rules = [
            'username'      => "required|string|is_unique[users.username,id,{$id}]",
            'email'         => "required|valid_email|is_unique[auth_identities.secret,user_id,{$id}]",
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'tc_kimlik_no'  => 'permit_empty|numeric|exact_length[11]',
        ];
        
        $groups = $this->request->getPost('groups') ?? [];
        if (in_array('ogretmen', $groups)) {
            $rules['branch'] = 'required|in_list[Fizyoterapist,Dil ve Konuşma Bozuklukları Uzmanı,Odyoloji ve Konuşma Bozuklukları Uzmanı,Özel Eğitim Alanı Öğretmeni,Uzman Öğretici,Psikolog & PDR,Okul Öncesi Öğretmeni,Çocuk Gelişimi Öğretmeni]';
        }

        if ($this->request->getPost('password')) {
            $rules['password']         = 'required|strong_password';
            $rules['password_confirm'] = 'required|matches[password]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user->fill([
            'username' => $this->request->getPost('username'),
            'active'   => $this->request->getPost('active') ? 1 : 0,
        ]);
        
        $identity = $user->getEmailIdentity();
        if ($identity && $identity->secret !== $this->request->getPost('email')) {
            $user->email = $this->request->getPost('email');
        }

        if ($this->request->getPost('password')) {
            $user->password = $this->request->getPost('password');
        }

        if ($user->hasChanged()) {
            $users->save($user);
        }

        $profileModel = new UserProfileModel();
        $profile = $profileModel->where('user_id', $id)->first();

        $profileData = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name'  => $this->request->getPost('last_name'),
            'tc_kimlik_no' => $this->request->getPost('tc_kimlik_no'),
        ];
        
        if (in_array('ogretmen', $groups)) {
            $profileData['branch'] = $this->request->getPost('branch');
        } else {
            $profileData['branch'] = null;
        }

        $croppedImageData = $this->request->getPost('cropped_image_data');
        if (!empty($croppedImageData)) {
            if ($profile && !empty($profile->profile_photo) && file_exists(FCPATH . ltrim($profile->profile_photo, '/'))) {
                 unlink(FCPATH . ltrim($profile->profile_photo, '/'));
            }
            list(, $croppedImageData) = explode(',', $croppedImageData);
            $decodedImage = base64_decode($croppedImageData);
            $imageName = 'user_' . $id . '_' . uniqid() . '.jpg';
            $uploadPath = FCPATH . 'uploads/profile_photos/';
            if (!is_dir($uploadPath)) { mkdir($uploadPath, 0777, true); }
            file_put_contents($uploadPath . $imageName, $decodedImage);
            $profileData['profile_photo'] = '/uploads/profile_photos/' . $imageName;
        }

        if ($profile) {
            $profileModel->update($profile->id, $profileData);
        } else {
            $profileData['user_id'] = $id;
            $profileModel->insert($profileData);
        }

        $user->syncGroups(...$groups);
        
        \CodeIgniter\Events\Events::trigger('user.updated', $user, auth()->user());
        return redirect()->to(route_to('admin.users.show', $id))->with('success', 'Kullanıcı bilgileri başarıyla güncellendi.');
    }

    /**
     * Bir kullanıcıyı siler.
     */
    public function delete($id)
    {
        if ($id == 1 || $id == auth()->id()) {
            return redirect()->back()->with('error', 'Bu kullanıcı silinemez.');
        }

        $users = auth()->getProvider();
        $user = $users->findById($id);

        if (!$user) {
            return redirect()->to(route_to('admin.users.index'))->with('error', 'Kullanıcı bulunamadı.');
        }

        if ($users->delete($id)) {
            \CodeIgniter\Events\Events::trigger('user.deleted', $user, auth()->user());
            return redirect()->to(route_to('admin.users.index'))->with('success', 'Kullanıcı başarıyla silindi (arşivlendi).');
        }

        return redirect()->back()->with('error', 'Kullanıcı silinirken bir hata oluştu.');
    }
}