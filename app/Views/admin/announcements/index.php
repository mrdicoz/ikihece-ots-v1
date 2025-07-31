<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-megaphone"></i> <?= esc($title) ?></h1>
        <a href="<?= route_to('admin.announcements.new') ?>" class="btn btn-success btn-sm shadow-sm">
            <i class="bi bi-plus-circle-fill fa-sm text-white-50"></i> Yeni Duyuru Oluştur
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
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Başlık</th>
                            <th>Hedef Kitle</th>
                            <th>Durum</th>
                            <th class="d-none d-md-table-cell">Yazar</th>
                            <th class="d-none d-md-table-cell">Oluşturulma Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($announcements as $ann): ?>
                        <tr>
                            <td class="align-middle fw-bold"><?= esc($ann['title']) ?></td>
                            <td class="align-middle">
                                <?php if($ann['target_group'] === 'all'): ?>
                                    <span class="badge bg-info">Tümü</span>
                                <?php elseif($ann['target_group'] === 'veli'): ?>
                                    <span class="badge bg-primary">Veliler</span>
                                <?php elseif($ann['target_group'] === 'ogretmen'): ?>
                                    <span class="badge bg-warning text-dark">Öğretmenler</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <?php if($ann['status'] === 'published'): ?>
                                    <span class="badge bg-success">Yayınlandı</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Taslak</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle d-none d-md-table-cell"><?= esc($ann['author_name']) ?></td>
                            <td class="align-middle d-none d-md-table-cell"><?= \CodeIgniter\I18n\Time::parse($ann['created_at'])->toLocalizedString('dd MMMM yyyy') ?></td>
                            <td class="align-middle">
                                <a href="<?= route_to('admin.announcements.edit', $ann['id']) ?>" class="btn btn-sm btn-primary" title="Düzenle"><i class="bi bi-pencil-fill"></i></a>
                                <form action="<?= route_to('admin.announcements.delete', $ann['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Bu duyuruyu silmek istediğinizden emin misiniz?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" title="Sil"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= $pager->links('default', 'bootstrap_centered') ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>