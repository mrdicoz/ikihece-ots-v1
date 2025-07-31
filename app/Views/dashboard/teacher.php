<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Öğretmen Paneli</h1>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-calendar-day"></i> Günün Programı</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($gununDersleri)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($gununDersleri as $ders): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="<?= site_url('students/' . $ders['student_id']) ?>" class="text-decoration-none text-body d-flex align-items-center">
                                    <img src="<?= base_url($ders['profile_image'] ?? 'assets/images/user.jpg') ?>" class="rounded-circle me-3" style="width:36px; height:36px; object-fit:cover;">
                                    <div>
                                        <div class="fw-bold"><?= esc($ders['adi'] . ' ' . $ders['soyadi']) ?></div>
                                    </div>
                                </a>
                                <span class="badge bg-success rounded-pill">
                                    <?= esc(date('H:i', strtotime($ders['start_time']))) ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-center text-muted mt-3">Bugün için planlanmış dersiniz bulunmamaktadır.</p>
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
                        <ul class="list-group list-group-flush">
                            <?php foreach($duyurular as $duyuru): ?>
                                <li class="list-group-item">
                                    <div class="fw-bold"><?= esc($duyuru['title']) ?></div>
                                    <small class="text-muted"><?= \CodeIgniter\I18n\Time::parse($duyuru['created_at'])->toLocalizedString('d MMMM yyyy') ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
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
                    <a href="<?= route_to('schedule.my') ?>" class="btn btn-outline-success m-2"><i class="bi bi-calendar-week"></i> Ders Programım</a>
                    <a href="<?= route_to('students.my') ?>" class="btn btn-outline-success m-2"><i class="bi bi-people"></i> Tüm Öğrencilerim</a>
                    <a href="<?= route_to('announcements.index') ?>" class="btn btn-outline-success m-2"><i class="bi bi-megaphone"></i> Tüm Duyurular</a>
                </div>
             </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>