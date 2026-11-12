<?php
/**
 * Keterangan saat pendaftaran belum terbuka (rancangan 6.1).
 *
 * @var App\Libraries\BukaTutupPendaftaran $bukaTutup
 */

$ikon = match ($bukaTutup->keadaan()) {
    $bukaTutup::BELUM_DIBUKA => 'bi-calendar-event',
    $bukaTutup::DITUTUP      => 'bi-calendar-x',
    default                  => 'bi-hourglass-split',
};
?>
<div class="pendaftaran-tertutup">
    <span class="ikon-bulat mb-3"><i class="bi <?= $ikon ?>" aria-hidden="true"></i></span>
    <h2 class="h4"><?= esc($bukaTutup->label()) ?></h2>
    <p><?= esc($bukaTutup->kalimat()) ?> Formulir pendaftaran hanya bisa diisi saat pendaftaran dibuka.</p>
    <a class="btn btn-outline-primary" href="<?= site_url('/') ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda
    </a>
</div>
