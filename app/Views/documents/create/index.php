<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .table tbody tr { cursor: pointer; }
    .table tbody tr.selected { background-color: #d1e7dd !important; }
    .template-panel { border-left: 3px solid #198754; }
    .no-selection { display: flex; align-items: center; justify-content: center; min-height: 300px; color: #6c757d; }
</style>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-files"></i> <?= esc($title) ?></h1>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="bi bi-folder-fill me-2"></i>Kategoriler</h5></div>
                <div class="card-body">
                    <table class="table table-hover" id="categoriesTable">
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr data-id="<?= $category->id ?>" data-name="<?= esc($category->name) ?>">
                                    <td>
                                        <i class="bi bi-folder me-2"></i>
                                        <?= esc($category->name) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm template-panel">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Şablonlar <span id="categoryNameBadge" class="badge bg-light text-dark ms-2"></span></h5>
                </div>
                <div class="card-body">
                    <div id="templatesContainer" class="list-group" style="display: none;">
                        </div>
                    <div id="noSelectionMessage" class="no-selection">
                        <div class="text-center">
                            <i class="bi bi-arrow-left-circle" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">Şablonları görmek için soldan bir kategori seçin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
$(document).ready(function() {
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';

    $('#categoriesTable tbody tr').on('click', function() {
        $('#categoriesTable tbody tr').removeClass('selected');
        $(this).addClass('selected');
        
        const categoryId = $(this).data('id');
        const categoryName = $(this).data('name');
        
        $('#categoryNameBadge').text(categoryName);
        loadTemplates(categoryId);
    });

    function loadTemplates(categoryId) {
        $('#templatesContainer').hide();
        $('#noSelectionMessage').html('<div class="spinner-border text-success" role="status"><span class="visually-hidden">Yükleniyor...</span></div>').show();

        $.ajax({
            url: '<?= base_url('documents/get-templates') ?>',
            type: 'GET',
            data: { 
                category_id: categoryId,
                [csrfName]: csrfHash  // CSRF token'ı GET isteğine eklemek genellikle gerekli değil ama güvenli olsun.
            },
            dataType: 'json',
            success: function(templates) {
                const container = $('#templatesContainer');
                container.empty();

                if (templates.length === 0) {
                    $('#noSelectionMessage').html(`
                        <div class="text-center">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">Bu kategoride şablon bulunmuyor.</p>
                        </div>
                    `).show();
                    return;
                }

                templates.forEach(tpl => {
                    const link = `<?= base_url('documents/create/form/') ?>${tpl.id}`;
                    const templateItem = `
                        <a href="${link}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-text me-2"></i>
                                ${tpl.name}
                                <br>
                                <small class="text-muted">${tpl.description || ''}</small>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>`;
                    container.append(templateItem);
                });

                $('#noSelectionMessage').hide();
                container.show();
            },
            error: function(xhr, status, error) {
                console.error("Hata:", error);
                $('#noSelectionMessage').html('<div class="text-center text-danger"><i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i><p class="mt-3">Şablonlar yüklenirken bir hata oluştu.</p></div>').show();
            }
        });
    }
});
</script>
<?= $this->endSection() ?>