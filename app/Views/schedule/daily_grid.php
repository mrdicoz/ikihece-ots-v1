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
        <div class="alert alert-light border d-print-none small mb-3">
            <strong><i class="bi bi-palette-fill"></i> Renk Kodları:</strong>
            <span class="badge text-bg-success mx-1">Yeşil</span>: Öğrencinin bu saatte sabit dersi var.
            <span class="badge text-bg-warning mx-1">Sarı</span>: Öğrencinin sabit dersi var, ancak farklı bir gün/saatte.
            <span class="badge text-bg-secondary mx-1">Gri</span>: Öğrencinin tanımlı bir sabit dersi yok.
            <span class="badge text-bg-danger mx-1">Kırmızı</span>: Öğrenci aynı anda başka bir öğretmende de ders alıyor (ÇAKIŞMA).
        </div>

        <div id="schedule-content-wrapper">
            <?php if (empty($teachers)): ?>
                <div class="alert alert-warning text-center">Bu programı görüntülemek için yetkiniz olan bir öğretmen bulunmamaktadır.</div>
            <?php else: ?>
                <div class="card shadow">
                    <div class="card-body">
                        <div class="" style="overflow-x: auto; overflow-y: visible;"> 
                            <table class="table table-bordered schedule-grid text-center" style="min-width: 900px;" id="schedule-table">
                                <thead class="sticky-top bg-white z-1">
                                    <tr>
                                        <th style="width: 250px;">Öğretmen</th>
                                        <?php for ($hour = config('Ots')->scheduleStartHour; $hour < config('Ots')->scheduleEndHour; $hour++): ?>
                                            <th><?= str_pad($hour, 2, '0', STR_PAD_LEFT) ?>:00</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr data-teacher-id="<?= $teacher->id ?>">
                                            <td class="align-middle fw-bold">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= base_url(ltrim($teacher->profile_photo ?? '/assets/images/user.jpg', '/')) ?>" class="rounded-circle me-3 d-print-none" width="40" height="40" style="object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold text-nowrap text-start"><?= esc($teacher->first_name . ' ' . $teacher->last_name) ?></div>
                                                        <small class="text-muted d-block text-truncate fw-lighter text-start" style="max-width: 150px;"><?= esc($teacher->branch) ?></small>
                                                    </div>
                                                </div>
                                                <div class="mt-2 btn-group w-100 action-buttons d-print-none">
                                                     <button class="btn btn-sm btn-success bildirim-gonder-tek" data-teacher-id="<?= esc($teacher->id) ?>" title="Bildirim Gönder"><i class="bi bi-bell"></i></button>
                                                     <button class="btn btn-sm btn-primary add-fixed-lessons" data-teacher-id="<?= esc($teacher->id) ?>" data-date="<?= esc($displayDate) ?>" title="Sabit Dersleri Ekle"><i class="bi bi-calendar-check"></i></button>
                                                     <button class="btn btn-sm btn-danger delete-day-lessons" data-teacher-id="<?= esc($teacher->id) ?>" data-date="<?= esc($displayDate) ?>" data-teacher-name="<?= esc($teacher->first_name . ' ' . $teacher->last_name) ?>" title="Günün Derslerini Sil"><i class="bi bi-calendar-x"></i></button>
                                                </div>
                                            </td>
                                            <?php for ($hour = config('Ots')->scheduleStartHour; $hour < config('Ots')->scheduleEndHour; $hour++): ?>
                                                <?php
                                                    $hourKey = str_pad((string)$hour, 2, '0', STR_PAD_LEFT);
                                                    $lesson = $lessonMap[$teacher->id][$hourKey] ?? null;
                                                ?>
                                                <?php if ($lesson): ?>
                                                    <td class="align-middle bg-success-subtle has-lesson" data-lesson-id="<?= $lesson['id'] ?>">
                                                        <?php
                                                        $studentNames = explode('||', $lesson['student_names']);
                                                        $studentIds = explode(',', $lesson['student_ids']);
                                                        foreach (array_filter($studentIds) as $index => $studentId):
                                                            $name = $studentNames[$index] ?? 'Bilinmeyen';
                                                            $info = $studentInfoMap[$studentId] ?? null;
                                                            $popoverContent = '';
                                                            if ($info) {
                                                                $popoverContent = htmlspecialchars('<div><img src="' . base_url($info['photo']) . '" class="rounded-circle me-2" width="40" height="40" style="object-fit:cover"><strong>' . esc($name) . '</strong></div><div class="text-muted small mt-1"><i class="bi bi-geo-alt-fill"></i> ' . esc(($info['city'] ?? '') . ' / ' . ($info['district'] ?? '')) . '</div><hr class="my-2"><small>' . ($info['message'] ?? '') . '</small>');
                                                            }
                                                                // ÖNCE ÇAKIŞMA KONTROLÜ (ÖNCELİK 1)
                                                                if (!empty($conflictMap[$studentId][$lesson['start_time']])) {
                                                                    $badgeClass = 'text-bg-danger';
                                                                } else {
                                                                    // ÇAKIŞMA YOKSA DİĞER KURALLARA BAK
                                                                    $badgeClass = 'text-bg-secondary';
                                                                    if ($info && !empty($info['fixed_lessons'])) {
                                                                        $isMatch = false;
                                                                        foreach ($info['fixed_lessons'] as $fixed) {
                                                                            if ($fixed['day_of_week'] == $dayOfWeekForGrid && $fixed['start_time'] == $lesson['start_time']) {
                                                                                $isMatch = true; break;
                                                                            }
                                                                        }
                                                                        $badgeClass = $isMatch ? 'text-bg-success' : 'text-bg-warning';
                                                                    }
                                                                }
                                                        ?>
                                                            <span class="badge <?= $badgeClass ?> student-badge" data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="hover" title="Öğrenci Bilgisi" data-bs-content="<?= $popoverContent ?>"><?= esc(trim($name)) ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                <?php else: ?>
                                                    <td class="align-middle available-slot" data-date="<?= $displayDate ?>" data-time="<?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00' ?>" data-teacher-id="<?= $teacher->id ?>">
                                                        <i class="bi bi-person-fill-add d-print-none"></i>
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
                <button type="button" class="btn btn-danger" id="deleteLessonBtn" style="display: none;">Dersi Sil</button>
                <button type="button" class="btn btn-primary" id="updateLessonBtn" style="display: none;">Güncelle</button>
                <button type="button" class="btn btn-success" id="saveLessonBtn" style="display: none;">Dersi Kaydet</button>
            </div>
        </div>
    </div>
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
    
    a[href]:after { content: none !important; }
}

