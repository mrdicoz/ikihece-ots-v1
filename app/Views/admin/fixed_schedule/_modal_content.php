
<h5 class="mb-3">Mevcut Sabit Dersler</h5>
<ul class="list-group mb-4" id="fixed-lesson-list">
    <?php if (empty($fixed_lessons)): ?>
        <li class="list-group-item text-muted">Bu gün için kayıtlı sabit ders bulunmamaktadır.</li>
    <?php else: ?>
        <?php foreach ($fixed_lessons as $lesson): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong><?= esc(date('H:i', strtotime($lesson['start_time']))) ?></strong> - <?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?></span>
                <button class="btn btn-danger btn-sm delete-fixed-lesson-btn" data-id="<?= $lesson['id'] ?>"><i class="bi bi-trash"></i></button>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>

<hr>

<h5 class="mb-3">Yeni Sabit Ders Ekle</h5>
<form id="add-fixed-lesson-form">
    <?= csrf_field() ?>
    <input type="hidden" name="teacher_id" value="<?= esc($teacher_id) ?>">
    <input type="hidden" name="day_of_week" value="<?= esc($day_of_week) ?>">
    <input type="hidden" name="day_name" value="<?= esc($day_name) ?>">

    <div class="mb-3">
        <label for="start_time" class="form-label">Ders Saati</label>
        <select name="start_time" class="form-select" required>
            <option value="">Saat Seçin...</option>
            <?php for ($h = 8; $h <= 18; $h++): $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; ?>
                <option value="<?= $time ?>"><?= $time ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="student_id" class="form-label">Öğrenci</label>
        <select name="student_id" id="student-select" required>
            <option value="">Öğrenci Seçin...</option>
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>"><?= esc($student['adi'] . ' ' . $student['soyadi']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        </div>
    <div class="text-end">
        <button type="submit" class="btn btn-success">Ekle</button>
    </div>
</form>