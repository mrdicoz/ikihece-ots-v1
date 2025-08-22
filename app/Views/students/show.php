<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-backpack2"></i> Öğrenci Profili</h1>
        
        <div class="mt-2 mt-sm-0">
            <div class="d-grid d-sm-flex gap-2">
                <a href="<?= auth()->user()->inGroup('ogretmen') ? route_to('students.my') : site_url('students') ?>" class="btn btn-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left"></i> Listeye Dön
                </a>
                
                <?php if (auth()->user()->can('ogrenciler.sil')): ?>
                <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
                    <i class="bi bi-trash-fill"></i> Öğrenciyi Sil
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <img class="img-fluid rounded-circle mb-3" src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" alt="Profil Fotoğrafı" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4 class="card-title"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></h4>
                    
                    <?php // HATA DÜZELTİLDİ ?>
                    <p class="text-muted">TCKN: <?= esc($student['tckn']) ?></p>

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
                    
                    <?php if (auth()->user()->can('ogrenciler.duzenle')): ?>
                    <a href="<?= site_url('students/' . $student['id'] . '/edit') ?>" class="btn btn-success w-100 mt-2">
                        <i class="bi bi-pencil-square"></i> Bilgileri Düzenle
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs nav-fill" id="studentTab" role="tablist">
                         <li class="nav-item" role="presentation"><button class="nav-link text-success active" data-bs-toggle="tab" data-bs-target="#temel" type="button" role="tab">Temel Bilgiler</button></li>
                         <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#veli" type="button" role="tab">Veli Bilgileri</button></li>
                         <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#adres" type="button" role="tab">Adres & Ulaşım</button></li>
                         <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#egitim" type="button" role="tab">Eğitim</button></li>
                         <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#rapor" type="button" role="tab">Raporlar & Sağlık</button></li>
                         <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#haklar" type="button" role="tab">Ders Hakları</button></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="studentTabContent">
                        
                        <div class="tab-pane fade show active" id="temel" role="tabpanel">
                            <h5 class="card-title text-success">Temel Bilgiler</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Cinsiyet:</strong> <?= esc($student['cinsiyet'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Doğum Tarihi:</strong> <?= esc($student['dogum_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>İletişim Tel:</strong> <?= esc($student['iletisim'] ?? 'Belirtilmemiş') ?></li>
                            </ul>
                        </div>
                        
                        <div class="tab-pane fade" id="veli" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h6 class="text-success">Anne Bilgileri</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['veli_anne'] ?? 'Belirtilmemiş') ?></li>
                                        <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['veli_anne_telefon'] ?? 'Belirtilmemiş') ?></li>
                                        <li class="list-group-item"><strong>TCKN:</strong> <?= esc($student['veli_anne_tc'] ?? 'Belirtilmemiş') ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success">Baba Bilgileri</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['veli_baba'] ?? 'Belirtilmemiş') ?></li>
                                        <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['veli_baba_telefon'] ?? 'Belirtilmemiş') ?></li>
                                        <li class="list-group-item"><strong>TCKN:</strong> <?= esc($student['veli_baba_tc'] ?? 'Belirtilmemiş') ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="adres" role="tabpanel">
                            <h5 class="card-title text-success">Adres ve Ulaşım Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Adres Detay:</strong> <?= esc($student['adres_detayi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>İl / İlçe:</strong> <?= esc($student['city_name'] ?? 'Belirtilmemiş') ?> / <?= esc($student['district_name'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Servis Durumu:</strong> <?= esc($student['servis'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Mesafe:</strong> <?= esc($student['mesafe'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Google Konum:</strong> <a href="<?= esc($student['google_konum'] ?? '#') ?>" target="_blank"><?= esc($student['google_konum'] ? 'Konumu Görüntüle' : 'Belirtilmemiş') ?></a></li>
                            </ul>
                        </div>
                        
                        <div class="tab-pane fade" id="egitim" role="tabpanel">
                             <h5 class="card-title text-success">Eğitim Bilgileri</h5>
                             <ul class="list-group list-group-flush">
                                 <li class="list-group-item"><strong>Örgün Eğitim Durumu:</strong> <?= esc($student['orgun_egitim'] ?? 'Belirtilmemiş') ?></li>
                                 <li class="list-group-item"><strong>Eğitim Şekli:</strong> <?= esc($student['egitim_sekli'] ?? 'Belirtilmemiş') ?></li>
                                 <li class="list-group-item"><strong>Eğitim Programları:</strong> 
                                     <?php if (!empty($student['egitim_programi'])): ?>
                                         <?php foreach ($student['egitim_programi'] as $program): ?>
                                             <span class="badge bg-secondary"><?= esc($program) ?></span>
                                         <?php endforeach; ?>
                                     <?php else: ?>
                                         Belirtilmemiş
                                     <?php endif; ?>
                                 </li>
                            </ul>
                        </div>
                        
                        <div class="tab-pane fade" id="rapor" role="tabpanel">
                            <h5 class="card-title text-success">RAM Rapor Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>RAM Açıklama:</strong> <?= esc($student['ram'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Rapor Tarihleri:</strong> <?= esc($student['ram_baslagic'] ?? 'Belirtilmemiş') ?> - <?= esc($student['ram_bitis'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Rapor Dosyası:</strong>
                                    <?php if (!empty($student['ram_raporu'])): ?>
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>">
                                            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">Rapor yüklenmemiş.</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                            <hr class="my-4">
                            <h5 class="card-title text-success">Hastane Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Hastane Adı:</strong> <?= esc($student['hastane_adi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Rapor Tarihleri:</strong> <?= esc($student['hastane_raporu_baslama_tarihi'] ?? 'Belirtilmemiş') ?> - <?= esc($student['hastane_raporu_bitis_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Randevu Tarih/Saat:</strong> <?= esc($student['hastane_randevu_tarihi'] ?? 'Belirtilmemiş') ?> <?= esc($student['hastane_randevu_saati'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Açıklama:</strong> <?= esc($student['hastane_aciklama'] ?? 'Belirtilmemiş') ?></li>
                            </ul>
                        </div>
                        
                        <div class="tab-pane fade" id="haklar" role="tabpanel">
                             <h5 class="card-title text-success">Tanımlanan Ders Hakları</h5>
                             <ul class="list-group list-group-flush">
                                 <li class="list-group-item"><strong>Normal Bireysel Hak:</strong> <span class="badge bg-primary fs-6"><?= esc($student['normal_bireysel_hak'] ?? '0') ?></span></li>
                                 <li class="list-group-item"><strong>Normal Grup Hak:</strong> <span class="badge bg-primary fs-6"><?= esc($student['normal_grup_hak'] ?? '0') ?></span></li>
                                 <li class="list-group-item"><strong>Telafi Bireysel Hak:</strong> <span class="badge bg-info fs-6"><?= esc($student['telafi_bireysel_hak'] ?? '0') ?></span></li>
                                 <li class="list-group-item"><strong>Telafi Grup Hak:</strong> <span class="badge bg-info fs-6"><?= esc($student['telafi_grup_hak'] ?? '0') ?></span></li>
                            </ul>
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
      <div class="modal-body p-0 text-center" style="height: 80vh; background-color: #f1f1f1;">
          <div id="pdf-viewer" class="overflow-auto h-100">
              <canvas id="pdf-canvas"></canvas>
          </div>
          <div id="pdf-loading" class="position-absolute top-50 start-50 translate-middle">
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

<?php if (auth()->user()->can('ogrenciler.sil')): ?>
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
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // PDF Viewer Logic
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

    if (prevPageBtn) prevPageBtn.addEventListener('click', showPrevPage);
    if (nextPageBtn) nextPageBtn.addEventListener('click', showNextPage);

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

    // Delete Modal Script
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