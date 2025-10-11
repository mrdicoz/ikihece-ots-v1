<?php
namespace App\Controllers;

use App\Models\AuthGroupsUsersModel;

class TrackingController extends BaseController
{
    public function map()
    {
        // Sadece 'servis' grubundaki kullanıcıları getir
        $db = \Config\Database::connect();
        $drivers = $db->table('users u')
            ->select('u.id, up.first_name, up.last_name')
            ->join('user_profiles up', 'up.user_id = u.id')
            ->join('auth_groups_users agu', 'agu.user_id = u.id')
            ->where('agu.group', 'servis')
            ->get()
            ->getResultArray();
        
        $this->data['drivers'] = $drivers;
        $this->data['title'] = 'Servis Takip Haritası';
        
        return view('tracking/map', $this->data);
    }
}