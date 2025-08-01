<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-week"></i> Ders Programım</h1>
        
        <?php
            // HAFTALIK NAVİGASYON İÇİN KESİN HESAPLAMA
            // İçinde bulunulan haftanın başlangıç (Pazar) ve bitiş (Cumartesi) günlerini bulalım.
            $dayOfWeek = (int)$currentDate->format('w');
            $startOfWeek = (clone $currentDate)->modify("-{$dayOfWeek} days");
        ?>

        <?php // MASAÜSTÜ NAVİGASYON (Geniş ekranlarda görünür) ?>
        <div class="btn-group d-none d-lg-block" role="group">
            <a href="<?= route_to('schedule.my', ['date' => (clone $startOfWeek)->modify('-1 week')->format('Y-m-d')]) ?>" class="btn btn-outline-success">
                <i class="bi bi-arrow-left"></i> Önceki Hafta
            </a>
            <a href="<?= route_to('schedule.my', ['date' => 'today']) ?>" class="btn btn-success">Bu Hafta</a>
            <a href="<?= route_to('schedule.my', ['date' => (clone $startOfWeek)->modify('+1 week')->format('Y-m-d')]) ?>" class="btn btn-outline-success">
                Sonraki Hafta <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        
        <?php // MOBİL NAVİGASYON (Küçük ekranlarda görünür) ?>
        <div class="btn-group d-lg-none w-100" role="group">
            <a href="<?= route_to('schedule.my', ['date' => (clone $currentDate)->modify('-1 day')->format('Y-m-d')]) ?>" class="btn btn-outline-success">
                <i class="bi bi-chevron-left"></i> Önceki Gün
            </a>
            <a href="<?= route_to('schedule.my', ['date' => 'today']) ?>" class="btn btn-success">Bugün</a>
            <a href="<?= route_to('schedule.my', ['date' => (clone $currentDate)->modify('+1 day')->format('Y-m-d')]) ?>" class="btn btn-outline-success">
                Sonraki Gün <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
    
    <?php 
        $dayNames = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
        $todayKey = $currentDate->format('Y-m-d');
        $todayLessons = $scheduleData[$todayKey] ?? [];
    ?>

    <div class="card shadow d-none d-lg-block">
        <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th style="width: 100px;">Saat</th>
                            <?php foreach ($weekDates as $day): ?>
                                <th class="<?= ($day->format('Y-m-d') == date('Y-m-d')) ? 'table-success' : '' ?>">
                                    <div><?= esc($dayNames[$day->format('w')]) ?></div>
                                    <small class="fw-normal"><?= $day->format('d.m') ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($hour = 8; $hour <= 18; $hour++): 
                            $time = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                        ?>
                        <tr>
                            <td class="fw-bold text-center align-middle bg-light"><?= $time ?></td>
                            <?php foreach ($weekDates as $day): 
                                $lessonsInSlot = $scheduleData[$day->format('Y-m-d')][$time] ?? [];
                            ?>
                            <td class="p-1 align-top" style="min-width: 150px;">
                                <?php if (!empty($lessonsInSlot)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($lessonsInSlot as $lesson): ?>
                                            <a href="<?= site_url('students/' . $lesson['student_id']) ?>" class="list-group-item list-group-item-action p-2" data-bs-toggle="tooltip" title="<?= esc(date('H:i', strtotime($lesson['start_time']))) ?> - <?= esc(date('H:i', strtotime($lesson['end_time']))) ?>">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= base_url($lesson['profile_image'] ?? 'assets/images/user.jpg') ?>" class="rounded-circle me-2" alt="<?= esc($lesson['adi']) ?>" style="width:28px; height:28px; object-fit:cover;">
                                                    <small class="text-truncate"><?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?></small>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-lg-none">
        <div class="card shadow">
            <div class="card-header text-center fw-bold fs-5 <?= ($currentDate->format('Y-m-d') == date('Y-m-d')) ? 'bg-success text-white' : 'bg-light' ?>">
                <?= esc($dayNames[$currentDate->format('w')]) ?>
                <small class="d-block fw-normal fs-6"><?= $currentDate->format('d.m.Y') ?></small>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($todayLessons)): ?>
                    <li class="list-group-item p-4 text-center text-muted">
                        Bu gün için planlanmış ders bulunmamaktadır.
                    </li>
                <?php else: ?>
                    <?php for ($hour = 8; $hour <= 18; $hour++): 
                        $time = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                        $lessonsInHour = $todayLessons[$time] ?? [];
                    ?>
                        <li class="list-group-item d-flex p-2">
                            <div class="fw-bold text-center text-muted me-3 border-end pe-3" style="width: 60px;"><?= $time ?></div>
                            <div class="flex-grow-1">
                                <?php if (!empty($lessonsInHour)): ?>
                                    <?php foreach ($lessonsInHour as $lesson): ?>
                                        <a href="<?= site_url('students/' . $lesson['student_id']) ?>" class="text-decoration-none text-body">
                                            <div class="d-flex align-items-center p-2 rounded mb-1 bg-light">
                                                <img src="<?= base_url($lesson['profile_image'] ?? 'assets/images/user.jpg') ?>" class="rounded-circle me-3" alt="<?= esc($lesson['adi']) ?>" style="width:36px; height:36px; object-fit:cover;">
                                                <div class="text-truncate">
                                                    <span class="fw-bold"><?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?></span>
                                                    <small class="d-block text-muted"><?= esc(date('H:i', strtotime($lesson['start_time']))) ?> - <?= esc(date('H:i', strtotime($lesson['end_time']))) ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endfor; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    // Bootstrap Tooltip'lerini etkinleştir
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
<?= $this->endSection() ?>