</style>

<script>
$(document).ready(function() {
    const lessonModal = new bootstrap.Modal(document.getElementById('lessonFormModal'));
    const modalBody = $('#lessonFormModalBody');
    const modalLabel = $('#lessonFormModalLabel');
    const saveBtn = $('#saveLessonBtn'), updateBtn = $('#updateLessonBtn'), deleteBtn = $('#deleteLessonBtn');
    const teachersForSelect = <?= json_encode(array_map(fn($t) => ['value' => $t->id, 'text' => $t->first_name . ' ' . $t->last_name], $teachers)) ?>;
    let tomSelect;

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

    // --- YENİ ANA YENİLEME FONKSİYONU ---
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

    // --- SİZİN YARDIMCI FONKSİYONLARINIZ (EKSİKSİZ) ---
    function showLoadingInModal() {
        modalBody.html('<div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2">Yükleniyor...</p></div>');
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
                    let warningIcon = data.warning ? '<i class="bi bi-exclamation-triangle-fill text-warning me-2" title="' + escape(data.warning) + '"></i>' : '';
                    if (data.type === 'fixed') { classes += ' text-success fw-bold'; typeLabel = '<span class="badge bg-success-subtle text-success-emphasis rounded-pill ms-auto">Sabit Program</span>'; } 
                    else if (data.type === 'history') { classes += ' text-primary'; typeLabel = '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill ms-auto">Sık Ders</span>'; }
                    let lessonCounts = `<span class="ms-2"><span class="badge bg-info-subtle text-info-emphasis" title="Bireysel Telafi Hakkı">B: ${escape(data.bireysel ?? 0)}</span><span class="badge bg-warning-subtle text-warning-emphasis" title="Grup Telafi Hakkı">G: ${escape(data.grup ?? 0)}</span></span>`;
                    return `<div class="${classes}"><div>${warningIcon}${escape(data.text)}${lessonCounts}</div>${typeLabel}</div>`;
                },
                item: function(data, escape) {
                    let warningIcon = data.warning ? '<i class="bi bi-exclamation-triangle-fill text-warning me-2" title="' + escape(data.warning) + '"></i>' : '';
                    let lessonCounts = `<span class="ms-2"><span class="badge bg-info-subtle text-info-emphasis" title="Bireysel Telafi Hakkı">B: ${escape(data.bireysel ?? 0)}</span><span class="badge bg-warning-subtle text-warning-emphasis" title="Grup Telafi Hakkı">G: ${escape(data.grup ?? 0)}</span></span>`;
                    return `<div>${warningIcon}${escape(data.text)}${lessonCounts}</div>`;
                }
            }
        });
    }

    // --- EVENT DELEGATION ILE OLAY DİNLEYİCİLER ---
    $(document).on('click', '.available-slot', function() {
        const slot = $(this); const teacherId = slot.data('teacher-id'); const date = slot.data('date'); const time = slot.data('time');
        modalLabel.text('Yeni Ders Ekle'); showLoadingInModal();
        saveBtn.show(); updateBtn.hide(); deleteBtn.hide();
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
    
    $(document).on('click', '.has-lesson', function() {
        const lessonId = $(this).data('lesson-id');
        modalLabel.text('Dersi Düzenle'); showLoadingInModal();
        saveBtn.hide(); updateBtn.data('lesson-id', lessonId).show(); deleteBtn.data('lesson-id', lessonId).show();
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

    // --- MODAL & GLOBAL BUTON AKSİYONLARI ---
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

});
</script>
<?= $this->endSection() ?>