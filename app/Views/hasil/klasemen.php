<?php
/**
 * Klasemen sekolah publik (F8-05, K124), tata letak baru `publik_tw`
 * (F9-03): hanya nomor yang hasilnya sudah diterbitkan. Pengendali:
 * `App\Controllers\HasilController::klasemen()`.
 *
 * @var CodeIgniter\View\View               $this
 * @var string                              $namaAcara
 * @var array<string, array<string, mixed>> $klasemen   `HasilLomba::klasemen(true)`
 * @var string                              $metode     `Klasemen::MEDALI` atau `Klasemen::POIN` (Pengaturan > Hasil, F9-11)
 * @var string                              $keterangan `Klasemen::keterangan()`
 */
$aksi = komponen('tombol', ['label' => 'Lihat hasil per nomor', 'href' => site_url('hasil'), 'varian' => 'kedua', 'ikon' => 'trophy']);

if ($klasemen !== []) {
    $aksi .= komponen('tombol', ['label' => 'Unduh PDF', 'href' => site_url('hasil/klasemen/pdf'), 'varian' => 'kedua', 'ikon' => 'file-text'])
        . komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('hasil/klasemen/excel'), 'varian' => 'kedua', 'ikon' => 'file-spreadsheet']);
}
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Klasemen Sekolah – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Klasemen sekolah per jenjang dari nomor lomba yang hasilnya sudah diterbitkan.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_judul_halaman_tw', [
    'judul' => 'Klasemen Sekolah',
    'ikon'  => 'award',
    'gambar' => 'judul/klasemen.webp',
    'teks'  => 'Klasemen per jenjang sekolah. ' . $keterangan . ' Dihitung dari nomor yang hasilnya sudah diterbitkan panitia.',
    'aksi'  => $aksi,
], ['saveData' => false]) ?>

<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-10">
    <?php if ($klasemen === []) : ?>
        <div id="klasemen-kosong">
            <?= komponen('kosong', [
                'gambar' => 'gambar/keadaan/kosong-hasil.webp',
                'ikon'   => 'hourglass',
                'judul'  => 'Klasemen belum tersedia',
                'teks'   => 'Klasemen muncul setelah panitia menerbitkan hasil Final. Silakan kembali lagi nanti.',
            ]) ?>
        </div>
    <?php endif ?>

    <?= view('hasil/_klasemen_tw', ['klasemen' => $klasemen, 'metode' => $metode], ['saveData' => false]) ?>
</div>
<?= $this->endSection() ?>
