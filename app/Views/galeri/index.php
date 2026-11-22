<?php
/**
 * Halaman Galeri publik (`/galeri`, F9-06), tata letak Tailwind `publik_tw`.
 * Pengendali: `App\Controllers\GaleriController`.
 *
 * Setiap album satu kartu bersampul yang seluruhnya bisa ditekan dan membuka
 * tautan album (Instagram atau Google Drive panitia) di tab baru dengan
 * `rel="noopener noreferrer"`. Album tanpa sampul memakai latar gradien
 * merek. Bila belum ada album, tampil keadaan kosong (ilustrasi G9-04 hanya
 * bila berkasnya ada).
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara `IsiHalamanDepan::acara()['nama']`
 * @var list<array{id: int, judul: string, keterangan: string, tautan: string, sampul: string|null, tempat: string}> $album
 */
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Galeri – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Album foto acara dari panitia.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_judul_halaman_tw', ['inggris' => 'Gallery', 'judul' => 'Galeri', 'ikon' => 'images', 'gambar' => 'judul/galeri.webp', 'teks' => 'Foto lomba dari panitia. Tekan album untuk membukanya di Instagram atau Google Drive.'], ['saveData' => false]) ?>

<div class="mx-auto max-w-screen-xl px-4 py-10">
    <?php if ($album === []) : ?>
        <div data-galeri-kosong>
            <?= komponen('kosong', [
                'gambar' => 'gambar/keadaan/kosong-data.webp',
                'ikon'   => 'images',
                'judul'  => 'Belum ada album foto',
                'teks'   => 'Panitia belum mengunggah album. Foto acara akan muncul di sini setelah tersedia, jadi buka lagi halaman ini nanti.',
                'aksi'   => komponen('tombol', ['label' => 'Kembali ke beranda', 'href' => site_url('/'), 'varian' => 'kedua', 'ikon' => 'arrow-left']),
            ]) ?>
        </div>
    <?php else : ?>
        <h2 class="sr-only">Daftar album</h2>
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-daftar-galeri>
            <?php foreach ($album as $i => $a) : ?>
                <?php $tombol = $a['tempat'] !== '' ? 'Buka di ' . $a['tempat'] : 'Buka album'; ?>
                <li class="min-w-0" data-album-galeri="<?= $a['id'] ?>">
                    <a href="<?= esc($a['tautan'], 'attr') ?>" target="_blank" rel="noopener noreferrer"
                        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-lg focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-300 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        <span class="relative block aspect-[4/3] overflow-hidden bg-primary-100">
                            <?php if ($a['sampul'] !== null) : ?>
                                <img src="<?= base_url($a['sampul']) ?>" alt="" class="size-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                                    width="1200" height="900" decoding="async"<?= $i > 2 ? ' loading="lazy"' : '' ?>>
                            <?php else : ?>
                                <span class="flex size-full items-center justify-center bg-linear-to-br from-primary-700 to-oranye-600 text-white" data-tanpa-sampul><?= ikon('images', 'size-12 opacity-80') ?></span>
                            <?php endif ?>
                        </span>
                        <span class="flex flex-1 flex-col gap-2 p-5">
                            <span class="text-lg font-bold text-gray-900 break-words group-hover:text-primary-700"><?= esc($a['judul']) ?></span>
                            <?php if ($a['keterangan'] !== '') : ?>
                                <span class="text-gray-600 break-words"><?= esc($a['keterangan']) ?></span>
                            <?php endif ?>
                            <span class="mt-auto inline-flex min-h-11 items-center gap-1.5 pt-2 font-semibold text-primary-700">
                                <?= esc($tombol) ?><?= ikon('external-link', 'size-4') ?>
                                <span class="sr-only">(terbuka di tab baru)</span>
                            </span>
                        </span>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
