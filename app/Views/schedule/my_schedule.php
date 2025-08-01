<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="row mb-4 align-items-center">
        <div class="col-12 col-lg-auto mb-3 mb-lg-0">
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-week"></i> Ders Programım</h1>
        </div>
        <div class="col-12 col-lg-auto ms-lg-auto d-lg-none">
            <div class="btn-group w-100" role="group">
                <button id="prevDayBtn" class="btn btn-outline-success"><i class="bi bi-chevron-left"></i> Önceki Gün</button>
                <button id="todayBtn" class="btn btn-success">Bugün</button>
                <button id="nextDayBtn" class="btn btn-outline-success">Sonraki Gün <i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
    
    <?php 
        // Gerekli PHP değişkenlerini hazırlayalım
        $dayNames = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    ?>

    <div class="card shadow d-none d-lg-block">
        <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th style="width: 100px;">Saat</th>
                            <?php foreach ($weekDates as $day): ?>
                                <th class="<?= ($day->format('Y-m-d') == date('Y-m-d')) ? 'table-success' : '' ?>">
                                    <div><?= esc($dayNames[$day->format('w')]) ?></div>
                                    <small class="fw-normal"><?= $day->format('d.m.Y') ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($hour = 8; $hour <= 18; $hour++): 
                            $time = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                        ?>
                        <tr>
                            <td class="fw-bold text-center align-middle bg-light"><?= $time ?></td>
                            <?php foreach ($weekDates as $day): 
                                $lessonsInSlot = $scheduleData[$day->format('Y-m-d')][$time] ?? [];
                            ?>
                            <td class="p-1 align-top" style="min-width: 150px;">
                                <?php if (!empty($lessonsInSlot)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($lessonsInSlot as $lesson): ?>
                                            <a href="<?= site_url('students/' . $lesson['student_id']) ?>" class="list-group-item list-group-item-action p-2" data-bs-toggle="tooltip" title="<?= esc(date('H:i', strtotime($lesson['start_time']))) ?> - <?= esc(date('H:i', strtotime($lesson['end_time']))) ?>">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= base_url($lesson['profile_image'] ?? 'assets/images/user.jpg') ?>" class="rounded-circle me-2" alt="<?= esc($lesson['adi']) ?>" style="width:28px; height:28px; object-fit:cover;">
                                                    <small class="text-truncate"><?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?></small>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="d-lg-none">
        <div class="card shadow">
            <div id="mobileDateHeader" class="card-header text-center fw-bold fs-5">
                </div>
            <ul id="mobileLessonList" class="list-group list-group-flush">
                </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --------------------------------------------------------------------
    // BÖLÜM 1: PHP'den Gelen Haftalık Veriyi JavaScript'e Aktarma
    // --------------------------------------------------------------------
    const weeklyScheduleData = <?= json_encode($scheduleData) ?>;
    const weekDates = <?= json_encode(array_map(fn($d) => $d->format('Y-m-d'), $weekDates)) ?>;
    const dayNames = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    const todayString = new Date().toISOString().split('T')[0];

    let currentDayIndex = weekDates.indexOf('<?= $currentDate->format('Y-m-d') ?>');
    if (currentDayIndex === -1) {
        currentDayIndex = 0;
    }

    // --------------------------------------------------------------------
    // BÖLÜM 2: Gerekli HTML Elementlerini Seçme
    // --------------------------------------------------------------------
    const prevDayBtn = document.getElementById('prevDayBtn');
    const nextDayBtn = document.getElementById('nextDayBtn');
    const todayBtn = document.getElementById('todayBtn');
    const mobileDateHeader = document.getElementById('mobileDateHeader');
    const mobileLessonList = document.getElementById('mobileLessonList');

    // --------------------------------------------------------------------
    // BÖLÜM 3: Mobil Arayüzü Dinamik Olarak Çizen Ana Fonksiyon
    // --------------------------------------------------------------------
    function renderDay(index) {
        const dateString = weekDates[index];
        const dateObj = new Date(dateString + 'T00:00:00');
        const dayLessons = weeklyScheduleData[dateString] || {};

        // Başlığı güncelle
        const isToday = (dateString === todayString);
        mobileDateHeader.innerHTML = `
            ${dayNames[dateObj.getDay()]}
            <small class="d-block fw-normal fs-6">${dateObj.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric' })}</small>
        `;
        mobileDateHeader.className = `card-header text-center fw-bold fs-5 ${isToday ? 'bg-success text-white' : 'bg-light'}`;
        
        // Ders listesini temizle ve yeniden oluştur
        mobileLessonList.innerHTML = '';
        let hasLessons = false;
        let listHtml = '';

        for (let hour = 8; hour <= 18; hour++) {
            const time = String(hour).padStart(2, '0') + ':00';
            const lessonsInHour = dayLessons[time] || [];
            
            let hourHtml = '';
            if (lessonsInHour.length > 0) {
                hasLessons = true;
                lessonsInHour.forEach(lesson => {
                    const startTime = lesson.start_time.substring(0, 5);
                    const endTime = lesson.end_time.substring(0, 5);
                    const studentUrl = `<?= site_url('students') ?>/${lesson.student_id}`;
                    const profileImage = `<?= base_url() ?>${lesson.profile_image || 'assets/images/user.jpg'}`;

                    hourHtml += `
                        <a href="${studentUrl}" class="text-decoration-none text-body">
                            <div class="d-flex align-items-center p-2 rounded mb-1 bg-light">
                                <img src="${profileImage}" class="rounded-circle me-3" alt="${lesson.adi}" style="width:36px; height:36px; object-fit:cover;">
                                <div class="text-truncate">
                                    <span class="fw-bold">${lesson.adi} ${lesson.soyadi}</span>
                                    <small class="d-block text-muted">${startTime} - ${endTime}</small>
                                </div>
                            </div>
                        </a>`;
                });
            }

            listHtml += `
                <li class="list-group-item d-flex p-2">
                    <div class="fw-bold text-center text-muted me-3 border-end pe-3" style="width: 60px;">${time}</div>
                    <div class="flex-grow-1">${hourHtml}</div>
                </li>`;
        }

        if (!hasLessons) {
            mobileLessonList.innerHTML = `<li class="list-group-item p-4 text-center text-muted">Bu gün için planlanmış ders bulunmamaktadır.</li>`;
        } else {
            mobileLessonList.innerHTML = listHtml;
        }

        // Butonların durumunu güncelle
        prevDayBtn.disabled = (index === 0);
        nextDayBtn.disabled = (index === weekDates.length - 1);
    }

    // --------------------------------------------------------------------
    // BÖLÜM 4: Butonlara Tıklama Olaylarını Atama
    // --------------------------------------------------------------------
    prevDayBtn.addEventListener('click', () => {
        if (currentDayIndex > 0) {
            currentDayIndex--;
            renderDay(currentDayIndex);
        }
    });

    nextDayBtn.addEventListener('click', () => {
        if (currentDayIndex < weekDates.length - 1) {
            currentDayIndex++;
            renderDay(currentDayIndex);
        }
    });

    todayBtn.addEventListener('click', () => {
        let todayIdx = weekDates.indexOf(todayString);
        if(todayIdx !== -1) {
            currentDayIndex = todayIdx;
            renderDay(currentDayIndex);
        } else {
            // Eğer bugün o haftada değilse, haftanın ilk gününü gösterelim
            currentDayIndex = 0;
            renderDay(currentDayIndex);
        }
    });

    // Sayfa ilk yüklendiğinde mevcut günü çiz
    renderDay(currentDayIndex);
});
</script>
<?= $this->endSection() ?>