<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;

class SettingsController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $query = $db->table('app_settings')->where('key', 'license_key')->get();
        $this->data['license_key'] = $query->getRow()->value ?? '';
        $this->data['title'] = 'Lisans Ayarları';

        // Cache'den sadece lisansın geçerli olup olmadığını al
        $license_valid = cache()->get('license_status');
        $this->data['is_license_active'] = ($license_valid === true);

        return view('admin/settings/index', $this->data);
    }

    public function save()
    {
        $db = db_connect();
        $licenseKey = $this->request->getPost('license_key');
        $data = ['key' => 'license_key', 'value' => $licenseKey];

        $builder = $db->table('app_settings');
        if ($builder->where('key', 'license_key')->countAllResults(false) > 0) {
             $builder->where('key', 'license_key')->update($data);
        } else {
            $builder->insert($data);
        }

        // Eski cache'i sil ki yeni anahtar hemen kontrol edilsin
        cache()->delete('license_status'); 

        // Kullanıcıyı yönlendirmeden önce yeni durumu kontrol et
        (new \App\Libraries\LicenseService())->checkLicense();

        return redirect()->to(route_to('admin.settings.index'))->with('success', 'Lisans anahtarı kaydedildi. Durum güncellendi.');
    }
}