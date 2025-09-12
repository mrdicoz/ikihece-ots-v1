<?php if (empty($lessons)): ?>
    <div class="text-muted small p-1">-</div>
<?php else: ?>
    <?php foreach ($lessons as $lesson): ?>
        <?php
            // Controller'dan gelen $conflicts dizisini kontrol et
            $studentId = $lesson['student_id'];
            $isConflict = isset($conflicts[$studentId]);
            
            // Çakışma varsa badge rengini kırmızı yap, yoksa yeşil kalsın
            $badgeClass = $isConflict ? 'bg-danger' : 'bg-success';
            
            // Çakışma varsa popover için gerekli HTML niteliklerini oluştur
            $popoverAttributes = '';
            if ($isConflict) {
                $popoverAttributes = 'data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="hover" title="Çakışma Bilgisi" data-bs-content="' . esc($conflicts[$studentId]) . '"';
            }
        ?>
        <span class="badge <?= $badgeClass ?> student-badge text-truncate" <?= $popoverAttributes ?>>
            <?= esc($lesson['adi'] . ' ' . $lesson['soyadi']) ?>
        </span>
    <?php endforeach; ?>
<?php endif; ?>