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
    var lessonModal = new bootstrap.Modal(document.getElementById('lessonFormModal'));
    var modalBody = $('#lessonFormModalBody');
    var modalLabel = $('#lessonFormModalLabel');
    var saveBtn = $('#saveLessonBtn');
    var deleteBtn = $('#deleteLessonBtn');
    var tomSelect;

    // --- SABİT DERS EKLEME ---
    $('.add-fixed-lessons').on('click', function() {
        var button = $(this);
        var teacherId = button.data('teacher-id');
        var date = button.data('date');

        if (!confirm('Bu öğretmenin bu güne ait tüm sabit derslerini programa eklemek istediğinizden emin misiniz? Mevcut dersler korunacaktır.')) {
            return;
        }

        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        $.post('<?= route_to("schedule.addFixed") ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            teacher_id: teacherId,
            date: date
        }).done(function(response) {
            if (response.success) {
                alert(response.message);
                window.location.reload();
            } else {
                alert('Hata: ' + response.message);
            }
        }).fail(function() {
            alert('Sunucuya bağlanırken bir hata oluştu. Lütfen tekrar deneyin.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-calendar-check"></i>');
        });
    });
    
    // --- GÜNÜN DERSLERİNİ SİLME ---
    $('.delete-day-lessons').on('click', function() {
        var button = $(this);
        var teacherId = button.data('teacher-id');
        var teacherName = button.data('teacher-name');
        var date = button.data('date');

        var confirmation = confirm(teacherName + ' adlı öğretmenin ' + date + ' tarihindeki TÜM derslerini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');
        
        if (!confirmation) {
            return;
        }

        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        $.post('<?= route_to("schedule.deleteForDay") ?>', {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            teacher_id: teacherId,
            date: date
        }).done(function(response) {
            if (response.success) {
                alert(response.message);
                window.location.reload();
            } else {
                alert('Hata: ' + response.message);
            }
        }).fail(function() {
            alert('Sunucu hatası. Lütfen tekrar deneyin.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-calendar-x"></i>');
        });
    });

    // --- MODAL İŞLEMLERİ ---
    $('.available-slot').on('click', function() {
        var slot = $(this);
        var teacherId = slot.data('teacher-id');
        var date = slot.data('date');
        var time = slot.data('time');

        modalLabel.text('Yeni Ders Ekle');
        modalBody.html('<div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2">Öğrenci önerileri yükleniyor...</p></div>');
        saveBtn.show();
        deleteBtn.hide();
        lessonModal.show();

        $.get('<?= route_to("schedule.suggestions") ?>', { 
            teacher_id: teacherId, 
            date: date, 
            start_time: time 
        }, function(students) {
            let tomSelectOptions = students.map(function(student) {
                return {
                    value: student.id, text: student.name, type: student.type,
                    bireysel: student.bireysel, grup: student.grup
                };
            });

            let form = $('<form id="lesson-form"></form>');
            form.append(`<input type="hidden" name="lesson_date" value="${date}">`);
            form.append(`<input type="hidden" name="start_time" value="${time}">`);
            form.append(`<input type="hidden" name="end_time" value="${calculateEndTime(time)}">`);
            form.append(`<input type="hidden" name="teacher_id" value="${teacherId}">`);
            form.append('<div class="mb-3"><label class="form-label">Öğrenci(ler)</label><select id="student-select-modal" name="students[]" multiple></select></div>');
            modalBody.html(form);

            if(tomSelect) tomSelect.destroy();
            tomSelect = new TomSelect('#student-select-modal', {
                plugins: ['remove_button'], options: tomSelectOptions,
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
        }).fail(() => modalBody.html('<div class="alert alert-danger">Öğrenci önerileri yüklenemedi.</div>'));
    });

    $('.has-lesson').on('click', function() {
        var lessonId = $(this).data('lesson-id');
        modalLabel.text('Ders Detayları');
        modalBody.html('<div class="text-center p-5"><div class="spinner-border text-success"></div></div>');
        saveBtn.hide();
        deleteBtn.data('lesson-id', lessonId).show();
        lessonModal.show();

        $.get(`<?= site_url('schedule/get-lesson-details/') ?>${lessonId}`, function(response) {
            if(response.success){
                let lesson = response.lesson;
                let studentNamesHtml = lesson.student_names.split(',').map(name => `<span class="badge bg-secondary me-1">${escape(name.trim())}</span>`).join('');
                let details = `<h6>Öğrenciler:</h6><div>${studentNamesHtml}</div>`;
                modalBody.html(details);
            }
        }).fail(() => modalBody.html('<div class="alert alert-danger">Ders detayları alınamadı.</div>'));
    });
    
    saveBtn.on('click', function() {
        $.post('<?= route_to("schedule.create") ?>', $('#lesson-form').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });
    
    deleteBtn.on('click', function() {
        if (!confirm('Bu dersi silmek istediğinizden emin misiniz?')) return;
        let lessonId = $(this).data('lesson-id');
        $.post(`<?= site_url('schedule/delete-lesson/') ?>${lessonId}`, {'<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });

    // --- YARDIMCI FONKSİYONLAR ---
    function calculateEndTime(startTime) {
        const [hours, minutes] = startTime.split(':').map(Number);
        const date = new Date();
        date.setHours(hours, minutes, 0);
        date.setMinutes(date.getMinutes() + <?= config('Ots')->lessonDurationMinutes ?? 50 ?>);
        const endHours = String(date.getHours()).padStart(2, '0');
        const endMinutes = String(date.getMinutes()).padStart(2, '0');
        return `${endHours}:${endMinutes}`;
    }

    function escape(str) {
        if (!str) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});

    // --- YENİ KOD: TOPLU SABİT DERS EKLEME ---
    $('#addAllFixedLessonsBtn').on('click', function() {
        var button = $(this);
        var date = button.data('date');
        
        // Sayfadaki tüm öğretmen ID'lerini topla
        var teacherIds = [];
        $('.add-fixed-lessons').each(function() {
            teacherIds.push($(this).data('teacher-id'));
        });

        if (teacherIds.length === 0) {
            alert('Listede işlem yapılacak öğretmen bulunmuyor.');
            return;
        }

        if (!confirm('Listelenen ' + teacherIds.length + ' öğretmenin bu güne ait tüm sabit derslerini programa eklemek istediğinizden emin misiniz?')) {
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
            alert('Sunucu hatası. Lütfen tekrar deneyin.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-calendar-check-fill"></i> Tüm Sabitleri Ekle');
        });
    });

    // --- YENİ KOD: TOPLU DERS SİLME ---
    $('#deleteAllLessonsBtn').on('click', function() {
        var button = $(this);
        var date = button.data('date');

        var teacherIds = [];
        $('.delete-day-lessons').each(function() {
            teacherIds.push($(this).data('teacher-id'));
        });

        if (teacherIds.length === 0) {
            alert('Listede işlem yapılacak öğretmen bulunmuyor.');
            return;
        }

        if (!confirm('DİKKAT! Listelenen ' + teacherIds.length + ' öğretmenin bu tarihteki TÜM derslerini kalıcı olarak sileceksiniz. Bu işlem geri alınamaz. Emin misiniz?')) {
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
            alert('Sunucu hatası. Lütfen tekrar deneyin.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="bi bi-trash3-fill"></i> Tüm Dersleri Sil');
        });
    });
</script>
<?= $this->endSection() ?>