<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4" id="daily-grid-page">
    <div class="d-sm-flex align-items-center justify-content-between mb-3 d-print-none">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-table"></i> <?= esc($title) ?></h1>
        <div class="btn-group">
            <a href="<?= route_to('schedule.index') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Ana Takvime Dön</a>
            <button id="printScheduleBtn" class="btn btn-info btn-sm"><i class="bi bi-printer"></i> Yazdır</button>
        </div>
    </div>
    
    <div class="card shadow-sm mb-3 d-print-none">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-2 fw-bold"><i class="bi bi-funnel"></i> Öğretmen Filtrele:</div>
                <div class="col-md-10 ">
                    <select id="teacher-filter" multiple placeholder="Tüm öğretmenler gösteriliyor..."></select>
                </div>
            </div>
        </div>
    </div>

    <div id="printableArea">
        <h3 class="text-center d-none d-print-block mb-3"><?= esc($title) ?></h3>
        <div id="colorCodesExplanation" class="alert alert-light border d-print-none small mb-3 alert-dismissible fade show" role="alert">
            <strong><i class="bi bi-palette-fill"></i> Renk Kodları:</strong>
            <span class="badge text-bg-success mx-1">Yeşil</span>: Öğrencinin bu saatte sabit dersi var.
            <span class="badge text-bg-warning mx-1">Sarı</span>: Öğrencinin sabit dersi var, ancak farklı bir gün/saatte.
            <span class="badge text-bg-secondary mx-1">Gri</span>: Öğrencinin tanımlı bir sabit dersi yok.
            <span class="badge text-bg-danger mx-1">Kırmızı</span>: Öğrenci aynı anda başka bir öğretmende de ders alıyor (ÇAKIŞMA).
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>

        <div id="schedule-content-wrapper">
            <?php if (empty($teachers)): ?>
                <div class="alert alert-warning text-center">Bu programı görüntülemek için yetkiniz olan bir öğretmen bulunmamaktadır.</div>
            <?php else: ?>
                <div class="card shadow" id="scheduleCard">
                    <div class="card-header d-flex justify-content-between align-items-center py-2 d-print-none">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-primary me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#studentOffcanvas" aria-controls="studentOffcanvas"><i class="bi bi-people-fill"></i> Öğrenciler Listesi</button>
                            <h6 class="m-0 font-weight-bold text-success"><i class="bi bi-calendar-week"></i> Günlük Program</h6>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleFullScreen('scheduleCard')" title="Tam Ekran"><i class="bi bi-fullscreen"></i></button>
                    </div>
                    <div class="card-body">
                        <div class="" style="overflow-x: auto; overflow-y: visible;"> 
                            <table class="table table-bordered schedule-grid text-center" style="min-width: 900px;" id="schedule-table">
                                <thead class="sticky-top bg-body z-1">
                                    <tr>
                                        <?php if (auth()->user()->inGroup('admin', 'mudur', 'sekreter')): ?>
                                            <th class="d-print-none" style="width: 40px;"></th>
                                        <?php endif; ?>
                                        <th style="width: 250px;">Öğretmen</th>
                                        <?php for ($hour = config('Ots')->scheduleStartHour; $hour < config('Ots')->scheduleEndHour; $hour++): ?>
                                            <th><?= str_pad($hour, 2, '0', STR_PAD_LEFT) ?>:00</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr data-teacher-id="<?= $teacher->id ?>">
                                            <?php if (auth()->user()->inGroup('admin', 'mudur', 'sekreter')): ?>
                                                <td class="align-middle d-print-none text-muted sortable-handle" style="cursor: grab; width: 40px;">
                                                    <i class="bi bi-grip-vertical fs-5"></i>
                                                </td>
                                            <?php endif; ?>
                                            <td class="align-middle fw-bold">
                                                <div class="d-flex align-items-center">
                                                    <div class="dropdown d-print-none me-3">
                                                        <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" class="d-block position-relative">
                                                            <img src="<?= base_url(ltrim($teacher->profile_photo ?? '/assets/images/user.jpg', '/')) ?>" class="rounded-circle shadow-sm" width="40" height="40" style="object-fit: cover; cursor: pointer;">
                                                            <!-- Bildirim Durum Noktası -->
                                                            <span class="position-absolute bottom-0 end-0 border border-2 border-white rounded-circle" 
                                                                  style="width: 12px; height: 12px; background-color: <?= ($teacher->push_count > 0) ? '#198754' : '#dc3545' ?>;"
                                                                  title="<?= ($teacher->push_count > 0) ? 'Bildirimler Aktif' : 'Bildirimler Kapalı' ?>"></span>
                                                        </a>
                                                        <ul class="dropdown-menu">
                                                            <li><button class="dropdown-item bildirim-gonder-tek" data-teacher-id="<?= esc($teacher->id) ?>"><i class="bi bi-bell me-2 text-success"></i> Bildirim Gönder</button></li>
                                                            <li><button class="dropdown-item add-fixed-lessons" data-teacher-id="<?= esc($teacher->id) ?>" data-date="<?= esc($displayDate) ?>"><i class="bi bi-calendar-check me-2 text-primary"></i> Sabit Dersleri Ekle</button></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><button class="dropdown-item delete-day-lessons text-danger" data-teacher-id="<?= esc($teacher->id) ?>" data-date="<?= esc($displayDate) ?>" data-teacher-name="<?= esc($teacher->first_name . ' ' . $teacher->last_name) ?>"><i class="bi bi-calendar-x me-2"></i> Günün Derslerini Sil</button></li>
                                                        </ul>
                                                    </div>
                                                    
                                                    <!-- Print Mode Image (Non-clickable) -->
                                                    <img src="<?= base_url(ltrim($teacher->profile_photo ?? '/assets/images/user.jpg', '/')) ?>" class="rounded-circle me-3 d-none d-print-none" width="40" height="40" style="object-fit: cover;">

                                                    <div>
                                                        <div class="fw-bold text-nowrap text-start">
                                                            <a href="<?= site_url('teachers/show/' . $teacher->id) ?>" class="text-decoration-none text-body-emphasis">
                                                                <?= esc($teacher->first_name . ' ' . $teacher->last_name) ?>
                                                            </a>
                                                        </div>
                                                        <small class="text-muted d-block text-truncate fw-lighter text-start" style="max-width: 150px;"><?= esc($teacher->branch) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php for ($hour = config('Ots')->scheduleStartHour; $hour < config('Ots')->scheduleEndHour; $hour++): ?>
                                                <?php
                                                    $hourKey = str_pad((string)$hour, 2, '0', STR_PAD_LEFT);
                                                    $slotContent = $lessonMap[$teacher->id][$hourKey] ?? null;
                                                ?>
                                                <?php if ($slotContent): ?>
                                                    <?php if ($slotContent['type'] === 'evaluation'): ?>
                                                        <td class="align-middle bg-info-subtle has-evaluation" data-evaluation-id="<?= $slotContent['id'] ?>">
                                                            <span class="badge text-bg-info">DEĞERLENDİRME</span>
                                                            <i class="bi bi-pencil-square d-print-none"></i>
                                                        </td>
                                                    <?php elseif ($slotContent['type'] === 'leave'): ?>
                                                        <td class="align-middle on-leave">
                                                            <span class="badge text-bg-secondary"><i class="bi bi-person-walking"></i> İzinli</span>
                                                        </td>
                                                    <?php else: // type is 'lesson' or default ?>
                                                        <td class="align-middle bg-success-subtle has-lesson" data-lesson-id="<?= $slotContent['id'] ?>" data-date="<?= $displayDate ?>" data-time="<?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00' ?>" data-teacher-id="<?= $teacher->id ?>">
                                                            <?php
                                                            $studentNames = explode('||', $slotContent['student_names']);
                                                            $studentIds = explode(',', $slotContent['student_ids']);
                                                            foreach (array_filter($studentIds) as $index => $studentId):
                                                                $name = $studentNames[$index] ?? 'Bilinmeyen';
                                                                $info = $studentInfoMap[$studentId] ?? null;

                                                                $isMatch = false;
                                                                if ($info && !empty($info['fixed_lessons'])) {
                                                                    foreach ($info['fixed_lessons'] as $fixed) {
                                                                        if ($fixed['day_of_week'] == $dayOfWeekForGrid && $fixed['start_time'] == $slotContent['start_time']) {
                                                                            $isMatch = true; break;
                                                                        }
                                                                    }
                                                                }

                                                                if (!empty($conflictMap[$studentId][$slotContent['start_time']])) {
                                                                    $badgeClass = 'text-bg-danger';
                                                                } else {
                                                                    $badgeClass = 'text-bg-secondary';
                                                                    if ($info && !empty($info['fixed_lessons'])) {
                                                                        $badgeClass = $isMatch ? 'text-bg-success' : 'text-bg-warning';
                                                                    }
                                                                }

                                                                $popoverContent = '';
                                                                if ($info) {
                                                                    $programsHtml = '';
                                                                    if (!empty($info['programs'])) {
                                                                        foreach($info['programs'] as $p) {
                                                                            $textColor = str_replace(['bg-', ' text-dark'], ['text-', ''], $p['class']);
                                                                            $programsHtml .= '<span class="' . $textColor . ' fw-bolder fs-6" title="' . esc($p['name']) . '">' . $p['letter'] . '</span>';
                                                                        }
                                                                    }

                                                                    $privilegesHtml = '<span class="badge border text-secondary fw-medium px-2 py-1 ms-1 bg-light d-inline-block" style="font-size: 11px; letter-spacing: 0.5px;">B:' . $info['bireysel'] . ' G:' . $info['grup'] . '</span>';
                                                                    $servisHtml = $info['servis'] ? '<span class="text-success ms-1 d-inline-block" style="font-size: 14px;" title="Servis Kullanıyor: ' . esc($info['mesafe']) . '"><i class="bi bi-bus-front-fill"></i></span>' : '';
                                                                    
                                                                    $ramHtml = '';
                                                                    if ($info['ram_status'] === 'none') {
                                                                        $ramHtml = '<span class="badge border text-secondary fw-bold px-2 py-1 ms-1 bg-light d-inline-block" style="font-size: 11px;" title="RAM raporu yok">R <i class="bi bi-x text-danger" style="font-size: 12px; margin-left:-3px;"></i></span>';
                                                                    } else if ($info['ram_status'] === 'active') {
                                                                        $ramHtml = '<span class="badge border text-secondary fw-bold px-2 py-1 ms-1 bg-light d-inline-block" style="font-size: 11px;" title="RAM Bitiş: ' . $info['ram_date'] . '">R <i class="bi bi-check-circle-fill text-success" style="font-size: 10px;"></i></span>';
                                                                    } else if ($info['ram_status'] === 'expired') {
                                                                        $ramHtml = '<span class="badge border text-secondary fw-bold px-2 py-1 ms-1 bg-light d-inline-block" style="font-size: 11px;" title="' . $info['ram_date'] . ' tarihinde raporun süresi bitmiştir">R <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 10px;"></i></span>';
                                                                    }

                                                                    $hasHtml = '';
                                                                    if ($info['has_status'] === 'none') {
                                                                        $hasHtml = '<span class="badge border text-secondary fw-bold px-2 py-1 ms-1 bg-light d-inline-block" style="font-size: 11px;" title="Hastane raporu yok">H <i class="bi bi-x text-danger" style="font-size: 12px; margin-left:-3px;"></i></span>';
                                                                    } else if ($info['has_status'] === 'active') {
                                                                        $hasHtml = '<span class="badge border text-secondary fw-bold px-2 py-1 ms-1 bg-light d-inline-block" style="font-size: 11px;" title="Hastane Bitiş: ' . $info['has_date'] . '">H <i class="bi bi-check-circle-fill text-success" style="font-size: 10px;"></i></span>';
                                                                    } else if ($info['has_status'] === 'expired') {
                                                                        $hasHtml = '<span class="badge border text-secondary fw-bold px-2 py-1 ms-1 bg-light d-inline-block" style="font-size: 11px;" title="' . $info['has_date'] . ' tarihinde raporun süresi bitmiştir">H <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 10px;"></i></span>';
                                                                    }

                                                                    $badgesContainer = '<div class="d-flex align-items-center flex-wrap mt-2"><div class="d-flex gap-1 me-1">' . $programsHtml . '</div>' . $servisHtml . $privilegesHtml . $ramHtml . $hasHtml . '</div>';

                                                                    // Form popover HTML securely
                                                                    $popoverHtml = '
                                                                        <div class="d-flex align-items-center w-100 mb-2">
                                                                            <img src="' . base_url($info['photo']) . '" class="rounded-circle me-3" width="42" height="42" style="object-fit:cover;">
                                                                            <div class="flex-grow-1 min-w-0">
                                                                                <div class="fw-bold text-dark text-truncate me-1" style="font-size: 0.95rem; max-width: 180px;">' . esc($name) . '</div>
                                                                            </div>
                                                                        </div>
                                                                        ' . $badgesContainer . '
                                                                        <hr class="my-2 border-secondary opacity-25">
                                                                        <div class="small mb-1">' . ($info['message'] ?? '') . '</div>
                                                                        <div class="text-muted small mt-2" style="font-size: 0.8rem;"><i class="bi bi-geo-alt-fill"></i> ' . esc(($info['city'] ?? '') . ' / ' . ($info['district'] ?? '')) . '</div>
                                                                    ';

                                                                    $popoverContent = htmlspecialchars(trim(preg_replace('/\s+/', ' ', $popoverHtml)));
                                                                }
                                                            ?>
                                                                <span class="badge <?= $badgeClass ?> student-badge draggable-student" draggable="true" data-student-id="<?= esc($studentId) ?>" data-lesson-id="<?= $slotContent['id'] ?>" data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="hover" title="Öğrenci Bilgisi" data-bs-content="<?= $popoverContent ?>"><?= esc(trim($name)) ?> <i class="bi bi-grip-vertical text-dark opacity-50 pe-none d-print-none"></i></span>
                                                            <?php endforeach; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <td class="align-middle available-slot" data-date="<?= $displayDate ?>" data-time="<?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00' ?>" data-teacher-id="<?= $teacher->id ?>">
                                                        <i class="bi bi-person-fill-add d-print-none pe-none"></i>
                                                    </td>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="text-center mt-4 d-print-none">
        <div class="btn-group" role="group">
            <button class="btn btn-success" id="bildirim-gonder-hepsi"><i class="bi bi-broadcast-pin"></i> Hepsine Bildir</button>
            <button class="btn btn-primary" id="addAllFixedLessonsBtn" data-date="<?= esc($displayDate) ?>"><i class="bi bi-calendar-check-fill"></i> Tüm Sabitleri Ekle</button>
            <button class="btn btn-danger" id="deleteAllLessonsBtn" data-date="<?= esc($displayDate) ?>"><i class="bi bi-trash3-fill"></i> Tüm Dersleri Sil</button>
        </div>
    </div>
