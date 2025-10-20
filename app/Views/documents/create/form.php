<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .preview-container { 
        background: #dee2e6; 
        padding: 20px; 
        border-radius: 8px; 
        position: sticky; 
        top: 80px; 
        max-height: calc(100vh - 100px); 
        overflow-y: auto; 
    }
    .a4-paper { 
        width: 21cm; 
        min-height: 29.7cm; 
        background: white; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.15); 
        margin: 0 auto; 
        padding: 2cm 2.5cm; 
        font-size: 12pt; 
        font-family: 'Times New Roman', serif; 
        color: #000;
        line-height: 1.6;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
<form id="documentForm" method="post" action="<?= base_url('documents/store') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="template_id" value="<?= $template->id ?>">
    <textarea id="rawTemplateContent" style="display: none;"><?= esc($template->content) ?></textarea>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= esc($template->name) ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3 pb-3 border-bottom">
                            <?php if($template->has_number): ?>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Evrak Numarası</label>
                                    <input type="text" class="form-control" name="document_number" id="docNumber" value="<?= $template->suggested_number ?>" <?= !$template->allow_custom_number ? 'readonly' : '' ?>>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tarih</label>
                                <input type="text" class="form-control" value="<?= date('d.m.Y') ?>" readonly>
                            </div>
                            <div class="col-md-4">
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

            <div class="col-lg-5 mb-4">
                <div class="preview-container">
                    <div class="text-center mb-3"><span class="badge bg-success">Canlı Önizleme</span></div>
                    <div class="a4-paper" id="preview-content"></div>
                </div>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rawTemplateContent = document.getElementById('rawTemplateContent').value;
    const settings = <?= json_encode($settings ?? null, JSON_UNESCAPED_UNICODE) ?>;
    let attachmentCount = 0;

    function updatePreview() {
        let content = rawTemplateContent;

        // Sabit değişkenleri değiştir
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
            '[TARIH]': '<?= date('d.m.Y') ?>',
            '[EVRAK_NO]': document.querySelector('input[name="document_number"]')?.value || '',
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

        // Ekler
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

        document.getElementById('preview-content').innerHTML = content;
    }

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
        
        newRow.querySelector('.attachment-input').addEventListener('input', updatePreview);
        newRow.querySelector('.remove-attachment').addEventListener('click', function() {
            newRow.remove();
            updatePreview();
        });
        
        newRow.querySelector('input').focus();
    });

    // Tüm dynamic-field'lara event listener
    document.querySelectorAll('.dynamic-field').forEach(input => {
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });

    // İlk önizleme
    updatePreview();
});
</script>
<?= $this->endSection() ?>