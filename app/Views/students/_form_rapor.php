<h5 class="mb-3">Rapor Bilgileri</h5>
<div class="row g-3">
    <div class="col-12"><label class="form-label">RAM</label><textarea name="ram" class="form-control" rows="2"><?= old('ram', $student['ram'] ?? '') ?></textarea></div>
    <div class="col-md-4"><label class="form-label">RAM Başlangıç</label><input type="date" name="ram_baslagic" class="form-control" value="<?= old('ram_baslagic', $student['ram_baslagic'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">RAM Bitiş</label><input type="date" name="ram_bitis" class="form-control" value="<?= old('ram_bitis', $student['ram_bitis'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">RAM Raporu Yükle</label><input type="file" name="ram_raporu" class="form-control" accept="application/pdf">
        <?php if (!empty($student['ram_raporu'])): ?><small class="form-text text-muted">Mevcut: <a href="<?= site_url('students/view-ram-report/' . $student['id']) ?>" target="_blank">Görüntüle</a></small><?php endif; ?>
    </div>
    <hr class="my-4">
    <div class="col-md-12"><label class="form-label">Hastane Adı</label><input type="text" name="hastane_adi" class="form-control" value="<?= old('hastane_adi', $student['hastane_adi'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Hastane Rapor Başlangıç</label><input type="date" name="hastane_raporu_baslama_tarihi" class="form-control" value="<?= old('hastane_raporu_baslama_tarihi', $student['hastane_raporu_baslama_tarihi'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Hastane Rapor Bitiş</label><input type="date" name="hastane_raporu_bitis_tarihi" class="form-control" value="<?= old('hastane_raporu_bitis_tarihi', $student['hastane_raporu_bitis_tarihi'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Hastane Randevu Tarihi</label><input type="date" name="hastane_randevu_tarihi" class="form-control" value="<?= old('hastane_randevu_tarihi', $student['hastane_randevu_tarihi'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Hastane Randevu Saati</label><input type="time" name="hastane_randevu_saati" class="form-control" value="<?= old('hastane_randevu_saati', $student['hastane_randevu_saati'] ?? '') ?>"></div>
    <div class="col-12"><label class="form-label">Hastane Açıklama</label><textarea name="hastane_aciklama" class="form-control" rows="2"><?= old('hastane_aciklama', $student['hastane_aciklama'] ?? '') ?></textarea></div>
</div>