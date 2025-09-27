<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-speedometer2"></i> Yönetim Paneli</h1>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-success shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Toplam Öğrenci</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['students']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people-fill fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-secondary shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Toplam Öğretmen</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['teachers']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-workspace fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-success shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Toplam Veli</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['parents']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-vcard-fill fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-6 mb-4">
                    <div class="card border-start-secondary shadow-sm h-100">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Toplam Servis</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= esc($stats['services']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-bus-front-fill fs-2 text-secondary opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs nav-fill" id="announcementTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-success active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-panel" type="button" role="tab">Son Duyurular</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-success" id="new-tab" data-bs-toggle="tab" data-bs-target="#new-panel" type="button" role="tab">Hızlı Duyuru Ekle</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="announcementTabContent">
                        <div class="tab-pane fade show active" id="list-panel" role="tabpanel">
                            <h5 class="mb-3">Son 5 Duyuru</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach($latestAnnouncements as $ann): ?>
                                    <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <?= esc($ann['title']) ?>
                                        <span>
                                            <?php if($ann['status'] === 'published'): ?>
                                                <span class="badge bg-success">Yayınlandı</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Taslak</span>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="text-end mt-2">
                                <a href="<?= route_to('admin.announcements.index') ?>" class="text-success">Tümünü Gör...</a>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="new-panel" role="tabpanel">
                            <form action="<?= route_to('admin.announcements.create') ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="published"> <div class="mb-3">
                                    <label for="quick-title" class="form-label">Başlık</label>
                                    <input type="text" name="title" id="quick-title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="quick-body" class="form-label">İçerik</label>
                                    <textarea name="body" id="quick-body" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="quick-target" class="form-label">Hedef Kitle</label>
                                    <select name="target_group" id="quick-target" class="form-select" required>
                                        <option value="all">Tüm Kullanıcılar</option>
                                        <option value="veli">Sadece Veliler</option>
                                        <option value="ogretmen">Sadece Öğretmenler</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Yayınla ve Bildirim Gönder</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-success"><i class="bi bi-backpack2-fill"></i> Son Eklenen Öğrenciler</h6>
                    <a href="<?= site_url('students/new') ?>" class="btn btn-sm btn-outline-success" title="Yeni Öğrenci Ekle">
                        <i class="bi bi-person-plus-fill"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="studentDashboardTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Öğrenci</th>
                                    <th class="d-none d-lg-table-cell">Programlar</th>
                                    <th class="d-none d-lg-table-cell text-center">RAM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr data-href="<?= site_url('students/' . $student['id']) ?>" style="cursor: pointer;">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" 
                                                 alt="<?= esc($student['adi']) ?>" 
                                                 class="rounded-circle me-2" width="40" height="40" 
                                                 style="object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?= esc($student['adi']) . ' ' . esc($student['soyadi']) ?></div>
                                                <div class="small text-muted d-lg-none">
                                                <?php
                                                    $programs = explode(',', $student['egitim_programi'] ?? '');
                                                    foreach ($programs as $program) {
                                                        $program = trim($program);
                                                        if (empty($program)) continue;
                                                        $badgeClass = 'bg-secondary'; $badgeHarf = '?';
                                                        switch ($program) {
                                                            case 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-danger'; $badgeHarf = 'F'; break;
                                                            case 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-primary'; $badgeHarf = 'D'; break;
                                                            case 'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-success'; $badgeHarf = 'Z'; break;
                                                            case 'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-warning text-dark'; $badgeHarf = 'Ö'; break;
                                                            case 'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-info text-dark'; $badgeHarf = 'O'; break;
                                                        }
                                                        echo "<span class=\"badge rounded-pill {$badgeClass}\" title=\"".esc($program)."\">{$badgeHarf}</span> ";
                                                    }
                                                ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle d-none d-lg-table-cell">
                                        <?php
                                            $programs = explode(',', $student['egitim_programi'] ?? '');
                                            foreach ($programs as $program):
                                                $program = trim($program);
                                                if (empty($program)) continue;
                                                $badgeClass = 'bg-secondary'; $badgeHarf = '?';
                                                switch ($program) {
                                                    case 'Bedensel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-danger'; $badgeHarf = 'F'; break;
                                                    case 'Dil ve Konuşma Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-primary'; $badgeHarf = 'D'; break;
                                                    case 'Zihinsel Yetersizliği Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-success'; $badgeHarf = 'Z'; break;
                                                    case 'Öğrenme Güçlüğü Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-warning text-dark'; $badgeHarf = 'Ö'; break;
                                                    case 'Otizm Spektrum Bozukluğu Olan Bireyler İçin Destek Eğitim Programı': $badgeClass = 'bg-info text-dark'; $badgeHarf = 'O'; break;
                                                }
                                            ?>
                                            <span class="badge rounded-pill <?= $badgeClass ?>" title="<?= esc($program) ?>"><?= $badgeHarf ?></span>
                                            <?php endforeach; ?>
                                    </td>
                                    <td class="align-middle text-center d-none d-lg-table-cell">
                                        <?php if (!empty($student['ram_raporu'])): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Var</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Yok</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        $('#studentDashboardTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
            "pageLength": 7,
            "searching": false,
            "lengthChange": false,
            "info": false,
             "order": [],
            "columnDefs": [
                { "orderable": false, "targets": [0, 1, 2] }
            ]
        });

        $('#studentDashboardTable tbody').on('click', 'tr', function() {
            window.location.href = $(this).data('href');
        });
    });
</script>
<?= $this->endSection() ?>