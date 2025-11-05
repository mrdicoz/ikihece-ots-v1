<?php

namespace App\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table            = 'user_profiles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'first_name',
        'last_name',
        'tc_kimlik_no', 
        'branch', 
        'profile_photo',
        'address',
        'city_id',
        'district_id',
        'phone_number'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function getTeachers()
    {
        return $this->select('user_profiles.user_id, user_profiles.first_name, user_profiles.last_name, user_profiles.branch, user_profiles.profile_photo')
                    ->join('users', 'users.id = user_profiles.user_id')
                    ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
                    ->where('auth_groups_users.group', 'ogretmen')
                    ->findAll();
    }

    public function getTeacherDetails($id)
    {
        return $this->select('user_profiles.*, auth_identities.secret as email, cities.name as city_name, districts.name as district_name')
                    ->join('users', 'users.id = user_profiles.user_id')
                    ->join('auth_identities', 'auth_identities.user_id = users.id AND auth_identities.type = \'email_password\'', 'left')
                    ->join('cities', 'cities.id = user_profiles.city_id', 'left')
                    ->join('districts', 'districts.id = user_profiles.district_id', 'left')
                    ->where('user_profiles.user_id', $id)
                    ->first();
    }
}