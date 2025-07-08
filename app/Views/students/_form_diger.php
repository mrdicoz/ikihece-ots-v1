<h5 class="mb-3">Muhasebe Bilgileri</h5>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Sözleşme No</label><input type="text" name="sozlesme_no" class="form-control" value="<?= old('sozlesme_no', $student['sozlesme_no'] ?? '') ?>"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Sözleşme Tutarı</label><input type="text" name="sozlesme_tutari" class="form-control" value="<?= old('sozlesme_tutari', $student['sozlesme_tutari'] ?? '') ?>"></div>
</div>
<hr>
<h5 class="mt-4 mb-3">Acil Durum Kişisi</h5>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Adı Soyadı</label><input type="text" name="acil_durum_aranacak_kisi_1_adi" class="form-control" value="<?= old('acil_durum_aranacak_kisi_1_adi', $student['acil_durum_aranacak_kisi_1_adi'] ?? '') ?>"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Telefon</label><input type="tel" name="acil_durum_aranacak_kisi_1_telefon" class="form-control" value="<?= old('acil_durum_aranacak_kisi_1_telefon', $student['acil_durum_aranacak_kisi_1_telefon'] ?? '') ?>"></div>
</div>