</div>

<div class="modal fade" id="lessonFormModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lessonFormModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="lessonFormModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-warning" id="reportAbsenceTriggerBtn" style="display: none;">Devamsızlığı Bildir ve Sil</button>
                <button type="button" class="btn btn-danger" id="deleteLessonBtn" style="display: none;">Dersi Sil</button>
                <button type="button" class="btn btn-primary" id="updateLessonBtn" style="display: none;">Güncelle</button>
                <button type="button" class="btn btn-success" id="saveLessonBtn" style="display: none;">Dersi Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="evaluationDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evaluationDetailModalLabel">Değerlendirme Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="evaluation-form">
                    <input type="hidden" name="evaluation_id" id="evaluationId">
                    <div class="mb-3">
                        <label for="evaluationNotes" class="form-label">Notlar</label>
                        <textarea class="form-control" id="evaluationNotes" name="notes" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-danger" id="deleteEvaluationBtn">Değerlendirmeyi Sil</button>
                <button type="button" class="btn btn-primary" id="updateEvaluationNotesBtn">Notları Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Absence Modal -->
<div class="modal fade" id="absenceModal" tabindex="-1" aria-labelledby="absenceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="absenceModalLabel">Devamsızlık Bildir ve Dersi Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="absence-form">
                <div class="modal-body" id="absenceModalBody">
                    <!-- JS will load content here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary" id="saveAbsenceBtn">Kaydet ve Dersi Sil</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Student Offcanvas -->
