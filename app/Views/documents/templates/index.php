<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-file-earmark-text"></i> <?= esc($title) ?></h1>
        <a href="<?= base_url('documents/templates/create') ?>" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Yeni Şablon
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="templatesTable">
                    <thead>
                        <tr>
                            <th>Şablon Adı</th>
                            <th>Kategori</th>
                            <th>Açıklama</th>
                            <th>Durum</th>
                            <th width="150">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?= esc($template->name) ?></td>
                                <td><span class="badge bg-secondary"><?= esc($template->category_name) ?></span></td>
                                <td><?= esc($template->description) ?></td>
                                <td>
                                    <?php if ($template->active): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url("documents/templates/edit/{$template->id}") ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= base_url("documents/templates/delete/{$template->id}") ?>" class="btn btn-sm btn-danger" title="Sil" onclick="return confirm('Bu şablonu silmek istediğinize emin misiniz?')">
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
        $('#templatesTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json' }
        });
    });
</script>
<?= $this->endSection() ?>