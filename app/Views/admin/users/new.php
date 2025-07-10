<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-person-plus-fill"></i> <?= esc($title) ?></h1>
    </div>

    <?= service('validation')->listErrors('list') ?>

<form action="<?= route_to('admin.users.create') ?>" method="post">
    
    <div class="card shadow">

            <div class="card-body">
                <div class="row">

                    <div class="col-md-8">
                        <h5 class="mb-3">Profil Bilgileri</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Adı</label><input type="text" name="first_name" class="form-control" value="<?= old('first_name', $profile->first_name ?? '') ?>"required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Soyadı</label><input type="text" name="last_name" class="form-control" value="<?= old('last_name', $profile->last_name ?? '') ?>"required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Kullanıcı Adı</label><input type="text" name="username" class="form-control" value="<?= old('username', $profile->username ?? '') ?>"required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">E-Posta</label><input type="email" name="email" class="form-control" value="<?= old('email', $profile->email ?? '') ?>"required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Şifre</label><input type="password" name="password" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Şifre Tekrar</label><input type="password" name="password_confirm" class="form-control" required></div>
                        </div>

                    </div>

                    <div class="col-md-4">
                            <h5 class="mb-3 text-center">Profil Fotoğrafı</h5>

                            <div class=" d-flex justify-content-center">
                                <img src="<?= base_url($profile->profile_photo ?? '/assets/images/user.jpg') ?>" alt="Profil Fotoğrafı" id="profile-pic-preview" class="img-thumbnail rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                            </div>
                            <div>
                                <label for="profile_photo_input" class="btn btn-outline-success  w-100"><i class="bi bi-camera"></i> Fotoğraf Değiştir</label>
                                <input type="file" id="profile_photo_input" class="d-none" accept="image/*">
                                <input type="hidden" name="cropped_image_data" id="cropped_image_data">
                            </div>

                            <select class="form-select mt-3" name="groups[]" id="groups" multiple>
                                <?php foreach ($allGroups as $key => $name): ?>
                                    <option value="<?= esc($key) ?>"><?= esc($name) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="active" role="switch" id="active" checked>
                            <label class="form-check-label" for="active">Hesap Aktif Olsun</label>
                        </div>
                    </div>
                </div>             
            </div>

        <div class="card-footer text-end">
            <a href="<?= route_to('admin.users.index') ?>" class="btn btn-secondary">İptal</a>
            <button type="submit" class="btn btn-success">Kullanıcıyı Oluştur</button>

        </div>


    </div>    

</form>
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
            placeholder: 'Grup seçin...'
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