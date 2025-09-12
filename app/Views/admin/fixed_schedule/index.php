<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title ?? 'Sabit Ders Programı') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4" id="fixed-schedule-page">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 d-print-none">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-pin-angle-fill"></i> Sabit Ders Programı Yönetimi</h1>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="alert alert-info d-print-none">
                Bu ekrandan, öğretmenlerin haftalık standart program şablonunu oluşturabilirsiniz. Burada eklenen dersler, ana ders programı oluşturulurken size bir "öneri" olarak sunulacaktır.
            </div>
            
            <?php if (empty($teachers)): ?>
                <div class="alert alert-warning text-center">Yönetilecek öğretmen bulunamadı. Lütfen önce "Sorumlu Atama" panelinden kendinize öğretmen atayın.</div>
            <?php else: ?>
                
                <ul class="nav nav-pills nav-pills-success mb-4 d-print-none" id="dayTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#day1" type="button">Pazartesi</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#day2" type="button">Salı</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#day3" type="button">Çarşamba</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#day4" type="button">Perşembe</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#day5" type="button">Cuma</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#day6" type="button">Cumartesi</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#day7" type="button">Pazar</button></li>
                </ul>

                <div class="tab-content" id="dayTabsContent">
                    <?php 
                    $dayNames = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                    for ($dayNum = 1; $dayNum <= 7; $dayNum++): 
                    ?>
                        <div class="tab-pane fade <?= $dayNum === 1 ? 'show active' : '' ?>" id="day<?= $dayNum ?>" role="tabpanel">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                                <h4><?= $dayNames[$dayNum] ?> Sabit Ders Programı</h4>
                                <button class="btn btn-secondary btn-sm" onclick="printDay(<?= $dayNum ?>, '<?= $dayNames[$dayNum] ?>')">
                                    <i class="bi bi-printer"></i> <?= $dayNames[$dayNum] ?>'yi Yazdır
                                </button>
                            </div>

                            <div id="printableArea<?= $dayNum ?>">
                                <h3 class="text-center d-none d-print-block mb-3"><?= $dayNames[$dayNum] ?> Sabit Ders Programı</h3>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered schedule-grid text-center" style="min-width: 900px;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 200px;">Öğretmen</th>
                                                <?php for ($hour = config('Ots')->scheduleStartHour ?? 10; $hour < (config('Ots')->scheduleEndHour ?? 18); $hour++): ?>
                                                    <th><?= str_pad((string)$hour, 2, '0', STR_PAD_LEFT) ?>:00</th>
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
                                                        <div class="mt-1 action-buttons d-print-none">
                                                            <button class="btn btn-sm btn-success add-day-lesson-btn" 
                                                                    data-teacher-id="<?= $teacher->id ?>" 
                                                                    data-day-of-week="<?= $dayNum ?>" 
                                                                    title="Bu Güne Ders Ekle">
                                                                <i class="bi bi-calendar-plus"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <?php for ($hour = config('Ots')->scheduleStartHour ?? 10; $hour < (config('Ots')->scheduleEndHour ?? 18); $hour++): ?>
                                                        <td class="align-middle fixed-lesson-slot" data-teacher-id="<?= $teacher->id ?>" data-day="<?= $dayNum ?>" data-hour="<?= $hour ?>">
                                                            <div class="fixed-lesson-content" id="slot-<?= $teacher->id ?>-<?= $dayNum ?>-<?= $hour ?>">
                                                                <div class="text-center text-muted small p-2">
                                                                    <div class="spinner-border spinner-border-sm" role="status">
                                                                        <span class="visually-hidden">Yükleniyor...</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button class="btn btn-sm btn-outline-success add-hour-lesson-btn d-print-none" 
                                                                    data-teacher-id="<?= $teacher->id ?>" 
                                                                    data-day-of-week="<?= $dayNum ?>" 
                                                                    data-hour="<?= $hour ?>" 
                                                                    style="font-size: 10px; padding: 2px 4px; margin-top: 2px;" 
                                                                    title="<?= $hour ?>:00 Dersini Düzenle">
                                                                <i class="bi bi-plus-circle"></i>
                                                            </button>
                                                        </td>
                                                    <?php endfor; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
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
<style>
@media print {
    @page { 
        size: landscape; /* Sayfayı yatay yap */
        margin: 1cm;     /* Kenar boşlukları */
    }

    body { 
        font-size: 14pt !important; 
    }

    /* Yazdırılmayacak tüm genel elementleri gizle */
    body > .navbar, 
    body > footer, 
    #fixed-schedule-page > .d-print-none, 
    .modal, 
    .d-print-none { 
        display: none !important; 
    }

    /* Yazdırma alanını ve içindekileri görünür yap ve tam sayfa yap */
    [id^="printableArea"] {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        padding: 1cm; /* @page margin ile aynı */
        box-sizing: border-box;
    }

    /* Gereksiz kenarlık ve gölgeleri temizle */
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
    
    /* YENİ EKLENECEK KOD BURAYA */
    #printableArea .fixed-lesson-content {
        padding: 4px !important;
        min-height: 40px !important; /* Yüksekliği koruyalım */
    }
    /* YENİ EKLENECEK KOD SONU */

    .fixed-lesson-content.has-lessons { 
        background-color: #f0f0f0 !important; 
        color: #000 !important; 
        -webkit-print-color-adjust: exact !important; 
        print-color-adjust: exact !important; 
    }

    .badge, .student-badge {
        border: 1px solid #333 !important;
        background-color: transparent !important;
        color: #000 !important;
        font-weight: normal !important;
    }
    
    a[href]:after { 
        content: none !important; 
    }

    /* Sadece yazdırılan günü göster, diğerlerini gizle */
    .tab-pane:not(.print-active) {
        display: none !important;
    }
    .tab-pane.print-active {
        display: block !important;
    }
}
/* Yeşil temalı Nav Pills */
.nav-pills-success .nav-link {
    color: var(--bs-success);
    background-color: transparent;
    border: 1px solid transparent;
}

