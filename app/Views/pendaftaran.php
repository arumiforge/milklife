<?php
/**
 * Wizard pendaftaran (`/pendaftaran`, rancangan 7). Pengendali:
 * `App\Controllers\PendaftaranController::index`.
 *
 * Halaman dirender server sebagai kerangka lima langkah; `aset/js/pendaftaran.js`
 * mengisi dan menjalankannya dari data JSON `#data-pendaftaran`. Tanpa
 * JavaScript, halaman hanya menampilkan pesan agar JavaScript dinyalakan.
 *
 * @var CodeIgniter\View\View                 $this
 * @var string                                $namaAcara
 * @var App\Libraries\BukaTutupPendaftaran    $bukaTutup
 * @var array<string, mixed>|null             $wizard    null bila pendaftaran belum terbuka
 */

$langkah = [
    1 => 'Data Sekolah',
    2 => 'Penanggung Jawab',
    3 => 'Atlet',
    4 => 'Biaya',
    5 => 'Pembayaran',
];
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Pendaftaran – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-pendaftaran">
    <div class="pendaftaran-kepala">
        <div class="container">
            <h1><i class="bi bi-pencil-square" aria-hidden="true"></i> Pendaftaran</h1>
            <p class="mb-0"><?= esc($bukaTutup->kalimat()) ?></p>
        </div>
    </div>

    <div class="container pendaftaran-isi">
        <?php if ($wizard === null) : ?>
            <?= $this->include('pendaftaran/_tertutup') ?>
        <?php else : ?>
            <noscript>
                <div class="alert alert-warning">
                    Halaman pendaftaran membutuhkan JavaScript. Nyalakan JavaScript di peramban Anda, lalu muat ulang halaman ini.
                </div>
            </noscript>

            <div id="wizard-pendaftaran" class="wizard" hidden>
                <nav aria-label="Langkah pendaftaran">
                    <ol class="pil-langkah">
                        <?php foreach ($langkah as $nomor => $judul) : ?>
                            <li>
                                <button type="button" class="pil" data-ke-langkah="<?= $nomor ?>" disabled>
                                    <span class="pil-nomor" aria-hidden="true"><span class="pil-angka"><?= $nomor ?></span><i class="bi bi-check-lg pil-centang"></i></span>
                                    <span class="pil-judul"><?= esc($judul) ?></span>
                                    <span class="visually-hidden pil-keadaan"></span>
                                </button>
                            </li>
                        <?php endforeach ?>
                    </ol>
                </nav>

                <div id="peringatan-draf" class="alert alert-warning" role="status" hidden>
                    Isian belum bisa disimpan di peramban ini. Jangan muat ulang halaman sampai pendaftaran tersimpan.
                </div>

                <?= $this->include('pendaftaran/_langkah_sekolah') ?>
                <?= $this->include('pendaftaran/_langkah_penanggung_jawab') ?>
                <?= $this->include('pendaftaran/_langkah_atlet') ?>
                <?= $this->include('pendaftaran/_langkah_biaya') ?>
                <?= $this->include('pendaftaran/_langkah_pembayaran') ?>

                <p class="mulai-ulang">
                    <button type="button" class="btn btn-link btn-sm" id="tombol-mulai-ulang">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Kosongkan isian dan mulai dari awal
                    </button>
                </p>
            </div>

            <script type="application/json" id="data-pendaftaran"><?= json_encode($wizard, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

<?php if ($wizard !== null) : ?>
    <?= $this->section('kepala') ?>
    <meta name="csrf-kepala" content="<?= esc(config('Security')->headerName, 'attr') ?>">
    <meta name="csrf-token" content="<?= esc(csrf_hash(), 'attr') ?>">
    <link rel="stylesheet" href="<?= aset('css/pendaftaran.css') ?>">
    <?= $this->endSection() ?>

    <?= $this->section('skrip') ?>
    <script src="<?= aset('js/pendaftaran.js') ?>"></script>
    <?= $this->endSection() ?>
<?php endif ?>
