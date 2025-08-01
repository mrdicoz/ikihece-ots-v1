<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid">

    <?php if (isset($no_student_found)): ?>
        <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle-fill fs-3"></i>
            <h4 class="alert-heading mt-2">Öğrenci Bulunamadı!</h4>
            <p>Sistemde, profilinizdeki T.C. Kimlik Numarası ile eşleşen bir öğrenci kaydı bulunamadı.</p>
            <hr>
            <p class="mb-0">Lütfen <a href="/profile" class="alert-link">profil sayfanızdan</a> T.C. Kimlik Numaranızı kontrol ediniz veya kurum yönetimi ile iletişime geçiniz.</p>
        </div>
    <?php else: ?>
        <div class="row align-items-center mb-4">
    <div class="col-12 col-md-auto">
        <h1 class="h3 mb-2 mb-md-0 text-gray-800">Veli Paneli: <?= esc($active_child['adi'] . ' ' . $active_child['soyadi']) ?></h1>
    </div>
    
    <?php if (count($parent_children) > 1): ?>
    <div class="col-12 col-md-auto ms-md-auto">
        <form action="<?= site_url('dashboard/set-active-child') ?>" method="post" class="w-100">
            <?= csrf_field() ?>
            <div class="input-group">
                <label class="input-group-text" for="child_id"><i class="bi bi-people-fill"></i></label>
                <select name="child_id" id="child_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($parent_children as $child): ?>
                        <option value="<?= $child['id'] ?>" <?= ($child['id'] == $active_child['id']) ? 'selected' : '' ?>>
                            <?= esc($child['adi'] . ' ' . $child['soyadi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

        <div class="row">
            <div class="col-12">
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-calendar-day-fill"></i> Bugünün Ders Programı</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($gununDersleri)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($gununDersleri as $ders): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-success me-2"><?= esc(date('H:i', strtotime($ders['start_time']))) ?></span>
                                    <strong><?= esc($ders['ders_tipi']) ?></strong>
                                </div>
                                <small class="text-muted">
                                    Öğretmen: <?= esc($ders['ogretmen_adi']) ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <p><i class="bi bi-info-circle fs-4"></i></p>
                        Bugün için planlanmış ders bulunmamaktadır.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-megaphone-fill"></i> Kurum Duyuruları</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($duyurular)): ?>
                    <div class="list-group list-group-flush">
                    <?php foreach ($duyurular as $duyuru): ?>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                
                                <h6 class="mb-1"><?= esc($duyuru['title']) ?></h6>
                                <small><?= \CodeIgniter\I18n\Time::parse($duyuru['created_at'])->toLocalizedString('dd MMM') ?></small>
                            </div>
                            <small class="mb-1 text-muted"><?= esc(word_limiter($duyuru['body'], 15)) ?></small>
                        </a>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center small">Yeni duyuru bulunmamaktadır.</p>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="/duyurular" class="btn btn-outline-success btn-sm">Tüm Duyurular</a>
                </div>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-person-video3"></i> Öğretmenleri</h6>
            </div>
            <ul class="list-group list-group-flush">
                 <?php if (!empty($ogretmenler)): ?>
                    <?php foreach($ogretmenler as $ogretmen): ?>
                    <li class="list-group-item d-flex align-items-center">
                        <img src="<?= base_url($ogretmen['profile_photo'] ?? 'assets/images/user.jpg') ?>" class="rounded-circle me-2" alt="<?= esc($ogretmen['first_name'])?>" width="32" height="32">
                        <span><?= esc($ogretmen['first_name'] . ' ' . $ogretmen['last_name']) ?></span>
                        <a href="#" class="btn btn-outline-success btn-sm ms-auto disabled"><i class="bi bi-chat-dots-fill"></i> Mesaj Gönder</a>
                    </li>
                    <?php endforeach; ?>
                 <?php else: ?>
                     <li class="list-group-item text-muted text-center small">Atanmış öğretmen bulunamadı.</li>
                 <?php endif; ?>
            </ul>
        </div>

    </div>
</div>            </div>
        </div>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>