.nav-pills-success .nav-link.active,
.nav-pills-success .nav-link:hover,
.nav-pills-success .nav-link:focus {
    color: #fff;
    background-color: var(--bs-success);
    border-color: var(--bs-success);
}

/* Normal Ekran Stilleri (Değişiklik yok) */

.fixed-lesson-content.has-lessons { background-color: #e8f5e8; border-radius: 4px; }
.student-badge { display: inline-block; margin: 1px; font-size: 0.7rem; }
.fixed-lesson-slot:hover .add-hour-lesson-btn { opacity: 1; }
</style>

<script>
function printDay(dayNumber, dayName) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('print-active'));
    document.getElementById('day' + dayNumber).classList.add('print-active');
    document.title = dayName + ' Sabit Ders Programı';
    window.print();
    setTimeout(() => {
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('print-active'));
        document.title = '<?= esc($title ?? "Sabit Ders Programı") ?>';
    }, 500);
}

$(document).ready(function() {

    // YENİ EKLENEN KOD: Popover'ları tüm sayfada etkinleştirir.
    // Bu yöntem, AJAX ile sonradan eklenen elementlerde de popover'ların çalışmasını sağlar.
    $('body').popover({
        selector: '[data-bs-toggle="popover"]',
        trigger: 'hover',
        placement: 'top',
        container: 'body' // Popover'ın tablo sınırları içinde sıkışıp kalmasını engeller.
    });
    
    const modalElement = document.getElementById('fixedLessonModal');
    const modal = new bootstrap.Modal(modalElement);
    const modalBody = modalElement.querySelector('.modal-body');
    const modalTitle = modalElement.querySelector('.modal-title');
    let tomSelect;

    function loadSlotContent(teacherId, dayOfWeek, hour) {
        const slot = $(`#slot-${teacherId}-${dayOfWeek}-${hour}`);
        const url = `<?= site_url('admin/fixed-schedule/get-slot-content') ?>/${teacherId}/${dayOfWeek}/${hour}`;
        
        slot.html('<div class="text-center p-2"><div class="spinner-border spinner-border-sm"></div></div>');
        
        $.get(url).done(function(response) {
            slot.html(response);
            if (slot.find('.student-badge').length > 0) {
                slot.addClass('has-lessons');
            } else {
                slot.removeClass('has-lessons');
            }
        }).fail(function() { slot.html('<div class="text-muted small p-1">-</div>'); });
    }

    function loadModalContent(teacherId, dayOfWeek, hour = null) {
        let url = hour ? `<?= site_url('admin/fixed-schedule/get-hour-details') ?>/${teacherId}/${dayOfWeek}/${hour}` : `<?= site_url('admin/fixed-schedule/get-day-details') ?>/${teacherId}/${dayOfWeek}`;

        $.get(url, function(response) {
            modalBody.innerHTML = response;
            
            const dayNames = ['','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
            let title = dayNames[dayOfWeek];
            if(hour) { title += ` - ${String(hour).padStart(2,'0')}:00`; }
            modalTitle.textContent = title + ' Sabit Dersleri';
            
            if (tomSelect) tomSelect.destroy();

            const selectEl = document.getElementById('student-select');
            if (selectEl) {
                tomSelect = new TomSelect(selectEl, {
                    valueField: 'value', labelField: 'text', searchField: 'text',
                    placeholder: 'Öğrenci adını yazın...',
                    options: <?= json_encode($studentsForSelect) ?>,
                    load: function(query, callback) {
                        if (!query.length || query.length < 2) return callback();
                        const searchUrl = `<?= route_to('admin.fixed_schedule.search_students') ?>?q=${encodeURIComponent(query)}`;
                        
                        fetch(searchUrl, {
                            headers: { "X-Requested-With": "XMLHttpRequest" }
                        })
                        .then(response => response.json())
                        .then(json => callback(json))
                        .catch((error) => {
                            console.error("Öğrenci arama hatası:", error);
                            callback();
                        });
                    }, create: false,
                });
            }
        });
    }
    
    function loadActiveTabData() {
        const activeTabPane = document.querySelector('.tab-pane.active');
        if (activeTabPane) {
            activeTabPane.querySelectorAll('.fixed-lesson-content').forEach(function(slot) {
                const id = slot.id.split('-');
                loadSlotContent(id[1], id[2], id[3]);
            });
            activeTabPane.dataset.loaded = 'true';
        }
    }

    loadActiveTabData();

    $('#dayTabs button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
        if (targetPane && !targetPane.dataset.loaded) {
            loadActiveTabData();
        }
    });

    modalElement.addEventListener('hidden.bs.modal', function (event) {
        const teacherId = $(modalBody).find('input[name="teacher_id"]').val();
        const dayOfWeek = $(modalBody).find('input[name="day_of_week"]').val();
        const hour = $(modalBody).find('input[name="hour"]').val();
        
        if (teacherId && dayOfWeek) {
            if (hour) {
                loadSlotContent(teacherId, dayOfWeek, hour);
            } else {
                for (let h = <?= config('Ots')->scheduleStartHour ?? 10 ?>; h < <?= config('Ots')->scheduleEndHour ?? 18 ?>; h++) {
                    loadSlotContent(teacherId, dayOfWeek, h);
                }
            }
        }
    });

    $(document).on('click', '.add-hour-lesson-btn', function() {
        const btn = $(this);
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';
        modal.show();
        loadModalContent(btn.data('teacher-id'), btn.data('day-of-week'), btn.data('hour'));
    });

    $(document).on('click', '.add-day-lesson-btn', function() {
        const btn = $(this);
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';
        modal.show();
        loadModalContent(btn.data('teacher-id'), btn.data('day-of-week'));
    });

    $(modalBody).on('submit', '#add-fixed-lesson-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post('<?= route_to("admin.fixed_schedule.save") ?>', form.serialize())
            .done(function(response) {
                if (response.success) {
                    const hour = form.find('input[name="hour"]').val();
                    loadModalContent(form.find('input[name="teacher_id"]').val(), form.find('input[name="day_of_week"]').val(), hour);
                } else {
                    alert(response.message || 'Hata oluştu.');
                }
            })
            .fail(function() { alert('Sunucu hatası.'); })
            .always(function() { btn.prop('disabled', false).html(originalHtml); });
    });

    $(modalBody).on('click', '.delete-fixed-lesson-btn', function(e) {
        e.preventDefault();
        if (!confirm('Bu dersi silmek istediğinizden emin misiniz?')) return;
        
        const btn = $(this);
        const lessonId = btn.data('id');
        
        $.post('<?= route_to("admin.fixed_schedule.delete") ?>', { id: lessonId, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
        .done(function(response) {
            if (response.success) {
                btn.closest('li').fadeOut(300, function() { 
                    $(this).remove(); 
                    if ($('#fixed-lesson-list').children('li').length === 0) {
                        $('#fixed-lesson-list').html('<li class="list-group-item text-muted">Bu gün/saat için kayıtlı sabit ders bulunmamaktadır.</li>');
                    }
                });
            } else { alert(response.message); }
        })
        .fail(function() { alert('Sunucu hatası.'); });
    });

});
</script>
<?= $this->endSection() ?>