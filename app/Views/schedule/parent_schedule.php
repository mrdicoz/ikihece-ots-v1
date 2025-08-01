<?= $this->extend('layouts/app') ?>

<?= $this->section('main') ?>
<div class="container mt-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-month"></i> <?= esc($title) ?></h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Ders Programı Filtrele</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= route_to('parent.schedule') ?>" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="year" class="form-label">Yıl Seçin</label>
                    <select name="year" id="year" class="form-select">
                        <?php 
                        $processed_years = [];
                        foreach ($available_months as $item):
                            if (!in_array($item['year'], $processed_years)): ?>
                                <option value="<?= $item['year'] ?>" <?= ($item['year'] == $selected_year) ? 'selected' : '' ?>>
                                    <?= $item['year'] ?>
                                </option>
                            <?php 
                            $processed_years[] = $item['year'];
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="month" class="form-label">Ay Seçin</label>
                    <select name="month" id="month" class="form-select">
                        <?php 
                        $aylar = ["", "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
                        foreach ($available_months as $item): 
                            if ($item['year'] == $selected_year): ?>
                                <option value="<?= $item['month'] ?>" <?= ($item['month'] == $selected_month) ? 'selected' : '' ?>>
                                    <?= $aylar[$item['month']] ?>
                                </option>
                            <?php endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Göster</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">
                <?php $aylar = ["", "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"]; ?>
                <?= esc($selected_year) ?> - <?= $aylar[(int)$selected_month] ?? '' ?> Ayı Ders Programı
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Gün</th>
                            <th>Saat</th>
                            <th>Ders Tipi</th>
                            <th>Öğretmen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lessons)): ?>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td><?= \CodeIgniter\I18n\Time::parse($lesson['lesson_date'])->toLocalizedString('d MMMM yyyy') ?></td>
                                    <td><?= \CodeIgniter\I18n\Time::parse($lesson['lesson_date'])->toLocalizedString('EEEE') ?></td>
                                    <td><?= date('H:i', strtotime($lesson['start_time'])) ?></td>
                                    <td>
                                        <?php if ($lesson['lesson_type'] === 'Grup Dersi'): ?>
                                            <span class="badge bg-info">Grup Dersi</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Bireysel Ders</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($lesson['teacher_first_name'] . ' ' . $lesson['teacher_last_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Seçili ay için planlanmış ders bulunmamaktadır.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>