<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuPermissionModel extends Model
{
    protected $table = 'menu_permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['menu_item_id', 'role'];

    public function syncPermissions(int $menuItemId, array $roles)
    {
        $this->where('menu_item_id', $menuItemId)->delete();
        
        if (!empty($roles)) {
            $data = array_map(fn($role) => [
                'menu_item_id' => $menuItemId,
                'role' => $role
            ], $roles);
            
            $this->insertBatch($data);
        }
    }
}