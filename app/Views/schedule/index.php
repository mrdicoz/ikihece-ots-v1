<?= $this->extend('layouts/app') ?>
<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <h1 class="h3 mb-3"><i class="bi bi-calendar-week"></i> Ders Programı</h1>
    <div class="card shadow"><div class="card-body"><div id="calendar"></div></div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var lessonDates = []; 

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'tr',
        buttonText: {
            today: 'Bugün', // Butonu Türkçeleştirme
        },
        // Sağ üstteki butonları kaldırma
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '' 
        },
        events: '<?= route_to('schedule.get_month_lessons') ?>', 

        // Rakamları hücre ortasında göstermek için bu fonksiyonu kullanıyoruz
        eventContent: function(info) {
            return {
                html: '<div class="day-lesson-count">' + info.event.title + '</div>'
            };
        },
        
        // GÜN İÇERİĞİNE GİRMEK İÇİN
        dateClick: function(info) {
            window.location.href = '<?= site_url('schedule/daily/') ?>' + info.dateStr;
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault(); 
            if (info.event.url) {
                window.location.href = info.event.url;
            }
        },

        // SAAT DİLİMİ HATASINI DÜZELTME VE RENKLENDİRME
        dayCellDidMount: function(info) {
            var cellDate = info.date;
            var today = new Date();
            today.setHours(0,0,0,0);

            if (cellDate < today) {
                // DÜZELTME: toISOString() yerine manuel formatlama yapıyoruz
                let year = cellDate.getFullYear();
                let month = String(cellDate.getMonth() + 1).padStart(2, '0');
                let day = String(cellDate.getDate()).padStart(2, '0');
                let dateString = `${year}-${month}-${day}`;

                if (lessonDates.includes(dateString)) {
                    info.el.style.backgroundColor = 'rgba(25, 135, 84, 0.15)'; // Yeşil
                } else {
                    info.el.style.backgroundColor = 'rgba(220, 53, 69, 0.09)'; // Kırmızı
                }
            }
        },

        datesSet: function() {
            fetchLessonDates();
        }
    });

    function fetchLessonDates() {
        $.get('<?= route_to('schedule.get_lesson_dates') ?>', function(dates) {
            lessonDates = dates;
            calendar.render();
        });
    }

    fetchLessonDates();
});
</script>
<?= $this->endSection() ?>