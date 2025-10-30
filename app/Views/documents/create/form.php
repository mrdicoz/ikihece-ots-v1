<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<form id="documentForm" method="post" action="<?= base_url('documents/store') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="template_id" value="<?= $template->id ?>">
    <textarea id="rawTemplateContent" style="display: none;"><?= esc($template->content) ?></textarea>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= esc($template->name) ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3 pb-3 border-bottom">
                            <?php if($template->has_number): ?>
                                <div class="col-md-6">
                                    <label for="document_number" class="form-label">Evrak Numarası</label>
                                    <input type="text" name="document_number" id="document_number" class="form-control dynamic-field" placeholder="<?= esc($template->suggested_number ?? 'Otomatik') ?>">
                                    <div class="form-text">Boş bırakırsanız otomatik olarak atanacaktır.</div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-3">
                                <label for="document_date" class="form-label">Belge Tarihi</label>
                                <input type="date" name="document_date" id="document_date" class="form-control dynamic-field" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Kategori</label>
                                <input type="text" class="form-control" value="<?= esc($template->category_name) ?>" readonly>
                            </div>
                        </div>

                        <div class="border-top pt-3 mb-3">
                            <h6 class="text-success mb-3"><i class="bi bi-fonts me-2"></i>Genel Bilgiler</h6>
                            <div class="mb-3">
                                <label class="form-label">Konu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control dynamic-field" name="subject" data-key="KONU" placeholder="Belgenin konusu" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alıcı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control dynamic-field" name="recipient" data-key="ALICI" placeholder="Serdivan İlçe Milli Eğitim Müdürlüğü'ne" required>
                            </div>
                        </div>

                        <?php if(!empty($template->dynamic_fields)): ?>
                            <div class="border-top pt-3 mb-3">
                                <h6 class="text-success mb-3"><i class="bi bi-file-text me-2"></i>İçerik Bilgileri</h6>
                                <?php foreach($template->dynamic_fields as $field): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?= esc($field['label']) ?> <?= $field['required'] ? '<span class="text-danger">*</span>' : '' ?></label>
                                        <?php
                                            $attributes = [
                                                'name' => 'fields['.$field['name'].']',
                                                'class' => 'form-control dynamic-field',
                                                'data-key' => $field['name'],
                                                'required' => $field['required']
                                            ];
                                        ?>
                                        <?php if($field['type'] == 'textarea'): ?>
                                            <?= form_textarea($attributes) ?>
                                        <?php elseif($field['type'] == 'select'): ?>
                                            <?= form_dropdown($attributes['name'], array_combine($field['options'], $field['options']), '', ['class' => 'form-select dynamic-field', 'data-key' => $field['name'], 'required' => $field['required']]) ?>
                                        <?php else: ?>
                                            <?php $attributes['type'] = $field['type']; ?>
                                            <?= form_input($attributes) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="border-top pt-3 mb-3">
                            <h6 class="text-success mb-3">
                                <i class="bi bi-paperclip me-2"></i>Ekler
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="addAttachment"><i class="bi bi-plus-circle"></i></button>
                            </h6>
                            <div id="attachmentsContainer"></div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check-circle me-2"></i>Belgeyi Oluştur ve Arşivle</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="preview-container">
                    <div class="preview-toolbar">
                        <div class="zoom-controls">
                            <button type="button" id="zoomOut" title="Uzaklaştır"><i class="bi bi-dash-circle"></i></button>
                            <button type="button" id="zoomReset" title="Sıfırla">%<span id="zoomLevel">50</span></button>
                            <button type="button" id="zoomIn" title="Yakınlaştır"><i class="bi bi-plus-circle"></i></button>
                        </div>
                        <div class="page-controls">
                            <button type="button" id="prevPage" title="Önceki Sayfa" disabled><i class="bi bi-chevron-left"></i></button>
                            <span id="pageInfo" class="mx-2">Sayfa: <span id="currentPage">1</span> / <span id="totalPages">1</span></span>
                            <button type="button" id="nextPage" title="Sonraki Sayfa"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <div class="view-mode-controls">
                            <button type="button" id="dragModeBtn" class="btn btn-sm btn-outline-light active" title="Sürükleme Modu">
                                <i class="bi bi-hand-index"></i>
                            </button>
                            <span class="badge bg-success ms-2">Canlı Önizleme</span>
                        </div>
                    </div>
                    <div class="preview-viewport" id="previewViewport">
                        <div class="preview-canvas" id="previewCanvas">
                            <div id="preview-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
    /* Ana Container Stilleri */
    .preview-container {
        background: #2c3e50;
        border-radius: 8px;
        max-height: 75vh;
        overflow: hidden;
        position: sticky;
        top: 20px;
        display: flex;
        flex-direction: column;
    }
    
    .preview-toolbar {
        background: #34495e;
        padding: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        user-select: none;
    }
    
    .preview-viewport {
        flex: 1;
        overflow: auto;
        position: relative;
        background: #e9ecf0;
        min-height: 500px;
        scroll-behavior: smooth;
    }
    
    /* Sürükleme modu için cursor */
    .preview-viewport.drag-mode {
        cursor: grab;
    }
    
    .preview-viewport.drag-mode.dragging {
        cursor: grabbing;
        scroll-behavior: auto !important;
        user-select: none;
    }
    
    .preview-canvas {
        padding: 20px;
        min-height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    #preview-content {
        display: flex;
        flex-direction: column;
        gap: 30px;
        transform-origin: center top;
        transition: transform 0.2s ease;
    }
    
    /* A4 Sayfa Stilleri */
    .a4-paper {
        width: 21cm;
        height: 29.7cm !important;
        min-height: 29.7cm;
        max-height: 29.7cm;
        background: white;
        box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        margin: 0 auto;
        padding: 25mm 15mm 15mm 15mm;
        overflow: hidden;
        position: relative;
        box-sizing: border-box;
        border: 1px solid #ddd;
        font-family: 'Times New Roman', serif;
        font-size: 11pt;
        line-height: 1.2;
    }
    
    .a4-paper.active-page {
        box-shadow: 0 8px 20px rgba(133, 133, 133, 0.4);
        border: 2px solid #b8b8b8ff;
    }
    
    /* Toolbar Kontrolleri */
    .zoom-controls button,
    .page-controls button,
    .view-mode-controls button {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.3s;
    }
    
    .zoom-controls button:hover:not(:disabled),
    .page-controls button:hover:not(:disabled),
    .view-mode-controls button:hover {
        background: rgba(255,255,255,0.1);
        border-color: rgba(255,255,255,0.5);
        transform: scale(1.05);
    }
    
    .zoom-controls button:disabled,
    .page-controls button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .view-mode-controls button.active {
        background: #28a745;
        border-color: #28a745;
    }
    
    #pageInfo {
        color: white;
        font-size: 14px;
        user-select: none;
    }
    
    /* Scroll davranışları */
    .preview-viewport::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    .preview-viewport::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.1);
    }
    
    .preview-viewport::-webkit-scrollbar-thumb {
        background: #28a745;
        border-radius: 5px;
    }
    
    .preview-viewport::-webkit-scrollbar-thumb:hover {
        background: #218838;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .preview-container {
            position: relative;
            top: 0;
            max-height: 60vh;
        }
    }
    
    /* İçerik stilleri */
    .a4-paper h1, .a4-paper h2, .a4-paper h3 {
        color: #2c3e50;
        margin-top: 0px;
        margin-bottom: 0px;
    }
    
    .a4-paper table {
        width: 100%;
        border-collapse: collapse;
        margin: 0px 0;
    }
    
    .a4-paper table td, .a4-paper table th {
        border: 0px solid #ddd;
        padding: 0px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === DEĞİŞKENLER ===
    const previewViewport = document.getElementById('previewViewport');
    const previewCanvas = document.getElementById('previewCanvas');
    const previewContent = document.getElementById('preview-content');
    const rawTemplateContent = document.getElementById('rawTemplateContent').value;
    const settings = <?= json_encode($settings ?? []) ?>;
    
    let currentZoom = 0.5;
    let currentPageIndex = 0;
    let totalPageCount = 1;
    let attachmentCount = 0;
    let updateTimer = null;
    
    // Drag scroll değişkenleri
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let scrollLeft = 0;
    let scrollTop = 0;

    // === ZOOM FONKSİYONLARI ===
    function setZoom(value) {
        currentZoom = Math.max(0.25, Math.min(2, value));
        previewContent.style.transform = `scale(${currentZoom})`;
        document.getElementById('zoomLevel').textContent = Math.round(currentZoom * 100);
        
        // Zoom değiştiğinde aktif sayfayı göster
        setTimeout(() => {
            const activePage = document.querySelector('.a4-paper.active-page');
            if (activePage) {
                activePage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    }

    // === DRAG SCROLL (EL İKONU İLE GEZME) ===
    function enableDragScroll() {
        previewViewport.classList.add('drag-mode');
        
        previewViewport.addEventListener('mousedown', startDragging);
        previewViewport.addEventListener('mouseleave', stopDragging);
        previewViewport.addEventListener('mouseup', stopDragging);
        previewViewport.addEventListener('mousemove', drag);
    }

    function disableDragScroll() {
        previewViewport.classList.remove('drag-mode');
        
        previewViewport.removeEventListener('mousedown', startDragging);
        previewViewport.removeEventListener('mouseleave', stopDragging);
        previewViewport.removeEventListener('mouseup', stopDragging);
        previewViewport.removeEventListener('mousemove', drag);
    }

    function startDragging(e) {
        isDragging = true;
        previewViewport.classList.add('dragging');
        
        startX = e.pageX - previewViewport.offsetLeft;
        startY = e.pageY - previewViewport.offsetTop;
        scrollLeft = previewViewport.scrollLeft;
        scrollTop = previewViewport.scrollTop;
    }

    function stopDragging() {
        isDragging = false;
        previewViewport.classList.remove('dragging');
    }

    function drag(e) {
        if (!isDragging) return;
        
        e.preventDefault();
        
        const x = e.pageX - previewViewport.offsetLeft;
        const y = e.pageY - previewViewport.offsetTop;
        const walkX = (x - startX) * 1.5; // Hız çarpanı
        const walkY = (y - startY) * 1.5;
        
        previewViewport.scrollLeft = scrollLeft - walkX;
        previewViewport.scrollTop = scrollTop - walkY;
    }

    // === SAYFA NAVİGASYONU ===
    function goToPage(pageIndex) {
        const pages = document.querySelectorAll('.a4-paper');
        if (pages.length === 0) return;
        
        // Önceki aktif sayfayı temizle
        document.querySelectorAll('.a4-paper').forEach(p => p.classList.remove('active-page'));
        
        currentPageIndex = Math.max(0, Math.min(pageIndex, pages.length - 1));
        const targetPage = pages[currentPageIndex];
        
        if (targetPage) {
            targetPage.classList.add('active-page');
            targetPage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        updatePageInfo();
    }

    function updatePageInfo() {
        const pages = document.querySelectorAll('.a4-paper');
        totalPageCount = pages.length || 1;
        
        document.getElementById('currentPage').textContent = currentPageIndex + 1;
        document.getElementById('totalPages').textContent = totalPageCount;
        
        // Buton durumlarını güncelle
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        
        if (prevBtn) prevBtn.disabled = currentPageIndex === 0;
        if (nextBtn) nextBtn.disabled = currentPageIndex >= totalPageCount - 1;
    }

    // === ÖNİZLEME GÜNCELLEME ===
    function updatePreview() {
        // Debounce için timer kullan
        clearTimeout(updateTimer);
        updateTimer = setTimeout(() => {
            performUpdate();
        }, 300);
    }

    function performUpdate() {
        let content = rawTemplateContent;

        const dateInput = document.querySelector('input[name="document_date"]');
        const numberInput = document.querySelector('input[name="document_number"]');

        let formattedDate = '';
        if (dateInput && dateInput.value) {
            formattedDate = new Date(dateInput.value).toLocaleDateString('tr-TR');
        }

        let documentNumber = '';
        if (numberInput) {
            documentNumber = numberInput.value || numberInput.placeholder;
        }

        // Statik değişkenleri değiştir
        const staticReplacements = {
            '[KURUM_ADI]': settings?.kurum_adi || '',
            '[KURUM_KISA_ADI]': settings?.kurum_kisa_adi || '',
            '[MUDUR]': settings?.kurum_muduru_adi || '',
            '[KURUCU_MUDUR]': settings?.kurucu_mudur_adi || '',
            '[ADRES]': settings?.adresi || '',
            '[TELEFON]': settings?.telefon || '',
            '[SABIT_TELEFON]': settings?.sabit_telefon || '',
            '[EPOSTA]': settings?.epostasi || '',
            '[WEB_SAYFA]': settings?.web_sayfasi || '',
            '[TARIH]': formattedDate,
            '[EVRAK_NO]': documentNumber,
            '[EVRAK_PREFIX]': settings?.evrak_prefix || '',
            '[EVRAK_BASLANGIC_NO]': settings?.evrak_baslangic_no || '',
            '[KONU]': document.querySelector('input[name="subject"]')?.value || '',
            '[ALICI]': document.querySelector('input[name="recipient"]')?.value || '',
            '[LOGO]': settings?.kurum_logo_path ? `<img src="<?= base_url() ?>${settings.kurum_logo_path}" style="max-width:150px">` : '',
            '[QR_KOD]': settings?.kurum_qr_kod_path ? `<img src="<?= base_url() ?>${settings.kurum_qr_kod_path}" style="max-width:80px">` : ''
        };

        for (const [key, value] of Object.entries(staticReplacements)) {
            content = content.replaceAll(key, value);
        }

        // Dinamik alanları değiştir
        document.querySelectorAll('.dynamic-field').forEach(input => {
            if (input.dataset.key) {
                const key = input.dataset.key.toUpperCase();
                const value = input.value.replace(/\n/g, '<br>');
                content = content.replaceAll(`[${key}]`, value);
                content = content.replaceAll(`{${key}}`, value);
            }
        });

        // Ekleri işle
        let attachmentHtml = '';
        const attachments = document.querySelectorAll('.attachment-input');
        if (attachments.length > 0) {
            let items = [];
            attachments.forEach(input => {
                if (input.value.trim()) {
                    items.push(`<li>${input.value.trim().toUpperCase()}</li>`);
                }
            });
            if (items.length > 0) {
                attachmentHtml = `<ol>${items.join('')}</ol>`;
            }
        }
        content = content.replace('{EKLER}', attachmentHtml || '<li>-</li>');

        // SAYFALARA BÖL - Optimize edilmiş
        renderPages(content);
    }

    function renderPages(content) {
        // Geçici content oluştur
        previewContent.innerHTML = `<div class="temp-content" style="width: 21cm; visibility: hidden;">${content}</div>`;
        
        setTimeout(() => {
            const tempContent = document.querySelector('.temp-content');
            if (!tempContent) return;
            
            const totalHeight = tempContent.scrollHeight;
            const pageHeight = 29.7 * 37.795; // A4 yüksekliği px cinsinden
            const pageCount = Math.max(1, Math.ceil(totalHeight / pageHeight));
            
            let pagesHtml = '';
            for (let i = 0; i < pageCount; i++) {
                const activeClass = i === 0 ? 'active-page' : '';
                pagesHtml += `
                    <div class="a4-paper ${activeClass}" data-page="${i}">
                        <div style="position: absolute; top: ${-i * pageHeight}px; left: 2cm; right: 2cm;">
                            ${content}
                        </div>
                    </div>
                `;
            }
            
            previewContent.innerHTML = pagesHtml;
            currentPageIndex = 0;
            updatePageInfo();
        }, 50);
    }

    // === EVENT LISTENERS ===
    
    // Zoom kontrolleri
    document.getElementById('zoomOut').addEventListener('click', (e) => {
        e.preventDefault();
        setZoom(currentZoom - 0.1);
    });

    document.getElementById('zoomReset').addEventListener('click', (e) => {
        e.preventDefault();
        setZoom(0.5);
    });

    document.getElementById('zoomIn').addEventListener('click', (e) => {
        e.preventDefault();
        setZoom(currentZoom + 0.1);
    });

    // Sayfa geçiş butonları
    document.getElementById('prevPage').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (currentPageIndex > 0) {
            goToPage(currentPageIndex - 1);
        }
    });

    document.getElementById('nextPage').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (currentPageIndex < totalPageCount - 1) {
            goToPage(currentPageIndex + 1);
        }
    });

    // Drag mode toggle
    const dragModeBtn = document.getElementById('dragModeBtn');
    if (dragModeBtn) {
        dragModeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            dragModeBtn.classList.toggle('active');
            
            if (dragModeBtn.classList.contains('active')) {
                enableDragScroll();
            } else {
                disableDragScroll();
            }
        });
    }

    // Klavye kısayolları
    document.addEventListener('keydown', (e) => {
        // Form inputlarında klavye kısayollarını devre dışı bırak
        if (e.target.matches('input, textarea, select')) return;
        
        switch(e.key) {
            case 'ArrowLeft':
                if (currentPageIndex > 0) {
                    e.preventDefault();
                    goToPage(currentPageIndex - 1);
                }
                break;
            case 'ArrowRight':
                if (currentPageIndex < totalPageCount - 1) {
                    e.preventDefault();
                    goToPage(currentPageIndex + 1);
                }
                break;
            case '+':
            case '=':
                e.preventDefault();
                setZoom(currentZoom + 0.1);
                break;
            case '-':
                e.preventDefault();
                setZoom(currentZoom - 0.1);
                break;
            case '0':
                e.preventDefault();
                setZoom(1);
                break;
        }
    });

    // Mouse tekerleği ile zoom (Ctrl basılıyken)
    previewViewport.addEventListener('wheel', (e) => {
        if (e.ctrlKey) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            setZoom(currentZoom + delta);
        }
    });

    // Ek Ekle
    document.getElementById('addAttachment').addEventListener('click', function(e) {
        e.preventDefault();
        attachmentCount++;
        const container = document.getElementById('attachmentsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'input-group mb-2';
        newRow.innerHTML = `
            <span class="input-group-text">${attachmentCount}.</span>
            <input type="text" name="attachments[]" class="form-control attachment-input" placeholder="Dilekçe (1 Adet)">
            <button class="btn btn-outline-danger remove-attachment" type="button"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(newRow);
        
        // Event listener'ları ekle
        const inputField = newRow.querySelector('.attachment-input');
        const removeBtn = newRow.querySelector('.remove-attachment');
        
        inputField.addEventListener('input', updatePreview);
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            newRow.remove();
            updatePreview();
        });
        
        inputField.focus();
    });

    // Form alanları için event listener'lar
    document.querySelectorAll('.dynamic-field, .attachment-input').forEach(input => {
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });

    // Form submit kontrolü
    document.getElementById('documentForm').addEventListener('submit', function(e) {
        // Form submit edilmeden önce son kontroller
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Lütfen zorunlu alanları doldurunuz!');
        }
    });

    // === İLK YÜKLEME ===
    updatePreview();
    setZoom(0.5);
    enableDragScroll(); // Varsayılan olarak drag scroll aktif
});
</script>
<?= $this->endSection() ?>