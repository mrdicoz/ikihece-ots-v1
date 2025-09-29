<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-bus-front text-success"></i> Servis Paneli
        </h1>
        <div class="text-muted">
            <i class="bi bi-calendar-day"></i> <?= date('d F Y') ?>
        </div>
    </div>

    <!-- Üst Widget Kartları -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Bugün Ders Alan Öğrenci
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($stats['total_students']) ?> Kişi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Servis Kullanan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($stats['with_service']) ?> Kişi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-bus-front-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Civar
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($stats['civar']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-geo-alt-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Yakın
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($stats['yakin']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-geo-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Uzak
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($stats['uzak']) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-geo fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Öğrenci Listesi -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">
                <i class="bi bi-list-ul"></i> Bugün Ders Alan Öğrenciler
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($todayStudents)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="serviceStudentsTable" width="100%">
                        <thead>
                            <tr>
                                <th>Öğrenci</th>
                                <th>Adres</th>
                                <th class="text-center">Servis</th>
                                <th class="text-center">Mesafe</th>
                                <th>İletişim</th>
                                <th class="text-center">Konum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayStudents as $student): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>" 
                                             alt="<?= esc($student['adi']) ?>" 
                                             class="rounded-circle me-2" 
                                             width="40" height="40" 
                                             style="object-fit: cover;">
                                        <div>
                                            <div class="fw-bold"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        <?= esc($student['adres'] ?? 'Adres bilgisi yok') ?><br>
                                        <span class="text-muted">
                                            <?= esc($student['district_name'] ?? '') ?> / <?= esc($student['city_name'] ?? '') ?>
                                        </span>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if ($student['servis'] === 'var'): ?>
                                        <span class="badge bg-success">Var</span>
                                    <?php elseif ($student['servis'] === 'arasira'): ?>
                                        <span class="badge bg-warning">Arasıra</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Yok</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($student['mesafe'] === 'civar'): ?>
                                        <span class="badge bg-info">Civar</span>
                                    <?php elseif ($student['mesafe'] === 'yakın'): ?>
                                        <span class="badge bg-warning text-dark">Yakın</span>
                                    <?php elseif ($student['mesafe'] === 'uzak'): ?>
                                        <span class="badge bg-danger">Uzak</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php if (!empty($student['veli_anne_telefon'])): ?>
                                            <a href="tel:<?= esc($student['veli_anne_telefon']) ?>" class="text-decoration-none">
                                                <i class="bi bi-telephone-fill"></i> <?= esc($student['veli_anne_telefon']) ?>
                                            </a><br>
                                        <?php endif; ?>
                                        <?php if (!empty($student['veli_baba_telefon'])): ?>
                                            <a href="tel:<?= esc($student['veli_baba_telefon']) ?>" class="text-decoration-none">
                                                <i class="bi bi-telephone"></i> <?= esc($student['veli_baba_telefon']) ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($student['veli_anne_telefon']) && empty($student['veli_baba_telefon'])): ?>
                                            <span class="text-muted">Telefon yok</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($student['adres'])): ?>
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($student['adres'] . ' ' . $student['district_name'] . ' ' . $student['city_name']) ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-success"
                                           title="Haritada Göster">
                                            <i class="bi bi-map"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-info-circle"></i> Bugün ders alan öğrenci bulunmuyor.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
$(document).ready(function() {
    $('#serviceStudentsTable').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
        "pageLength": 25,
        "order": [[0, 'asc']], // İsme göre alfabetik
        "columnDefs": [
            { "orderable": true, "targets": [0, 1, 2, 3] },
            { "orderable": false, "targets": [4, 5] }
        ]
    });
});
</script>
<?= $this->endSection() ?>