<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container mt-4">
    <h4><i class="bi bi-pencil-square"></i> <?= esc($title) ?>: <?= esc($user->username) ?></h4>
    <hr>

    <div class="card">
        <div class="card-body">
            
            <?= session()->getFlashdata('error') ? '<div class="alert alert-danger">'.session()->getFlashdata('error').'</div>' : '' ?>
            <?= service('validation')->listErrors('list') ?>

            <form action="<?= route_to('admin.users.update', $user->id) ?>" method="post">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-6">
                        <h5>Profil Bilgileri</h5>
                        <div class="mb-3">
                             <label class="form-label">Profil Fotoğrafı</label>
                             <div class="d-flex align-items-center">
                                 <img src="<?= base_url($profile->profile_photo ?? '/assets/images/user.jpg') ?>" alt="Profil Fotoğrafı" id="profile-pic-preview" class="img-thumbnail rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                 <div>
                                     <label for="profile_photo_input" class="btn btn-outline-primary btn-sm">
                                         <i class="bi bi-camera"></i> Fotoğrafı Değiştir
                                     </label>
                                     <input type="file" id="profile_photo_input" class="d-none" accept="image/*">
                                     <input type="hidden" name="cropped_image_data" id="cropped_image_data">
                                 </div>
                             </div>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Ad</label>
                            <input type="text" class="form-control" name="first_name" value="<?= old('first_name', $profile->first_name ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Soyad</label>
                            <input type="text" class="form-control" name="last_name" value="<?= old('last_name', $profile->last_name ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>Hesap Bilgileri</h5>
                        <div class="mb-3">
                            <label for="username" class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" name="username" value="<?= old('username', $user->username) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" value="<?= old('email', $user->email) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" name="password">
                            <small class="form-text text-muted">Değiştirmek istemiyorsanız boş bırakın.</small>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" name="password_confirm">
                        </div>
                    </div>
                </div>

                <hr>

                <h5>Yetkilendirme</h5>
                <div class="row">
                    <div class="col-md-6">
                        <label for="groups" class="form-label">Kullanıcı Grupları</label>
<select name="groups[]" id="groups" class="form-select" multiple>
    <?php foreach ($allGroups as $key => $name): ?>
        <option value="<?= esc($key) ?>" <?= in_array($key, $userGroups) ? 'selected' : '' ?>>
            <?= esc($name) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" role="switch" id="active" <?= $user->active ? 'checked' : '' ?>>
                            <label class="form-check-label" for="active">Hesap Aktif Olsun</label>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <a href="<?= route_to('admin.users.show', $user->id) ?>" class="btn btn-secondary">İptal</a>
                    <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="cropperModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Fotoğrafı Kırp</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><img id="cropper-image" src="" style="max-width: 50%;"></div>
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
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect('#groups',{
            plugins: ['remove_button'],
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cropperModalEl = document.getElementById('cropperModal');
    const image = document.getElementById('cropper-image');
    const fileInput = document.getElementById('profile_photo_input');
    const cropButton = document.getElementById('crop-button');
    const profilePicPreview = document.getElementById('profile-pic-preview');
    const hiddenInput = document.getElementById('cropped_image_data');
    let cropper;
    const cropperModal = new bootstrap.Modal(cropperModalEl);
    fileInput.addEventListener('change', function (e) {
        let files = e.target.files;
        if (files && files.length > 0) {
            let reader = new FileReader();
            reader.onload = function (e) { image.src = e.target.result; cropperModal.show(); };
            reader.readAsDataURL(files[0]);
        }
    });
    cropperModalEl.addEventListener('shown.bs.modal', function () {
        cropper = new Cropper(image, { aspectRatio: 1 / 1, viewMode: 1 });
    });
    cropperModalEl.addEventListener('hidden.bs.modal', function () {
        cropper.destroy();
        cropper = null;
        fileInput.value = '';
    });
    cropButton.addEventListener('click', function () {
        if (!cropper) { return; }
        const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
        const croppedImageData = canvas.toDataURL('image/jpeg', 0.9);
        profilePicPreview.src = croppedImageData;
        hiddenInput.value = croppedImageData;
        cropperModal.hide();
    });
});
</script>
<?= $this->endSection() ?>