<div class="offcanvas offcanvas-start shadow" tabindex="-1" id="studentOffcanvas" aria-labelledby="studentOffcanvasLabel" data-bs-backdrop="false" data-bs-scroll="true" style="width: 350px;">
  <div class="offcanvas-header border-bottom bg-body-tertiary">
    <h5 class="offcanvas-title fw-bold" id="studentOffcanvasLabel"><i class="bi bi-people-fill text-primary me-2"></i>Öğrenciler Listesi</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0 d-flex flex-column">
    <div class="p-3 border-bottom shadow-sm z-1 bg-body">
        <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Listeden öğrenci ismini tutarak tabloda istediğiniz <strong class="text-body-emphasis">"+"</strong> alanına sürükleyip bırakabilirsiniz.</p>
        
        <div class="row g-2 mb-2">
            <div class="col-6">
                <select id="offcanvas-sort" class="form-select form-select-sm" style="box-shadow: none;">
                    <option value="freq">Günün Sık Gelenleri</option>
                    <option value="telafi">Telafi Sırası (En Çok)</option>
                    <option value="az">Alfabetik (A-Z)</option>
                </select>
            </div>
            <div class="col-6">
                <select id="offcanvas-teacher-filter" class="form-select form-select-sm" style="box-shadow: none;">
                    <option value="">Tüm Öğretmenler</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= esc($t->first_name . ' ' . $t->last_name) ?>"><?= esc($t->first_name . ' ' . $t->last_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" id="offcanvas-search" placeholder="Öğrenci ara..." style="box-shadow: none;">
        </div>
    </div>
    
    <div id="offcanvas-student-loading" class="text-center py-5 d-none flex-grow-1">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Yükleniyor...</span>
        </div>
    </div>
    
    <div id="offcanvas-student-list" class="list-group list-group-flush flex-grow-1" style="overflow-y: auto;">
        <!-- Students will be loaded here via AJAX -->
    </div>
  </div>
</div>

<!-- Student Context Menu -->
<div id="student-context-menu" class="dropdown-menu shadow border-0" style="display:none; position: absolute; z-index: 9999; min-width: 220px;">
    <button class="dropdown-item py-2 text-warning-emphasis fw-medium" id="cmReportAbsence">
        <i class="bi bi-person-x-fill me-2 text-warning"></i> Devamsızlığı Bildir ve Sil
    </button>
    <div class="dropdown-divider my-1"></div>
    <button class="dropdown-item py-2 text-danger fw-medium" id="cmDeleteLesson">
        <i class="bi bi-trash3-fill me-2"></i> Dersi Sil
    </button>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<style>
@media print {
    @page {
        size: landscape; /* Sayfayı yatay yap */
        margin: 1cm;     /* Kenar boşlukları */
    }

    body {
        font-size: 14pt !important;
    }

    /* Sayfadaki ana layout elemanlarını ve yazdırılmayacakları gizle */
    body > .navbar,
    body > footer,
    #daily-grid-page > .d-print-none,
    .modal {
        display: none !important;
    }

    /* Yazdırma alanını ve içindekileri görünür yap ve tam sayfa yap */
    #printableArea {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        padding: 1cm; /* @page margin ile aynı */
        box-sizing: border-box;
    }

    .card, .card-body, .table-responsive, .table-sticky-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .sticky-top { position: static !important; }
    
    .table-bordered th, .table-bordered td { border: 1px solid #6c757d !important; }

    .has-lesson {
        background-color: #f0f0f0 !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .badge {
        border: 1px solid #333 !important;
        background-color: transparent !important;
        color: #000 !important;
        font-weight: normal !important;
    }
    .on-leave {
        background-color: #e9ecef !important;
        color: #6c757d !important;
        font-style: italic;
        cursor: not-allowed;
    }
    
    a[href]:after { content: none !important; }
}

