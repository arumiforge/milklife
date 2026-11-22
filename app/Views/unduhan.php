<?php
/**
 * Halaman publik semua berkas unduhan (`/unduhan`, K40), tata letak baru
 * `publik_tw` (F9-03). Pengendali: `App\Controllers\UnduhanController`.
 *
 * Daftar memakai potongan bersama `beranda/_daftar_unduhan` (sama dengan
 * bagian unduhan halaman depan). Bila belum ada berkas, tampil keadaan kosong.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara `IsiHalamanDepan::acara()['nama']`
 * @var list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}> $unduhan
 */
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Unduhan – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Flyer, petunjuk teknis, dan berkas lain dari panitia.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_judul_halaman_tw', ['inggris' => 'Downloads', 'alis' => $namaAcara, 'judul' => 'Unduhan', 'ikon' => 'download', 'gambar' => 'judul/unduhan.webp', 'teks' => 'Flyer, petunjuk teknis, dan templat dari panitia. Tekan Lihat untuk membuka berkas sebelum mengunduh.'], ['saveData' => false]) ?>

<div class="mx-auto max-w-screen-xl px-4 py-10">
    <?php if ($unduhan === []) : ?>
        <div data-unduhan-kosong>
            <?= komponen('kosong', [
                'gambar' => 'gambar/keadaan/kosong-data.webp',
                'ikon'   => 'folder-open',
                'judul'  => 'Belum ada berkas unduhan',
                'teks'   => 'Panitia belum menaruh berkas di sini. Flyer dan petunjuk teknis akan muncul di halaman ini setelah tersedia. Silakan kembali lagi nanti.',
                'aksi'   => komponen('tombol', ['label' => 'Kembali ke beranda', 'href' => site_url('/'), 'varian' => 'kedua', 'ikon' => 'arrow-left']),
            ]) ?>
        </div>
    <?php else : ?>
        <?= view('beranda/_daftar_unduhan', ['unduhan' => $unduhan, 'tingkatJudul' => 'h2'], ['saveData' => false]) ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<?php if (App\Libraries\BerkasUnduhan::adaPratinjau($unduhan)) : ?>
    <script src="<?= aset('js/unduhan-pratinjau.js') ?>" defer></script>
<?php endif ?>
<?= $this->endSection() ?>
