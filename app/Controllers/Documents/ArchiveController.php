<?php

namespace App\Controllers\Documents;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\DocumentCategoryModel;

class ArchiveController extends BaseController
{
    protected $documentModel;
    protected $categoryModel;

    public function __construct()
    {
        helper('auth'); // Hata düzeltmesi burada

        $this->documentModel  = new DocumentModel();
        $this->categoryModel  = new DocumentCategoryModel();
    }

    private function isAdmin(): bool
    {
        $user = auth()->user();
        return $user && ($user->inGroup('admin') || $user->inGroup('yonetici'));
    }

    /**
     * Arşiv listesini gösterir.
     */
    public function index()
    {
        $isAdmin = auth()->user()->inGroup('admin') || auth()->user()->inGroup('yonetici');
        $userId  = auth()->id();

        $documents = $this->documentModel->getDocumentsWithDetails($userId, $isAdmin);

        $data = [
            'title' => 'Belge Arşivi',
            'documents' => $documents,
            'isAdmin' => $isAdmin
        ];

        return view('documents/archive/index', array_merge($this->data, $data));
    }

    /**
     * Detaylı arama sayfasını gösterir ve arama işlemini yapar.
     */
    public function search()
    {
        $data = [
            'title' => 'Detaylı Belge Arama',
            'categories' => $this->categoryModel->getActiveCategories(),
            'searched' => false // Arama yapılıp yapılmadığını kontrol eder
        ];

        // Eğer form gönderilmişse (POST isteği ise)
        if ($this->request->getMethod() === 'post') {
            $searchParams = [
                'date_start' => $this->request->getPost('date_start'),
                'date_end' => $this->request->getPost('date_end'),
                'category_id' => $this->request->getPost('category_id'),
                'subject' => $this->request->getPost('subject'),
                'document_number' => $this->request->getPost('document_number'),
                'content_search' => $this->request->getPost('content_search')
            ];
            
            $results = $this->performSearch($searchParams);
            $data['results'] = $results;
            $data['searched'] = true; // Arama yapıldığını belirt
        }

        return view('documents/archive/search', array_merge($this->data, $data));
    }

    /**
     * Verilen parametrelere göre veritabanında arama yapar.
     */
    private function performSearch($params)
    {
        $isAdmin = auth()->user()->inGroup('admin') || auth()->user()->inGroup('yonetici');
        $userId = auth()->id();

        $builder = $this->documentModel
            ->select('
                documents.*,
                document_templates.name as template_name,
                document_categories.name as category_name,
                users.username as creator_name
            ')
            ->join('document_templates', 'document_templates.id = documents.template_id')
            ->join('document_categories', 'document_categories.id = document_templates.category_id')
            ->join('users', 'users.id = documents.created_by');

        if (!$isAdmin) {
            $builder->where('documents.created_by', $userId);
        }

        if (!empty($params['date_start'])) {
            $builder->where('documents.created_at >=', $params['date_start']);
        }
        if (!empty($params['date_end'])) {
            $builder->where('documents.created_at <=', $params['date_end'] . ' 23:59:59');
        }
        if (!empty($params['category_id'])) {
            $builder->where('document_templates.category_id', $params['category_id']);
        }
        if (!empty($params['subject'])) {
            $builder->like('documents.subject', $params['subject']);
        }
        if (!empty($params['document_number'])) {
            $builder->like('documents.document_number', $params['document_number']);
        }
        if (!empty($params['content_search'])) {
            $builder->like('documents.form_data', $params['content_search']);
        }

        return $builder->orderBy('documents.created_at', 'DESC')->findAll();
    }

    /**
     * Belge silme işlemini gerçekleştirir. (Sadece Admin/Yönetici)
     */
    public function delete($id)
    {
        if (!(auth()->user()->inGroup('admin') || auth()->user()->inGroup('yonetici'))) {
            return redirect()->back()->with('error', 'Bu işlem için yetkiniz yok.');
        }

        $this->documentModel->delete($id);
        return redirect()->back()->with('success', 'Belge başarıyla silindi.');
    }
}