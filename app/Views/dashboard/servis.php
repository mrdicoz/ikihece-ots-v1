<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4 d-print-none" id="screen-view">

<div class="row align-items-center mb-4 g-3">

    
    <div class="col-md-5">
        <h1 class="h3 mb-0 text-success"><i class="bi bi-bus-front-fill"></i> Servis Planı</h1>
        <span class="text-muted"><?= esc($formattedDate) ?></span>
    </div>

    
    <div class="col-md-3">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
            <input type="date" id="date-selector" class="form-control" value="<?= esc($currentDate) ?>" aria-label="Tarih Seç">
        </div>
    </div>

    
    <div class="col-md-4 text-md-end">
        <div class="btn-group" role="group" aria-label="Sayfa Aksiyonları">
            <button id="printButton" class="btn btn-secondary">
                <i class="bi bi-printer-fill"></i> Yazdır
            </button>

            <?php if (auth()->loggedIn() && auth()->user()->inGroup('servis')): ?>
                <button id="gprsButton" class="btn btn-success">
                    <i class="bi bi-broadcast"></i> Konum Aç
                </button>
            <?php endif; ?>
            
        </div>
    </div>

</div>
    
    <div id="gprsStatus" class="alert alert-secondary text-center" style="display:none;"></div>

    <?php if (!empty($groupedByTime)): ?>
        <?php foreach ($groupedByTime as $time => $students): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-alarm-fill"></i> Ders Saati: <?= esc(substr($time, 0, 5)) ?></h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr data-start-time="<?= esc(substr($student['start_time'], 0, 5)) ?>">
                                <td class="text-center" style="width: 80px;"><img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>" class="rounded-circle" width="50" height="50" style="object-fit: cover;"></td>
                                <td>
                                    <button type="button" class="btn btn-link text-decoration-none text-dark fw-bold p-0" data-bs-toggle="popover" data-bs-trigger="click" data-bs-placement="top" data-bs-custom-class="student-popover" data-bs-html="true" data-bs-content="<div class='text-center p-2'><img src='<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>' class='rounded mb-3' width='140' height='140' style='object-fit: cover;'><p class='mb-2 text-start'><?= esc($student['adres'] ?? 'Adres bilgisi yok') ?></p><a href='tel:<?= esc($student['iletisim'] ?? $student['veli_anne_telefon']) ?>' class='btn btn-sm btn-success w-100 mb-2'><i class='bi bi-telephone-fill'></i> Telefonla Ara</a><?php if (!empty($student['google_konum'])): ?><a href='<?= esc($student['google_konum']) ?>' target='_blank' rel='noopener noreferrer' class='btn btn-sm btn-primary w-100'><i class='bi bi-geo-alt-fill'></i> Haritada Göster</a><?php endif; ?></div>">
                                        <?= esc($student['adi'] . ' ' . $student['soyadi']) ?>
                                    </button>
                                    <small class="d-block text-muted"><?= esc($student['adres']) ?></small>
                                </td>
                                <td class="text-center d-none d-md-table-cell"><span class="badge bg-<?= ($student['servis'] === 'var') ? 'primary' : 'info' ?>"><?= ($student['servis'] === 'var') ? 'Servis Var' : 'Arasıra' ?></span></td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php $mesafe_class = 'info'; if ($student['mesafe'] === 'yakın') $mesafe_class = 'warning text-dark'; if ($student['mesafe'] === 'uzak') $mesafe_class = 'danger'; ?>
                                    <span class="badge bg-<?= $mesafe_class ?>"><?= esc(ucfirst($student['mesafe'])) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($student['google_konum'])): ?>
                                        <a href="<?= esc($student['google_konum']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success"><i class="bi bi-map-fill"></i></a>
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
        <div class="alert alert-info text-center mt-4">Seçilen tarih için servis kullanan öğrenci bulunmuyor.</div>
    <?php endif; ?>
