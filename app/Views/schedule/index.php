<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= $title ?? 'Ders Programı' ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-week"></i> Ders Programı</h1>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="lessonModal" tabindex="-1" aria-labelledby="lessonModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="lessonModalLabel">Ders Detayları</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="lessonModalBody">
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-success" id="saveLessonBtn">Kaydet</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'tr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: '<?= route_to('schedule.get_month_lessons') ?>', 
        
        // GÜNE TIKLANDIĞINDA ARTIK SADECE YÖNLENDİRME YAPIYOR
        dateClick: function(info) {
            // Yeni URL formatımız: /schedule/daily/YYYY-MM-DD
            window.location.href = '<?= site_url('schedule/daily/') ?>' + info.dateStr;
        },
        
        eventClick: function(info) {
            // Bu da artık o günün sayfasına yönlendirebilir
            window.location.href = '<?= site_url('schedule/daily/') ?>' + info.event.startStr.substring(0, 10);
        }
    });

    calendar.render();
});
</script>
<?= $this->endSection() ?>