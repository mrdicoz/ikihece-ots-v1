<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= route_to('admin.menu.index') ?>">Menü Yönetimi</a></li>
                    <li class="breadcrumb-item active"><?= esc($title) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-folder-plus"></i> <?= esc($title) ?></h5>
                </div>
                <div class="card-body">
                    <?php if (session('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session('errors') as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Grup Adı (Teknik) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= old('name', $group->name ?? '') ?>" required
                                   pattern="[a-z0-9_\-]+"
                                   placeholder="ogrenci_yonetimi">
                            <small class="text-muted">Küçük harf, rakam, tire ve alt çizgi kullanılabilir</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Grup Başlığı <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" 
                                   value="<?= old('title', $group->title ?? '') ?>" required
                                   placeholder="Öğrenci Yönetimi">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">İkon (Bootstrap Icons)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="icon" id="groupIcon" class="form-control" 
                                       value="<?= old('icon', $group->icon ?? '') ?>"
                                       placeholder="folder">
                            </div>
                            <div id="groupIconPreview" class="mt-2">
                                <?php if (!empty($group->icon ?? old('icon'))): ?>
                                    <i class="bi bi-<?= esc($group->icon ?? old('icon')) ?>" style="font-size: 2rem;"></i>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sıra</label>
                            <input type="number" name="order" class="form-control" 
                                   value="<?= old('order', $group->order ?? 0) ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       name="active" value="1" id="groupActive"
                                       <?= old('active', $group->active ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="groupActive">
                                    Aktif
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="<?= route_to('admin.menu.index') ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Geri
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('groupIcon').addEventListener('input', function(e) {
    const preview = document.getElementById('groupIconPreview');
    const iconName = e.target.value.trim();
    
    if (iconName) {
        preview.innerHTML = `<i class="bi bi-${iconName}" style="font-size: 2rem;"></i>`;
    } else {
        preview.innerHTML = '';
    }
});
</script>
<?= $this->endSection() ?>