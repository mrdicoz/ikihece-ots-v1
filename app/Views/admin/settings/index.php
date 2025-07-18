<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>
<div class="container mt-4">
    <h4><i class="bi bi-key-fill"></i> <?= esc($title) ?></h4>
    <hr>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Lisans Anahtarını Gir</h5>
                    <form action="<?= route_to('admin.settings.save') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="license_key" class="form-label">Lisans Anahtarı</label>
                            <input type="text" class="form-control" name="license_key" id="license_key" value="<?= esc($license_key) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success">Kaydet ve Doğrula</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Lisans Durumu</div>
                <div class="card-body">
                    <?php if(isset($is_license_active) && $is_license_active): ?>
                        <div class="alert alert-success">
                            <h5 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Lisans Aktif!</h5>
                            <p class="mb-0">Sisteminiz Mantar Yazılım lisans sunucusuna başarıyla bağlandı ve lisansınız doğrulandı.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Lisans Aktif Değil!</h5>
                            <p class="mb-0">Lütfen geçerli bir lisans anahtarı girip kaydedin.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>