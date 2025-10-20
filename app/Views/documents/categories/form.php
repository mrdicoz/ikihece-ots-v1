<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-folder-plus"></i> <?= esc($title) ?>
        </h1>
        <a href="<?= base_url('documents/categories') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="post">
                <?= csrf_field() ?>

                <?php if (session('errors')) : ?>
                    <div class="alert alert-danger">
                        <?php foreach (session('errors') as $error) : ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Kategori Adı *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= old('name', $category->name ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Açıklama</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= old('description', $category->description ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="order" class="form-label">Sıra No</label>
                        <input type="number" id="order" name="order" class="form-control" value="<?= old('order', $category->order ?? 0) ?>">
                    </div>

                    <?php if (isset($category)): ?>
                        <div class="col-md-6 mb-3">
                            <label for="active" class="form-label">Durum</label>
                            <select id="active" name="active" class="form-select">
                                <option value="1" <?= old('active', $category->active ?? '') == 1 ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= old('active', $category->active ?? '') == 0 ? 'selected' : '' ?>>Pasif</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Kaydet
                </button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>