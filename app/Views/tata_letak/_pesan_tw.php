<?php
/**
 * Pesan kilat untuk tata letak bertampilan baru (`publik_tw.php`, `admin_tw.php`),
 * padanan `_pesan_publik.php` dan `admin/_pesan.php`.
 *
 * Membaca `pesan_sukses` (hijau) dan `pesan_galat` (merah) dari sesi kilat;
 * teks biasa, di-escape di sini. Tombol tutup memakai `data-dismiss-target` Flowbite.
 */

$daftarPesan = [];

foreach ([
    'pesan_sukses' => ['warna' => 'border-green-200 bg-green-50 text-green-900', 'ikon' => 'circle-check', 'peran' => 'status'],
    'pesan_galat'  => ['warna' => 'border-red-200 bg-red-50 text-red-900', 'ikon' => 'circle-alert', 'peran' => 'alert'],
] as $kunci => $gaya) {
    $teks = session()->getFlashdata($kunci);

    if (is_string($teks) && trim($teks) !== '') {
        $daftarPesan[] = $gaya + ['teks' => trim($teks), 'kunci' => $kunci];
    }
}

if ($daftarPesan === []) {
    return;
}
?>
<div class="mx-auto max-w-screen-xl space-y-3 px-4 pt-4">
    <?php foreach ($daftarPesan as $i => $pesan) : ?>
        <div id="pesan-kilat-<?= $i ?>" class="flex items-start gap-3 rounded-xl border p-4 text-sm <?= $pesan['warna'] ?>" role="<?= $pesan['peran'] ?>" data-pesan="<?= $pesan['kunci'] ?>">
            <?= ikon($pesan['ikon'], 'mt-0.5') ?>
            <div class="flex-1"><?= esc($pesan['teks']) ?></div>
            <button type="button" class="-m-2 inline-flex size-11 items-center justify-center rounded-lg hover:bg-black/5" data-dismiss-target="#pesan-kilat-<?= $i ?>">
                <?= ikon('x', '', 'Tutup pesan') ?>
            </button>
        </div>
    <?php endforeach ?>
</div>
