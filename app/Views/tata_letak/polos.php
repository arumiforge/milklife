<?php
/**
 * Tata letak polos untuk halaman masuk dan halaman galat
 * (kerangka; tampilan jadinya dikerjakan F1-05).
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman untuk tab peramban. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `isi`: isi halaman.
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap.
 *
 * @var CodeIgniter\View\View $this
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('judul') ?></title>
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/tema.css') ?>">
</head>
<body>
    <main>
        <?= $this->renderSection('isi') ?>
    </main>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
