<h5 class="mb-3">Tanımlanan Ders Hakları</h5>
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Normal Bireysel Hak</label><input type="number" name="normal_bireysel_hak" class="form-control" value="<?= old('normal_bireysel_hak', $student['normal_bireysel_hak'] ?? '0') ?>"></div>
    <div class="col-md-6"><label class="form-label">Normal Grup Hak</label><input type="number" name="normal_grup_hak" class="form-control" value="<?= old('normal_grup_hak', $student['normal_grup_hak'] ?? '0') ?>"></div>
    <div class="col-md-6"><label class="form-label">Telafi Bireysel Hak</label><input type="number" name="telafi_bireysel_hak" class="form-control" value="<?= old('telafi_bireysel_hak', $student['telafi_bireysel_hak'] ?? '0') ?>"></div>
    <div class="col-md-6"><label class="form-label">Telafi Grup Hak</label><input type="number" name="telafi_grup_hak" class="form-control" value="<?= old('telafi_grup_hak', $student['telafi_grup_hak'] ?? '0') ?>"></div>
</div>