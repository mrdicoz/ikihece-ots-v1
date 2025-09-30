<!DOCTYPE html>
<html lang="tr" data-bs-theme="auto">
<head>
    <?= $this->include('layouts/partials/_head') ?>
</head>
<body>
    <?= $this->include('layouts/partials/_session_messages') ?>
    <?= $this->include('layouts/partials/_navbar') ?>

    <main class="container py-4 mt-5">
        <?php
    // Her sayfada lisans servisini ve kalan günleri kontrol edelim.
    $licenseService = new \App\Libraries\LicenseService();
    $daysRemaining = $licenseService->getDaysRemaining();
?>

<div class="container pt-3"> 

    <?php
        if (auth()->loggedIn() && auth()->user()->inGroup('admin', 'yonetici')) {
            if (isset($daysRemaining) && $daysRemaining !== null && $daysRemaining <= 10) {
        ?>

            <div class="alert alert-danger" role="alert">
<strong>Uyarı!</strong> Lisansınızın sona ermesine sadece <strong><?= $daysRemaining ?> gün</strong> kaldı. Sorun yaşamamak için lütfen lisansınızı <a href="https://mantaryazilim.tr/ikihece-okul-takip-sistemi-v1/" class="alert-link">yenileyin</a>.
</div>
        <?php
            }
        }
        ?>

</div>
        <?= $this->renderSection('main') ?>
    </main>

    <?= $this->include('layouts/partials/_footer') ?>

    <?= $this->include('layouts/partials/_scripts') ?>

<div class="modal fade" id="roleSwitcherModal" tabindex="-1" aria-labelledby="roleSwitcherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleSwitcherModalLabel"><i class="bi bi-people"></i> Görünümü Değiştir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Lütfen devam etmek istediğiniz rolü seçin:</p>
                <div class="list-group">
                    <?php // Değişkenler zaten mevcut olduğu için buradaki PHP bloğunu sildik. ?>
                    <?php if(isset($userGroups)): // Sadece değişkenin varlığını kontrol etmek yeterli ?>
                        <?php foreach ($userGroups as $group): ?>
                            <a href="<?= route_to('user.switchRole', strtolower($group)) ?>" 
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= (strtolower($group) === $activeRole) ? 'active' : '' ?>">
                                <?= esc(ucfirst($group)) ?>
                                <?php if (strtolower($group) === $activeRole): ?>
                                    <span class="badge bg-light text-success rounded-pill"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


</body>
</html>