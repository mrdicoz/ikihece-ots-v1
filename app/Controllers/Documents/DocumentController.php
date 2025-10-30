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
        $documentDate   = $this->request->getPost('document_date'); 
        $subject  = $this->request->getPost('subject');
        $recipient = $this->request->getPost('recipient');
        $attachments = $this->request->getPost('attachments');

        // Tarih girilmemişse, bugünün tarihini kullan
        $documentDate = !empty($documentDate) ? $documentDate : date('Y-m-d'); // <-- 2. YENİ: Boş tarih kontrolü.

        // Evrak numarası kontrolü
        if ($template->has_number) {
            // Eğer kullanıcı numara girmemişse, önerilen numarayı biz atayalım.
            if (empty($documentNumber)) {
                $documentNumber = $this->numberingModel->suggestNextNumber($templateId, (bool)$template->fill_gaps);
            } 
            // Kullanıcı bir numara girdiyse, kullanılıp kullanılmadığını kontrol edelim.
            elseif ($this->numberingModel->isNumberUsed($documentNumber, $templateId)) { // templateId ile kontrolü netleştirelim.
                return redirect()->back()->withInput()->with('error', 'Bu evrak numarası zaten kullanılmış!');
            }
        }

        // HTML'i render et (YENİ: Konu ve Evrak Numarası da gönderiliyor)
            $renderedHtml = $this->renderDocument($template, $formData, $subject, $recipient, $documentNumber, $attachments, $documentDate);

        // Veritabanına kaydet
        $this->documentModel->insert([
            'template_id' => $templateId,
            'document_number' => $template->has_number ? $documentNumber : null,
            'document_date'   => $documentDate, // <-- 4. YENİ: Tarihi veritabanına ekliyoruz.
            'subject' => $subject,
            'recipient' => $recipient,
            'attachments' => json_encode($attachments),
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
     * Belge içeriğini render eder.
     */
    private function renderDocument($template, $formData, $subject, $recipient, $documentNumber, $attachments, $documentDate)
    {
        $content = $template->content;
        $settings = $this->institutionModel->first();

        // Tarihi formatla ve [TARIH] etiketiyle değiştir
        $formattedDate = date('d.m.Y', strtotime($documentDate));
        
        // --- BURADAKİ TÜM replaceAll'LARI str_replace YAPACAĞIZ ---
        $content = str_replace('[TARIH]', $formattedDate, $content);
        $content = str_replace('[KONU]', $subject, $content);
        $content = str_replace('[ALICI]', $recipient, $content);
        $content = str_replace('[EVRAK_NO]', $documentNumber, $content);
        
        // Kurum bilgileri
        $content = str_replace('[KURUM_ADI]', $settings->kurum_adi, $content);
        $content = str_replace('[KURUM_KISA_ADI]', $settings->kurum_kisa_adi, $content);
        $content = str_replace('[MUDUR]', $settings->kurum_muduru_adi, $content);
        $content = str_replace('[KURUCU_MUDUR]', $settings->kurucu_mudur_adi, $content);
        $content = str_replace('[ADRES]', $settings->adresi, $content);
        $content = str_replace('[TELEFON]', $settings->telefon, $content);
        $content = str_replace('[SABIT_TELEFON]', $settings->sabit_telefon, $content);
        $content = str_replace('[EPOSTA]', $settings->epostasi, $content);
        $content = str_replace('[WEB_SAYFA]', $settings->web_sayfasi, $content);
        
// LOGO İŞLEMLERİ
        $logoHtml = ''; // Başlangıçta boş
        $logoDbPath = $settings->kurum_logo_path; // Veritabanından gelen yolu al (örn: uploads/institution/logo.png)
        if (!empty($logoDbPath)) {
            // Fiziksel yolu oluştur (örn: C:/.../public/uploads/institution/logo.png)
            $logoPhysicalPath = FCPATH . $logoDbPath; 
            // Dosyanın varlığını kontrol et
            if (is_file($logoPhysicalPath)) {
                $logoHtml = '<img src="' . $logoPhysicalPath . '" style="max-width:150px">';
            }
        }
        $content = str_replace('[LOGO]', $logoHtml, $content);

        // QR KOD İŞLEMLERİ (Logodan tamamen ayrı)
        $qrHtml = ''; // Başlangıçta boş
        $qrDbPath = $settings->kurum_qr_kod_path; // Veritabanından gelen yolu al (örn: uploads/qr_codes/qr.png)
        if (!empty($qrDbPath)) {
            // Fiziksel yolu oluştur (örn: C:/.../public/uploads/qr_codes/qr.png)
            $qrPhysicalPath = FCPATH . $qrDbPath;
             // Dosyanın varlığını kontrol et
            if (is_file($qrPhysicalPath)) {
                $qrHtml = '<img src="' . $qrPhysicalPath . '" style="max-width:80px">';
            }
        }
        $content = str_replace('[QR_KOD]', $qrHtml, $content);

        // Dinamik alanlar
        if (!empty($formData)) {
            foreach ($formData as $key => $value) {
                // Hem [KEY] hem de {KEY} formatını destekleyelim
                $content = str_replace('{' . strtoupper($key) . '}', nl2br($value), $content);
                $content = str_replace('[' . strtoupper($key) . ']', nl2br($value), $content);
            }
        }

        // Ekler
        $attachmentHtml = '';
        if (!empty($attachments) && !empty(array_filter($attachments))) {
            $attachmentHtml = '<ol>';
            foreach (array_filter($attachments) as $attachment) {
                $attachmentHtml .= '<li>' . esc(strtoupper($attachment)) . '</li>';
            }
            $attachmentHtml .= '</ol>';
        } else {
            $attachmentHtml = '<li>-</li>';
        }
        $content = str_replace('{EKLER}', $attachmentHtml, $content);

        return $content;
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