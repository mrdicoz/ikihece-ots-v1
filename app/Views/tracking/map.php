<?= $this->extend('layouts/map') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<!-- Harita Container -->
<div id="map"></div>

<!-- Mobil Panel Tetikleyici -->
<button class="mobile-menu-trigger d-lg-none" id="mobile-panel-trigger" type="button" 
        data-bs-toggle="offcanvas" data-bs-target="#controlPanel">
    <i class="bi bi-list"></i>
</button>

<!-- Sol Kontrol Paneli -->
<div class="offcanvas-start control-panel" tabindex="-1" id="controlPanel">
    <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title text-success">
            <i class="bi bi-geo-alt-fill"></i> Servis Takip
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    
    <div class="offcanvas-body">
        <div class="panel-wrapper">
            <!-- Desktop Header -->
            <div class="panel-header d-none d-lg-flex">
                <h5 class="mb-0 text-success">
                    <i class="bi bi-geo-alt-fill"></i> Servis Takip
                </h5>
                <span class="badge bg-success"><?= $driverCount ?> Servis</span>
            </div>
            
            <!-- Panel İçeriği -->
            <div class="panel-body">
                <?php if ($driverCount > 1): ?>
                    <!-- Çoklu Servis Seçimi -->
                    <div class="mb-3">
                        <label class="form-label small text-muted">İzlenecek Servisleri Seçin</label>
                        <select id="driverSelect" multiple>
                            <?php foreach ($drivers as $driver): ?>
                            <option value="<?= $driver['id'] ?>">
                                <?= esc($driver['first_name'] . ' ' . $driver['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($driverCount === 1 && !empty($drivers)): ?>
                    <!-- Tek Servis -->
                    <div class="alert alert-success p-2 mb-3">
                        <i class="bi bi-person-check-fill"></i>
                        <strong><?= esc($drivers[0]['first_name'] . ' ' . $drivers[0]['last_name']) ?></strong>
                        <div class="small">Otomatik takip aktif</div>
                    </div>
                    <input type="hidden" id="singleDriverId" value="<?= $drivers[0]['id'] ?>">
                <?php else: ?>
                    <!-- Servis Yok -->
                    <div class="alert alert-warning p-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Aktif servis bulunamadı.</strong>
                    </div>
                <?php endif; ?>
                
                <!-- Kontrol Butonları -->
                <div class="control-buttons">
                    <button class="btn btn-outline-primary btn-sm w-100" id="autoZoomBtn">
                        <i class="bi bi-aspect-ratio"></i> 
                        <span id="autoZoomText">Otomatik Yakınlaştırma: Açık</span>
                    </button>
                    
                    <?php if ($driverCount > 0): ?>
                    <button class="btn btn-outline-info btn-sm w-100" id="showAllBtn">
                        <i class="bi bi-pin-map"></i> Tümünü Göster
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($companyLocation)): ?>
                    <button class="btn btn-outline-secondary btn-sm w-100" id="focusCompanyBtn">
                        <i class="bi bi-building"></i> Kuruma Odaklan
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Güncelleme Aralığı -->
                <div class="update-interval">
                    <label class="form-label small text-muted">Güncelleme Aralığı</label>
                    <select class="form-select form-select-sm" id="updateIntervalSelect">
                        <option value="5000">5 saniye</option>
                        <option value="10000">10 saniye</option>
                        <option value="15000" selected>15 saniye</option>
                        <option value="30000">30 saniye</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Desktop Panel Toggle Butonu -->
    <button class="panel-toggle d-none d-lg-block" id="panelToggleBtn">
        <i class="bi bi-chevron-left"></i>
    </button>
</div>

<!-- Sağ Durum Kartları -->
<div class="status-cards">
    <div class="status-card">
        <div class="pulse-indicator live"></div>
        <div>
            <div class="fw-bold">Canlı Takip</div>
            <div class="small text-muted" id="liveTime">Başlatılıyor...</div>
        </div>
    </div>
    
    <?php if ($driverCount > 0): ?>
    <div class="status-card">
        <i class="bi bi-bus-front-fill text-success fs-5"></i>
        <div>
            <div class="fw-bold" id="activeCount">0</div>
            <div class="small text-muted">Aktif Servis</div>
        </div>
    </div>
    <?php endif; ?>
    
<?php if (!empty($companyLocation)): ?>
<div class="status-card" id="etaCard" style="display: none;">
    <i class="bi bi-clock-history text-primary fs-5"></i>
    <div>
        <div class="fw-bold" id="etaTime">-- dk</div>
        <div class="small text-muted">Kuruma <span id="etaInfo">En Yakın</span></div>
    </div>
</div>
<?php endif; ?>
</div>

<!-- Tam Ekran Butonu -->
<button class="fullscreen-btn" id="fullscreenBtn">
    <i class="bi bi-arrows-fullscreen"></i>
</button>
<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    /* ============ PANEL STİLLERİ ============ */
    
    /* Mobil Tetikleyici */
    .mobile-menu-trigger {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 1000;
        background: white;
        border: none;
        border-radius: 8px;
        padding: 10px 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .mobile-menu-trigger:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    
    /* Desktop Panel (LG+) */
    @media (min-width: 992px) {
        .control-panel {
            position: absolute !important;
            top: calc(var(--navbar-height) + 15px);
            left: 15px;
            width: var(--panel-width);
            height: auto;
            max-height: calc(100vh - var(--navbar-height) - 30px);
            background: transparent;
            border: none;
            transform: translateX(0);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }
        
        /* Tam ekran modunda panel pozisyonu */
        .fullscreen-mode .control-panel {
            top: 15px;
            max-height: calc(100vh - 30px);
        }
        
        /* Panel gizlendiğinde */
        .control-panel.collapsed {
            transform: translateX(calc(-100% - 15px));
        }
        
        .offcanvas-body {
            padding: 0;
            overflow: visible !important;
        }
        
        .panel-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-body {
            padding: 20px;
            width: 320px;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        /* Geliştirilmiş Toggle Butonu */
        .panel-toggle {
            position: absolute;
            right: -35px;
            top: 50%;
            transform: translateY(-50%);
            width: 35px;
            height: 70px;
            background: white;
            border: none;
            border-radius: 0 12px 12px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .panel-toggle::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(25,135,84,0.1));
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .panel-toggle:hover::before {
            transform: translateX(0);
        }
        
        .panel-toggle:hover {
            box-shadow: 3px 0 15px rgba(0,0,0,0.15);
            background: #f8f9fa;
        }
        
        .panel-toggle i {
            font-size: 18px;
            color: #198754;
            transition: transform 0.3s ease;
        }
        
        .control-panel.collapsed .panel-toggle i {
            transform: rotate(180deg);
        }
    }
    
    /* ============ DURUM KARTLARI ============ */
    .status-cards {
        position: absolute;
        top: calc(var(--navbar-height) + 15px);
        right: 15px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .fullscreen-mode .status-cards {
        top: 15px;
    }
    
    .status-card {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        padding: 12px 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 180px;
        animation: slideInRight 0.4s ease;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Pulse Animasyonu */
    .pulse-indicator {
        width: 12px;
        height: 12px;
        background: #6c757d;
        border-radius: 50%;
        position: relative;
    }
    
    .pulse-indicator.live {
        background: #10b981;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }
    
    /* ============ TAM EKRAN BUTONU ============ */
    .fullscreen-btn {
        position: absolute;
        bottom: 30px;
        right: 20px;
        z-index: 1000;
        width: 46px;
        height: 46px;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .fullscreen-btn:hover {
        transform: scale(1.1);
        border-color: #198754;
        box-shadow: 0 4px 15px rgba(25,135,84,0.2);
    }
    
    .fullscreen-btn i {
        font-size: 20px;
        color: #495057;
        transition: color 0.3s ease;
    }
    
    .fullscreen-btn:hover i {
        color: #198754;
    }
    
    /* ============ KONTROL BUTONLARI ============ */
    .control-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 20px;
    }
    
    .update-interval {
        padding-top: 15px;
        border-top: 1px solid rgba(0,0,0,0.1);
    }
    
    /* ============ MARKER STİLLERİ ============ */
    .bus-marker {
        background: transparent !important;
        border: none !important;
    }
    
    .bus-icon-wrapper {
        position: relative;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.25));
        transform-origin: center bottom;
    }
    
    .bus-icon-main {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
        position: relative;
    }
    
    .bus-icon-main i {
        transform: rotate(45deg);
        color: white;
        font-size: 20px;
    }
    
    /* Servis durumları */
    .bus-icon-wrapper.idle .bus-icon-main {
        background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%);
    }
    
    .bus-icon-wrapper.offline .bus-icon-main {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    }
    
    .driver-label {
        position: absolute;
        bottom: 35px;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        border: 1px solid #e5e7eb;
    }
    
    .eta-badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #0d6efd;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        white-space: nowrap;
        z-index: 1;
        box-shadow: 0 2px 4px rgba(13,110,253,0.3);
    }

    .update-time-badge {
    position: absolute;
    bottom: -50px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(108, 117, 125, 0.95);
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 3px;
}

.update-time-badge i {
    font-size: 9px;
}

/* Aktif durumda yeşil arka plan */
.bus-icon-wrapper.active .update-time-badge {
    background: rgba(25, 135, 84, 0.95);
}

/* Offline durumda kırmızı arka plan */
.bus-icon-wrapper.offline .update-time-badge {
    background: rgba(220, 53, 69, 0.95);
}

/* İkon boyutunu ayarla */
.bus-marker {
    margin-bottom: 20px !important;
}

        .update-badge {
        position: absolute;
        bottom: -45px;
        left: 50%;
        transform: translateX(-50%);
        background: #6c757d;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 9px;
        font-weight: 500;
        white-space: nowrap;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    
    /* Kurum Marker */
    .company-marker-wrapper {
        position: relative;
    }
    
    .company-marker {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        animation: company-pulse 2.5s infinite;
    }
    
    @keyframes company-pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.6);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 15px rgba(220, 53, 69, 0);
        }
    }
    
    /* ============ RESPONSİVE ============ */
    @media (max-width: 991px) {
        .status-cards {
            top: 15px;
            flex-direction: row;
            right: auto;
            left: 70px;
        }
        
        .status-card {
            min-width: auto;
        }
        
        .fullscreen-btn {
            bottom: 80px;
        }
    }
    
    @media (max-width: 576px) {
        .status-cards {
            display: none;
        }
        
        .fullscreen-btn {
            bottom: 20px;
            right: 15px;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
// Wayne Tech Tracking System v2.0
class TrackingSystem {
    constructor() {
        this.map = null;
        this.markers = {};
        this.companyMarker = null;
        this.autoZoom = true;
        this.updateTimer = null;
        this.isFullscreen = false;
        this.driverCount = <?= $driverCount ?? 0 ?>;
        this.companyLocation = <?= json_encode($companyLocation ?? null) ?>;
        this.drivers = <?= json_encode($drivers ?? []) ?>;
        this.tomSelect = null;
        
        this.init();
    }
    
    startLiveTimeUpdater() {
    // Her saniye saati güncelle
    setInterval(() => {
        const liveTimeEl = document.getElementById('liveTime');
        if (liveTimeEl) {
            liveTimeEl.textContent = new Date().toLocaleTimeString('tr-TR');
        }
    }, 1000);
}

    init() {
        this.initMap();
        this.initControls();
        this.bindEvents();
        this.startTracking();
        this.startLiveTimeUpdater(); // YENİ SATIR

    }
    
    initMap() {
        // Harita oluştur
        this.map = L.map('map', {
            center: [
                this.companyLocation?.lat || 40.7889,
                this.companyLocation?.lng || 30.4008
            ],
            zoom: 13,
            zoomControl: false,
            preferCanvas: true
        });
        
        // Tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(this.map);
        
        // Zoom kontrolü
        L.control.zoom({
            position: 'bottomright'
        }).addTo(this.map);
        
        // Kurum marker'ı
        if (this.companyLocation?.lat && this.companyLocation?.lng) {
            this.addCompanyMarker();
        }
        
        // Harita boyutunu ayarla
        setTimeout(() => this.map.invalidateSize(), 100);
    }
    
    addCompanyMarker() {
        const icon = L.divIcon({
            className: 'company-marker-wrapper',
            html: `<div class="company-marker">
                      <i class="bi bi-building text-white fs-5"></i>
                   </div>`,
            iconSize: [44, 44],
            iconAnchor: [22, 22]
        });
        
        this.companyMarker = L.marker(
            [this.companyLocation.lat, this.companyLocation.lng],
            { icon: icon, zIndexOffset: 1000 }
        ).addTo(this.map);
        
        this.companyMarker.bindPopup(`
            <div class="text-center p-2">
                <h6 class="text-danger mb-1">${this.companyLocation.name || 'Merkez'}</h6>
                <small>${this.companyLocation.address || ''}</small>
            </div>
        `);
    }
    
    initControls() {
        // Tom Select (çoklu servis durumu)
        if (this.driverCount > 1) {
            this.tomSelect = new TomSelect('#driverSelect', {
                plugins: ['remove_button'],
                placeholder: 'Servis seçin...',
                maxItems: null
            });
            
            this.tomSelect.on('change', () => {
                this.stopTimer();
                this.updateDriverLocations();
                this.startTimer();
            });
        }
    }
    
    bindEvents() {
        // Panel toggle (desktop)
        document.getElementById('panelToggleBtn')?.addEventListener('click', () => {
            document.getElementById('controlPanel').classList.toggle('collapsed');
        });
        
        // Auto zoom
        document.getElementById('autoZoomBtn')?.addEventListener('click', () => {
            this.autoZoom = !this.autoZoom;
            document.getElementById('autoZoomText').textContent = 
                `Otomatik Yakınlaştırma: ${this.autoZoom ? 'Açık' : 'Kapalı'}`;
            if (this.autoZoom) this.fitBounds();
        });
        
        // Tümünü göster
        document.getElementById('showAllBtn')?.addEventListener('click', () => {
            if (this.driverCount > 1 && this.tomSelect) {
                const allValues = this.drivers.map(d => d.id.toString());
                this.tomSelect.setValue(allValues);
            }
            this.updateDriverLocations();
        });
        
        // Kuruma odaklan
        document.getElementById('focusCompanyBtn')?.addEventListener('click', () => {
            if (this.companyMarker) {
                this.map.setView(this.companyMarker.getLatLng(), 16);
                this.companyMarker.openPopup();
            }
        });
        
        // Güncelleme aralığı
        document.getElementById('updateIntervalSelect')?.addEventListener('change', (e) => {
            this.stopTimer();
            this.startTimer();
        });
        
        // Tam ekran
        document.getElementById('fullscreenBtn')?.addEventListener('click', () => {
            this.toggleFullscreen();
        });
        
        // Fullscreen API events
        document.addEventListener('fullscreenchange', () => {
            this.handleFullscreenChange();
        });
    }
    
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().then(() => {
                document.body.classList.add('fullscreen-mode');
                document.getElementById('fullscreenIcon').className = 'bi bi-arrows-angle-contract';
            });
        } else {
            document.exitFullscreen().then(() => {
                document.body.classList.remove('fullscreen-mode');
                document.getElementById('fullscreenIcon').className = 'bi bi-arrows-fullscreen';
            });
        }
        
        // Harita boyutunu güncelle
        setTimeout(() => this.map.invalidateSize(), 300);
    }
    
    handleFullscreenChange() {
        this.isFullscreen = !!document.fullscreenElement;
        
        // Harita boyutunu güncelle
        setTimeout(() => {
            this.map.invalidateSize();
            if (this.autoZoom) this.fitBounds();
        }, 300);
    }
    
async updateDriverLocations() {
    let driverIds = [];
    
    // Sürücü ID'lerini belirle
    if (this.driverCount === 1) {
        const singleId = document.getElementById('singleDriverId')?.value;
        if (singleId) driverIds.push(singleId);
    } else if (this.driverCount > 1 && this.tomSelect) {
        driverIds = this.tomSelect.getValue();
    }
    
    if (driverIds.length === 0) {
        this.clearMarkers();
        this.updateStatusCards(0, null);
        return;
    }
    
    try {
        const response = await fetch(`<?= site_url('api/location/drivers') ?>?drivers=${driverIds.join(',')}`);
        const drivers = await response.json();
        
        this.updateMarkers(drivers);
        
        // En yakın sürücüyü bul
        const closestDriver = this.findClosestDriver(drivers);
        this.updateStatusCards(drivers.length, closestDriver);
        
        // Durum güncellemeleri
        document.querySelector('.pulse-indicator')?.classList.add('live');
        document.getElementById('liveTime').textContent = new Date().toLocaleTimeString('tr-TR');
        
    } catch (error) {
        console.error('Konum güncellemesi başarısız:', error);
        document.querySelector('.pulse-indicator')?.classList.remove('live');
    }
}
    
    updateMarkers(drivers) {
    const receivedIds = drivers.map(d => d.user_id.toString());
    
    // Eski marker'ları temizle
    Object.keys(this.markers).forEach(id => {
        if (!receivedIds.includes(id)) {
            if (this.markers[id]) {
                this.map.removeLayer(this.markers[id]);
                delete this.markers[id];
            }
        }
    });
    
    // Marker'ları güncelle veya oluştur
    drivers.forEach(driver => {
        const status = this.getDriverStatus(driver.updated_at);
        
        // Her seferinde yeni ikon oluştur (güncelleme zamanını göstermek için)
        const icon = this.createDriverIcon(driver, status);
        
        if (this.markers[driver.user_id]) {
            // Marker'ı kaldır ve yeniden oluştur
            this.map.removeLayer(this.markers[driver.user_id]);
        }
        
        // Yeni marker oluştur
        this.markers[driver.user_id] = L.marker(
            [driver.latitude, driver.longitude],
            { icon: icon, zIndexOffset: 1000 }
        ).addTo(this.map);
        
        // Popup güncelle
        this.markers[driver.user_id].bindPopup(this.createPopupContent(driver));
    });
    
    // Otomatik yakınlaştırma
    if (this.autoZoom && Object.keys(this.markers).length > 0) {
        this.fitBounds();
    }
}
    
createDriverIcon(driver, status) {
    // Son güncelleme metnini hazırla
    const updateText = driver.last_update_text || 'Bilinmiyor';
    
    const html = `
        <div class="bus-icon-wrapper ${status}">
            <div class="bus-icon-main">
                <i class="bi bi-bus-front-fill"></i>
            </div>
            <div class="driver-label">${driver.first_name}</div>
            ${driver.eta ? `<div class="eta-badge">${driver.eta.text}</div>` : ''}
        </div>
    `;
    
    return L.divIcon({
        className: 'bus-marker',
        html: html,
        iconSize: [60, 100],
        iconAnchor: [30, 70]
    });
}
    
createPopupContent(driver) {
    return `
        <div class="p-2">
            <h6 class="text-success mb-2">
                <i class="bi bi-person-circle"></i> 
                ${driver.first_name} ${driver.last_name}
            </h6>
            ${driver.eta ? `
                <div class="d-flex justify-content-between small mb-1">
                    <span>Mesafe:</span>
                    <strong>${driver.eta.distance} km</strong>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span>Tahmini:</span>
                    <strong class="text-primary">${driver.eta.text}</strong>
                </div>
            ` : ''}
            <div class="text-muted small">
                <i class="bi bi-speedometer2"></i> ${driver.speed || 0} km/s<br>
                <i class="bi bi-clock"></i> ${driver.last_update_text || 'Bilinmiyor'}
            </div>
        </div>
    `;
}
    
    getDriverStatus(updatedAt) {
        const now = new Date();
        const updated = new Date(updatedAt);
        const diffMinutes = (now - updated) / 60000;
        
        if (diffMinutes < 2) return 'active';
        if (diffMinutes < 5) return 'idle';
        return 'offline';
    }
    
  findClosestDriver(drivers) {
    if (!this.companyLocation || drivers.length === 0) return null;
    
    return drivers.reduce((closest, driver) => {
        if (!driver.eta) return closest;
        if (!closest || driver.eta.minutes < closest.eta.minutes) {
            return driver;
        }
        return closest;
    }, null);
}
    
    updateStatusCards(count, closestDriver) {
        // Aktif servis sayısı
        const activeCountEl = document.getElementById('activeCount');
        if (activeCountEl) activeCountEl.textContent = count;

        // ETA kartı
        const etaCard = document.getElementById('etaCard');
        if (etaCard && this.companyLocation) {
            if (closestDriver && closestDriver.eta) {
                etaCard.style.display = 'flex';
                document.getElementById('etaTime').textContent = closestDriver.eta.text;
                // Sürücü adını da gösterebiliriz
                const etaInfo = document.getElementById('etaInfo');
                if (etaInfo) {
                    etaInfo.textContent = closestDriver.first_name;
                }
            } else {
                etaCard.style.display = 'none';
            }
        }
    }
    
    clearMarkers() {
        Object.values(this.markers).forEach(marker => {
            if (marker) this.map.removeLayer(marker);
        });
        this.markers = {};
    }
    
    fitBounds() {
        const allMarkers = Object.values(this.markers);
        if (this.companyMarker) allMarkers.push(this.companyMarker);
        
        if (allMarkers.length > 0) {
            const group = new L.featureGroup(allMarkers);
            this.map.fitBounds(group.getBounds().pad(0.15));
        }
    }
    
    startTimer() {
        const interval = parseInt(
            document.getElementById('updateIntervalSelect')?.value || 15000
        );
        
        if (!this.updateTimer) {
            this.updateTimer = setInterval(() => {
                this.updateDriverLocations();
            }, interval);
        }
    }
    
    stopTimer() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }
    
    startTracking() {
        // İlk güncelleme
        this.updateDriverLocations();
        
        // Pulse animasyonunu başlat
        document.querySelector('.pulse-indicator')?.classList.add('live');
        
        // Timer'ı başlat
        this.startTimer();
    }
}

// Sistem başlat
document.addEventListener('DOMContentLoaded', () => {
    window.trackingSystem = new TrackingSystem();
});
</script>
<?= $this->endSection() ?>