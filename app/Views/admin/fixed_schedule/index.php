<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title ?? 'Sabit Ders Programı') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <h2 class="mb-4 d-print-none"><i class="bi bi-pin-angle-fill"></i> Sabit Ders Programı Planlama</h2>
    
    <div class="card shadow-sm mb-4 d-print-none">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="row">
                         <div class="col-md-6 mb-3 mb-md-0">
                             <label class="form-label fw-bold"><i class="bi bi-calendar3-week me-2"></i>Görüntülenecek Günler</label>
                             <div class="btn-group w-100" id="day-selector" role="group">
                                 <button type="button" class="btn btn-outline-secondary" data-day="1">Pzt</button>
                                 <button type="button" class="btn btn-outline-secondary" data-day="2">Sal</button>
                                 <button type="button" class="btn btn-outline-secondary" data-day="3">Çrş</button>
                                 <button type="button" class="btn btn-outline-secondary" data-day="4">Per</button>
                                 <button type="button" class="btn btn-outline-secondary" data-day="5">Cum</button>
                                 <button type="button" class="btn btn-outline-secondary" data-day="6">Cmt</button>
                                 <button type="button" class="btn btn-outline-secondary" data-day="7">Paz</button>
                             </div>
                         </div>
                         <div class="col-md-6">
                             <label for="teacher-filter" class="form-label fw-bold"><i class="bi bi-person-video3 me-2"></i>Öğretmen Filtrele</label>
                             <select id="teacher-filter" multiple placeholder="Tüm öğretmenler gösteriliyor..."></select>
                         </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <label class="form-label fw-bold d-block mb-2"><i class="bi bi-palette-fill me-2"></i>Görüntülenecek Haftalar</label>
                    <div class="btn-group" id="week-selector" role="group">
                         <button type="button" class="btn btn-outline-danger active" data-week-id="A">A</button>
                         <button type="button" class="btn btn-outline-primary" data-week-id="B">B</button>
                         <button type="button" class="btn btn-outline-success" data-week-id="C">C</button>
                         <button type="button" class="btn btn-outline-warning" data-week-id="D">D</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="schedule-display-area">
        <div class="text-center p-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <p class="mt-3">Ders programı yükleniyor...</p>
        </div>
    </div>
</div>