</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    // Renk kodları bilgilendirmesi için oturum kontrolü
    if (sessionStorage.getItem('colorCodesDismissed') === 'true') {
        $('#colorCodesExplanation').remove();
    }
    $('#colorCodesExplanation').on('close.bs.alert', function () {
        sessionStorage.setItem('colorCodesDismissed', 'true');
    });

    // MODAL DEFINITIONS
    const lessonModal = new bootstrap.Modal(document.getElementById('lessonFormModal'));
    const evaluationDetailModal = new bootstrap.Modal(document.getElementById('evaluationDetailModal'));
    const absenceModal = new bootstrap.Modal(document.getElementById('absenceModal')); // New

    // MODAL BODY AND LABEL DEFINITIONS
    const modalBody = $('#lessonFormModalBody');
    const modalLabel = $('#lessonFormModalLabel');
    const absenceModalBody = $('#absenceModalBody'); // New

    // BUTTON DEFINITIONS
    const saveBtn = $('#saveLessonBtn');
    const updateBtn = $('#updateLessonBtn');
    const deleteBtn = $('#deleteLessonBtn');
    const reportAbsenceTriggerBtn = $('#reportAbsenceTriggerBtn'); // New

    const teachersForSelect = <?= json_encode(array_map(fn($t) => ['value' => $t->id, 'text' => $t->first_name . ' ' . $t->last_name], $teachers)) ?>;
    let tomSelect;

    // --- INITIALIZATIONS ---
    const teacherFilter = new TomSelect('#teacher-filter', {
        options: teachersForSelect,
        plugins: ['remove_button'],
        onChange: function(value) {
            const selectedIds = value;
            const allRows = $('#schedule-table tbody tr');
            if (selectedIds.length === 0) { allRows.show(); }
            else {
                allRows.hide();
                selectedIds.forEach(id => allRows.filter(`[data-teacher-id="${id}"]`).show());
            }
        }
    });

    $('body').popover({ selector: '[data-bs-toggle="popover"]', html: true, trigger: 'hover', placement: 'top', container: 'body' });
    $('#printScheduleBtn').on('click', () => window.print());

    // --- CORE FUNCTIONS ---
    async function refreshSchedule(preserveScroll = false) {
        let scrollPosition = 0;
        if (preserveScroll) scrollPosition = window.scrollY;
        
        $('.popover').remove();
        
        $('#schedule-content-wrapper').html('<div class="text-center p-5"><div class="spinner-border text-success" style="width: 3rem; height: 3rem;"></div><p class="mt-3">Program güncelleniyor...</p></div>');
        try {
            const response = await $.get(window.location.href);
            const newContent = $(response).find('#schedule-content-wrapper').html(); 
            $('#schedule-content-wrapper').html(newContent);
            
            const currentFilterValues = teacherFilter.getValue();
            teacherFilter.trigger('change', currentFilterValues);
        } catch (error) {
            console.error("Yenileme hatası:", error);
            $('#schedule-content-wrapper').html('<div class="alert alert-danger">Sayfa yenilenirken bir hata oluştu.</div>');
        } finally {
            if (preserveScroll) {
                requestAnimationFrame(() => window.scrollTo({ top: scrollPosition, behavior: 'auto' }));
            }
        }
    }

    function showLoadingInModal(bodyElement = modalBody) { // Modified to accept different modal bodies
        bodyElement.html('<div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2">Yükleniyor...</p></div>');
    }

    function calculateEndTime(startTime) {
        const [hours, minutes] = startTime.split(':').map(Number);
        const date = new Date();
        date.setHours(hours, minutes, 0);
        date.setMinutes(date.getMinutes() + <?= config('Ots')->lessonDurationMinutes ?? 50 ?>);
        return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    }

    function createTomSelect(options = [], items = []) {
        if (tomSelect) tomSelect.destroy();
        tomSelect = new TomSelect('#student-select-modal', {
            plugins: ['remove_button'], options: options, items: items,
            placeholder: 'Öğrenci arayın veya seçin...', valueField: 'value',
            labelField: 'text', searchField: 'text',
            render: {
                option: function(data, escape) {
                    let classes = 'd-flex align-items-center p-2'; let typeLabel = '';
                    let warningIcon = data.warning ? `<i class="bi bi-exclamation-triangle-fill text-warning me-2" title="${escape(data.warning)}"></i>` : '';
                    if (data.type === 'fixed') { classes += ' text-success fw-bold'; typeLabel = '<span class="badge bg-success-subtle text-success-emphasis rounded-pill ms-auto">Sabit Program</span>'; } 
                    else if (data.type === 'history') { classes += ' text-primary'; typeLabel = '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill ms-auto">Sık Ders</span>'; }
                    let lessonCounts = `<span class="ms-2"><span class="badge bg-info-subtle text-info-emphasis" title="Bireysel Telafi Hakkı">B: ${escape(data.bireysel ?? 0)}</span><span class="badge bg-warning-subtle text-warning-emphasis" title="Grup Telafi Hakkı">G: ${escape(data.grup ?? 0)}</span></span>`;
                    return `<div class="${classes}"><div>${warningIcon}${escape(data.text)}${lessonCounts}</div>${typeLabel}</div>`;
                },
                item: function(data, escape) {
                    let warningIcon = data.warning ? `<i class="bi bi-exclamation-triangle-fill text-warning me-2" title="${escape(data.warning)}"></i>` : '';
                    let lessonCounts = `<span class="ms-2"><span class="badge bg-info-subtle text-info-emphasis" title="Bireysel Telafi Hakkı">B: ${escape(data.bireysel ?? 0)}</span><span class="badge bg-warning-subtle text-warning-emphasis" title="Grup Telafi Hakkı">G: ${escape(data.grup ?? 0)}</span></span>`;
                    return `<div>${warningIcon}${escape(data.text)}${lessonCounts}</div>`;
                }
            }
        });
    }

    // --- EVENT LISTENERS (DELEGATED) ---
    $(document).on('click', '.available-slot', function() {
        const slot = $(this); const teacherId = slot.data('teacher-id'); const date = slot.data('date'); const time = slot.data('time');
        modalLabel.text('Yeni Ders / Değerlendirme Ekle');
        showLoadingInModal();
        saveBtn.show();
        updateBtn.hide();
        deleteBtn.hide();
        reportAbsenceTriggerBtn.hide(); // New
        $('#createEvaluationBtn').remove();
        $('#lessonFormModal .modal-footer').prepend(`<button type="button" class="btn btn-info" id="createEvaluationBtn" data-teacher-id="${teacherId}" data-date="${date}" data-time="${time}">Değerlendirme Ekle</button>`);
        lessonModal.show();
        $.get('<?= route_to("schedule.suggestions") ?>', { teacher_id: teacherId, date: date, start_time: time })
            .done(function(students) {
                const tomSelectOptions = students.map(s => ({ value: s.id, text: s.name, type: s.type, bireysel: s.bireysel, grup: s.grup, warning: s.warning }));
                const form = $(`<form id="lesson-form"></form>`);
                form.append(`<input type="hidden" name="teacher_id" value="${teacherId}">`);
                form.append(`<input type="hidden" name="lesson_date" value="${date}">`);
                form.append(`<input type="hidden" name="start_time" value="${time}">`);
                form.append(`<input type="hidden" name="end_time" value="${calculateEndTime(time.substring(0, 5))}:00">`);
                form.append('<div class="mb-3"><label class="form-label">Öğrenci(ler)</label><select id="student-select-modal" name="students[]" multiple></select></div>');
                modalBody.html(form);
                createTomSelect(tomSelectOptions);
            }).fail(() => modalBody.html('<div class="alert alert-danger">Öğrenci önerileri yüklenemedi.</div>'));
    });
    
    $(document).on('click', '#createEvaluationBtn', function() {
        const button = $(this); const teacherId = button.data('teacher-id'); const date = button.data('date'); const time = button.data('time');
        const endTime = calculateEndTime(time.substring(0, 5)) + ':00';
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Ekleniyor...');
        $.post('<?= route_to("degerlendirme.create") ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>', teacher_id: teacherId, evaluation_date: date, start_time: time, end_time: endTime })
        .done(res => { lessonModal.hide(); if (res.success) { refreshSchedule(true); } else { alert(res.message); } })
        .fail(() => alert('Değerlendirme eklenirken bir hata oluştu.'))
        .always(() => button.prop('disabled', false).html('Değerlendirme Ekle'));
    });
    
    $(document).on('click', '.has-lesson', function() {
        const lessonId = $(this).data('lesson-id');
        modalLabel.text('Dersi Düzenle');
        showLoadingInModal();
        saveBtn.hide();
        updateBtn.data('lesson-id', lessonId).show();
        deleteBtn.data('lesson-id', lessonId).show();
        reportAbsenceTriggerBtn.data('lesson-id', lessonId).show(); // New
        $('#createEvaluationBtn').remove();
        lessonModal.show();
        $.get(`<?= site_url('schedule/get-lesson-details/') ?>${lessonId}`)
            .done(function(response) {
                if (!response.success) { modalBody.html(`<div class="alert alert-danger">${response.message}</div>`); return; }
                const lesson = response.lesson; const existingStudents = lesson.students.map(s => s.id);
                $.get('<?= route_to("schedule.suggestions") ?>', { teacher_id: lesson.teacher_id, date: lesson.lesson_date, start_time: lesson.start_time })
                    .done(function(students) {
                        const tomSelectOptions = students.map(s => ({ value: s.id, text: s.name, type: s.type, bireysel: s.bireysel, grup: s.grup, warning: s.warning }));
                        const form = $(`<form id="lesson-form"></form>`);
                        form.append('<div class="mb-3"><label class="form-label">Öğrenci(ler)</label><select id="student-select-modal" name="students[]" multiple></select></div>');
                        modalBody.html(form);
                        createTomSelect(tomSelectOptions, existingStudents);
                    }).fail(() => modalBody.html('<div class="alert alert-danger">Öğrenci listesi yüklenemedi.</div>'));
            }).fail(() => modalBody.html('<div class="alert alert-danger">Ders detayları alınamadı.</div>'));
    });

    $(document).on('click', '.has-evaluation', function() {
        const evaluationId = $(this).data('evaluation-id');
        $('#evaluationId').val(evaluationId);
        $('#evaluationNotes').val('Yükleniyor...').prop('disabled', true);
        evaluationDetailModal.show();
        $.get(`<?= site_url("degerlendirme/get/") ?>${evaluationId}`)
            .done(function(response) {
                if (response.success && response.data) {
                    $('#evaluationNotes').val(response.data.notes || '').prop('disabled', false);
                } else {
                    $('#evaluationNotes').val('Notlar yüklenemedi.').prop('disabled', true);
                    alert(response.message || 'Değerlendirme detayları alınamadı.');
                }
            })
            .fail(function() {
                $('#evaluationNotes').val('Sunucu hatası.').prop('disabled', true);
                alert('Değerlendirme detayları alınırken sunucu hatası oluştu.');
            });
    });

    // --- BUTTON ACTIONS ---
    $('#updateEvaluationNotesBtn').on('click', function() {
        const evaluationId = $('#evaluationId').val();
        const notes = $('#evaluationNotes').val();
        const button = $(this);
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...');
        $.post(`<?= site_url("degerlendirme/update/") ?>${evaluationId}`, {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            notes: notes
        })
        .done(function(response) {
            if (response.success) {
                alert(response.message);
                evaluationDetailModal.hide();
                refreshSchedule(true);
            } else {
                alert(response.message || 'Notlar güncellenirken bir hata oluştu.');
            }
        })
        .fail(function() { alert('Sunucu hatası: Notlar güncellenemedi.'); })
        .always(function() { button.prop('disabled', false).html('Notları Kaydet'); });
    });

    $('#deleteEvaluationBtn').on('click', function() {
        if (!confirm('Bu değerlendirmeyi kalıcı olarak silmek istediğinizden emin misiniz?')) return;
        const evaluationId = $('#evaluationId').val();
        const button = $(this);
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Siliniyor...');
        $.post(`<?= site_url("degerlendirme/delete/") ?>${evaluationId}`, { '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
        .done(function(response) {
            if (response.success) {
                alert(response.message);
                evaluationDetailModal.hide();
                refreshSchedule(true);
            } else {
                alert(response.message || 'Değerlendirme silinirken bir hata oluştu.');
            }
        })
        .fail(function() { alert('Sunucu hatası: Değerlendirme silinemedi.'); })
        .always(function() { button.prop('disabled', false).html('Değerlendirmeyi Sil'); });
    });

    $(document).on('click', '.add-fixed-lessons', function() {
        var button = $(this); var teacherId = button.data('teacher-id'); var date = button.data('date');
        if (!confirm('Bu öğretmenin bu güne ait tüm sabit derslerini programa eklemek istediğinizden emin misiniz? Mevcut dersler korunacaktır.')) return;
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.post('<?= route_to("schedule.addFixed") ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>', teacher_id: teacherId, date: date })
            .done(function(response) { alert(response.message); if(response.success) refreshSchedule(true); })
            .fail(() => alert('Sunucu hatası.'))
            .always(() => button.prop('disabled', false).html('<i class="bi bi-calendar-check"></i>'));
    });

    $(document).on('click', '.delete-day-lessons', function() {
        var button = $(this); var teacherId = button.data('teacher-id'); var teacherName = button.data('teacher-name'); var date = button.data('date');
        if (!confirm(teacherName + ' adlı öğretmenin ' + date + ' tarihindeki TÜM derslerini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) return;
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.post('<?= route_to("schedule.deleteForDay") ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>', teacher_id: teacherId, date: date })
            .done(function(response) { alert(response.message); if(response.success) refreshSchedule(true); })
            .fail(() => alert('Sunucu hatası.'))
            .always(() => button.prop('disabled', false).html('<i class="bi bi-calendar-x"></i>'));
    });

    saveBtn.on('click', function() {
        $.post('<?= route_to("schedule.create") ?>', $('#lesson-form').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(res => { lessonModal.hide(); if (res.success) refreshSchedule(true); else alert(res.message); })
            .fail(() => alert('Hata oluştu.'));
    });

    updateBtn.on('click', function() {
        const lessonId = $(this).data('lesson-id');
        $.post(`<?= site_url('schedule/update-lesson/') ?>${lessonId}`, $('#lesson-form').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(res => { lessonModal.hide(); if (res.success) refreshSchedule(true); else alert(res.message); })
            .fail(() => alert('Hata oluştu.'));
    });

    deleteBtn.on('click', function() {
        if (!confirm('Bu dersi kalıcı olarak silmek istediğinizden emin misiniz?')) return;
        const lessonId = $(this).data('lesson-id');
        $.post(`<?= site_url('schedule/delete-lesson/') ?>${lessonId}`, {'<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
            .done(res => { lessonModal.hide(); if (res.success) refreshSchedule(true); else alert(res.message); })
            .fail(() => alert('Hata oluştu.'));
    });

    $('#addAllFixedLessonsBtn').on('click', function() {
        var button = $(this); var date = button.data('date');
        var teacherIds = $('#schedule-table tbody tr').map(function() { return $(this).data('teacher-id'); }).get();
        if (teacherIds.length === 0) { alert('Listede öğretmen bulunmuyor.'); return; }
        if (!confirm('Listelenen ' + teacherIds.length + ' öğretmenin tüm sabit derslerini eklemek istediğinizden emin misiniz?')) return;
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Yükleniyor...');
        $.post('<?= route_to("schedule.addAllFixed") ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>', teacher_ids: teacherIds, date: date })
            .done(function(response) { alert(response.message); if(response.success) refreshSchedule(true); })
            .fail(() => alert('Sunucu hatası.'))
            .always(() => button.prop('disabled', false).html('<i class="bi bi-calendar-check-fill"></i> Tüm Sabitleri Ekle'));
    });

    $('#deleteAllLessonsBtn').on('click', function() {
        var button = $(this); var date = button.data('date');
        var teacherIds = $('#schedule-table tbody tr').map(function() { return $(this).data('teacher-id'); }).get();
        if (teacherIds.length === 0) { alert('Listede öğretmen bulunmuyor.'); return; }
        if (!confirm('DİKKAT! ' + teacherIds.length + ' öğretmenin bu tarihteki TÜM derslerini kalıcı olarak sileceksiniz. Emin misiniz?')) return;
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Siliniyor...');
        $.post('<?= route_to("schedule.deleteAllForDay") ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>', teacher_ids: teacherIds, date: date })
            .done(function(response) { alert(response.message); if(response.success) refreshSchedule(true); })
            .fail(() => alert('Sunucu hatası.'))
            .always(() => button.prop('disabled', false).html('<i class="bi bi-trash3-fill"></i> Tüm Dersleri Sil'));
    });
    
    function sendNotification(teacherIds, buttonElement) {
        if (!teacherIds || teacherIds.length === 0) { alert('Bildirim gönderilecek öğretmen seçilmedi.'); return; }
        const originalHtml = buttonElement.html();
        buttonElement.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '<?= route_to("notifications.sendManual") ?>', type: 'POST',
            data: { '<?= csrf_token() ?>': '<?= csrf_hash() ?>', 'teacher_ids': teacherIds },
            dataType: 'json',
            success: function(response) { alert(response.message || 'İşlem tamamlandı.'); },
            error: function() { alert('Bildirim gönderilirken bir sunucu hatası oluştu.'); },
            complete: function() { buttonElement.prop('disabled', false).html(originalHtml); }
        });
    }
    
    $(document).on('click', '.bildirim-gonder-tek', function(e) { e.preventDefault(); const teacherId = $(this).data('teacher-id'); sendNotification([teacherId], $(this)); });
    
    $('#bildirim-gonder-hepsi').on('click', function(e) {
        e.preventDefault();
        let teacherIds = [];
        $('#schedule-table tbody tr').each(function() { teacherIds.push($(this).data('teacher-id')); });
        if (teacherIds.length > 0 && confirm(teacherIds.length + ' öğretmene program güncelleme bildirimi göndermek istediğinizden emin misiniz?')) {
            sendNotification(teacherIds, $(this));
        }
    });

    // --- NEW ABSENCE REPORTING LOGIC ---
    reportAbsenceTriggerBtn.on('click', function() {
        const lessonId = $(this).data('lesson-id');
        if (!lessonId) {
            alert('Hata: Ders ID bulunamadı.');
            return;
        }

        lessonModal.hide();
        showLoadingInModal(absenceModalBody);
        absenceModal.show();

        $.get(`<?= site_url('schedule/get-lesson-details/') ?>${lessonId}`)
            .done(function(response) {
                if (!response.success || !response.lesson.students || response.lesson.students.length === 0) {
                    absenceModalBody.html('<div class="alert alert-warning">Bu derse kayıtlı öğrenci bulunamadı.</div>');
                    return;
                }

                let studentCheckboxes = '<p>Devamsızlık kaydı yapılacak öğrenci(ler)i seçin:</p>';
                response.lesson.students.forEach(student => {
                    studentCheckboxes += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="student_ids[]" value="${student.id}" id="student_${student.id}">
                            <label class="form-check-label" for="student_${student.id}">
                                ${student.name}
                            </label>
                        </div>`;
                });

                const formContent = `
                    <input type="hidden" name="lesson_id" value="${lessonId}">
                    <div class="mb-3">${studentCheckboxes}</div>
                    <div class="mb-3">
                        <label for="absenceReason" class="form-label">Devamsızlık Nedeni (İsteğe Bağlı)</label>
                        <textarea class="form-control" id="absenceReason" name="reason" rows="3"></textarea>
                    </div>
                `;
                absenceModalBody.html(formContent);
            })
            .fail(() => {
                absenceModalBody.html('<div class="alert alert-danger">Dersteki öğrenciler yüklenirken bir hata oluştu.</div>');
            });
    });

    $('#absence-form').on('submit', function(e) {
        e.preventDefault();
        const saveButton = $('#saveAbsenceBtn');
        const studentIds = $(this).find('input[name="student_ids[]"]:checked').map(function() { return $(this).val(); }).get();

        if (studentIds.length === 0) {
            alert('Lütfen en az bir öğrenci seçin.');
            return;
        }

        saveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...');

        $.post('<?= route_to("schedule.reportAbsence") ?>', $(this).serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(function(response) {
                if (response.success) {
                    absenceModal.hide();
                    lessonModal.hide(); // Make sure the other modal is also hidden
                    alert(response.message);
                    refreshSchedule(true);
                } else {
                    alert(response.message || 'Bir hata oluştu.');
                }
            })
            .fail(() => {
                alert('Sunucu hatası: Devamsızlık kaydedilemedi.');
            })
            .always(() => {
                saveButton.prop('disabled', false).html('Kaydet ve Dersi Sil');
            });
    });

    // --- CONTEXT MENU LOGIC ---
    let currentCmLessonId = null;

    $(document).on('contextmenu', '.student-badge', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        currentCmLessonId = $(this).data('lesson-id');
        
        const menu = $('#student-context-menu');
        // Ensure menu stays within viewport
        let posX = e.pageX;
        let posY = e.pageY;
        
        menu.css({ display: 'block', left: posX + 'px', top: posY + 'px' });
        
        const menuWidth = menu.outerWidth();
        const menuHeight = menu.outerHeight();
        if (posX + menuWidth > $(window).width()) { posX -= menuWidth; }
        if (posY + menuHeight > $(window).height() + $(window).scrollTop()) { posY -= menuHeight; }
        
        menu.css({ left: posX + 'px', top: posY + 'px' });
        
        $('[data-bs-toggle="popover"]').popover('hide');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#student-context-menu').length) {
            $('#student-context-menu').hide();
        }
    });

    $('#cmReportAbsence').on('click', function(e) {
        e.preventDefault();
        $('#student-context-menu').hide();
        if (currentCmLessonId) {
            reportAbsenceTriggerBtn.data('lesson-id', currentCmLessonId).trigger('click');
        }
    });

    $('#cmDeleteLesson').on('click', function(e) {
        e.preventDefault();
        $('#student-context-menu').hide();
        if (currentCmLessonId) {
            if (confirm('Bu dersi programdan silmek istediğinize emin misiniz?')) {
                $.post(`<?= site_url('schedule/delete-lesson/') ?>${currentCmLessonId}`, { '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
                    .done(function(response) {
                        alert(response.message);
                        if (response.success) refreshSchedule(true);
                    })
                    .fail(function() { alert('Ders silinirken sunucu hatası oluştu.'); });
            }
        }
    });

    // --- SORTABLE LOGIC ---
    <?php if (auth()->user()->inGroup('admin', 'mudur', 'sekreter')): ?>
    const el = document.querySelector('#schedule-table tbody');
    const sortable = Sortable.create(el, {
        handle: '.sortable-handle',
        animation: 150,
        ghostClass: 'bg-light',
        onEnd: function (evt) {
            const order = [];
            $('#schedule-table tbody tr').each(function() {
                order.push($(this).data('teacher-id'));
            });

            // AJAX call to save the order
            $.post('<?= route_to("schedule.update_teacher_order") ?>', {
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                'order': order
            }).done(function(response) {
                if (response.success) {
                    // Success silently or show partial toast
                    console.log('Sıralama güncellendi');
                } else {
                    alert('Sıralama kaydedilemedi: ' + response.message);
                }
            }).fail(function() {
                alert('Sıralama kaydedilirken sunucu hatası oluştu.');
            });
        }
    });
    <?php endif; ?>

    // --- OFFCANVAS & DRAG/DROP LOGIC ---
    let offcanvasLoaded = false;
    let allOffcanvasStudents = [];

    const studentOffcanvas = document.getElementById('studentOffcanvas');
    if (studentOffcanvas) {
        studentOffcanvas.addEventListener('show.bs.offcanvas', function () {
            if (!offcanvasLoaded) {
                $('#offcanvas-student-loading').removeClass('d-none');
                
                // Get the current date from the URL or any available element (for instance, the "addAllFixedLessonsBtn")
                const currentDate = $('#addAllFixedLessonsBtn').data('date') || '<?= esc($displayDate) ?>';
                
                $.get('<?= route_to("schedule.offcanvas_students") ?>', { date: currentDate }, function(data) {
                    allOffcanvasStudents = data;
                    renderOffcanvasStudents(allOffcanvasStudents);
                    offcanvasLoaded = true;
                    $('#offcanvas-student-loading').addClass('d-none');
                }).fail(function() {
                    $('#offcanvas-student-loading').addClass('d-none');
                    $('#offcanvas-student-list').html('<div class="alert alert-danger mx-3 mt-3">Öğrenciler yüklenirken bir hata oluştu.</div>');
                });
            }
        });
    }

    function renderOffcanvasStudents(students) {
        let html = '';
        if (students.length === 0) {
            html = '<div class="text-center text-muted p-4">Öğrenci bulunamadı.</div>';
        } else {
            students.forEach(student => {
                let badgeHtml = '';
                if (student.is_fixed) {
                    badgeHtml += `<span class="badge ms-1 rounded-pill shadow-sm" style="font-size: 0.65rem; background-color: #e6f4ea; color: #137333; font-weight: 600;">Sabit</span>`;
                }
                if (student.freq > 0) {
                    badgeHtml += `<span class="badge ms-1 rounded-pill shadow-sm" style="font-size: 0.65rem; background-color: #e8f0fe; color: #1967d2; font-weight: 600;">Sık</span>`;
                }
                
                let programsHtml = student.programs.map(p => `<span class="badge ${p.class} rounded-circle d-inline-flex align-items-center justify-content-center p-0" title="${p.name}" style="width: 20px; height: 20px; font-size: 11px;">${p.letter}</span>`).join('');
                let privilegesHtml = `<span class="badge border text-secondary fw-medium px-2 py-1 ms-1 bg-light" style="font-size: 11px; letter-spacing: 0.5px;">B:${student.bireysel} G:${student.grup}</span>`;
                let servisHtml = student.servis ? `<span class="text-success ms-1" style="font-size: 14px;" title="Servis Kullanıyor: ${student.mesafe}"><i class="bi bi-bus-front-fill"></i></span>` : '';
                let ramHtml = '';
                if (student.ram_status === 'none') {
                    ramHtml = `<span class="badge border text-secondary fw-bold px-1 py-1 ms-1 bg-light d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="RAM raporu yok">R <i class="bi bi-x text-danger" style="font-size: 12px; margin-left:-3px;"></i></span>`;
                } else if (student.ram_status === 'active') {
                    ramHtml = `<span class="badge border text-secondary fw-bold px-1 py-1 ms-1 bg-light d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="RAM Bitiş: ${student.ram_date}">R <i class="bi bi-check-circle-fill text-success" style="font-size: 10px;"></i></span>`;
                } else if (student.ram_status === 'expired') {
                    ramHtml = `<span class="badge border text-secondary fw-bold px-1 py-1 ms-1 bg-light d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="${student.ram_date} tarihinde raporun süresi bitmiştir">R <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 10px;"></i></span>`;
                }

                let hasHtml = '';
                if (student.has_status === 'none') {
                    hasHtml = `<span class="badge border text-secondary fw-bold px-1 py-1 ms-1 bg-light d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="Hastane raporu yok">H <i class="bi bi-x text-danger" style="font-size: 12px; margin-left:-3px;"></i></span>`;
                } else if (student.has_status === 'active') {
                    hasHtml = `<span class="badge border text-secondary fw-bold px-1 py-1 ms-1 bg-light d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="Hastane Bitiş: ${student.has_date}">H <i class="bi bi-check-circle-fill text-success" style="font-size: 10px;"></i></span>`;
                } else if (student.has_status === 'expired') {
                    hasHtml = `<span class="badge border text-secondary fw-bold px-1 py-1 ms-1 bg-light d-inline-flex align-items-center gap-1" style="font-size: 11px;" title="${student.has_date} tarihinde raporun süresi bitmiştir">H <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 10px;"></i></span>`;
                }
                
                let bgColorClass = student.scheduled_color_class ? student.scheduled_color_class : 'bg-body';
                
                html += `
                    <a href="#" class="list-group-item list-group-item-action py-2 border-start-0 border-end-0 offcanvas-student-item ${bgColorClass}" draggable="true" data-student-id="${student.id}" title="Öğrenciyi sürükleyin">
                        <div class="d-flex align-items-center w-100">
                            <!-- Profil Fotoğrafı -->
                            <img src="${student.photo}" class="rounded-circle me-3" width="42" height="42" style="object-fit:cover;">
                            
                            <!-- Bilgiler -->
                            <div class="flex-grow-1 min-w-0">
                                <!-- İsim (Satır 1) -->
                                <div class="d-flex align-items-center mb-1 flex-wrap">
                                    <span class="fw-semibold text-body-emphasis text-truncate me-1" style="font-size: 0.95rem; max-width: 140px;" title="${student.name}">${student.name}</span>
                                    ${badgeHtml}
                                </div>
                                
                                <!-- Rozetler (Satır 2) -->
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="d-flex gap-1 me-1">${programsHtml}</div>
                                    ${servisHtml}
                                    ${privilegesHtml}
                                    ${ramHtml}
                                    ${hasHtml}
                                </div>
                            </div>
                            
                            <!-- Sürükleme Tutamacı -->
                            <i class="bi bi-grip-vertical ms-1 text-muted opacity-25 pe-none"></i>
                        </div>
                    </a>
                `;
            });
        }
        $('#offcanvas-student-list').html(html);
    }

    function applyOffcanvasFilters() {
        if (!allOffcanvasStudents || allOffcanvasStudents.length === 0) return;
        
        const term = $('#offcanvas-search').val().toLowerCase();
        const sortType = $('#offcanvas-sort').val();
        const teacherFilter = $('#offcanvas-teacher-filter').val();
        
        // 1. Filter
        let filtered = allOffcanvasStudents.filter(s => {
            let matchSearch = term === '' || s.name.toLowerCase().includes(term);
            let matchTeacher = true;
            if (teacherFilter !== '') {
                // Determine if student has history with this teacher
                if (!s.teachers || !s.teachers[teacherFilter]) {
                    matchTeacher = false;
                }
            }
            return matchSearch && matchTeacher;
        });
        
        // 2. Sort
        filtered.sort((a, b) => {
            if (teacherFilter !== '') {
                // If a teacher is selected, sort primarily by how often they took lessons with THAT teacher
                const aFreq = a.teachers ? (a.teachers[teacherFilter] || 0) : 0;
                const bFreq = b.teachers ? (b.teachers[teacherFilter] || 0) : 0;
                if (aFreq !== bFreq) {
                    return bFreq - aFreq; // descending
                }
            }
            
            if (sortType === 'telafi') {
                if (a.total_telafi !== b.total_telafi) return b.total_telafi - a.total_telafi;
                return a.name.localeCompare(b.name);
            } else if (sortType === 'az') {
                return a.name.localeCompare(b.name);
            } else { // 'freq'
                if (a.freq !== b.freq) return b.freq - a.freq;
                return a.name.localeCompare(b.name);
            }
        });
        
        renderOffcanvasStudents(filtered);
    }

    $('#offcanvas-search').on('input', applyOffcanvasFilters);
    $('#offcanvas-sort, #offcanvas-teacher-filter').on('change', applyOffcanvasFilters);

    let draggedStudentId = null;
    let draggedLessonId = null; // To handle moving from a cell

    $(document).on('dragstart', '.offcanvas-student-item, .draggable-student', function(e) {
        draggedStudentId = $(this).data('student-id');
        draggedLessonId = $(this).data('lesson-id') || null; // null if from offcanvas
        
        // Hide popovers if dragging from table
        if(draggedLessonId) {
            $(this).popover('hide');
        }
        
        // Use JSON structure within text/plain format for maximum compatibility
        const transferData = {
            studentId: draggedStudentId,
            lessonId: draggedLessonId,
            source: draggedLessonId ? 'table' : 'offcanvas'
        };
        e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(transferData));
        e.originalEvent.dataTransfer.effectAllowed = 'copyMove';
        $(this).css('opacity', '0.5');
        
        // Prevent click event (editing lesson) right after dropping
        e.stopPropagation();
    });

    $(document).on('dragend', '.offcanvas-student-item, .draggable-student', function(e) {
        $(this).css('opacity', '1');
        $('.available-slot, .has-lesson').removeClass('bg-warning-subtle');
        draggedStudentId = null;
        draggedLessonId = null;
    });

    $(document).on('dragover', '.available-slot, .has-lesson', function(e) {
        e.preventDefault(); 
        $(this).addClass('bg-warning-subtle shadow-inner'); 
        e.originalEvent.dataTransfer.dropEffect = 'copy';
    });

    $(document).on('dragleave', '.available-slot, .has-lesson', function(e) {
        e.preventDefault();
        $(this).removeClass('bg-warning-subtle shadow-inner');
    });

    $(document).on('drop', '.available-slot, .has-lesson', function(e) {
        e.preventDefault();
        $(this).removeClass('bg-warning-subtle shadow-inner');
        
        let transferData;
        try {
            transferData = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
        } catch(err) {
            transferData = { studentId: draggedStudentId, lessonId: draggedLessonId };
        }
        
        const studentId = transferData.studentId;
        const sourceLessonId = transferData.lessonId;
        
        if (!studentId) return;

        const slot = $(this);
        const targetLessonId = slot.data('lesson-id') || null;
        
        // Prevent dropping the student into the exact same lesson visually
        if (sourceLessonId && targetLessonId === sourceLessonId) return;

        const targetTeacherId = slot.data('teacher-id');
        const lessonDate = slot.data('date');
        const startTime = slot.data('time');
        const endTime = calculateEndTime(startTime.substring(0, 5)) + ':00';

        const originalHtml = slot.html();
        slot.html('<div class="spinner-border spinner-border-sm text-primary"></div>');

        $.post('<?= route_to("schedule.move_student") ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            student_id: studentId,
            source_lesson_id: sourceLessonId,
            target_lesson_id: targetLessonId,
            target_teacher_id: targetTeacherId,
            lesson_date: lessonDate,
            start_time: startTime,
            end_time: endTime
        }).done(res => {
            if (res.success) {
                refreshSchedule(true);
            } else {
                alert(res.message || 'İşlem sırasında bir hata oluştu.');
                slot.html(originalHtml);
            }
        }).fail(() => {
            alert('Sunucu ile iletişim kurulamadı. İşlem başarısız.');
            slot.html(originalHtml);
        });
    });

});
</script>
<script>
    function toggleFullScreen(elementId) {
        var element = document.getElementById(elementId);
        if (!document.fullscreenElement) {
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.mozRequestFullScreen) { /* Firefox */
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) { /* IE/Edge */
                element.msRequestFullscreen();
            }
            // Tam ekrana geçince arkaplanı beyaz yap, yoksa siyah olabilir
            element.classList.add('bg-white');
            element.classList.add('p-3'); 
            element.style.overflowY = 'auto'; // Kaydırma çubuklarını aktif et
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            element.classList.remove('bg-white');
            element.classList.remove('p-3');
            element.style.overflowY = '';
        }
    }
</script>
<?= $this->endSection() ?>