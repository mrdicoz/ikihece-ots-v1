<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.13.7/sorting/turkish-string.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/imask/7.1.3/imask.min.js"></script>


<script src="<?= base_url('assets/js/custom.js') ?>"></script>

<?= $this->renderSection('pageScripts') ?>

<script>

    // PWA'NIN ÇALIŞMASINI SAĞLAYAN ÇEKİRDEK KOD (BU KOD _scripts.php'de KALACAK)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('Service Worker başarıyla kaydedildi: ', registration.scope);
            })
            .catch(err => {
                console.log('Service Worker kaydı başarısız: ', err);
            });
    });
}

    // Profil fotoğrafı kontrol ve zorunlu modal
    <?php if (auth()->loggedIn()): ?>
        <?php
        $profileModel = new \App\Models\UserProfileModel();
        $userProfile = $profileModel->where('user_id', auth()->id())->first();
        $hasProfilePhoto = !empty($userProfile->profile_photo) && $userProfile->profile_photo !== '/assets/images/user.jpg';
        $isProfilePage = (uri_string() === 'profile' || strpos(uri_string(), 'profile/') === 0);
        ?>

        <?php if (!$hasProfilePhoto && !$isProfilePage): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const profilePhotoModal = new bootstrap.Modal(document.getElementById('profilePhotoRequiredModal'));
                profilePhotoModal.show();
            });
        <?php endif; ?>
    <?php endif; ?>
</script>