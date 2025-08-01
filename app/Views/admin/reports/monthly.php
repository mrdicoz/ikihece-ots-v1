<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-bar-chart-line-fill"></i> <?= esc($title) ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="<?= route_to('admin.reports.monthly') ?>" method="post">
                <?= csrf_field() ?>
                <div class="row align-items-end g-3">
                    <div class="col-md-5">
                        <label for="year" class="form-label fw-bold">Yıl Seçin</label>
                        <select name="year" id="year" class="form-select">
                            <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                                <option value="<?= $y ?>" <?= ($y == $selectedYear) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="month" class="form-label fw-bold">Ay Seçin</label>
                        <select name="month" id="month" class="form-select">
                            <?php 
                                $turkishMonths = [
                                    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                                    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                                    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
                                ];
                                foreach ($turkishMonths as $num => $name): ?>
                                <option value="<?= $num ?>" <?= ($num == $selectedMonth) ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-funnel-fill"></i> Getir
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <h4 class="text-muted mb-3"><?= esc($turkishMonths[$selectedMonth]) ?> <?= esc($selectedYear) ?> Ayı Özeti</h4>
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Toplam Ders Saati</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($summary['total_hours'] ?? 0) ?> Saat</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-clock-history fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
             <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ders Alan Öğrenci</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($summary['total_students'] ?? 0) ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-person-check-fill fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bireysel Ders Adedi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($summary['total_individual'] ?? 0) ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-person-fill fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Grup Dersi Adedi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($summary['total_group'] ?? 0) ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-people-fill fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-success">Öğrenci Bazlı Ders Raporu</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="studentReportTable" width="100%">
                            <thead>
                                <tr>
                                    <th>Öğrenci</th>
                                    <th>Toplam Ders</th>
                                    <th>Bireysel</th>
                                    <th>Grup</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($studentReport as $row): ?>
                                    <tr>
                                        <td><?= esc($row['student_name']) ?></td>
                                        <td class="text-center fw-bold"><?= esc($row['total_hours']) ?></td>
                                        <td class="text-center"><?= esc($row['individual_lessons']) ?></td>
                                        <td class="text-center"><?= esc($row['group_lessons']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-danger">Bu Ay Hiç Derse Girmeyenler</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="noLessonStudentTable" width="100%">
                            <thead>
                                <tr>
                                    <th>Öğrenci</th>
                                    <th>Veli Telefon</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($studentsWithNoLessons as $student): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= site_url('students/' . $student['id']) ?>" class="link-dark">
                                                <?= esc($student['student_name']) ?>
                                            </a>
                                        </td>
                                        <td><?= esc($student['veli_anne_telefon'] ?? $student['veli_baba_telefon']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Öğretmen Performans Raporu</h6></div>
        <div class="card-body">
             <div class="table-responsive">
                <table class="table table-bordered table-hover" id="teacherReportTable" width="100%">
                    <thead>
                        <tr>
                            <th>Öğretmen</th>
                            <th>Toplam Ders</th>
                            <th>Bireysel Ders</th>
                            <th>Grup Dersi</th>
                            <th>Eğittiği Öğrenci</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teacherReport as $teacher): ?>
                            <tr>
                                <td>
                                    <img src="<?= base_url($teacher['profile_photo'] ?? 'assets/images/user.jpg') ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                                    <?= esc(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')) ?>
                                </td>
                                <td class="text-center fw-bold"><?= esc($teacher['total_hours']) ?></td>
                                <td class="text-center"><?= esc($teacher['individual_lessons']) ?></td>
                                <td class="text-center"><?= esc($teacher['group_lessons']) ?></td>
                                <td class="text-center"><?= esc($teacher['total_students']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        $('#studentReportTable, #teacherReportTable, #noLessonStudentTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
            "pageLength": 10,
            "order": [[ 1, "desc" ]],
            "info": false
        });
    });
</script>
<?= $this->endSection() ?>