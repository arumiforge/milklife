<?php
/**
 * Pesan kilat di halaman publik, dipasang sekali di `tata_letak/publik.php`
 * (di atas isi halaman). Sama dengan `admin/_pesan` untuk panel admin.
 *
 * Data yang dibaca (dari sesi kilat):
 * - `pesan_sukses` (string): kotak hijau.
 * - `pesan_galat` (string): kotak merah, misalnya kiriman yang melebihi batas
 *   unggah (`App\Filters\BatasUnggahFilter`).
 * Teks biasa; di-escape di sini.
 */

$daftarPesan = [];

foreach ([
    'pesan_sukses' => ['kelas' => 'alert-success', 'ikon' => 'check-circle-fill', 'peran' => 'status'],
    'pesan_galat'  => ['kelas' => 'alert-danger', 'ikon' => 'exclamation-triangle-fill', 'peran' => 'alert'],
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
<div class="container pesan-kilat pt-3">
    <?php foreach ($daftarPesan as $pesan) : ?>
        <div class="alert <?= $pesan['kelas'] ?> alert-dismissible d-flex align-items-start gap-2 fade show mb-0" role="<?= $pesan['peran'] ?>" data-pesan="<?= $pesan['kunci'] ?>">
            <i class="bi bi-<?= $pesan['ikon'] ?> flex-shrink-0" aria-hidden="true"></i>
            <div class="flex-grow-1"><?= esc($pesan['teks']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup pesan"></button>
        </div>
    <?php endforeach; ?>
</div>
