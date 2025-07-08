<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-6">
            <h4><i class="bi bi-people-fill"></i> <?= esc($title) ?></h4>
        </div>
        <div class="col-6 text-end">
            <a href="<?= route_to('admin.users.new') ?>" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Yeni Kullanıcı Ekle
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="users-table">
                    <thead>
                        <tr>
                            <th>Fotoğraf</th>
                            <th>Kullanıcı Adı</th>
                            <th class="d-none d-lg-table-cell">Ad Soyad</th>
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
                                <td class="d-none d-lg-table-cell">
                                    <?php 
                                    $groups = explode(',', $user->user_groups ?? '');
                                    foreach($groups as $group): 
                                        if (!empty(trim($group))): ?>
                                            <span class="badge bg-info text-dark"><?= esc(trim($group)) ?></span>
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
            // Sütunları gizlediğimiz için, Datatables'ın sıralama özelliğinin
            // doğru çalışması için hedef sütunları belirtmek iyi bir pratiktir.
            // Bu örnekte varsayılan sıralama yeterli olacaktır.
        });

        // Satırlara tıklama özelliği ekle
        $('#users-table tbody').on('click', 'tr', function () {
            // Datatables'ın arama sonrası satırları yeniden çizdiğini düşünerek
            // event listener'ı bu şekilde bağlamak daha sağlamdır.
            window.location.href = $(this).data('href');
        });
    });
</script>
<?= $this->endSection() ?>