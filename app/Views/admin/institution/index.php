<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>

<style>
    .card-header {
        background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    }
    .preview-box {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .preview-box img {
        max-width: 200px;
        max-height: 150px;
        object-fit: contain;
    }
</style>

<div class="container-fluid py-4">
    
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i><?= esc($title) ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= route_to('admin.institution.save') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2"><i class="bi bi-file-earmark-text me-2"></i>Antet ve Görsel Ayarları</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="kurum_adi" class="form-label">Kurum Adı (Antet Üst Başlık)</label>
                                <input type="text" class="form-control" id="kurum_adi" name="kurum_adi" value="<?= esc($institution->kurum_adi ?? '') ?>" placeholder="Örn: İkihece Özel Eğitim ve Rehabilitasyon Merkezi">
                                <small class="text-muted">Evraklarda üst kısımda gösterilecek</small>
                            </div>
                            <div class="col-md-6">
                                <label for="kurum_kisa_adi" class="form-label">Kısa İsim</label>
                                <input type="text" class="form-control" id="kurum_kisa_adi" name="kurum_kisa_adi" value="<?= esc($institution->kurum_kisa_adi ?? '') ?>" placeholder="Örn: ikihece">
                                <small class="text-muted">Kurum adının kısaltılmış hali</small>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="kurum_logo_path" class="form-label">Logo (Opsiyonel)</label>
                                <input type="file" class="form-control" id="kurum_logo_path" name="kurum_logo_path" accept="image/*">
                                <small class="text-muted">PNG veya JPG formatında. En iyi görünüm için şeffaf PNG.</small>
                                <div class="preview-box mt-3">
                                    <?php if (!empty($institution->kurum_logo_path)): ?>
                                        <img src="<?= base_url($institution->kurum_logo_path) ?>" alt="Mevcut Logo">
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-image fs-1 d-block"></i>Logo Önizleme</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="kurum_qr_kod_path" class="form-label">MEB Karekod</label>
                                <input type="file" class="form-control" id="kurum_qr_kod_path" name="kurum_qr_kod_path" accept="image/*">
                                <small class="text-muted">PNG veya SVG formatında</small>
                                <div class="preview-box mt-3">
                                    <?php if (!empty($institution->kurum_qr_kod_path)): ?>
                                        <img src="<?= base_url($institution->kurum_qr_kod_path) ?>" alt="Mevcut QR Kod">
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-qr-code fs-1 d-block"></i>Karekod Önizleme</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2"><i class="bi bi-info-circle me-2"></i>Footer & İletişim Bilgileri</h6>
                            </div>
                            <div class="col-12">
                                <label for="adresi" class="form-label">Adres</label>
                                <textarea class="form-control" id="adresi" name="adresi" rows="2" placeholder="Bahçelievler Mah. 106 Sokak No:3/1..."><?= esc($institution->adresi ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="city_id" class="form-label">İl</label>
                                <select class="form-select" id="city_id" name="city_id">
                                    <option value="">İl Seçiniz...</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city->id ?>" <?= (($institution->city_id ?? '') == $city->id) ? 'selected' : '' ?>><?= esc($city->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="district_id" class="form-label">İlçe</label>
                                <select class="form-select" id="district_id" name="district_id">
                                    <option value="">Önce İl Seçiniz...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="sabit_telefon" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="sabit_telefon" name="sabit_telefon" value="<?= esc($institution->sabit_telefon ?? '') ?>" placeholder="(0264) 278 04 05">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="epostasi" class="form-label">E-posta</label>
                                <input type="email" class="form-control" id="epostasi" name="epostasi" value="<?= esc($institution->epostasi ?? '') ?>" placeholder="serdivan@meb.gov.tr">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="web_sayfasi" class="form-label">Web Sitesi (Opsiyonel)</label>
                                <input type="url" class="form-control" id="web_sayfasi" name="web_sayfasi" value="<?= esc($institution->web_sayfasi ?? '') ?>" placeholder="www.serdivan.meb.gov.tr">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2"><i class="bi bi-person-badge me-2"></i>Yetkili Bilgileri</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="kurum_muduru_user_id" class="form-label">Kurum Müdürü (Sistem Kullanıcısı)</label>
                                <select class="form-select" id="kurum_muduru_user_id" name="kurum_muduru_user_id">
                                    <option value="">Sistemden bir kullanıcı seçin...</option>
                                    <?php foreach($users as $user): ?>
                                        <option value="<?= $user->id ?>" <?= (($institution->kurum_muduru_user_id ?? '') == $user->id) ? 'selected' : '' ?>><?= esc($user->username) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">İmza alanında gösterilecek yetkili (sistem kullanıcısı ise)</small>
                            </div>
                            <div class="col-md-6">
                                <label for="kurum_muduru_adi" class="form-label">Kurum Müdürü Adı (Manuel)</label>
                                <input type="text" class="form-control" id="kurum_muduru_adi" name="kurum_muduru_adi" value="<?= esc($institution->kurum_muduru_adi ?? '') ?>">
                                <small class="text-muted">Kullanıcı olarak seçilmediyse veya farklı bir isim gösterilecekse buraya yazın.</small>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="kurucu_mudur_adi" class="form-label">Kurucu Müdür Adı</label>
                                <input type="text" class="form-control" id="kurucu_mudur_adi" name="kurucu_mudur_adi" value="<?= esc($institution->kurucu_mudur_adi ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2"><i class="bi bi-123 me-2"></i>Evrak Numarası Ayarları</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="evrak_prefix" class="form-label">Evrak Numarası Öneki</label>
                                <input type="text" class="form-control" id="evrak_prefix" name="evrak_prefix" value="<?= esc($institution->evrak_prefix ?? 'SRGM-2025-') ?>" placeholder="Örn: SRGM-2025-">
                                <small class="text-muted">Evrak numaralarının başına gelecek sabit metin. Örnek: <code>SRGM-2025-</code></small>
                            </div>
                            <div class="col-md-6">
                                <label for="evrak_baslangic_no" class="form-label">Başlangıç Numarası</label>
                                <input type="number" class="form-control" id="evrak_baslangic_no" name="evrak_baslangic_no" value="<?= esc($institution->evrak_baslangic_no ?? 1000) ?>" placeholder="1000">
                                <small class="text-muted">Evrak numaralandırması bu sayıdan başlayacak.</small>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2"><i class="bi bi-building-gear me-2"></i>Diğer Kurum Bilgileri</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="kurum_kodu" class="form-label">Kurum Kodu</label>
                                <input type="text" class="form-control" id="kurum_kodu" name="kurum_kodu" value="<?= esc($institution->kurum_kodu ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="acilis_tarihi" class="form-label">Açılış Tarihi</label>
                                <input type="date" class="form-control" id="acilis_tarihi" name="acilis_tarihi" value="<?= esc($institution->acilis_tarihi ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="google_konum" class="form-label">Google Konum Linki</label>
                                <input type="text" class="form-control" id="google_konum" name="google_konum" value="<?= esc($institution->google_konum ?? '') ?>" placeholder="Google Haritalar veya WhatsApp konum linki...">
                                <small class="text-muted">Linkin içinde "@" ile başlayan koordinat bilgisi olmalıdır.</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2"><i class="bi bi-briefcase me-2"></i>Kurucu/Finans Bilgileri</h6>
                            </div>
                             <div class="col-md-4">
                                <label for="kurucu_tipi" class="form-label">Kurucu Tipi</label>
                                <input type="text" class="form-control" id="kurucu_tipi" name="kurucu_tipi" value="<?= esc($institution->kurucu_tipi ?? '') ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="sirket_adi" class="form-label">Şirket Adı</label>
                                <input type="text" class="form-control" id="sirket_adi" name="sirket_adi" value="<?= esc($institution->sirket_adi ?? '') ?>">
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="kurucu_temsilci_tckn" class="form-label">Kurucu Temsilci TCKN</label>
                                <input type="text" class="form-control" id="kurucu_temsilci_tckn" name="kurucu_temsilci_tckn" value="<?= esc($institution->kurucu_temsilci_tckn ?? '') ?>">
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="kurum_vergi_dairesi" class="form-label">Kurum Vergi Dairesi</label>
                                <input type="text" class="form-control" id="kurum_vergi_dairesi" name="kurum_vergi_dairesi" value="<?= esc($institution->kurum_vergi_dairesi ?? '') ?>">
                            </div>
                            <div class="col-md-4 mt-3">
                                <label for="kurum_vergi_no" class="form-label">Kurum Vergi No</label>
                                <input type="text" class="form-control" id="kurum_vergi_no" name="kurum_vergi_no" value="<?= esc($institution->kurum_vergi_no ?? '') ?>">
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="reset" class="btn btn-secondary me-2"><i class="bi bi-arrow-clockwise me-2"></i>Sıfırla</button>
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-2"></i>Bilgileri Kaydet</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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