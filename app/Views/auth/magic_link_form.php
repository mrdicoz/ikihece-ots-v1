<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.useMagicLink') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5  position-absolute top-50 start-50 translate-middle">
    <div class="col-12 col-md-5">
        <div class="card-body">
            <div class="d-flex justify-content-center align-items-center">
                <img src="/assets/images/logo.png" alt="Logo" class="mb-4" style="max-width: 150px;">
            </div>
                <?php if (session('error') !== null) : ?>
                    <div class="alert alert-danger" role="alert"><?= session('error') ?></div>
                <?php elseif (session('errors') !== null) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php if (is_array(session('errors'))) : ?>
                            <?php foreach (session('errors') as $error) : ?>
                                <?= $error ?>
                                <br>
                            <?php endforeach ?>
                        <?php else : ?>
                            <?= session('errors') ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>

            <form action="<?= url_to('magic-link') ?>" method="post">
                <?= csrf_field() ?>

                <!-- Email -->
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="bi bi-envelope-at-fill"></i></span>
                        <div class="form-floating">
                            <input type="email" class="form-control" id="floatingInputGroup1" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email', auth()->user()->email ?? null) ?>" required>
                            <label for="floatingInputGroup1"><?= lang('Auth.email') ?></label>
                        </div>
                </div>

                <div class="d-grid col-12 col-md-8 mx-auto m-3">
                    <button type="submit" class="btn btn-success btn-block"><?= lang('Auth.send') ?></button>
                </div>

            </form>

            <p class="text-center"><a class="link-success link-underline link-underline-opacity-0" href="<?= url_to('login') ?>"><?= lang('Auth.backToLogin') ?></a></p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
