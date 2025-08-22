<div class="row">
    <div class="col-md-8">
        <h5 class="mb-3">Temel Bilgiler</h5>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Adı</label><input type="text" name="adi" class="form-control" value="<?= old('adi', $student['adi'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Soyadı</label><input type="text" name="soyadi" class="form-control" value="<?= old('soyadi', $student['soyadi'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label">TC Kimlik No</label><input type="text" name="tckn" class="form-control" value="<?= old('tckn', $student['tckn'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Doğum Tarihi</label><input type="date" name="dogum_tarihi" class="form-control" value="<?= old('dogum_tarihi', $student['dogum_tarihi'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Cinsiyet</label><select name="cinsiyet" id="cinsiyet" class="form-select">
                <option value="erkek" <?= (old('cinsiyet', $student['cinsiyet'] ?? '') === 'erkek') ? 'selected' : '' ?>>Erkek</option>
                <option value="kadin" <?= (old('cinsiyet', $student['cinsiyet'] ?? '') === 'kadin') ? 'selected' : '' ?>>Kadın</option>
            </select></div>
            <div class="col-md-6"><label class="form-label">İletişim Telefonu</label><input type="tel" name="iletisim" class="form-control" value="<?= old('iletisim', $student['iletisim'] ?? '') ?>"></div>
        </div>
    </div>
    <div class="col-md-4 text-center">
        <h5 class="mb-3">Profil Fotoğrafı</h5>
        <img src="<?= base_url($student['profile_image'] ?? 'assets/images/user.jpg') ?>" alt="Profil Fotoğrafı" id="profile-pic-preview" class="img-thumbnail rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
        <div>
            <label for="profile_photo_input" class="btn btn-outline-success w-100"><i class="bi bi-camera"></i> Fotoğraf Değiştir</label>
            <input type="file" id="profile_photo_input" class="d-none" accept="image/*">
            <input type="hidden" name="cropped_image_data" id="cropped_image_data">
        </div> 
    </div>
</div>