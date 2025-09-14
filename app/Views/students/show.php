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
                <div class="card-body mb-4">
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
                             </ul>
                             <hr class="my-4">
                             <h5 class="card-title text-success">Hastane Bilgileri</h5>
                             <ul class="list-group list-group-flush">
                                 <li class="list-group-item"><strong>Hastane Adı:</strong> <?= esc($student['hastane_adi'] ?? 'Belirtilmemiş') ?></li>
                                 <li class="list-group-item"><strong>Rapor Tarihleri:</strong> <?= esc($student['hastane_raporu_baslama_tarihi'] ?? 'Belirtilmemiş') ?> - <?= esc($student['hastane_raporu_bitis_tarihi'] ?? 'Belirtilmemiş') ?></li>
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

            <div class="card shadow mt-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0 me-3"><i class="bi bi-journal-text text-success"></i> Gelişim Günlüğü</h5>
                    <div class="d-flex align-items-center mt-2 mt-md-0">
                        <?php if (!empty($evaluators) && count($evaluators) > 1): ?>
                            <select id="teacher-filter" class="form-select form-select-sm me-2" style="width: auto;">
                                <option value="all">Tüm Öğretmenler</option>
                                <?php foreach ($evaluators as $evaluator): ?>
                                    <option value="<?= $evaluator['id'] ?>"><?= esc($evaluator['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-success btn-sm flex-shrink-0" id="apply-teacher-filter">Filtrele</button>
                        <?php endif; ?>
                        <?php if ($canAddEvaluation): ?>
                            <button class="btn btn-success btn-sm flex-shrink-0 ms-2" id="addNewEvaluationBtn">
                                <i class="bi bi-plus-circle"></i> Ekle
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body" id="evaluation-list">
                    <?php if (!empty($evaluations)): ?>
                        <?php foreach ($evaluations as $index => $eval): ?>
                            <div class="evaluation-wrapper" data-teacher-id="<?= esc($eval['teacher_id']) ?>">
                                <div class="d-flex mb-4">
                                    <div class="flex-shrink-0">
                                        <img src="<?= base_url($eval['profile_photo'] ?? 'assets/images/user.jpg') ?>" alt="Öğretmen" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="fw-bold mb-0"><?= esc($eval['teacher_snapshot_name']) ?></h6>
                                                <?php if(!empty($eval['teacher_title'])): ?><small class="text-muted"><?= esc($eval['teacher_title']) ?></small><?php endif; ?>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-3" title="<?= esc($eval['created_at']) ?>"><?= \CodeIgniter\I18n\Time::parse($eval['created_at'])->humanize() ?></small>
                                                <?php if(auth()->id() == $eval['teacher_id']): ?>
                                                    <button class="btn btn-sm btn-outline-primary me-1 edit-evaluation-btn" data-id="<?= $eval['id'] ?>"><i class="bi bi-pencil"></i></button>
                                                <?php endif; ?>
                                                <?php if(auth()->id() == $eval['teacher_id'] || auth()->user()->inGroup('admin', 'yonetici')): ?>
                                                    <button class="btn btn-sm btn-outline-danger delete-evaluation-btn" data-id="<?= $eval['id'] ?>"><i class="bi bi-trash"></i></button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="mt-2 mb-0" style="white-space: pre-wrap;"><?= esc($eval['evaluation']) ?></p>
                                    </div>
                                </div>
                                <?php if ($index < count($evaluations) - 1): ?>
                                    <hr>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted p-4"><p>Bu öğrenci için henüz bir gelişim notu eklenmemiş.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evaluationModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="evaluation-form">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="<?= esc($student['id']) ?>">
                    <input type="hidden" id="evaluation-id" name="evaluation_id" value="">
                    <div class="mb-3">
                        <label for="evaluation-text" class="form-label">Gözlemleriniz:</label>
                        <textarea class="form-control" id="evaluation-text" name="evaluation" rows="10" required></textarea>
                    </div>
                    <div id="form-alert" class="alert d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-success" id="save-evaluation-btn">Kaydet</button>
                </div>
            </form>
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
    // =================================================================
    // ÇALIŞAN KODLARIN (VANILLA JAVASCRIPT)
    // =================================================================
    document.addEventListener('DOMContentLoaded', function () {
        // PDF Viewer Logic
        const reportModalEl = document.getElementById('reportModal');
        if (reportModalEl) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js`;
            const pdfCanvas = document.getElementById('pdf-canvas');
            const loadingSpinner = document.getElementById('pdf-loading');
            const pageNumSpan = document.getElementById('page-num');
            const pageCountSpan = document.getElementById('page-count');
            const prevPageBtn = document.getElementById('prev-page');
            const nextPageBtn = document.getElementById('next-page');
            let pdfDoc = null, pageNum = 1, pageIsRendering = false, pageNumPending = null;
            const renderPage = num => {
                pageIsRendering = true;
                if (!pdfDoc) return;
                pdfDoc.getPage(num).then(page => {
                    const viewport = page.getViewport({ scale: 1.5 });
                    pdfCanvas.height = viewport.height;
                    pdfCanvas.width = viewport.width;
                    const renderContext = { canvasContext: pdfCanvas.getContext('2d'), viewport: viewport };
                    page.render(renderContext).promise.then(() => {
                        pageIsRendering = false;
                        if (pageNumPending !== null) { renderPage(pageNumPending); pageNumPending = null; }
                    });
                });
                if(pageNumSpan) pageNumSpan.textContent = num;
            };
            const queueRenderPage = num => {
                if (pageIsRendering) { pageNumPending = num; } else { renderPage(num); }
            };
            const showPrevPage = () => { if (pageNum <= 1) return; pageNum--; queueRenderPage(pageNum); };
            const showNextPage = () => { if (pageNum >= pdfDoc.numPages) return; pageNum++; queueRenderPage(pageNum); };
            if(prevPageBtn) prevPageBtn.addEventListener('click', showPrevPage);
            if(nextPageBtn) nextPageBtn.addEventListener('click', showNextPage);
            reportModalEl.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const pdfUrl = button.getAttribute('data-src');
                if (!pdfUrl) return;
                loadingSpinner.style.display = 'block';
                pdfCanvas.style.display = 'none';
                pdfjsLib.getDocument(pdfUrl).promise.then(doc => {
                    pdfDoc = doc;
                    if(pageCountSpan) pageCountSpan.textContent = doc.numPages;
                    pageNum = 1;
                    renderPage(pageNum);
                    loadingSpinner.style.display = 'none';
                    pdfCanvas.style.display = 'block';
                }).catch(err => {
                    console.error('PDF yüklenirken hata oluştu: ', err);
                    if(loadingSpinner) loadingSpinner.innerHTML = '<p class="text-danger">Rapor yüklenemedi.</p>';
                });
            });
            reportModalEl.addEventListener('hidden.bs.modal', function () { pdfDoc = null; });
        }

        // Delete Student Modal Script
        const deleteModalEl = document.getElementById('deleteStudentModal');
        if (deleteModalEl) {
            const studentName = "<?= esc($student['adi'] . ' ' . $student['soyadi']) ?>";
            const confirmInput = deleteModalEl.querySelector('#studentNameConfirm');
            const confirmButton = deleteModalEl.querySelector('#confirmDeleteButton');
            if (confirmInput) {
                confirmInput.addEventListener('input', function() {
                    if(confirmButton) confirmButton.disabled = this.value.trim().toLowerCase() !== studentName.toLowerCase();
                });
                deleteModalEl.addEventListener('hidden.bs.modal', function () {
                    confirmInput.value = '';
                    if(confirmButton) confirmButton.disabled = true;
                });
            }
        }
    });

    // =================================================================
    // ÇALIŞAN JQUERY KODLARI (GELİŞİM GÜNLÜĞÜ)
    // =================================================================
    $(document).ready(function() {
        const evaluationModal = new bootstrap.Modal(document.getElementById('evaluationModal'));
        const modalLabel = $('#evaluationModalLabel');
        const form = $('#evaluation-form');
        const evaluationIdInput = $('#evaluation-id');
        const evaluationTextInput = $('#evaluation-text');
        const saveBtn = $('#save-evaluation-btn');
        const formAlert = $('#form-alert');

        // "Yeni Not Ekle" butonu
        $('#addNewEvaluationBtn').on('click', function() {
            form.trigger('reset');
            evaluationIdInput.val('');
            modalLabel.html('<i class="bi bi-plus-circle"></i> Yeni Gelişim Notu Ekle');
            form.attr('action', '<?= route_to('evaluations.create') ?>');
            form.find('input[name="_method"]').remove();
            evaluationModal.show();
        });

        // "Düzenle" butonu
        $('#evaluation-list').on('click', '.edit-evaluation-btn', function() {
            const id = $(this).data('id');
            $.get('<?= route_to('evaluations.get', 0) ?>'.replace('0', id), function(response) {
                if(response.success) {
                    form.trigger('reset');
                    modalLabel.html('<i class="bi bi-pencil-square"></i> Gelişim Notunu Düzenle');
                    evaluationIdInput.val(response.data.id);
                    evaluationTextInput.val(response.data.evaluation);
                    form.attr('action', '<?= route_to('evaluations.update', 0) ?>'.replace('0', id));
                    if (!form.find('input[name="_method"]').length) {
                        form.append('<input type="hidden" name="_method" value="POST">');
                    }
                    evaluationModal.show();
                }
            });
        });

        // Formu Kaydetme (Yeni veya Güncelleme)
        form.on('submit', function(e) {
            e.preventDefault();
            let url = $(this).attr('action');
            saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...');
            formAlert.addClass('d-none');
            $.ajax({
                url: url, type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(response) {
                    if (response.success) { window.location.reload(); } 
                    else {
                        formAlert.removeClass('d-none').addClass('alert-danger').text(response.message);
                        saveBtn.prop('disabled', false).text('Kaydet');
                    }
                },
                error: function() {
                    formAlert.removeClass('d-none').addClass('alert-danger').text('Sunucu hatası!');
                    saveBtn.prop('disabled', false).text('Kaydet');
                }
            });
        });

        // FİLTRELEME BUTONU
        $('#apply-teacher-filter').on('click', function() {
            var selectedTeacherId = $('#teacher-filter').val();
            if (selectedTeacherId === 'all') {
                $('.evaluation-wrapper').show();
            } else {
                $('.evaluation-wrapper').hide();
                $('.evaluation-wrapper[data-teacher-id="' + selectedTeacherId + '"]').show();
            }
        });

        // DEĞERLENDİRME SİLME
        $('#evaluation-list').on('click', '.delete-evaluation-btn', function() {
            var evaluationId = $(this).data('id');
            if (confirm('Bu gelişim notunu silmek istediğinizden emin misiniz?')) {
                $.ajax({
                    url: '<?= route_to('evaluations.delete', 0) ?>'.replace('0', evaluationId),
                    type: 'POST',
                    data: { '<?= csrf_token() ?>': '<?= csrf_hash() ?>' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) { window.location.reload(); } 
                        else { alert(response.message || 'Bir hata oluştu.'); }
                    },
                    error: function() { alert('Sunucuyla iletişim kurulamadı.'); }
                });
            }
        });
    });
</script>
<?= $this->endSection() ?>