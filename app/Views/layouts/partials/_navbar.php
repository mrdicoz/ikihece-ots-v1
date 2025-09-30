<?php
// Bu değişkenler BaseController'dan geliyor
$isLoggedIn = auth()->loggedIn();
if ($isLoggedIn) {
    $userDisplayName = $userDisplayName ?? auth()->user()->username ?? 'Misafir';
    $avatar = $userAvatar ?? base_url('assets/images/user.jpg');
}
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary shadow fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?= site_url('/') ?>">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo" height="32">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            
            <?php if ($isLoggedIn && isset($activeRole) && isset($menuData)): ?>
<ul class="navbar-nav">
    <?php foreach ($menuData as $groupName => $items): ?>
        <?php echo view('layouts/partials/_menu_items', ['items' => $items]); ?>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $avatar ?>" alt="Kullanıcı Profili" class="rounded-circle" height="32" width="32" style="object-fit: cover;">
                        <span class="d-none d-lg-inline-block ms-2 fw-bold"><?= esc($userDisplayName) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person"></i> Profilim</a></li>
                        <li><a class="dropdown-item notification-bell" href="#" title="Bildirim Ayarları"><i class="bi bi-bell-fill"></i> Bildirim Ayarları</a></li>
                        
                        <?php if (isset($userGroups) && count($userGroups) > 1): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#roleSwitcherModal">
                                    <i class="bi bi-people"></i> Görünümü Değiştir <span class="badge bg-success ms-1"><?= esc(ucfirst($activeRole)) ?></span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Çıkış Yap</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <div class="px-3 py-2">
                                <div class="btn-group w-100" role="group" aria-label="Tema Seçimi">
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="light"><i class="bi bi-sun-fill"></i></button>
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="dark"><i class="bi bi-moon-stars-fill"></i></button>
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="auto"><i class="bi bi-circle-half"></i></button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>