<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-backpack2"></i> Öğrenci Profili</h1>
        
               <div class="mt-2 mt-sm-0">
             <div class="d-grid d-sm-flex gap-2">
                <a href="<?= site_url('students') ?>" class="btn btn-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left"></i> Listeye Dön
                </a>
                <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
                    <i class="bi bi-trash-fill"></i> Öğrenciyi Sil
                </button>
            </div>
        </div>

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
                            <h5 class="card-title">Temel Bilgiler</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Cinsiyet:</strong> <?= esc($student['cinsiyet'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Doğum Tarihi:</strong> <?= esc($student['dogum_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Servis Durumu:</strong> <?= esc($student['servis_durumu'] ?? 'Belirtilmemiş') ?></li>
                            </ul>
                            <hr>
                            <h5 class="card-title">Acil Durum Kişisi</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['acil_durum_aranacak_kisi_1_adi'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Yakınlık:</strong> <?= esc($student['acil_durum_aranacak_kisi_1_yakinlik'] ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['acil_durum_aranacak_kisi_1_telefon'] ?? 'Belirtilmemiş') ?></li>
                            </ul>
                        </div>
                        
                        <div class="tab-pane fade" id="veli" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">

                            <h5 class="card-title">Anne Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['veli_anne_adi_soyadi']) ?></li>
                                <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['veli_anne_telefon']) ?></li>
                                <li class="list-group-item"><strong>E-posta:</strong> <?= esc($student['veli_anne_eposta']) ?></li>
                                <li class="list-group-item"><strong>TCKN:</strong> <?= esc($student['veli_anne_tc']) ?></li>
                            </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title">Baba Bilgileri</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Adı Soyadı:</strong> <?= esc($student['veli_baba_adi_soyadi']) ?></li>
                                        <li class="list-group-item"><strong>Telefon:</strong> <?= esc($student['veli_baba_telefon']) ?></li>
                                        <li class="list-group-item"><strong>E-posta:</strong> <?= esc($student['veli_baba_eposta']) ?></li>
                                        <li class="list-group-item"><strong>TCKN:</strong> <?= esc($student['veli_baba_tc']) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="saglik" role="tabpanel">
                                    <h5 class="card-title">Genel Sağlık Bilgileri</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Kan Grubu:</strong>  <?= esc($student['kan_grubu'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Geçirilen Hastalıklar:</strong>  <?= esc($student['gecirilen_hastaliklar'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Alerjiler:</strong>  <?= esc($student['alerjiler'] ?? 'Belirtilmemiş') ?></li>
                                </ul>
    <hr>

                                        <h5 class="card-title">RAM Rapor Bilgileri</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Rapor Başlangıç Tarihi:</strong>  <?= esc($student['ram_baslangic_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Rapor Bitiş Tarihi:</strong>  <?= esc($student['ram_bitis_tarihi'] ?? 'Belirtilmemiş') ?></li>
                                    <li class="list-group-item"><strong>Rapor Dosyası:</strong>
                                    <?php if (!empty($student['ram_raporu'])): ?>
        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="<?= site_url('students/view-ram-report/' . $student['id']) ?>">
            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
        </button>
    <?php else: ?>
        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" data-src="#" disabled>
            <i class="bi bi-eye-fill"></i> Raporu Görüntüle
        </button>
    <?php endif; ?>
                                </li>
                                </ul>
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

<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteStudentModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> Silme İşlemi Onayı</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><b><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></b> adlı öğrenciyi kalıcı olarak silmek (arşivlemek) üzeresiniz. Bu işlem geri alınamaz.</p>
        <p>Devam etmek için lütfen aşağıdaki alana öğrencinin tam adını (<strong class="text-danger"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></strong>) yazarak onaylayın:</p>
        <div class="mb-3">
            <label for="studentNameConfirm" class="form-label">Onay için Ad Soyad:</label>
            <input type="text" class="form-control" id="studentNameConfirm" autocomplete="off">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
        <form action="<?= site_url('students/' . $student['id']) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" id="confirmDeleteButton" class="btn btn-danger" disabled>Evet, Sil</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Rapor Görüntüleme Modal Script'i
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const pdfUrl = button.getAttribute('data-src');
            const iframe = document.getElementById('report-iframe');
            iframe.setAttribute('src', pdfUrl);
        });
        reportModal.addEventListener('hidden.bs.modal', function () {
            const iframe = document.getElementById('report-iframe');
            iframe.setAttribute('src', '');
        });
    }

    // Silme Onay Modal Script'i
    const deleteModalEl = document.getElementById('deleteStudentModal');
    if (deleteModalEl) {
        const studentName = "<?= esc($student['adi'] . ' ' . $student['soyadi']) ?>";
        const confirmInput = deleteModalEl.querySelector('#studentNameConfirm');
        const confirmButton = deleteModalEl.querySelector('#confirmDeleteButton');

        confirmInput.addEventListener('input', function() {
            if (confirmInput.value.trim().toLowerCase() === studentName.toLowerCase()) {
                confirmButton.disabled = false;
            } else {
                confirmButton.disabled = true;
            }
        });
        
        deleteModalEl.addEventListener('hidden.bs.modal', function () {
            confirmInput.value = '';
            confirmButton.disabled = true;
        });
    }
});
</script>
<?= $this->endSection() ?>