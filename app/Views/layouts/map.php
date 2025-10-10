<!DOCTYPE html>
<html lang="tr" data-bs-theme="auto">
<head>
    <?= $this->include('layouts/partials/_head') ?>
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?= $this->include('layouts/partials/_navbar') ?>
    
    <main>
        <?= $this->renderSection('main') ?>
    </main>
    
    <?= $this->include('layouts/partials/_footer') ?>
    <?= $this->include('layouts/partials/_scripts') ?>
</body>
</html>