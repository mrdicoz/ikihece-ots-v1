<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-check"></i> Öğrenci Devamsızlık Raporu</h1>
        
        <div class="mt-2 mt-sm-0">
            <div class="d-grid d-sm-flex gap-2">
                <a href="<?= site_url('students/' . $student['id']) ?>" class="btn btn-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left"></i> Öğrenci Profili'ne Dön
                </a>
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
                            <i class="bi bi-eye-fill"></i> RAM Raporu Görüntüle
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary w-100 mt-2" disabled>
                            <i class="bi bi-eye-slash-fill"></i> RAM Raporu Yok
                        </button>
                    <?php endif; ?>
                    <a href="<?= site_url('students/' . $student['id'] . '/edit') ?>" class="btn btn-success w-100 mt-2">
                        <i class="bi bi-pencil-square"></i> Bilgileri Düzenle
                    </a>
                    <?php if (auth()->user()->inGroup('admin', 'yonetici', 'mudur', 'sekreter')): ?>
                    <a href="<?= site_url('students/' . $student['id'] . '/attendance') ?>" class="btn btn-success w-100 mt-2">
                        <i class="bi bi-calendar-check"></i> Devamsızlık Raporu
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="card-title mb-0"><?= esc($title) ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('students/' . $student['id'] . '/attendance') ?>" method="get" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="year" class="form-label">Yıl Seçin</label>
                                <select name="year" id="year" class="form-control">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?= $y ?>" <?= ($selectedYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="month" class="form-label">Ay Seçin</label>
                                <?php
                                $monthNames = [
                                    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                                    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                                    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
                                ];
                                ?>
                                <select name="month" id="month" class="form-control">
                                    <?php foreach ($monthNames as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= ($selectedMonth == $num) ? 'selected' : '' ?>><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-success">Filtrele</button>
                            </div>
                        </div>
                    </form>

                    <hr>

                    <h4 class="text-success">Katıldığı Dersler</h4>
                    <?php if (!empty($attendance)): ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Öğretmen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $att): ?>
                                    <tr>
                                        <td><?= \CodeIgniter\I18n\Time::parse($att['lesson_date'])->toLocalizedString('dd MMMM yyyy') ?></td>
                                        <td><?= substr($att['start_time'], 0, 5) ?> - <?= substr($att['end_time'], 0, 5) ?></td>
                                        <td><?= esc($att['first_name'] . ' ' . $att['last_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Seçilen ay ve yıl için katıldığı ders bulunmamaktadır.</p>
                    <?php endif; ?>

                    <h4 class="mt-4 text-danger">Devamsızlıklar</h4>
                    <?php if (!empty($absences)): ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Sebep</th>
                                    <th>Öğretmen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($absences as $abs): ?>
                                    <tr>
                                        <td><?= \CodeIgniter\I18n\Time::parse($abs['lesson_date'])->toLocalizedString('dd MMMM yyyy') ?></td>
                                        <td><?= substr($abs['start_time'], 0, 5) ?> - <?= substr($abs['end_time'], 0, 5) ?></td>
                                        <td><?= esc($abs['reason']) ?></td>
                                        <td><?= esc($abs['first_name'] . ' ' . $abs['last_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Seçilen ay ve yıl için devamsızlık kaydı bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals from show.php if needed, e.g., reportModal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="reportModalLabel"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?> - RAM Raporu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
</script>
<?= $this->endSection() ?>