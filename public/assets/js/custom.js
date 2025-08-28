// public/assets/js/custom.js

document.addEventListener('DOMContentLoaded', function() {
    // Login şifre göster/gizle
    const passwordInput = document.getElementById('passwordInput');
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                togglePassword.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                togglePassword.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });
    }
    // Register şifre göster/gizle
    const regPass = document.getElementById('registerPasswordInput');
    const regToggle = document.getElementById('toggleRegisterPassword');
    if (regToggle && regPass) {
        regToggle.addEventListener('click', function() {
            if (regPass.type === 'password') {
                regPass.type = 'text';
                regToggle.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                regPass.type = 'password';
                regToggle.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });
    }
    const regPass2 = document.getElementById('registerPasswordConfirm');
    const regToggle2 = document.getElementById('toggleRegisterPasswordConfirm');
    if (regToggle2 && regPass2) {
        regToggle2.addEventListener('click', function() {
            if (regPass2.type === 'password') {
                regPass2.type = 'text';
                regToggle2.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                regPass2.type = 'password';
                regToggle2.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });
    }
});

// Profil/Öğrenci Fotoğraf Croplama
window.initCropper = function(inputId, modalId, cropperImageId, cropAndSaveId, hiddenInputId) {
    let cropper;
    const input = document.getElementById(inputId);
    const modalEl = document.getElementById(modalId);
    const modal = new bootstrap.Modal(modalEl);
    const cropperImage = document.getElementById(cropperImageId);
    const hiddenInput = document.getElementById(hiddenInputId);

    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            cropperImage.src = e.target.result;
            modal.show();
            setTimeout(() => {
                if (cropper) cropper.destroy();
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                    minCropBoxWidth: 500,
                    minCropBoxHeight: 500,
                    cropBoxResizable: false,
                    dragMode: 'move',
                    responsive: true,
                });
            }, 500);
        };
        reader.readAsDataURL(file);
    });

    document.getElementById(cropAndSaveId).addEventListener('click', function() {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({
            width: 500,
            height: 500,
            imageSmoothingQuality: 'high'
        });
        hiddenInput.value = canvas.toDataURL('image/jpeg', 0.92);
        modal.hide();
    });
}

document.querySelectorAll('.theme-btn').forEach(function(btn) {
    btn.addEventListener('click', function () {
        let theme = btn.getAttribute('data-theme');
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme); // Kullanıcı tercihini sakla
    });
});

// Sayfa yüklenince kullanıcının seçtiği tema gelsin:
window.addEventListener('DOMContentLoaded', function () {
    let theme = localStorage.getItem('theme') || 'auto';
    document.documentElement.setAttribute('data-bs-theme', theme);
});

// Bildirimlere abone olma ve abonelikten çıkma işlemleri
// public/assets/js/custom.js (TAMAMI)