<div class="modal fade" id="lessonModal" tabindex="-1" aria-labelledby="lessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lessonModalLabel">Ders Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-slot-info" class="mb-4"></div>
                <div id="current-students" class="mb-4">
                    <h6 class="mb-3">Bu Hafta Kayıtlı Öğrenciler:</h6>
                    <div id="students-list"></div>
                </div>
                <div class="mb-4">
                    <h6 class="mb-3">Yeni Öğrenci Ekle:</h6>
                    <select id="new-student-select" placeholder="Eklemek için öğrenci adını yazın..." multiple></select>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div><button type="button" class="btn btn-outline-danger" id="clear-slot-btn"><i class="bi bi-trash"></i> Bu Saati Temizle</button></div>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="save-slot-btn"><i class="bi bi-check-circle"></i> Değişiklikleri Kaydet</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<style>
    .thead-week-A th { background-color: rgba(220, 53, 69, 0.1); } .thead-week-B th { background-color: rgba(13, 110, 253, 0.1); } .thead-week-C th { background-color: rgba(25, 135, 84, 0.1); } .thead-week-D th { background-color: rgba(255, 193, 7, 0.1); }
    .schedule-table th, .schedule-table td { border: 1px solid #dee2e6; padding: 0; text-align: center; font-size: 12px; }
    .schedule-table thead th { padding: 10px 8px;  top: 0; z-index: 10; background-clip: padding-box; }
    .teacher-cell { text-align: left; width: 220px; min-width: 220px; padding: 8px !important; background-color: #fff; vertical-align: middle; }
    .lesson-slot { height: 70px; vertical-align: top; cursor: pointer; position: relative; background-color: #fff; padding: 4px !important; }
    .lesson-slot:hover { background-color: #eef5ff; }
    .student-name { display: block; border-radius: 4px; padding: 4px; font-size: 11px; margin: 2px; font-weight: 500; text-align: left; }
    .student-week-A { background-color: #f8d7da; color: #721c24; } .student-week-B { background-color: #cce5ff; color: #004085; } .student-week-C { background-color: #d4edda; color: #155724; } .student-week-D { background-color: #fff3cd; color: #856404; }
    .week-dots { position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); display: flex; gap: 3px; }
    .week-dot { width: 8px; height: 8px; border-radius: 50%; }
    .week-A-dot { background-color: #dc3545; } .week-B-dot { background-color: #0d6efd; } .week-C-dot { background-color: #198754; } .week-D-dot { background-color: #ffc107; }
    .add-lesson-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0; transition: opacity 0.3s ease; background: rgba(0, 123, 255, 0.9); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 16px; z-index: 5; }
    .lesson-slot:hover .add-lesson-icon { opacity: 1; }
    .student-item { display: flex; align-items: center; justify-content: space-between; padding: 8px; margin-bottom: 8px; border-radius: 6px; background-color: #f8f9fa; }
    .student-item .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .student-popover-list { list-style-type: none; padding-left: 0; margin-bottom: 0; font-size: 12px; }
    .student-popover-list li { display: flex; align-items: center; margin-bottom: 8px; }
    .student-popover-list li:last-child { margin-bottom: 0; }
    .student-popover-list .student-info { text-align: left; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Değişkenler ---
        const teachersForSelect = <?= json_encode($teachersForSelect) ?>;
        const allTeachersData = <?= json_encode($teachers) ?>;
        const allStudentsData = <?= json_encode($students) ?>;
        const csrfName = '<?= csrf_token() ?>';
        let csrfHash = '<?= csrf_hash() ?>';
        const displayArea = document.getElementById('schedule-display-area');
        const lessonModal = new bootstrap.Modal(document.getElementById('lessonModal'));
        let scheduleData = {}, currentModalSlotId = '', currentModalWeekType = '';

        // --- TomSelect Tanımlamaları ---
        const teacherFilter = new TomSelect('#teacher-filter', { options: teachersForSelect, plugins: ['remove_button'], onChange: renderSchedule });
        const newStudentSelect = new TomSelect('#new-student-select', {
            options: allStudentsData.map(s => ({ value: s.id, text: `${s.adi} ${s.soyadi}`, subtext: `${s.city_name || ''} / ${s.district_name || ''}` })),
            placeholder: 'Eklemek için öğrenci adını yazın...', multiple: true, plugins: ['remove_button'],
            render: { option: (data, esc) => `<div><span class="text-dark">${esc(data.text)}</span><small class="text-muted d-block">${esc(data.subtext)}</small></div>` }
        });

        // --- Event Listeners ---
        document.getElementById('day-selector').addEventListener('click', e => toggleButton(e, renderSchedule));
        document.getElementById('week-selector').addEventListener('click', e => toggleWeekButton(e, renderSchedule));
        document.getElementById('save-slot-btn').addEventListener('click', saveSlotData);
        document.getElementById('clear-slot-btn').addEventListener('click', clearSlotData);
        displayArea.addEventListener('click', e => { if (e.target.closest('.lesson-slot')) openLessonModal(e.target.closest('.lesson-slot')); });
        displayArea.addEventListener('mouseover', e => {
            const popoverEl = e.target.closest('.lesson-slot');
            if (popoverEl && !bootstrap.Popover.getInstance(popoverEl)) {
                new bootstrap.Popover(popoverEl, { trigger: 'hover', html: true, placement: 'top', container: 'body', content: () => generatePopoverContent(popoverEl), delay: { "show": 200, "hide": 100 } }).show();
            }
        });

        // --- Fonksiyonlar ---
        const toggleButton = (e, cb) => { const btn = e.target.closest('button'); if (!btn) return; btn.classList.toggle('active'); btn.classList.toggle('btn-secondary'); btn.classList.toggle('btn-outline-secondary'); if (cb) cb(); };
        const toggleWeekButton = (e, cb) => { const btn = e.target.closest('button'); if (!btn) return; btn.classList.toggle('active'); const color = btn.dataset.color; btn.classList.toggle(`btn-${color}`); btn.classList.toggle(`btn-outline-${color}`); if (cb) cb(); };

        async function fetchScheduleData() {
            const selectedTeacherIds = teacherFilter.getValue().length > 0 ? teacherFilter.getValue() : teachersForSelect.map(t => t.value);
            const selectedDayNumbers = [...document.querySelectorAll('#day-selector button.active')].map(b => b.dataset.day);
            if (selectedTeacherIds.length === 0 || selectedDayNumbers.length === 0) { scheduleData = {}; return; }
            const params = new URLSearchParams();
            selectedTeacherIds.forEach(id => params.append('teachers[]', id));
            selectedDayNumbers.forEach(day => params.append('days[]', day));
            displayArea.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Veriler yükleniyor...</p></div>`;
            try {
                const response = await fetch(`<?= site_url('admin/fixed-schedule/get-data') ?>?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('Network response');
                const data = await response.json();
                scheduleData = data.schedule;
            } catch (error) {
                console.error("Fetch Error:", error); scheduleData = {};
                displayArea.innerHTML = `<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu.</div>`;
            }
        }

        async function renderSchedule() {
            await fetchScheduleData();
            const selectedDays = [...document.querySelectorAll('#day-selector button.active')];
            const selectedWeeks = [...document.querySelectorAll('#week-selector button.active')];
            displayArea.innerHTML = '';
            if (selectedDays.length === 0 || selectedWeeks.length === 0 || allTeachersData.length === 0) {
                let msg = 'Lütfen en az bir gün ve bir hafta seçin.';
                if (allTeachersData.length === 0) msg = 'Sistemde kayıtlı öğretmen bulunmamaktadır.';
                // DÜZELTME 3: Öğretmen filtresi boşken hata mesajı göstermemek için bu kontrolü kaldırıyoruz.
                // if (teacherFilter.getValue().length === 0 && teachersForSelect.length > 0) msg = 'Lütfen en az bir öğretmen seçin.';
                displayArea.innerHTML = `<div class="alert alert-info text-center">${msg}</div>`;
                return;
            }
            selectedDays.forEach(dayBtn => selectedWeeks.forEach(weekBtn => displayArea.insertAdjacentHTML('beforeend', createDayTable(dayBtn.dataset.day, weekBtn.dataset.weekId))));
        }
        
        function createDayTable(dayNum, weekType) {
            const days = { 1: 'Pazartesi', 2: 'Salı', 3: 'Çarşamba', 4: 'Perşembe', 5: 'Cuma', 6: 'Cumartesi', 7: 'Pazar' };
            const hours = Array.from({length: (<?= config('Ots')->scheduleEndHour ?? 18 ?> - <?= config('Ots')->scheduleStartHour ?? 10 ?>)}, (_, i) => <?= config('Ots')->scheduleStartHour ?? 10 ?> + i);
            const selectedTeacherIds = teacherFilter.getValue().length > 0 ? teacherFilter.getValue() : teachersForSelect.map(t => t.value);
            const filteredTeachers = allTeachersData.filter(t => selectedTeacherIds.includes(String(t.id)));
            return `<div class="card mb-4 shadow-sm"><div class="card-body p-0"><div class=""><table class="table schedule-table table-hover mb-0">
                <thead class="thead-week-${weekType}">
                    <tr><th colspan="${hours.length + 1}">${days[dayNum]} - ${weekType} Haftası</th></tr>
                    <tr><th class="teacher-cell">Öğretmenler</th>${hours.map(h => `<th>${h}:00</th>`).join('')}</tr>
                </thead><tbody>${filteredTeachers.map(teacher => `
                    <tr>
                        <td class="teacher-cell">
                            <div class="d-flex align-items-center">
                                <img src="<?= base_url() ?>${teacher.profile_photo || 'assets/images/user.jpg'}" 
                                    class="rounded-circle me-3" 
                                    width="40" 
                                    height="40" 
                                    alt="${teacher.first_name}" 
                                    style="object-fit:cover;">

                                <div class="flex-grow-1 text-start"> <!-- Burayı değiştirdim -->
                                    <div class="fw-bold text-nowrap text-start">${teacher.first_name} ${teacher.last_name}</div> <!-- text-start eklendi -->
                                    <small class="text-muted d-block text-truncate text-start" style="max-width: 150px;">${teacher.branch || ''}</small> <!-- text-start eklendi -->
                                </div>
                            </div>
                        </td>
                        ${hours.map(hour => {
                            const slotId = `${teacher.id}-${dayNum}-${hour}`;
                            const slotData = scheduleData[slotId] || {};
                            const dots = Object.keys(slotData).map(wt => `<div class="week-dot week-${wt}-dot" title="${wt} Haftası"></div>`).join('');
                            const studentList = slotData[weekType] || [];
                            const studentElements = studentList.map(s => `<span class="student-name student-week-${weekType}">${s.name}</span>`).join('');
                            return `<td class="lesson-slot" data-slot-id="${slotId}" data-week-type="${weekType}">${studentElements}<button class="add-lesson-icon" title="Ders ekle/düzenle"><i class="bi bi-pencil-fill"></i></button><div class="week-dots">${dots}</div></td>`;
                        }).join('')}
                    </tr>`).join('')}
                </tbody></table></div></div></div>`;
        }

        // DÜZELTME 2: Popover içeriğini oluşturan fonksiyon güncellendi
        function generatePopoverContent(element) {
            const slotId = element.dataset.slotId;
            const data = scheduleData[slotId] || {};
            let content = '', hasLesson = false;
            const weekColors = { 'A': 'danger', 'B': 'primary', 'C': 'success', 'D': 'warning' };
            ['A', 'B', 'C', 'D'].forEach(weekType => {
                const studentList = data[weekType] || [];
                if (studentList.length > 0) {
                    hasLesson = true;
                    content += `<h6><span class="badge bg-${weekColors[weekType]}">${weekType} Haftası</span></h6><ul class="student-popover-list">`;
                    studentList.forEach(student => {
                        content += `<li>
                            <img src="<?= base_url() ?>${student.photo}" class="rounded-circle me-2" width="24" height="24" style="object-fit:cover;" alt="${student.name}">
                            <div class="student-info">
                                <strong>${student.name}</strong>
                                <div class="text-muted" style="font-size: 0.8em;"><i class="bi bi-geo-alt-fill"></i> ${student.city || ''} / ${student.district || ''}</div>
                            </div>
                        </li>`;
                    });
                    content += `</ul><hr class="my-1">`;
                }
            });
            if (!hasLesson) return 'Bu saat diliminde planlanmış ders bulunmuyor.';
            return content.slice(0, -18);
        }

        function openLessonModal(slotElement) {
            currentModalSlotId = slotElement.dataset.slotId;
            currentModalWeekType = slotElement.dataset.weekType;
            const [teacherId, dayNum, hour] = currentModalSlotId.split('-');
            const teacher = allTeachersData.find(t => t.id == teacherId);
            const days = { 1: 'Pzt', 2: 'Sal', 3: 'Çrş', 4: 'Per', 5: 'Cum', 6: 'Cmt', 7: 'Paz' };
            const weekColors = { 'A': 'danger', 'B': 'primary', 'C': 'success', 'D': 'warning' };
            document.getElementById('modal-slot-info').innerHTML = `<div class="alert alert-light border"><strong>${teacher.first_name} ${teacher.last_name}</strong> - ${days[dayNum]}, ${hour}:00<span class="badge bg-${weekColors[currentModalWeekType]} float-end">${currentModalWeekType} Haftası</span></div>`;
            const studentsInSlot = (scheduleData[currentModalSlotId] || {})[currentModalWeekType] || [];
            document.getElementById('students-list').innerHTML = studentsInSlot.length > 0 ? studentsInSlot.map(createStudentItemHTML).join('') : '<p class="text-muted">Bu hafta için kayıtlı öğrenci yok.</p>';
            newStudentSelect.clear();
            lessonModal.show();
        }

        function createStudentItemHTML(student) {
            return `<div class="student-item" data-student-id="${student.student_id}"><img src="<?= base_url() ?>${student.photo}" alt="${student.name}" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;"><span class="flex-grow-1">${student.name}</span><button type="button" class="btn btn-outline-danger btn-sm remove-student-btn"><i class="bi bi-trash"></i></button></div>`;
        }
        
        document.getElementById('students-list').addEventListener('click', e => { if (e.target.closest('.remove-student-btn')) e.target.closest('.student-item').remove(); });

        async function saveSlotData() {
            const newStudentIds = newStudentSelect.getValue();
            let currentStudentIds = [...document.querySelectorAll('#students-list .student-item')].map(item => item.dataset.studentId);
            newStudentIds.forEach(id => { if (!currentStudentIds.includes(id)) currentStudentIds.push(id); });
            const formData = new FormData();
            formData.append('slotId', currentModalSlotId);
            formData.append('weekType', currentModalWeekType);
            currentStudentIds.forEach(id => formData.append('studentIds[]', id));
            formData.append(csrfName, csrfHash);
            try {
                const response = await fetch('<?= site_url('admin/fixed-schedule/save-slot') ?>', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('Server response');
                const result = await response.json();
                if (result.success) { await renderSchedule(); lessonModal.hide(); } 
                else { alert(result.message || 'Hata.'); }
            } catch (error) { console.error("Save error:", error); alert('Sunucu hatası.'); }
        }
        
        async function clearSlotData() {
            if (!confirm('Bu saate ait tüm dersleri temizlemek istediğinizden emin misiniz?')) return;
            document.getElementById('students-list').innerHTML = '';
            newStudentSelect.clear();
            await saveSlotData();
        }

        // --- Başlangıç ---
        document.querySelectorAll('#week-selector button').forEach(button => {
            const color = (button.className.match(/btn-(?:outline-)?(\w+)/) || [])[2];
            button.dataset.color = color;
        });
        
        renderSchedule();
    });
</script>
<?= $this->endSection() ?>