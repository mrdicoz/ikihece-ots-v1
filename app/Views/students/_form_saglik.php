<h5 class="mb-3">Sağlık Bilgileri</h5>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Kan Grubu</label><input type="text" name="kan_grubu" class="form-control" value="<?= old('kan_grubu', $student['kan_grubu'] ?? '') ?>"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Engel Durumu</label><input type="text" name="engel_durumu" class="form-control" value="<?= old('engel_durumu', $student['engel_durumu'] ?? '') ?>"></div>
    <div class="col-12 mb-3"><label class="form-label">Alerjiler</label><textarea name="alerjiler" class="form-control"><?= old('alerjiler', $student['alerjiler'] ?? '') ?></textarea></div>
    <div class="col-12 mb-3"><label class="form-label">Sürekli Kullandığı İlaçlar</label><textarea name="ilaclar" class="form-control"><?= old('ilaclar', $student['ilaclar'] ?? '') ?></textarea></div>
</div>
<hr>
<h5 class="mt-4 mb-3">RAM Rapor Bilgileri</h5>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="ram_raporu" class="form-label">RAM Raporu (PDF)</label>
        <input class="form-control" type="file" id="ram_raporu" name="ram_raporu" accept="application/pdf">
        <?php if (!empty($student['ram_raporu'])): ?>
            <small class="form-text text-muted">Mevcut rapor: <a href="<?= site_url('students/view-ram-report/' . $student['id']) ?>" target="_blank">Görüntüle</a></small>
        <?php endif; ?>
    </div>
    <div class="col-md-3 mb-3">
        <label for="ram_baslangic_tarihi" class="form-label">Rapor Başlangıç</label>
        <input type="date" class="form-control" id="ram_baslangic_tarihi" name="ram_baslangic_tarihi" value="<?= old('ram_baslangic_tarihi', $student['ram_baslangic_tarihi'] ?? '') ?>">
    </div>
    <div class="col-md-3 mb-3">
        <label for="ram_bitis_tarihi" class="form-label">Rapor Bitiş</label>
        <input type="date" class="form-control" id="ram_bitis_tarihi" name="ram_bitis_tarihi" value="<?= old('ram_bitis_tarihi', $student['ram_bitis_tarihi'] ?? '') ?>">
    </div>
</div>