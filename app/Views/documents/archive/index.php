<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-archive"></i> <?= esc($title) ?>
        </h1>
        <div>
            <a href="<?= base_url('documents/archive/search') ?>" class="btn btn-warning">
                <i class="bi bi-search"></i> Detaylı Arama
            </a>
            <a href="<?= base_url('documents/create') ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Yeni Belge
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-hover" id="archiveTable">
                <thead>
                    <tr>
                        <th>Evrak No</th>
                        <th>Konu</th>
                        <th>Kategori</th>
                        <th>Şablon</th>
                        <th>Oluşturan</th>
                        <th>Tarih</th>
                        <th width="200">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><strong><?= esc($doc->document_number ?? '-') ?></strong></td>
                            <td><?= esc($doc->subject) ?></td>
                            <td><span class="badge bg-info"><?= esc($doc->category_name) ?></span></td>
                            <td><?= esc($doc->template_name) ?></td>
                            <td><?= esc($doc->creator_name) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($doc->created_at)) ?></td>
                            <td>
                                <a href="<?= base_url("documents/view-pdf/{$doc->id}") ?>" class="btn btn-sm btn-primary" target="_blank" title="Görüntüle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= base_url("documents/download-pdf/{$doc->id}") ?>" class="btn btn-sm btn-success" title="İndir">
                                    <i class="bi bi-download"></i>
                                </a>
                                <?php if ($isAdmin): ?>
                                    <a href="<?= base_url("documents/archive/delete/{$doc->id}") ?>" class="btn btn-sm btn-danger" title="Sil" onclick="return confirm('Bu belgeyi silmek istediğinize emin misiniz?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        $('#archiveTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
            },
            order: [[5, 'desc']] // 6. sütun olan Tarih'e göre tersten sırala
        });
    });
</script>
<?= $this->endSection() ?>