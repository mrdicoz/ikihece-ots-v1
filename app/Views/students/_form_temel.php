<div class="row">
    <div class="col-md-8">
        <h5 class="mb-3">Temel Bilgiler</h5>

        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Adı</label><input type="text" name="adi" class="form-control" value="<?= old('adi', $student['adi'] ?? '') ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Soyadı</label><input type="text" name="soyadi" class="form-control" value="<?= old('soyadi', $student['soyadi'] ?? '') ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">TC Kimlik No</label><input type="text" name="tc_kimlik_no" class="form-control" value="<?= old('tc_kimlik_no', $student['tc_kimlik_no'] ?? '') ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Doğum Tarihi</label><input type="date" name="dogum_tarihi" class="form-control" value="<?= old('dogum_tarihi', $student['dogum_tarihi'] ?? '') ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Cinsiyet</label><select name="cinsiyet" class="form-control">
                <option value="" <?= (old('cinsiyet', $student['cinsiyet'] ?? '') === '') ? 'selected' : '' ?>>Seçiniz</option>
                <option value="erkek" <?= (old('cinsiyet', $student['cinsiyet'] ?? '') === 'erkek') ? 'selected' : '' ?>>Erkek</option>
                <option value="kadin" <?= (old('cinsiyet', $student['cinsiyet'] ?? '') === 'kadin') ? 'selected' : '' ?>>Kadın</option>
            </select></div>
            <div class="col-md-6 mb-3"><label class="form-label">Servis Durumu</label><select name="servis_durumu" class="form-control">
                <option value="" <?= (old('servis_durumu', $student['servis_durumu'] ?? '') === '') ? 'selected' : '' ?>>Seçiniz</option>
                <option value="var" <?= (old('servis_durumu', $student['servis_durumu'] ?? '') === 'var') ? 'selected' : '' ?>>Var</option>
                <option value="yok" <?= (old('servis_durumu', $student['servis_durumu'] ?? '') === 'yok') ? 'selected' : '' ?>>Yok</option>
                <option value="arasira" <?= (old('servis_durumu', $student['servis_durumu'] ?? '') === 'arasira') ? 'selected' : '' ?>>Arasıra</option>
            </select></div>


        </div>
    </div>
    <div class="col-md-4">
        <h5 class="mb-3 text-center">Profil Fotoğrafı</h5>
        <div class=" d-flex justify-content-center">
        <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>" alt="Profil Fotoğrafı" id="profile-pic-preview" class="img-thumbnail rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
        </div>
        <div>
            <label for="profile_photo_input" class="btn btn-outline-success  w-100"><i class="bi bi-camera"></i> Fotoğraf Değiştir</label>
            <input type="file" id="profile_photo_input" class="d-none" accept="image/*">
            <input type="hidden" name="cropped_image_data" id="cropped_image_data">
        </div> 
    </div>
</div>
<hr>
<h5 class="mt-4 mb-3">Acil Durum Kişisi</h5>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Adı Soyadı</label><input type="text" name="acil_durum_aranacak_kisi_1_adi" class="form-control" value="<?= old('acil_durum_aranacak_kisi_1_adi', $student['acil_durum_aranacak_kisi_1_adi'] ?? '') ?>"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Yakınlık</label><input type="text" name="acil_durum_aranacak_kisi_1_yakinlik" class="form-control" value="<?= old('acil_durum_aranacak_kisi_1_yakinlik', $student['acil_durum_aranacak_kisi_1_yakinlik'] ?? '') ?>"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Telefon</label><input type="text" name="acil_durum_aranacak_kisi_1_telefon" class="form-control" value="<?= old('acil_durum_aranacak_kisi_1_telefon', $student['acil_durum_aranacak_kisi_1_telefon'] ?? '') ?>"></div>
</div>