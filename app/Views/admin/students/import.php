<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?>Öğrenci Verilerini İçeri Aktar<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-cloud-upload-fill"></i> Öğrenci Verilerini İçeri Aktar</h1>
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
                    <p class="text-muted">Toplu öğrenci kaydı için lütfen aşağıdaki adımları takip edin.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-1-circle-fill text-success me-3 fs-4"></i>
                            <div>
                                <strong>Örnek Şablonu İndirin</strong>
                                <small class="d-block">Verilerinizi hazırlamak için güncel şablonu kullanın.</small>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-2-circle-fill text-success me-3 fs-4"></i>
                            <div>
                                <strong>Dosyanızı Hazırlayın</strong>
                                <small class="d-block">Şablondaki sütunlara uygun şekilde verilerinizi girin.</small>
                            </div>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-3-circle-fill text-success me-3 fs-4"></i>
                            <div>
                                <strong>Dosyayı Yükleyin</strong>
                                <small class="d-block">Hazırladığınız dosyayı yandaki alana sürükleyin veya seçin.</small>
                            </div>
                        </li>
                    </ul>
                    <div class="d-grid mt-4">
                        <a href="<?= base_url('assets/OgrenciListesi_Sablon.xls') ?>" class="btn btn-success" download>
                            <i class="bi bi-download"></i> Örnek Şablonu İndir
                        </a>
                    </div>
                </div>

                <div class="col-lg-7 border-start-lg">
                    <form id="import-form" action="<?= site_url('admin/students/import') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <div id="drop-zone" class="drop-zone">
                            <i class="bi bi-file-earmark-arrow-up-fill fs-1"></i>
                            <p class="drop-zone-text mt-3">Dosyanızı buraya sürükleyin veya seçmek için tıklayın</p>
                            <small>(.xls, .xlsx, .csv)</small>
                        </div>
                        
                        <input type="file" id="file-input" name="file" class="d-none" required accept=".xls, .xlsx, .csv">
                        
                        <div class="mt-3 text-center" id="file-info" style="display: none;">
                            Seçilen Dosya: <span id="file-name-display" class="file-name-display"></span>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" id="submit-button" class="btn btn-success mt-3" disabled>
                                <i class="bi bi-hdd-stack-fill"></i> Yükle ve İşle
                            </button>
                        </div>
                        
                        <div class="progress mt-3" id="progress-bar-container" style="display: none;">
                            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
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
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const submitButton = document.getElementById('submit-button');
    const fileInfo = document.getElementById('file-info');
    const fileNameDisplay = document.getElementById('file-name-display');
    const importForm = document.getElementById('import-form');
    const progressBarContainer = document.getElementById('progress-bar-container');
    const progressBar = document.getElementById('progress-bar');

    // Dropzone'a tıklandığında gizli file input'u tetikle
    dropZone.addEventListener('click', () => fileInput.click());

    // Dosya sürükleme olayları
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
    
    // Dosya input'u değiştiğinde
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

    // Form gönderildiğinde
    importForm.addEventListener('submit', function() {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> İşleniyor...';
        
        progressBarContainer.style.display = 'block';
        let progress = 0;
        const interval = setInterval(() => {
            progress += 10;
            if (progress > 100) progress = 100;
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            if(progress === 100) {
                clearInterval(interval);
            }
        }, 200);
    });
});
</script>
<?= $this->endSection() ?>