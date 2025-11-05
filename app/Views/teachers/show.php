<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-person-badge"></i> Öğretmen Profili</h1>
        
        <a href="<?= site_url('teachers') ?>" class="btn btn-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Listeye Dön
        </a>
    </div>

    <div class="row">
        <!-- Sol Taraf - Profil Kartı -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <img class="img-fluid mb-3" src="<?= base_url($teacher->profile_photo ?? 'uploads/profile_photos/default.png') ?>" alt="Profil Fotoğrafı" style="width: 250px; height: 250px; object-fit: cover;">
                    <h4 class="card-title"><?= esc($teacher->first_name . ' ' . $teacher->last_name) ?></h4>
                    <p class="text-muted"><?= esc($teacher->branch) ?></p>
                    <hr>
                    <p class="text-muted mb-0">TCKN: <?= esc($teacher->tc_kimlik_no) ?></p>
                    <div class="mt-3">
                        <a href="#" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#leaveModal">
                            <i class="bi bi-calendar-check"></i> İzin İşlemleri
                        </a>
                    </div>
                </div>
            </div>

            <!-- İletişim ve Adres Bilgileri Kartı -->
            <div class="card shadow mb-4">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs nav-fill" id="teacherTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-success active" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">İletişim Bilgileri</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#address" type="button" role="tab">Adres Bilgileri</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="teacherTabContent">
                        <div class="tab-pane fade show active" id="contact" role="tabpanel">
                            <h5 class="card-title text-success">İletişim Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>E-posta Adresi:</strong> <?= esc($teacher->email ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Telefon Numarası:</strong> <?= esc($teacher->phone_number ?? 'Belirtilmemiş') ?></li>
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="address" role="tabpanel">
                            <h5 class="card-title text-success">Adres Bilgileri</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>İl / İlçe:</strong> <?= esc($teacher->city_name ?? 'Belirtilmemiş') ?> / <?= esc($teacher->district_name ?? 'Belirtilmemiş') ?></li>
                                <li class="list-group-item"><strong>Adres Detayı:</strong> <?= esc($teacher->address ?? 'Belirtilmemiş') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf - İstatistikler ve Diğer Kartlar -->
        <div class="col-lg-8">

            <!-- Tarih Filtre Butonları -->
            <div class="d-flex justify-content-center flex-wrap mb-3">
                <?php
                $months = [];
                for ($i = 0; $i < 6; $i++) {
                    $time = new \CodeIgniter\I18n\Time("-$i months");
                    $months[$time->format('Y-m')] = ['year' => $time->getYear(), 'month' => $time->getMonth(), 'name' => $time->toLocalizedString('MMMM yyyy')];
                }
                $months = array_reverse($months);

                foreach ($months as $key => $value):
                    $isActive = ($selected_year == $value['year'] && $selected_month == $value['month']);
                ?>
                    <a href="<?= site_url('teachers/show/' . $teacher->id . '?year=' . $value['year'] . '&month=' . $value['month']) ?>" 
                       class="btn <?= $isActive ? 'btn-success' : 'btn-outline-success' ?> btn-sm m-1">
                        <?= esc($value['name']) ?>
                    </a>
                <?php endforeach; ?>

                <a href="<?= site_url('teachers/show/' . $teacher->id) ?>" 
                   class="btn <?= (empty($selected_year) && empty($selected_month)) ? 'btn-success' : 'btn-outline-success' ?> btn-sm m-1">
                    Tüm Zamanlar
                </a>
            </div>

            <!-- Grafikler -->
            <div class="row">
                <!-- Aylık Öğrenci Grafiği -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Aylara Göre Ders Sayısı</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyStudentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Aylık Boş Ders Grafiği -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Aylara Göre Boş Ders Sayısı</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyEmptyLessonsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İstatistik Kartları -->
            <div class="row">
                <!-- Toplam İzin Saati Kartı -->
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Toplam İzin (Saat)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($stats['totalLeaveHours']) ?> Saat</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-clock-history fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toplam Değerlendirme Kartı -->
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Toplam Değerlendirme</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($stats['totalEvaluations']) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-check2-square fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tablolar -->
            <div class="row">
                <!-- En Çok Ders Verilen Öğrenciler -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">En Çok Ders Verilen Öğrenciler</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="topStudentsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Öğrenci</th>
                                            <th>Ders Sayısı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($stats['topStudents'])): ?>
                                            <?php foreach($stats['topStudents'] as $student): ?>
                                                <?php if (isset($student['student_id'], $student['student_name'], $student['lesson_count'])): ?>
                                                    <tr>
                                                        <td><a href="<?= site_url('students/' . $student['student_id']) ?>"><?= esc($student['student_name']) ?></a></td>
                                                        <td><?= esc($student['lesson_count']) ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center">Veri bulunamadı.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Gelişim Değerlendirmeleri -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Son Gelişim Değerlendirmeleri</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="latestEvaluationsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Öğrenci</th>
                                            <th>Değerlendirme Tarihi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($stats['latestEvaluations'])): ?>
                                            <?php foreach($stats['latestEvaluations'] as $eval): ?>
                                                <?php if (isset($eval['student_id'], $eval['student_name'], $eval['evaluation_date'])): ?>
                                                    <tr>
                                                        <td><a href="<?= site_url('students/' . $eval['student_id']) ?>"><?= esc($eval['student_name']) ?></a></td>
                                                        <td><?= esc(date('d.m.Y', strtotime($eval['evaluation_date']))) ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center">Veri bulunamadı.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İzin Geçmişi -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-success">İzin Geçmişi</h6>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-bordered" id="leaves-table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>İzin Başlangıç</th>
                                    <th>İzin Bitiş</th>
                                    <th>Açıklama</th>
                                    <th>Türü</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $leaveTypeTranslations = [
                                    'hourly' => 'Saatlik İzin',
                                    'unpaid_daily' => 'Ücretsiz Günlük İzin',
                                    'paid_daily' => 'Ücretli Günlük İzin / Raporlu',
                                ];
                                ?>
                                <?php if (!empty($filtered_leaves)): ?>
                                    <?php foreach ($filtered_leaves as $leave): ?>
                                        <tr>
                                            <td><?= esc(CodeIgniter\I18n\Time::parse($leave->start_date)->toLocalizedString('dd MMMM yyyy, HH:mm')) ?></td>
                                            <td><?= esc(CodeIgniter\I18n\Time::parse($leave->end_date)->toLocalizedString('dd MMMM yyyy, HH:mm')) ?></td>
                                            <td><?= esc($leave->reason) ?></td>
                                            <td><?= esc($leaveTypeTranslations[$leave->leave_type] ?? $leave->leave_type) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Veri bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leave Modal -->
