<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
<!-- Başlık ve Aksiyonlar -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-success">
        <i class="bi bi-bus-front-fill"></i> Servis Yönetim Paneli
    </h1>
    <div>
        <button id="gprsButton" class="btn btn-success me-2">
            <i class="bi bi-broadcast"></i> GPRS Konum Göndermeye Başla
        </button>
        <a href="<?= base_url('tracking/map') ?>" class="btn btn-outline-success">
            <i class="bi bi-geo-alt-fill"></i> Canlı Takip Haritası
        </a>
    </div>
</div>

<!-- GPRS Durum Mesajı -->
<div class="row mb-3">
    <div class="col-12">
        <div id="gprsStatus" class="alert alert-secondary text-center" style="display:none;">
            <i class="bi bi-info-circle"></i> GPRS durumu burada görünecek
        </div>
    </div>
</div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Öğrenci
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
                                Zaman Dilimleri
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($stats['total_groups']) ?> Grup
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
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

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Yakın / Uzak
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($stats['yakin']) ?> / <?= esc($stats['uzak']) ?>
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

    <!-- Saate Göre Gruplandırılmış Öğrenciler -->
    <?php if (!empty($groupedByTime)): ?>
        <?php foreach ($groupedByTime as $time => $students): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="bi bi-alarm-fill"></i> Ders Saati: <?= esc(substr($time, 0, 5)) ?>
                    </h6>
                    <span class="badge bg-white text-success">
                        <?= count($students) ?> Öğrenci
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Öğrenci</th>
                                <th width="30%">Adres</th>
                                <th width="10%" class="text-center">Mesafe</th>
                                <th width="20%">İletişim</th>
                                <th width="10%" class="text-center">Konum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $index + 1 ?></span>
                                </td>
                                <td>
                                    <a href="#" 
                                       class="text-decoration-none" 
                                       data-bs-toggle="popover" 
                                       data-bs-html="true"
                                       data-bs-trigger="click"
                                       data-bs-content="
                                           <div class='text-center'>
                                               <img src='<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>' class='rounded mb-2' width='120' height='120' style='object-fit: cover;'>
                                               <h6><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></h6>
                                               <a href='tel:<?= esc($student['iletisim'] ?? $student['veli_anne_telefon']) ?>' class='btn btn-sm btn-success mb-2 w-100'>
                                                   <i class='bi bi-telephone'></i> Ara
                                               </a>
                                               <?php if (!empty($student['google_konum'])): ?>
                                               <a href='<?= esc($student['google_konum']) ?>' target='_blank' class='btn btn-sm btn-primary w-100'>
                                                   <i class='bi bi-geo-alt'></i> Konuma Git
                                               </a>
                                               <?php endif; ?>
                                           </div>
                                       ">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>" 
                                                 alt="<?= esc($student['adi']) ?>" 
                                                 class="rounded-circle me-2" 
                                                 width="40" height="40" 
                                                 style="object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></div>
                                                <small class="text-muted"><?= esc(substr($student['start_time'], 0, 5)) ?> - <?= esc(substr($student['end_time'], 0, 5)) ?></small>
                                            </div>
                                        </div>
                                    </a>
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
                                    <?php if ($student['mesafe'] === 'civar'): ?>
                                        <span class="badge bg-info">Civar</span>
                                    <?php elseif ($student['mesafe'] === 'yakın'): ?>
                                        <span class="badge bg-warning text-dark">Yakın</span>
                                    <?php elseif ($student['mesafe'] === 'uzak'): ?>
                                        <span class="badge bg-danger">Uzak</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php if (!empty($student['iletisim'])): ?>
                                            <a href="tel:<?= esc($student['iletisim']) ?>" class="text-decoration-none">
                                                <i class="bi bi-telephone-fill"></i> <?= esc($student['iletisim']) ?>
                                            </a><br>
                                        <?php endif; ?>
                                        <?php if (!empty($student['veli_anne_telefon'])): ?>
                                            <a href="tel:<?= esc($student['veli_anne_telefon']) ?>" class="text-decoration-none">
                                                <i class="bi bi-telephone-fill"></i> Anne: <?= esc($student['veli_anne_telefon']) ?>
                                            </a><br>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($student['google_konum'])): ?>
                                        <a href="<?= esc($student['google_konum']) ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-map"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle fs-1"></i>
            <p class="mt-2 mb-0">Bugün servis kullanan öğrenci bulunmuyor.</p>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
$(document).ready(function() {
    // Popover'ları aktif et
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl, {
        html: true,
        sanitize: false
    }));
});

// GPRS Konum Gönderimi
let gprsInterval = null;
let isTracking = false;

$('#gprsButton').click(function() {
    if (!isTracking) {
        startTracking();
    } else {
        stopTracking();
    }
});

function startTracking() {
    if (!navigator.geolocation) {
        showGprsStatus('Tarayıcınız konum desteği vermiyor!', 'danger');
        return;
    }
    
    isTracking = true;
    $('#gprsButton')
        .removeClass('btn-success')
        .addClass('btn-danger')
        .html('<i class="bi bi-stop-circle"></i> Konum Göndermeyi Durdur');
    
    // İlk konumu hemen gönder
    sendLocation();
    
    // 30 saniyede bir konum gönder
    gprsInterval = setInterval(() => {
        sendLocation();
    }, 30000);
}

function stopTracking() {
    clearInterval(gprsInterval);
    isTracking = false;
    $('#gprsButton')
        .removeClass('btn-danger')
        .addClass('btn-success')
        .html('<i class="bi bi-broadcast"></i> GPRS Konum Göndermeye Başla');
    
    showGprsStatus('Konum gönderimi durduruldu', 'secondary');
}

function sendLocation() {
    navigator.geolocation.getCurrentPosition(
        (position) => {
            $.post('<?= base_url('api/location/save') ?>', {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            })
            .done(() => {
                const time = new Date().toLocaleTimeString();
                showGprsStatus(
                    `<i class="bi bi-check-circle-fill"></i> Konum başarıyla gönderildi - ${time}`, 
                    'success'
                );
            })
            .fail(() => {
                showGprsStatus(
                    '<i class="bi bi-exclamation-triangle-fill"></i> Konum gönderilemedi!', 
                    'danger'
                );
            });
        },
        (error) => {
            showGprsStatus(
                `<i class="bi bi-x-circle-fill"></i> Konum alınamadı: ${error.message}`, 
                'warning'
            );
        }
    );
}

function showGprsStatus(message, type) {
    $('#gprsStatus')
        .removeClass('alert-success alert-danger alert-warning alert-secondary alert-info')
        .addClass(`alert-${type}`)
        .html(message)
        .slideDown();
}
</script>

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
</style>
<?= $this->endSection() ?>