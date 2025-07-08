<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.useMagicLink') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5  position-absolute top-50 start-50 translate-middle">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-center align-items-center">
                <img src="/assets/images/logo.png" alt="Logo" class="mb-4" style="max-width: 150px;">
            </div>

            <p class="text-center"><b><?= lang('Auth.checkYourEmail') ?></b></p>

            <p class="text-center"><?= lang('Auth.magicLinkDetails', [setting('Auth.magicLinkLifetime') / 60]) ?></p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
