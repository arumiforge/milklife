<?php
/**
 * Halaman depan. Hanya menyusun bagian dengan urutan K42; isi tiap bagian
 * ada di potongan `app/Views/beranda/`:
 *
 * | Potongan       | Bagian                                              | `id` jangkar |
 * |----------------|-----------------------------------------------------|--------------|
 * | `_kepala.php`  | Gambar kepala, logo, judul, teks, acara, keadaan, tombol | `pembuka` |
 * | `_lomba.php`   | Kelompok umur, nomor lomba, biaya                   | `lomba`      |
 * | `_jadwal.php`  | Jadwal                                              | `jadwal`     |
 * | `_unduhan.php` | Unduhan                                             | `unduhan`    |
 * | `_kontak.php`  | Narahubung, media sosial, tombol WA                 | `kontak`     |
 *
 * Footer ada di tata letak publik (`IsiHalamanDepan::kaki()`). Semua data
 * dari `BerandaController` (lihat `IsiHalamanDepan` untuk bentuk lariknya).
 *
 * @var CodeIgniter\View\View                       $this
 * @var array<string, string>                       $acara
 * @var array<string, mixed>                        $tampilan
 * @var App\Libraries\BukaTutupPendaftaran          $bukaTutup
 * @var list<array<string, mixed>>                  $tombol
 * @var list<array<string, mixed>>                  $kelompokUmur
 * @var list<array<string, mixed>>                  $jadwal
 * @var list<array<string, mixed>>                  $unduhan
 * @var list<array<string, string>>                 $narahubung
 * @var list<array<string, string>>                 $mediaSosial
 * @var string|null                                 $waTanya
 * @var string                                      $waBagikan
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?><?= esc(trim($acara['nama'] . ' ' . $acara['seri_tahun'])) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('beranda/_kepala', ['acara' => $acara, 'tampilan' => $tampilan, 'bukaTutup' => $bukaTutup, 'tombol' => $tombol], ['saveData' => false]) ?>
<?= view('beranda/_lomba', ['kelompokUmur' => $kelompokUmur], ['saveData' => false]) ?>
<?= view('beranda/_jadwal', ['jadwal' => $jadwal], ['saveData' => false]) ?>
<?= view('beranda/_unduhan', ['unduhan' => $unduhan], ['saveData' => false]) ?>
<?= view('beranda/_kontak', ['narahubung' => $narahubung, 'mediaSosial' => $mediaSosial, 'waTanya' => $waTanya, 'waBagikan' => $waBagikan], ['saveData' => false]) ?>
<?= $this->endSection() ?>
