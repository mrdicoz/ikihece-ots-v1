<meta charset="UTF-8">
<title><?= $title ?? 'İkihece OTS v1.0' ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#198754"/>
<meta name="csrf-token" content="<?= csrf_hash() ?>">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/custom.css') ?>" rel="stylesheet">