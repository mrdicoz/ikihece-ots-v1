<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-person-badge"></i> Öğrenci Profili</h1>
        <a href="<?= site_url('students') ?>" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left fa-sm text-white-50"></i> Listeye Dön
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <img class="img-fluid rounded-circle mb-3" src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" alt="Profil Fotoğrafı" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4 class="card-title"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></h4>
                    <p class="text-muted">TCKN: <?= esc($student['tc_kimlik_no']) ?></p>
                    <hr>
    <?php if (!empty($student['ram_raporu'])): ?>
        <button type="button" class="btn btn-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>">
            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
        </button>
    <?php else: ?>
        <button type="button" class="btn btn-success w-100 mt-2" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>" disabled>
            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
        </button>    <?php endif; ?>
                    <a href="<?= site_url('students/' . $student['id'] . '/edit') ?>" class="btn btn-success w-100 mt-2">
                        <i class="bi bi-pencil-square"></i> Bilgileri Düzenle
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs nav-fill" id="studentTab" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link text-success active" id="temel-tab" data-bs-toggle="tab" data-bs-target="#temel" type="button" role="tab">Temel Bilgiler</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="veli-tab" data-bs-toggle="tab" data-bs-target="#veli" type="button" role="tab">Veli Bilgileri</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="saglik-tab" data-bs-toggle="tab" data-bs-target="#saglik" type="button" role="tab">Sağlık</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="adres-tab" data-bs-toggle="tab" data-bs-target="#adres" type="button" role="tab">Adres & Konum</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link text-success" id="diger-tab" data-bs-toggle="tab" data-bs-target="#diger" type="button" role="tab">Diğer</button></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="studentTabContent">
                        <div class="tab-pane fade show active" id="temel" role="tabpanel">
                            <h5 class="mb-3">Temel Bilgiler</h5>
                            <dl class="row"><dt class="col-sm-4">TC Kimlik No</dt><dd class="col-sm-8"><?= esc($student['tc_kimlik_no']) ?></dd><dt class="col-sm-4">Cinsiyet</dt><dd class="col-sm-8"><?= esc($student['cinsiyet']) ?></dd><dt class="col-sm-4">Doğum Tarihi</dt><dd class="col-sm-8"><?= esc($student['dogum_tarihi']) ?></dd></dl>
                            <h5 class="mt-4 mb-3">Okul Bilgileri</h5>
                            <dl class="row"><dt class="col-sm-4">Kayıt Tarihi</dt><dd class="col-sm-8"><?= esc($student['kayit_tarihi']) ?></dd><dt class="col-sm-4">Sınıfı / Şubesi</dt><dd class="col-sm-8"><?= esc($student['sinifi']) ?> / <?= esc($student['subesi']) ?></dd><dt class="col-sm-4">Servis Durumu</dt><dd class="col-sm-8"><?= esc($student['servis_durumu']) ?></dd></dl>
                        </div>
                        
                        <div class="tab-pane fade" id="veli" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6"><h5 class="mb-3">Anne Bilgileri</h5><dl class="row"><dt class="col-sm-5">Adı Soyadı</dt><dd class="col-sm-7"><?= esc($student['veli_anne_adi_soyadi']) ?></dd><dt class="col-sm-5">Telefon</dt><dd class="col-sm-7"><?= esc($student['veli_anne_telefon']) ?></dd></dl></div>
                                <div class="col-md-6"><h5 class="mb-3">Baba Bilgileri</h5><dl class="row"><dt class="col-sm-5">Adı Soyadı</dt><dd class="col-sm-7"><?= esc($student['veli_baba_adi_soyadi']) ?></dd><dt class="col-sm-5">Telefon</dt><dd class="col-sm-7"><?= esc($student['veli_baba_telefon']) ?></dd></dl></div>
                            </div>
                        </div>

<div class="tab-pane fade" id="saglik" role="tabpanel">
    <h5 class="mb-3">Genel Sağlık Bilgileri</h5>
    <dl class="row"><dt class="col-sm-4">Kan Grubu</dt><dd class="col-sm-8"><?= esc($student['kan_grubu']) ?></dd><dt class="col-sm-4">Geçirilen Hastalıklar</dt><dd class="col-sm-8"><?= esc($student['gecirilen_hastaliklar']) ?></dd><dt class="col-sm-4">Alerjiler</dt><dd class="col-sm-8"><?= esc($student['alerjiler']) ?></dd></dl>
    <hr>
    <h5 class="mt-4 mb-3">RAM Rapor Bilgileri</h5>
    <dl class="row">
        <dt class="col-sm-4">Rapor Başlangıç Tarihi</dt>
        <dd class="col-sm-8"><?= esc($student['ram_baslangic_tarihi']) ?></dd>
        <dt class="col-sm-4">Rapor Bitiş Tarihi</dt>
        <dd class="col-sm-8"><?= esc($student['ram_bitis_tarihi']) ?></dd>
        <dt class="col-sm-4">Rapor Dosyası</dt>
<dd class="col-sm-8">
    <?php if (!empty($student['ram_raporu'])): ?>
        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>">
            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
        </button>
    <?php else: ?>
        <span class="text-muted">Rapor yüklenmemiş.</span>
    <?php endif; ?>
</dd>
    </dl>
</div>
                        
                        <div class="tab-pane fade" id="adres" role="tabpanel">
                             <h5 class="mb-3">Adres ve Konum Bilgisi</h5>
                             <p><?= esc($student['adres_detay']) ?> <?= esc($student['adres_mahalle']) ?>, <?= esc($student['adres_ilce']) ?> / <?= esc($student['adres_il']) ?></p>
                             <div id="map-container" class="alert alert-info">Harita özelliği için bir API anahtarı gereklidir. Bu özellik daha sonra eklenecektir.</div>
                        </div>

                        <div class="tab-pane fade" id="diger" role="tabpanel">
                             <h5 class="mb-3">Acil Durum Kişileri</h5>
                             <p><b>Kişi 1:</b> <?= esc($student['acil_durum_aranacak_kisi_1_adi']) ?> (<?= esc($student['acil_durum_aranacak_kisi_1_yakinlik']) ?>) - <?= esc($student['acil_durum_aranacak_kisi_1_telefon']) ?></p>
                             <hr>
                             <h5 class="mt-4 mb-3">Kardeş Bilgileri</h5>
                             <p><?= esc($student['kardes_adi_1']) ?></p>
                             <hr>
                             <h5 class="mt-4 mb-3">Muhasebe Bilgileri</h5>
                             <dl class="row"><dt class="col-sm-4">Sözleşme Tutarı</dt><dd class="col-sm-8"><?= esc($student['sozlesme_tutari']) ?> ₺</dd></dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportModalLabel"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?> - RAM Raporu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="height: 80vh;">
        <iframe id="report-iframe" src="" style="width: 100%; height: 100%;" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('show.bs.modal', function (event) {
            // Modal'ı tetikleyen butonu al
            const button = event.relatedTarget;
            // Butonun data-src özelliğinden PDF linkini al
            const pdfUrl = button.getAttribute('data-src');
            
            // Modal içindeki iframe'i bul
            const iframe = document.getElementById('report-iframe');
            
            // iframe'in src özelliğine PDF linkini ata
            iframe.setAttribute('src', pdfUrl);
        });

        // Modal kapandığında iframe'in içeriğini temizle (kaynak tüketimini önlemek için)
        reportModal.addEventListener('hidden.bs.modal', function () {
            const iframe = document.getElementById('report-iframe');
            iframe.setAttribute('src', '');
        });
    }
});
</script>
<?= $this->endSection() ?>