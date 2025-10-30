<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>

<style>
    .card-stats .card-body {
        min-height: 130px;
    }
</style>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800"><i class="bi bi-window-dock"></i> Servis Raporları ve Ayarlar</h1

    <?php if (session()->has('message')): ?>
        <div class="alert alert-success"> <?= session('message') ?></div>
        
    <?php endif; ?>
    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (session('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Raporları Filtrele</h6>
        </div>
        <div class="card-body">
            <form action="<?= route_to('admin.service.reports') ?>" method="get">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 col-6 mb-3">
                        <label for="month" class="form-label">Ay:</label>
                        <select name="month" id="month" class="form-control">
                            <?php 
                                $turkish_months = [
                                    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
                                    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                                ];
                                foreach ($turkish_months as $num => $name):
                            ?>
                                <option value="<?= $num ?>" <?= (int)$selectedMonth === $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6 mb-3">
                        <label for="year" class="form-label">Yıl:</label>
                        <select name="year" id="year" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= (int)$selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6 mb-3">
                        <label for="driver_id" class="form-label">Sürücü:</label>
                        <select name="driver_id" id="driver_id" class="form-control">
                            <option value="">Tüm Sürücüler</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= $driver['id'] ?>" <?= (int)$selectedDriver === (int)$driver['id'] ? 'selected' : '' ?>><?= esc($driver['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6 mb-3">
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-filter"></i> Filtrele</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Toplam Mesafe</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_distance, 2) ?> km</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-road fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Toplam Yakıt Maliyeti</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_fuel_cost, 2) ?> TL</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-gas-pump fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 card-stats">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Toplam Bekleme Süresi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                    $hours = floor($total_idle_time / 3600);
                                    $minutes = floor(($total_idle_time % 3600) / 60);
                                    echo sprintf('%02d saat %02d dakika', $hours, $minutes);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pie Chart -->
        <div class="col-xl-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Sürücüye Göre Mesafe Dağılımı (KM)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="driverPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Card -->
        <div class="col-xl-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Rapor Ayarları</h6>
                </div>
                <div class="card-body">
                    <form action="<?= route_to('admin.service.reports') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tracking_start_time">Takip Başlangıç Saati</label>
                                    <input type="time" class="form-control" id="tracking_start_time" name="tracking_start_time" value="<?= esc($settings['tracking_start_time'] ?? '08:00') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tracking_end_time">Takip Bitiş Saati</label>
                                    <input type="time" class="form-control" id="tracking_end_time" name="tracking_end_time" value="<?= esc($settings['tracking_end_time'] ?? '19:00') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="fuel_price">Yakıt Fiyatı (TL/km)</label>
                                    <input type="text" class="form-control" id="fuel_price" name="fuel_price" value="<?= esc(str_replace('.', ',', $settings['fuel_price'] ?? '0.0')) ?>" placeholder="Örn: 52,89">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Haftanın Aktif Günleri</label>
                            <div class="d-flex flex-wrap">
                                <?php
                                $days = ['1' => 'Pazartesi', '2' => 'Salı', '3' => 'Çarşamba', '4' => 'Perşembe', '5' => 'Cuma', '6' => 'Cumartesi', '0' => 'Pazar'];
                                foreach ($days as $key => $day): ?>
                                    <div class="form-check form-check-inline mr-3">
                                        <input class="form-check-input" type="checkbox" name="tracking_active_days[]" id="day_<?= $key ?>" value="<?= $key ?>" <?= in_array($key, $active_days) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="day_<?= $key ?>"><?= $day ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Ayarları Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Aylık Rapor Detayları</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Tarih</th>
                            <th>Sürücü</th>
                            <th>Toplam Mesafe (km)</th>
                            <th>Bekleme Süresi</th>
                            <th>Yakıt Maliyeti (TL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?= esc(date('d.m.Y', strtotime($report['date']))) ?></td>
                                    <td><?= esc($report['username'] ?? 'Bilinmiyor') ?></td>
                                    <td><?= esc(number_format($report['total_km'], 2)) ?> km</td>
                                    <td>
                                        <?php 
                                            $hours = floor($report['total_idle_time_seconds'] / 3600);
                                            $minutes = floor(($report['total_idle_time_seconds'] % 3600) / 60);
                                            echo sprintf('%02d saat %02d dakika', $hours, $minutes);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $fuelCost = (float)$report['total_km'] * (float)($settings['fuel_price'] ?? 0);
                                            echo esc(number_format($fuelCost, 2));
                                        ?> TL
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Filtrelenen kriterlere uygun rapor bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">
                <?= $pager->links() ?>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Pie Chart
    const pieChartData = {
        labels: <?= json_encode($pie_chart_data['labels']) ?>,
        datasets: [{
            data: <?= json_encode($pie_chart_data['data']) ?>,
            backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'],
            hoverBackgroundColor: ['#17a673', '#2c9faf', '#dda20a', '#be2617', '#60616f', '#37383e'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    };

    const pieChartOptions = {
        maintainAspectRatio: false,
        tooltips: {
            backgroundColor: "rgb(255,255,255)",
            bodyFontColor: "#858796",
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            caretPadding: 10,
            callbacks: {
                label: function(tooltipItem, data) {
                    let label = data.labels[tooltipItem.index] || '';
                    if (label) {
                        label += ': ';
                    }
                    label += parseFloat(data.datasets[0].data[tooltipItem.index]).toFixed(2) + ' km';
                    return label;
                }
            }
        },
        legend: {
            display: true,
            position: 'bottom'
        },
        cutoutPercentage: 80,
    };

    const ctx = document.getElementById("driverPieChart").getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: pieChartData,
        options: pieChartOptions,
    });
</script>

<?= $this->endSection() ?>
