<?= $this->extend('layouts/map') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div style="position: relative; flex: 1; width: 100%;">
    <!-- Floating Control Panel -->
    <div style="position: absolute; top: 80px; left: 15px; z-index: 1000; max-width: 320px;">
        <div class="bg-white rounded-3 shadow-lg p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-success">
                    <i class="bi bi-people-fill"></i> Söför Takip
                </span>
            </div>
            <select id="driverSelect" multiple>
                <?php foreach ($drivers as $driver): ?>
                <option value="<?= $driver['id'] ?>"><?= esc($driver['first_name'] . ' ' . $driver['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- Canlı Durum Badge -->
    <div style="position: absolute; top: 80px; right: 15px; z-index: 1000;">
        <span class="badge bg-success" id="liveStatus">
            <i class="bi bi-broadcast"></i> Canlı Takip Aktif
        </span>
    </div>
    
    <!-- Tam Ekran Harita -->
    <div id="map" style="height: 100%; width: 100%;"></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
$(document).ready(function() {
    // Tom Select multiselect
    new TomSelect('#driverSelect', {
        plugins: ['remove_button'],
        placeholder: 'Söför seçin...',
        maxItems: null
    });
    
    // Harita oluştur
    let map = L.map('map', {
        zoomControl: true,
        attributionControl: true
    }).setView([40.7889, 30.4008], 12); // Sakarya merkez
    
    // Zoom kontrolünü sağ alt köşeye taşı
    map.zoomControl.setPosition('bottomright');
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    let markers = {};
    let updateInterval;

    $('#driverSelect').on('change', function() {
        updateMap();
        
        // Söför seçildiğinde otomatik güncellemeyi başlat
        if ($(this).val() && $(this).val().length > 0) {
            if (!updateInterval) {
                updateInterval = setInterval(updateMap, 15000); // 15 saniye
            }
        } else {
            // Söför seçimi kaldırılırsa güncellemeyi durdur
            clearInterval(updateInterval);
            updateInterval = null;
        }
    });

    function updateMap() {
        $.get('<?= base_url('api/location/drivers') ?>', function(drivers) {
            // Eski markerları temizle
            for (let id in markers) {
                map.removeLayer(markers[id]);
            }
            markers = {};
            
            let selectedDrivers = $('#driverSelect').val();
            
            if (!selectedDrivers || selectedDrivers.length === 0) {
                return;
            }
            
            let hasActiveDriver = false;
            
drivers.forEach(driver => {
    if (selectedDrivers.includes(driver.user_id.toString())) {
        hasActiveDriver = true;
        
        // BÜYÜK ve BELİRGİN Servis Aracı İkonu
        let busIcon = L.divIcon({
            className: 'custom-bus-marker',
            html: `
                <div class="bus-icon-wrapper">
                    <div class="bus-icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-bus-front-fill" viewBox="0 0 16 16">
                            <path d="M16 7a1 1 0 0 1-1 1v3.5c0 .818-.393 1.544-1 2v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V14H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2a2.5 2.5 0 0 1-1-2V8a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1V2.64C1 1.452 1.845.408 3.064.268A44 44 0 0 1 8 0c2.1 0 3.792.136 4.936.268C14.155.408 15 1.452 15 2.64V4a1 1 0 0 1 1 1zM3.552 3.22A43 43 0 0 1 8 3c1.837 0 3.353.107 4.448.22a.5.5 0 0 0 .104-.994A44 44 0 0 0 8 2c-1.876 0-3.426.109-4.552.226a.5.5 0 1 0 .104.994M8 4c-1.876 0-3.426.109-4.552.226A.5.5 0 0 0 3 4.5v3a.5.5 0 0 0 .448.497C4.574 8.891 6.124 9 8 9s3.426-.109 4.552-.226A.5.5 0 0 0 13 8.5v-3a.5.5 0 0 0-.448-.497A44 44 0 0 0 8 4m-3 7a1 1 0 1 0-2 0 1 1 0 0 0 2 0m8 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m-7 0a1 1 0 0 0 1 1h2a1 1 0 1 0 0-2H7a1 1 0 0 0-1 1"/>
                        </svg>
                    </div>
                    <div class="driver-name">${driver.first_name}</div>
                </div>
            `,
            iconSize: [60, 80],
            iconAnchor: [30, 60],
            popupAnchor: [0, -60]
        });
        
        let marker = L.marker([driver.latitude, driver.longitude], {icon: busIcon})
            .addTo(map)
            .bindPopup(`
                <div class="text-center p-2">
                    <div class="mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-bus-front-fill text-success" viewBox="0 0 16 16">
                            <path d="M16 7a1 1 0 0 1-1 1v3.5c0 .818-.393 1.544-1 2v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V14H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2a2.5 2.5 0 0 1-1-2V8a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1V2.64C1 1.452 1.845.408 3.064.268A44 44 0 0 1 8 0c2.1 0 3.792.136 4.936.268C14.155.408 15 1.452 15 2.64V4a1 1 0 0 1 1 1zM3.552 3.22A43 43 0 0 1 8 3c1.837 0 3.353.107 4.448.22a.5.5 0 0 0 .104-.994A44 44 0 0 0 8 2c-1.876 0-3.426.109-4.552.226a.5.5 0 1 0 .104.994M8 4c-1.876 0-3.426.109-4.552.226A.5.5 0 0 0 3 4.5v3a.5.5 0 0 0 .448.497C4.574 8.891 6.124 9 8 9s3.426-.109 4.552-.226A.5.5 0 0 0 13 8.5v-3a.5.5 0 0 0-.448-.497A44 44 0 0 0 8 4m-3 7a1 1 0 1 0-2 0 1 1 0 0 0 2 0m8 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m-7 0a1 1 0 0 0 1 1h2a1 1 0 1 0 0-2H7a1 1 0 0 0-1 1"/>
                        </svg>
                    </div>
                    <strong class="text-success d-block fs-5">${driver.first_name} ${driver.last_name}</strong>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-clock"></i> ${driver.created_at}
                    </small>
                </div>
            `);
        markers[driver.user_id] = marker;
    }
});
            
            // Durum badge'ini güncelle
            if (hasActiveDriver) {
                $('#liveStatus').removeClass('bg-secondary').addClass('bg-success')
                    .html('<i class="bi bi-broadcast"></i> Canlı Takip Aktif');
            } else {
                $('#liveStatus').removeClass('bg-success').addClass('bg-secondary')
                    .html('<i class="bi bi-broadcast-pin"></i> Bekleniyor');
            }
            
            // Eğer marker varsa, haritayı otomatik zoom yap
            if (Object.keys(markers).length > 0) {
                let group = new L.featureGroup(Object.values(markers));
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }).fail(function() {
            $('#liveStatus').removeClass('bg-success').addClass('bg-danger')
                .html('<i class="bi bi-exclamation-triangle"></i> Bağlantı Hatası');
        });
    }
    
    // Sayfa yüklendiğinde harita boyutunu ayarla
    setTimeout(function(){ 
        map.invalidateSize();
    }, 100);
});
</script>

<style>
/* Tom Select özelleştirme */
.ts-wrapper {
    width: 100% !important;
}
.ts-control {
    border: 1px solid #198754 !important;
    border-radius: 0.375rem !important;
}
.ts-control:focus {
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important;
}

/* Leaflet popup özelleştirme */
.leaflet-popup-content-wrapper {
    border-radius: 0.5rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* BÜYÜK Servis Aracı Marker */
.custom-bus-marker {
    background: transparent !important;
    border: none !important;
}

.bus-icon-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
}

.bus-icon-container {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #198754 0%, #157347 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.4);
    animation: pulse-bus 2s infinite;
}

.bus-icon-container svg {
    color: white;
}

.driver-name {
    margin-top: 5px;
    background: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    color: #198754;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

@keyframes pulse-bus {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(25, 135, 84, 0.6);
    }
}
</style>
<?= $this->endSection() ?>