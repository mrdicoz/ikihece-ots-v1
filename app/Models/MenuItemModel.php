<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table = 'menu_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['group_id', 'parent_id', 'title', 'route_name', 'url', 'icon', 'order', 'active', 'is_dropdown'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getMenuByRole($role)
    {
        return $this->select('menu_items.*, menu_groups.name as group_name')
            ->join('menu_permissions', 'menu_permissions.menu_item_id = menu_items.id')
            ->join('menu_groups', 'menu_groups.id = menu_items.group_id', 'left')
            ->where('menu_items.active', 1)
            ->where('menu_permissions.role', $role)
            ->orderBy('menu_groups.order', 'ASC')
            ->orderBy('menu_items.order', 'ASC')
            ->findAll();
    }

    public function getItemsWithPermissions($itemId = null)
    {
        $builder = $this->db->table('menu_items')
            ->select('menu_items.*, GROUP_CONCAT(menu_permissions.role) as roles')
            ->join('menu_permissions', 'menu_permissions.menu_item_id = menu_items.id', 'left')
            ->groupBy('menu_items.id');
        
        if ($itemId) {
            $builder->where('menu_items.id', $itemId);
            return $builder->get()->getRow();
        }
        
        return $builder->get()->getResult();
    }
}