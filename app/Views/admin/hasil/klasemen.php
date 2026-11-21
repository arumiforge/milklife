<?php
/**
 * Klasemen sekolah di panel admin (F8-05, K124), tata letak Tailwind
 * `admin_tw`. Data dari `KlasemenController::index()`.
 *
 * Tabel per jenjang sama dengan `hasil/_klasemen_tw.php` (halaman publik),
 * ditambah tanda "belum terbit" pada piala dari nomor yang belum diterbitkan.
 * Kolom poin selalu tampil (F9-11): pada metode poin ditebalkan dan tampil di
 * ponsel, pada metode medali disembunyikan di ponsel.
 *
 * @var CodeIgniter\View\View               $this
 * @var array<string, array<string, mixed>> $klasemen    `Klasemen::hitung()`, semua nomor
 * @var int                                 $belumTerbit nomor ber-juara yang belum terbit
 * @var string                              $metode      `Klasemen::MEDALI` atau `Klasemen::POIN` (Pengaturan > Hasil)
 * @var string                              $keterangan  `Klasemen::keterangan()`
 */

use App\Libraries\Hasil\Klasemen;

$romawi     = [1 => 'I', 2 => 'II', 3 => 'III'];
$metodePoin = $metode === Klasemen::POIN;
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Klasemen Sekolah<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <div class="flex flex-wrap items-start gap-4">
        <p class="max-w-3xl min-w-0 flex-1 basis-72 text-gray-700">
            <?= esc($keterangan) ?>
            Klasemen dihitung per jenjang dari babak Final semua nomor<?= $metodePoin ? ' (poin dari peringkat 1–' . Klasemen::PERINGKAT_POIN . ')' : '' ?>; satu regu juara = satu piala untuk sekolahnya, sekolah dikenali dari NPSN.
            Metode diatur di <a href="<?= site_url('admin/pengaturan/hasil') ?>" class="font-semibold text-primary-700 underline">Pengaturan &gt; Hasil</a>.
            Halaman publik hanya menghitung nomor yang sudah diterbitkan.
        </p>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Unduh klasemen">
            <?= komponen('tombol', ['label' => 'PDF', 'href' => site_url('admin/klasemen/pdf'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-text', 'kelas' => 'min-h-11']) ?>
            <?= komponen('tombol', ['label' => 'Excel', 'href' => site_url('admin/klasemen/excel'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'file-spreadsheet', 'kelas' => 'min-h-11']) ?>
        </div>
    </div>

    <?php if ($belumTerbit > 0) : ?>
        <div id="belum-terbit">
            <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => $belumTerbit . ' nomor yang juaranya ikut dihitung di sini belum diterbitkan, jadi klasemen publik masih berbeda. '
                . '<a href="' . site_url('admin/hasil') . '" class="font-semibold underline">Buka Hasil lomba</a> untuk menerbitkannya.']) ?>
        </div>
    <?php endif ?>

    <?php if ($klasemen === []) : ?>
        <p class="text-gray-600" id="klasemen-kosong">Belum ada juara. Juara diambil dari babak Final di menu Hasil lomba.</p>
    <?php endif ?>

    <?php foreach ($klasemen as $jenjang) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-6" aria-labelledby="klasemen-<?= esc($jenjang['jenjang'], 'attr') ?>">
            <h2 class="text-lg font-bold text-gray-900" id="klasemen-<?= esc($jenjang['jenjang'], 'attr') ?>"><?= esc($jenjang['label']) ?></h2>
            <div class="relative mt-4 overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm text-gray-700">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                        <tr>
                            <th scope="col" class="px-2 py-3 text-center sm:px-3">Per.</th>
                            <th scope="col" class="px-2 py-3 sm:px-3">Sekolah</th>
                            <th scope="col" class="px-2 py-3 text-center sm:px-3"><span class="sr-only">Juara </span>I</th>
                            <th scope="col" class="px-2 py-3 text-center sm:px-3"><span class="sr-only">Juara </span>II</th>
                            <th scope="col" class="px-2 py-3 text-center sm:px-3"><span class="sr-only">Juara </span>III</th>
                            <th scope="col" class="hidden px-2 py-3 text-center sm:table-cell sm:px-3">Jumlah</th>
                            <th scope="col" class="<?= $metodePoin ? '' : 'hidden sm:table-cell ' ?>px-2 py-3 text-center sm:px-3" data-kolom="poin">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jenjang['baris'] as $b) : ?>
                            <tr class="border-t border-gray-100 align-top <?= $b['juara_umum'] !== null ? 'bg-yellow-50' : 'bg-white' ?>"<?= $b['juara_umum'] !== null ? ' data-juara-umum="' . (int) $b['juara_umum'] . '"' : '' ?>>
                                <td class="px-2 py-3 text-center font-bold text-gray-900 sm:px-3"><?= $b['peringkat'] ?></td>
                                <td class="px-2 py-3 sm:px-3">
                                    <span class="font-semibold break-words text-gray-900"><?= esc($b['sekolah']) ?></span>
                                    <?php if ($b['juara_umum'] !== null) : ?>
                                        <?= komponen('lencana', ['label' => 'Juara umum ' . $romawi[$b['juara_umum']], 'warna' => 'kuning']) ?>
                                    <?php endif ?>
                                    <?php if ($b['piala'] === []) : ?>
                                        <span class="mt-1 block text-sm text-gray-600">Tanpa piala; poin dari peringkat 4–<?= Klasemen::PERINGKAT_POIN ?></span>
                                    <?php else : ?>
                                        <details class="mt-1 text-gray-600">
                                            <summary class="inline-flex min-h-11 cursor-pointer items-center text-sm font-semibold text-primary-700">Rincian <?= $b['jumlah'] ?> piala</summary>
                                            <ul class="mb-1 list-disc space-y-0.5 ps-5 text-sm">
                                                <?php foreach ($b['piala'] as $p) : ?>
                                                    <li>
                                                        Juara <?= $romawi[$p['peringkat']] ?> <?= esc($p['kelompok_umur'] . ' ' . $p['nomor']) ?> – <?= esc($p['nama']) ?>
                                                        <?php if (! $p['terbit']) : ?><?= komponen('lencana', ['label' => 'belum terbit', 'warna' => 'abu']) ?><?php endif ?>
                                                    </li>
                                                <?php endforeach ?>
                                            </ul>
                                        </details>
                                    <?php endif ?>
                                </td>
                                <td class="px-2 py-3 text-center font-semibold sm:px-3"><?= $b['emas'] ?></td>
                                <td class="px-2 py-3 text-center font-semibold sm:px-3"><?= $b['perak'] ?></td>
                                <td class="px-2 py-3 text-center font-semibold sm:px-3"><?= $b['perunggu'] ?></td>
                                <td class="hidden px-2 py-3 text-center font-bold sm:table-cell sm:px-3"><?= $b['jumlah'] ?></td>
                                <td class="<?= $metodePoin ? 'font-extrabold text-gray-900' : 'hidden font-semibold sm:table-cell' ?> px-2 py-3 text-center sm:px-3"><?= $b['poin'] ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
