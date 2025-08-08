<?php if (empty($lessons)): ?>
    <div class="text-center text-muted small py-3">
        <i class="bi bi-calendar-x"></i><br>
        Sabit ders yok
    </div>
<?php else: ?>
    <ul class="list-unstyled mb-0 small">
        <?php foreach ($lessons as $lesson): ?>
            <li class="text-truncate" title="<?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?>">
                <i class="bi bi-person-check-fill text-success"></i>
                <?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>