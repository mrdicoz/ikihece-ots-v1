<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container mt-4">
    <h4><i class="bi bi-building-gear"></i> <?= esc($title) ?></h4>
    <hr>
    
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= route_to('admin.institution.save') ?>" method="post">
                <?= csrf_field() ?>
                
                <h5 class="text-success">KURUM BİLGİLERİ</h5>
                <hr>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="kurum_kodu" class="form-label">Kurum Kodu</label>
                        <input type="text" class="form-control" id="kurum_kodu" name="kurum_kodu" value="<?= esc($institution->kurum_kodu ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="kurum_adi" class="form-label">Kurum Adı</label>
                        <input type="text" class="form-control" id="kurum_adi" name="kurum_adi" value="<?= esc($institution->kurum_adi ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="kurum_kisa_adi" class="form-label">Kurum Kısa Adı</label>
                        <input type="text" class="form-control" id="kurum_kisa_adi" name="kurum_kisa_adi" value="<?= esc($institution->kurum_kisa_adi ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="city_id" class="form-label">İl</label>
                        <select class="form-select" id="city_id" name="city_id">
                            <option value="">İl Seçiniz...</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city->id ?>" <?= (($institution->city_id ?? '') == $city->id) ? 'selected' : '' ?>><?= esc($city->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="district_id" class="form-label">İlçe</label>
                        <select class="form-select" id="district_id" name="district_id">
                            <option value="">Önce İl Seçiniz...</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label for="adresi" class="form-label">Adresi</label>
                        <textarea class="form-control" id="adresi" name="adresi" rows="2"><?= esc($institution->adresi ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="google_konum" class="form-label">Google Konum Linki</label>
                        <input type="text" class="form-control" id="google_konum" name="google_konum" value="<?= esc($institution->google_konum ?? '') ?>" placeholder="Google Haritalar veya WhatsApp konum linkini buraya yapıştırın...">
                        <div class="form-text">
                            Kopyaladığınız linkin içinde "@" ile başlayan koordinat bilgisi olmalıdır.
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="acilis_tarihi" class="form-label">Açılış Tarihi</label>
                        <input type="date" class="form-control" id="acilis_tarihi" name="acilis_tarihi" value="<?= esc($institution->acilis_tarihi ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="web_sayfasi" class="form-label">Web Sayfası</label>
                        <input type="url" class="form-control" id="web_sayfasi" name="web_sayfasi" value="<?= esc($institution->web_sayfasi ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="epostasi" class="form-label">E-postası</label>
                        <input type="email" class="form-control" id="epostasi" name="epostasi" value="<?= esc($institution->epostasi ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="sabit_telefon" class="form-label">Sabit Telefon</label>
                        <input type="tel" class="form-control" id="sabit_telefon" name="sabit_telefon" value="<?= esc($institution->sabit_telefon ?? '') ?>">
                    </div>
                </div>

                <h5 class="mt-4 text-success">KURUCU-TEMSİLCİ BİLGİLERİ</h5>
                <hr>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="kurucu_tipi" class="form-label">Kurucu Tipi</label>
                        <input type="text" class="form-control" id="kurucu_tipi" name="kurucu_tipi" value="<?= esc($institution->kurucu_tipi ?? '') ?>">
                    </div>
                    <div class="col-md-8">
                        <label for="sirket_adi" class="form-label">Şirket Adı</label>
                        <input type="text" class="form-control" id="sirket_adi" name="sirket_adi" value="<?= esc($institution->sirket_adi ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="kurucu_temsilci_tckn" class="form-label">Kurucu Temsilci TCKN</label>
                        <input type="text" class="form-control" id="kurucu_temsilci_tckn" name="kurucu_temsilci_tckn" value="<?= esc($institution->kurucu_temsilci_tckn ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="kurum_vergi_dairesi" class="form-label">Kurum Vergi Dairesi</label>
                        <input type="text" class="form-control" id="kurum_vergi_dairesi" name="kurum_vergi_dairesi" value="<?= esc($institution->kurum_vergi_dairesi ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="kurum_vergi_no" class="form-label">Kurum Vergi No</label>
                        <input type="text" class="form-control" id="kurum_vergi_no" name="kurum_vergi_no" value="<?= esc($institution->kurum_vergi_no ?? '') ?>">
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Bilgileri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const citySelect = document.getElementById('city_id');
    const districtSelect = document.getElementById('district_id');

    async function fetchDistricts(cityId, selectedDistrictId = null) {
        if (!cityId) {
            districtSelect.innerHTML = '<option value="">Önce İl Seçiniz...</option>';
            return;
        }

        try {
            // Profil sayfasındaki rotayı kullanabiliriz, işlevleri aynı.
            const response = await fetch('<?= site_url('profile/get-districts/') ?>' + cityId);
            if (!response.ok) throw new Error('Network response was not ok');
            const districts = await response.json();

            let options = '<option value="">İlçe Seçiniz...</option>';
            districts.forEach(district => {
                const selected = (district.id == selectedDistrictId) ? 'selected' : '';
                options += `<option value="${district.id}" ${selected}>${district.name}</option>`;
            });
            districtSelect.innerHTML = options;
        } catch (error) {
            console.error('İlçeler yüklenirken hata oluştu:', error);
            districtSelect.innerHTML = '<option value="">İlçeler yüklenemedi!</option>';
        }
    }

    citySelect.addEventListener('change', function () {
        fetchDistricts(this.value);
    });

    // Sayfa yüklendiğinde seçili olan ilin ilçelerini getir.
    const initialCityId = citySelect.value;
    const initialDistrictId = '<?= esc($institution->district_id ?? '') ?>';
    if (initialCityId) {
        fetchDistricts(initialCityId, initialDistrictId);
    }
});
</script>
<?= $this->endSection() ?>