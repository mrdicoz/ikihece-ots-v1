<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-robot"></i> <?= esc($title) ?></h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body p-lg-5">
            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h4>İşlem Adımları</h4>
                    <p class="text-muted">Geçmiş ders programı verilerinizi yükleyerek yapay zeka modelini eğitebilirsiniz.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-1-circle-fill text-success me-3 fs-4"></i>
                            <div>
                                <strong>Dosyanızı Hazırlayın</strong>
                                <small class="d-block">Dosyanın ilk satırında başlıklar olmalı ve sütun sırası şu şekilde olmalıdır: <b>Tarih, Saat, Eğitimci, Öğrenci.</b></small>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-2-circle-fill text-success me-3 fs-4"></i>
                            <div>
                                <strong>Dosyayı Yükleyin</strong>
                                <small class="d-block">Hazırladığınız dosyayı yandaki alana sürükleyin veya seçin.</small>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-7 border-start-lg">
                    <form id="import-form" action="<?= route_to('admin.ai.processUpload') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <div id="drop-zone" class="drop-zone">
                            <i class="bi bi-file-earmark-arrow-up-fill fs-1"></i>
                            <p class="drop-zone-text mt-3">Excel dosyanızı buraya sürükleyin veya seçmek için tıklayın</p>
                            <small>(.xls, .xlsx, .csv)</small>
                        </div>
                        
                        <input type="file" id="file-input" name="file" class="d-none" required accept=".xls, .xlsx, .csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                        
                        <div class="mt-3 text-center" id="file-info" style="display: none;">
                            Seçilen Dosya: <span id="file-name-display" class="file-name-display"></span>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" id="submit-button" class="btn btn-success mt-3" disabled>
                                <i class="bi bi-hdd-stack-fill"></i> Yükle ve Veri Setini Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
// Bu script, öğrenci içe aktarma sayfasındakiyle aynıdır.
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const submitButton = document.getElementById('submit-button');
    const fileInfo = document.getElementById('file-info');
    const fileNameDisplay = document.getElementById('file-name-display');

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect();
        }
    });
    
    fileInput.addEventListener('change', handleFileSelect);

    function handleFileSelect() {
        if (fileInput.files.length > 0) {
            const fileName = fileInput.files[0].name;
            fileNameDisplay.textContent = fileName;
            fileInfo.style.display = 'block';
            submitButton.disabled = false;
        } else {
            fileInfo.style.display = 'none';
            submitButton.disabled = true;
        }
    }
});
</script>
<?= $this->endSection() ?>