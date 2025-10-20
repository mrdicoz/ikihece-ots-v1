<?php

namespace App\Controllers\Documents;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\DocumentTemplateModel;
use App\Models\DocumentCategoryModel;
use App\Models\DocumentNumberingModel;
use App\Models\InstitutionModel;
use App\Libraries\DocumentRenderer; // FAZ 7 için eklendi

class DocumentController extends BaseController
{
    protected $documentModel;
    protected $templateModel;
    protected $categoryModel;
    protected $numberingModel;
    protected $institutionModel;

    public function __construct()
    {
        $this->documentModel    = new DocumentModel();
        $this->templateModel    = new DocumentTemplateModel();
        $this->categoryModel    = new DocumentCategoryModel();
        $this->numberingModel   = new DocumentNumberingModel();
        $this->institutionModel = new InstitutionModel();

        // Yetkilendirme
        if (!auth()->user()->inGroup('admin', 'yonetici', 'mudur', 'sekreter')) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }
    }

    /**
     * Kategori ve Şablon Seçim Sayfası
     */
    public function index()
    {
        $data = [
            'title'      => 'Yeni Belge Oluştur: Şablon Seçimi',
            'categories' => $this->categoryModel->getActiveCategories(),
        ];

        return view('documents/create/index', array_merge($this->data, $data));
    }

    /**
     * Belge oluşturma formunu gösterir
     */
    public function form($templateId = null)
    {
        // getTemplateWithCategory metodu, şablona kategori adını da ekler.
        $template = $this->templateModel->getTemplateWithCategory($templateId);
        
        if (!$template) {
            return redirect()->to(base_url('documents/create'))->with('error', 'Lütfen geçerli bir şablon seçin.');
        }

        // Alanları PHP tarafında kullanılabilir hale getir
        $template->dynamic_fields = json_decode($template->dynamic_fields, true);
        
        // Numaralı belge ise, sıradaki numarayı öner
        if ($template->has_number) {
            $template->suggested_number = $this->numberingModel->suggestNextNumber(
                $templateId, (bool)$template->fill_gaps
            );
        }

        $data = [
            'title'      => 'Belge Oluştur: ' . $template->name,
            'template'   => $template,
            'settings'   => $this->institutionModel->first()
        ];
        
        return view('documents/create/form', array_merge($this->data, $data));
    }

    /**
     * AJAX: Seçilen kategoriye göre şablonları listeler.
     */
    public function getTemplates()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }
        $categoryId = $this->request->getGet('category_id');
        $templates  = $this->templateModel->getTemplatesByCategory($categoryId);
        return $this->response->setJSON($templates);
    }
    
    /**
     * Belgeyi kaydeder.
     */
