<?php
/**
 * Daftar album Galeri (`GET /admin/pengaturan/galeri`, F9-06), tata letak
 * `admin_tw`: sakelar halaman Galeri publik, lalu daftar album dengan sampul, tautan, dan
 * tombol Naik, Turun, Ubah, Sembunyikan/Tampilkan, dan Hapus (dengan konfirmasi).
 *
 * @var CodeIgniter\View\View       $this
 * @var list<array<string, mixed>>  $album    semua album menurut urutan
 * @var bool                        $tampil   sakelar `Galeri.tampil`
 * @var App\Libraries\Unggahan      $unggahan
 */

use App\Models\GaleriModel;

$alamat   = static fn (string $jalur = ''): string => site_url('admin/pengaturan/galeri' . ($jalur !== '' ? '/' . $jalur : ''));
$terakhir = count($album) - 1;
$penuh    = count($album) >= GaleriModel::BATAS;
// ikon-sprite: arrow-up arrow-down eye eye-off
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Galeri<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Galeri<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="flex max-w-3xl items-start gap-3 rounded-xl border border-primary-200 bg-primary-100 p-4 text-sm text-primary-900" role="note">
        <?= ikon('info', 'mt-0.5 shrink-0') ?>
        <div>
            Foto acara tidak diunggah ke web ini. Setiap album berisi sampul dan tautan ke album Instagram atau Google Drive panitia;
            pengunjung yang menekan album dibawa ke sana di tab baru. Album tanpa tautan atau yang disembunyikan tidak tampil untuk umum.
        </div>
    </div>

    <section id="sakelar-galeri" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-sakelar-galeri">
        <h2 id="judul-sakelar-galeri" class="text-lg font-bold text-gray-900">Halaman Galeri</h2>
        <p class="mt-1 text-sm text-gray-600">
            Bila dimatikan, alamat /galeri tidak bisa dibuka dan menu Galeri tidak tampil di halaman depan.
            <?php if ($tampil) : ?>
                Lihat di <a href="<?= site_url('galeri') ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary-700 underline">halaman Galeri</a>.
            <?php endif ?>
        </p>
        <form action="<?= $alamat('tampil') ?>" method="post" class="mt-3 flex flex-wrap items-center gap-3">
            <?= csrf_field() ?>
            <?= komponen('sakelar', ['label' => 'Tampilkan halaman Galeri', 'nama' => 'tampil_galeri', 'centang' => $tampil]) ?>
            <?= komponen('tombol', ['label' => 'Simpan sakelar Galeri', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="daftar-album" aria-labelledby="judul-daftar-album">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 id="judul-daftar-album" class="flex flex-wrap items-center gap-2 text-lg font-bold text-gray-900">
                    <?= ikon('images', 'text-primary-700') ?>
                    Album
                    <?= komponen('lencana', ['label' => (string) count($album), 'warna' => 'abu']) ?>
                </h2>
                <p class="mt-1 text-sm text-gray-600">Urutan di sini sama dengan urutan di halaman Galeri.</p>
            </div>
            <?php if (! $penuh) : ?>
                <?= komponen('tombol', ['label' => 'Tambah album', 'href' => $alamat('tambah'), 'ikon' => 'plus']) ?>
            <?php endif ?>
        </div>

        <?php if ($album === []) : ?>
            <p class="rounded-xl border border-gray-200 px-4 py-3 text-gray-600">Belum ada album. Tekan <strong class="font-semibold text-gray-900">Tambah album</strong> untuk menambahkan album pertama.</p>
        <?php else : ?>
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm text-gray-700">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-end">No</th>
                            <th scope="col" class="px-4 py-3">Sampul</th>
                            <th scope="col" class="px-4 py-3">Album</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($album as $i => $baris) : ?>
                            <?php
                            $id     = (int) $baris['id'];
                            $aktif  = (int) $baris['aktif'] === 1;
                            $judul  = (string) $baris['judul'];
                            $tautan = (string) $baris['tautan'];
                            $sampul = is_string($baris['sampul']) && $unggahan->ada($baris['sampul']) ? $baris['sampul'] : null;
                            ?>
                            <tr id="album-<?= $id ?>" data-album="<?= $id ?>" class="border-t border-gray-100<?= $aktif ? '' : ' text-gray-500' ?>">
                                <td class="px-4 py-3 text-end align-top text-gray-500"><?= $i + 1 ?></td>
                                <td class="px-4 py-3 align-top">
                                    <?php if ($sampul !== null) : ?>
                                        <img src="<?= base_url($sampul) ?>" alt="" width="96" height="64" class="h-16 w-24 max-w-none rounded-lg border border-gray-200 object-cover" loading="lazy">
                                    <?php else : ?>
                                        <span class="inline-flex h-16 w-24 items-center justify-center rounded-lg bg-gray-100 text-gray-500"><?= ikon('image', 'size-6', 'Tanpa sampul') ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="min-w-48 px-4 py-3 align-top">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold break-words <?= $aktif ? 'text-gray-900' : 'text-gray-500' ?>"><?= esc($judul) ?></span>
                                        <?php if (! $aktif) : ?>
                                            <?= komponen('lencana', ['label' => 'Disembunyikan', 'warna' => 'abu', 'ikon' => 'eye-off']) ?>
                                        <?php endif ?>
                                        <?php if ($tautan === '') : ?>
                                            <span class="inline-flex items-center gap-1 rounded-md bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800" data-tanpa-tautan><?= ikon('link-2-off', 'size-3.5') ?>Belum ada tautan</span>
                                        <?php endif ?>
                                    </div>
                                    <?php if ($tautan !== '') : ?>
                                        <a class="mt-1 block text-sm break-all text-primary-700 hover:underline" href="<?= esc($tautan, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($tautan) ?></a>
                                    <?php endif ?>
                                    <?php if ((string) $baris['keterangan'] !== '') : ?>
                                        <span class="mt-1 block text-sm text-gray-600"><?= esc((string) $baris['keterangan']) ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <?php foreach (['naik' => ['arrow-up', 'Naikkan', $i === 0], 'turun' => ['arrow-down', 'Turunkan', $i === $terakhir]] as $arah => [$ikon, $label, $mati]) : ?>
                                            <form action="<?= $alamat($id . '/' . $arah) ?>" method="post">
                                                <?= csrf_field() ?>
                                                <?= komponen('tombol', ['label' => '', 'ikon' => $ikon, 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 min-w-11', 'nonaktif' => $mati, 'atribut' => ['title' => $label, 'aria-label' => $label . ' ' . $judul]]) ?>
                                            </form>
                                        <?php endforeach ?>
                                        <?= komponen('tombol', ['label' => 'Ubah', 'href' => $alamat($id . '/ubah'), 'ikon' => 'pencil', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Ubah ' . $judul]]) ?>
                                        <form action="<?= $alamat($id . '/aktif') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <?= komponen('tombol', ['label' => $aktif ? 'Sembunyikan' : 'Tampilkan', 'ikon' => $aktif ? 'eye-off' : 'eye', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => ($aktif ? 'Sembunyikan ' : 'Tampilkan ') . $judul]]) ?>
                                        </form>
                                        <form action="<?= $alamat($id . '/hapus') ?>" method="post"
                                            data-konfirmasi="<?= esc('Album "' . $judul . '" dan sampulnya akan dihapus. Foto di Instagram atau Google Drive tidak ikut terhapus. Tindakan ini tidak bisa dibatalkan. Bila hanya ingin menyembunyikannya, tekan Sembunyikan.', 'attr') ?>"
                                            data-konfirmasi-judul="Hapus album?"
                                            data-konfirmasi-tombol="Hapus album">
                                            <?= csrf_field() ?>
                                            <?= komponen('tombol', ['label' => 'Hapus', 'ikon' => 'trash-2', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap text-red-700 hover:text-red-800', 'atribut' => ['aria-label' => 'Hapus ' . $judul]]) ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
