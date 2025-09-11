<?php if (empty($lessons)): ?>
    <div class="text-muted small p-1">-</div>
<?php else: ?>
    <?php foreach ($lessons as $lesson): ?>
        <span class="badge bg-success student-badge text-truncate" title="<?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?>">
            <?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?>
        </span>
    <?php endforeach; ?>
<?php endif; ?>