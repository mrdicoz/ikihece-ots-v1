<?php
// Bu blok dosyanın en üstünde kalabilir, sorun değil.
$isLoggedIn = auth()->loggedIn();
if ($isLoggedIn) {
    $user = auth()->user();
    $userDisplayName = $userDisplayName ?? $user->username ?? 'Misafir';
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
            
            <?php if ($isLoggedIn): // YENİ KONTROL BAŞLANGICI ?>
            
            <ul class="navbar-nav">
                <?php if ($user->inGroup('admin', 'yonetici', 'mudur', 'sekreter')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('/students') ?>"><i class="bi bi-backpack2"></i> Öğrenci Yönetimi</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('/schedule') ?>"><i class="bi bi-calendar3"></i> Ders Programı</a></li>
                <?php endif; ?>

                <?php if ($user->inGroup('ogretmen')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= route_to('schedule.my') ?>"><i class="bi bi-calendar-week"></i> Ders Programım</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= route_to('students.my') ?>"><i class="bi bi-people"></i> Öğrencilerim</a></li>
                <?php endif; ?>
                
                <?php if ($user->inGroup('veli')): ?>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-calendar-check"></i> Çocuğumun Programı</a></li>
                <?php endif; ?>
                
                <li class="nav-item"><a class="nav-link" href="<?= route_to('announcements.index') ?>"><i class="bi bi-megaphone"></i> Duyurular</a></li>


                <?php if ($user->inGroup('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sliders"></i> Sistem Yönetimi
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('admin/users') ?>"><i class="bi bi-people"></i> Kullanıcılar</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.institution.index') ?>"><i class="bi bi-building"></i> Kurum Ayarları</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.assignments.index') ?>"><i class="bi bi-person-rolodex"></i> Atamalar</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.announcements.index') ?>"><i class="bi bi-megaphone"></i> Duyuru Yap</a></li>               
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.logs.index') ?>"><i class="bi bi-journal-text"></i> Log Kayıtları</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.students.importView') ?>"><i class="bi bi-cloud-upload-fill"></i> İçe Aktar</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.settings.index') ?>"><i class="bi bi-gear-wide-connected"></i> Genel Ayarlar</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $avatar ?>" alt="Kullanıcı Profili" class="rounded-circle" height="32" width="32" style="object-fit: cover;">
                        <span class="d-none d-lg-inline-block ms-2 fw-bold"><?= esc($userDisplayName) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person"></i> Profilim</a></li>
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

            <?php endif; // YENİ KONTROL BİTİŞİ ?>
        </div>
    </div>
</nav>