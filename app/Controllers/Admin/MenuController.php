<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MenuGroupModel;
use App\Models\MenuItemModel;
use App\Models\MenuPermissionModel;

class MenuController extends BaseController
{
    protected $menuGroupModel;
    protected $menuItemModel;
    protected $permissionModel;

    public function __construct()
    {
        $this->menuGroupModel = new MenuGroupModel();
        $this->menuItemModel = new MenuItemModel();
        $this->permissionModel = new MenuPermissionModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Menü Yönetimi',
            'groups' => $this->menuGroupModel->findAll(),
            'items' => $this->menuItemModel->getItemsWithPermissions()
        ];

        return view('admin/menu/index', array_merge($this->data, $data));  // ✅ DOĞRU
    }

    // Grup CRUD
    public function createGroup()
    {
        // POST isteği mi kontrol et
        if ($this->request->getMethod() === 'POST') {
            $postData = $this->request->getPost();
            
            // TEST 1: Veri geldi mi?
            if (empty($postData)) {
                return redirect()->back()->with('error', 'POST verisi boş geldi! Form düzgün gönderilmedi.');
            }
            
            // TEST 2: Hangi alanlar geldi görelim
            $receivedFields = array_keys($postData);
            log_message('info', 'Grup Create - Gelen POST alanları: ' . implode(', ', $receivedFields));
            log_message('info', 'Grup Create - POST Verisi: ' . json_encode($postData));
            
            // TEST 3: Model'e kaydetmeyi dene
            try {
                $saved = $this->menuGroupModel->save($postData);
                
                if ($saved) {
                    $insertId = $this->menuGroupModel->getInsertID();
                    log_message('info', 'Grup başarıyla kaydedildi. ID: ' . $insertId);
                    return redirect()->to(route_to('admin.menu.index'))->with('message', 'Grup başarıyla oluşturuldu! ID: ' . $insertId);
                } else {
                    // Validation hataları var mı?
                    $errors = $this->menuGroupModel->errors();
                    
                    if (!empty($errors)) {
                        log_message('error', 'Validation hataları: ' . json_encode($errors));
                        return redirect()->back()->withInput()->with('errors', $errors);
                    } else {
                        log_message('error', 'Model save() false döndü ama hata yok!');
                        return redirect()->back()->withInput()->with('error', 'Kayıt yapılamadı, nedeni bilinmiyor.');
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Exception: ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Hata: ' . $e->getMessage());
            }
        }

        // GET isteği - formu göster
        $data = ['title' => 'Yeni Grup'];
        return view('admin/menu/group_form', array_merge($this->data, $data));
    }

    // Menü Item CRUD
    public function createItem()
    {
        if ($this->request->getMethod() === 'POST') {
            $postData = $this->request->getPost();
            
            if (empty($postData)) {
                return redirect()->back()->with('error', 'POST verisi boş geldi!');
            }
            
            log_message('info', 'Menu Item Create - POST Data: ' . json_encode($postData));
            
            $roles = $postData['roles'] ?? [];
            unset($postData['roles']);
            
            if (empty($roles)) {
                return redirect()->back()->withInput()->with('error', 'En az bir rol seçmelisiniz!');
            }
            
            // 🔧 DÜZELTME: Boş değerleri NULL yap
            if (empty($postData['group_id'])) $postData['group_id'] = null;
            if (empty($postData['parent_id'])) $postData['parent_id'] = null;
            if (empty($postData['route_name'])) $postData['route_name'] = null;
            if (empty($postData['url'])) $postData['url'] = null;
            if (empty($postData['icon'])) $postData['icon'] = null;
            
            // Checkbox değerleri
            $postData['is_dropdown'] = isset($postData['is_dropdown']) ? 1 : 0;
            $postData['active'] = isset($postData['active']) ? 1 : 0;
            
            log_message('info', 'Menu Item Create - Cleaned Data: ' . json_encode($postData));
            
            try {
                if ($this->menuItemModel->save($postData)) {
                    $itemId = $this->menuItemModel->getInsertID();
                    log_message('info', 'Menu Item kaydedildi. ID: ' . $itemId);
                    
                    $this->permissionModel->syncPermissions($itemId, $roles);
                    log_message('info', 'Permissions kaydedildi');
                    
                    return redirect()->to(route_to('admin.menu.index'))->with('message', 'Menü öğesi başarıyla eklendi!');
                } else {
                    $errors = $this->menuItemModel->errors();
                    
                    if (!empty($errors)) {
                        log_message('error', 'Menu Item Validation Errors: ' . json_encode($errors));
                        return redirect()->back()->withInput()->with('errors', $errors);
                    } else {
                        return redirect()->back()->withInput()->with('error', 'Menü öğesi kaydedilemedi.');
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Menu Item Exception: ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Hata: ' . $e->getMessage());
            }
        }

        $data = [
            'title' => 'Yeni Menü Öğesi',
            'groups' => $this->menuGroupModel->findAll(),
            'items' => $this->menuItemModel->where('is_dropdown', 1)->findAll(),
            'roles' => ['admin', 'yonetici', 'mudur', 'sekreter', 'ogretmen', 'veli', 'servis'],
            'selectedRoles' => []
        ];

        return view('admin/menu/item_form', array_merge($this->data, $data));
    }

    public function deleteItem($id)
    {
        // AJAX isteği mi kontrol et
        if ($this->request->isAJAX()) {
            try {
                if ($this->menuItemModel->delete($id)) {
                    log_message('info', 'Menu item silindi. ID: ' . $id);
                    return $this->response->setJSON(['success' => true]);
                } else {
                    log_message('error', 'Menu item silinemedi. ID: ' . $id);
                    return $this->response->setJSON(['success' => false, 'message' => 'Silme işlemi başarısız']);
                }
            } catch (\Exception $e) {
                log_message('error', 'Menu item silme hatası: ' . $e->getMessage());
                return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        
        // Normal request ise
        if ($this->menuItemModel->delete($id)) {
            return redirect()->to(route_to('admin.menu.index'))->with('message', 'Menü öğesi silindi');
        }
        
        return redirect()->back()->with('error', 'Silme başarısız');
    }

    public function updateItem($id)
{
    $item = $this->menuItemModel->getItemsWithPermissions($id);
    
    if (!$item) {
        return redirect()->to(route_to('admin.menu.index'))->with('error', 'Menü öğesi bulunamadı');
    }

    if ($this->request->getMethod() === 'POST') {
        $postData = $this->request->getPost();
        
        if (empty($postData)) {
            return redirect()->back()->with('error', 'POST verisi boş geldi!');
        }
        
        $roles = $postData['roles'] ?? [];
        unset($postData['roles']);
        
        if (empty($roles)) {
            return redirect()->back()->withInput()->with('error', 'En az bir rol seçmelisiniz!');
        }
        
        // Boş değerleri NULL yap
        if (empty($postData['group_id'])) $postData['group_id'] = null;
        if (empty($postData['parent_id'])) $postData['parent_id'] = null;
        if (empty($postData['route_name'])) $postData['route_name'] = null;
        if (empty($postData['url'])) $postData['url'] = null;
        if (empty($postData['icon'])) $postData['icon'] = null;
        
        $postData['is_dropdown'] = isset($postData['is_dropdown']) ? 1 : 0;
        $postData['active'] = isset($postData['active']) ? 1 : 0;

        try {
            if ($this->menuItemModel->update($id, $postData)) {
                $this->permissionModel->syncPermissions($id, $roles);
                return redirect()->to(route_to('admin.menu.index'))->with('message', 'Güncellendi');
            }
            
            $errors = $this->menuItemModel->errors();
            if (!empty($errors)) {
                return redirect()->back()->withInput()->with('errors', $errors);
            } else {
                return redirect()->back()->withInput()->with('error', 'Güncelleme başarısız.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Hata: ' . $e->getMessage());
        }
    }

    // GET - Form göster
    $data = [
        'title' => 'Menü Düzenle',
        'item' => $item,
        'groups' => $this->menuGroupModel->findAll(),
        'items' => $this->menuItemModel->where('is_dropdown', 1)->where('id !=', $id)->findAll(),
        'roles' => ['admin', 'yonetici', 'mudur', 'sekreter', 'ogretmen', 'veli', 'servis'],
        'selectedRoles' => !empty($item->roles) ? explode(',', $item->roles) : []
    ];

    return view('admin/menu/item_form', array_merge($this->data, $data));
}
}