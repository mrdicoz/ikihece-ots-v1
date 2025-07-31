<?= csrf_field() ?>
<div class="mb-3">
    <label for="title" class="form-label">Duyuru Başlığı</label>
    <input type="text" class="form-control" id="title" name="title" value="<?= old('title', $announcement['title'] ?? '') ?>" required>
</div>

<div class="mb-3">
    <label for="body" class="form-label">Duyuru İçeriği</label>
    <textarea class="form-control" id="body" name="body" rows="5" required><?= old('body', $announcement['body'] ?? '') ?></textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="target_group" class="form-label">Hedef Kitle</label>
        <select class="form-select" id="target_group" name="target_group" required>
            <option value="all" <?= (old('target_group', $announcement['target_group'] ?? '') === 'all') ? 'selected' : '' ?>>Tüm Kullanıcılar</option>
            <option value="veli" <?= (old('target_group', $announcement['target_group'] ?? '') === 'veli') ? 'selected' : '' ?>>Sadece Veliler</option>
            <option value="ogretmen" <?= (old('target_group', $announcement['target_group'] ?? '') === 'ogretmen') ? 'selected' : '' ?>>Sadece Öğretmenler</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Durum</label>
        <select class="form-select" id="status" name="status" required>
            <option value="draft" <?= (old('status', $announcement['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Taslak</option>
            <option value="published" <?= (old('status', $announcement['status'] ?? '') === 'published') ? 'selected' : '' ?>>Yayınlandı</option>
        </select>
        <small class="form-text text-muted">"Yayınlandı" seçilirse ilgili kişilere anında bildirim gider.</small>
    </div>
</div>