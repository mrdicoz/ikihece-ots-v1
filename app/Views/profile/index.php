<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            
            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h4><i class="bi bi-person-fill-gear"></i> Profil Bilgileri</h4>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('profile/update') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <div class="text-center mb-4">
                            <img src="<?= base_url($profile->profile_photo ?? '/assets/images/user.jpg') . '?v=' . time() ?>" alt="Profil Fotoğrafı" id="profile-pic-preview" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <br>
                            <label for="profile_photo_input" class="btn btn-outline-primary btn-sm mt-2">
                                <i class="bi bi-camera"></i> Fotoğrafı Değiştir
                            </label>
                            <input type="file" id="profile_photo_input" class="d-none" accept="image/*">
                            <input type="hidden" name="cropped_image_data" id="cropped_image_data">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Ad</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?= esc($profile->first_name) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Soyad</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?= esc($profile->last_name) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Telefon Numarası</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= esc($profile->phone_number) ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city_id" class="form-label">İl</label>
                                <select class="form-select" id="city_id" name="city_id">
                                    <option value="">İl Seçiniz...</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city->id ?>" <?= ($profile->city_id == $city->id) ? 'selected' : '' ?>><?= esc($city->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="district_id" class="form-label">İlçe</label>
                                <select class="form-select" id="district_id" name="district_id">
                                    <option value="">Önce İl Seçiniz...</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Adres</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= esc($profile->address) ?></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Bilgileri Güncelle
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel">Fotoğrafı Kırp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div>
                    <img id="cropper-image" src="" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="crop-button">Kırp ve Kaydet</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ... (Cropper.js ile ilgili kodlar aynı kalacak) ...
    const cropperModalEl = document.getElementById('cropperModal');
    const image = document.getElementById('cropper-image');
    const fileInput = document.getElementById('profile_photo_input');
    const cropButton = document.getElementById('crop-button');
    const profilePicPreview = document.getElementById('profile-pic-preview');
    const hiddenInput = document.getElementById('cropped_image_data');
    
    let cropper;
    const cropperModal = new bootstrap.Modal(cropperModalEl);

    fileInput.addEventListener('change', function (e) {
        let files = e.target.files;
        if (files && files.length > 0) {
            let reader = new FileReader();
            reader.onload = function (e) {
                image.src = e.target.result;
                cropperModal.show();
            };
            reader.readAsDataURL(files[0]);
        }
    });

    cropperModalEl.addEventListener('shown.bs.modal', function () {
        cropper = new Cropper(image, {
            aspectRatio: 1 / 1,
            viewMode: 1,
            dragMode: 'move',
            background: false,
            autoCropArea: 0.8
        });
    });

    cropperModalEl.addEventListener('hidden.bs.modal', function () {
        cropper.destroy();
        cropper = null;
        fileInput.value = '';
    });

    cropButton.addEventListener('click', function () {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
        const croppedImageData = canvas.toDataURL('image/jpeg', 0.9);
        profilePicPreview.src = croppedImageData;
        hiddenInput.value = croppedImageData;
        cropperModal.hide();
    });


    // --- İL - İLÇE DİNAMİK YÜKLEME (GÜNCELLENMİŞ KOD) ---
    const citySelect = document.getElementById('city_id');
    const districtSelect = document.getElementById('district_id');

    async function fetchDistricts(cityId, selectedDistrictId = null) {
        // İl seçilmemişse ilçe listesini temizle ve çık
        if (!cityId) {
            districtSelect.innerHTML = '<option value="">Önce İl Seçiniz...</option>';
            return;
        }

        try {
            const response = await fetch('<?= site_url('profile/get-districts/') ?>' + cityId);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
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

    // İl seçimi değiştiğinde ilçeleri getir
    citySelect.addEventListener('change', function () {
        fetchDistricts(this.value);
    });

    // Sayfa ilk yüklendiğinde, eğer bir il zaten seçiliyse, o ilin ilçelerini getir
    const initialCityId = citySelect.value;
    const initialDistrictId = '<?= esc($profile->district_id) ?>';
    if (initialCityId) {
        fetchDistricts(initialCityId, initialDistrictId);
    }
});
</script>
<?= $this->endSection() ?>