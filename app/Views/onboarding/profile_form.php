<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?>Profili Tamamla<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="/assets/images/logo.png" alt="Logo" class="mb-4" style="max-width: 150px;">
                    <h3 class="card-title">Son Bir Adım Kaldı!</h3>
                    <p class="text-muted">Lütfen profil bilgilerinizi eksiksiz doldurun.</p>
                </div>
                
                <?= service('validation')->listErrors('list') ?>

                <form action="<?= site_url('onboarding/profile') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="text-center mb-4">
                        <img src="<?= base_url('/assets/images/user.jpg') ?>" alt="Profil Fotoğrafı" id="profile-pic-preview" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        <br>
                        <label for="profile_photo_input" class="btn btn-outline-success btn-sm mt-2">
                            <i class="bi bi-camera"></i> Fotoğraf Yükle
                        </label>
                        <input type="file" id="profile_photo_input" class="d-none" accept="image/*">
                        <input type="hidden" name="cropped_image_data" id="cropped_image_data">
                    </div>
                    
                    <div class="mb-3">
                        <label for="first_name" class="form-label">Adınız</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= old('first_name') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Soyadınız</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= old('last_name') ?>" required>
                    </div>

                    <?php if ($role === 'veli'): ?>
                        <div class="mb-3">
                            <label for="tc_kimlik_no" class="form-label">TC Kimlik Numarası</label>
                            <input type="text" class="form-control" id="tc_kimlik_no" name="tc_kimlik_no" value="<?= old('tc_kimlik_no') ?>" required>
                        </div>
                    <?php else: // calisan ?>
                        <div class="mb-3">
                            <label for="branch" class="form-label">Branş / Departman</label>
                            <select class="form-select" id="branch" name="branch" required>
                                <option value="">Branş Seçiniz...</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= esc($branch) ?>" <?= (old('branch') == $branch) ? 'selected' : '' ?>><?= esc($branch) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg">Profili Tamamla ve Başla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cropperModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Fotoğrafı Kırp</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><img id="cropper-image" src="" style="max-width: 100%;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" id="crop-button">Kırp ve Kaydet</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // initCropper fonksiyonu public/assets/js/custom.js dosyasından geliyor
    initCropper(
        'profile_photo_input',
        'cropperModal',
        'cropper-image',
        'crop-button',
        'cropped_image_data'
    );
});
</script>
<?= $this->endSection() ?>