document.addEventListener('DOMContentLoaded', function() {

    // --- GENEL AYARLAR VE DEĞİŞKENLER ---
    const subscribeButtons = document.querySelectorAll('.notification-bell');

    // Tarayıcı desteği yoksa işlemi en baştan durdur.
    if (!('serviceWorker' in navigator && 'PushManager' in window)) {
        console.warn('Bildirimler bu tarayıcıda desteklenmiyor.');
        if (subscribeButtons.length > 0) {
            subscribeButtons.forEach(button => button.style.display = 'none');
        }
        return;
    }

    // --- YARDIMCI FONKSİYON ---
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // --- ANA FONKSİYONLAR ---

    // 1. İkonun ve Butonun Durumunu Güncelleyen Fonksiyon
    async function updateUIButton(button) {
        try {
            const bellIcon = button.querySelector('i');
            if (!bellIcon) return;

            const permission = Notification.permission;
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            button.classList.remove('status-default', 'status-granted', 'status-denied');

            if (permission === 'denied') {
                bellIcon.className = 'bi bi-bell-slash-fill';
                button.classList.add('status-denied');
                button.title = 'Bildirimlere izin vermeyi engellediniz.';
                button.style.pointerEvents = 'none'; // Tıklamayı tamamen engelle
            } else if (permission === 'granted' && subscription) {
                bellIcon.className = 'bi bi-bell-fill';
                button.classList.add('status-granted');
                button.title = 'Bildirim aboneliğini iptal etmek için tıklayın.';
                button.style.pointerEvents = 'auto';
            } else {
                bellIcon.className = 'bi bi-bell-fill';
                button.classList.add('status-default');
                button.title = 'Bildirimleri açmak için tıklayın.';
                button.style.pointerEvents = 'auto';
            }
        } catch (error) {
            console.error("İkon durumu güncellenirken hata oluştu:", error);
        }
    }

    // 2. Tıklama Olayını Yöneten Fonksiyon
    async function handleBellClick(event) {
        event.preventDefault();
        const button = event.currentTarget; // Tıklanan butonu al
        button.disabled = true; // Çift tıklamayı önle

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await unsubscribe(subscription);
                alert('Bildirim aboneliğiniz başarıyla kaldırıldı.');
            } else {
                await subscribe(registration);
                alert('Bildirimlere başarıyla abone oldunuz!');
            }
        } catch (error) {
            console.error('İşlem sırasında hata:', error);
            alert('Bir hata oluştu: ' + error.message);
        } finally {
            button.disabled = false; // İşlem bitince butonu tekrar aktif et
            updateUIButton(button); // Son duruma göre ikonu güncelle
        }
    }

    // 3. Abone Olma Mantığı
    async function subscribe(registration) {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Bildirimlere izin verilmedi.');
        }

        const response = await fetch('/notifications/vapid-key');
        if (!response.ok) throw new Error('VAPID anahtarı sunucudan alınamadı.');
        const vapidData = await response.json();
        const applicationServerKey = urlBase64ToUint8Array(vapidData.publicKey);

        const newSubscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        });

        await sendSubscriptionToServer(newSubscription, '/notifications/subscribe', 'POST');
    }

    // 4. Abonelikten Çıkma Mantığı
    async function unsubscribe(subscription) {
        await sendSubscriptionToServer(subscription, '/notifications/unsubscribe', 'POST');
        const unsubscribed = await subscription.unsubscribe();
        if (!unsubscribed) {
            throw new Error("Abonelikten çıkma işlemi tarayıcı tarafında başarısız oldu.");
        }
    }
    
    // 5. Sunucuya Veri Gönderen Genel Fonksiyon
    async function sendSubscriptionToServer(subscription, url, method) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(subscription.toJSON()) // .toJSON() kullanmak daha standarttır
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Sunucu işlemi başarısız oldu.');
        }
        return response.json();
    }

    // --- KODUN BAŞLATILMASI ---

    // Sayfadaki tüm bildirim butonlarını bul ve her biri için işlem yap
    if (subscribeButtons.length > 0) {
        subscribeButtons.forEach(button => {
            // 1. Sayfa yüklendiğinde her butonun ikon durumunu ayarla
            updateUIButton(button);
            // 2. Her butona tıklama olayı ekle
            button.addEventListener('click', handleBellClick);
        });
    }

});
/**
 * DataTables arama alanını büyük harfe zorlar ve aramayı tetikler.
 * @param {DataTable} table - Hedef DataTable nesnesi.
 */
function forceUppercaseSearch(table) {
    const searchInput = $('div.dataTables_wrapper div.dataTables_filter input');
    
    searchInput.on('keyup', function() {
        // Input değerini al ve büyük harfe çevir
        const uppercaseValue = this.value.toLocaleUpperCase('tr-TR');
        
        // Eğer değer değiştiyse, input'a geri yaz ve aramayı tetikle
        if (this.value !== uppercaseValue) {
            this.value = uppercaseValue;
        }
        
        // DataTable'ın aramasıyla input değerini senkronize et
        table.search(uppercaseValue).draw();
    });
}