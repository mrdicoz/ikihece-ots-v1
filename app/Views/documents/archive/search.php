<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-search"></i> <?= esc($title) ?>
        </h1>
        <a href="<?= base_url('documents/archive') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Arama Kriterleri</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="date_start" class="form-control" value="<?= old('date_start') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="date_end" class="form-control" value="<?= old('date_end') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Tümü</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= old('category_id') == $cat->id ? 'selected' : '' ?>>
                                    <?= esc($cat->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Evrak Numarası</label>
                        <input type="text" name="document_number" class="form-control" placeholder="Örn: SRGM-2025-1001" value="<?= old('document_number') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Konu</label>
                        <input type="text" name="subject" class="form-control" placeholder="Konu içinde ara..." value="<?= old('subject') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">İçerik Arama</label>
                        <input type="text" name="content_search" class="form-control" placeholder="Form içeriğinde ara..." value="<?= old('content_search') ?>">
                        <small class="text-muted">Belgede doldurulmuş herhangi bir bilgiyi arayabilirsiniz</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-search"></i> Ara
                </button>
            </form>
        </div>
    </div>

    <?php if ($searched === true): ?>
    <div class="card shadow">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Arama Sonuçları (<?= count($results) ?> adet)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($results)): ?>
                <p class="text-muted text-center">Aramanıza uygun belge bulunamadı.</p>
            <?php else: ?>
                <table class="table table-hover" id="searchResultsTable">
                    <thead>
                        <tr>
                            <th>Evrak No</th>
                            <th>Konu</th>
                            <th>Kategori</th>
                            <th>Tarih</th>
                            <th width="150">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $doc): ?>
                            <tr>
                                <td><strong><?= esc($doc->document_number ?? '-') ?></strong></td>
                                <td><?= esc($doc->subject) ?></td>
                                <td><span class="badge bg-info"><?= esc($doc->category_name) ?></span></td>
                                <td><?= date('d.m.Y H:i', strtotime($doc->created_at)) ?></td>
                                <td>
                                    <a href="<?= base_url("documents/view-pdf/{$doc->id}") ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= base_url("documents/download-pdf/{$doc->id}") ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<?php if ($searched === true): ?>
<script>
    $(document).ready(function() {
        $('#searchResultsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
            }
        });
    });
</script>
<?php endif; ?>
<?= $this->endSection() ?>