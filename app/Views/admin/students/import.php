<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid">

    <div class="d-sm-flex flex-column mb-4">
        <h1 class="h3 mb-0 text-gray-800">Öğrenci Verilerini İçeri Aktar</h1>
        <p class="mb-4">Toplu öğrenci kaydı için lütfen aşağıdaki şablona uygun olarak hazırlanmış Excel (.xls, .xlsx) veya CSV (.csv) dosyasını seçin.</p>
    </div>

    <div class="mb-4">
        <a href="/path/to/your/template/OgrenciListesi_Sablon.xlsx" class="btn btn-info btn-icon-split" download>
            <span class="icon text-white-50">
                <i class="fas fa-download"></i>
            </span>
            <span class="text">Örnek Şablonu İndir</span>
        </a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Dosya Yükleme Formu</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/students/import') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="file">Yüklenecek Dosyayı Seçin</label>
                    <input type="file" class="form-control-file" id="file" name="file" required accept=".xls, .xlsx, .csv">
                </div>
                <button type="submit" class="btn btn-primary">Yükle ve İşle</button>
            </form>
        </div>
    </div>

</div>
<?= $this->endSection() ?>