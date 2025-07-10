<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="bi bi-person-lines-fill"></i> <?= esc($title) ?></h4>
                <a href="<?= route_to('admin.users.index') ?>" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kullanıcı Listesine Dön
                </a>
            </div>

            <div class="card">
                <div class="row g-0">
                    <div class="col-md-4 text-center p-3">
                        <img src="<?= base_url($profile->profile_photo ?? 'assets/images/user.jpg') ?>" class="img-fluid rounded-circle" alt="<?= esc($profile->first_name ?? '') ?>" style="width: 150px; height: 150px; object-fit: cover;">
                        <h5 class="mt-3"><?= esc($profile->first_name ?? '') ?> <?= esc($profile->last_name ?? '') ?></h5>
                        <p class="text-muted">@<?= esc($user->username) ?></p>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <h5 class="card-title">Kullanıcı Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>E-posta:</strong> <?= esc($user->email ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Telefon:</strong> <?= esc($profile->phone_number ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Durum:</strong>
                                    <?php if ($user->active): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pasif</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item"><strong>Gruplar:</strong>
                                    <?php if (!empty($groups)):
                                        foreach($groups as $group): ?>
                                            <span class="badge bg-secondary"><?= esc(trim($group->group)) ?></span>
                                        <?php endforeach;
                                    else:
                                        echo 'Grup atanmamış';
                                    endif; ?>
                                </li>
                                <li class="list-group-item"><strong>Kayıt Tarihi:</strong> <?= \CodeIgniter\I18n\Time::parse($user->created_at)->toLocalizedString('d MMMM yyyy') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="<?= route_to('admin.users.edit', $user->id) ?>" class="btn btn-warning">
                        <i class="bi bi-pencil-square"></i> Düzenle
                    </a>
                    
                    <?php if (auth()->id() != $user->id && $user->id != 1): ?>
                        <form action="<?= route_to('admin.users.delete', $user->id) ?>" method="post" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash-fill"></i> Sil
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<?= $this->endSection() ?>