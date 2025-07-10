<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-backpack2"></i> Öğrenci Bilgilerini Düzenle</h1>
    </div>

    <form action="<?= site_url('students/' . $student['id']) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="card shadow">
            <div class="card-header p-0">
                <ul class="nav nav-tabs nav-fill" id="studentEditTab" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link text-success active" id="temel-tab" data-bs-toggle="tab" data-bs-target="#temel" type="button" role="tab">Temel Bilgiler</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" id="veli-tab" data-bs-toggle="tab" data-bs-target="#veli" type="button" role="tab">Veli Bilgileri</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" id="saglik-tab" data-bs-toggle="tab" data-bs-target="#saglik" type="button" role="tab">Sağlık</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" id="adres-tab" data-bs-toggle="tab" data-bs-target="#adres" type="button" role="tab">Adres</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" id="diger-tab" data-bs-toggle="tab" data-bs-target="#diger" type="button" role="tab">Diğer</button></li>
                </ul>
            </div>
            <div class="card-body">
                <?= service('validation')->listErrors('list') ?>
                <div class="tab-content" id="studentTabContent">
                    <div class="tab-pane fade show active" id="temel" role="tabpanel">
                        <?= $this->include('students/_form_temel') ?>
                    </div>
                    <div class="tab-pane fade" id="veli" role="tabpanel">
                        <?= $this->include('students/_form_veli') ?>
                    </div>
                    <div class="tab-pane fade" id="saglik" role="tabpanel">
                        <?= $this->include('students/_form_saglik') ?>
                    </div>
                    <div class="tab-pane fade" id="adres" role="tabpanel">
                        <?= $this->include('students/_form_adres') ?>
                    </div>
                    <div class="tab-pane fade" id="diger" role="tabpanel">
                        <?= $this->include('students/_form_diger') ?>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="<?= site_url('students/' . $student['id']) ?>" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button>
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