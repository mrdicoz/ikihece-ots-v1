<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.13.7/sorting/turkish-string.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>


<script src="<?= base_url('assets/js/custom.js') ?>"></script>

<?= $this->renderSection('pageScripts') ?>

<script>
// Buton yerine linkimize tıklandığında abonelik sürecini başlat
document.getElementById('subscribeButton').addEventListener('click', (event) => {
    event.preventDefault(); // Linkin varsayılan tıklama davranışını engelle
    runSubscriptionProcess();
});

const runSubscriptionProcess = async () => {
    // 1. Tarayıcının Service Worker ve Push API'ı destekleyip desteklemediğini kontrol et
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.warn('Push Mesajlaşması bu tarayıcı tarafından desteklenmiyor.');
        alert('Maalesef bu tarayıcı bildirimleri desteklemiyor.');
        return;
    }

    try {
        // 2. Service Worker'ı kaydet
        const registration = await navigator.serviceWorker.register('/service-worker.js');
        console.log('Service Worker başarıyla kaydedildi.');

        // 3. Kullanıcıdan bildirim izni iste (eğer daha önce verilmediyse)
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            console.log('Bildirim izni verilmedi.');
            alert('Bildirimleri alabilmek için izin vermeniz gerekmektedir.');
            return;
        }

        // 4. Mevcut bir abonelik var mı diye kontrol et
        let subscription = await registration.pushManager.getSubscription();
        if (subscription) {
            console.log('Kullanıcı zaten abone.', subscription);
            alert('Bu cihazda zaten bildirimlere abonesiniz.');
            return;
        }

        // 5. Sunucudan VAPID public key'i al
        const response = await fetch('/notifications/vapid-key');
        const vapidData = await response.json();
        const vapidPublicKey = vapidData.publicKey;

        // VAPID key'i doğru formata dönüştür
        const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

        // 6. Yeni bir push aboneliği oluştur
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: convertedVapidKey
        });
        console.log('Yeni abonelik oluşturuldu:', subscription);

        // 7. Abonelik bilgilerini sunucuya gönder ve kaydet
        await fetch('/notifications/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(subscription)
        });
        console.log('Abonelik sunucuya başarıyla gönderildi.');
        alert('Bildirimlere başarıyla abone oldunuz!');

    } catch (error) {
        console.error('Abonelik sürecinde hata oluştu:', error);
        alert('Abonelik sırasında bir hata oluştu. Lütfen tekrar deneyin.');
    }
}

/**
 * Bu yardımcı fonksiyon, VAPID public key'i tarayıcının anladığı formata çevirir.
 * Değiştirmeden bu şekilde kullanılması gerekir.
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
</script>