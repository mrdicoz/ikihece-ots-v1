<div class="row">
    <div class="col-md-6 border-end">
        <h5 class="mb-3">Anne Bilgileri</h5>
        <div class="mb-3"><label class="form-label">Adı Soyadı</label><input type="text" name="veli_anne_adi_soyadi" class="form-control" value="<?= old('veli_anne_adi_soyadi', $student['veli_anne_adi_soyadi'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Telefon</label><input type="tel" name="veli_anne_telefon" class="form-control" value="<?= old('veli_anne_telefon', $student['veli_anne_telefon'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="veli_anne_eposta" class="form-control" value="<?= old('veli_anne_eposta', $student['veli_anne_eposta'] ?? '') ?>"></div>
    </div>
    <div class="col-md-6">
        <h5 class="mb-3">Baba Bilgileri</h5>
        <div class="mb-3"><label class="form-label">Adı Soyadı</label><input type="text" name="veli_baba_adi_soyadi" class="form-control" value="<?= old('veli_baba_adi_soyadi', $student['veli_baba_adi_soyadi'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Telefon</label><input type="tel" name="veli_baba_telefon" class="form-control" value="<?= old('veli_baba_telefon', $student['veli_baba_telefon'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="veli_baba_eposta" class="form-control" value="<?= old('veli_baba_eposta', $student['veli_baba_eposta'] ?? '') ?>"></div>
    </div>
</div>