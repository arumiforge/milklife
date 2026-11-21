<?php
/**
 * Keterangan saat pendaftaran belum terbuka (rancangan 6.1). Ilustrasi G9-04
 * `pendaftaran-tutup.webp` bila berkasnya ada, selain itu ikon (`komponen('kosong')`).
 *
 * @var App\Libraries\BukaTutupPendaftaran $bukaTutup
 */

// ikon-sprite: calendar-clock calendar-x hourglass
$ikon = match ($bukaTutup->keadaan()) {
    $bukaTutup::BELUM_DIBUKA => 'calendar-clock',
    $bukaTutup::DITUTUP      => 'calendar-x',
    default                  => 'hourglass',
};
?>
<div class="pendaftaran-tertutup mt-8" data-pendaftaran-tertutup>
    <?= komponen('kosong', [
        'gambar' => 'gambar/keadaan/pendaftaran-tutup.webp',
        'ikon'   => $ikon,
        'judul'  => $bukaTutup->label(),
        'teks'   => $bukaTutup->kalimat() . ' Formulir pendaftaran hanya bisa diisi saat pendaftaran dibuka.',
        'aksi'   => komponen('tombol', ['label' => 'Kembali ke beranda', 'href' => site_url('/'), 'varian' => 'kedua', 'ikon' => 'arrow-left']),
    ]) ?>
</div>
