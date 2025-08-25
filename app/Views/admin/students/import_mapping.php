<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?>Sütun Eşleştirme<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control {
        padding: 0.6rem 1rem !important;
    }
</style>
<?= $this->endSection() ?>


<?= $this->section('main') ?>
<div class="container-fluid mt-4">
    <h1 class="h3 mb-4 text-gray-800"><i class="bi bi-diagram-3-fill"></i> Sütunları Eşleştir ve Aktar</h1>

    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill"></i>
        Lütfen yüklediğiniz dosyadaki sütunları sistemdeki veritabanı alanlarıyla dikkatlice eşleştirin. Eşleştirmek istemediğiniz sütunları "-- Eşleştirme Yapma --" olarak bırakabilirsiniz.
        <strong>TCKN alanı eşleştirmesi zorunludur.</strong>
    </div>

    <div class="card shadow">
        <div class="card-body p-lg-5">
            <form action="<?= route_to('admin.students.importProcess') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="file_name" value="<?= esc($fileName) ?>">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50%;">Dosyanızdaki Sütun Adı</th>
                                <th>Eşleştirilecek Veritabanı Alanı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fileColumns as $index => $columnName): ?>
                            <tr>
                                <td class="fw-bold align-middle"><?= esc($columnName) ?></td>
                                <td>
                                    <select name="mappings[<?= $index ?>]" class="form-select tom-select">
                                        <option value="">-- Eşleştirme Yapma --</option>
                                        <?php 
                                            // Basit bir otomatik eşleştirme denemesi
                                            $normalizedFileCol = str_replace(['İ', 'ı'], ['i', 'i'], strtolower(trim($columnName)));
                                            $selected = '';
                                        ?>
                                        <?php foreach ($dbColumns as $dbCol): ?>
                                            <?php
                                                $normalizedDbCol = str_replace('_', '', strtolower($dbCol));
                                                if (!$selected && $normalizedFileCol === $normalizedDbCol) {
                                                    $selected = $dbCol;
                                                }
                                                // Özel durumlar
                                                if (!$selected && $normalizedFileCol === 'il' && $dbCol === 'city_id') $selected = 'il';
                                                if (!$selected && $normalizedFileCol === 'ilce' && $dbCol === 'district_id') $selected = 'district_id';
                                            ?>
                                            <option value="<?= esc($dbCol) ?>" <?= ($selected === $dbCol) ? 'selected' : '' ?>>
                                                <?= esc($dbCol) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                       <i class="bi bi-arrow-down-square-fill"></i> Eşleştirmeyi Tamamla ve Verileri Aktar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var a = document.querySelectorAll('.tom-select');
    a.forEach(item => {
        new TomSelect(item, {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
});
</script>
<?= $this->endSection() ?>