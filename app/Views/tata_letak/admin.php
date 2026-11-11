<?php
/**
 * Tata letak panel admin (kerangka; tampilan jadinya dikerjakan F1-05).
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman untuk tab peramban. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `isi`: isi halaman.
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap.
 *
 * Nama admin dibaca dari sesi `admin_nama`. Tombol Keluar mengirim `POST`
 * ke `/admin/keluar` dengan token CSRF (rutenya dibuat F1-04).
 *
 * @var CodeIgniter\View\View $this
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $this->renderSection('judul') ?></title>
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/tema.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/admin.css') ?>">
</head>
<body>
    <header>
        <span><?= esc((string) session('admin_nama')) ?></span>
        <form action="<?= site_url('admin/keluar') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit">Keluar</button>
        </form>
    </header>

    <main>
        <?= $this->renderSection('isi') ?>
    </main>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
