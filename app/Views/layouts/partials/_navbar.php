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
            
            <?php if ($isLoggedIn && isset($activeRole)): ?>
            
            <ul class="navbar-nav">
                <?php // --- LİNKLERİN TAMAMI DÜZELTİLDİ --- ?>
                
                <?php if (in_array($activeRole, ['admin', 'yonetici', 'mudur', 'sekreter'])): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('students') ?>"><i class="bi bi-backpack2"></i> Öğrenci Yönetimi</a></li>
                     <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-calendar3"></i> Ders Programı
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= route_to('schedule.index') ?>"><i class="bi bi-calendar-plus"></i> Program Oluştur</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.fixed_schedule.index') ?>"><i class="bi bi-pin-angle-fill"></i> Sabitler</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($activeRole === 'ogretmen'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= route_to('schedule.my') ?>"><i class="bi bi-calendar-week"></i> Ders Programım</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= route_to('students.my') ?>"><i class="bi bi-people"></i> Öğrencilerim</a></li>
                <?php endif; ?>
                
                <?php if ($activeRole === 'veli'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= route_to('parent.schedule') ?>"><i class="bi bi-calendar-check"></i> Çocuğumun Programı</a></li>
                    <?php // Bu rotalar henüz tanımlı değil, şimdilik '#' kalabilir ?>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-chat-dots-fill"></i> Mesajlar</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bus-front"></i> Servis</a></li>
                <?php endif; ?>
                
                <li class="nav-item"><a class="nav-link" href="<?= route_to('announcements.index') ?>"><i class="bi bi-megaphone"></i> Duyurular</a></li>

                <?php if ($activeRole === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sliders"></i> Sistem Yönetimi
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= route_to('admin.users.index') ?>"><i class="bi bi-people"></i> Kullanıcılar</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.institution.index') ?>"><i class="bi bi-building"></i> Kurum Ayarları</a></li>
                            <li><a class="dropdown-item" href="<?= route_to('admin.reports.monthly') ?>"><i class="bi bi-bar-chart-fill"></i> Aylık Raporlar</a></li>
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

            <?php endif; ?>
        </div>
    </div>
</nav>