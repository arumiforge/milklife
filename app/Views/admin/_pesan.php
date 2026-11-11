<?php
/**
 * Pesan kilat hasil tindakan admin, dipasang sekali di `tata_letak/admin.php`
 * (di atas isi halaman). Halaman tidak menampilkan pesan ini sendiri.
 *
 * Cara memakai dari pengendali: alihkan dengan pesan kilat.
 *
 *     return redirect()->to('admin/pengaturan/acara')->with('pesan_sukses', 'Data acara tersimpan.');
 *     return redirect()->to('admin/pengaturan/kelompok-umur')->with('pesan_galat', 'Kelompok umur ini sudah dipakai atlet, jadi tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.');
 *
 * Data yang dibaca (dari sesi kilat, bukan dari data tampilan):
 * - `pesan_sukses` (string): simpan atau hapus berhasil. Kotak hijau.
 * - `pesan_galat` (string): tindakan gagal karena keadaan data; sebut sebab
 *   dan jalan keluarnya. Kotak merah.
 * Teks biasa; di-escape di sini. Keduanya boleh muncul bersamaan.
 *
 * Isian formulir yang salah tidak memakai pesan kilat, tetapi
 * `admin/_galat_isian` di atas formulir yang ditampilkan ulang.
 *
 * @var CodeIgniter\View\View $this
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
<div class="container-fluid px-3 px-lg-4 pesan-kilat">
    <?php foreach ($daftarPesan as $pesan) : ?>
        <div class="alert <?= $pesan['kelas'] ?> alert-dismissible d-flex align-items-start gap-2 fade show" role="<?= $pesan['peran'] ?>" data-pesan="<?= $pesan['kunci'] ?>">
            <i class="bi bi-<?= $pesan['ikon'] ?> flex-shrink-0" aria-hidden="true"></i>
            <div class="flex-grow-1"><?= esc($pesan['teks']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup pesan"></button>
        </div>
    <?php endforeach; ?>
</div>
