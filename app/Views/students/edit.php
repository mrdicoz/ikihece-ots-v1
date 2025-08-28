<?= $this->extend('layouts/app') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-pencil-square"></i> <?= esc($title) ?></h1>
    </div>

        <?= $this->include('layouts/partials/_session_messages') ?>


    <form action="<?= site_url('students/' . $student['id']) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        
        <div class="card shadow">
            <div class="card-header p-0">
                <ul class="nav nav-tabs nav-fill" id="studentTab" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link text-success active" data-bs-toggle="tab" data-bs-target="#temel" type="button" role="tab">Temel Bilgiler</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#veli" type="button" role="tab">Veli Bilgileri</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#adres" type="button" role="tab">Adres & Servis</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#egitim" type="button" role="tab">Eğitim</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#rapor" type="button" role="tab">Raporlar</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link text-success" data-bs-toggle="tab" data-bs-target="#haklar" type="button" role="tab">Ders Hakları</button></li>
                </ul>
            </div>
            <div class="card-body">
                <?= service('validation')->listErrors('list') ?>
                <div class="tab-content p-3" id="studentTabContent">
                    <div class="tab-pane fade show active" id="temel" role="tabpanel"><?= $this->include('students/_form_temel') ?></div>
                    <div class="tab-pane fade" id="veli" role="tabpanel"><?= $this->include('students/_form_veli') ?></div>
                    <div class="tab-pane fade" id="adres" role="tabpanel"><?= $this->include('students/_form_adres') ?></div>
                    <div class="tab-pane fade" id="egitim" role="tabpanel"><?= $this->include('students/_form_egitim') ?></div>
                    <div class="tab-pane fade" id="rapor" role="tabpanel"><?= $this->include('students/_form_rapor') ?></div>
                    <div class="tab-pane fade" id="haklar" role="tabpanel"><?= $this->include('students/_form_haklar') ?></div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="<?= site_url('students/' . $student['id']) ?>" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="cropperModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Fotoğrafı Kırp</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><img id="cropper-image" src="" style="max-width: 100%;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" id="crop-button">Kırp ve Kaydet</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Cropper
    initCropper('profile_photo_input', 'cropperModal', 'cropper-image', 'crop-button', 'cropped_image_data');

    // TomSelect
    new TomSelect('#cinsiyet', { create: false, placeholder: 'Cinsiyet seçin...' });
    new TomSelect('#servis', { create: false, placeholder: 'Servis durumu seçin...' });
    new TomSelect('#mesafe', { create: false, placeholder: 'Mesafe seçin...' });
    new TomSelect('#orgun_egitim', { create: false, placeholder: 'Seçim yapın...' });
    new TomSelect('#egitim_sekli', { create: false, placeholder: 'Eğitim şekli seçin...' });
    new TomSelect('#city_id', { create: false, placeholder: 'İl seçin...' });
    const districtSelect = new TomSelect('#district_id', { create: false, placeholder: 'Önce il seçin...' });
    const egitimProgramiSelect = new TomSelect('#egitim_programi', {
        plugins: ['remove_button'],
        placeholder: 'Eğitim programı seçin...'
    });

    <?php if(isset($student['egitim_programi']) && !empty($student['egitim_programi'])): ?>
        egitimProgramiSelect.setValue(<?= json_encode($student['egitim_programi']) ?>);
    <?php endif; ?>

    // Dinamik İlçe Yükleme
    const citySelect = document.getElementById('city_id');
    async function fetchDistricts(cityId, selectedDistrictId = null) {
        if (!cityId) {
            districtSelect.clear();
            districtSelect.clearOptions();
            districtSelect.disable();
            return;
        }
        try {
            const response = await fetch('<?= site_url('profile/get-districts/') ?>' + cityId);
            const districts = await response.json();
            districtSelect.enable();
            districtSelect.clear();
            districtSelect.clearOptions();
            districtSelect.addOptions(districts.map(d => ({ value: d.id, text: d.name })));
            if(selectedDistrictId) {
                districtSelect.setValue(selectedDistrictId);
            }
        } catch (error) {
            console.error('İlçeler yüklenemedi:', error);
        }
    }
    
    citySelect.addEventListener('change', () => fetchDistricts(citySelect.value));

    const initialCityId = '<?= old('city_id', $student['city_id'] ?? '') ?>';
    const initialDistrictId = '<?= old('district_id', $student['district_id'] ?? '') ?>';
    if(initialCityId) {
        fetchDistricts(initialCityId, initialDistrictId);
    }
});
</script>
<?= $this->endSection() ?>