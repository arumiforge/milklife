<?php
/**
 * Halaman Peserta publik (`/peserta`, F9-12), tata letak Tailwind `publik_tw`.
 *
 * Formulir saringan memakai GET, jadi tanpa JavaScript halaman tetap bisa
 * dicari dan dibuka per halaman (render server). Dengan JavaScript,
 * `aset/js/peserta.js` mengambil `/peserta/data` (JSON) saat mengetik atau
 * memilih, menampilkan kerangka selama memuat, dan mengisi kartu (ponsel) dan
 * tabel (layar lebar) dari `<template>` di bawah.
 *
 * Isi hanya nama, sekolah, nomor atlet, kelompok umur, putra/putri, dan nomor
 * lomba dari pendaftaran Lunas (`App\Libraries\PesertaPublik`).
 *
 * Ilustrasi nomor lomba G9-05 (K144, F9-17): saat saringan Nomor lomba
 * dipilih, gambarnya tampil di bawah formulir. Nama nomor dipetakan ke berkas
 * oleh `IlustrasiBeranda::nomor()` (milik F9-03); pilihan tanpa berkas tidak
 * diberi gambar (K41). Alamatnya di `data-gambar` tiap `<option>`.
 *
 * @var CodeIgniter\View\View $this
 * @var string $namaAcara
 * @var array{q: string, ku: int|null, nomor: int|null, jk: string|null, halaman: int} $saring
 * @var list<array{id: int, nama: string, nomor: list<array{id: int, nama: string}>}> $pilihan
 * @var array{baris: list<array<string, mixed>>, total: int, halaman: int, jumlah_halaman: int, per_halaman: int}|null $hasil null bila pembatas laju menolak
 */

use App\Controllers\PesertaController;
use App\Libraries\IlustrasiBeranda;
use App\Libraries\PesertaPublik;

$kelasPilihan = 'block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
$disaring     = $saring['q'] !== '' || $saring['ku'] !== null || $saring['nomor'] !== null || $saring['jk'] !== null;
$kosongRow    = ['nama' => '', 'sekolah' => '', 'nomor_atlet' => '', 'kelompok_umur' => '', 'jenis_kelamin' => '', 'nomor' => []];

// Ilustrasi G9-05 per nomor lomba (id => alamat), hanya yang berkasnya ada.
$gambarNomor = [];
$namaNomor   = [];

foreach ($pilihan as $ku) {
    foreach ($ku['nomor'] as $n) {
        $namaNomor[$n['id']] = $n['nama'];
        $alamat              = IlustrasiBeranda::nomor($n['nama']);

        if ($alamat !== null) {
            $gambarNomor[$n['id']] = $alamat;
        }
    }
}
$gambarTerpilih = $saring['nomor'] === null ? null : ($gambarNomor[$saring['nomor']] ?? null);

// Alamat halaman lain dengan saringan yang sama.
$alamatHalaman = static function (int $halaman) use ($saring): string {
    $q = array_filter([
        'q'       => $saring['q'],
        'ku'      => $saring['ku'],
        'nomor'   => $saring['nomor'],
        'jk'      => $saring['jk'],
        'halaman' => $halaman > 1 ? $halaman : null,
    ], static fn ($v): bool => $v !== null && $v !== '');

    return site_url('peserta') . ($q === [] ? '' : '?' . http_build_query($q));
};

$keterangan = static function (?array $hasil, bool $disaring): string {
    if ($hasil === null) {
        return '';
    }

    if ($hasil['total'] === 0) {
        return $disaring ? 'Tidak ada atlet yang cocok.' : 'Belum ada atlet.';
    }

    $awal  = ($hasil['halaman'] - 1) * $hasil['per_halaman'] + 1;
    $akhir = $awal + count($hasil['baris']) - 1;

    return 'Menampilkan ' . number_format($awal, 0, ',', '.') . '–' . number_format($akhir, 0, ',', '.')
        . ' dari ' . number_format($hasil['total'], 0, ',', '.') . ' atlet';
};
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Peserta – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('kepala') ?>
    <meta name="robots" content="noindex">
