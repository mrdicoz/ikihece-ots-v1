<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-people-fill"></i> <?= esc($title) ?></h1>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="teachersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Fotoğraf</th>
                            <th>Adı Soyadı</th>
                            <th>Branşı</th>
                            <th style="width: 120px;" class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td class="align-middle text-center">
                                <img src="<?= base_url($teacher->profile_photo ?? 'uploads/profile_photos/default.png') ?>" 
                                     alt="<?= esc($teacher->first_name) ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                            </td>
                            <td class="align-middle fw-bold">
                                <?= esc($teacher->first_name) ?> <?= esc($teacher->last_name) ?>
                            </td>
                            <td class="align-middle">
                                <?= esc($teacher->branch) ?>
                            </td>
                            <td class="align-middle text-center">
                                <a href="<?= site_url('teachers/show/' . $teacher->user_id) ?>" class="btn btn-success btn-sm" title="Görüntüle"><i class="bi bi-eye-fill"></i></a>
                            </td>
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
        const teachersTable = $('#teachersTable').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
            "paging": true,
            "searching": true,
            "info": true,
            "lengthChange": true,
            "pageLength": 10,
            "order": [[ 1, "asc" ]], 
            "columnDefs": [
                { "type": "turkish", "targets": "_all" },
                { "orderable": false, "targets": [0, 3] } // Fotoğraf ve İşlemler kolonlarında sıralama olmasın
            ],
        });

        forceUppercaseSearch(teachersTable);
    });
</script>
<?= $this->endSection() ?>
