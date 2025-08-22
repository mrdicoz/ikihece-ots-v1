<h5 class="mb-3">Veli Bilgileri</h5>
<div class="row">
    <div class="col-md-6 border-end">
        <h6 class="text-success">Anne Bilgileri</h6>
        <div class="mb-3"><label class="form-label">Adı Soyadı</label><input type="text" name="veli_anne" class="form-control" value="<?= old('veli_anne', $student['veli_anne'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Telefon</label><input type="tel" name="veli_anne_telefon" class="form-control" value="<?= old('veli_anne_telefon', $student['veli_anne_telefon'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">TCKN</label><input type="text" name="veli_anne_tc" class="form-control" value="<?= old('veli_anne_tc', $student['veli_anne_tc'] ?? '') ?>"></div>
    </div>
    <div class="col-md-6">
        <h6 class="text-success">Baba Bilgileri</h6>
        <div class="mb-3"><label class="form-label">Adı Soyadı</label><input type="text" name="veli_baba" class="form-control" value="<?= old('veli_baba', $student['veli_baba'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Telefon</label><input type="tel" name="veli_baba_telefon" class="form-control" value="<?= old('veli_baba_telefon', $student['veli_baba_telefon'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">TCKN</label><input type="text" name="veli_baba_tc" class="form-control" value="<?= old('veli_baba_tc', $student['veli_baba_tc'] ?? '') ?>"></div>
    </div>
</div>