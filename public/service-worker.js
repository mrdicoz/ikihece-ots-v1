// Service Worker'ın başarıyla kurulduğunu konsola yazdırır.
console.log('Service Worker Yüklendi.');

// 'push' olayını dinle. Sunucudan bir bildirim geldiğinde bu bölüm çalışır.
self.addEventListener('push', event => {
    console.log('[Service Worker] Push Alındı.');

    // Sunucudan gelen veriyi JSON olarak ayrıştır.
    // Eğer veri JSON değilse, düz metin olarak kullan.
    let pushData;
    try {
        pushData = event.data.json();
    } catch (e) {
        pushData = {
            title: 'Yeni Bildirim',
            body: event.data.text()
        };
    }

    const title = pushData.title || 'Yeni Bildirim';
    const options = {
        body: pushData.body || 'Burada bildirim içeriği yer alacak.',
        icon: '/images/icon.png', // Gösterilecek ikonun yolu (public/images/icon.png)
        badge: '/images/badge.png', // Android'de bildirim çubuğunda görünecek küçük ikon
        // Diğer opsiyonlar eklenebilir: data, actions, etc.
    };

    // Bildirimi kullanıcıya göster.
    event.waitUntil(self.registration.showNotification(title, options));
});

// Kullanıcı bildirime tıkladığında ne olacağını belirler.
    self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] Bildirime tıklandı.');

    // Bildirimi kapat.
    event.notification.close();

    const rootUrl = self.location.origin;

        event.waitUntil(
        clients.openWindow(rootUrl)
    );
});