<div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">İzin İşlemleri: <?= esc($teacher->first_name . ' ' . $teacher->last_name) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (session()->has('error')): ?>
                    <div class="alert alert-danger">
                        <?= session('error') ?>
                    </div>
                <?php endif; ?>
                <?php if (session()->has('message')): ?>
                    <div class="alert alert-success">
                        <?= session('message') ?>
                    </div>
                <?php endif; ?>

                <h6>Yeni İzin Ekle</h6>
                <form action="<?= site_url('teachers/' . $teacher->id . '/leaves') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="leave_type" class="form-label">İzin Türü</label>
                                <select class="form-select" name="leave_type" id="leave_type" required>
                                    <option value="hourly">Saatlik İzin</option>
                                    <option value="unpaid_daily">Ücretsiz Günlük İzin</option>
                                    <option value="paid_daily">Ücretli Günlük İzin / Raporlu</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Başlangıç</label>
                                <input type="datetime-local" class="form-control" name="start_date" id="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Bitiş</label>
                                <input type="datetime-local" class="form-control" name="end_date" id="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Açıklama (İsteğe Bağlı)</label>
                        <textarea class="form-control" name="reason" id="reason" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">İzin Ekle</button>
                </form>

                <hr class="my-4">

                <h6>Mevcut İzinler</h6>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tür</th>
                            <th>Başlangıç</th>
                            <th>Bitiş</th>
                            <th>Açıklama</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaves)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Kayıtlı izin bulunmamaktadır.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leaves as $leave): ?>
                                <tr>
                                    <td><?= esc($leave->leave_type) ?></td>
                                    <td><?= esc($leave->start_date) ?></td>
                                    <td><?= esc($leave->end_date) ?></td>
                                    <td><?= esc($leave->reason) ?></td>
                                    <td>
                                        <a href="<?= site_url('teachers/' . $teacher->id . '/leaves/' . $leave->id . '/delete') ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu izni silmek istediğinizden emin misiniz?')">Sil</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- DataTables CSS ve JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // İzin Modal scripti
    const leaveTypeSelect = document.getElementById('leave_type');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    function toggleTimePicker() {
        if (leaveTypeSelect.value === 'unpaid_daily' || leaveTypeSelect.value === 'paid_daily') {
            startDateInput.type = 'date';
            endDateInput.type = 'date';
        } else {
            startDateInput.type = 'datetime-local';
            endDateInput.type = 'datetime-local';
        }
    }
    
    if (leaveTypeSelect) {
        toggleTimePicker();
        leaveTypeSelect.addEventListener('change', toggleTimePicker);
    }

    // Aylık Öğrenci Grafiği
    var ctx1 = document.getElementById('monthlyStudentChart');
    if (ctx1) {
        ctx1 = ctx1.getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?= json_encode($stats['monthlyStudentChart']['labels'] ?? []) ?>,
                datasets: [{
                    label: 'Ders Sayısı',
                    data: <?= json_encode($stats['monthlyStudentChart']['data'] ?? []) ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Aylık Boş Ders Grafiği
    var ctx2 = document.getElementById('monthlyEmptyLessonsChart');
    if (ctx2) {
        ctx2 = ctx2.getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: <?= json_encode($stats['monthlyEmptyLessonsChart']['labels'] ?? []) ?>,
                datasets: [{
                    label: 'Boş Ders Sayısı',
                    data: <?= json_encode($stats['monthlyEmptyLessonsChart']['data'] ?? []) ?>,
                    borderColor: '#e74a3b',
                    backgroundColor: 'rgba(231, 74, 59, 0.05)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});

// DataTables için jQuery kullanarak
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';

    // DataTable tanımlama
    $('#leaves-table').DataTable({
        "language": {
            "sEmptyTable": "Tabloda herhangi bir veri mevcut değil",
            "sInfo": "_TOTAL_ kayıttan _START_ - _END_ arasındakiler gösteriliyor",
            "sInfoEmpty": "Kayıt yok",
            "sInfoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
            "sInfoPostFix": "",
            "sInfoThousands": ".",
            "sLengthMenu": "Sayfada _MENU_ kayıt göster",
            "sLoadingRecords": "Yükleniyor...",
            "sProcessing": "İşleniyor...",
            "sSearch": "Ara:",
            "sZeroRecords": "Eşleşen kayıt bulunamadı",
            "oPaginate": {
                "sFirst": "İlk",
                "sLast": "Son",
                "sNext": "Sonraki",
                "sPrevious": "Önceki"
            },
            "oAria": {
                "sSortAscending": ": artan sütun sıralamasını aktifleştir",
                "sSortDescending": ": azalan sütun sıralamasını aktifleştir"
            }
        },
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tümü"]],
        "order": [[ 0, "desc" ]]
    });

    $('#topStudentsTable').DataTable({
        "language": {
            "sEmptyTable": "Tabloda herhangi bir veri mevcut değil",
            "sInfo": "_TOTAL_ kayıttan _START_ - _END_ arasındakiler gösteriliyor",
            "sInfoEmpty": "Kayıt yok",
            "sInfoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
            "sInfoPostFix": "",
            "sInfoThousands": ".",
            "sLengthMenu": "Sayfada _MENU_ kayıt göster",
            "sLoadingRecords": "Yükleniyor...",
            "sProcessing": "İşleniyor...",
            "sSearch": "Ara:",
            "sZeroRecords": "Eşleşen kayıt bulunamadı",
            "oPaginate": {
                "sFirst": "İlk",
                "sLast": "Son",
                "sNext": "Sonraki",
                "sPrevious": "Önceki"
            },
            "oAria": {
                "sSortAscending": ": artan sütun sıralamasını aktifleştir",
                "sSortDescending": ": azalan sütun sıralamasını aktifleştir"
            }
        },
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tümü"]],
        "searching": false,
        "order": [[ 1, "desc" ]],
        "dom": 'lrtip'
    });

    $('#latestEvaluationsTable').DataTable({
        "language": {
            "sEmptyTable": "Tabloda herhangi bir veri mevcut değil",
            "sInfo": "_TOTAL_ kayıttan _START_ - _END_ arasındakiler gösteriliyor",
            "sInfoEmpty": "Kayıt yok",
            "sInfoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
            "sInfoPostFix": "",
            "sInfoThousands": ".",
            "sLengthMenu": "Sayfada _MENU_ kayıt göster",
            "sLoadingRecords": "Yükleniyor...",
            "sProcessing": "İşleniyor...",
            "sSearch": "Ara:",
            "sZeroRecords": "Eşleşen kayıt bulunamadı",
            "oPaginate": {
                "sFirst": "İlk",
                "sLast": "Son",
                "sNext": "Sonraki",
                "sPrevious": "Önceki"
            },
            "oAria": {
                "sSortAscending": ": artan sütun sıralamasını aktifleştir",
                "sSortDescending": ": azalan sütun sıralamasını aktifleştir"
            }
        },
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tümü"]],
        "searching": false,
        "dom": 'lrtip'
    });
});
</script>
<?= $this->endSection() ?>