/**
     * Belgeyi kaydeder.
     */
    public function store()
    {
        $templateId = $this->request->getPost('template_id');
        $template  = $this->templateModel->find($templateId);

        if (!$template) {
            return redirect()->back()->with('error', 'Şablon bulunamadı.');
        }

        // Form verilerini al
        $formData = $this->request->getPost('fields');
        $documentNumber  = $this->request->getPost('document_number');
        $subject  = $this->request->getPost('subject');

        // Evrak numarası kontrolü
        if ($template->has_number) {
            if ($this->numberingModel->isNumberUsed($documentNumber)) {
                return redirect()->back()->withInput()->with('error', 'Bu evrak numarası zaten kullanılmış!');
            }
        }

        // HTML'i render et (YENİ: Konu ve Evrak Numarası da gönderiliyor)
        $renderedHtml = $this->renderDocument($template, $formData, $subject, $documentNumber);

        // Veritabanına kaydet
        $this->documentModel->insert([
            'template_id' => $templateId,
            'document_number' => $template->has_number ? $documentNumber : null,
            'subject' => $subject,
            'form_data' => json_encode($formData),
            'rendered_html' => $renderedHtml,
            'created_by' => auth()->id()
        ]);

        return redirect()->to('/documents/archive')->with('success', 'Belge başarıyla oluşturuldu.');
    }

    /**
     * Nihai HTML'i oluşturur.
     */
   /**
     * Şablonu render eder.
     */
    private function renderDocument($template, $formData, $subject, $documentNumber)
    {
        $html  = $template->content;
        $settings = $this->institutionModel->first(); // FAZ 4 raporuna göre bu model kullanılıyor

        // YENİ: Konu ve Sayı alanlarını değiştir
        $html = str_replace('{KONU}', esc($subject), $html);
        // Not: Şablondaki [EVRAK_PREFIX][EVRAK_BASLANGIC_NO] yerine tam numarayı basıyoruz
        $html = str_replace('[EVRAK_PREFIX][EVRAK_BASLANGIC_NO]', esc($documentNumber), $html);

        // Sabit alanları değiştir
        $staticFields = json_decode($template->static_fields, true) ?? [];
        foreach ($staticFields as $field) {
            switch ($field) {
                case 'KURUM_ADI':
                    $html = str_replace('[KURUM_ADI]', $settings->name ?? '', $html);
                    break;
                case 'LOGO':
                    $logoPath  = base_url($settings->logo_path ?? '');
                    $html  = str_replace('[LOGO]', "<img src='{$logoPath}' style='max-width: 150px;'>", $html);
                    break;
                case 'MUDUR':
                    $html = str_replace('[MUDUR]', $settings->kurum_muduru_adi ?? 'Müdür Adı', $html);
                    break;
                case 'ADRES':
                    $html = str_replace('[ADRES]', $settings->address ?? '', $html);
                    break;
            }
        }

        // Dinamik alanları değiştir
        if (!empty($formData)) {
            foreach ($formData as $key => $value) {
                $html  = str_replace('{' . $key . '}', esc($value), $html);
            }
        }
        
        return $html;
    }
    /**
     * Belgeyi PDF olarak tarayıcıda görüntüler.
     *
     * @param int $id Görüntülenecek belgenin ID'si.
     * @return mixed
     */
        public function viewPDF($id)
    {
        $document = $this->documentModel->find($id);
        if (!$document) {
            return redirect()->back()->with('error', 'Belge bulunamadı.');
        }

        $isAdmin = auth()->user()->inGroup('admin') || auth()->user()->inGroup('yonetici');
        if (!$isAdmin && $document->created_by != auth()->id()) {
            return redirect()->back()->with('error', 'Bu belgeyi görüntüleme yetkiniz yok.');
        }

        $renderer = new \App\Libraries\DocumentRenderer();

        // 1. PDF verisini doğrudan ekrana basmak yerine bir değişkene alıyoruz ('S' parametresi ile).
        $pdfData = $renderer->generatePDF($document->rendered_html, 'document.pdf', 'S');

        // 2. CodeIgniter'ın response nesnesini kullanarak doğru başlıkları ayarlıyoruz.
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($pdfData);
    }

    /**
     * Belgeyi PDF olarak indirir.
     *
     * @param int $id İndirilecek belgenin ID'si.
     * @return mixed
     */
    public function downloadPDF($id)
    {
        $document = $this->documentModel->find($id);
        if (!$document) {
            return redirect()->back()->with('error', 'Belge bulunamadı.');
        }

        // Yetki kontrolü (admin/yonetici hariç sadece kendi belgesini indirebilir)
        $isAdmin = auth()->user()->inGroup('admin') || auth()->user()->inGroup('yonetici');
        if (!$isAdmin && $document->created_by != auth()->id()) {
            return redirect()->back()->with('error', 'Bu belgeyi indirme yetkiniz yok.');
        }

        $renderer = new \App\Libraries\DocumentRenderer();
        $filename = ($document->document_number ?? 'belge_' . $id) . '.pdf';
        
        // Çıktı türü 'D' (Download) olarak ayarlandı.
        return $renderer->generatePDF($document->rendered_html, $filename, 'D');
    }
}