</div>
<div class="d-none d-print-block" id="print-view">
    <!-- Basit Başlık -->
    <div style="text-align: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #000;">
        <h4 style="margin: 0; font-size: 14pt; font-weight: bold; text-transform: uppercase;">
            <?= esc($formattedDate) ?> SERVİS PLANI
        </h4>
    </div>

    <!-- Kompakt Tablo -->
    <table class="print-table-minimal">
        <thead>
            <tr>
                <th style="width: 6%;">Saat</th>
                <th style="width: 7%;">Foto</th>
                <th style="width: 20%;">Ad Soyad</th>
                <th style="width: 49%;">Adres</th>
                <th style="width: 18%;">Telefon</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($groupedByTime)): ?>
                <?php foreach ($groupedByTime as $time => $students): ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;">
                            <?= esc(substr($time, 0, 5)) ?>
                        </td>
                        <td style="text-align: center;">
                            <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>" 
                                 width="32" height="32" style="border-radius: 50%; object-fit: cover;">
                        </td>
                        <td style="font-weight: 600;">
                            <?= esc($student['adi'] . ' ' . $student['soyadi']) ?>
                        </td>
                        <td style="font-size: 7.5pt;">
                            <?= esc($student['adres']) ?>
                            <span style="color: #666; font-style: italic;">
                                (<?= esc($student['district_name']) ?>)
                            </span>
                        </td>
                        <td style="font-weight: 500;">
                            <?= esc($student['iletisim'] ?? $student['veli_anne_telefon'] ?? $student['veli_baba_telefon']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">
                        Bu tarih için servis planı bulunmuyor.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
// --- TÜM JAVASCRIPT KODLARI BURADA, TEMİZ VE TEK BİR BLOK İÇİNDE ---

// Değişkenleri en üstte tanımlayalım
let gprsInterval = null; 
let isTracking = false; 
let periodicSyncRegistered = false;

// Sayfa yüklendiğinde çalışacak ana fonksiyon
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tarih seçici event'i
    document.getElementById('date-selector').addEventListener('change', function() {
        if (this.value) {
            window.location.href = "<?= site_url('dashboard/servis') ?>" + '?date=' + this.value;
        }
    });

    // 2. Yazdır butonu event'i
    document.getElementById('printButton').addEventListener('click', function() {
        window.print();
    });

    // 3. Popover'ları aktif et
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            html: true,
            sanitize: false
        });
    });

    // 4. Dinamik renklendirmeyi sadece bugün için çalıştır
    if ("<?= esc($currentDate) ?>" === "<?= date('Y-m-d') ?>") {
        updateRowStyles();
        setInterval(updateRowStyles, 60000); // Her dakika güncelle
    }

    // 5. GPRS butonu event'i (jQuery ile)
    $('#gprsButton').click(function(){ 
        isTracking ? stopTracking() : startTracking(); 
    });
});

// Dinamik renklendirme fonksiyonu
function updateRowStyles() {
    const now = new Date();
    const currentTime = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);

    document.querySelectorAll('tbody tr[data-start-time]').forEach(row => {
        const startTime = row.dataset.startTime;
        const endTime = new Date(now.toDateString() + ' ' + startTime);
        endTime.setMinutes(endTime.getMinutes() + 40); // 40 dakika ders süresi
        const endTimeStr = ('0' + endTime.getHours()).slice(-2) + ':' + ('0' + endTime.getMinutes()).slice(-2);
        
        row.classList.remove('table-success', 'table-danger', 'table-secondary');

        if (currentTime >= startTime && currentTime <= endTimeStr) {
            row.classList.add('table-success'); // Aktif ders
        } else if (currentTime > endTimeStr) {
            row.classList.add('table-danger'); // Geçmiş ders
        } else {
            row.classList.add('table-secondary'); // Gelecek ders
        }
    });
}

// --- GPRS ve Konum Fonksiyonları ---

async function startTracking(){ 
    if(!navigator.geolocation || !('serviceWorker' in navigator)){
        showGprsStatus('Tarayıcınız konum veya PWA özelliklerini desteklemiyor!', 'danger');
        return;
    } 
    isTracking=true; 
    $('#gprsButton').removeClass('btn-success').addClass('btn-danger').html('<i class="bi bi-stop-circle"></i> Konum Kapat'); 
    
    sendLocation(); 
    
    try { 
        const registration = await navigator.serviceWorker.ready; 
        if('periodicSync' in registration){ 
            const status = await navigator.permissions.query({name:'periodic-background-sync'}); 
            if(status.state==='granted'){
                await registration.periodicSync.register('periodic-location-sync',{minInterval:30*1000}); 
                periodicSyncRegistered=true; 
                showGprsStatus('✓ Arka plan takibi aktif (PWA)', 'success');
            } else {
                startNormalInterval();
            }
        } else {
            startNormalInterval();
        }
    } catch(error) {
        startNormalInterval();
    }
}

function startNormalInterval(){ 
    gprsInterval = setInterval(sendLocation, 30000); // 30 saniye
}

async function stopTracking(){ 
    clearInterval(gprsInterval); 
    isTracking=false; 
    $('#gprsButton').removeClass('btn-danger').addClass('btn-success').html('<i class="bi bi-broadcast"></i> Konum Aç'); 
    if(periodicSyncRegistered){ 
        try { 
            const registration = await navigator.serviceWorker.ready; 
            if('periodicSync' in registration){ 
                await registration.periodicSync.unregister('periodic-location-sync'); 
                periodicSyncRegistered=false; 
            }
        } catch(error) {
            console.error('Periodic sync iptal hatası:', error);
        }
    } 
    showGprsStatus('Konum gönderimi durduruldu','secondary');
}

