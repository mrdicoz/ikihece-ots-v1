<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?>
    <?= $title ?>
<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <a href="<?= site_url('admin/students/new') ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Yeni Öğrenci Ekle</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Öğrenci Kayıtları</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Okul No</th>
                            <th>Adı Soyadı</th>
                            <th>TC Kimlik No</th>
                            <th>Sınıfı</th>
                            <th>Veli (Anne) Telefon</th>
                            <th>Veli (Baba) Telefon</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= esc($student['id']) ?></td>
                                    <td><?= esc($student['okul_no']) ?></td>
                                    <td><?= esc($student['adi']) . ' ' . esc($student['soyadi']) ?></td>
                                    <td><?= esc($student['tc_kimlik_no']) ?></td>
                                    <td><?= esc($student['sinifi']) ?></td>
                                    <td><?= esc($student['veli_anne_telefon']) ?></td>
                                    <td><?= esc($student['veli_baba_telefon']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/students/' . $student['id']) ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                        <a href="<?= site_url('admin/students/' . $student['id'] . '/edit') ?>" class="btn btn-warning btn-sm"><i class="fas fa-pencil-alt"></i></a>
                                        <form action="<?= site_url('admin/students/' . $student['id']) ?>" method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Henüz öğrenci kaydı bulunmamaktadır.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
             <div class="d-flex justify-content-end">
                <?= $pager->links() ?>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json"
            }
        });
    });
</script>
<?= $this->endSection() ?>