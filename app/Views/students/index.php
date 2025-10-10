<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-backpack2"></i> <?= esc($title) ?></h1>
        
        <a href="<?= site_url('students/new') ?>" class="btn btn-success btn-sm shadow-sm">
            <i class="bi bi-person-plus-fill"></i> Yeni Öğrenci Ekle
        </a>
    </div>

<div class="card shadow mb-3">
    <div class="card-header py-2" data-bs-toggle="collapse" href="#collapse-filters" role="button" aria-expanded="true" aria-controls="collapse-filters" style="cursor: pointer;">
        <h6 class="m-0 fw-bold text-success d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-funnel-fill"></i> Filtreleme Seçenekleri
            </span>
            <i class="bi bi-chevron-down filter-chevron"></i>
        </h6>
    </div>
    <div class="collapse" id="collapse-filters">
        <div class="card-body">
            <form action="<?= site_url('students') ?>" method="get" id="filter-form">
                <div class="row g-2 align-items-end">
                    
                    <div class="col-lg-2 col-md-4">
                        <label for="district_id" class="form-label small">İlçe</label>
                        <select name="district_id" id="district_id" class="form-select form-select-sm">
                            <option value="">Tüm İlçeler</option>
                            <?php foreach($districts as $district): ?>
                                <option value="<?= $district->id ?>" <?= ($selected_district == $district->id) ? 'selected' : '' ?>>
                                    <?= esc($district->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="mesafe" class="form-label small">Mesafe</label>
                        <select name="mesafe" id="mesafe" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option value="Civar" <?= ($selected_mesafe == 'Civar') ? 'selected' : '' ?>>Civar</option>
                            <option value="Yakın" <?= ($selected_mesafe == 'Yakın') ? 'selected' : '' ?>>Yakın</option>
                            <option value="Uzak" <?= ($selected_mesafe == 'Uzak') ? 'selected' : '' ?>>Uzak</option>
                        </select>
                    </div>
                    
                    <div class="col-lg-3 col-md-4">
                        <label for="egitim_programi" class="form-label small">Eğitim Programı</label>
                        <select name="egitim_programi" id="egitim_programi" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <?php foreach($egitim_programlari as $program): ?>
                            <option value="<?= esc($program) ?>" <?= ($selected_program == $program) ? 'selected' : '' ?>>
                                <?= esc($program) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-6">
                        <label for="sabit_durum" class="form-label small">Sabit Program</label>
                        <select name="sabit_durum" id="sabit_durum" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option value="eklenen" <?= ($selected_sabit_durum == 'eklenen') ? 'selected' : '' ?>>Eklenenler</option>
                            <option value="eklenmeyen" <?= ($selected_sabit_durum == 'eklenmeyen') ? 'selected' : '' ?>>Eklenmeyenler</option>
                        </select>
                    </div>

                    <div class="col-lg-1 col-md-6" id="sabit_gun_div" style="display: <?= ($selected_sabit_durum == 'eklenen') ? 'block' : 'none' ?>;">
                        <label for="sabit_gun" class="form-label small">Günü</label>
                        <select name="sabit_gun" id="sabit_gun" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option value="1" <?= ($selected_sabit_gun == '1') ? 'selected' : '' ?>>Pzt</option>
                            <option value="2" <?= ($selected_sabit_gun == '2') ? 'selected' : '' ?>>Salı</option>
                            <option value="3" <?= ($selected_sabit_gun == '3') ? 'selected' : '' ?>>Çrş</option>
                            <option value="4" <?= ($selected_sabit_gun == '4') ? 'selected' : '' ?>>Perş</option>
                            <option value="5" <?= ($selected_sabit_gun == '5') ? 'selected' : '' ?>>Cuma</option>
                            <option value="6" <?= ($selected_sabit_gun == '6') ? 'selected' : '' ?>>Cmt</option>
                            <option value="7" <?= ($selected_sabit_gun == '7') ? 'selected' : '' ?>>Paz</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-12">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-success btn-sm">Filtrele</button>
                            <a href="<?= site_url('students') ?>" class="btn btn-secondary btn-sm" title="Filtreyi Temizle"><i class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="card shadow">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Öğrenci Listesi</h4>
</div>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="studentsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Fotoğraf</th>
                            <th>Adı Soyadı</th>
                            <th class="d-none d-lg-table-cell">Eğitim Programları</th>
                            <th class="d-none d-md-table-cell">Ulaşılabilir Veli</th>
                            <th class="d-none d-lg-table-cell text-center">RAM Raporu</th>
                            <th style="width: 120px;" class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="align-middle text-center">
                                <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" 
                                     alt="<?= esc($student['adi']) ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                            </td>
                            <td class="align-middle fw-bold">
                                <?= esc($student['adi']) . ' ' . esc($student['soyadi']) ?>
                                <div class="d-md-none mt-1">
                                    <?php
                                    $programs = explode(',', $student['egitim_programi'] ?? '');
                                    foreach ($programs as $program):
                                        $program = trim($program);
                                        if (empty($program)) continue;
                                        $badgeClass = 'bg-secondary'; $badgeHarf = '?';
                                        switch ($program) {
                                            case 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-danger'; $badgeHarf = 'F'; break;
                                            case 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-primary'; $badgeHarf = 'D'; break;
                                            case 'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-success'; $badgeHarf = 'Z'; break;
                                            case 'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-warning text-dark'; $badgeHarf = 'Ö'; break;
                                            case 'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-info text-dark'; $badgeHarf = 'O'; break;
                                        }
                                    ?>
                                    <span class="badge rounded-pill <?= $badgeClass ?>" title="<?= esc($program) ?>"><?= $badgeHarf ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="align-middle d-none d-lg-table-cell">
                                <?php
                                $programs = explode(',', $student['egitim_programi'] ?? '');
                                foreach ($programs as $program):
                                    $program = trim($program);
                                    if (empty($program)) continue;
                                    $badgeClass = 'bg-secondary'; $badgeHarf = '?';
                                    switch ($program) {
                                        case 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-danger'; $badgeHarf = 'F'; break;
                                        case 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-primary'; $badgeHarf = 'D'; break;
                                        case 'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-success'; $badgeHarf = 'Z'; break;
                                        case 'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-warning text-dark'; $badgeHarf = 'Ö'; break;
                                        case 'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-info text-dark'; $badgeHarf = 'O'; break;
                                    }
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass ?>" title="<?= esc($program) ?>"><?= $badgeHarf ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td class="align-middle d-none d-md-table-cell">
                                <?php
                                $veliAdi = 'N/A'; $veliTelefon = 'Telefon eklenmemiş'; $veliEtiket = '';
                                if (!empty(trim((string)($student['veli_anne_telefon'] ?? '')))) {
                                    $veliAdi = esc($student['veli_anne'] ?? 'Anne Adı Yok'); $veliTelefon = esc($student['veli_anne_telefon']); $veliEtiket = '(Anne)';
                                } elseif (!empty(trim((string)($student['veli_baba_telefon'] ?? '')))) {
                                    $veliAdi = esc($student['veli_baba'] ?? 'Baba Adı Yok'); $veliTelefon = esc($student['veli_baba_telefon']); $veliEtiket = '(Baba)';
                                }
                                ?>
                                <div><i class="bi bi-person-fill text-muted"></i> <?= $veliAdi ?> <span class="text-muted small"><?= $veliEtiket ?></span></div>
                                <div class="small text-muted"><i class="bi bi-telephone-fill text-muted"></i> <?= $veliTelefon ?></div>
                            </td>
                            <td class="align-middle text-center d-none d-lg-table-cell">
                                <?php if (!empty($student['ram_raporu'])): ?>
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>" data-student-name="<?= esc($student['adi'] . ' ' . $student['soyadi']) ?>" title="RAM Raporunu Görüntüle">
                                        <i class="bi bi-file-earmark-pdf-fill"></i> RAM Raporu
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-octagon"></i> Rapor Yok</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-center">
                                <a href="<?= site_url('students/' . $student['id']) ?>" class="btn btn-success btn-sm" title="Görüntüle"><i class="bi bi-eye-fill"></i></a>
                                <a href="<?= site_url('students/' . $student['id'] . '/edit') ?>" class="btn btn-warning btn-sm" title="Düzenle"><i class="bi bi-pencil-fill"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportModalLabel">RAM Raporu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="height: 80vh;">
        <iframe id="report-iframe" src="" style="width: 100%; height: 100%;" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<style>
    /* Açılır/kapanır filtre başlığındaki ok ikonu için animasyon */
    .filter-chevron {
        transition: transform 0.3s ease-in-out;
    }
    [aria-expanded="false"] .filter-chevron {
        transform: rotate(-90deg);
    }
</style>
<script>
        $(document).ready(function() {
        // DataTable'ı başlat
        const studentsTable = $('#studentsTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
            "paging": true,
            "searching": true,
            "info": true,
            "lengthChange": true,
            "pageLength": 10,
            "order": [[ 1, "asc" ]], 
            "columnDefs": [
                { "type": "turkish", "targets": "_all" }
            ],
        });

        // Arama kutusunu büyük harfe zorlama fonksiyonunu çağır
        forceUppercaseSearch(studentsTable);

    });

    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const pdfUrl = button.getAttribute('data-src');
            const studentName = button.getAttribute('data-student-name');
            const modalTitle = reportModal.querySelector('.modal-title');
            const iframe = document.getElementById('report-iframe');
            
            modalTitle.textContent = studentName + ' - RAM Raporu';
            iframe.setAttribute('src', pdfUrl);
        });

        reportModal.addEventListener('hidden.bs.modal', function () {
            const iframe = document.getElementById('report-iframe');
            iframe.setAttribute('src', '');
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
    const filterCollapseElement = document.getElementById('collapse-filters');
        
        // Bootstrap'in Collapse bileşenini toggle özelliğini kapatarak manuel olarak başlatıyoruz.
        // Bu, sayfa yüklenirken otomatik açılıp kapanmasını engeller.
        const bsCollapse = new bootstrap.Collapse(filterCollapseElement, {
            toggle: false 
        });

        // 1. Sayfa yüklendiğinde localStorage'ı kontrol et ve durumu ayarla
        if (localStorage.getItem('filterCollapseState') === 'open') {
            bsCollapse.show();
        } else {
            // Hiçbir kayıt yoksa veya 'closed' ise kapalı kalmasını sağla
            bsCollapse.hide();
        }

        // 2. Filtre bölümü AÇILDIĞINDA bu durumu kaydet
        filterCollapseElement.addEventListener('show.bs.collapse', function () {
            localStorage.setItem('filterCollapseState', 'open');
        });

        // 3. Filtre bölümü KAPANDIĞINDA bu durumu kaydet
        filterCollapseElement.addEventListener('hide.bs.collapse', function () {
            localStorage.setItem('filterCollapseState', 'closed');
        });
        // --- YENİ KOD SONU ---
        const tomSelectConfig = {
            create: false,
            render: {
                option: function(data, escape) {
                    return `<div class="option">${escape(data.text)}</div>`;
                }
            }
        };

        new TomSelect('#district_id',{ ...tomSelectConfig, sortField: { field: "text", direction: "asc" } });
        new TomSelect('#mesafe', tomSelectConfig);
        new TomSelect('#sabit_durum', tomSelectConfig);
        new TomSelect('#sabit_gun', tomSelectConfig);
        new TomSelect('#egitim_programi', { ...tomSelectConfig, placeholder: 'Programa göre filtrele...' });

        // Sabit Program filtresi değiştiğinde Günü göster/gizle
        $('#sabit_durum').on('change', function() {
            if ($(this).val() === 'eklenen') {
                $('#sabit_gun_div').slideDown('fast');
            } else {
                $('#sabit_gun_div').slideUp('fast');
                if (document.getElementById('sabit_gun').tomselect) {
                    document.getElementById('sabit_gun').tomselect.setValue('');
                }
            }
        });
    });

</script>
<?= $this->endSection() ?>