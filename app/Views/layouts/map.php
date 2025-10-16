<!DOCTYPE html>
<html lang="tr" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'İkihece OTS v1.0' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/custom.css') ?>" rel="stylesheet">

    <style>
        :root {
            --navbar-height: 56px;
            --panel-width: 320px;
            --panel-top-offset: 15px;
            --status-top-offset: 15px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* Normal durum - navbar'lı */
        body {
            display: flex;
            flex-direction: column;
        }

        #main-navbar {
            height: var(--navbar-height);
            flex-shrink: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        main {
            flex: 1;
            position: relative;
            height: calc(100vh - var(--navbar-height));
            overflow: hidden;
        }

        /* TAM EKRAN MODU */
        body.fullscreen-mode {
            display: block;
        }

        body.fullscreen-mode #main-navbar {
            position: absolute;
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
        }

        body.fullscreen-mode main {
            height: 100vh;
        }

        #map {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        /* Harita kontrollerinin z-index ayarlaması */
        .leaflet-control {
            z-index: 800 !important;
        }
    </style>
    
    <?= $this->renderSection('pageStyles') ?>
</head>
<body>
    <nav id="main-navbar">
        <?= $this->include('layouts/partials/_navbar') ?>
    </nav>

    <main>
        <?= $this->renderSection('main') ?>
    </main>

    <!-- Core Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <?php if(file_exists(FCPATH . 'assets/js/custom.js')): ?>
    <script src="<?= base_url('assets/js/custom.js') ?>"></script>
    <?php endif; ?>

    <?= $this->renderSection('pageScripts') ?>
</body>
</html>