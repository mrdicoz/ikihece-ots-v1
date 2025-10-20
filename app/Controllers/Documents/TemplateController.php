<?php

namespace App\Controllers\Documents;

use App\Controllers\BaseController;
use App\Models\DocumentTemplateModel;
use App\Models\DocumentCategoryModel;
use App\Models\InstitutionModel;

class TemplateController extends BaseController
{
    protected $templateModel;
    protected $categoryModel;
    protected $institutionModel;


    public function __construct()
    {
        $this->templateModel = new DocumentTemplateModel();
        $this->categoryModel = new DocumentCategoryModel();
        $this->institutionModel = new \App\Models\InstitutionModel(); 

        if (!auth()->user()->inGroup('admin', 'yonetici')) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }
    }

    public function index()
    {
        $data = [
            'title' => 'Belge Şablonları',
            'templates' => $this->templateModel
                ->select('document_templates.*, document_categories.name as category_name')
                ->join('document_categories', 'document_categories.id = document_templates.category_id', 'left')
                ->findAll()
        ];

        return view('documents/templates/index', array_merge($this->data, $data));
    }

    public function create()
    {
        if ($this->request->is('post')) {
            $rules = [
                'category_id' => 'required|integer',
                'name'        => 'required|max_length[255]',
                'content'     => 'required'
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $this->templateModel->insert([
                'category_id'         => $this->request->getPost('category_id'),
                'name'                => $this->request->getPost('name'),
                'description'         => $this->request->getPost('description'),
                'content'             => $this->request->getPost('content'),
                'static_fields'       => json_encode($this->request->getPost('static_fields') ?? []),
                'dynamic_fields'      => $this->request->getPost('dynamic_fields'),
                'has_number'          => $this->request->getPost('has_number') ?? 0,
                'allow_custom_number' => $this->request->getPost('allow_custom_number') ?? 0,
                'fill_gaps'           => $this->request->getPost('fill_gaps') ?? 0, // Düzeltme: ?? 1 yerine ?? 0
                'active'              => 1
            ]);

            return redirect()->to('/documents/templates')->with('success', 'Şablon başarıyla oluşturuldu.');
        }

        $data = [
            'title'      => 'Yeni Şablon Oluştur',
            'categories' => $this->categoryModel->getActiveCategories(),
            'institution' => $this->getInstitutionData() // BU SATIRI EKLE

        ];

        return view('documents/templates/create', array_merge($this->data, $data));
    }
    
    // YENİ EKLENEN METOD
    public function edit($id)
    {
        $template = $this->templateModel->find($id);
        if (!$template) {
            return redirect()->to('/documents/templates')->with('error', 'Şablon bulunamadı.');
        }

        if ($this->request->is('post')) {
            $rules = [
                'category_id' => 'required|integer',
                'name'        => 'required|max_length[255]',
                'content'     => 'required'
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $this->templateModel->update($id, [
                'category_id'         => $this->request->getPost('category_id'),
                'name'                => $this->request->getPost('name'),
                'description'         => $this->request->getPost('description'),
                'content'             => $this->request->getPost('content'),
                'static_fields'       => json_encode($this->request->getPost('static_fields') ?? []),
                'dynamic_fields'      => $this->request->getPost('dynamic_fields'),
                'has_number'          => $this->request->getPost('has_number') ?? 0,
                'allow_custom_number' => $this->request->getPost('allow_custom_number') ?? 0,
                'fill_gaps'           => $this->request->getPost('fill_gaps') ?? 0,
                'active'              => $this->request->getPost('active') ?? 0,
            ]);

            return redirect()->to('/documents/templates')->with('success', 'Şablon başarıyla güncellendi.');
        }

        $data = [
            'title'      => 'Şablon Düzenle',
            'template'   => $template,
            'categories' => $this->categoryModel->getActiveCategories(),
            'institution' => $this->getInstitutionData()
        ];

        return view('documents/templates/edit', array_merge($this->data, $data));
    }
    
    // YENİ EKLENEN METOD
    public function delete($id)
    {
        if ($this->templateModel->delete($id)) {
            return redirect()->to('/documents/templates')->with('success', 'Şablon başarıyla silindi.');
        }
        
        return redirect()->to('/documents/templates')->with('error', 'Şablon silinirken bir hata oluştu.');
    }

    /**
     * Giriş yapan kullanıcının kurum bilgilerini getirir
     */
    private function getInstitutionData()
    {
        // Direkt ilk kurumu al
        $institution = $this->institutionModel->first();
        
        if (!$institution) {
            return (object)[
                'name'             => 'Kurum Tanımlı Değil',
                'short_name'       => 'Kısa Ad',
                'logo'             => null,
                'qr_code'          => null,
                'director'         => 'Müdür Tanımlı Değil',
                'founder_director' => 'Kurucu Müdür Tanımlı Değil',
                'address'          => 'Adres Tanımlı Değil',
                'phone'            => '',
                'landline'         => '',
                'email'            => '',
                'website'          => '',
                'evrak_prefix'     => '',
                'evrak_baslangic_no'         => ''
            ];
        }
        
        return (object)[
            'name'             => $institution->kurum_adi ?? 'Kurum Adı',
            'short_name'       => $institution->kurum_kisa_adi ?? '',
            'logo'             => $institution->kurum_logo_path ?? null,
            'qr_code'          => $institution->kurum_qr_kod_path ?? null,
            'director'         => $institution->kurum_muduru_adi ?? 'Müdür',
            'founder_director' => $institution->kurucu_mudur_adi ?? 'Kurucu Müdür',
            'address'          => $institution->adresi ?? 'Adres',
            'phone'            => $institution->telefon ?? '',
            'landline'         => $institution->sabit_telefon ?? '',
            'email'            => $institution->epostasi ?? '',
            'website'          => $institution->web_sayfasi ?? '',
            'evrak_prefix'     => $institution->evrak_prefix ?? '',
            'evrak_baslangic_no'         => $institution->evrak_baslangic_no ?? ''
        ];
    }
}