function sendLocation(){ 
    navigator.geolocation.getCurrentPosition( 
        (position) => { 
            const lat = position.coords.latitude; 
            const lng = position.coords.longitude; 
            $.post('<?= base_url('api/location/save') ?>',{latitude:lat,longitude:lng})
            .done(() => { 
                const time = new Date().toLocaleTimeString(); 
                showGprsStatus(`<i class="bi bi-check-circle-fill"></i> Konum gönderildi - ${time}`,'success');
            })
            .fail(() => { 
                showGprsStatus('<i class="bi bi-exclamation-triangle-fill"></i> Konum gönderilemedi! (Çevrimdışı)', 'danger'); 
                if('serviceWorker' in navigator && 'SyncManager' in window){
                    navigator.serviceWorker.ready.then(reg => reg.sync.register('sync-location'));
                }
            });
        }, 
        (error) => { 
            showGprsStatus(`<i class="bi bi-x-circle-fill"></i> Konum alınamadı: ${error.message}`,'warning');
        }
    );
}

function showGprsStatus(message, type){
    $('#gprsStatus').removeClass().addClass(`alert alert-${type} text-center`).html(message).slideDown();
}

window.addEventListener('beforeunload', function(e){
    if(isTracking && !periodicSyncRegistered){
        e.preventDefault(); 
        e.returnValue = '⚠️ Konum takibi aktif! Sayfa kapatılırsa takip duracak.';
    }
});
</script>

<style>
    /* Ekran görünümü stilleri */
    tbody tr { 
        transition: background-color 0.5s ease-in-out; 
    }
    
    .student-popover { 
        max-width: 280px !important; 
    }
    
    .table-success { --bs-table-bg: #d1e7dd; --bs-table-border-color: #a3cfbb; }
    .table-danger { --bs-table-bg: #f8d7da; --bs-table-border-color: #f1aeb5; }
    .table-secondary { --bs-table-bg: #e2e3e5; --bs-table-border-color: #c6c8ca; }

    /* ==================================================================== */
    /* YAZDIRMA - DENGELİ */
    /* ==================================================================== */
    @media print {
        /* Sayfa Ayarları */
        @page {
            size: A4 portrait;
            margin: 8mm 5mm; /* Üst-Alt: 8mm, Sol-Sağ: 5mm */
        }

        /* Renk koruması */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Ekran elemanlarını gizle */
        body > nav,
        body > main > footer,
        #screen-view {
            display: none !important;
        }

        /* Print view göster */
        #print-view {
            display: block !important;
        }

        /* Body ayarları */
        body {
            margin: 0;
            padding: 0;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        /* Main container */
        main {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Bootstrap container düzeltmeleri */
        .container,
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Print view container */
        #print-view {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Başlık */
        #print-view > div:first-child {
            page-break-after: avoid;
        }

        h4 {
            page-break-after: avoid;
        }

        /* Ana Tablo - TAM GENİŞLİK */
        .print-table-minimal {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
            margin: 0;
        }

        /* Thead - Her sayfada tekrarlanır */
        .print-table-minimal thead {
            display: table-header-group;
        }

        .print-table-minimal thead th {
            background-color: #000 !important;
            color: #fff !important;
            border: 1px solid #000 !important;
            padding: 4px 3px;
            font-weight: bold;
            font-size: 9pt;
            text-align: center;
        }

        /* Body */
        .print-table-minimal tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .print-table-minimal tbody td {
            border: 1px solid #333 !important;
            padding: 3px 4px;
            background-color: #fff !important;
            color: #000 !important;
            vertical-align: middle;
        }

        /* Zebra striping */
        .print-table-minimal tbody tr:nth-child(even) {
            background-color: #f5f5f5 !important;
        }

        /* Saat kolonu */
        .print-table-minimal tbody td:nth-child(1) {
            font-size: 9pt;
        }

        /* Foto kolonu */
        .print-table-minimal tbody td:nth-child(2) {
            padding: 2px !important;
        }

        /* Ad Soyad kolonu */
        .print-table-minimal tbody td:nth-child(3) {
            font-size: 9pt;
        }

        /* Adres kolonu */
        .print-table-minimal tbody td:nth-child(4) {
            line-height: 1.3;
        }

        /* Telefon kolonu */
        .print-table-minimal tbody td:nth-child(5) {
            font-size: 8pt;
        }

        /* Fotoğraflar - Siyah beyaz */
        img {
            filter: grayscale(100%);
            page-break-inside: avoid;
        }

        /* Bağlantı URL'lerini gösterme */
        a[href]:after {
            content: none !important;
        }

        /* Orphans ve widows */
        .print-table-minimal tbody tr {
            orphans: 2;
            widows: 2;
        }
    }
</style>
<?= $this->endSection() ?>