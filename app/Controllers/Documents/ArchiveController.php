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

        return view('documents/archive/index', array_merge($this->data ?? [], $data));
    }

   /**
     * Detaylı arama formunu gösterir (GET) veya arama sonuçlarını işler (POST).
     *
     * @return string View
     */
    public function search(): string
    {
        $user = auth()->user(); // Yetki kontrolü için kullanıcıyı al
        if (!$user) {
             return redirect()->to('login')->with('error', 'Arama yapmak için giriş yapmalısınız.');
        }

        // Başlangıç view verileri (GET isteği için)
        $viewData = [
            'title'      => 'Detaylı Belge Arama',
            'categories' => $this->categoryModel->getActiveCategories(),
            'results'    => [], // Sonuçları başlangıçta boş dizi olarak ayarla
            'searched'   => false, // Henüz arama yapılmadı
            'oldInput'   => [], // Form tekrar doldurma için
             // BaseController'dan gelen diğer değişkenler (varsa)
            // 'userProfile' => $this->userProfile ?? null,
        ];

        // Eğer form POST ile gönderildiyse
        if (strtolower($this->request->getMethod()) === 'post') {
            // CSRF kontrolü (CodeIgniter genellikle otomatik yapar ama emin olmak için)
            // if (! $this->validate([])) { // Sadece CSRF'yi tetiklemek için boş kural
            //     return redirect()->back()->withInput()->with('error', 'CSRF token hatası.');
            // }

            $searchParams = [
                'date_start'      => $this->request->getPost('date_start'),
                'date_end'        => $this->request->getPost('date_end'),
                'category_id'     => $this->request->getPost('category_id'),
                'subject'         => trim($this->request->getPost('subject') ?? ''), // Trim eklendi
                'document_number' => trim($this->request->getPost('document_number') ?? ''), // Trim eklendi
                'content_search'  => trim($this->request->getPost('content_search') ?? '') // Trim eklendi
            ];

            // Arama yap
            $results = $this->performSearch($searchParams);

            // View verilerini güncelle
            $viewData['results'] = $results;
            $viewData['searched'] = true; // Arama yapıldı olarak işaretle
            $viewData['oldInput'] = $searchParams; // Formu tekrar doldurmak için
        } else {
             // GET isteği ise, önceki form girdilerini (varsa) session'dan al
             // Not: CodeIgniter'ın old() fonksiyonu bunu zaten yapar, bu satırlar manuel alternatif.
             // $viewData['oldInput'] = session()->get('_ci_old_input')['post'] ?? [];
        }


        // BaseController'dan gelen $this->data ile birleştirme
        return view('documents/archive/search', array_merge($this->data ?? [], $viewData));
    }

    /**
     * Verilen arama parametrelerine göre veritabanından belgeleri getirir.
     *
     * @param array $params Arama kriterleri
     * @return array Sonuçlar dizisi
     */
    private function performSearch(array $params): array
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->inGroup('admin') || $user->inGroup('yonetici'));
        $userId = $user ? $user->id : null;

        // Sorgu oluşturucu
        $builder = $this->documentModel->select([
                'documents.*',
                'document_templates.name as template_name',
                'document_categories.name as category_name',
                'users.username as creator_name'
            ])
            ->join('document_templates', 'document_templates.id = documents.template_id', 'left') // Left join daha güvenli
            ->join('document_categories', 'document_categories.id = document_templates.category_id', 'left') // Left join
            ->join('users', 'users.id = documents.created_by', 'left'); // Left join

        // Yetki: Admin/Yönetici değilse sadece kendi belgeleri
        if (!$isAdmin && $userId) {
            $builder->where('documents.created_by', $userId);
        }

        // Arama Kriterleri
        if (!empty($params['date_start'])) {
            $builder->where('DATE(documents.created_at) >=', $params['date_start']); // Sadece tarih kısmı
        }
        if (!empty($params['date_end'])) {
            $builder->where('DATE(documents.created_at) <=', $params['date_end']); // Sadece tarih kısmı
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
        // İçerik arama (form_data JSON alanı içinde)
        // DİKKAT: Bu LIKE sorgusu JSON yapısına bağlıdır ve büyük veri setlerinde yavaş olabilir.
        // Daha performanslı arama için veritabanı JSON fonksiyonları (MySQL 5.7+ / PostgreSQL)
        // veya ayrı bir arama motoru (Elasticsearch vb.) düşünülebilir.
        if (!empty($params['content_search'])) {
             // Basit LIKE, anahtar veya değer içinde arar. Örn: "ahmet" araması {"name":"ahmet"} eşleşir.
             $builder->like('documents.form_data', $params['content_search']);
             // Sadece değerlerde aramak için (daha yavaş olabilir):
             // $builder->where("JSON_SEARCH(documents.form_data, 'one', '%" . $this->db->escapeLikeString($params['content_search']) . "%') IS NOT NULL");
        }

        // Sonuçları al ve döndür
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