<h5 class="mb-3">Adres Bilgileri</h5>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">İl</label><input type="text" name="adres_il" class="form-control" value="<?= old('adres_il', $student['adres_il'] ?? '') ?>"></div>
    <div class="col-md-4 mb-3"><label class="form-label">İlçe</label><input type="text" name="adres_ilce" class="form-control" value="<?= old('adres_ilce', $student['adres_ilce'] ?? '') ?>"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Mahalle</label><input type="text" name="adres_mahalle" class="form-control" value="<?= old('adres_mahalle', $student['adres_mahalle'] ?? '') ?>"></div>
    <div class="col-12 mb-3"><label class="form-label">Açık Adres</label><textarea name="adres_detay" class="form-control"><?= old('adres_detay', $student['adres_detay'] ?? '') ?></textarea></div>
    <div class="col-12 mb-3"><label class="form-label">Google Konum Linki</label><input type="text" name="google_konum" class="form-control" value="<?= old('google_konum', $student['google_konum'] ?? '') ?>"></div>
</div>