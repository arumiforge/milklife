<?php
/**
 * Daftar ulang hari lomba (F8-03), tata letak `admin_tw`. Data dari
 * `DaftarUlangController::index()`.
 *
 * Pemindai kode QR (`aset/js/daftar-ulang.js`) mengisi kotak `id` dengan isi
 * kode QR lalu mengirim formulir pencarian; peramban tanpa `BarcodeDetector`
 * hanya menampilkan isian ID. Skrip itu hanya mengubah atribut `hidden`, jadi
 * tidak bergantung pada kelas tampilan; `Kemajuan` dipakai bila ada saja.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $masukan isian sebelumnya bila tidak ditemukan
 * @var string|null           $galat
 * @var array{pendaftaran: array<string, mixed>, id: int, atlet: list<array<string, mixed>>, digabung_dari: string|null}|null $hasil
 */

use App\Libraries\DataPendaftaran;

$p          = $hasil['pendaftaran'] ?? null;
$atlet      = $hasil['atlet'] ?? [];
$lunas      = $p !== null && $p['status'] === 'lunas';
$jumlahHadir = count(array_filter($atlet, static fn (array $a): bool => $a['hadir_pada'] !== null));
$tautan     = 'font-semibold text-primary-700 underline';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Daftar Ulang<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= view('admin/hari_lomba/_tab', ['aktif' => 'admin/daftar-ulang'], ['saveData' => false]) ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-cari" id="kotak-pindai">
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-bold text-gray-900" id="judul-cari">Pindai kode QR bukti pendaftaran</h2>
            <?= gambar_opsional('admin/daftar-ulang.webp', ['class' => 'hidden h-24 w-auto shrink-0 sm:block', 'data-gambar-admin' => 'daftar-ulang']) ?>
        </div>

        <div id="pemindai" class="mt-3 space-y-2" hidden>
            <?= komponen('tombol', ['label' => 'Pindai dengan kamera', 'ikon' => 'camera', 'ukuran' => 'besar', 'blok' => true, 'atribut' => ['id' => 'tombol-pindai']]) ?>
            <div id="bingkai-kamera" class="aspect-[4/3] overflow-hidden rounded-xl bg-gray-900" hidden>
                <video id="video-pindai" muted playsinline class="size-full object-cover"></video>
            </div>
            <?= komponen('tombol', ['label' => 'Berhenti memindai', 'varian' => 'kedua', 'ikon' => 'camera-off', 'blok' => true, 'atribut' => ['id' => 'tombol-berhenti', 'hidden' => true]]) ?>
            <p class="text-sm text-gray-600" id="status-pindai" role="status" aria-live="polite"></p>
        </div>

        <p class="mt-2 text-sm text-gray-600" id="tanpa-pemindai">
            Peramban ini tidak bisa memindai kode QR di halaman ini (paling mudah memakai Chrome di Android).
            Ketik ID pendaftaran di bawah, atau pindai kode QR dengan aplikasi kamera ponsel: halaman bukti akan menampilkan tombol "Buka daftar ulang".
        </p>

        <form action="<?= site_url('admin/daftar-ulang') ?>" method="get" id="formulir-cari" class="mt-4 flex gap-2">
            <label class="sr-only" for="isian-id">ID pendaftaran</label>
            <input class="block min-h-11 w-full min-w-0 flex-1 rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm" type="text" id="isian-id" name="id" value="<?= esc($masukan, 'attr') ?>" placeholder="Contoh: MAC2A0000126"
                autocomplete="off" autocapitalize="characters" spellcheck="false" required>
            <?= komponen('tombol', ['label' => 'Cari', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'search', 'kelas' => 'shrink-0']) ?>
        </form>
    </section>

    <?php if ($galat !== null) : ?>
        <div class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" id="galat-cari">
            <?= ikon('circle-alert', 'mt-0.5 shrink-0') ?>
            <p><?= esc($galat) ?></p>
        </div>
    <?php endif ?>

    <?php if ($p !== null) : ?>
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-sekolah" id="hasil-daftar-ulang">
            <?php if ($hasil['digabung_dari'] !== null) : ?>
                <div class="rounded-xl border border-primary-200 bg-primary-100 p-4 text-sm text-primary-900" id="keterangan-gabung">
                    ID <?= esc($hasil['digabung_dari']) ?> sudah digabung ke <?= esc($p['id_pendaftaran']) ?> (pendaftaran sekolah yang sama). Yang tampil di bawah ini pendaftaran gabungannya.
                </div>
            <?php endif ?>

            <div>
                <h2 class="text-xl font-bold break-words text-gray-900" id="judul-sekolah"><?= esc($p['nama_sekolah']) ?></h2>
                <p class="mt-1 text-gray-600">
                    <a href="<?= site_url('admin/pendaftaran/' . $hasil['id']) ?>" class="<?= $tautan ?>"><?= esc($p['id_pendaftaran']) ?></a>
                    <?php if ($p['kabupaten'] !== null && $p['kabupaten'] !== '') : ?> · <?= esc($p['kabupaten']) ?><?php endif ?>
                    <?php if ($p['npsn'] !== null) : ?> · NPSN <?= esc($p['npsn']) ?><?php endif ?>
                </p>
            </div>

            <p class="flex flex-wrap items-center gap-x-3 gap-y-2" id="status-pendaftaran">
                <?php if ($p['ditolak_semua']) : ?>
                    <?= komponen('lencana', ['label' => DataPendaftaran::LABEL_DITOLAK_SEMUA, 'warna' => 'abu', 'ikon' => 'user-x']) ?>
                <?php elseif ($lunas) : ?>
                    <?= komponen('lencana', ['label' => 'Lunas', 'warna' => 'hijau', 'ikon' => 'circle-check']) ?>
                <?php else : ?>
                    <?= komponen('lencana', ['label' => 'Menunggu', 'warna' => 'kuning', 'ikon' => 'hourglass']) ?>
                <?php endif ?>
                <?php if ($atlet !== []) : ?>
                    <span class="text-gray-700" id="jumlah-hadir"><strong><?= $jumlahHadir ?></strong> dari <?= count($atlet) ?> atlet hadir</span>
                <?php endif ?>
            </p>

            <?php if (! $lunas && ! $p['ditolak_semua']) : ?>
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="alert" id="peringatan-menunggu">
                    Pendaftaran ini belum Lunas, jadi atletnya belum punya nomor atlet (BIB) dan belum bisa ditandai hadir.
                    Periksa pembayarannya di <a href="<?= site_url('admin/pendaftaran/' . $hasil['id']) ?>" class="font-semibold underline">rincian pendaftaran</a>.
                </div>
            <?php elseif ($p['ditolak_semua']) : ?>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800" id="peringatan-ditolak">Semua atlet pendaftaran ini ditolak panitia, jadi tidak ada atlet yang ikut lomba.</div>
            <?php endif ?>

            <?php if ($lunas && $atlet !== [] && $jumlahHadir < count($atlet)) : ?>
                <form action="<?= site_url('admin/daftar-ulang/' . $hasil['id'] . '/hadir') ?>" method="post" id="formulir-hadir-semua">
                    <?= csrf_field() ?>
                    <input type="hidden" name="semua" value="1">
                    <?= komponen('tombol', ['label' => 'Tandai semua atlet hadir (' . (count($atlet) - $jumlahHadir) . ')', 'tipe' => 'submit', 'ukuran' => 'besar', 'blok' => true, 'ikon' => 'check-check']) ?>
                </form>
            <?php endif ?>

            <ul class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200" id="daftar-atlet">
                <?php foreach ($atlet as $a) : ?>
                    <?php $hadir = $a['hadir_pada'] !== null; ?>
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3<?= $hadir ? ' bg-green-50' : '' ?>" data-atlet="<?= $a['id'] ?>"<?= $hadir ? ' data-hadir' : '' ?>>
                        <div class="min-w-16 font-mono text-2xl font-bold text-gray-900"><?= $a['nomor_dada'] !== null ? esc($a['nomor_dada']) : '<span class="font-sans text-sm font-normal text-gray-500">Belum ada</span>' ?></div>
                        <div class="min-w-48 flex-1">
                            <div class="font-semibold break-words text-gray-900"><?= esc($a['nama']) ?></div>
                            <div class="text-sm text-gray-600"><?= esc($a['kelompok_umur']) ?> · <?= esc(implode(', ', $a['nomor_lomba'])) ?></div>
                            <?php if ($hadir) : ?>
                                <div class="mt-0.5 flex items-center gap-1 text-sm font-medium text-green-800"><?= ikon('circle-check', 'size-4 shrink-0') ?> Hadir <?= esc(tanggal_id($a['hadir_pada'], true)) ?><?= $a['hadir_oleh'] !== null ? ' oleh ' . esc($a['hadir_oleh']) : '' ?></div>
                            <?php endif ?>
                        </div>
                        <?php if ($lunas && ! $hadir) : ?>
                            <form action="<?= site_url('admin/daftar-ulang/' . $hasil['id'] . '/hadir') ?>" method="post" class="ms-auto">
                                <?= csrf_field() ?>
                                <input type="hidden" name="atlet[]" value="<?= $a['id'] ?>">
                                <?= komponen('tombol', ['label' => 'Tandai hadir', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
                            </form>
                        <?php elseif ($hadir) : ?>
                            <form action="<?= site_url('admin/daftar-ulang/' . $hasil['id'] . '/batal-hadir') ?>" method="post" class="ms-auto"
                                data-konfirmasi="<?= esc('Tanda hadir ' . $a['nama'] . ' dibatalkan.', 'attr') ?>" data-konfirmasi-judul="Batal hadir?" data-konfirmasi-tombol="Batal hadir">
                                <?= csrf_field() ?>
                                <input type="hidden" name="atlet" value="<?= $a['id'] ?>">
                                <?= komponen('tombol', ['label' => 'Batal hadir', 'tipe' => 'submit', 'varian' => 'hantu', 'ikon' => 'undo-2', 'ukuran' => 'kecil', 'kelas' => 'min-h-11']) ?>
                            </form>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
                <?php if ($atlet === []) : ?>
                    <li class="px-4 py-3 text-gray-600">Pendaftaran ini tidak punya atlet.</li>
                <?php endif ?>
            </ul>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/daftar-ulang.js') ?>"></script>
<?= $this->endSection() ?>
