<?php
/**
 * Pengaturan > Tabel Skor (F9-10): daftar tabel skor multi-lomba, pasang
 * tabel bawaan panitia, tambah dan hapus tabel, tata letak Tailwind `admin_tw`.
 * Data dari `TabelSkorController::index()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var list<array<string, mixed>> $daftar        `DataTabelSkor::daftar()`
 * @var list<string>               $belumDipasang nama tabel bawaan yang belum terpasang
 * @var array<string, string>      $galat         galat tambah tabel
 * @var array<string, mixed>       $isian         isian tambah tabel yang dikembalikan
 */

use App\Libraries\Hasil\TabelSkor;

$alamat   = 'admin/pengaturan/tabel-skor';
$komponen = array_values((array) ($isian['komponen'] ?? []));
$kelasKotak = static fn (bool $salah): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . ($salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?>Tabel Skor<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Tabel Skor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Tabel skor mengubah prestasi (waktu atau jarak) menjadi poin untuk nomor berjenis hasil poin multi-lomba, misalnya Trilomba.
        Prestasi di antara dua baris memakai baris yang lebih buruk; prestasi di luar tabel mendapat 0 poin dan ditandai.
        Setiap perubahan baris langsung menghitung ulang poin dan peringkat hasil lomba yang memakai tabel itu.
    </p>

    <?php if ($belumDipasang !== []) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" id="tabel-bawaan" aria-labelledby="judul-bawaan">
            <h2 class="text-lg font-bold text-gray-900" id="judul-bawaan">Tabel bawaan panitia</h2>
            <p class="mt-1 text-sm text-gray-600">Belum terpasang: <?= esc(implode(', ', $belumDipasang)) ?>. Isinya dari tabel penilaian panitia (scoring table IAAF/PASI dan World Athletics).</p>
            <form action="<?= site_url($alamat . '/pasang') ?>" method="post" class="mt-4">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Pasang tabel bawaan', 'tipe' => 'submit', 'ikon' => 'download']) ?>
            </form>
        </section>
    <?php endif ?>

    <?php foreach ($daftar as $t) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" id="tabel-<?= $t['id'] ?>" aria-labelledby="judul-tabel-<?= $t['id'] ?>">
            <h2 class="text-lg font-bold break-words text-gray-900" id="judul-tabel-<?= $t['id'] ?>"><?= esc($t['nama']) ?></h2>
            <?php if ($t['keterangan'] !== '') : ?><p class="mt-1 text-sm text-gray-600"><?= esc($t['keterangan']) ?></p><?php endif ?>
            <p class="mt-1 text-sm text-gray-700" data-pemakai>
                <?= $t['nomor'] === [] ? 'Belum dipakai nomor lomba.' : 'Dipakai: ' . esc(implode(', ', $t['nomor'])) . '.' ?>
            </p>
            <div class="relative mt-4 overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm text-gray-700" data-tabel-klien>
                    <caption class="sr-only">Komponen <?= esc($t['nama']) ?></caption>
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3">Komponen</th>
                            <th scope="col" class="px-4 py-3">Jenis</th>
                            <?php foreach (TabelSkor::KATEGORI as $label) : ?><th scope="col" class="px-4 py-3"><?= $label ?></th><?php endforeach ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($t['komponen'] as $k) : ?>
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= esc($k['nama']) ?></td>
                                <td class="px-4 py-3"><?= esc(TabelSkor::JENIS_KOMPONEN[$k['jenis']]) ?></td>
                                <?php foreach (array_keys(TabelSkor::KATEGORI) as $kategori) : ?>
                                    <td class="px-4 py-1">
                                        <a class="inline-flex min-h-11 items-center whitespace-nowrap font-semibold text-primary-700 underline" href="<?= site_url($alamat . '/' . $t['id'] . '/' . $k['kode'] . '/' . $kategori) ?>">
                                            <?= number_format($t['jumlah'][$k['kode']][$kategori] ?? 0, 0, ',', '.') ?> baris<span class="sr-only"> <?= esc($k['nama'] . ' ' . $kategori) ?></span>
                                        </a>
                                    </td>
                                <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php if ($t['nomor'] === []) : ?>
                <form action="<?= site_url($alamat . '/' . $t['id'] . '/hapus') ?>" method="post" class="mt-4"
                    data-konfirmasi="<?= esc('Tabel ' . $t['nama'] . ' beserta semua barisnya akan dihapus. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                    data-konfirmasi-judul="Hapus tabel skor?" data-konfirmasi-tombol="Hapus tabel">
                    <?= csrf_field() ?>
                    <?= komponen('tombol', ['label' => 'Hapus tabel', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'trash-2', 'kelas' => 'text-red-700 hover:text-red-800']) ?>
                </form>
            <?php endif ?>
        </section>
    <?php endforeach ?>

    <?php if ($daftar === [] && $belumDipasang === []) : ?>
        <p class="text-gray-600" id="tabel-kosong">Belum ada tabel skor.</p>
    <?php endif ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" id="tambah-tabel" aria-labelledby="judul-tambah-tabel">
        <h2 class="text-lg font-bold text-gray-900" id="judul-tambah-tabel">Tambah tabel</h2>
        <p class="mt-1 text-sm text-gray-600">Untuk tabel lain dari panitia. Tulis nama tabel dan komponennya; barisnya diisi sesudahnya lewat Excel (kolom A prestasi, kolom B poin) untuk tiap komponen, putra dan putri.</p>
        <form action="<?= site_url($alamat) ?>" method="post" class="mt-4 max-w-2xl space-y-5" novalidate>
            <?= csrf_field() ?>
            <div>
                <label for="nama" class="mb-1.5 block text-sm font-semibold text-gray-900">Nama tabel</label>
                <input type="text" class="<?= $kelasKotak(isset($galat['nama'])) ?>" id="nama" name="nama" maxlength="<?= TabelSkor::NAMA_MAKSIMAL ?>" value="<?= esc((string) ($isian['nama'] ?? ''), 'attr') ?>"<?= isset($galat['nama']) ? ' aria-invalid="true" aria-describedby="nama-galat"' : '' ?>>
                <?php if (isset($galat['nama'])) : ?><p id="nama-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['nama']) ?></p><?php endif ?>
            </div>
            <fieldset<?= isset($galat['komponen']) ? ' aria-describedby="komponen-galat"' : '' ?>>
                <legend class="mb-2 text-sm font-semibold text-gray-900">Komponen</legend>
                <div class="space-y-2">
                    <?php for ($i = 0; $i < 5; $i++) : ?>
                        <div class="grid grid-cols-[minmax(0,7fr)_minmax(0,5fr)] gap-2">
                            <div>
                                <label class="sr-only" for="komponen-nama-<?= $i ?>">Nama komponen <?= $i + 1 ?></label>
                                <input type="text" class="<?= $kelasKotak(isset($galat['komponen'])) ?>" id="komponen-nama-<?= $i ?>" name="komponen_nama[]" maxlength="<?= TabelSkor::NAMA_KOMPONEN_MAKSIMAL ?>"
                                    placeholder="<?= ['Lari 60 m', 'Lompat Jauh', 'Tolak Peluru', '', ''][$i] ?>" value="<?= esc((string) ($komponen[$i]['nama'] ?? ''), 'attr') ?>"<?= isset($galat['komponen']) ? ' aria-invalid="true"' : '' ?>>
                            </div>
                            <div>
                                <label class="sr-only" for="komponen-jenis-<?= $i ?>">Jenis komponen <?= $i + 1 ?></label>
                                <select class="<?= $kelasKotak(false) ?> min-h-11" id="komponen-jenis-<?= $i ?>" name="komponen_jenis[]">
                                    <?php foreach (TabelSkor::JENIS_KOMPONEN as $kode => $label) : ?>
                                        <option value="<?= $kode ?>"<?= ($komponen[$i]['jenis'] ?? ($i === 0 ? 'waktu' : 'jarak')) === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    <?php endfor ?>
                </div>
                <?php if (isset($galat['komponen'])) : ?><p id="komponen-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['komponen']) ?></p><?php endif ?>
            </fieldset>
            <?= komponen('tombol', ['label' => 'Tambah tabel', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'plus']) ?>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
