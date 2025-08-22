<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-cloud-arrow-down-fill"></i> <?= esc($title) ?></h1>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Sistem Durumu</h6>
                </div>
                <div class="card-body">
                    <p><strong>Mevcut Yüklü Versiyon:</strong> <span class="badge bg-success fs-6"><?= esc($currentVersion) ?></span></p>
                    
                    <p><strong>Sunucudaki Son Versiyon:</strong> <span id="latest-version" class="badge bg-secondary fs-6">Bilinmiyor</span></p>
                    
                    <hr>
                    <div id="update-status">
                        <div class="d-grid gap-2">
                             <button id="check-update-btn" class="btn btn-info">
                                <i class="bi bi-arrow-repeat"></i> Güncellemeleri Kontrol Et
                            </button>
                            <button id="run-update-btn" class="btn btn-warning" style="display: none;">
                                <i class="bi bi-download"></i> Yeni Sürümü Yükle ve Sistemi Güncelle
                            </button>
                        </div>
                         <div id="up-to-date-msg" class="alert alert-success mt-3" style="display: none;">
                            <i class="bi bi-check-circle-fill"></i> Sisteminiz güncel.
                        </div>
                    </div>
                    <div id="release-notes-container" class="mt-3" style="display:none;">
                        <strong>Sürüm Notları:</strong>
                        <div id="release-notes" class="border p-2 rounded bg-light" style="max-height: 150px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-dark">Güncelleme Çıktısı</h6>
                </div>
                <div class="card-body p-0">
                    <pre id="update-log" class="bg-dark text-white m-0 p-3 rounded-bottom" style="height: 400px; overflow-y: scroll; font-size: 0.8em; white-space: pre-wrap;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkBtn = document.getElementById('check-update-btn');
    const runBtn = document.getElementById('run-update-btn');
    const logArea = document.getElementById('update-log');
    const latestVersionSpan = document.getElementById('latest-version');
    const upToDateMsg = document.getElementById('up-to-date-msg');
    const releaseNotesContainer = document.getElementById('release-notes-container');
    const releaseNotesDiv = document.getElementById('release-notes');

    function addToLog(message, type = 'log') {
        const timestamp = new Date().toLocaleTimeString();
        let colorClass = 'text-white-50';
        if (type === 'error') colorClass = 'text-danger fw-bold';
        if (type === 'success') colorClass = 'text-success fw-bold';

        logArea.innerHTML += `<span class="${colorClass}">${timestamp}: ${message.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</span><br>`;
        logArea.scrollTop = logArea.scrollHeight;
    }

    checkBtn.addEventListener('click', async function () {
        addToLog('Güncellemeler kontrol ediliyor...');
        checkBtn.disabled = true;
        checkBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Kontrol Ediliyor...';
        upToDateMsg.style.display = 'none';
        runBtn.style.display = 'none';
        releaseNotesContainer.style.display = 'none';

        try {
            const response = await fetch('<?= route_to("admin.update.check") ?>');
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Bilinmeyen bir sunucu hatası oluştu.');
            }
            
            latestVersionSpan.textContent = data.latest_version;
            if (data.update_available) {
                addToLog('Yeni bir güncelleme bulundu!', 'success');
                latestVersionSpan.className = 'badge bg-warning fs-6';
                runBtn.style.display = 'block';
                runBtn.dataset.url = data.download_url; // İndirme linkini butona ata
                checkBtn.style.display = 'none';
                releaseNotesDiv.textContent = data.release_notes;
                releaseNotesContainer.style.display = 'block';
            } else {
                addToLog('Sisteminiz zaten güncel.');
                latestVersionSpan.className = 'badge bg-success fs-6';
                upToDateMsg.style.display = 'block';
            }
        } catch (error) {
            addToLog('Kontrol sırasında hata: ' + error.message, 'error');
            latestVersionSpan.textContent = 'Hata!';
            latestVersionSpan.className = 'badge bg-danger fs-6';
        } finally {
            checkBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Güncellemeleri Kontrol Et';
            checkBtn.disabled = false;
        }
    });

    runBtn.addEventListener('click', function () {
        const downloadUrl = runBtn.dataset.url;
        if (!downloadUrl) {
            addToLog('İndirme adresi bulunamadı!', 'error');
            return;
        }

        addToLog('Güncelleme işlemi başlatılıyor. Lütfen bekleyin...');
        runBtn.disabled = true;
        runBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Güncelleniyor...';
        
        const eventSource = new EventSource('<?= site_url("admin/update/run") ?>?url=' + encodeURIComponent(downloadUrl));

        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            addToLog(data.message, data.type);

            if (data.message.includes('GÜNCELLEME BAŞARIYLA TAMAMLANDI')) {
                eventSource.close();
                runBtn.classList.remove('btn-warning');
                runBtn.classList.add('btn-success');
                runBtn.innerHTML = '<i class="bi bi-check-all"></i> Başarıyla Güncellendi';
                addToLog('Bağlantı kapatıldı. Yeni versiyonu görmek için sayfayı yenileyin.');
            }
        };

        eventSource.onerror = function() {
            addToLog('Sunucu ile bağlantı kesildi veya bir hata oluştu.', 'error');
            runBtn.disabled = false;
            runBtn.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Hata Oluştu, Tekrar Dene';
            runBtn.classList.remove('btn-warning');
            runBtn.classList.add('btn-danger');
            eventSource.close();
        };
    });
});
</script>
<?= $this->endSection() ?>