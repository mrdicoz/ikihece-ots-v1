<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-person-badge"></i> <?= esc($title) ?></h1>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <p class="text-muted">Öğretmen ataması yapmak için listeden bir sekreter seçin ve "Atamaları Yönet" butonuna tıklayın.</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sekreter Adı Soyadı</th>
                            <th>Kullanıcı Adı</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($secretaries as $secretary): ?>
                        <tr>
                            <td class="align-middle"><?= esc($secretary->first_name . ' ' . $secretary->last_name) ?></td>
                            <td class="align-middle">@<?= esc($secretary->username) ?></td>
                            <td class="text-end">
                                <button class="btn btn-success btn-sm manage-assignments-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignmentModal"
                                        data-secretary-id="<?= $secretary->id ?>"
                                        data-secretary-name="<?= esc($secretary->first_name . ' ' . $secretary->last_name) ?>">
                                    <i class="bi bi-pencil-square"></i> Atamaları Yönet
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= route_to('admin.assignments.save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="secretary_id" id="modal_secretary_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignmentModalLabel">Atama Yönetimi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Sorumlu Sekreter:</strong> <span id="modal_secretary_name"></span></p>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="teacher_ids" class="form-label mb-0">Bu sekretere atanacak öğretmenleri seçin:</label>
                            <div>
                                <button type="button" class="btn btn-outline-success btn-sm" id="select-all-teachers">
                                    <i class="bi bi-check-all"></i> Tümünü Seç
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="deselect-all-teachers">
                                    <i class="bi bi-x-lg"></i> Seçimi Temizle
                                </button>
                            </div>
                        </div>
                        <select id="teacher_ids" name="teacher_ids[]" multiple>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher->id ?>"><?= esc($teacher->first_name . ' ' . $teacher->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // TomSelect'i başlatmak için bir değişken oluşturuyoruz, daha sonra erişebilmek için.
    let tomSelectInstance;

    const assignmentModal = document.getElementById('assignmentModal');

        // --- YENİ EKLENEN KISIM BAŞLANGICI ---
    const selectAllBtn = document.getElementById('select-all-teachers');
    const deselectAllBtn = document.getElementById('deselect-all-teachers');
    // PHP'den tüm öğretmenlerin ID'lerini bir JavaScript dizisine aktarıyoruz.
    const allTeacherIds = <?= json_encode(array_column($teachers, 'id')) ?>;

    selectAllBtn.addEventListener('click', function() {
        if (tomSelectInstance) {
            tomSelectInstance.setValue(allTeacherIds);
        }
    });

    deselectAllBtn.addEventListener('click', function() {
        if (tomSelectInstance) {
            tomSelectInstance.clear();
        }
    });
    // --- YENİ EKLENEN KISIM SONU ---
    
    // Modal açıldığında çalışacak kod
    assignmentModal.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const secretaryId = button.dataset.secretaryId;
        const secretaryName = button.dataset.secretaryName;
        
        // Modal içindeki gizli inputa ve isme sekreterin bilgilerini yaz
        document.getElementById('modal_secretary_id').value = secretaryId;
        document.getElementById('modal_secretary_name').textContent = secretaryName;
        
        // TomSelect'i başlat veya temizle
        if (tomSelectInstance) {
            tomSelectInstance.clear();
            tomSelectInstance.clearOptions();
        } else {
             tomSelectInstance = new TomSelect('#teacher_ids', {
                plugins: ['remove_button'],
                placeholder: 'Öğretmen arayın veya seçin...'
            });
        }
        
        // Öğretmen seçeneklerini manuel olarak ekle (TomSelect'in doğru çalışması için)
        <?php foreach ($teachers as $teacher): ?>
            tomSelectInstance.addOption({value: '<?= $teacher->id ?>', text: '<?= esc($teacher->first_name . ' ' . $teacher->last_name) ?>'});
        <?php endforeach; ?>
        tomSelectInstance.refreshOptions(false);


        // AJAX ile bu sekretere atanmış mevcut öğretmenleri getir
        try {
            const response = await fetch('<?= site_url('admin/assignments/get-assigned/') ?>' + secretaryId);
            if (!response.ok) throw new Error('Sunucu yanıtı başarısız.');
            
            const assignedIds = await response.json();
            
            // TomSelect'te bu öğretmenleri seçili olarak işaretle
            tomSelectInstance.setValue(assignedIds);

        } catch (error) {
            console.error('Atanmış öğretmenler getirilirken hata:', error);
            alert('Atanmış öğretmen bilgileri yüklenirken bir hata oluştu.');
        }
    });
});
</script>
<?= $this->endSection() ?>
