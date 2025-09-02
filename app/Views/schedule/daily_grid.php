<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4" id="daily-grid-page">

    <div class="d-sm-flex align-items-center justify-content-between mb-3 d-print-none">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-table"></i> <?= esc($title) ?></h1>
        <div class="btn-group">
            <a href="<?= route_to('schedule.index') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Ana Takvime Dön
            </a>
            <button id="printScheduleBtn" class="btn btn-info btn-sm">
                <i class="bi bi-printer"></i> Yazdır
            </button>
        </div>
    </div>

    <div id="printableArea">
        <h3 class="text-center d-none d-print-block mb-3"><?= esc($title) ?></h3>
        
        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($teachers)): ?>
                    <div class="alert alert-warning text-center">Bu programı görüntülemek için yetkiniz olan bir öğretmen bulunmamaktadır.</div>
                <?php else: ?>
                    <div class="table-responsive table-sticky-container">
                        <table class="table table-bordered schedule-grid text-center" style="min-width: 900px;">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th style="width: 200px;">Öğretmen</th>
                                    <?php for ($hour = config('Ots')->scheduleStartHour; $hour < config('Ots')->scheduleEndHour; $hour++): ?>
                                        <th><?= str_pad($hour, 2, '0', STR_PAD_LEFT) ?>:00</th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td class="align-middle fw-bold">
                                            <img src="<?= base_url(ltrim($teacher->profile_photo ?? '/assets/images/user.jpg', '/')) ?>" class="rounded-circle me-2 d-print-none" width="40" height="40" style="object-fit: cover;">
                                            <br class="d-print-none">
                                            <?= esc($teacher->first_name . ' ' . $teacher->last_name) ?>
                                            <?php if (!empty($teacher->branch)): ?>
                                                <span class="badge bg-secondary d-block mt-1 text-truncate"><?= esc($teacher->branch) ?></span>
                                            <?php endif; ?>
                                            <div class="mt-1 btn-group action-buttons d-print-none">
                                                <button class="btn btn-sm btn-success bildirim-gonder-tek" data-teacher-id="<?= esc($teacher->id) ?>" title="Bildirim Gönder">
                                                    <i class="bi bi-bell"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary add-fixed-lessons" data-teacher-id="<?= esc($teacher->id) ?>" data-date="<?= esc($displayDate) ?>" title="Sabit Dersleri Ekle">
                                                    <i class="bi bi-calendar-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-day-lessons" data-teacher-id="<?= esc($teacher->id) ?>" data-date="<?= esc($displayDate) ?>" data-teacher-name="<?= esc($teacher->first_name . ' ' . $teacher->last_name) ?>" title="Günün Derslerini Sil">
                                                    <i class="bi bi-calendar-x"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <?php for ($hour = config('Ots')->scheduleStartHour; $hour < config('Ots')->scheduleEndHour; $hour++): ?>
                                            <?php
                                                $timeStr = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
                                                $hourKey = str_pad($hour, 2, '0', STR_PAD_LEFT);
                                                $lesson = $lessonMap[$teacher->id][$hourKey] ?? null;
                                            ?>
                                            <?php if ($lesson): ?>
                                                <td class="align-middle bg-success-subtle has-lesson" data-lesson-id="<?= $lesson['id'] ?>">
                                                    <?php
                                                    $studentNames = explode(',', $lesson['student_names']);
                                                    foreach ($studentNames as $name) {
                                                        echo '<span class="badge text-bg-secondary student-badge">' . esc(trim($name)) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                            <?php else: ?>
                                                <td class="align-middle available-slot" data-date="<?= $displayDate ?>" data-time="<?= $timeStr ?>" data-teacher-id="<?= $teacher->id ?>">
                                                    <i class="bi bi-person-fill-add d-print-none"></i>
                                                </td>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="text-center mt-4 d-print-none">
                        <div class="btn-group" role="group" aria-label="Toplu İşlem Butonları">
                            <button class="btn btn-success" id="bildirim-gonder-hepsi">
                                <i class="bi bi-broadcast-pin"></i> Hepsine Bildir
                            </button>
                            <button class="btn btn-primary" id="addAllFixedLessonsBtn" data-date="<?= esc($displayDate) ?>">
                                <i class="bi bi-calendar-check-fill"></i> Tüm Sabitleri Ekle
                            </button>
                            <button class="btn btn-danger" id="deleteAllLessonsBtn" data-date="<?= esc($displayDate) ?>">
                                <i class="bi bi-trash3-fill"></i> Tüm Dersleri Sil
                            </button>
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
        overflow: visible !important;
        height: auto !important;
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
    // --- YAZDIR BUTONU ---
    $('#printScheduleBtn').on('click', function() {
        window.print();
    });

    // --- GENEL DEĞİŞKENLER ---
    const lessonModal = new bootstrap.Modal(document.getElementById('lessonFormModal'));
    const modalBody = $('#lessonFormModalBody');
    const modalLabel = $('#lessonFormModalLabel');
    const saveBtn = $('#saveLessonBtn');
    const updateBtn = $('#updateLessonBtn');
    const deleteBtn = $('#deleteLessonBtn');
    let tomSelect;

    // --- YARDIMCI FONKSİYONLAR ---
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
                    let classes = 'd-flex align-items-center p-2';
                    let typeLabel = '';
                    if (data.type === 'fixed') {
                        classes += ' text-success fw-bold';
                        typeLabel = '<span class="badge bg-success-subtle text-success-emphasis rounded-pill ms-auto">Sabit Program</span>';
                    } else if (data.type === 'history') {
                        classes += ' text-primary';
                        typeLabel = '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill ms-auto">Sık Ders</span>';
                    }
                    let lessonCounts = `<span class="ms-2"><span class="badge bg-info-subtle text-info-emphasis" title="Bireysel Telafi Hakkı">B: ${escape(data.bireysel ?? 0)}</span><span class="badge bg-warning-subtle text-warning-emphasis" title="Grup Telafi Hakkı">G: ${escape(data.grup ?? 0)}</span></span>`;
                    return `<div class="${classes}"><div>${escape(data.text)}${lessonCounts}</div>${typeLabel}</div>`;
                },
                item: function(data, escape) {
                     let lessonCounts = `<span class="ms-2"><span class="badge bg-info-subtle text-info-emphasis" title="Bireysel Telafi Hakkı">B: ${escape(data.bireysel ?? 0)}</span><span class="badge bg-warning-subtle text-warning-emphasis" title="Grup Telafi Hakkı">G: ${escape(data.grup ?? 0)}</span></span>`;
                    return `<div>${escape(data.text)}${lessonCounts}</div>`;
                }
            }
        });
    }

    // --- YENİ DERS EKLEME MODALI ---
    $('.available-slot').on('click', function() {
        const slot = $(this);
        const teacherId = slot.data('teacher-id');
        const date = slot.data('date');
        const time = slot.data('time');

        modalLabel.text('Yeni Ders Ekle');
        showLoadingInModal();
        saveBtn.show();
        updateBtn.hide();
        deleteBtn.hide();
        lessonModal.show();

        $.get('<?= route_to("schedule.suggestions") ?>', { teacher_id: teacherId, date: date, start_time: time })
            .done(function(students) {
                const tomSelectOptions = students.map(s => ({
                    value: s.id, text: s.name, type: s.type, bireysel: s.bireysel, grup: s.grup
                }));

                const form = $(`<form id="lesson-form"></form>`);
                form.append(`<input type="hidden" name="teacher_id" value="${teacherId}">`);
                form.append(`<input type="hidden" name="lesson_date" value="${date}">`);
                form.append(`<input type="hidden" name="start_time" value="${time}">`);
                form.append(`<input type="hidden" name="end_time" value="${calculateEndTime(time)}:00">`);
                form.append('<div class="mb-3"><label class="form-label">Öğrenci(ler)</label><select id="student-select-modal" name="students[]" multiple></select></div>');
                
                modalBody.html(form);
                createTomSelect(tomSelectOptions);
            })
            .fail(() => modalBody.html('<div class="alert alert-danger">Öğrenci önerileri yüklenemedi.</div>'));
    });
    
    // --- MEVCUT DERSİ DÜZENLEME MODALI ---
    $('.has-lesson').on('click', function() {
        const lessonId = $(this).data('lesson-id');

        modalLabel.text('Dersi Düzenle');
        showLoadingInModal();
        saveBtn.hide();
        updateBtn.data('lesson-id', lessonId).show();
        deleteBtn.data('lesson-id', lessonId).show();
        lessonModal.show();

        $.get(`<?= site_url('schedule/get-lesson-details/') ?>${lessonId}`)
            .done(function(response) {
                if (!response.success) {
                    modalBody.html(`<div class="alert alert-danger">${response.message}</div>`);
                    return;
                }
                const lesson = response.lesson;
                const existingStudents = lesson.students.map(s => s.id);

                $.get('<?= route_to("schedule.suggestions") ?>', { 
                    teacher_id: lesson.teacher_id, 
                    date: lesson.lesson_date, 
                    start_time: lesson.start_time 
                }).done(function(students) {
                    const tomSelectOptions = students.map(s => ({
                        value: s.id, text: s.name, type: s.type, bireysel: s.bireysel, grup: s.grup
                    }));

                    const form = $(`<form id="lesson-form"></form>`);
                    form.append('<div class="mb-3"><label class="form-label">Öğrenci(ler)</label><select id="student-select-modal" name="students[]" multiple></select></div>');
                    modalBody.html(form);
                    
                    createTomSelect(tomSelectOptions, existingStudents);
                }).fail(() => modalBody.html('<div class="alert alert-danger">Öğrenci listesi yüklenemedi.</div>'));
            })
            .fail(() => modalBody.html('<div class="alert alert-danger">Ders detayları alınamadı.</div>'));
    });


    // --- MODAL BUTON AKSİYONLARI ---
    saveBtn.on('click', function() {
        $.post('<?= route_to("schedule.create") ?>', $('#lesson-form').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Ders kaydedilirken bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });
    
    updateBtn.on('click', function() {
        const lessonId = $(this).data('lesson-id');
        $.post(`<?= site_url('schedule/update-lesson/') ?>${lessonId}`, $('#lesson-form').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Ders güncellenirken bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });

    deleteBtn.on('click', function() {
        if (!confirm('Bu dersi kalıcı olarak silmek istediğinizden emin misiniz?')) return;
        const lessonId = $(this).data('lesson-id');
        $.post(`<?= site_url('schedule/delete-lesson/') ?>${lessonId}`, {'<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Ders silinirken bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });

    // --- ÖĞRETMENE ÖZEL VE GENEL TOPLU İŞLEMLER ---
    $('.add-fixed-lessons').on('click', function() {
        var button = $(this);
        var teacherId = button.data('teacher-id');
        var date = button.data('date');
        if (!confirm('Bu öğretmenin bu güne ait tüm sabit derslerini programa eklemek istediğinizden emin misiniz? Mevcut dersler korunacaktır.')) {
            return;
        }
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.post('<?= route_to("schedule.addFixed") ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            teacher_id: teacherId,
            date: date
        }).done(function(response) {
            alert(response.message);
            if(response.success) window.location.reload();
        }).fail(function() {
            alert('Sunucu hatası.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-calendar-check"></i>');
        });
    });

    $('.delete-day-lessons').on('click', function() {
        var button = $(this);
        var teacherId = button.data('teacher-id');
        var teacherName = button.data('teacher-name');
        var date = button.data('date');
        if (!confirm(teacherName + ' adlı öğretmenin ' + date + ' tarihindeki TÜM derslerini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
            return;
        }
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.post('<?= route_to("schedule.deleteForDay") ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            teacher_id: teacherId,
            date: date
        }).done(function(response) {
            alert(response.message);
            if(response.success) window.location.reload();
        }).fail(function() {
            alert('Sunucu hatası.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-calendar-x"></i>');
        });
    });

    $('#addAllFixedLessonsBtn').on('click', function() {
        var button = $(this);
        var date = button.data('date');
        var teacherIds = [];
        $('.add-fixed-lessons').each(function() {
            teacherIds.push($(this).data('teacher-id'));
        });
        if (teacherIds.length === 0) {
            alert('Listede öğretmen bulunmuyor.');
            return;
        }
        if (!confirm('Listelenen ' + teacherIds.length + ' öğretmenin tüm sabit derslerini eklemek istediğinizden emin misiniz?')) {
            return;
        }
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Yükleniyor...');
        $.post('<?= route_to("schedule.addAllFixed") ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            teacher_ids: teacherIds,
            date: date
        }).done(function(response) {
            alert(response.message);
            if(response.success) window.location.reload();
        }).fail(function() {
            alert('Sunucu hatası.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-calendar-check-fill"></i> Tüm Sabitleri Ekle');
        });
    });

    $('#deleteAllLessonsBtn').on('click', function() {
        var button = $(this);
        var date = button.data('date');
        var teacherIds = [];
        $('.delete-day-lessons').each(function() {
            teacherIds.push($(this).data('teacher-id'));
        });
        if (teacherIds.length === 0) {
            alert('Listede öğretmen bulunmuyor.');
            return;
        }
        if (!confirm('DİKKAT! ' + teacherIds.length + ' öğretmenin bu tarihteki TÜM derslerini kalıcı olarak sileceksiniz. Emin misiniz?')) {
            return;
        }
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Siliniyor...');
        $.post('<?= route_to("schedule.deleteAllForDay") ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            teacher_ids: teacherIds,
            date: date
        }).done(function(response) {
            alert(response.message);
            if(response.success) window.location.reload();
        }).fail(function() {
            alert('Sunucu hatası.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-trash3-fill"></i> Tüm Dersleri Sil');
        });
    });

      // --- BİLDİRİM GÖNDERME FONKSİYONLARI ---

    // Genel bildirim gönderme fonksiyonu
    function sendNotification(teacherIds, buttonElement) {
        if (!teacherIds || teacherIds.length === 0) {
            alert('Bildirim gönderilecek öğretmen seçilmedi.');
            return;
        }

        // Tıklanan butonu ve diğer ilgili butonları devre dışı bırak
        const originalHtml = buttonElement.html();
        buttonElement.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        $.ajax({
            // Mevcut ve doğru rotayı kullanıyoruz
            url: '<?= route_to("notifications.sendManual") ?>',
            type: 'POST',
            data: {
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
                'teacher_ids': teacherIds
            },
            dataType: 'json',
            success: function(response) {
                alert(response.message || 'İşlem tamamlandı.');
            },
            error: function() {
                alert('Bildirim gönderilirken bir sunucu hatası oluştu.');
            },
            complete: function() {
                // İşlem bitince butonu eski haline getir
                buttonElement.prop('disabled', false).html(originalHtml);
            }
        });
    }

    // Tek bir öğretmene bildirim gönder
    $('.bildirim-gonder-tek').on('click', function(e) {
        e.preventDefault(); // Diğer olayları durdur
        const teacherId = $(this).data('teacher-id');
        sendNotification([teacherId], $(this));
    });

    // Tüm öğretmenlere bildirim gönder
    $('#bildirim-gonder-hepsi').on('click', function(e) {
        e.preventDefault();
        let teacherIds = [];
        $('.bildirim-gonder-tek').each(function() {
            teacherIds.push($(this).data('teacher-id'));
        });

        if (confirm(teacherIds.length + ' öğretmene program güncelleme bildirimi göndermek istediğinizden emin misiniz?')) {
            sendNotification(teacherIds, $(this));
        }
    });
});
</script>
<?= $this->endSection() ?>