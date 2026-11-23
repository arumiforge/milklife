<?php
/**
 * Klasemen sekolah di panel admin (F8-05, K124), tata letak Tailwind
 * `admin_tw`. Data dari `KlasemenController::index()`.
 *
 * Tabel per klasemen kelompok umur dari `hasil/_klasemen_tw.php` (sama dengan halaman
 * publik) dengan tanda "belum terbit" pada piala dari nomor yang belum diterbitkan.
 * Kolom poin selalu tampil (F9-11): pada metode poin ditebalkan dan tampil di
 * ponsel, pada metode medali disembunyikan di ponsel.
 *
 * @var CodeIgniter\View\View               $this
 * @var array<string, array<string, mixed>> $klasemen    `Klasemen::hitung()`, semua nomor
 * @var int                                 $belumTerbit nomor ber-juara yang belum terbit
 * @var string                              $metode      `Klasemen::MEDALI` atau `Klasemen::POIN` (Pengaturan > Hasil)
 * @var string                              $keterangan  `Klasemen::teksSusunan()` dan `Klasemen::keterangan()`
 */

use App\Libraries\Hasil\Klasemen;

$metodePoin = $metode === Klasemen::POIN;
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Klasemen sekolah<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <div class="flex flex-wrap items-start gap-4">
        <p class="min-w-0 flex-1 basis-72 text-gray-700">
            <?= esc($keterangan) ?>
            Juara diambil dari babak Final semua nomor<?= $metodePoin ? ' (poin dari peringkat 1 sampai ' . Klasemen::PERINGKAT_POIN . ')' : '' ?>. Satu regu juara dihitung satu piala untuk sekolahnya. Sekolah dikenali dari NPSN.
            Metode dan gabungan kelompok umur diatur di <a href="<?= site_url('admin/pengaturan/hasil') ?>" class="font-semibold text-primary-700 underline">Pengaturan &gt; Hasil</a>.
            Halaman publik hanya menghitung nomor yang sudah diterbitkan.
        </p>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Unduh klasemen">
            <?= komponen('tombol_unduh', ['jenis' => 'pdf', 'href' => site_url('admin/klasemen/pdf')]) ?>
            <?= komponen('tombol_unduh', ['jenis' => 'excel', 'href' => site_url('admin/klasemen/excel')]) ?>
        </div>
    </div>

    <?php if ($belumTerbit > 0) : ?>
        <div id="belum-terbit">
            <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => $belumTerbit . ' nomor yang juaranya ikut dihitung di sini belum diterbitkan. Karena itu, klasemen publik masih berbeda. '
                . '<a href="' . site_url('admin/hasil') . '" class="font-semibold underline">Buka hasil lomba</a> untuk menerbitkannya.']) ?>
        </div>
    <?php endif ?>

    <?php if ($klasemen === []) : ?>
        <p class="text-gray-600" id="klasemen-kosong">Belum ada juara. Juara diambil dari babak Final di menu Hasil lomba.</p>
    <?php endif ?>

    <?= view('hasil/_klasemen_tw', ['klasemen' => $klasemen, 'metode' => $metode, 'admin' => true], ['saveData' => false]) ?>
</div>
<?= $this->endSection() ?>
