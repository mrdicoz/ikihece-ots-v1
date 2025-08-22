<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-people"></i> <?= esc($title) ?></h1>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="studentsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Fotoğraf</th>
                            <th>Adı Soyadı</th>
                            <th class="d-none d-md-table-cell">Veli (Anne)</th>
                            <th class="d-none d-md-table-cell">Telefon (Anne)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr data-href="<?= site_url('students/' . $student['id']) ?>" style="cursor: pointer;">
                            <td class="align-middle">
                                <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') . '?v=' . time() ?>" 
                                     alt="<?= esc($student['adi']) ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                            </td>
                            <td class="align-middle fw-bold">
                                <?= esc($student['adi']) . ' ' . esc($student['soyadi']) ?>
                                <div class="d-md-none text-muted small">
                                    Veli: <?= esc($student['veli_anne']) ?>
                                </div>
                            </td>
                            <td class="align-middle d-none d-md-table-cell"><?= esc($student['veli_anne']) ?></td>
                            <td class="align-middle d-none d-md-table-cell"><?= esc($student['veli_anne_telefon']) ?></td>
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
        $('#studentsTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
            "order": [[ 1, "asc" ]]
        });
        $('#studentsTable tbody').on('click', 'tr', function() {
            window.location.href = $(this).data('href');
        });
    });
</script>
<?= $this->endSection() ?>