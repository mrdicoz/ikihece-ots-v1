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

 // _scriptin Devamı
// public/assets/js/custom.js (Debug versiyonu)

console.log('custom.js dosyası başarıyla yüklendi ve çalıştırılıyor.');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM tamamen yüklendi. "subscribeButton" aranıyor...');

    const subscribeButton = document.getElementById('subscribeButton');

    if (subscribeButton) {
        console.log('SUCCESS: Buton (id="subscribeButton") bulundu! Olay dinleyicisi şimdi ekleniyor.');
        
        subscribeButton.addEventListener('click', (event) => {
            console.log('SUCCESS: Butona tıklandı! Abonelik süreci başlatılıyor.');
            event.preventDefault();
            handleSubscription();
        });
    } else {
        console.error('HATA: id="subscribeButton" olan bir buton sayfada bulunamadı! Lütfen HTML kodunu kontrol et.');
    }

    // --- Diğer fonksiyonlar aşağıda ---

    async function handleSubscription() {
        // ... (önceki cevaptaki handleSubscription fonksiyonunun içeriği buraya gelecek) ...
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            alert('Maalesef bu tarayıcı bildirimleri desteklemiyor.');
            return;
        }

        subscribeButton.disabled = true;

        try {
            const registration = await navigator.serviceWorker.ready;
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') throw new Error('Bildirimlere izin verilmedi.');

            let subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                alert('Bu cihazda zaten bildirimlere abonesiniz.');
                subscribeButton.textContent = 'Abonelik Aktif';
                return;
            }
            
            const response = await fetch('/notifications/vapid-key');
            if (!response.ok) throw new Error('VAPID anahtarı alınamadı.');
            
            const vapidData = await response.json();
            const applicationServerKey = urlBase64ToUint8Array(vapidData.publicKey);

            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

            const saveResponse = await fetch('/notifications/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(subscription)
            });
            
            if (!saveResponse.ok) {
                const errorData = await saveResponse.json();
                throw new Error(errorData.message || 'Abonelik sunucuya kaydedilemedi.');
            }

            const successData = await saveResponse.json();
            alert(successData.message || 'Bildirimlere başarıyla abone olundu!');
            subscribeButton.textContent = 'Abonelik Aktif';

        } catch (error) {
            console.error('Abonelik sürecinde hata:', error);
            alert('Abonelik sırasında bir hata oluştu: ' + error.message);
            subscribeButton.disabled = false;
        }
    }

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
});
