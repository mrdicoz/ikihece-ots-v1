<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Veli Paneli</h1>
    </div>

     <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-calendar-day"></i> Çocuğumun Bugünkü Programı</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($cocugumunProgrami)): ?>
                        <?php else: ?>
                        <p class="text-center text-muted mt-3">Bugün için planlanmış ders bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-megaphone"></i> Kurum Duyuruları</h6>
                </div>
                <div class="card-body">
                     <?php if (!empty($duyurular)): ?>
                        <?php else: ?>
                        <p class="text-center text-muted mt-3">Yeni duyuru bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
     <div class="row">
        <div class="col-12 mb-4">
             <div class="card shadow">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success"><i class="bi bi-lightning-charge"></i> Hızlı Erişim</h6></div>
                <div class="card-body text-center">
                    <a href="#" class="btn btn-outline-success m-2"><i class="bi bi-person-video3"></i> Çocuğumun Bilgileri</a>
                    <a href="#" class="btn btn-outline-secondary m-2 disabled"><i class="bi bi-chat-dots"></i> Öğretmene Mesaj Gönder</a>
                </div>
             </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>