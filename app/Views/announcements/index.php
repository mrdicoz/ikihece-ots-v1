<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container mt-4">
    <h1 class="h3 mb-4"><i class="bi bi-megaphone"></i> <?= esc($title) ?></h1>

    <?php if (!empty($announcements) && count($announcements) > 0): ?>
        <div class="list-group">
            <?php foreach ($announcements as $ann): ?>
                <div class="list-group-item list-group-item-action flex-column align-items-start mb-3 shadow-sm rounded">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1 text-success"><?= esc($ann['title']) ?></h5>
                        <small class="text-muted"><?= \CodeIgniter\I18n\Time::parse($ann['created_at'])->humanize() ?></small>
                    </div>
                    <p class="mb-1"><?= nl2br(esc($ann['body'])) ?></p>
                    <small class="text-muted">Yayınlayan: <?= esc($ann['author_name']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <?= $pager->links('default', 'bootstrap_centered') ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle-fill fs-3"></i>
            <p class="mt-2 mb-0">Henüz size yönelik bir duyuru yayınlanmadı.</p>
        </div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>