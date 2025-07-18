<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-backpack2"></i> Öğrenci Profili</h1>
        
               <div class="mt-2 mt-sm-0">
             <div class="d-grid d-sm-flex gap-2">
                <a href="<?= site_url('students') ?>" class="btn btn-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left"></i> Listeye Dön
                </a>
                <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
                    <i class="bi bi-trash-fill"></i> Öğrenciyi Sil
                </button>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <img class="img-fluid rounded-circle mb-3" src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" alt="Profil Fotoğrafı" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4 class="card-title"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></h4>
                    <p class="text-muted">TCKN: <?= esc($student['tc_kimlik_no']) ?></p>
                    <hr>
                    <?php if (!empty($student['ram_raporu'])): ?>
                        <button type="button" class="btn btn-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>">
                            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary w-100 mt-2" disabled>
                            <i class="bi bi-eye-slash-fill"></i> Rapor Yok
                        </button>
                    <?php endif; ?>
                    <a href="<?= site_url('students/' . $student['id'] . '/edit') ?>" class="btn btn-success w-100 mt-2">
                        <i class="bi bi-pencil-square"></i> Bilgileri Düzenle
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs nav-fill" id="studentTab" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link text-success active" id="temel-tab" data-bs-toggle="tab" data-bs-target="#temel" type="button" role="tab">Temel Bilgiler</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="veli-tab" data-bs-toggle="tab" data-bs-target="#veli" type="button" role="tab">Veli Bilgileri</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="saglik-tab" data-bs-toggle="tab" data-bs-target="#saglik" type="button" role="tab">Sağlık</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="adres-tab" data-bs-toggle="tab" data-bs-target="#adres" type="button" role="tab">Adres & Konum</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="diger-tab" data-bs-toggle="tab" data-bs-target="#diger" type="button" role="tab">Diğer</button></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="studentTabContent">
                        <div class="tab-pane fade show active" id="temel" role="tabpanel">
                            <h5 class="card-title">Temel Bilgiler</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Cinsiyet:</strong> <?= esc($student['cinsiyet'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Doğum Tarihi:</strong> <?= esc($student['dogum_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Servis Durumu:</strong> <?= esc($student['servis_durumu'] ?? 'Belirtilmemiş') ?></li>
                            </ul>
                            <hr>
                            <h5 class="card-title">Acil Durum Kişisi</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['acil_durum_aranacak_kisi_1_adi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Yakınlık:</strong> <?= esc($student['acil_durum_aranacak_kisi_1_yakinlik'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['acil_durum_aranacak_kisi_1_telefon'] ?? 'Belirtilmemiş') ?></li>
                            </ul>
                        </div>
                        
                        <div class="tab-pane fade" id="veli" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">

                            <h5 class="card-title">Anne Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['veli_anne_adi_soyadi']) ?></li>
                                <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['veli_anne_telefon']) ?></li>
                                <li class="list-group-item"><strong>E-posta:</strong> <?= esc($student['veli_anne_eposta']) ?></li>
                                <li class="list-group-item"><strong>TCKN:</strong> <?= esc($student['veli_anne_tc']) ?></li>
                            </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title">Baba Bilgileri</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['veli_baba_adi_soyadi']) ?></li>
                                        <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['veli_baba_telefon']) ?></li>
                                        <li class="list-group-item"><strong>E-posta:</strong> <?= esc($student['veli_baba_eposta']) ?></li>
                                        <li class="list-group-item"><strong>TCKN:</strong> <?= esc($student['veli_baba_tc']) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="saglik" role="tabpanel">
                                    <h5 class="card-title">Genel Sağlık Bilgileri</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Kan Grubu:</strong>  <?= esc($student['kan_grubu'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Geçirilen Hastalıklar:</strong>  <?= esc($student['gecirilen_hastaliklar'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Alerjiler:</strong>  <?= esc($student['alerjiler'] ?? 'Belirtilmemiş') ?></li>
                                </ul>
    <hr>

                                        <h5 class="card-title">RAM Rapor Bilgileri</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Rapor Başlangıç Tarihi:</strong>  <?= esc($student['ram_baslangic_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Rapor Bitiş Tarihi:</strong>  <?= esc($student['ram_bitis_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Rapor Dosyası:</strong>
                                    <?php if (!empty($student['ram_raporu'])): ?>
        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>">
            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
        </button>
    <?php else: ?>
        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="#" disabled>
            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
        </button>
    <?php endif; ?>
                                </li>
                                </ul>
                        </div>
                        
                        <div class="tab-pane fade" id="adres" role="tabpanel">
                            <h5 class="mb-3">Adres ve Konum Bilgisi</h5>
                            <p><?= esc($student['adres_detay']) ?> <?= esc($student['adres_mahalle']) ?>, <?= esc($student['adres_ilce']) ?> / <?= esc($student['adres_il']) ?></p>
                            <div id="map-container" class="alert alert-info">Harita özelliği için bir API anahtarı gereklidir. Bu özellik daha sonra eklenecektir.</div>
                        </div>

                        <div class="tab-pane fade" id="diger" role="tabpanel">
                            <h5 class="mb-3">Acil Durum Kişileri</h5>
                            <p><b>Kişi 1:</b> <?= esc($student['acil_durum_aranacak_kisi_1_adi']) ?> (<?= esc($student['acil_durum_aranacak_kisi_1_yakinlik']) ?>) - <?= esc($student['acil_durum_aranacak_kisi_1_telefon']) ?></p>
                            <hr>
                            <h5 class="mt-4 mb-3">Kardeş Bilgileri</h5>
                            <p><?= esc($student['kardes_adi_1']) ?></p>
                            <hr>
                            <h5 class="mt-4 mb-3">Muhasebe Bilgileri</h5>
                            <dl class="row"><dt class="col-sm-4">Sözleşme Tutarı</dt><dd class="col-sm-8"><?= esc($student['sozlesme_tutari']) ?> ₺</dd></dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportModalLabel"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?> - RAM Raporu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 text-center" style="height: 80vh; background-color: #969696ff;">
          <div id="pdf-viewer" class="overflow-auto h-100">
              <canvas id="pdf-canvas"></canvas>
          </div>
          <div id="pdf-loading" class="position-absolute top-50 start-50 translate-middle text-white">
              <div class="spinner-border" role="status">
                  <span class="visually-hidden">Yükleniyor...</span>
              </div>
          </div>
      </div>
      <div class="modal-footer justify-content-center">
          <button id="prev-page" class="btn btn-outline-success"><i class="bi bi-arrow-left-circle"></i></button>
          <span class="align-self-center mx-3">
              Sayfa <span id="page-num"></span> / <span id="page-count"></span>
          </span>
          <button id="next-page" class="btn btn-outline-success"><i class="bi bi-arrow-right-circle"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteStudentModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> Silme İşlemi Onayı</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><b><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></b> adlı öğrenciyi kalıcı olarak silmek (arşivlemek) üzeresiniz. Bu işlem geri alınamaz.</p>
        <p>Devam etmek için lütfen aşağıdaki alana öğrencinin tam adını (<strong class="text-danger"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></strong>) yazarak onaylayın:</p>
        <div class="mb-3">
            <label for="studentNameConfirm" class="form-label">Onay için Ad Soyad:</label>
            <input type="text" class="form-control" id="studentNameConfirm" autocomplete="off">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
        <form action="<?= site_url('students/' . $student['id']) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" id="confirmDeleteButton" class="btn btn-danger" disabled>Evet, Sil</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // PDF.js worker'ının yolunu belirtiyoruz.
    pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js`;

    const reportModalEl = document.getElementById('reportModal');
    const pdfCanvas = document.getElementById('pdf-canvas');
    const loadingSpinner = document.getElementById('pdf-loading');
    const pageNumSpan = document.getElementById('page-num');
    const pageCountSpan = document.getElementById('page-count');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');

    let pdfDoc = null;
    let pageNum = 1;
    let pageIsRendering = false;
    let pageNumPending = null;

    const renderPage = num => {
        pageIsRendering = true;

        pdfDoc.getPage(num).then(page => {
            const viewport = page.getViewport({ scale: 1.5 });
            pdfCanvas.height = viewport.height;
            pdfCanvas.width = viewport.width;

            const renderContext = {
                canvasContext: pdfCanvas.getContext('2d'),
                viewport: viewport
            };

            page.render(renderContext).promise.then(() => {
                pageIsRendering = false;
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
            });
        });
        pageNumSpan.textContent = num;
    };
    
    const queueRenderPage = num => {
        if (pageIsRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    };

    const showPrevPage = () => {
        if (pageNum <= 1) return;
        pageNum--;
        queueRenderPage(pageNum);
    };

    const showNextPage = () => {
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        queueRenderPage(pageNum);
    };

    prevPageBtn.addEventListener('click', showPrevPage);
    nextPageBtn.addEventListener('click', showNextPage);

    if (reportModalEl) {
        reportModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const pdfUrl = button.getAttribute('data-src');
            
            loadingSpinner.style.display = 'block';
            pdfCanvas.style.display = 'none';

            pdfjsLib.getDocument(pdfUrl).promise.then(doc => {
                pdfDoc = doc;
                pageCountSpan.textContent = doc.numPages;
                pageNum = 1;
                renderPage(pageNum);

                loadingSpinner.style.display = 'none';
                pdfCanvas.style.display = 'block';

            }).catch(err => {
                console.error('PDF yüklenirken hata oluştu: ', err);
                loadingSpinner.innerHTML = '<p class="text-danger">Rapor yüklenemedi.</p>';
            });
        });

        reportModalEl.addEventListener('hidden.bs.modal', function () {
            pdfDoc = null;
        });
    }

    // Silme Onay Modal Script'i
    const deleteModalEl = document.getElementById('deleteStudentModal');
    if (deleteModalEl) {
        const studentName = "<?= esc($student['adi'] . ' ' . $student['soyadi']) ?>";
        const confirmInput = deleteModalEl.querySelector('#studentNameConfirm');
        const confirmButton = deleteModalEl.querySelector('#confirmDeleteButton');

        confirmInput.addEventListener('input', function() {
            if (confirmInput.value.trim().toLowerCase() === studentName.toLowerCase()) {
                confirmButton.disabled = false;
            } else {
                confirmButton.disabled = true;
            }
        });
        
        deleteModalEl.addEventListener('hidden.bs.modal', function () {
            confirmInput.value = '';
            confirmButton.disabled = true;
        });
    }
});
</script>
<?= $this->endSection() ?>