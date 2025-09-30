<?php

namespace App\Libraries;

use App\Models\MenuItemModel;

class MenuBuilder
{
    protected $menuItemModel;
    protected $currentRole;

    public function __construct()
    {
        $this->menuItemModel = new MenuItemModel();
    }

    public function buildMenu(string $role): array
    {
        $this->currentRole = $role;
        $items = $this->menuItemModel->getMenuByRole($role);
        
        return $this->organizeMenu($items);
    }

    protected function organizeMenu(array $items): array
    {
        $organized = [];
        $grouped = [];

        foreach ($items as $item) {
            $groupName = $item->group_name ?? 'default';
            
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [];
            }
            
            $grouped[$groupName][] = $item;
        }

        foreach ($grouped as $groupName => $groupItems) {
            $organized[$groupName] = $this->buildTree($groupItems);
        }

        return $organized;
    }

    protected function buildTree(array $items, $parentId = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $children = $this->buildTree($items, $item->id);
                
                $menuItem = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'icon' => $item->icon,
                    'url' => $item->route_name ? route_to($item->route_name) : ($item->url ?? '#'),
                    'is_dropdown' => (bool)$item->is_dropdown,
                    'children' => $children
                ];
                
                $branch[] = $menuItem;
            }
        }

        return $branch;
    }
}