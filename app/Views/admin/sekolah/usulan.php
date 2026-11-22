<?php
/**
 * Usulan nama sekolah dari pendaftar (K99): `admin/sekolah/usulan`.
 * Data dari `SekolahController::usulan()` (`UsulanNamaSekolahModel::daftarLengkap()`).
 * Tata letak `admin_tw` (F9-15).
 *
 * Usulan yang menunggu punya formulir Setujui (nama boleh dirapikan dulu)
 * dan Tolak (alasan pilihan), keduanya memakai jendela konfirmasi
 * `aset/js/konfirmasi.js`. Usulan yang sudah diputuskan tampil sebagai riwayat.
 *
 * @var CodeIgniter\View\View      $this
 * @var list<array<string, mixed>> $usulan
 */

use App\Models\SekolahModel;
use App\Models\UsulanNamaSekolahModel;

$menunggu   = array_values(array_filter($usulan, static fn (array $u): bool => $u['status'] === UsulanNamaSekolahModel::MENUNGGU));
$diputuskan = array_values(array_filter($usulan, static fn (array $u): bool => $u['status'] !== UsulanNamaSekolahModel::MENUNGGU));

$kelasLabel = 'mb-1.5 block text-sm font-semibold text-gray-900';
$kelasKotak = 'block min-h-11 w-full min-w-0 flex-1 rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
$kelasTh    = 'border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap text-gray-600';
$kelasTd    = 'px-4 py-3 align-top';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?>Usulan nama sekolah<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [['label' => 'Sekolah', 'alamat' => 'admin/sekolah'], ['label' => 'Usulan nama sekolah']]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <p class="max-w-3xl text-gray-700">
        Pendaftar bisa mengusulkan nama sekolah yang lebih lengkap saat mendaftar. Pendaftarannya langsung memakai nama usulan.
        Bila disetujui, nama di data sekolah dan semua pendaftaran dari sekolah itu ikut berganti. Bila ditolak, pendaftaran pengusul
        kembali memakai nama dari data sekolah.
    </p>

    <section class="space-y-4" aria-labelledby="judul-usulan-menunggu">
        <h2 id="judul-usulan-menunggu" class="text-lg font-bold text-gray-900">Menunggu keputusan (<?= count($menunggu) ?>)</h2>

        <?php if ($menunggu === []) : ?>
            <p class="text-gray-600" id="usulan-kosong">Belum ada usulan yang menunggu.</p>
        <?php endif ?>

        <div class="grid gap-4 xl:grid-cols-2">
            <?php foreach ($menunggu as $u) : ?>
                <?php $id = (int) $u['id']; ?>
                <article class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" data-usulan="<?= $id ?>">
                    <div class="space-y-1">
                        <p class="text-sm text-gray-600">
                            NPSN <?= esc($u['npsn']) ?>
                            <?php if ($u['sekolah_id'] !== null) : ?>
                                · <?= esc(SekolahModel::lokasi($u)) ?>
                            <?php endif ?>
                            · diusulkan <?= esc(tanggal_id((string) $u['dibuat_pada'], true)) ?>
                            <?php if ($u['id_pendaftaran'] !== null) : ?>
                                · pendaftaran <a class="font-semibold text-primary-700 underline hover:no-underline" href="<?= site_url('admin/pendaftaran/' . (int) $u['pendaftaran_id']) ?>"><?= esc($u['id_pendaftaran']) ?></a>
                            <?php endif ?>
                        </p>
                        <p class="text-gray-700">Nama di data sekolah: <strong class="text-gray-900"><?= esc($u['nama_sekarang'] ?? $u['nama_lama']) ?></strong></p>
                        <p class="text-gray-700">Usulan pendaftar: <strong class="text-gray-900"><?= esc($u['nama_usulan']) ?></strong></p>
                    </div>

                    <form action="<?= site_url('admin/sekolah/usulan/' . $id . '/setujui') ?>" method="post"
                        data-konfirmasi="Nama sekolah NPSN <?= esc($u['npsn'], 'attr') ?> akan diganti di data sekolah dan di semua pendaftaran dari sekolah itu."
                        data-konfirmasi-judul="Setujui usulan nama?" data-konfirmasi-tombol="Setujui" data-konfirmasi-jenis="utama">
                        <?= csrf_field() ?>
                        <label class="<?= $kelasLabel ?>" for="usulan-<?= $id ?>-nama">Nama yang dipakai (boleh dirapikan)</label>
                        <div class="flex flex-wrap gap-2 sm:flex-nowrap">
                            <input type="text" class="<?= $kelasKotak ?>" id="usulan-<?= $id ?>-nama" name="nama" maxlength="<?= SekolahModel::PANJANG['nama'] ?>"
                                value="<?= esc($u['nama_usulan'], 'attr') ?>" required>
                            <?= komponen('tombol', ['label' => 'Setujui', 'tipe' => 'submit', 'ikon' => 'check', 'kelas' => 'shrink-0']) ?>
                        </div>
                    </form>

                    <form action="<?= site_url('admin/sekolah/usulan/' . $id . '/tolak') ?>" method="post"
                        data-konfirmasi="Usulan ditolak. Pendaftaran pengusul kembali memakai nama dari data sekolah."
                        data-konfirmasi-judul="Tolak usulan nama?" data-konfirmasi-tombol="Tolak">
                        <?= csrf_field() ?>
                        <label class="<?= $kelasLabel ?>" for="usulan-<?= $id ?>-alasan">Alasan menolak (pilihan)</label>
                        <div class="flex flex-wrap gap-2 sm:flex-nowrap">
                            <input type="text" class="<?= $kelasKotak ?>" id="usulan-<?= $id ?>-alasan" name="alasan" maxlength="<?= UsulanNamaSekolahModel::ALASAN_MAKSIMAL ?>">
                            <?= komponen('tombol', ['label' => 'Tolak', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'x', 'kelas' => 'shrink-0 text-red-700!']) ?>
                        </div>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($diputuskan !== []) : ?>
        <section class="space-y-4" aria-labelledby="judul-usulan-riwayat">
            <h2 id="judul-usulan-riwayat" class="text-lg font-bold text-gray-900">Sudah diputuskan</h2>
            <div class="relative overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-lembut">
                <table class="w-full text-left text-sm text-gray-700" data-tabel-klien>
                    <caption class="sr-only">Riwayat usulan nama sekolah</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="<?= $kelasTh ?>">NPSN</th>
                            <th scope="col" class="<?= $kelasTh ?>">Nama lama</th>
                            <th scope="col" class="<?= $kelasTh ?>">Usulan</th>
                            <th scope="col" class="<?= $kelasTh ?>">Keputusan</th>
                            <th scope="col" class="<?= $kelasTh ?>">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diputuskan as $u) : ?>
                            <tr class="border-b border-gray-100 last:border-b-0">
                                <td class="<?= $kelasTd ?> whitespace-nowrap"><?= esc($u['npsn']) ?></td>
                                <td class="<?= $kelasTd ?>"><?= esc($u['nama_lama']) ?></td>
                                <td class="<?= $kelasTd ?>"><?= esc($u['nama_usulan']) ?></td>
                                <td class="<?= $kelasTd ?>">
                                    <?php if ($u['status'] === UsulanNamaSekolahModel::DISETUJUI) : ?>
                                        <?= komponen('lencana', ['label' => 'Disetujui', 'warna' => 'hijau']) ?> <?= esc((string) $u['nama_disetujui']) ?>
                                    <?php else : ?>
                                        <?= komponen('lencana', ['label' => 'Ditolak', 'warna' => 'abu']) ?> <?= esc((string) ($u['alasan'] ?? '')) ?>
                                    <?php endif ?>
                                </td>
                                <td class="<?= $kelasTd ?> whitespace-nowrap"><?= esc(tanggal_id((string) $u['diputuskan_pada'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
