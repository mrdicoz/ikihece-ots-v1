<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuGroupModel extends Model
{
    protected $table = 'menu_groups';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['name', 'title', 'icon', 'order', 'active'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'name' => 'required|alpha_dash|max_length[100]|is_unique[menu_groups.name,id,{id}]',
        'title' => 'required|max_length[255]',
    ];

    public function getActiveGroups()
    {
        return $this->where('active', 1)->orderBy('order', 'ASC')->findAll();
    }
}