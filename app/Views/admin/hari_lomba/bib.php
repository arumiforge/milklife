<?php
/**
 * Cetak nomor atlet (BIB) massal (F8-03, K137), tata letak `admin_tw` (F9-16).
 * Data dari `CetakBibController::index()`.
 *
 * Latar: unggah sendiri, atau pilih latar bawaan G9-01 (`latar_bawaan`) yang
 * hanya tampil bila berkasnya ada di `public/aset/gambar/bib/`.
 *
 * @var CodeIgniter\View\View $this
 * @var list<array{id: int, nama: string, atlet: int}> $kelompokUmur
 * @var int $jumlahSemua
 * @var list<array{kunci: string, nama: string, npsn: string, atlet: int}> $sekolahBib
 * @var App\Libraries\HariLomba\TampilanBib $tampilanBib
 * @var array<string, array{label: string, warna: string, alamat: string}> $latarBawaan
 * @var bool $kepalaDipilih
 * @var string $warnaDipilih
 */

use App\Libraries\HariLomba\TampilanBib;
use App\Libraries\Unggahan;

$latarBawaan ??= [];
$kunciBawaan  = $tampilanBib->kunciBawaan();
$adaUnggahan  = $tampilanBib->latar !== null && $kunciBawaan === null;
$tombolUnduh  = 'inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-900 hover:bg-gray-50 hover:text-primary-700';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Cetak Nomor Atlet (BIB)<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= view('admin/hari_lomba/_tab', ['aktif' => 'admin/nomor-atlet/cetak'], ['saveData' => false]) ?>

    <p class="max-w-3xl text-gray-700">
        Satu nomor atlet (BIB) seukuran setengah A4 mendatar (210 × 148,5 mm). PDF memuat dua BIB per halaman A4; potong di garis putus-putus.
        Gambar JPG berukuran 2480 × 1754 piksel (300 dpi), satu berkas per atlet, untuk dikirim ke percetakan.
        Isinya nomor atlet, nama atlet, sekolah, dan kelompok umur. Hanya atlet dari pendaftaran Lunas.
        Urutkan nomor atlet lebih dulu bila perlu (<a class="font-semibold text-primary-700 underline" href="<?= site_url('admin/atlet/nomor-dada') ?>">Urutkan nomor atlet</a>).
    </p>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-tampilan">
        <h2 class="text-lg font-bold text-gray-900" id="judul-tampilan">Tampilan BIB</h2>
        <div class="mt-4 grid gap-6 lg:grid-cols-2">
            <div class="min-w-0">
                <p class="mb-2 text-sm text-gray-600">Contoh hasil cetak<?= $jumlahSemua > 0 ? ' (atlet pertama)' : '' ?>:</p>
                <img src="<?= site_url('admin/nomor-atlet/cetak/contoh') ?>?v=<?= esc(md5((string) $tampilanBib->latar . (int) $tampilanBib->kepala . $tampilanBib->namaWarna), 'attr') ?>"
                    class="h-auto w-full rounded-lg border border-gray-200 bg-white" id="contoh-bib" alt="Contoh nomor atlet (BIB) dengan tampilan yang berlaku" width="2480" height="1754" loading="lazy">
            </div>
            <form action="<?= site_url('admin/nomor-atlet/cetak/tampilan') ?>" method="post" enctype="multipart/form-data" id="form-tampilan-bib" class="min-w-0 space-y-5">
                <?= csrf_field() ?>

                <?php if ($latarBawaan !== []) : ?>
                    <fieldset data-latar-bawaan>
                        <legend class="mb-1.5 text-sm font-semibold text-gray-900">Latar bawaan</legend>
                        <p class="mb-3 text-sm text-gray-600">Latar siap pakai dengan bagian tengah polos untuk nomor dan nama atlet.</p>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($latarBawaan as $kunci => $b) : ?>
                                <label class="group relative block cursor-pointer rounded-xl border-2 border-gray-200 p-1.5 hover:border-primary-300 has-[:checked]:border-primary-700 has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-primary-300" data-latar="<?= esc($kunci, 'attr') ?>">
                                    <input type="radio" class="sr-only" name="latar_bawaan" id="latar_bawaan_<?= esc($kunci, 'attr') ?>" value="<?= esc($kunci, 'attr') ?>"<?= $kunciBawaan === $kunci ? ' checked' : '' ?>>
                                    <img src="<?= esc($b['alamat'], 'attr') ?>" alt="" class="aspect-[2480/1754] w-full rounded-lg object-cover" width="2480" height="1754" loading="lazy">
                                    <span class="mt-1.5 flex flex-wrap items-center justify-between gap-1 px-1 text-sm">
                                        <span class="font-semibold text-gray-900"><?= esc($b['label']) ?></span>
                                        <span class="text-xs text-gray-600">tulisan <?= $b['warna'] === 'terang' ? 'Terang' : 'Gelap' ?></span>
                                    </span>
                                    <span class="absolute end-3 top-3 hidden size-7 items-center justify-center rounded-full bg-primary-700 text-white group-has-[:checked]:inline-flex"><?= ikon('check', 'size-4') ?></span>
                                </label>
                            <?php endforeach ?>
                        </div>
                        <label class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm text-gray-900">
                            <input type="radio" class="size-4 border-gray-300 text-primary-700 focus:ring-primary-300" name="latar_bawaan" id="latar_bawaan_tanpa" value=""<?= $kunciBawaan === null ? ' checked' : '' ?>>
                            <?= $adaUnggahan ? 'Pakai latar unggahan sendiri' : 'Tanpa latar bawaan' ?>
                        </label>
                    </fieldset>
                <?php endif ?>

                <div>
                    <label for="latar" class="mb-1.5 block text-sm font-semibold text-gray-900"><?= $adaUnggahan ? 'Ganti latar sendiri' : 'Unggah latar sendiri' ?></label>
                    <input type="file" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 file:me-3 file:min-h-11 file:cursor-pointer file:border-0 file:bg-primary-700 file:px-4 file:font-semibold file:text-white hover:file:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-100"
                        id="latar" name="latar" accept="image/jpeg,image/png,image/webp"
                        data-ukuran-maksimal="<?= TampilanBib::BATAS_LATAR ?>" aria-describedby="latar-bantuan">
                    <p id="latar-bantuan" class="mt-1.5 text-sm text-gray-600">
                        Gambar latar penuh satu BIB, dipakai semua BIB<?= $latarBawaan !== [] ? ', menggantikan latar bawaan' : '' ?>. JPG, PNG, atau WEBP, paling besar <?= Unggahan::ukuranTeks(TampilanBib::BATAS_LATAR) ?>.
                        Ukuran terbaik 2480 × 1754 piksel (mendatar); ukuran lain dipotong di tengah supaya pas.
                        Sisakan bagian tengah yang polos untuk nomor dan nama atlet.
                    </p>
                    <?php if ($tampilanBib->latar !== null) : ?>
                        <label class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm text-gray-900">
                            <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" value="1" id="hapus_latar" name="hapus_latar"
                                data-konfirmasi-centang="Latar BIB unggahan dihapus dari server saat tampilan disimpan, dan tidak bisa dikembalikan." data-konfirmasi-tombol="Hapus latar">
                            Hapus latar (kembali ke BIB putih)
                        </label>
                    <?php endif ?>
                </div>

                <div>
                    <label for="tampilkan_kepala" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                        <input type="checkbox" id="tampilkan_kepala" name="tampilkan_kepala" value="1" class="peer sr-only" aria-describedby="tampilkan_kepala-bantuan"<?= $kepalaDipilih ? ' checked' : '' ?>>
                        <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300" aria-hidden="true"></span>
                        <span class="text-sm font-semibold text-gray-900">Cetak kepala di atas latar (logo, nama acara, kelompok umur)</span>
                    </label>
                    <p id="tampilkan_kepala-bantuan" class="ms-14 text-sm text-gray-600">Matikan bila desain latar sudah memuat logo dan nama acara. Tanpa latar, kepala selalu dicetak.</p>
                </div>

                <fieldset>
                    <legend class="mb-1 text-sm font-semibold text-gray-900">Warna tulisan di atas latar</legend>
                    <div class="flex flex-wrap gap-x-6">
                        <?php foreach (TampilanBib::PILIHAN_WARNA as $nilai => $label) : ?>
                            <label class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-900">
                                <input class="size-4 border-gray-300 text-primary-700 focus:ring-primary-300" type="radio" name="warna_teks" id="warna_<?= $nilai ?>" value="<?= $nilai ?>"<?= ($warnaDipilih === $nilai || ($nilai === 'gelap' && ! isset(TampilanBib::PILIHAN_WARNA[$warnaDipilih]))) ? ' checked' : '' ?>>
                                <?= esc($label) ?>
                            </label>
                        <?php endforeach ?>
                    </div>
                    <p class="text-sm text-gray-600">Pilih Terang untuk latar gelap. Tanpa latar, tulisan selalu gelap.</p>
                </fieldset>

                <?= komponen('tombol', ['label' => 'Simpan tampilan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
            </form>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-sekolah-bib">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-gray-900" id="judul-sekolah-bib">Per sekolah</h2>
            <?php if ($sekolahBib !== []) : ?>
                <?= komponen('tombol', ['label' => 'Unduh semua sekolah (ZIP)', 'href' => site_url('admin/nomor-atlet/cetak/zip'), 'varian' => 'kedua', 'ikon' => 'file-archive', 'atribut' => ['id' => 'unduh-zip-semua']]) ?>
            <?php endif ?>
        </div>
        <?php if ($sekolahBib === []) : ?>
            <p class="text-gray-600" id="bib-sekolah-kosong">Belum ada sekolah dengan atlet ber-nomor atlet (BIB).</p>
        <?php else : ?>
            <p class="mb-3 text-sm text-gray-600">
                ZIP sekolah berisi gambar JPG per atlet dan PDF sekolah itu. ZIP semua sekolah berisi satu folder per sekolah
                (<?= count($sekolahBib) ?> sekolah, <?= $jumlahSemua ?> atlet) dan bisa butuh beberapa menit sampai terunduh.
            </p>
            <ul class="divide-y divide-gray-100 border-y border-gray-100" id="daftar-bib-sekolah">
                <?php foreach ($sekolahBib as $s) : ?>
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3" data-sekolah-bib="<?= esc($s['kunci'], 'attr') ?>">
                        <div class="min-w-0 flex-1 basis-48">
                            <p class="font-semibold break-words text-gray-900"><?= esc($s['nama']) ?></p>
                            <p class="text-sm text-gray-600"><?php if ($s['npsn'] !== '') : ?>NPSN <?= esc($s['npsn']) ?> · <?php endif ?><span data-jumlah-atlet><?= $s['atlet'] ?></span> atlet</p>
                        </div>
                        <div class="flex gap-2">
                            <a class="<?= $tombolUnduh ?>" href="<?= site_url('admin/nomor-atlet/cetak/sekolah/' . $s['kunci'] . '/pdf') ?>" aria-label="<?= esc('Unduh PDF BIB ' . $s['nama'], 'attr') ?>"><?= ikon('file-text', 'size-4') ?>PDF</a>
                            <a class="<?= $tombolUnduh ?>" href="<?= site_url('admin/nomor-atlet/cetak/sekolah/' . $s['kunci'] . '/zip') ?>" aria-label="<?= esc('Unduh ZIP gambar BIB ' . $s['nama'], 'attr') ?>"><?= ikon('file-archive', 'size-4') ?>ZIP</a>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-bib">
        <h2 class="mb-3 text-lg font-bold text-gray-900" id="judul-bib">Per kelompok umur (PDF)</h2>
        <?php if ($jumlahSemua === 0) : ?>
            <p class="text-gray-600" id="bib-kosong">Belum ada atlet dengan nomor atlet (BIB). Nomor atlet dibuat saat pendaftaran ditandai Lunas.</p>
        <?php else : ?>
            <div class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200" id="daftar-bib">
                <?php foreach ($kelompokUmur as $ku) : ?>
                    <?php if ($ku['atlet'] > 0) : ?>
                        <a class="flex min-h-12 flex-wrap items-center justify-between gap-2 px-4 py-2 hover:bg-gray-50" href="<?= site_url('admin/nomor-atlet/cetak/pdf') ?>?ku=<?= $ku['id'] ?>">
                            <span class="inline-flex items-center gap-2 font-semibold text-primary-800"><?= ikon('file-text', 'size-4') ?><?= esc($ku['nama']) ?></span>
                            <span class="text-sm text-gray-600"><?= $ku['atlet'] ?> atlet · <?= (int) ceil($ku['atlet'] / 2) ?> halaman</span>
                        </a>
                    <?php else : ?>
                        <div class="flex min-h-12 flex-wrap items-center justify-between gap-2 px-4 py-2 text-gray-500">
                            <span><?= esc($ku['nama']) ?></span><span class="text-sm">Belum ada atlet</span>
                        </div>
                    <?php endif ?>
                <?php endforeach ?>
                <a class="flex min-h-12 flex-wrap items-center justify-between gap-2 bg-gray-50 px-4 py-2 hover:bg-gray-100" href="<?= site_url('admin/nomor-atlet/cetak/pdf') ?>?ku=semua">
                    <span class="inline-flex items-center gap-2 font-bold text-primary-800"><?= ikon('file-text', 'size-4') ?>Semua kelompok umur</span>
                    <span class="text-sm text-gray-600"><?= $jumlahSemua ?> atlet · <?= (int) ceil($jumlahSemua / 2) ?> halaman</span>
                </a>
            </div>
            <p class="mt-3 text-sm text-gray-600">Berkas besar butuh beberapa detik sampai terunduh.</p>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
