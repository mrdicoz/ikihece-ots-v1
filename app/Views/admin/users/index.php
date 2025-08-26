<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-6">
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-people"></i> <?= esc($title) ?></h1>
        </div>
        <div class="col-6 text-end">
            <a href="<?= route_to('admin.users.new') ?>" class="btn btn-success btn-sm">
                <i class="bi bi-person-plus-fill fa-sm text-white-50"></i> Yeni Kullanıcı Ekle
            </a>
        </div>
    </div>
    
<ul class="nav nav-tabs nav-pills nav-justified gap-2 mb-4">
    <li class="nav-item">
        <a class="nav-link <?= is_null($currentGroup) ? 'active bg-success text-white' : '' ?>" aria-current="page" href="<?= route_to('admin.users.index') ?>">Tüm Kullanıcılar</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $currentGroup === 'calisan' ? 'active bg-success text-white' : '' ?>" href="<?= route_to('admin.users.index.filtered', 'calisan') ?>">Çalışanlar</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $currentGroup === 'veli' ? 'active bg-success text-white' : '' ?>" href="<?= route_to('admin.users.index.filtered', 'veli') ?>">Veliler</a>
    </li>
</ul>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="users-table">
                    <thead>
                        <tr>
                            <th>Fotoğraf</th>
                            <th>Kullanıcı Adı</th>
                            <th class="d-none d-lg-table-cell">Ad Soyad</th>
                            <th class="d-none d-md-table-cell">Branş</th>
                            <th class="d-none d-lg-table-cell">Gruplar</th>
                            <th class="d-none d-lg-table-cell">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-href="<?= route_to('admin.users.show', $user->id) ?>" style="cursor: pointer;">
                                <td>
                                    <img src="<?= base_url($user->profile_photo ?: 'assets/images/user.jpg') ?>" 
                                         alt="<?= esc($user->first_name) ?>" 
                                         class="rounded-circle" width="40" height="40"
                                         style="object-fit: cover;">
                                </td>
                                <td><?= esc($user->username) ?></td>
                                <td class="d-none d-lg-table-cell"><?= esc($user->first_name ?? '') ?> <?= esc($user->last_name ?? '') ?></td>
                                <td class="d-none d-md-table-cell"><?= esc($user->branch ?? '---') ?></td>
                                <td class="d-none d-lg-table-cell">
                                    <?php 
                                    $groups = explode(',', $user->user_groups ?? '');
                                    foreach($groups as $group): 
                                        if (!empty(trim($group))): ?>
                                            <span class="badge bg-secondary"><?= esc(trim($group)) ?></span>
                                    <?php 
                                        endif;
                                    endforeach; ?>
                                </td>
                                <td>
                                    <?php if ($user->deleted_at !== null): ?>
                                        <span class="badge bg-dark">Silinmiş</span>
                                    <?php elseif ($user->active): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pasif</span>
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
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        $('#users-table').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json" },
            "columnDefs": [
                { "type": "turkish", "targets": "_all" }
            ],
            "order": []
        });

        $('#users-table tbody').on('click', 'tr', function () {
            window.location.href = $(this).data('href');
        });
    });
</script>
<?= $this->endSection() ?>