<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<section class="border-b border-primary-100 bg-white" aria-labelledby="judul-peserta">
    <div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12">
        <p class="text-sm font-semibold text-oranye-600"><?= esc($namaAcara) ?></p>
        <h1 id="judul-peserta" class="mt-1 text-3xl font-extrabold text-primary-900 sm:text-4xl">Peserta</h1>
        <p class="mt-3 max-w-2xl text-lg text-gray-700">Atlet dari sekolah yang pembayarannya sudah lunas. Cari namamu untuk melihat nomor atlet dan nomor lombamu.</p>

        <form action="<?= site_url('peserta') ?>" method="get" class="mt-6 grid gap-4 md:grid-cols-12 md:items-end" role="search" data-saring-peserta data-alamat-data="<?= site_url('peserta/data') ?>">
            <div class="md:col-span-12 lg:col-span-5">
                <label for="cari-peserta" class="mb-1.5 block text-sm font-semibold text-gray-900">Cari nama, sekolah, atau nomor atlet</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-500"><?= ikon('search', 'size-5') ?></span>
                    <input type="search" id="cari-peserta" name="q" value="<?= esc($saring['q'], 'attr') ?>" maxlength="<?= PesertaPublik::PANJANG_CARI ?>" class="block min-h-12 w-full rounded-xl border border-gray-300 bg-white ps-11 pe-4 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100" placeholder="Contoh: Budi, SD Negeri 1, atau 1023" autocomplete="off" enterkeyhint="search">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 md:col-span-9 md:grid-cols-3 lg:col-span-5">
                <div>
                    <label for="saring-ku" class="mb-1.5 block text-sm font-semibold text-gray-900">Kelompok umur</label>
                    <select id="saring-ku" name="ku" class="<?= $kelasPilihan ?>">
                        <option value="">Semua</option>
                        <?php foreach ($pilihan as $ku) : ?>
                            <option value="<?= $ku['id'] ?>"<?= $saring['ku'] === $ku['id'] ? ' selected' : '' ?>><?= esc($ku['nama']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div>
                    <label for="saring-jk" class="mb-1.5 block text-sm font-semibold text-gray-900">Putra/putri</label>
                    <select id="saring-jk" name="jk" class="<?= $kelasPilihan ?>">
                        <option value="">Semua</option>
                        <?php foreach (PesertaPublik::JENIS_KELAMIN as $kode => $label) : ?>
                            <option value="<?= $kode ?>"<?= $saring['jk'] === $kode ? ' selected' : '' ?>><?= $label ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label for="saring-nomor" class="mb-1.5 block text-sm font-semibold text-gray-900">Nomor lomba</label>
                    <select id="saring-nomor" name="nomor" class="<?= $kelasPilihan ?>">
                        <option value="">Semua</option>
                        <?php foreach ($pilihan as $ku) : ?>
                            <optgroup label="<?= esc($ku['nama'], 'attr') ?>" data-ku="<?= $ku['id'] ?>">
                                <?php foreach ($ku['nomor'] as $n) : ?>
                                    <option value="<?= $n['id'] ?>"<?= $saring['nomor'] === $n['id'] ? ' selected' : '' ?><?= isset($gambarNomor[$n['id']]) ? ' data-gambar="' . esc($gambarNomor[$n['id']], 'attr') . '"' : '' ?>><?= esc($n['nama']) ?></option>
                                <?php endforeach ?>
                            </optgroup>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 md:col-span-3 lg:col-span-2">
                <?= komponen('tombol', ['label' => 'Cari', 'tipe' => 'submit', 'ikon' => 'search', 'kelas' => 'flex-1', 'atribut' => ['data-tombol-cari' => '']]) ?>
                <span<?= $disaring ? '' : ' class="hidden"' ?> data-hapus-saringan>
                    <?= komponen('tombol', ['label' => 'Hapus', 'href' => site_url('peserta'), 'varian' => 'kedua', 'atribut' => ['aria-label' => 'Hapus pencarian dan saringan']]) ?>
                </span>
            </div>
        </form>

        <?php if ($gambarNomor !== []) : ?>
            <div class="mt-5 flex items-center gap-4 rounded-2xl border border-primary-100 bg-primary-50 p-3 pe-5<?= $gambarTerpilih === null ? ' hidden' : '' ?>" data-ilustrasi-nomor>
                <img<?= $gambarTerpilih === null ? '' : ' src="' . esc($gambarTerpilih, 'attr') . '"' ?> alt="" width="512" height="512" class="size-16 shrink-0 sm:size-20" data-gambar-nomor>
                <p class="min-w-0">
                    <span class="block text-xs font-semibold tracking-wide text-primary-700 uppercase">Nomor lomba</span>
                    <span class="block text-lg font-bold break-words text-primary-900" data-nama-nomor><?= esc($gambarTerpilih === null ? '' : ($namaNomor[$saring['nomor']] ?? '')) ?></span>
                </p>
            </div>
        <?php endif ?>
    </div>
</section>

<div class="mx-auto max-w-screen-xl px-4 py-6">
    <?php if ($hasil === null) : ?>
        <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => esc(PesertaController::PESAN_BATAS)]) ?>
    <?php else : ?>
        <p class="mb-4 min-h-6 text-sm font-semibold text-gray-700" aria-live="polite" data-keterangan-peserta><?= esc($keterangan($hasil, $disaring)) ?></p>

        <div class="hidden" data-pesan-galat>
            <?= komponen('peringatan', ['jenis' => 'bahaya', 'isi' => '<span data-teks-galat>Daftar peserta gagal dimuat. Periksa sambungan internet, lalu coba lagi.</span>']) ?>
        </div>

        <div class="hidden" data-kerangka-peserta>
            <?= komponen('kerangka', ['jenis' => 'tabel', 'baris' => 8, 'label' => 'Memuat daftar peserta…']) ?>
        </div>

        <div data-wadah-peserta<?= $hasil['total'] === 0 ? ' class="hidden"' : '' ?>>
            <ul class="grid gap-3 md:hidden" data-daftar-kartu>
                <?php foreach ($hasil['baris'] as $p) : ?>
                    <?= view('peserta/_kartu', ['p' => $p, 'bentuk' => 'kartu'], ['saveData' => false]) ?>
                <?php endforeach ?>
            </ul>

            <div class="hidden overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-lembut md:block">
                <table class="w-full text-left text-sm text-gray-700">
                    <caption class="sr-only">Daftar peserta</caption>
                    <thead class="bg-gray-50 text-xs font-semibold tracking-wide text-gray-700 uppercase">
                        <tr>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">No. atlet</th>
                            <th scope="col" class="px-4 py-3">Nama</th>
                            <th scope="col" class="px-4 py-3">Sekolah</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Kelompok umur</th>
                            <th scope="col" class="px-4 py-3">Nomor lomba</th>
                        </tr>
                    </thead>
                    <tbody data-daftar-baris>
                        <?php foreach ($hasil['baris'] as $p) : ?>
                            <?= view('peserta/_kartu', ['p' => $p, 'bentuk' => 'baris'], ['saveData' => false]) ?>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="<?= $hasil['total'] === 0 && $disaring ? '' : 'hidden' ?>" data-kosong-cari>
            <?= komponen('kosong', ['gambar' => 'gambar/keadaan/tidak-ketemu.webp', 'ikon' => 'search-x', 'judul' => 'Atlet tidak ditemukan', 'teks' => 'Periksa ejaan nama, coba sebagian nama saja, atau hapus saringan. Atlet baru tampil setelah pembayaran sekolahnya dinyatakan lunas oleh panitia.']) ?>
        </div>
        <div class="<?= $hasil['total'] === 0 && ! $disaring ? '' : 'hidden' ?>" data-kosong-semua>
            <?= komponen('kosong', ['gambar' => 'gambar/keadaan/kosong-data.webp', 'ikon' => 'users', 'judul' => 'Belum ada peserta', 'teks' => 'Daftar peserta tampil setelah pembayaran sekolah dinyatakan lunas oleh panitia.']) ?>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-3<?= $hasil['jumlah_halaman'] > 1 ? '' : ' hidden' ?>" aria-label="Halaman daftar peserta" data-halaman-peserta>
            <?= komponen('tombol', ['label' => 'Sebelumnya', 'href' => $alamatHalaman(max(1, $hasil['halaman'] - 1)), 'varian' => 'kedua', 'ikon' => 'chevron-left', 'nonaktif' => $hasil['halaman'] <= 1, 'atribut' => ['data-ke-halaman' => 'sebelum', 'rel' => 'prev']]) ?>
            <p class="order-first w-full text-center text-sm text-gray-700 sm:order-none sm:w-auto" data-posisi-halaman>Halaman <?= $hasil['halaman'] ?> dari <?= $hasil['jumlah_halaman'] ?></p>
            <?= komponen('tombol', ['label' => 'Berikutnya', 'href' => $alamatHalaman(min($hasil['jumlah_halaman'], $hasil['halaman'] + 1)), 'varian' => 'kedua', 'ikon' => 'chevron-right', 'ikonKanan' => true, 'nonaktif' => $hasil['halaman'] >= $hasil['jumlah_halaman'], 'atribut' => ['data-ke-halaman' => 'berikut', 'rel' => 'next']]) ?>
        </nav>

        <p class="mt-8 text-sm text-gray-600">
            Nama atau nomor lomba salah? Hubungi panitia lewat halaman <a href="<?= site_url('bantuan') ?>" class="inline-flex min-h-11 items-center font-semibold text-primary-700 underline">Bantuan</a>.
        </p>

        <template data-templat-kartu><?= view('peserta/_kartu', ['p' => $kosongRow, 'bentuk' => 'kartu'], ['saveData' => false]) ?></template>
        <template data-templat-baris><table><tbody><?= view('peserta/_kartu', ['p' => $kosongRow, 'bentuk' => 'baris'], ['saveData' => false]) ?></tbody></table></template>
        <template data-templat-nomor><li class="rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-800"></li></template>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/peserta.js') ?>"></script>
<?= $this->endSection() ?>
