<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-speedometer2"></i> Yönetim Paneli</h1>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-success shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Toplam Öğrenci</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['students']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people-fill fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-secondary shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Toplam Öğretmen</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['teachers']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-workspace fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-success shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Toplam Veli</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['parents']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-vcard-fill fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-secondary shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Toplam Servis</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['services']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-bus-front-fill fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                     <h6 class="m-0 fw-bold text-success"><i class="bi bi-megaphone-fill"></i> Hızlı Duyuru Paneli</h6>
                </div>
                <div class="card-body">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="announcementTitle" placeholder="Duyuru Başlığı">
                        <label for="announcementTitle">Duyuru Başlığı</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select class="form-select" id="announcementTarget" aria-label="Hedef Kitle">
                            <option selected>Gönderilecek kitleyi seçin</option>
                            <option value="parents">Tüm Velilere</option>
                            <option value="teachers">Tüm Öğretmenlere</option>
                        </select>
                        <label for="announcementTarget">Kime?</label>
                    </div>
                    <div class="form-floating">
                        <textarea class="form-control" placeholder="Duyuru mesajınızı buraya yazın" id="announcementMessage" style="height: 120px"></textarea>
                        <label for="announcementMessage">Duyuru Mesajı</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="button" class="btn btn-success" disabled data-bs-toggle="tooltip" data-bs-placement="top" title="Bu özellik yakında aktif olacaktır.">
                        <i class="bi bi-send-fill"></i> Gönder
                    </button>
                </div>
            </div>

        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-success"><i class="bi bi-backpack2-fill"></i> Öğrenci Listesi</h6>
                    
                    <div class="ms-auto">
                        <div class="btn-group" role="group" aria-label="Hızlı Ekleme Butonları">
                            <?php if (auth()->user()->can('ogrenciler.ekle')): ?>
                                <a href="<?= site_url('students/new') ?>" class="btn btn-sm btn-outline-success" title="Yeni Öğrenci Ekle">
                                    <i class="bi bi-person-plus-fill"> Yeni Öğrenci Ekle</i>
                                </a>
                            <?php endif; ?>

                            <?php if (auth()->user()->can('kullanicilar.yonet')): ?>
                                <a href="<?= route_to('admin.users.new') ?>" class="btn btn-sm btn-outline-secondary" title="Yeni Kullanıcı Ekle">
                                    <i class="bi bi-person-add"> Yeni Kullanıcı Ekle</i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="studentDashboardTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="d-none d-md-table-cell" style="width: 50px;">Fotoğraf</th>
                                    <th class="d-none d-md-table-cell">Adı Soyadı</th>
                                    <th class="d-none d-md-table-cell">Veli Adı</th>
                                    <th class="d-md-none">Öğrenci Bilgileri</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr data-href="<?= site_url('students/' . $student['id']) ?>" style="cursor: pointer;">
                                    <?php 
                                        // Veli adını önceden hesapla
                                        $veli_adi = !empty($student['veli_anne_adi_soyadi']) 
                                                    ? $student['veli_anne_adi_soyadi'] 
                                                    : ($student['veli_baba_adi_soyadi'] ?? 'Belirtilmemiş');
                                    ?>

                                    <td class="d-none d-md-table-cell">
                                        <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" 
                                             alt="<?= esc($student['adi']) ?>" 
                                             class="rounded-circle" width="40" height="40" 
                                             style="object-fit: cover;">
                                    </td>
                                    <td class="d-none d-md-table-cell"><?= esc($student['adi']) . ' ' . esc($student['soyadi']) ?></td>
                                    <td class="d-none d-md-table-cell"><?= esc($veli_adi) ?></td>

                                    <td class="d-md-none">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" 
                                                 alt="<?= esc($student['adi']) ?>" 
                                                 class="rounded-circle me-3" width="40" height="40" 
                                                 style="object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?= esc($student['adi']) . ' ' . esc($student['soyadi']) ?></div>
                                                <div class="small text-muted">Veli: <?= esc($veli_adi) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        $('#studentDashboardTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
            "pageLength": 10,
            "searching": true,
            "lengthChange": false,
            "info": false,
            "columnDefs": [
                { "orderable": false, "searchable": false, "targets": 0 }
            ]
        });

        $('#studentDashboardTable tbody').on('click', 'tr', function() {
            window.location.href = $(this).data('href');
        });

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
<?= $this->endSection() ?>