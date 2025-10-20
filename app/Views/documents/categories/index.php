<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-folder"></i> <?= esc($title) ?>
        </h1>
        <a href="<?= base_url('documents/categories/create') ?>" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Yeni Kategori
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="categoriesTable">
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>Kategori Adı</th>
                            <th>Açıklama</th>
                            <th>Durum</th>
                            <th width="150">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= $cat->order ?></td>
                                <td><?= esc($cat->name) ?></td>
                                <td><?= esc($cat->description) ?></td>
                                <td>
                                    <?php if ($cat->active): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url("documents/categories/edit/{$cat->id}") ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= base_url("documents/categories/delete/{$cat->id}") ?>" class="btn btn-sm btn-danger" title="Sil" onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz? İlişkili şablonlar da silinebilir.')">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
        $('#categoriesTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json' },
            order: [[ 0, 'asc' ]]
        });
    });
</script>
<?= $this->endSection() ?>