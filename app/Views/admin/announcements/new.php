<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <h1 class="h3 mb-3"><i class="bi bi-megaphone-fill"></i> <?= esc($title) ?></h1>

    <div class="card shadow">
        <div class="card-body">
            <?= service('validation')->listErrors('list') ?>

            <form action="<?= route_to('admin.announcements.create') ?>" method="post">
                <?= $this->include('admin/announcements/_form') ?>

                <div class="text-end mt-3">
                    <a href="<?= route_to('admin.announcements.index') ?>" class="btn btn-secondary">İptal</a>
                    <button type="submit" class="btn btn-success">Duyuruyu Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>