<?php
// Bu kısım BaseController'dan veya bir helper'dan gelecek şekilde ayarlanacak.
// Şimdilik placeholder olarak bırakalım.
$userDisplayName = $userDisplayName ?? auth()->user()->username ?? 'Misafir';
$avatar = $userAvatar ?? base_url('assets/images/user.jpg');
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
            <ul class="navbar-nav ms-auto align-items-lg-center">           

                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-bus-front"></i> Servis</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= route_to('schedule.index') ?>"><i class="bi bi-calendar3"></i> Ders Programı</a>
                </li>                        

                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('/students') ?>"><i class="bi bi-backpack2"></i> Öğrenci Yönetimi</a>
                </li>
                <?php if (auth()->loggedIn() && auth()->user()->inGroup('admin')) : ?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-sliders"></i> Sistem Yönetimi
        </a>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="<?= site_url('admin/users') ?>">
                    <i class="bi bi-people"></i> Kullanıcılar
                </a>
            </li>
               <li>
        <a class="dropdown-item" href="<?= route_to('admin.institution.index') ?>">
            <i class="bi bi-building"></i> Kurum Ayarları
        </a>
    </li>
    <li>
    <a class="dropdown-item" href="<?= route_to('admin.assignments.index') ?>">
        <i class="bi bi-person-badge"></i> Sorumlu Atama
    </a>
</li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= route_to('admin.logs.index') ?>"><i class="bi bi-journal-text"></i> Log Kayıtları</a></li>
            <li><a class="dropdown-item" href="<?= site_url('admin/students/import') ?>"><i class="bi bi-cloud-upload-fill"></i> İçe Aktar</a></li>
            </ul>
    </li>
<?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center">
                                <li class="nav-item dropdown d-none d-lg-block ms-lg-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="me-2 d-none d-xl-inline fw-bold"><?= esc($userDisplayName) ?></span>
                        <img src="<?= $avatar ?>" alt="Kullanıcı Profili" class="rounded-circle" height="32" width="32" style="object-fit: cover;">

                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person"></i> Profilim</a></li>
                        <li>
                            <a class="dropdown-item notification-bell" href="#" title="Bildirim Ayarları">
                                <i class="bi bi-bell-fill"></i> Bildirim Ayarları
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Çıkış Yap</a></li>
                        <li><hr class="dropdown-divider"></li>
                            <li>
                                <div class="px-3 py-2">
                                    <div class="btn-group w-100" role="group" aria-label="Tema Seçimi">
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="light">
                                        <i class="bi bi-sun-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="dark">
                                        <i class="bi bi-moon-stars-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="auto">
                                        <i class="bi bi-circle-half"></i>
                                    </button>
                                    </div>
                                </div>
                            </li>
                    </ul>
                </li>
                 </ul>
            <ul class="navbar-nav d-lg-none w-100 mt-3 border-top pt-3">
                                <li class="nav-item dropdown dropup">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $avatar ?>" alt="Kullanıcı Profili" class="rounded-circle" height="32" width="32" style="object-fit: cover;">
                        <span class="fw-bold ms-1"><?= esc($userDisplayName) ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person-circle"></i> Profilim</a></li>
                        <li>
                            <a class="dropdown-item notification-bell" href="#" title="Bildirim Ayarları">
                                <i class="bi bi-bell-fill"></i> Bildirim Ayarları
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i>Çıkış Yap</a></li>
                        <li><hr class="dropdown-divider"></li>
                            <li>
                                <div class="px-3 py-2">
                                    <div class="btn-group w-100" role="group" aria-label="Tema Seçimi">
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="light">
                                        <i class="bi bi-sun-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="dark">
                                        <i class="bi bi-moon-stars-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary theme-btn" data-theme="auto">
                                        <i class="bi bi-circle-half"></i>
                                    </button>
                                    </div>
                                </div>
                            </li>
                        
                    </ul>
                </li>
                 </ul>
        </div>
    </div>
</nav>