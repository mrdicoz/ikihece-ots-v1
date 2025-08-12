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
    // --- GENEL DEĞİŞKENLER ---
    var lessonModal = new bootstrap.Modal(document.getElementById('lessonFormModal'));
    var modalBody = $('#lessonFormModalBody');
    var modalLabel = $('#lessonFormModalLabel');
    var saveBtn = $('#saveLessonBtn');
    var deleteBtn = $('#deleteLessonBtn');
    var tomSelect; // TomSelect instance'ını burada tanımlıyoruz

    // --- MODAL İŞLEMLERİ ---

    // Boş bir zaman dilimine tıklandığında
    $('.available-slot').on('click', function() {
        var slot = $(this);
        var teacherId = slot.data('teacher-id');
        var date = slot.data('date');
        var time = slot.data('time');

        modalLabel.text('Yeni Ders Ekle');
        // Modal açılmadan önce yükleniyor animasyonu göster
        modalBody.html('<div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2">Öğrenci önerileri yükleniyor...</p></div>');
        saveBtn.show();
        deleteBtn.hide();
        lessonModal.show();

        // --- AKILLI ÖNERİ SİSTEMİ ENTEGRASYONU ---
        // Backend'den akıllı önerileri çekelim
        $.get('<?= route_to("schedule.suggestions") ?>', { 
            teacher_id: teacherId, 
            date: date, 
            start_time: time 
        }, function(students) {
            // Backend'den gelen veriyi TomSelect formatına çevirelim
            let tomSelectOptions = students.map(function(student) {
                return {
                    value: student.id,
                    text: student.name,
                    type: student.type // 'fixed', 'history', 'other' etiketini koru
                };
            });

            // Modal içine formu dinamik olarak oluşturalım
            let form = $('<form id="lesson-form"></form>');
            let endTime = calculateEndTime(time);

            form.append(`<input type="hidden" name="lesson_date" value="${date}">`);
            form.append(`<input type="hidden" name="start_time" value="${time}">`);
            form.append(`<input type="hidden" name="end_time" value="${endTime}">`);
            form.append(`<input type="hidden" name="teacher_id" value="${teacherId}">`);
            form.append('<div class="mb-3"><label class="form-label">Öğrenci(ler)</label><select id="student-select-modal" name="students[]" multiple></select></div>');
            
            modalBody.html(form);

            // Önceki TomSelect'i yok et (varsa)
            if(tomSelect) tomSelect.destroy();

            // TomSelect'i yeni ve akıllı verilerle başlat
            tomSelect = new TomSelect('#student-select-modal', {
                plugins: ['remove_button'],
                options: tomSelectOptions, // Hazırladığımız öneri listesi
                placeholder: 'Öğrenci arayın veya seçin...',
                valueField: 'value',
                labelField: 'text',
                searchField: 'text',
                // --- RENKLENDİRME KISMI ---
                render: {
                    option: function(data, escape) {
                        let classes = 'd-flex align-items-center p-2';
                        let label = '';
                        if (data.type === 'fixed') {
                            classes += ' text-success fw-bold';
                            label = '<span class="badge bg-success-subtle text-success-emphasis rounded-pill ms-auto">Sabit Program</span>';
                        } else if (data.type === 'history') {
                            classes += ' text-primary';
                            label = '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill ms-auto">Sık Ders</span>';
                        }
                        return `<div class="${classes}"><div>${escape(data.text)}</div>${label}</div>`;
                    },
                    item: function(data, escape) {
                        return `<div>${escape(data.text)}</div>`;
                    }
                }
            });

        }).fail(() => modalBody.html('<div class="alert alert-danger">Öğrenci önerileri yüklenemedi. Lütfen tekrar deneyin.</div>'));
    });

    // --- SİZİN ÇALIŞAN DİĞER FONKSİYONLARINIZ (Değişiklik yok) ---

    // Dolu bir derse tıklandığında
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

    // "Dersi Kaydet" butonu
    saveBtn.on('click', function() {
        $.post('<?= route_to("schedule.create") ?>', $('#lesson-form').serialize() + '&<?= csrf_token() ?>=<?= csrf_hash() ?>')
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });
    
        // "Dersi Sil" butonu
    deleteBtn.on('click', function() {
        if (!confirm('Bu dersi silmek istediğinizden emin misiniz?')) return;
        let lessonId = $(this).data('lesson-id');
        $.post(`<?= site_url('schedule/delete-lesson/') ?>${lessonId}`, {'<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
            .done(res => res.success ? window.location.reload() : alert(res.message))
            .fail(() => alert('Bir hata oluştu.'))
            .always(() => lessonModal.hide());
    });

    // Bitiş saatini hesaplayan yardımcı fonksiyon
    function calculateEndTime(startTime) {
        const [hours, minutes] = startTime.split(':').map(Number);
        const date = new Date();
        date.setHours(hours, minutes, 0);
        date.setMinutes(date.getMinutes() + <?= config('Ots')->lessonDurationMinutes ?? 50 ?>);
        const endHours = String(date.getHours()).padStart(2, '0');
        const endMinutes = String(date.getMinutes()).padStart(2, '0');
        return `${endHours}:${endMinutes}`;
    }

    // Basit HTML escape fonksiyonu
    function escape(str) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
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