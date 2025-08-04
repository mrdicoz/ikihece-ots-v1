<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?>Rolünüzü Seçin<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-5 text-center">
                <img src="/assets/images/logo.png" alt="Logo" class="mb-4" style="max-width: 150px;">
                <h3 class="card-title mb-4">Sisteme Hoş Geldiniz!</h3>
                <p class="text-muted">Lütfen sisteme hangi rolle devam etmek istediğinizi seçin.</p>

                <?php if (session('error')) : ?>
                    <div class="alert alert-danger"><?= session('error') ?></div>
                <?php endif ?>

                <form action="<?= site_url('onboarding/role') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="d-grid gap-3 mt-4">
                        <button type="submit" name="role" value="veli" class="btn btn-success btn-lg">
                            <i class="bi bi-people-fill"></i> Ben bir Veliyim
                        </button>
                        <button type="submit" name="role" value="calisan" class="btn btn-outline-success btn-lg">
                            <i class="bi bi-briefcase-fill"></i> Ben bir Kurum Çalışanıyım
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>