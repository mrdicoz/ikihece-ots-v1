<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="bi bi-list-nested"></i> Menü Yönetimi</h1>
        <div>
            <a href="<?= route_to('admin.menu.group.create') ?>" class="btn btn-outline-success btn-sm me-2">
                <i class="bi bi-folder-plus"></i> Yeni Grup
            </a>
            <a href="<?= route_to('admin.menu.item.create') ?>" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Yeni Menü Öğesi
            </a>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="items-tab" data-bs-toggle="tab" 
                            data-bs-target="#items-content" type="button" role="tab">
                        <i class="bi bi-list-ul"></i> Menü Öğeleri
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="groups-tab" data-bs-toggle="tab" 
                            data-bs-target="#groups-content" type="button" role="tab">
                        <i class="bi bi-folder"></i> Gruplar
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Menü Öğeleri Tab -->
                <div class="tab-pane fade show active" id="items-content" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover" id="menu-table">
                            <thead>
                                <tr>
                                    <th width="5%">Sıra</th>
                                    <th width="25%">Başlık</th>
                                    <th width="15%">Grup</th>
                                    <th width="10%">İkon</th>
                                    <th width="20%">Roller</th>
                                    <th width="10%">Durum</th>
                                    <th width="15%">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-2">Henüz menü öğesi eklenmemiş</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= esc($item->order) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($item->parent_id): ?>
                                                    <i class="bi bi-arrow-return-right text-muted"></i>
                                                <?php endif; ?>
                                                <?= esc($item->title) ?>
                                                <?php if ($item->is_dropdown): ?>
                                                    <i class="bi bi-caret-down-fill text-success ms-1" title="Dropdown Menü"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $group = array_filter($groups, fn($g) => $g->id == $item->group_id);
                                                if ($group) {
                                                    $groupData = reset($group);
                                                    echo '<span class="badge bg-light text-dark">';
                                                    if ($groupData->icon) {
                                                        echo '<i class="bi bi-' . esc($groupData->icon) . '"></i> ';
                                                    }
                                                    echo esc($groupData->title) . '</span>';
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($item->icon): ?>
                                                    <i class="bi bi-<?= esc($item->icon) ?>" style="font-size: 1.5rem;"></i>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item->roles): ?>
                                                    <?php foreach (explode(',', $item->roles) as $role): ?>
                                                        <span class="badge bg-success me-1 mb-1"><?= esc(ucfirst($role)) ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Rol atanmamış</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $item->active ? 'success' : 'danger' ?>">
                                                    <?= $item->active ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= route_to('admin.menu.item.update', $item->id) ?>" 
                                                       class="btn btn-outline-primary" title="Düzenle">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button onclick="deleteMenuItem(<?= $item->id ?>)" 
                                                            class="btn btn-outline-danger" title="Sil">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Gruplar Tab -->
                <div class="tab-pane fade" id="groups-content" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover" id="groups-table">
                            <thead>
                                <tr>
                                    <th width="10%">Sıra</th>
                                    <th width="20%">Grup Adı</th>
                                    <th width="30%">Başlık</th>
                                    <th width="15%">İkon</th>
                                    <th width="15%">Durum</th>
                                    <th width="10%">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($groups)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-folder-x" style="font-size: 3rem;"></i>
                                            <p class="mt-2">Henüz grup eklenmemiş</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($groups as $group): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= esc($group->order) ?></span>
                                            </td>
                                            <td>
                                                <code><?= esc($group->name) ?></code>
                                            </td>
                                            <td>
                                                <strong><?= esc($group->title) ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($group->icon): ?>
                                                    <i class="bi bi-<?= esc($group->icon) ?>" style="font-size: 1.5rem;"></i>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $group->active ? 'success' : 'danger' ?>">
                                                    <?= $group->active ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline-secondary btn-sm" disabled title="Yakında">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Menü öğesi silme fonksiyonu
function deleteMenuItem(id) {
    if (!confirm('Bu menü öğesini silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    button.disabled = true;
    
    fetch('<?= site_url('admin/menu/item/') ?>' + id, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Modal göster
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="bi bi-check-circle-fill me-2"></i>Başarılı</h5>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">Menü öğesi başarıyla silindi.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success" onclick="location.reload()">Tamam</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            alert('Hata: ' + (data.message || 'Silme işlemi başarısız'));
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    })
    .catch(error => {
        alert('Bağlantı hatası: ' + error.message);
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}

// DataTables başlatma
$(document).ready(function() {
    $('#menu-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        },
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true
    });

    $('#groups-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        },
        order: [[0, 'asc']],
        pageLength: 10,
        responsive: true
    });
});
</script>
<?= $this->endSection() ?>