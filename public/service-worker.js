// public/service-worker.js

// Önbellek adını versiyonla belirtmek, güncellemelerde eski önbelleği temizlemeyi sağlar.
const CACHE_NAME = 'ikihece-ots-cache-v4'; // <-- VERSİYONU ARTIRDIK

// "App Shell" - yani uygulamanın iskeletini oluşturan, her zaman gerekli olan dosyalar.
const urlsToCache = [
    '/',
    '/manifest.json', // PWA için manifest dosyası
    '/assets/css/custom.css',
    '/assets/js/custom.js',
    '/assets/images/logo.png',
    '/assets/images/favicon-192x192.png',
    '/assets/images/favicon-512x512.png',
    // Projemizde kullandığımız dış kütüphaneler (CDN)
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
];

// 1. Yükleme (Install) Olayı: Uygulama kabuğunu önbelleğe alır.
self.addEventListener('install', event => {
    console.log('Service Worker: Yükleniyor...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Gerekli dosyalar önbelleğe alınıyor.');
                return cache.addAll(urlsToCache);
            })
            .then(() => {
                // Yeni Service Worker'ın hemen aktif olmasını sağla
                return self.skipWaiting();
            })
    );
});

// 2. Etkinleştirme (Activate) Olayı: Eski önbellekleri temizler.
self.addEventListener('activate', event => {
    console.log('Service Worker: Etkinleştiriliyor...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    // Eğer mevcut önbellek adı, bizim yeni adımızdan farklıysa sil.
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Eski önbellek temizleniyor ->', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Service Worker'ı anında kontrolü ele alması için zorla
            return self.clients.claim();
        })
    );
});

// 3. Ağ İsteği (Fetch) Olayı: "Önce Ağa Git, Ağ Yoksa Önbelleğe Bak" stratejisi
self.addEventListener('fetch', event => {
    // Sadece GET isteklerini önbelleğe alıyoruz. POST (login gibi) istekleri her zaman ağa gitmeli.
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        // Önce ağı dene
        fetch(event.request)
            .then(response => {
                // Ağdan cevap geldiyse, hem tarayıcıya döndür hem de bir kopyasını önbelleğe al.
                const responseToCache = response.clone();
                caches.open(CACHE_NAME)
                    .then(cache => {
                        // --- ÇÖZÜM: Sadece http/https isteklerini önbelleğe al ---
                        // Bu kontrol, chrome-extension:// gibi desteklenmeyen şemaya sahip isteklerin
                        // cache.put() ile işlenmesini engelleyerek hatayı ortadan kaldırır.
                        if (event.request.url.startsWith('http')) {
                            cache.put(event.request, responseToCache);
                        }
                    });
                return response;
            })
            .catch(error => {
                // Ağ başarısız olursa (çevrimdışı olma durumu), önbellekten yanıtı döndürmeyi dene.
                console.log('Service Worker: Ağdan getirme başarısız, önbelleğe bakılıyor.', error);
                return caches.match(event.request)
                    .then(response => {
                        // Önbellekte varsa, onu döndür.
                        if (response) {
                            return response;
                        }
                        // Önbellekte de yoksa, bir şey yapamayız. Tarayıcı kendi hatasını verecektir.
                    });
            })
    );
});

// 4. Push Bildirimi Olayı (Bu kısım aynı kalıyor)
self.addEventListener('push', function(event) {
    console.log('Service Worker: Push bildirimi alındı!', event);
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: data.icon,
        data: { url: data.data.url }
    };
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// 5. Bildirime Tıklama Olayı (Bu kısım da aynı kalıyor)
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});

// ============================================
// 6. BACKGROUND SYNC - Konum Takibi
// ============================================

// Background Sync Event - Sayfa kapalıyken bile çalışır
self.addEventListener('sync', function(event) {
    console.log('Service Worker: Background Sync tetiklendi ->', event.tag);
    
    if (event.tag === 'sync-location') {
        event.waitUntil(sendLocationInBackground());
    }
});

// Periodic Background Sync - Düzenli aralıklarla çalışır
self.addEventListener('periodicsync', function(event) {
    console.log('Service Worker: Periodic Sync tetiklendi ->', event.tag);
    
    if (event.tag === 'periodic-location-sync') {
        event.waitUntil(sendLocationInBackground());
    }
});

// Arka planda konum gönder
async function sendLocationInBackground() {
    try {
        console.log('Service Worker: Arka planda konum gönderiliyor...');
        
        // IndexedDB'den kayıtlı konum bilgisini al
        const locationData = await getStoredLocation();
        
        if (!locationData) {
            console.log('Service Worker: Kayıtlı konum bulunamadı');
            return;
        }
        
        // API'ye gönder
        const response = await fetch('/api/location/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `latitude=${locationData.latitude}&longitude=${locationData.longitude}`
        });
        
        if (response.ok) {
            console.log('Service Worker: Konum başarıyla gönderildi (arka plan)');
            
            // Kullanıcıya bildirim gönder (opsiyonel)
            self.registration.showNotification('Konum Güncellendi', {
                body: 'Konumunuz başarıyla güncellendi',
                icon: '/assets/images/favicon-192x192.png',
                badge: '/assets/images/favicon-192x192.png',
                tag: 'location-update',
                silent: true // Sessiz bildirim
            });
        } else {
            console.error('Service Worker: Konum gönderilemedi');
        }
        
    } catch (error) {
        console.error('Service Worker: Arka plan konum gönderimi hatası:', error);
    }
}

// IndexedDB'den konum al
function getStoredLocation() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('LocationDB', 1);
        
        request.onerror = () => reject(request.error);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['locations'], 'readonly');
            const store = transaction.objectStore('locations');
            const getRequest = store.get('lastLocation');
            
            getRequest.onsuccess = () => resolve(getRequest.result);
            getRequest.onerror = () => reject(getRequest.error);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('locations')) {
                db.createObjectStore('locations');
            }
        };
    });
}