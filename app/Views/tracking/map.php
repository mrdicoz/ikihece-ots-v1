<?php
// =================================================================
// REFACTOR NOTU:
// Kontrol paneli içeriğini (sürücü listesi ve butonlar)
// tekrar etmemek için bir yardımcı fonksiyon oluşturuldu.
// Bu fonksiyon hem masaüstü hem de mobil panelde çağrılıyor.
// =================================================================
function renderControlPanelBody($drivers, $driverCount, $companyLocation, $isMobile = false) {
    // Mobil ve masaüstü için farklı ID'ler oluşturmak amacıyla sonek belirleniyor.
    $suffix = $isMobile ? 'Mobile' : 'Desktop';
?>
    <div class="panel-body">
        <?php if ($driverCount > 1): ?>
            <div class="mb-3">
                <label class="form-label small text-muted">İzlenecek Servisleri Seçin</label>
                <select class="driver-select" multiple>
                    <?php foreach ($drivers as $driver): ?>
                    <option value="<?= $driver['id'] ?>">
                        <?= esc($driver['first_name'] . ' ' . $driver['last_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif ($driverCount === 1 && !empty($drivers)): ?>
            <div class="alert alert-light p-2 mb-3 border">
                <i class="bi bi-person-check-fill text-success"></i>
                <strong><?= esc($drivers[0]['first_name'] . ' ' . $drivers[0]['last_name']) ?></strong>
                <div class="small text-muted">Otomatik takip aktif</div>
            </div>
            <input type="hidden" id="singleDriverId" value="<?= $drivers[0]['id'] ?>">
        <?php else: ?>
            <div class="alert alert-warning p-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Aktif servis bulunamadı.</strong>
            </div>
        <?php endif; ?>

        <div class="control-buttons">
            <?php if ($driverCount > 0): ?>
                <button class="btn btn-outline-info btn-sm w-100" data-action="show-all">
                    <i class="bi bi-pin-map"></i> Tümünü Göster
                </button>
            <?php endif; ?>

            <?php if (!empty($companyLocation)): ?>
                <button class="btn btn-outline-secondary btn-sm w-100" data-action="focus-company">
                    <i class="bi bi-building"></i> Kuruma Odaklan
                </button>
            <?php endif; ?>
        </div>
    </div>
<?php
}
?>

<?= $this->extend('layouts/map') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div id="map"></div>

<button class="btn btn-light btn-lg d-none d-lg-block" id="fullscreen-btn">
    <i class="bi bi-fullscreen"></i>
</button>

<div class="mobile-controls d-lg-none">
    <button class="mobile-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#leftPanel">
        <i class="bi bi-sliders2"></i>
        <span>Servisler</span>
    </button>
    <button class="mobile-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#bottomPanel">
        <i class="bi bi-card-list"></i>
        <span>Canlı Durum</span>
        <span class="badge rounded-pill bg-danger" id="mobileDriverCount">0</span>
    </button>
</div>

<div class="offcanvas-start control-panel" tabindex="-1" id="controlPanel">
    <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title text-success">
            <i class="bi bi-geo-alt-fill"></i> Servis Takip
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#controlPanel"></button>
    </div>

    <div class="offcanvas-body">
        <div class="panel-wrapper">
            <div class="panel-header d-none d-lg-flex">
                <h5 class="mb-0 text-success">
                    <i class="bi bi-geo-alt-fill"></i> Servis Takip
                </h5>
                <span class="badge bg-success"><?= $driverCount ?> Servis</span>
            </div>
            <?php renderControlPanelBody($drivers, $driverCount, $companyLocation, false); ?>
        </div>
    </div>

    <button class="panel-toggle d-none d-lg-block" id="panelToggleBtn">
        <i class="bi bi-chevron-left"></i>
    </button>
</div>

<div class="offcanvas offcanvas-start control-panel" tabindex="-1" id="leftPanel" aria-labelledby="leftPanelLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-success" id="leftPanelLabel">
            <i class="bi bi-sliders2"></i> Kontrol Paneli
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#leftPanel"></button>
    </div>

    <div class="offcanvas-body">
        <div class="panel-wrapper">
            <?php renderControlPanelBody($drivers, $driverCount, $companyLocation, true); ?>
        </div>
    </div>
</div>

<div class="status-cards d-none d-lg-block">
    <div class="status-card">
        <div class="pulse-indicator live"></div>
        <div>
            <div class="fw-bold">Canlı Takip</div>
            <div class="small text-muted" id="desktopLiveTime">Başlatılıyor...</div>
        </div>
    </div>
    <div id="desktopDriverCards"></div>
</div>

<div class="offcanvas offcanvas-bottom status-panel d-lg-none" tabindex="-1" id="bottomPanel" aria-labelledby="bottomPanelLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="bottomPanelLabel">
            <i class="bi bi-broadcast"></i> Canlı Durum
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#bottomPanel"></button>
    </div>
    <div class="offcanvas-body">
        <div class="live-status-card">
            <div class="pulse-indicator live"></div>
            <div>
                <div class="fw-bold">Canlı Takip</div>
                <div class="small text-muted" id="mobileLiveTime">Başlatılıyor...</div>
            </div>
        </div>
        <div id="mobileDriverCards" class="mt-2"></div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    :root {
        --navbar-height: 56px;
        --panel-width: 340px;
    }
    #map {
        position: absolute; top: 0; left: 0;
        width: 100%; height: 100vh; z-index: 1;
    }
    .mobile-controls {
        position: absolute; bottom: 20px; left: 50%;
        transform: translateX(-50%); z-index: 1000;
        display: flex; gap: 10px;
        background: rgba(255, 255, 255, 0.95);
        padding: 10px; border-radius: 50px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        backdrop-filter: blur(10px);
    }
    .mobile-btn {
        background: white; border: 1px solid #dee2e6; border-radius: 50px;
        padding: 10px 18px; font-weight: 500;
        display: flex; align-items: center; gap: 8px;
        color: #212529; transition: all 0.2s ease;
    }
    .mobile-btn:hover { background: #f8f9fa; transform: translateY(-1px); }
    .mobile-btn .badge { padding: 0.3em 0.6em; }

    @media (min-width: 992px) {
        .control-panel {
            position: fixed !important; top: calc(var(--navbar-height) + 15px); left: 15px;
            width: var(--panel-width); height: auto; max-height: calc(100vh - var(--navbar-height) - 30px);
            background: transparent; border: none;
            transform: translateX(0); transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }
        .control-panel.collapsed { transform: translateX(calc(-100% - 15px)); }
        .control-panel .offcanvas-body { padding: 0; overflow: visible !important; }
        .panel-wrapper {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); overflow: hidden;
        }
        .panel-header {
            padding: 15px 20px; border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center;
        }
        .panel-body { padding: 20px; max-height: calc(100vh - 200px); overflow-y: auto; }
        .panel-toggle {
            position: absolute; right: -35px; top: 50%;
            transform: translateY(-50%); width: 35px; height: 70px;
            background: white; border: none; border-radius: 0 12px 12px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease;
        }
        .panel-toggle:hover { box-shadow: 3px 0 15px rgba(0,0,0,0.15); background: #f8f9fa; }
        .panel-toggle i { font-size: 18px; color: #198754; transition: transform 0.3s ease; }
        .control-panel.collapsed .panel-toggle i { transform: rotate(180deg); }
    }

    @media (max-width: 991.98px) {
        #controlPanel { display: none !important; } /* Masaüstü panelini mobilde gizle */
        #leftPanel { width: 85% !important; max-width: 400px; }
        #leftPanel .offcanvas-body { padding: 0; }
        #leftPanel .panel-wrapper { height: 100%; background: white; }
        #leftPanel .panel-body { padding: 20px; height: 100%; overflow-y: auto; }
    }

    .status-cards {
        position: fixed; top: calc(var(--navbar-height) + 15px); right: 15px;
        z-index: 1000; display: flex; flex-direction: column; gap: 10px;
        max-height: calc(100vh - var(--navbar-height) - 30px); overflow-y: auto;
    }
    .status-card {
        background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
        border-radius: 10px; padding: 12px 16px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        display: flex; align-items: center; gap: 12px; min-width: 280px;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .status-panel { height: 50vh; border-top-left-radius: 16px; border-top-right-radius: 16px; }
    .status-panel .offcanvas-body { display: flex; flex-direction: column; gap: 10px; }
    .live-status-card {
        display: flex; align-items: center; gap: 12px; background: #fff;
        padding: 12px 16px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }
    .driver-card {
        background: #fff; border: 1px solid #e9ecef; transition: all 0.2s ease;
        display: flex; align-items: center; gap: 12px; padding: 12px 16px;
        border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); cursor: pointer;
        margin: 5px;
    }
    .driver-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .driver-card-icon {
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .driver-card-info { flex: 1; min-width: 0; }
    .driver-card-name { font-weight: 700; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .driver-card-details { display: flex; gap: 12px; font-size: 0.85rem; color: #6c757d; margin-top: 4px;}
    .driver-card-eta { font-weight: 600; color: #198754;}
    .pulse-indicator { width: 12px; height: 12px; background: #6c757d; border-radius: 50%; position: relative; }
    .pulse-indicator.live { background: #10b981; animation: pulse 2s infinite; }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .control-buttons { display: flex; flex-direction: column; gap: 8px; }
    .bus-marker { background: transparent !important; border: none !important; }
    .bus-icon-wrapper { position: relative; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.25)); }
    .bus-icon-main {
        width: 48px; height: 48px;
        background: linear-gradient(135deg, #198754 0%, #157347 100%);
        border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
        display: flex; align-items: center; justify-content: center;
        border: 3px solid white;
    }
    .bus-icon-main i { transform: rotate(45deg); color: white; font-size: 20px; }
    .bus-icon-wrapper.idle .bus-icon-main { background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%); }
    .bus-icon-wrapper.offline .bus-icon-main { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); }
    .company-marker-wrapper { position: relative; }
    .company-marker {
        width: 44px; height: 44px;
        background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        border: 3px solid white; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        animation: company-pulse 2.5s infinite;
    }
    @keyframes company-pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.6); }
        50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(220, 53, 69, 0); }
    }

    @media (max-width: 576px) {
        .mobile-controls { bottom: 15px; padding: 8px; }
        .mobile-btn { padding: 8px 14px; font-size: 0.9rem; }
        .status-panel { height: 60vh; }
    }

    /* GÜNCELLENDİ: Tam Ekran Stilleri */
    #fullscreen-btn {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 1001;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #ccc;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    /* Tam ekran modunda sadece navbar'ı gizle */
    body.fullscreen-active .navbar {
        display: none !important;
    }

    /* Haritanın tam ekran olmasını sağla */
    body.fullscreen-active #map {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        z-index: 999;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
// Wayne Tech Tracking System v2.1 (Refactored)
class TrackingSystem {
    constructor() {
        // Configuration from PHP
        this.config = {
            driverCount: <?= $driverCount ?? 0 ?>,
            companyLocation: <?= json_encode($companyLocation ?? null) ?>,
            drivers: <?= json_encode($drivers ?? []) ?>,
            locationsUrl: `<?= site_url('tracking/locations') ?>`,
            updateInterval: 15000 // 15 saniye
        };

        // Leaflet Map instance
        this.map = null;
        this.markers = {};
        this.companyMarker = null;

        // Timers
        this.updateTimer = null;
        this.liveTimeTimer = null;

        // UI Elements (cached for performance)
        this.elements = {
            map: document.getElementById('map'),
            controlPanel: document.getElementById('controlPanel'),
            panelToggleBtn: document.getElementById('panelToggleBtn'),
            desktopCardsContainer: document.getElementById('desktopDriverCards'),
            mobileCardsContainer: document.getElementById('mobileDriverCards'),
            mobileDriverCount: document.getElementById('mobileDriverCount'),
            liveTimeDesktop: document.getElementById('desktopLiveTime'),
            liveTimeMobile: document.getElementById('mobileLiveTime'),
            pulseIndicators: document.querySelectorAll('.pulse-indicator'),
            driverSelects: document.querySelectorAll('.driver-select'), // Ortak class
            showAllBtns: document.querySelectorAll('[data-action="show-all"]'),
            focusCompanyBtns: document.querySelectorAll('[data-action="focus-company"]'),
        };

        // TomSelect instances
        this.tomSelectInstances = [];

        this.init();
    }

    init() {
        this.initMap();
        this.initControls();
        this.bindEvents();
        this.startTracking();
    }

    // =================================================================
    // INITIALIZATION
    // =================================================================

    initMap() {
        this.map = L.map(this.elements.map, {
            center: [this.config.companyLocation?.lat || 40.7889, this.config.companyLocation?.lng || 30.4008],
            zoom: 13,
            zoomControl: false,
            preferCanvas: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(this.map);

        L.control.zoom({ position: 'bottomright' }).addTo(this.map);

        if (this.config.companyLocation?.lat && this.config.companyLocation?.lng) {
            this.addCompanyMarker();
        }

        // Harita boyutunu ayarla
        setTimeout(() => this.map.invalidateSize(), 100);
    }

    initControls() {
        // Tom Select'i tüm ilgili select kutuları için başlat
        if (this.config.driverCount > 1) {
            this.elements.driverSelects.forEach(selectElement => {
                const ts = new TomSelect(selectElement, {
                    plugins: ['remove_button'],
                    placeholder: 'Servis seçin...',
                    maxItems: null
                });

                ts.on('change', () => {
                    this.stopTimer();
                    this.updateDriverLocations();
                    this.startTimer();
                });

                this.tomSelectInstances.push(ts);
            });
        }
    }

    bindEvents() {
        // Panel toggle (desktop)
        this.elements.panelToggleBtn?.addEventListener('click', () => {
            this.elements.controlPanel.classList.toggle('collapsed');
        });

        // Tümünü göster butonları
        this.elements.showAllBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (this.config.driverCount > 1 && this.tomSelectInstances.length > 0) {
                    const allValues = this.config.drivers.map(d => d.id.toString());
                    this.tomSelectInstances.forEach(ts => ts.setValue(allValues));
                } else {
                    this.updateDriverLocations();
                }
            });
        });

        // Kuruma odaklan butonları
        this.elements.focusCompanyBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (this.companyMarker) {
                    this.map.setView(this.companyMarker.getLatLng(), 16);
                    this.companyMarker.openPopup();
                }
            });
        });
    }

    // =================================================================
    // CORE TRACKING LOGIC
    // =================================================================

    async updateDriverLocations() {
        let driverIds = this.getSelectedDriverIds();

        if (driverIds.length === 0) {
            this.clearMarkers();
            this.renderDriverCards([]); // Boş dizi ile kartları temizle
            return;
        }

        try {
            const response = await fetch(`${this.config.locationsUrl}?drivers=${driverIds.join(',')}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error(`API isteği başarısız: ${response.statusText}`);

            const driversData = await response.json();
            this.updateMarkers(driversData);
            this.renderDriverCards(driversData);
            this.elements.pulseIndicators.forEach(p => p.classList.add('live'));

        } catch (error) {
            console.error('Konum güncellemesi başarısız:', error);
            this.elements.pulseIndicators.forEach(p => p.classList.remove('live'));
        }
    }

    getSelectedDriverIds() {
        if (this.config.driverCount === 1) {
            const singleId = document.getElementById('singleDriverId')?.value;
            return singleId ? [singleId] : [];
        }

        if (this.config.driverCount > 1 && this.tomSelectInstances.length > 0) {
            // Set kullanarak farklı panellerdeki seçimleri birleştir ve kopyaları engelle
            const selectedIds = new Set();
            this.tomSelectInstances.forEach(ts => {
                ts.getValue().forEach(id => selectedIds.add(id));
            });
            return Array.from(selectedIds);
        }

        return [];
    }

    // =================================================================
    // MARKER & POPUP MANAGEMENT
    // =================================================================

    updateMarkers(drivers) {
        const receivedIds = new Set(drivers.map(d => d.user_id.toString()));

        // Artık ekranda olmayan marker'ları kaldır
        for (const id in this.markers) {
            if (!receivedIds.has(id)) {
                this.map.removeLayer(this.markers[id]);
                delete this.markers[id];
            }
        }

        // Mevcut marker'ları güncelle veya yenilerini ekle
        drivers.forEach(driver => {
            const status = this.getDriverStatus(driver.created_at);
            const icon = this.createDriverIcon(status);
            const position = [driver.latitude, driver.longitude];

            if (this.markers[driver.user_id]) {
                this.markers[driver.user_id].setLatLng(position).setIcon(icon);
            } else {
                this.markers[driver.user_id] = L.marker(position, { icon: icon }).addTo(this.map);
            }
            this.markers[driver.user_id].bindPopup(this.createPopupContent(driver));
        });

        if (Object.keys(this.markers).length > 0) {
            this.fitBounds();
        }
    }

    addCompanyMarker() {
        const icon = L.divIcon({
            className: 'company-marker-wrapper',
            html: `<div class="company-marker"><i class="bi bi-building text-white fs-5"></i></div>`,
            iconSize: [44, 44], iconAnchor: [22, 22]
        });
        this.companyMarker = L.marker([this.config.companyLocation.lat, this.config.companyLocation.lng], { icon, zIndexOffset: 1000 }).addTo(this.map);
        this.companyMarker.bindPopup(`<div class="text-center p-2"><h6 class="text-danger mb-1">${this.config.companyLocation.name || 'Merkez'}</h6><small>${this.config.companyLocation.address || ''}</small></div>`);
    }

    createDriverIcon(status) {
        return L.divIcon({
            className: 'bus-marker',
            html: `<div class="bus-icon-wrapper ${status}"><div class="bus-icon-main"><i class="bi bi-bus-front-fill"></i></div></div>`,
            iconSize: [48, 48], iconAnchor: [24, 24]
        });
    }

    createPopupContent(driver) {
        const etaInfo = driver.eta ? `
            <div class="d-flex justify-content-between small mb-1">
                <span><i class="bi bi-geo-alt"></i> Mesafe:</span>
                <strong>${driver.eta.distance} km</strong>
            </div>
            <div class="d-flex justify-content-between small mb-2">
                <span><i class="bi bi-clock-history"></i> Süre:</span>
                <strong class="text-success">${driver.eta.text}</strong>
            </div>` : '<div class="text-muted small">Varış süresi hesaplanamadı.</div>';

        return `<div class="p-2">
                    <h6 class="text-success mb-2"><i class="bi bi-person-circle"></i> ${driver.first_name} ${driver.last_name}</h6>
                    ${etaInfo}
                    <hr class="my-2">
                    <div class="text-muted small"><i class="bi bi-clock"></i> ${driver.last_update_text || 'Bilinmiyor'}</div>
                </div>`;
    }

    // =================================================================
    // UI & CARD RENDERING
    // =================================================================

    renderDriverCards(drivers) {
        const containers = [this.elements.desktopCardsContainer, this.elements.mobileCardsContainer];
        if(this.elements.mobileDriverCount) {
            this.elements.mobileDriverCount.textContent = drivers.length;
        }

        containers.forEach((container, index) => {
            if (!container) return;
            const containerPrefix = index === 0 ? 'desktop' : 'mobile';

            // Mevcut kartları "silinecek" olarak işaretle
            container.querySelectorAll('.driver-card').forEach(card => card.dataset.stale = 'true');

            drivers.forEach(driver => {
                if (!driver.eta) return;
                const cardId = `driver-card-${containerPrefix}-${driver.user_id}`;
                let card = document.getElementById(cardId);

                if (card) {
                    // Kart varsa içeriği güncelle ve işareti kaldır
                    card.innerHTML = this._createDriverCardInnerHtml(driver);
                    delete card.dataset.stale;
                } else {
                    // Kart yoksa yenisini oluştur
                    card = this._createDriverCardElement(driver, cardId);
                    container.appendChild(card);
                }
            });

            // "stale" olarak işaretli kalan kartları sil
            container.querySelectorAll('[data-stale="true"]').forEach(staleCard => staleCard.remove());
        });
    }

    _createDriverCardElement(driver, cardId) {
        const card = document.createElement('div');
        card.id = cardId;
        card.className = 'driver-card';
        card.innerHTML = this._createDriverCardInnerHtml(driver);
        card.addEventListener('click', () => this.onDriverCardClick(driver));
        return card;
    }

    _createDriverCardInnerHtml(driver) {
        const status = this.getDriverStatus(driver.created_at);
        const statusColors = { 'active': 'success', 'idle': 'warning', 'offline': 'secondary' };
        const eta = driver.eta || { distance: '...', text: '...' };

        return `
            <div class="driver-card-icon bg-${statusColors[status]} text-white"><i class="bi bi-bus-front-fill"></i></div>
            <div class="driver-card-info">
                <div class="driver-card-name">${driver.first_name} ${driver.last_name}</div>
                <div class="driver-card-details">
                    <span><i class="bi bi-geo-alt"></i> ${eta.distance} km</span>
                    <span class="driver-card-eta"><i class="bi bi-clock-history"></i> ${eta.text}</span>
                </div>
            </div>`;
    }
    
    onDriverCardClick(driver) {
        if (this.markers[driver.user_id]) {
            this.map.setView([driver.latitude, driver.longitude], 16);
            this.markers[driver.user_id].openPopup();
            
            // Mobil'de kartlara tıklanınca offcanvas'ı kapat
            const offcanvasElement = document.getElementById('bottomPanel');
            if (offcanvasElement?.classList.contains('show')) {
                bootstrap.Offcanvas.getInstance(offcanvasElement)?.hide();
            }
        }
    }

    // =================================================================
    // UTILITY & HELPERS
    // =================================================================

    getDriverStatus(createdAt) {
        const diffMinutes = (new Date() - new Date(createdAt)) / 60000;
        if (diffMinutes < 2) return 'active';
        if (diffMinutes < 5) return 'idle';
        return 'offline';
    }

    clearMarkers() {
        for (const id in this.markers) {
            this.map.removeLayer(this.markers[id]);
        }
        this.markers = {};
    }

    fitBounds() {
        const markersInView = Object.values(this.markers);
        if (this.companyMarker) markersInView.push(this.companyMarker);

        if (markersInView.length > 0) {
            const group = new L.featureGroup(markersInView);
            this.map.fitBounds(group.getBounds().pad(0.15));
        }
    }

    startTimer() {
        if (!this.updateTimer) {
            this.updateTimer = setInterval(() => this.updateDriverLocations(), this.config.updateInterval);
        }
    }

    stopTimer() {
        clearInterval(this.updateTimer);
        this.updateTimer = null;
    }
    
    startLiveTimeUpdater() {
        if (this.liveTimeTimer) clearInterval(this.liveTimeTimer);
        const update = () => {
            const timeString = new Date().toLocaleTimeString('tr-TR');
            if(this.elements.liveTimeDesktop) this.elements.liveTimeDesktop.textContent = timeString;
            if(this.elements.liveTimeMobile) this.elements.liveTimeMobile.textContent = timeString;
        };
        update(); // Immediately update once
        this.liveTimeTimer = setInterval(update, 1000);
    }

    startTracking() {
        this.updateDriverLocations(); // İlk veriyi hemen çek
        this.startLiveTimeUpdater(); // Canlı saat göstergesini başlat
        this.startTimer(); // Periyodik güncellemeyi başlat
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.trackingSystem = new TrackingSystem();

    // GÜNCELLENDİ: Tam Ekran Kodu
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    const body = document.body;
    
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', () => {
            // Tarayıcı uyumluluğu için document.documentElement kullanılır.
            toggleFullScreen(document.documentElement); 
        });
    }

    function toggleFullScreen(element) {
        if (!document.fullscreenElement) {
            element.requestFullscreen().catch(err => {
                alert(`Tam ekran moduna geçilemedi: ${err.message} (${err.name})`);
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    document.addEventListener('fullscreenchange', () => {
        const isFullscreen = !!document.fullscreenElement;
        body.classList.toggle('fullscreen-active', isFullscreen);

        // İkonu güncelle
        const icon = fullscreenBtn.querySelector('i');
        icon.classList.toggle('bi-fullscreen', !isFullscreen);
        icon.classList.toggle('bi-fullscreen-exit', isFullscreen);

        // Harita boyutunu yeniden doğrula
        if (window.trackingSystem && window.trackingSystem.map) {
            setTimeout(() => {
                window.trackingSystem.map.invalidateSize();
            }, 300); // Animasyonun bitmesi için küçük bir gecikme
        }
    });
});
</script>
<?= $this->endSection() ?>