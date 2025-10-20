<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    /* A4 Önizleme Stilleri */
    @media print {
        body, .modal-dialog {
            margin: 0;
            box-shadow: 0;
        }
    }
    .a4-page {
        background: white;
        width: 21cm;
        min-height: 29.7cm;
        display: block;
        margin: 1cm auto;
        padding: 1.5cm;
        border: 1px #D3D3D3 solid;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        color: #000;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
<form method="post" id="templateForm" action="<?= base_url('documents/templates/edit/' . $template->id) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="dynamic_fields" id="dynamicFieldsJSON">

    <div class="container-fluid py-4">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i><?= esc($title) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Şablon Adı <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Örn: İşe Giriş Bildirimi" value="<?= old('name', $template->name) ?>" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="" selected>Seçiniz...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat->id ?>" <?= old('category_id', $template->category_id) == $cat->id ? 'selected' : '' ?>><?= esc($cat->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Açıklama</label>
                                <input type="text" name="description" class="form-control" placeholder="Bu şablonun ne için kullanıldığına dair kısa bir açıklama..." value="<?= old('description', $template->description) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Şablon Metni</h6></div>
                    <div class="card-body">
                        <div class="mb-3 d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[LOGO]">[LOGO]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[QR_KOD]">[QR_KOD]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[KURUM_ADI]">[KURUM_ADI]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[KURUM_KISA_ADI]">[KURUM_KISA_ADI]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[MUDUR]">[MUDUR]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[KURUCU_MUDUR]">[KURUCU_MUDUR]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[ADRES]">[ADRES]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[TELEFON]">[TELEFON]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[SABIT_TELEFON]">[SABIT_TELEFON]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[EPOSTA]">[EPOSTA]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[EVRAK_PREFIX]">[EVRAK_PREFIX]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[EVRAK_BASLANGIC_NO]">[EVRAK_BASLANGIC_NO]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="[TARIH]">[TARIH]</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm static-variable-btn" data-value="{EKLER}">{EKLER}</button>
                        </div>
                        <textarea name="content" id="tinymceEditor" class="form-control"><?= old('content', $template->content) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-braces me-2"></i>Değişken Tanımları</h6>
                        <button type="button" class="btn btn-light btn-sm" id="addFieldBtn"><i class="bi bi-plus-circle me-1"></i>Değişken Ekle</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="dynamicFieldsTable">
                                <thead><tr><th width="25%">Değişken</th><th width="35%">Form Etiketi</th><th width="20%">Tip</th><th width="20%">Aksiyon</th></tr></thead>
                                <tbody><tr id="no-variables-row"><td colspan="4" class="text-center text-muted">Henüz değişken eklenmedi.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow mb-3">
            <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="bi bi-123"></i> Evrak Numaralama Kuralları</h5></div>
            <div class="card-body"><div class="row">
                <div class="col-md-4"><div class="form-check form-switch"><input type="checkbox" name="has_number" value="1" class="form-check-input" id="hasNumber" <?= $template->has_number ? 'checked' : '' ?>><label class="form-check-label" for="hasNumber">Bu belgede evrak numarası olsun</label></div></div>
                <div class="col-md-4" id="customNumberDiv"><div class="form-check form-switch"><input type="checkbox" name="allow_custom_number" value="1" class="form-check-input" id="allowCustom" <?= $template->allow_custom_number ? 'checked' : '' ?>><label class="form-check-label" for="allowCustom">Esnek Numara</label></div></div>
                <div class="col-md-4" id="fillGapsDiv"><div class="form-check form-switch"><input type="checkbox" name="fill_gaps" value="1" class="form-check-input" id="fillGaps" <?= $template->fill_gaps ? 'checked' : '' ?>><label class="form-check-label" for="fillGaps">Boşlukları Doldur</label></div></div>
            <div class="col-md-4">
    <div class="form-check form-switch">
        <input type="checkbox" name="active" value="1" class="form-check-input" id="activeStatus" <?= $template->active ? 'checked' : '' ?>>
        <label class="form-check-label" for="activeStatus">Şablon Aktif</label>
    </div>
</div>
            </div></div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between">
                        <a href="<?= base_url('documents/templates') ?>" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Geri Dön</a>
                        <div>
                            <button type="button" class="btn btn-secondary me-1" id="previewBtn"><i class="bi bi-eye me-2"></i>Önizleme</button>
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-2"></i>Değişiklikleri Kaydet</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="fieldModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="bi bi-braces me-2"></i>Değişken Tanımla</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="fieldForm">
                <div class="mb-3"><label class="form-label">Değişken Adı *</label><input type="text" id="fieldName" class="form-control" placeholder="AD_SOYAD"><small class="text-muted">Şablonda <code>{AD_SOYAD}</code> şeklinde kullanılacak</small></div>
                <div class="mb-3"><label class="form-label">Form Etiketi *</label><input type="text" id="fieldLabel" class="form-control" placeholder="Ad Soyad"><small class="text-muted">Formda gösterilecek etiket</small></div>
                <div class="mb-3"><label class="form-label">Input Tipi *</label><select id="fieldType" class="form-select"><option selected value="text">Text (Kısa metin)</option><option value="textarea">Textarea (Uzun metin)</option><option value="number">Number (Sayı)</option><option value="date">Date (Tarih)</option><option value="select">Select (Seçim listesi)</option></select></div>
                <div class="mb-3" id="optionsDiv" style="display:none;"><label class="form-label">Seçenekler</label><input type="text" id="fieldOptions" class="form-control" placeholder="Psikolog, Öğretmen"><small class="text-muted">Virgülle ayırarak yazın</small></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" id="fieldRequired" checked><label class="form-check-label" for="fieldRequired">Zorunlu alan</label></div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
            <button type="button" class="btn btn-success" id="saveFieldBtn"><i class="bi bi-check-circle me-2"></i>Kaydet</button>
        </div>
    </div></div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Belge Önizlemesi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="a4-preview-content" class="a4-page">
                    </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- DEĞİŞKENLER VE MODAL NESNELERİ ---
    let dynamicFields = [];
    let editingIndex = -1;
    const fieldModalEl = document.getElementById('fieldModal');
    const fieldModal = new bootstrap.Modal(fieldModalEl);
    const previewModalEl = document.getElementById('previewModal');
    const previewModal = new bootstrap.Modal(previewModalEl);

    // --- MEVCUT TEMPLATE VERİLERİNİ YÜKLE ---
    <?php if (!empty($template->dynamic_fields)): ?>
        try {
            dynamicFields = JSON.parse('<?= addslashes($template->dynamic_fields) ?>');
            renderFieldsTable();
        } catch(e) {
            console.error('Dynamic fields parse hatası:', e);
        }
    <?php endif; ?>

tinymce.init({
    selector: '#tinymceEditor',
    license_key: 'gpl',

    // 1. Önce plugins kısmına 'advlist' ekleyin
    plugins: 'lists link table code fullscreen advlist',
    
    // 2. Toolbar'da fontsizeselect kullanın
    toolbar: 'undo redo | alignjustify alignleft aligncenter alignright | fontsize | bold italic | table | hr | fullscreen | code ',
    
    // 3. Font boyutu seçenekleri
    font_size_formats: '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px',
    
    height: 500,
    menubar: false,
    content_style: 'body { font-family: Arial, sans-serif; font-size: 12pt; }',
});

    // --- OLAY DİNLEYİCİLER (EVENT LISTENERS) ---
    document.getElementById('addFieldBtn').addEventListener('click', () => {
        resetFieldForm();
        editingIndex = -1;
        document.getElementById('saveFieldBtn').textContent = 'Ekle';
        fieldModal.show();
    });

    document.getElementById('fieldType').addEventListener('change', e => {
        document.getElementById('optionsDiv').style.display = (e.target.value === 'select') ? 'block' : 'none';
    });

    document.getElementById('saveFieldBtn').addEventListener('click', saveField);
    document.getElementById('templateForm').addEventListener('submit', prepareFormForSubmit);
    
    document.getElementById('hasNumber').addEventListener('change', function() {
        const customDiv = document.getElementById('customNumberDiv');
        const fillDiv = document.getElementById('fillGapsDiv');
        if (this.checked) {
            customDiv.style.display = 'block';
            fillDiv.style.display = 'block';
        } else {
            customDiv.style.display = 'none';
            fillDiv.style.display = 'none';
        }
    });
    
    document.getElementById('previewBtn').addEventListener('click', showPreview);

    document.querySelectorAll('.static-variable-btn').forEach(button => {
        button.addEventListener('click', function() {
            const editor = tinymce.get('tinymceEditor');
            if (editor) {
                editor.execCommand('mceInsertContent', false, this.getAttribute('data-value'));
            }
        });
    });

    // --- FONKSİYONLAR ---
    function saveField() {
        const fieldName = document.getElementById('fieldName').value.trim().toUpperCase().replace(/ /g, '_').replace(/[^A-Z0-9_]/g, '');
        const fieldLabel = document.getElementById('fieldLabel').value.trim();
        if (!fieldName || !fieldLabel) { alert('Alan Adı ve Form Etiketi zorunludur!'); return; }
        
        const field = {
            name: fieldName,
            label: fieldLabel,
            type: document.getElementById('fieldType').value,
            options: document.getElementById('fieldType').value === 'select' ? document.getElementById('fieldOptions').value.split(',').map(o => o.trim()) : [],
            required: document.getElementById('fieldRequired').checked
        };
        
        if (editingIndex > -1) {
            dynamicFields[editingIndex] = field;
        } else {
            dynamicFields.push(field);
        }
        
        renderFieldsTable();
        fieldModal.hide();
    }

    function renderFieldsTable() {
        const tbody = document.querySelector('#dynamicFieldsTable tbody');
        tbody.innerHTML = '';
        
        if (dynamicFields.length === 0) {
            tbody.innerHTML = '<tr id="no-variables-row"><td colspan="4" class="text-center text-muted">Henüz değişken eklenmedi.</td></tr>';
            return;
        }
        
        dynamicFields.forEach((field, index) => {
            tbody.innerHTML += `
                <tr>
                    <td><code>{${field.name}}</code></td>
                    <td>${field.label}</td>
                    <td><span class="badge bg-secondary">${field.type}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.editField(${index})"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="window.deleteField(${index})"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
        });
    }

    window.editField = function(index) {
        editingIndex = index;
        const field = dynamicFields[index];
        document.getElementById('fieldName').value = field.name;
        document.getElementById('fieldLabel').value = field.label;
        document.getElementById('fieldType').value = field.type;
        document.getElementById('fieldOptions').value = field.options.join(', ');
        document.getElementById('fieldRequired').checked = field.required;
        document.getElementById('optionsDiv').style.display = (field.type === 'select') ? 'block' : 'none';
        document.getElementById('saveFieldBtn').textContent = 'Güncelle';
        fieldModal.show();
    }

    window.deleteField = function(index) {
        if (confirm('Bu alanı silmek istediğinize emin misiniz?')) {
            dynamicFields.splice(index, 1);
            renderFieldsTable();
        }
    }

    function resetFieldForm() {
        document.getElementById('fieldForm').reset();
        document.getElementById('fieldRequired').checked = true;
        document.getElementById('optionsDiv').style.display = 'none';
    }

    function prepareFormForSubmit(e) {
        tinymce.triggerSave();
        
        const content = tinymce.get('tinymceEditor').getContent();
        if (!content || content.trim() === '') {
            e.preventDefault();
            alert('Şablon metni boş olamaz!');
            return false;
        }
        
        document.getElementById('dynamicFieldsJSON').value = JSON.stringify(dynamicFields);
    }

    function showPreview() {
        let content = tinymce.get('tinymceEditor').getContent();
        
        // PHP'den gelen gerçek institution verilerini kullan
        const institution = <?= json_encode($institution) ?>;
        
        content = content.replace(/\[KURUM_ADI\]/g, `<b>${institution.name}</b>`);
        content = content.replace(/\[KURUM_KISA_ADI\]/g, `<b>${institution.short_name || 'Kısa Ad'}</b>`);
        content = content.replace(/\[LOGO\]/g, institution.logo 
            ? `<img src="<?= base_url('uploads/logos/') ?>${institution.logo}" style="max-width:150px;">` 
            : '<span class="text-muted">[Logo]</span>');
        content = content.replace(/\[QR_KOD\]/g, institution.qr_code 
            ? `<img src="<?= base_url('uploads/qr_codes/') ?>${institution.qr_code}" style="max-width:100px;">` 
            : '<span class="text-muted">[QR Kod]</span>');
        content = content.replace(/\[MUDUR\]/g, `<b>${institution.director || 'Müdür'}</b>`);
        content = content.replace(/\[KURUCU_MUDUR\]/g, `<b>${institution.founder_director || 'Kurucu Müdür'}</b>`);
        content = content.replace(/\[ADRES\]/g, `<i>${institution.address || 'Adres'}</i>`);
        content = content.replace(/\[TELEFON\]/g, institution.phone || 'Telefon');
        content = content.replace(/\[SABIT_TELEFON\]/g, institution.landline || 'Sabit Telefon');
        content = content.replace(/\[EPOSTA\]/g, institution.email || 'E-posta');
        content = content.replace(/\[WEB_SAYFA\]/g, institution.website || 'Web Sayfası');
        content = content.replace(/\[EVRAK_PREFIX\]/g, institution.evrak_prefix || 'Prefix');
        content = content.replace(/\[EVRAK_BASLANGIC_NO\]/g, institution.evrak_baslangic_no || 'Başlangıç No');
        content = content.replace(/\[TARIH\]/g, new Date().toLocaleDateString('tr-TR'));

        // Dinamik değişkenleri örnek verilerle doldur
        dynamicFields.forEach(field => {
            const regex = new RegExp(`\\{${field.name}\\}`, 'g');
            const sampleData = `<span style="background-color: #fff3cd; padding: 2px 5px; border-radius:3px; color:#664d03;">${field.label}</span>`;
            content = content.replace(regex, sampleData);
        });

        document.getElementById('a4-preview-content').innerHTML = content;
        previewModal.show();
    }
});
</script>
<?= $this->endSection() ?>