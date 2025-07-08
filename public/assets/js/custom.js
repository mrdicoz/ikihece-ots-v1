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
