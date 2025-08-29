<?= $this->extend('layouts/blank') ?>
<?= $this->section('title') ?>Sistem Bakımda<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="text-center">
        <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo" class="mb-4" style="max-width: 150px;">
        <h1 class="display-3">Sistem Geçici Olarak Hizmet Dışıdır</h1>
        <p class="lead text-muted">Lisans doğrulama hatası nedeniyle sisteme şu anda erişilememektedir.</p>
        <p>Lütfen daha sonra tekrar deneyin veya sistem yöneticinizle iletişime geçin.</p>
        <a href="<?= route_to('login') ?>" class="btn btn-success mt-3">Giriş Sayfasına Dön</a>

    </div>
</div>
<?= $this->endSection() ?>