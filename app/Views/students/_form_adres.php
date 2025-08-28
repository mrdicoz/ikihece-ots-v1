<h5 class="mb-3">Adres ve Ulaşım Bilgileri</h5>
<div class="row g-3">
    <div class="col-md-6">
        <label for="city_id" class="form-label">İl</label>
        <select id="city_id" name="city_id" class="form-select" >
            <option value="">İl Seçiniz...</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?= esc($city->id) ?>" <?= (old('city_id', $student['city_id'] ?? '') == $city->id) ? 'selected' : '' ?>><?= esc($city->name) ?> </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="district_id" class="form-label">İlçe</label>
        <select id="district_id" name="district_id" class="form-select" >
            <option value="">Önce İl Seçiniz...</option>
        </select>
    </div>
    <div class="col-12"><label class="form-label">Açık Adres</label><textarea name="adres_detayi" class="form-control" rows="2"><?= old('adres_detayi', $student['adres_detayi'] ?? '') ?></textarea></div>
    <div class="col-12"><label class="form-label">Google Konum Linki</label><input type="url" name="google_konum" class="form-control" value="<?= old('google_konum', $student['google_konum'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Servis Kullanımı</label><select id="servis" name="servis" class="form-select" >
        <option value="var" <?= (old('servis', $student['servis'] ?? '') === 'var') ? 'selected' : '' ?>>Var</option>
        <option value="yok" <?= (old('servis', $student['servis'] ?? '') === 'yok') ? 'selected' : '' ?>>Yok</option>
        <option value="arasira" <?= (old('servis', $student['servis'] ?? '') === 'arasira') ? 'selected' : '' ?>>Arasıra</option>
    </select></div>
    <div class="col-md-6"><label class="form-label">Mesafe</label><select id="mesafe" name="mesafe" class="form-select" >
        <option value="civar" <?= (old('mesafe', $student['mesafe'] ?? '') === 'civar') ? 'selected' : '' ?>>Civar</option>
        <option value="yakın" <?= (old('mesafe', $student['mesafe'] ?? '') === 'yakın') ? 'selected' : '' ?>>Yakın</option>
        <option value="uzak" <?= (old('mesafe', $student['mesafe'] ?? '') === 'uzak') ? 'selected' : '' ?>>Uzak</option>
    </select></div>
</div>