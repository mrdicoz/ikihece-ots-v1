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
                                </td>
<?php for ($hour = config('Ots')->scheduleStartHour; $hour < config('Ots')->scheduleEndHour; $hour++): ?>
    <?php
        $timeStr = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
        $hourKey = str_pad($hour, 2, '0', STR_PAD_LEFT); // Anahtarı string yapıyoruz ('08', '09' gibi)
        $lesson = $lessonMap[$teacher->id][$hourKey] ?? null; // <--- DÜZELTİLMİŞ KONTROL
    ?>
    <?php if ($lesson): ?>
        <td class="align-middle bg-success-subtle has-lesson" data-lesson-id="<?= $lesson['id'] ?>">
            <small><?= esc($lesson['student_names']) ?></small>
        </td>
    <?php else: ?>
        <td class="align-middle available-slot" data-date="<?= $displayDate ?>" data-time="<?= $timeStr ?>" data-teacher-id="<?= $teacher->id ?>">
            +
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
</div>

<div class="modal fade" id="lessonFormModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="lessonFormModalLabel">Yeni Ders Ekle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="lessonFormModalBody">
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-danger" id="deleteLessonBtn" style="display: none;">Dersi Sil</button>
        <button type="button" class="btn btn-success" id="saveLessonBtn">Dersi Kaydet</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Modal ve bileşenlerini bir kere seçip değişkenlere atıyoruz.
    var lessonModal = new bootstrap.Modal(document.getElementById('lessonFormModal'));
    var lessonModalBody = $('#lessonFormModalBody');
    var lessonModalLabel = $('#lessonFormModalLabel');
    var saveLessonBtn = $('#saveLessonBtn');
    var deleteLessonBtn = $('#deleteLessonBtn');

    // --- OLAY DİNLEYİCİLERİ (EVENT LISTENERS) ---

    // 1. Boş bir slota (+) tıklandığında: YENİ DERS EKLEME FORMUNU AÇAR
    $('.available-slot').on('click', function() {
        var slot = $(this);
        var date = slot.data('date');
        var time = slot.data('time');
        var teacherId = slot.data('teacher-id');

        lessonModalLabel.text('Yeni Ders Ekle (' + date + ' ' + time.substring(0, 5) + ')');
        lessonModalBody.html('<div class="text-center p-5"><div class="spinner-border text-success" role="status"></div></div>');
        
        // Butonların görünürlüğünü ayarla
        deleteLessonBtn.hide();
        saveLessonBtn.show();
        
        lessonModal.show();

        // Öğrencileri getirmek için AJAX isteği
        $.ajax({
            url: '<?= route_to('schedule.get_students') ?>',
            type: 'GET',
            dataType: 'json',
            success: function(students) {
                let formHtml = '<form id="add-lesson-form">';
                formHtml += '<input type="hidden" name="lesson_date" value="' + date + '">';
                formHtml += '<input type="hidden" name="start_time" value="' + time + '">';
                
                let startTime = new Date(date + 'T' + time);
                startTime.setHours(startTime.getHours() + 1);
                let endTime = startTime.toTimeString().substring(0,8);
                formHtml += '<input type="hidden" name="end_time" value="' + endTime + '">';
                
                formHtml += '<input type="hidden" name="teacher_id" value="' + teacherId + '">';
                
                formHtml += '<div class="mb-3">';
                formHtml += '<label for="student-select" class="form-label">Öğrenci(ler) Seçin</label>';
                formHtml += '<select id="student-select" name="students[]" multiple placeholder="Öğrenci arayın..."></select>';
                formHtml += '</div></form>';
                
                lessonModalBody.html(formHtml);

                new TomSelect('#student-select', {
                    plugins: ['remove_button'],
                    create: false,
                    options: students,
                    maxItems: 10
                });
            },
            error: function() {
                lessonModalBody.html('<div class="alert alert-danger">Öğrenci listesi yüklenemedi.</div>');
            }
        });
    });

    // 2. Dolu bir derse tıklandığında: DERS DETAYLARINI GÖSTERİR
    $('.has-lesson').on('click', function() {
        var lessonId = $(this).data('lesson-id');
        deleteLessonBtn.data('lesson-id', lessonId); 

        lessonModalLabel.text('Ders Detayları');
        lessonModalBody.html('<div class="text-center p-5"><div class="spinner-border text-success" role="status"></div></div>');
        
        saveLessonBtn.hide();
        deleteLessonBtn.show();
        
        lessonModal.show();

        $.ajax({
            url: '<?= site_url('schedule/get-lesson-details/') ?>' + lessonId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success){
                    let lesson = response.lesson;
                    let detailHtml = '<p><strong>Tarih:</strong> ' + lesson.lesson_date + '</p>';
                    detailHtml += '<p><strong>Saat:</strong> ' + lesson.start_time.substring(0,5) + ' - ' + lesson.end_time.substring(0,5) + '</p>';
                    detailHtml += '<hr><p><strong>Katılan Öğrenciler:</strong></p><div>' + (lesson.student_names || 'Öğrenci atanmamış.') + '</div>';
                    lessonModalBody.html(detailHtml);
                } else {
                    lessonModalBody.html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                lessonModalBody.html('<div class="alert alert-danger">Ders detayları alınamadı.</div>');
            }
        });
    });

    // 3. Modal'daki "Dersi Kaydet" butonuna tıklandığında: YENİ DERSİ KAYDEDER
    saveLessonBtn.on('click', function() {
        var form = $('#add-lesson-form');
        if (form.length === 0) return;

        $.ajax({
            url: '<?= route_to('schedule.create') ?>',
            type: 'POST',
            data: form.serialize() + '&<?= csrf_token() ?>=' + '<?= csrf_hash() ?>',
            dataType: 'json',
            beforeSend: function() {
                saveLessonBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Kaydediliyor...');
            },
            success: function(response) {
                if(response.success) {
                    lessonModal.hide();
                    window.location.reload(); 
                } else {
                    alert(response.message || 'Bir hata oluştu.');
                }
            },
            error: function() {
                alert('Sunucuyla iletişim kurulamadı.');
            },
            complete: function() {
                 saveLessonBtn.prop('disabled', false).text('Dersi Kaydet');
            }
        });
    });

    // 4. Modal'daki "Dersi Sil" butonuna tıklandığında: MEVCUT DERSİ SİLER
    deleteLessonBtn.on('click', function() {
        var lessonId = $(this).data('lesson-id');
        if(!confirm(lessonId + ' ID\'li dersi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')){
            return;
        }

        $.ajax({
            url: '<?= site_url('schedule/delete-lesson/') ?>' + lessonId,
            type: 'POST',
            data: {'<?= csrf_token() ?>': '<?= csrf_hash() ?>'},
            dataType: 'json',
            beforeSend: function() {
                deleteLessonBtn.prop('disabled', true).text('Siliniyor...');
            },
            success: function(response){
                if(response.success){
                    lessonModal.hide();
                    window.location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Ders silinirken bir hata oluştu.');
            },
            complete: function() {
                deleteLessonBtn.prop('disabled', false).text('Dersi Sil');
            }
        });
    });

});
</script>
<style>
/* Tıklanabilir hücreler için stiller */
.schedule-grid .available-slot, .schedule-grid .has-lesson {
    cursor: pointer;
    transition: background-color 0.2s;
}
.schedule-grid .available-slot {
    font-weight: bold;
    color: #198754;
}
.schedule-grid .available-slot:hover {
    background-color: #d1e7dd;
}
.schedule-grid .has-lesson:hover {
    background-color: #badbcc;
}
.schedule-grid .has-lesson {
    font-size: 0.8em;
}
</style>
<?= $this->endSection() ?>