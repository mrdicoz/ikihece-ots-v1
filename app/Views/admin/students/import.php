<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?>Öğrenci Verilerini İçeri Aktar<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <h1 class="h3 mb-4 text-gray-800"><i class="bi bi-cloud-upload-fill"></i> Toplu Öğrenci Veri Aktarımı</h1>
    
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body p-lg-5">
            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h4>İşlem Adımları</h4>
                    <p class="text-muted">Lütfen adımları takip ederek veri aktarımını gerçekleştirin.</p>
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item"><strong>Dosyanızı Kontrol Edin:</strong> Elinizdeki `student.xlsx` veya `.csv` dosyasının sütunlarının doğru olduğundan emin olun.</li>
                        <li class="list-group-item"><strong>Dosyayı Yükleyin:</strong> Hazırladığınız dosyayı yandaki alana sürükleyin veya seçin.</li>
                        <li class="list-group-item"><strong>İşlemi Başlatın:</strong> "Yükle ve İşle" butonuna tıklayarak verilerinizi sisteme aktarın.</li>
                    </ol>
                </div>
                <div class="col-lg-7 border-start-lg">
                    <form id="import-form" action="<?= route_to('admin.students.importMapping') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div id="drop-zone" class="drop-zone p-5 text-center">
                            <i class="bi bi-file-earmark-arrow-up-fill fs-1"></i>
                            <p class="drop-zone-text mt-3">Dosyanızı buraya sürükleyin veya seçmek için tıklayın</p>
                            <small>(.ods, .xls, .xlsx, .csv)</small>
                        </div>
                        <input type="file" id="file-input" name="file" class="d-none" required accept=".ods,.xls,.xlsx,.csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.spreadsheet">
                        <div class="mt-3 text-center" id="file-info" style="display: none;">
                            Seçilen Dosya: <span id="file-name-display" class="fw-bold text-success"></span>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" id="submit-button" class="btn btn-success mt-3" disabled>
                                <i class="bi bi-hdd-stack-fill"></i> Yükle ve İşle
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
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const submitButton = document.getElementById('submit-button');
    const fileNameDisplay = document.getElementById('file-name-display');
    const fileInfo = document.getElementById('file-info');
    const importForm = document.getElementById('import-form');

    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => handleFileSelect(fileInput.files));

    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('bg-light'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('bg-light'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('bg-light');
        handleFileSelect(e.dataTransfer.files);
    });

    function handleFileSelect(files) {
        if (files.length > 0) {
            fileInput.files = files;
            fileNameDisplay.textContent = files[0].name;
            fileInfo.style.display = 'block';
            submitButton.disabled = false;
        }
    }

    importForm.addEventListener('submit', function() {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Lütfen Bekleyin, İşleniyor...';
    });
});
</script>
<?= $this->endSection() ?>