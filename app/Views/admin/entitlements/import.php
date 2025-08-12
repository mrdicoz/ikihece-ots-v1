<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-person-check"></i> <?= esc($title) ?></h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body p-lg-5">
            <p>Bu arayüzü kullanarak, öğrencilerin aylık ders haklarını Excel dosyası üzerinden toplu olarak sisteme yükleyebilirsiniz.</p>
            <hr>
            <form action="<?= route_to('admin.entitlements.process') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="file" class="form-label"><b>Excel Dosyasını Seçin</b></label>
                    <p class="text-muted small">Sütun sırası: A: Öğrenci Adı Soyadı, B: Normal Bireysel, C: Normal Grup, D: Telafi Bireysel, E: Telafi Grup</p>
                    <input class="form-control" type="file" id="file" name="file" required accept=".xls, .xlsx">
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload"></i> Hakları Yükle ve Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>