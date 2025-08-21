<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\UserProfileModel;

class ShieldSeeder extends Seeder
{
    public function run()
    {
        $userModel = model(UserModel::class);
        $profileModel = new UserProfileModel();

        if ($userModel->where('username', 'admin')->first()) {
            echo "Admin kullanıcısı zaten mevcut, işlem atlandı.\n";
            return;
        }

        $adminUser = new User([
            'username' => 'admin',
            'active'   => 1,
        ]);
        $userModel->save($adminUser);

        $adminUser = $userModel->findById($userModel->getInsertID());

        $adminUser->createEmailIdentity([
            'email'    => 'admin@ikihece.com',
            'password' => 'admin@ikihece.com',
        ]);

        $adminUser->addGroup('admin');

        $profileModel->save([
            'user_id'    => $adminUser->id,
            'first_name' => 'Sistem',
            'last_name'  => 'Yöneticisi',
        ]);

        echo "Varsayılan admin kullanıcısı başarıyla oluşturuldu.\n";
    }
}