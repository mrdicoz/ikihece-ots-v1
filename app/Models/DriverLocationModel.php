<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverLocationModel extends Model
{
    protected $table            = 'driver_locations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'latitude',
        'longitude',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'user_id'   => 'required|integer',
        'latitude'  => 'required|decimal',
        'longitude' => 'required|decimal',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}