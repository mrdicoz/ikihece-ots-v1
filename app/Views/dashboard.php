<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Yönetim Paneli</h1>
    </div>

    <div class="alert alert-success">
        <h5>Merhaba, <?= esc(auth()->user()->username) ?>!</h5>
        <p>İkihece Öğrenci Takip Sistemi'ne hoş geldiniz. Buradan sistemi yönetebilir ve gerekli işlemleri yapabilirsiniz.</p>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Kullanıcılar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">[SAYI]</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Aktif Öğrenciler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">[SAYI]</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        </div>

</div>
<?= $this->endSection() ?>