<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-calendar-check text-success"></i> Sekreter Paneli
        </h1>
        <div class="text-muted">
            <i class="bi bi-calendar-day"></i> <?= date('d F Y') ?>
        </div>
    </div>

    <!-- Üst Widget Kartları -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Bugün Toplam Ders
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($widgetStats['today_lessons']) ?> Ders
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Bugün Ders Alan Öğrenci
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($widgetStats['today_students']) ?> Kişi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Eksik Bilgili Öğrenci
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($widgetStats['incomplete_students']) ?> Kişi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Bugün Doğum Günü
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($widgetStats['birthdays_today']) ?> Kişi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cake2 fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana İçerik - 2 Kolon -->
    <div class="row">
        <!-- Sol Kolon - Bugünkü Ders Programı -->
        <div class="col-md-8">
            <!-- Hızlı Erişim Butonları -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <a href="<?= site_url('students/new') ?>" class="btn btn-success w-100">
                                <i class="bi bi-person-plus-fill"></i> Yeni Öğrenci Ekle
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="<?= site_url('students') ?>" class="btn btn-outline-success w-100">
                                <i class="bi bi-search"></i> Öğrenci Ara
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bugünkü Ders Programı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="bi bi-calendar-day"></i> Bugünkü Ders Programı
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($todaySchedule)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Saat</th>
                                        <th>Öğretmen</th>
                                        <th>Öğrenci</th>
                                        <th class="text-center">Detay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todaySchedule as $lesson): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <span class="badge bg-primary">
                                                <?= date('H:i', strtotime($lesson['start_time'])) ?>
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <?= esc($lesson['first_name'] . ' ' . $lesson['last_name']) ?>
                                        </td>
                                        <td class="align-middle">
                                            <?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <a href="<?= site_url('schedule/daily/' . date('Y-m-d')) ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-info-circle"></i> Bugün kayıtlı ders bulunmuyor.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon -->
        <div class="col-md-4">
            <!-- Doğum Günü Olanlar -->
            <?php if (!empty($birthdayList)): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="bi bi-cake2-fill"></i> Bugün Doğum Günü Olanlar
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($birthdayList as $student): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-bold"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></div>
                                <small class="text-muted">
                                    Tel: <?= esc($student['veli_anne_telefon'] ?: $student['veli_baba_telefon'] ?: 'Yok') ?>
                                </small>
                            </div>
                            <span class="badge bg-info">🎂</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Eksik Bilgiler -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> Eksik Bilgili Öğrenciler
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($incompleteList)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($incompleteList, 0, 5) as $student): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 incomplete-item">
                                <div>
                                    <div class="fw-bold"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></div>
                                    <small class="text-danger">
                                        <?php
                                        $missing = [];
                                        if (empty($student['ram_raporu'])) $missing[] = 'RAM';
                                        if (empty($student['veli_anne_telefon']) && empty($student['veli_baba_telefon'])) $missing[] = 'Tel';
                                        echo 'Eksik: ' . implode(', ', $missing);
                                        ?>
                                    </small>
                                </div>
                                <a href="<?= site_url('students/' . $student['id'] . '/edit') ?>" 
                                   class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php foreach (array_slice($incompleteList, 5) as $student): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 incomplete-item hidden-incomplete" style="display: none;">
                                <div>
                                    <div class="fw-bold"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></div>
                                    <small class="text-danger">
                                        <?php
                                        $missing = [];
                                        if (empty($student['ram_raporu'])) $missing[] = 'RAM';
                                        if (empty($student['veli_anne_telefon']) && empty($student['veli_baba_telefon'])) $missing[] = 'Tel';
                                        echo 'Eksik: ' . implode(', ', $missing);
                                        ?>
                                    </small>
                                </div>
                                <a href="<?= site_url('students/' . $student['id'] . '/edit') ?>" 
                                   class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($incompleteList) > 5): ?>
                        <div class="text-center mt-3">
                            <button class="btn btn-sm btn-outline-warning" id="toggleIncomplete">
                                <i class="bi bi-chevron-down"></i> Diğerlerini Göster (<?= count($incompleteList) - 5 ?> daha)
                            </button>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle"></i> Tüm öğrencilerin bilgileri eksiksiz!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hızlı Duyuru -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="bi bi-megaphone-fill"></i> Hızlı Duyuru
                    </h6>
                </div>
                <div class="card-body">
                    <form action="<?= route_to('admin.announcements.create') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="published">
                        <div class="mb-3">
                            <label for="quick-title" class="form-label">Başlık</label>
                            <input type="text" name="title" id="quick-title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="quick-body" class="form-label">İçerik</label>
                            <textarea name="body" id="quick-body" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="quick-target" class="form-label">Hedef Kitle</label>
                            <select name="target_group" id="quick-target" class="form-select" required>
                                <option value="all">Tüm Kullanıcılar</option>
                                <option value="veli">Sadece Veliler</option>
                                <option value="ogretmen">Sadece Öğretmenler</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Yayınla</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
</style>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
$(document).ready(function() {
    // Eksik bilgiler toggle
    $('#toggleIncomplete').on('click', function() {
        const hiddenItems = $('.hidden-incomplete');
        const isHidden = hiddenItems.first().is(':hidden');
        
        if (isHidden) {
            hiddenItems.show();
            $(this).html('<i class="bi bi-chevron-up"></i> Daha Az Göster');
        } else {
            hiddenItems.hide();
            $(this).html('<i class="bi bi-chevron-down"></i> Diğerlerini Göster (' + hiddenItems.length + ' daha)');
        }
    });
});
</script>
<?= $this->endSection() ?>