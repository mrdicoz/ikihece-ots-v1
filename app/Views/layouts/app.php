<!DOCTYPE html>
<html lang="tr" data-bs-theme="auto">
<head>
    <?= $this->include('layouts/partials/_head') ?>
</head>
<body>
    
    <?= $this->include('layouts/partials/_navbar') ?>

    <main class="container py-4 mt-5">
        <?= $this->renderSection('main') ?>
    </main>

    <?= $this->include('layouts/partials/_footer') ?>

    <?= $this->include('layouts/partials/_scripts') ?>

</body>
</html>