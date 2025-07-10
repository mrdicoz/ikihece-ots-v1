<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-journal-richtext"></i> Sistem Hareketleri</h3>
    </div>

    <?php if (!empty($logs) && count($logs) > 0): ?>
        <div class="timeline">
            <?php foreach ($logs as $log): ?>
                <?php
                    // Olay türüne göre ikon ve renk belirliyoruz
                    $icon = 'bi-info-circle-fill';
                    $color = 'secondary';
                    $title = 'Sistem Bilgisi';

                    if (str_contains($log['event'], 'created')) {
                        $icon = 'bi-plus-circle-fill';
                        $color = 'success';
                        $title = 'Yeni Kayıt Oluşturuldu';
                    } elseif (str_contains($log['event'], 'updated')) {
                        $icon = 'bi-pencil-fill';
                        $color = 'info';
                        $title = 'Kayıt Güncellendi';
                    } elseif (str_contains($log['event'], 'deleted')) {
                        $icon = 'bi-trash-fill';
                        $color = 'danger';
                        $title = 'Kayıt Silindi';
                    }
                ?>
                <div class="timeline-item">
                    <div class="timeline-icon bg-<?= $color ?>">
                        <i class="bi <?= $icon ?>"></i>
                    </div>

                    <div class="timeline-content">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                <h6 class="m-0 fw-bold text-<?= $color ?>"><?= esc($title) ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> <?= \CodeIgniter\I18n\Time::parse($log['created_at'])->toLocalizedString('dd MMM yyyy, HH:mm') ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><?= esc($log['message']) ?></p>
                            </div>
                            <div class="card-footer bg-light-subtle text-muted small py-1 px-3 d-flex justify-content-between">
                                <span>
                                    <i class="bi bi-person-fill"></i>
                                    <?= esc($log['username'] ?? 'Sistem') ?>
                                </span>
                                <span>
                                    <i class="bi bi-pc-display-horizontal"></i>
                                    <?= esc($log['ip_address']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <?= $pager ? $pager->links('default', 'bootstrap_centered') : '' ?>
        </div>

    <?php else: ?>
        <div class="alert alert-info text-center">Henüz görüntülenecek log kaydı bulunmamaktadır.</div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>