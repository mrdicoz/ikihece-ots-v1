<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>

<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= $title ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('students/absences') ?>" method="get">
                        <div class="row align-items-end">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <label for="student_id" class="form-label">Öğrenci Seçin</label>
                                <select name="student_id" id="student_id" class="form-select">
                                    <option value="">Tüm Öğrenciler</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>" <?= ($selectedStudent == $student['id']) ? 'selected' : '' ?>>
                                            <?= esc($student['adi'] . ' ' . $student['soyadi']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-3">
                                <?php
                                $turkishMonths = [
                                    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
                                    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                                ];
                                ?>
                                <label for="month" class="form-label">Ay Seçin</label>
                                <select name="month" id="month" class="form-select">
                                    <?php foreach ($turkishMonths as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= ($selectedMonth == $num) ? 'selected' : '' ?>><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-3">
                                <label for="year" class="form-label">Yıl Seçin</label>
                                <select name="year" id="year" class="form-select">
                                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                        <option value="<?= $i ?>" <?= ($selectedYear == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6 mb-3">
                                <button type="submit" class="btn btn-success w-100">Filtrele</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Devamsızlık Listesi</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($absences)): ?>
                        <table id="absencesTable" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Öğrenci Adı Soyadı</th>
                                    <th>Dersi Veren Öğretmen</th>
                                    <th>Ders Tarihi</th>
                                    <th>Sebep</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($absences as $absence): ?>
                                    <tr>
                                        <td><?= esc($absence['student_name']) ?> <?= esc($absence['student_surname']) ?></td>
                                        <td><?= esc($absence['teacher_first_name']) ?> <?= esc($absence['teacher_last_name']) ?></td>
                                        <td><?= date('d.m.Y', strtotime($absence['lesson_date'])) ?></td>
                                        <td><?= esc($absence['reason']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            Seçilen kriterlere uygun devamsızlık kaydı bulunamadı.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        // Tom-select initialization
        new TomSelect('#student_id', {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
        new TomSelect('#month', { create: false });
        new TomSelect('#year', { create: false });

        // DataTables initialization
        $('#absencesTable').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
            },
            "order": [],
            "responsive": true,
            "columnDefs": [
                { "type": "turkish", "targets": [0, 1, 3] } // Apply Turkish sorting
            ]
        });
    });
</script>
<?= $this->endSection() ?>