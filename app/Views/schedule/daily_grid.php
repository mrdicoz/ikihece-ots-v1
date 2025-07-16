<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-table"></i> <?= esc($title) ?></h1>
        <a href="<?= route_to('schedule.index') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Ana Takvime Dön
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($teachers)): ?>
                <div class="alert alert-warning text-center">Bu programı görüntülemek için yetkiniz olan bir öğretmen bulunmamaktadır.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered schedule-grid text-center" style="min-width: 900px;">
                        <thead>
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
                                        <img src="<?= base_url(ltrim($teacher->profile_photo ?? '/assets/images/user.jpg', '/')) ?>" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                        <br>
                                        <?= esc($teacher->first_name . ' ' . $teacher->last_name) ?>
                                        <div class="mt-1">
                                            <button class="btn btn-sm btn-outline-success bildirim-gonder-tek" data-teacher-id="<?= esc($teacher->id) ?>">
                                                <i class="bi bi-bell"></i> Bildir
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
                                                // --- DEĞİŞİKLİK BURADA BAŞLIYOR ---
                                                // Öğrenci isimlerini virgüllerden ayırıp bir diziye atıyoruz.
                                                $studentNames = explode(',', $lesson['student_names']);
                                                foreach ($studentNames as $name) {
                                                    // Her bir ismi, alt alta gelmesi için bir rozet içinde yazdırıyoruz.
                                                    echo '<span class="badge text-bg-secondary student-badge">' . esc(trim($name)) . '</span>';
                                                }
                                                // --- DEĞİŞİKLİK BURADA BİTİYOR ---
                                                ?>
                                            </td>
                                        <?php else: ?>
                                            <td class="align-middle available-slot" data-date="<?= $displayDate ?>" data-time="<?= $timeStr ?>" data-teacher-id="<?= $teacher->id ?>">
                                                <i class="bi bi-person-fill-add"></i>
                                            </td>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="text-center mt-4">
                        <button class="btn btn-success" id="bildirim-gonder-hepsi">
                            <i class="bi bi-broadcast-pin"></i> Listelenen Tüm Öğretmenlere Bildirim Gönder
                        </button>
                    </div>
                </div>
            <?php endif; ?>
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
<script>
$(document).ready(function() {
    var lessonModal = new bootstrap.Modal(document.getElementById('lessonFormModal'));
    var modalBody = $('#lessonFormModalBody');
    var modalLabel = $('#lessonFormModalLabel');
    var saveBtn = $('#saveLessonBtn');
    var deleteBtn = $('#deleteLessonBtn');
    
    // Boş slota tıklama
    $('.available-slot').on('click', function() {
        var slot = $(this);
        modalLabel.text('Yeni Ders Ekle');
        modalBody.html('<div class="text-center p-5"><div class="spinner-border text-success"></div></div>');
        saveBtn.show();
        deleteBtn.hide();
        lessonModal.show();

        $.get('<?= route_to('schedule.get_students') ?>', function(students) {
            let form = $('<form id="lesson-form"></form>');
            form.append('<input type="hidden" name="lesson_date" value="' + slot.data('date') + '">');
            form.append('<input type="hidden" name="start_time" value="' + slot.data('time') + '">');
            form.append('<input type="hidden" name="end_time" value="' + (parseInt(slot.data('time').substring(0,2))+1).toString().padStart(2, '0') + ':00:00">');
            form.append('<input type="hidden" name="teacher_id" value="' + slot.data('teacher-id') + '">');
            form.append('<div class="mb-3"><label class="form-label">Öğrenci(ler)</label><select id="student-select" name="students[]" multiple></select></div>');
            
            modalBody.html(form);

            new TomSelect('#student-select', {
                plugins: ['remove_button'],
                options: students,
                placeholder: 'Öğrenci arayın...'
            });
        }).fail(() => modalBody.html('<div class="alert alert-danger">Öğrenciler yüklenemedi.</div>'));
    });

    // Dolu derse tıklama
    $('.has-lesson').on('click', function() {
        var lessonId = $(this).data('lesson-id');
        modalLabel.text('Ders Detayları');
        modalBody.html('<div class="text-center p-5"><div class="spinner-border text-success"></div></div>');
        saveBtn.hide();
        deleteBtn.data('lesson-id', lessonId).show();
        lessonModal.show();

        $.get('<?= site_url('schedule/get-lesson-details/') ?>' + lessonId, function(response) {
            if(response.success){
                let lesson = response.lesson;
                let details = '<h6>Öğrenciler:</h6><div>' + lesson.student_names + '</div>';
                modalBody.html(details);
            }
        }).fail(() => modalBody.html('<div class="alert alert-danger">Detaylar alınamadı.</div>'));
    });

    // Kaydetme
    saveBtn.on('click', function() {
        $.post('<?= route_to('schedule.create') ?>', $('#lesson-form').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });
    
    // Silme
    deleteBtn.on('click', function() {
        if (!confirm('Bu dersi silmek istediğinizden emin misiniz?')) return;
        let lessonId = $(this).data('lesson-id');
        $.post('<?= site_url('schedule/delete-lesson/') ?>' + lessonId, {'<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });
});

    $(document).ready(function() {
        
        // Dinamik olarak oluşturulmuş butonlar için event delegation kullanılır
        $('body').on('click', '.bildirim-gonder-tek', function() {
            const teacherId = $(this).data('teacher-id');
            if (teacherId) {
                sendNotificationRequest([teacherId], $(this));
            }
        });

        $('#bildirim-gonder-hepsi').on('click', function() {
            let teacherIds = [];
            // Tablodaki her bir tekli gönder butonundan teacher-id'yi topla
            $('.bildirim-gonder-tek').each(function() {
                teacherIds.push($(this).data('teacher-id'));
            });

            // Benzersiz ID'leri al (opsiyonel ama iyi bir pratik)
            let uniqueTeacherIds = [...new Set(teacherIds)];

            if (uniqueTeacherIds.length > 0) {
                sendNotificationRequest(uniqueTeacherIds, $(this));
            } else {
                alert('Listede bildirim gönderilecek öğretmen bulunmuyor.');
            }
        });

        function sendNotificationRequest(ids, button) {
            const originalHtml = button.html();
            // Butonu devre dışı bırak ve bekleme animasyonu ekle
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Gönderiliyor...');

            $.ajax({
                url: '<?= site_url('notifications/send-manual') ?>',
                method: 'POST',
                data: {
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>', // CSRF token'ı gönder
                    'teacher_ids': ids
                },
                dataType: 'json', // Sunucudan JSON cevabı beklediğimizi belirtiyoruz
                success: function(response) {
                    // Daha şık bir bildirim için Toastr, SweetAlert gibi kütüphaneler kullanılabilir
                    alert(response.message); 
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Bilinmeyen bir hata oluştu.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    alert('Hata: ' + errorMessage);
                },
                complete: function() {
                    // İşlem bittiğinde butonu eski haline getir
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        }
    });
</script>
<style>
.schedule-grid .available-slot, .schedule-grid .has-lesson { cursor: pointer; transition: background-color 0.2s; }
.schedule-grid .available-slot { font-weight: bold; color: #198754; }
.schedule-grid .available-slot:hover { background-color: #d1e7dd; }
.schedule-grid .has-lesson:hover { background-color: #badbcc; }
.schedule-grid .has-lesson { font-size: 0.8em; }
</style>
<?= $this->endSection() ?>