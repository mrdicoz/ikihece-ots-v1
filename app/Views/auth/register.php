<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.register') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

    <div class="container d-flex justify-content-center p-5  position-absolute top-50 start-50 translate-middle">
        <div class="col-12 col-md-5 ">
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

                <form action="<?= url_to('register') ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Email -->
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="bi bi-envelope-at-fill"></i></span>
                            <div class="form-floating">
                                <input type="email" class="form-control" id="floatingInputGroup1" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required>
                                <label for="floatingInputGroup1"><?= lang('Auth.email') ?></label>
                            </div>
                    </div>

                    <!-- Username -->
                    <div class="input-group mb-4">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <div class="form-floating">
                                <input type="text" class="form-control" id="floatingInputGroup1" name="username" inputmode="username" autocomplete="username" placeholder="<?= lang('Auth.username') ?>" value="<?= old('username') ?>" required>
                                <label for="floatingInputGroup1"><?= lang('Auth.username') ?></label>
                            </div>
                    </div>

                    <!-- Password -->
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <div class="form-floating">
                                <input type="password" class="form-control" id="floatingInputGroup1" name="password" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.password') ?>" value="<?= old('password') ?>" required>
                                <label for="floatingInputGroup1"><?= lang('Auth.password') ?></label>
                            </div>
                    </div>

                    <!-- Password (Again) -->
                    <div class="input-group mb-5">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <div class="form-floating">
                                <input type="password" class="form-control" id="floatingInputGroup1" name="password_confirm" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.passwordConfirm') ?>" required>
                                <label for="floatingInputGroup1"><?= lang('Auth.passwordConfirm') ?></label>
                            </div>
                    </div>

                    <div class="d-grid col-12 col-md-8 mx-auto m-3">
                        <button type="submit" class="btn btn-success btn-block"><?= lang('Auth.register') ?></button>
                    </div>

                    <p class="text-center"><?= lang('Auth.haveAccount') ?> <a class="link-success link-underline link-underline-opacity-0" href="<?= url_to('login') ?>"><?= lang('Auth.login') ?></a></p>

                </form>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>
