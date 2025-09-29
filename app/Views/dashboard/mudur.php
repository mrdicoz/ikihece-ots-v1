<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-graph-up text-success"></i> Müdür Paneli
        </h1>
        <div class="text-muted">
            <i class="bi bi-calendar-month"></i> <?= date('F Y') ?> Raporu
        </div>
    </div>

    <!-- Widget Kartları -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Bu Ay Toplam Ders Saati
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($monthlyStats['total_lesson_hours']) ?> Saat
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-history fa-2x text-gray-300"></i>
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
                                Ders Alan Öğrenci
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($monthlyStats['students_with_lessons']) ?> Kişi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Ders Almayan Öğrenci
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($monthlyStats['students_no_lessons']) ?> Kişi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-x fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Yeni / Silinen Kayıt
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <span class="text-success"><?= esc($monthlyStats['new_students']) ?></span> / 
                                <span class="text-danger"><?= esc($monthlyStats['deleted_students']) ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-arrow-left-right fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana İçerik - 2 Kolon -->
    <div class="row">
        <!-- Sol Kolon - Öğrenci Raporu -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="bi bi-table"></i> Bu Ay Öğrenci Ders Saati Raporu
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($studentReport)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="studentReportTable" width="100%">
                                <thead>
                                    <tr>
                                        <th>Öğrenci</th>
                                        <th class="text-center">Toplam</th>
                                        <th class="text-center">Bireysel</th>
                                        <th class="text-center">Grup</th>
                                        <th class="d-none d-lg-table-cell">Program</th>
                                        <th class="d-none d-lg-table-cell text-center">RAM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studentReport as $student): ?>
                                    <tr style="cursor: pointer;" data-href="<?= site_url('students/' . $student['id']) ?>">
                                        <td>
                                            <div class="fw-bold"><?= esc($student['student_name']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= esc($student['total_hours']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= esc($student['individual_lessons']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= esc($student['group_lessons']) ?></span>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            <?php
                                            $studentData = (new \App\Models\StudentModel())->find($student['id']);
                                            if ($studentData) {
                                                $programs = explode(',', $studentData['egitim_programi'] ?? '');
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
                                            }
                                            ?>
                                        </td>
                                        <td class="d-none d-lg-table-cell text-center">
                                            <?php
                                            $studentData = (new \App\Models\StudentModel())->find($student['id']);
                                            if ($studentData && !empty($studentData['ram_raporu'])):
                                            ?>
                                                <span class="badge bg-success"><i class="bi bi-check-circle"></i></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-info-circle"></i> Bu ay henüz ders verilmemiş.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon - Duyuru Paneli -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs nav-fill" id="announcementTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-success active" id="new-tab" data-bs-toggle="tab" data-bs-target="#new-panel" type="button" role="tab">Hızlı Duyuru</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="announcementTabContent">
                        <div class="tab-pane fade show active" id="new-panel" role="tabpanel">
                            <form action="<?= route_to('admin.announcements.create') ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="published">
                                <div class="mb-3">
                                    <label for="quick-title" class="form-label">Başlık</label>
                                    <input type="text" name="title" id="quick-title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="quick-body" class="form-label">İçerik</label>
                                    <textarea name="body" id="quick-body" class="form-control" rows="4" required></textarea>
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
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
$(document).ready(function() {
    $('#studentReportTable').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
        "pageLength": 15,
        "order": [[1, 'desc']],
        "searching": true,
        "lengthChange": true,
        "info": true,
        "columnDefs": [
            { "orderable": true, "targets": [0, 1, 2, 3] },
            { "orderable": false, "targets": [4, 5] }
        ]
    });

    $('#studentReportTable tbody').on('click', 'tr', function() {
        window.location.href = $(this).data('href');
    });
});
</script>
<?= $this->endSection() ?>