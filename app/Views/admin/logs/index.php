<?= $this->extend('layouts/app') // Ana layout dosyanızın adı ?>

<?= $this->section('main') // Ana layout'taki content bölümü ?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-journal-text"></i> <?= esc($pageTitle) ?></h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 15%;">Kullanıcı</th>
                            <th style="width: 15%;">Olay</th>
                            <th>Açıklama</th>
                            <th style="width: 10%;">IP Adresi</th>
                            <th style="width: 15%;">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td>
                                        <?php if ($log['username']): ?>
                                            <a href="#"><?= esc($log['username']) ?></a>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sistem</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info p-2"><?= esc($log['event']) ?></span></td>
                                    <td><?= esc($log['message']) ?></td>
                                    <td><?= esc($log['ip_address']) ?></td>
                                    <td><?= \CodeIgniter\I18n\Time::parse($log['created_at'])->toLocalizedString('dd MMM yyyy, HH:mm') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Henüz görüntülenecek log kaydı bulunmamaktadır.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <?php if ($pager) : ?>
<?= $pager->links() ?>            <?php endif ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>