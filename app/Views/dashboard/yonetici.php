<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-graph-up-arrow text-success"></i> Yönetici Paneli
        </h1>
        <div class="text-muted">
            <i class="bi bi-calendar-month"></i> <?= date('F Y') ?> Raporu
        </div>
    </div>

    <!-- Ana İstatistik Kartları -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Bu Ay Verilen Ders Saati
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($monthlyStats['total_lesson_hours']) ?> Saat
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-history fa-2x text-gray-300"></i>
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
                                Bu Ay Ders Alan Öğrenci
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($monthlyStats['students_with_lessons']) ?> Kişi
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Bu Ay Yeni Kayıt
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($monthlyStats['new_students']) ?> Öğrenci
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-plus fa-2x text-gray-300"></i>
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
                                Bu Ay Silinen Kayıt
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= esc($monthlyStats['deleted_students']) ?> Öğrenci
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-dash fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Bölümü -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="bi bi-pie-chart-fill"></i> Son 6 Ay Ders Dağılımı
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="lessonChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-bar-chart-fill"></i> Aylık Ders Saati Trendi
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana İçerik Satırı -->
    <div class="row">
        <!-- Sol Kolon - Öğretmen Performansı ve En Aktif Öğrenciler -->
        <div class="col-lg-8">
            <!-- Öğretmen Performansı -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="bi bi-person-workspace"></i> Bu Ay Öğretmen Performansı
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($detailedData['teachers_report'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Öğretmen</th>
                                        <th class="text-center">Toplam Ders</th>
                                        <th class="text-center">Bireysel</th>
                                        <th class="text-center">Grup</th>
                                        <th class="text-center">Öğrenci Sayısı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detailedData['teachers_report'] as $teacher): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= base_url($teacher['profile_photo'] ?? 'assets/images/user.jpg') ?>" 
                                                     class="rounded-circle me-2" width="35" height="35" style="object-fit: cover;">
                                                <span class="fw-bold"><?= esc($teacher['first_name'] . ' ' . $teacher['last_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= esc($teacher['total_hours']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= esc($teacher['individual_lessons']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= esc($teacher['group_lessons']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= esc($teacher['total_students']) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-info-circle"></i> Bu ay henüz ders verilmemiş.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- En Aktif Öğrenciler -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="bi bi-trophy-fill"></i> Bu Ay En Aktif Öğrenciler
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($detailedData['top_students'])): ?>
                        <div class="row text-center">
                            <?php foreach (array_slice($detailedData['top_students'], 0, 6) as $index => $student): ?>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                <div class="card border-success h-100">
                                    <div class="card-body p-3">
                                        <!-- Sıralama Badge -->
                                        <div class="position-relative mb-3">
                                            <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>" 
                                                 alt="<?= esc($student['adi']) ?>" 
                                                 class="rounded-circle mx-auto d-block" 
                                                 width="70" height="70" 
                                                 style="object-fit: cover;">
                                            <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill 
                                                       <?= $index === 0 ? 'bg-warning' : ($index === 1 ? 'bg-secondary' : ($index === 2 ? 'bg-info' : 'bg-success')) ?>">
                                                <?= $index + 1 ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Öğrenci Bilgileri -->
                                        <h6 class="card-title mb-2 fw-bold" style="font-size: 0.9rem;">
                                            <?= esc($student['adi']) ?><br>
                                            <small><?= esc($student['soyadi']) ?></small>
                                        </h6>
                                        
                                        <!-- İstatistikler -->
                                        <div class="mb-2">
                                            <span class="badge bg-primary">
                                                <?= esc($student['total_lessons']) ?> Ders
                                            </span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-center gap-1 mb-3">
                                            <span class="badge bg-info" title="Bireysel Dersler" style="font-size: 0.7rem;">
                                                <i class="bi bi-person"></i> <?= esc($student['individual_lessons']) ?>
                                            </span>
                                            <span class="badge bg-success" title="Grup Dersleri" style="font-size: 0.7rem;">
                                                <i class="bi bi-people"></i> <?= esc($student['group_lessons']) ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Detay Butonu -->
                                        <a href="<?= site_url('students/' . $student['id']) ?>" 
                                           class="btn btn-outline-success btn-sm w-100">
                                            <i class="bi bi-eye"></i> Detay
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-info-circle"></i> Bu ay henüz ders verisi bulunmuyor.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon -->
        <div class="col-lg-4">
            <!-- Yeni Kayıtlar -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="bi bi-person-plus-fill"></i> Bu Ay Yeni Kayıtlar
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($detailedData['new_students_list'])): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($detailedData['new_students_list'], 0, 5) as $student): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-bold"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></div>
                                    <small class="text-muted">
                                        <?= date('d.m.Y H:i', strtotime($student['created_at'])) ?>
                                    </small>
                                </div>
                                <a href="<?= site_url('students/' . $student['id']) ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($detailedData['new_students_list']) > 5): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">ve <?= count($detailedData['new_students_list']) - 5 ?> öğrenci daha...</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-info-circle"></i> Bu ay yeni kayıt yok.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ders Almayan Öğrenciler -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> Bu Ay Ders Almayan Öğrenciler
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($detailedData['students_no_lessons'])): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($detailedData['students_no_lessons'], 0, 5) as $student): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-bold"><?= esc($student['student_name']) ?></div>
                                    <small class="text-muted">
                                        Tel: <?= esc($student['veli_anne_telefon'] ?: $student['veli_baba_telefon'] ?: 'Yok') ?>
                                    </small>
                                </div>
                                <a href="<?= site_url('students/' . $student['id']) ?>" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-telephone"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($detailedData['students_no_lessons']) > 5): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">ve <?= count($detailedData['students_no_lessons']) - 5 ?> öğrenci daha...</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle"></i> Tüm öğrenciler bu ay ders aldı!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Silinen Kayıtlar -->
            <?php if (!empty($detailedData['deleted_students_list'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="bi bi-person-dash-fill"></i> Bu Ay Silinen Kayıtlar
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($detailedData['deleted_students_list'], 0, 5) as $student): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-bold text-muted"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></div>
                                <small class="text-danger">
                                    Silindi: <?= date('d.m.Y H:i', strtotime($student['deleted_at'])) ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // PHP'den gelen veriyi JavaScript'e aktar
    const chartData = <?= json_encode($chartData ?? []) ?>;
    
    if (chartData && chartData.length > 0) {
        // 1. PASTA GRAFİĞİ - Son 6 Ay Ders Dağılımı
        const pieCtx = document.getElementById('lessonChart').getContext('2d');
        
        // Son ayın verilerini al
        const lastMonth = chartData[chartData.length - 1];
        
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Bireysel Dersler', 'Grup Dersleri'],
                datasets: [{
                    data: [lastMonth.individual_lessons, lastMonth.group_lessons],
                    backgroundColor: [
                        '#198754', // success green
                        '#0d6efd'  // primary blue
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // 2. ÇUBUK GRAFİĞİ - Aylık Trend
        const barCtx = document.getElementById('trendChart').getContext('2d');
        
        const months = chartData.map(item => item.month);
        const totalHours = chartData.map(item => item.total_hours);
        
        new Chart(barCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Toplam Ders Saati',
                    data: totalHours,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    } else {
        // Veri yoksa uyarı mesajı göster
        document.getElementById('lessonChart').style.display = 'none';
        document.getElementById('trendChart').style.display = 'none';
        
        document.querySelector('#lessonChart').parentElement.innerHTML = 
            '<div class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> Henüz grafik verisi yok.</div>';
        document.querySelector('#trendChart').parentElement.innerHTML = 
            '<div class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> Henüz trend verisi yok.</div>';
    }

    // En Aktif Öğrenciler Toggle Fonksiyonu
    const toggleBtn = document.getElementById('toggleAllStudents');
    const hiddenStudents = document.querySelectorAll('.hidden-student');
    const showMoreInfo = document.getElementById('showMoreInfo');
    let isExpanded = false;

    if (toggleBtn && hiddenStudents.length > 0) {
        toggleBtn.addEventListener('click', function() {
            isExpanded = !isExpanded;
            
            hiddenStudents.forEach(student => {
                if (isExpanded) {
                    student.style.display = 'block';
                    student.classList.remove('hidden-student');
                } else {
                    student.style.display = 'none';
                    student.classList.add('hidden-student');
                }
            });
            
            // Buton metnini değiştir
            if (isExpanded) {
                toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i> Daha Az Göster';
                if (showMoreInfo) showMoreInfo.style.display = 'none';
            } else {
                toggleBtn.innerHTML = '<i class="bi bi-eye"></i> Tümünü Göster';
                if (showMoreInfo) showMoreInfo.style.display = 'block';
            }
        });
    }
});
</script>

<style>
.hidden-student {
    display: none;
}

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