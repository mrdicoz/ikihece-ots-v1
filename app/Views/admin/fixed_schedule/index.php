<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title ?? 'Sabit Ders Programı') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-pin-angle-fill"></i> Sabit Ders Programı Yönetimi</h1>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="alert alert-info">
                Bu ekrandan, öğretmenlerin haftalık standart program şablonunu oluşturabilirsiniz. Burada eklenen dersler, ana ders programı oluşturulurken size bir "öneri" olarak sunulacaktır.
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" style="min-width: 1200px;">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th style="width: 250px;">Öğretmen</th>
                            <th>Pazartesi</th>
                            <th>Salı</th>
                            <th>Çarşamba</th>
                            <th>Perşembe</th>
                            <th>Cuma</th>
                            <th>Cumartesi</th>
                            <th>Pazar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">
                                    Yönetilecek öğretmen bulunamadı. Lütfen önce "Sorumlu Atama" panelinden kendinize öğretmen atayın.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td class="fw-bold align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= base_url($teacher->profile_photo ?? 'assets/images/user.jpg') ?>" class="rounded-circle me-2" alt="<?= esc($teacher->first_name)?>" width="40" height="40" style="object-fit:cover;">
                                            <span><?= esc($teacher->first_name . ' ' . $teacher->last_name) ?></span>
                                        </div>
                                    </td>
                                    <?php for ($day = 1; $day <= 7; $day++): ?>
                                        <td class="p-2 align-top">
                                            <div class="fixed-lesson-cell" id="cell-<?= $teacher->id ?>-<?= $day ?>">
                                                <div class="text-center text-muted small p-3">
                                                    <div class="spinner-border spinner-border-sm" role="status">
                                                        <span class="visually-hidden">Yükleniyor...</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-outline-success btn-sm w-100 mt-2 add-fixed-lesson-btn" 
                                                    data-teacher-id="<?= $teacher->id ?>" 
                                                    data-day-of-week="<?= $day ?>">
                                                <i class="bi bi-plus-circle"></i> Düzenle
                                            </button>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fixedLessonModal" tabindex="-1" aria-labelledby="fixedLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fixedLessonModalLabel">Sabit Ders Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
$(document).ready(function() {
    
    // --- GENEL DEĞİŞKENLER ---
    const modalElement = document.getElementById('fixedLessonModal');
    const modal = new bootstrap.Modal(modalElement);
    const modalBody = modalElement.querySelector('.modal-body');
    const modalTitle = modalElement.querySelector('.modal-title');
    let tomSelect; // TomSelect nesnesini saklamak için

    // --- FONKSİYONLAR ---

    // Ana tablodaki belirli bir hücrenin içeriğini AJAX ile yeniler.
    function loadCellContent(teacherId, dayOfWeek) {
        const cell = $(`#cell-${teacherId}-${dayOfWeek}`);
        const url = `<?= site_url('admin/fixed-schedule/get-cell-content') ?>/${teacherId}/${dayOfWeek}`;
        
        cell.html('<div class="text-center text-muted small p-3"><div class="spinner-border spinner-border-sm"></div></div>');
        cell.load(url);
    }

    // Modal penceresinin içeriğini AJAX ile yeniler ve TomSelect'i başlatır.
    function loadModalContent(teacherId, dayOfWeek) {
        const url = `<?= site_url('admin/fixed-schedule/get-day-details') ?>/${teacherId}/${dayOfWeek}`;

        $.get(url, function(response) {
            modalBody.innerHTML = response;
            
            const dayNameInput = modalBody.querySelector('input[name="day_name"]');
            if(dayNameInput) {
                modalTitle.textContent = dayNameInput.value + ' Günü Sabit Dersleri';
            }
            
            if (tomSelect) tomSelect.destroy();

            tomSelect = new TomSelect('#student-select', {
                valueField: 'value',
                labelField: 'text',
                searchField: 'text',
                placeholder: 'Öğrenci adını yazmaya başlayın...',
                
                load: function(query, callback) {
                    if (!query.length || query.length < 2) return callback();
                    
                    const searchUrl = `<?= route_to('admin.fixed_schedule.search_students') ?>?q=${encodeURIComponent(query)}`;
                    fetch(searchUrl)
                        .then(response => response.json())
                        .then(json => {
                            callback(json);
                        }).catch(()=>{
                            callback();
                        });
                },
                create: false,
            });
        });
    }

    // --- SAYFA İLK YÜKLENDİĞİNDE ÇALIŞAN KOD ---
    $('.fixed-lesson-cell').each(function() {
        const cell = $(this);
        const teacherId = cell.attr('id').split('-')[1];
        const dayOfWeek = cell.attr('id').split('-')[2];
        loadCellContent(teacherId, dayOfWeek);
    });

    // --- OLAY DİNLEYİCİLERİ (EVENT LISTENERS) ---

    // Modal tamamen kapandığında, arka plandaki ilgili hücreyi yenile.
    modalElement.addEventListener('hidden.bs.modal', function (event) {
        const teacherId = $(modalBody).find('input[name="teacher_id"]').val();
        const dayOfWeek = $(modalBody).find('input[name="day_of_week"]').val();
        if (teacherId && dayOfWeek) {
            loadCellContent(teacherId, dayOfWeek);
        }
    });

    // "Düzenle" butonuna tıklandığında modalı aç ve içeriğini doldur.
    $('.add-fixed-lesson-btn').on('click', function() {
        const teacherId = $(this).data('teacher-id');
        const dayOfWeek = $(this).data('day-of-week');
        
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></div>';
        modal.show();
        loadModalContent(teacherId, dayOfWeek);
    });

    // Modal içindeki "Ekle" formunu AJAX ile gönder.
    $(modalBody).on('submit', '#add-fixed-lesson-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonHtml = submitButton.html();
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post('<?= route_to("admin.fixed_schedule.save") ?>', form.serialize())
            .done(function(response) {
                if (response.success) {
                    const teacherId = form.find('input[name="teacher_id"]').val();
                    const dayOfWeek = form.find('input[name="day_of_week"]').val();
                    loadModalContent(teacherId, dayOfWeek); // Sadece modal içeriğini yenile
                } else {
                    alert(response.message || 'Bir hata oluştu.');
                }
            })
            .fail(function() { alert('Bir sunucu hatası oluştu.'); })
            .always(function() { submitButton.prop('disabled', false).html(originalButtonHtml); });
    });

    // Modal içindeki "Sil" butonuna AJAX ile tıkla.
    $(modalBody).on('click', '.delete-fixed-lesson-btn', function(e) {
        e.preventDefault();
        if (!confirm('Bu sabit dersi silmek istediğinizden emin misiniz?')) return;
        
        const button = $(this);
        const lessonId = button.data('id');
        
        $.post('<?= route_to("admin.fixed_schedule.delete") ?>', {
            id: lessonId,
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        })
        .done(function(response) {
            if (response.success) {
                button.closest('li').fadeOut(300, function() { 
                    $(this).remove(); 
                    if ($('#fixed-lesson-list').children('li').length === 0) {
                        $('#fixed-lesson-list').html('<li class="list-group-item text-muted">Bu gün için kayıtlı sabit ders bulunmamaktadır.</li>');
                    }
                });
            } else {
                alert(response.message);
            }
        })
        .fail(function() { alert('Bir sunucu hatası oluştu.'); });
    });

});
</script>
<?= $this->endSection() ?>