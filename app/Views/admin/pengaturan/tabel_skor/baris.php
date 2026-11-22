<?php
/**
 * Baris satu komponen dan kategori tabel skor (F9-10): ubah, tambah, hapus
 * baris; ganti semua baris dari Excel; unduh Excel. Tata letak Tailwind
 * `admin_tw`. Data dari `TabelSkorController::baris()`.
 *
 * @var CodeIgniter\View\View                          $this
 * @var array<string, mixed>                           $tabel      `DataTabelSkor::tabel()`
 * @var array{kode: string, nama: string, jenis: string} $komponen
 * @var string                                         $kategori   putra atau putri
 * @var list<array{0: int, 1: int}>                    $baris      urut dari prestasi terbaik
 * @var array<string, string>                          $galat      galat simpan baris
 * @var list<string>                                   $galatImpor galat impor Excel per baris
 * @var array<string, mixed>                           $isian      isian simpan baris yang dikembalikan
 */

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Prestasi;

$alamat  = 'admin/pengaturan/tabel-skor/' . $tabel['id'] . '/' . $komponen['kode'] . '/' . $kategori;
$waktu   = $komponen['jenis'] === JenisHasil::WAKTU;
$contoh  = $waktu ? '7,00' : '4,25';
$judul   = $komponen['nama'] . ' ' . $kategori;
$kelasKotak = static fn (bool $salah): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . ($salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
// Pesan galat simpan baris dirujuk kedua kotak lewat `aria-describedby`.
$galatBaris = $galat === [] ? '' : ' aria-describedby="baris-galat"';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?><?= esc($judul) ?> – <?= esc($tabel['nama']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Tabel Skor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div>
        <h2 class="text-xl font-bold break-words text-gray-900"><?= esc($judul) ?></h2>
        <p class="mt-1 text-gray-600"><?= esc($tabel['nama']) ?> · <?= count($baris) ?> baris · prestasi <?= $waktu ? 'waktu dalam detik (makin kecil makin baik)' : 'jarak dalam meter (makin jauh makin baik)' ?></p>
    </div>

    <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
        <div class="space-y-6 lg:order-2 lg:col-span-5">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="ubah-baris" aria-labelledby="judul-ubah-baris">
                <h3 class="text-lg font-bold text-gray-900" id="judul-ubah-baris">Ubah atau tambah baris</h3>
                <p class="mt-1 text-sm text-gray-600">Tulis prestasi dan poinnya. Bila prestasi itu sudah ada, poinnya diganti.</p>
                <form action="<?= site_url($alamat) ?>" method="post" class="mt-4 space-y-3" novalidate>
                    <?= csrf_field() ?>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="prestasi" class="mb-1.5 block text-sm font-semibold text-gray-900">Prestasi</label>
                            <input type="text" inputmode="decimal" class="<?= $kelasKotak(isset($galat['prestasi'])) ?>" id="prestasi" name="prestasi" placeholder="<?= $contoh ?>" value="<?= esc((string) ($isian['prestasi'] ?? ''), 'attr') ?>"<?= isset($galat['prestasi']) ? ' aria-invalid="true"' : '' ?><?= $galatBaris ?>>
                        </div>
                        <div>
                            <label for="poin" class="mb-1.5 block text-sm font-semibold text-gray-900">Poin</label>
                            <input type="text" inputmode="numeric" class="<?= $kelasKotak(isset($galat['poin'])) ?>" id="poin" name="poin" placeholder="882" value="<?= esc((string) ($isian['poin'] ?? ''), 'attr') ?>"<?= isset($galat['poin']) ? ' aria-invalid="true"' : '' ?><?= $galatBaris ?>>
                        </div>
                    </div>
                    <?php if ($galat !== []) : ?>
                        <div id="baris-galat" class="space-y-1">
                            <?php foreach ($galat as $pesan) : ?><p class="text-sm font-medium text-red-700"><?= esc($pesan) ?></p><?php endforeach ?>
                        </div>
                    <?php endif ?>
                    <?= komponen('tombol', ['label' => 'Simpan baris', 'tipe' => 'submit', 'ikon' => 'save']) ?>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="impor" aria-labelledby="judul-impor">
                <h3 class="text-lg font-bold text-gray-900" id="judul-impor">Ganti dari Excel</h3>
                <p class="mt-1 text-sm text-gray-600">Kolom A prestasi (misalnya <?= $contoh ?><?= $waktu ? ' atau 2:05,10' : '' ?>), kolom B poin. Baris judul boleh ada. Semua baris <?= esc($judul) ?> diganti isi berkas; bila ada baris yang salah, tidak ada yang diganti.</p>
                <?php if ($galatImpor !== []) : ?>
                    <ul class="mt-3 list-disc space-y-0.5 rounded-xl border border-red-200 bg-red-50 p-4 ps-8 text-sm text-red-900" id="galat-impor" role="alert">
                        <?php foreach ($galatImpor as $pesan) : ?><li><?= esc($pesan) ?></li><?php endforeach ?>
                    </ul>
                <?php endif ?>
                <form action="<?= site_url($alamat . '/impor') ?>" method="post" enctype="multipart/form-data" class="mt-4 space-y-3"
                    data-konfirmasi="<?= esc('Semua baris ' . $judul . ' akan diganti isi berkas Excel, lalu poin hasil lomba dihitung ulang.', 'attr') ?>"
                    data-konfirmasi-judul="Ganti baris tabel?" data-konfirmasi-tombol="Ganti dari Excel">
                    <?= csrf_field() ?>
                    <div>
                        <label for="berkas" class="mb-1.5 block text-sm font-semibold text-gray-900">Berkas Excel</label>
                        <input type="file" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 file:me-3 file:min-h-11 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:font-semibold file:text-white hover:file:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-primary-100" id="berkas" name="berkas" accept=".xlsx,.xls,.csv">
                    </div>
                    <?= komponen('tombol', ['label' => 'Ganti dari Excel', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'upload']) ?>
                </form>
                <a class="mt-3 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-primary-700 hover:underline" href="<?= site_url($alamat . '/excel') ?>"><?= ikon('sheet', 'size-4') ?> Unduh baris ini sebagai Excel</a>
            </section>
            <?= komponen('tombol', ['label' => 'Kembali ke daftar tabel', 'href' => site_url('admin/pengaturan/tabel-skor') . '#tabel-' . $tabel['id'], 'varian' => 'kedua', 'ikon' => 'arrow-left']) ?>
        </div>

        <div class="lg:order-1 lg:col-span-7">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-baris">
                <h3 class="text-lg font-bold text-gray-900" id="judul-baris">Baris tabel</h3>
                <?php if ($baris === []) : ?>
                    <p class="mt-2 text-gray-600" id="baris-kosong">Belum ada baris. Isi lewat Excel atau satu per satu.</p>
                <?php else : ?>
                    <form action="<?= site_url($alamat . '/hapus') ?>" method="post" id="hapus-baris" class="mt-4"
                        data-konfirmasi="<?= esc('Baris yang dipilih akan dihapus dari tabel ' . $judul . ', lalu poin hasil lomba dihitung ulang.', 'attr') ?>"
                        data-konfirmasi-judul="Hapus baris?" data-konfirmasi-tombol="Hapus baris">
                        <?= csrf_field() ?>
                        <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-left text-sm text-gray-700" id="tabel-baris" data-tabel-klien>
                                <caption class="sr-only">Baris <?= esc($judul) ?></caption>
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    <tr><th scope="col" class="px-4 py-3">Prestasi</th><th scope="col" class="px-4 py-3 text-end">Poin</th><th scope="col" class="px-4 py-3 text-end">Hapus</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($baris as [$prestasi, $poin]) : ?>
                                        <?php $teks = esc(Prestasi::tulis($prestasi, $komponen['jenis'])); ?>
                                        <tr class="border-t border-gray-100">
                                            <td class="px-4 py-1"><?= $teks ?></td>
                                            <td class="px-4 py-1 text-end"><?= $poin ?></td>
                                            <td class="px-4 py-1 text-end"><button type="submit" name="prestasi" value="<?= $prestasi ?>" class="inline-flex size-11 items-center justify-center rounded-lg text-red-700 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100"><?= ikon('trash-2', 'size-4') ?><span class="sr-only"> Hapus baris <?= $teks ?></span></button></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                <?php endif ?>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
