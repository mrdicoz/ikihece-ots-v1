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
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> <?= esc($title) ?></h5>
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
                        <?php
                        // $item yoksa boş obje oluştur
                        if (!isset($item)) {
                            $item = (object)[
                                'title' => '',
                                'group_id' => '',
                                'parent_id' => '',
                                'route_name' => '',
                                'url' => '',
                                'icon' => '',
                                'order' => 0,
                                'is_dropdown' => 0,
                                'active' => 1
                            ];
                        }

                        if (!isset($selectedRoles)) {
                            $selectedRoles = [];
                        }
                        ?>
                        <?= csrf_field() ?>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Başlık <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?= old('title', $item->title ?? '') ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sıra</label>
                                <input type="number" name="order" class="form-control" 
                                       value="<?= old('order', $item->order ?? 0) ?>" min="0">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Grup</label>
                                <select name="group_id" class="form-select">
                                    <option value="">-- Grupsuz --</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group->id ?>" 
                                            <?= old('group_id', $item->group_id ?? '') == $group->id ? 'selected' : '' ?>>
                                            <?= esc($group->title) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Üst Menü (Dropdown için)</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- Ana Menü --</option>
                                    <?php foreach ($items as $parentItem): ?>
                                        <option value="<?= $parentItem->id ?>" 
                                            <?= old('parent_id', $item->parent_id ?? '') == $parentItem->id ? 'selected' : '' ?>>
                                            <?= esc($parentItem->title) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Alt menü oluşturmak için bir üst menü seçin</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Route Name</label>
                                <input type="text" name="route_name" class="form-control" 
                                       value="<?= old('route_name', $item->route_name ?? '') ?>"
                                       placeholder="admin.users.index">
                                <small class="text-muted">Route name varsa otomatik URL oluşur</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Manuel URL</label>
                                <input type="text" name="url" class="form-control" 
                                       value="<?= old('url', $item->url ?? '') ?>"
                                       placeholder="/admin/users">
                                <small class="text-muted">Route yoksa manuel URL girin</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İkon (Bootstrap Icons)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" name="icon" id="iconInput" class="form-control" 
                                           value="<?= old('icon', $item->icon ?? '') ?>"
                                           placeholder="people">
                                </div>
                                <small class="text-muted">
                                    <a href="https://icons.getbootstrap.com/" target="_blank">
                                        İkonları görüntüle <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </small>
                                <div id="iconPreview" class="mt-2">
                                    <?php if (!empty($item->icon ?? old('icon'))): ?>
                                        <i class="bi bi-<?= esc($item->icon ?? old('icon')) ?>" style="font-size: 2rem;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yetkili Roller <span class="text-danger">*</span></label>
                                <div class="border rounded p-3">
                                    <?php foreach ($roles as $role): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="roles[]" value="<?= $role ?>" 
                                                   id="role_<?= $role ?>"
                                                   <?= in_array($role, $selectedRoles ?? old('roles', [])) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="role_<?= $role ?>">
                                                <?= ucfirst($role) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           name="is_dropdown" value="1" id="isDropdown"
                                           <?= old('is_dropdown', $item->is_dropdown ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isDropdown">
                                        Dropdown Menü (Alt menüleri olacak)
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           name="active" value="1" id="active"
                                           <?= old('active', $item->active ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="active">
                                        Aktif
                                    </label>
                                </div>
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
document.getElementById('iconInput').addEventListener('input', function(e) {
    const preview = document.getElementById('iconPreview');
    const iconName = e.target.value.trim();
    
    if (iconName) {
        preview.innerHTML = `<i class="bi bi-${iconName}" style="font-size: 2rem;"></i>`;
    } else {
        preview.innerHTML = '';
    }
});
</script>
<?= $this->endSection() ?>