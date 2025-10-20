<?php

namespace App\Controllers\Documents;

use App\Controllers\BaseController;
use App\Models\DocumentCategoryModel;

class CategoryController extends BaseController
{
    protected $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new DocumentCategoryModel();
        // Pusuladaki yetkilendirme notu: Sadece admin ve yönetici erişebilir
        // Bu kontrolü rotada 'filter' ile yapmak daha merkezi bir yöntemdir,
        // ancak controller içinde de yapılabilir. Pusuladaki gibi bırakıyorum.
        if (!auth()->user()->inGroup('admin', 'yonetici')) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }
    }

    public function index()
    {
        $data = [
            'title' => 'Belge Kategorileri',
            'categories' => $this->categoryModel->orderBy('order', 'ASC')->findAll()
        ];

        return view('documents/categories/index', array_merge($this->data, $data));
    }

    public function create()
    {
        if ($this->request->is('post')) {
            $rules = [
                'name' => 'required|max_length[255]',
                'order' => 'permit_empty|integer'
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $this->categoryModel->insert([
                'name' => $this->request->getPost('name'),
                'description' => $this->request->getPost('description'),
                'order' => $this->request->getPost('order') ?? 0,
                'active' => 1 // Yeni kategori varsayılan olarak aktif olsun
            ]);

            return redirect()->to('/documents/categories')->with('success', 'Kategori başarıyla eklendi.');
        }

        $data = ['title' => 'Yeni Kategori'];

        return view('documents/categories/form', array_merge($this->data, $data));
    }

    public function edit($id)
    {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return redirect()->to('/documents/categories')->with('error', 'Kategori bulunamadı.');
        }

        if ($this->request->is('post')) {
            $rules = [
                'name' => 'required|max_length[255]',
                'order' => 'permit_empty|integer'
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $this->categoryModel->update($id, [
                'name' => $this->request->getPost('name'),
                'description' => $this->request->getPost('description'),
                'order' => $this->request->getPost('order') ?? 0,
                'active' => $this->request->getPost('active') ?? 0 // 0 olarak değiştirildi, checkbox işaretli değilse 0 gitsin
            ]);

            return redirect()->to('/documents/categories')->with('success', 'Kategori güncellendi.');
        }

        $data = ['title' => 'Kategori Düzenle', 'category' => $category];

        return view('documents/categories/form', array_merge($this->data, $data) );
    }

    public function delete($id)
    {
        // İlişkili şablonlar varsa silmeyi engellemek iyi bir pratik olabilir,
        // ancak pusula direkt silme belirttiği için o şekilde ilerliyoruz.
        $this->categoryModel->delete($id);
        return redirect()->to('/documents/categories')->with('success', 'Kategori silindi.');
    }
}