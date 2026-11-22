<?php
/**
 * Halaman depan (tata letak baru `publik_tw`, F9-03). Hanya menyusun bagian;
 * isi tiap bagian ada di potongan `app/Views/beranda/`. Urutan bagian lama
 * mengikuti K42; bagian baru (angka acara, hasil) disisipkan sesudah jadwal.
 *
 * | Potongan       | Bagian                                                     | `id` jangkar |
 * |----------------|------------------------------------------------------------|--------------|
 * | `_kepala.php`  | Pembuka (K81): judul, info acara, keadaan, dua tombol,     | `pembuka`    |
 * |                | carousel foto, dan deret kartu kelompok umur               |              |
 * | `_lomba.php`   | Kelompok umur, nomor lomba, biaya                          | `lomba`      |
 * | `_jadwal.php`  | Jadwal                                                     | `jadwal`     |
 * | `_angka.php`   | Angka acara dari pendaftaran Lunas (bila sudah ada)        | `angka`      |
 * | `_hasil.php`   | Hasil terbaru dan juara umum (bila sudah ada yang terbit)  | `hasil`      |
 * | `_unduhan.php` | Unduhan                                                    | `unduhan`    |
 * | `_kontak.php`  | Narahubung, media sosial, tombol WA                        | `kontak`     |
 *
 * Saat sakelar "Tampilkan hasil di atas (hari lomba)" menyala, `_hasil.php`
 * pindah tepat di bawah pembuka (K143).
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
 * @var bool                                        $tampilkanBiaya
 * @var list<array<string, mixed>>                  $jadwal
 * @var list<array<string, mixed>>                  $unduhan
 * @var list<array<string, string>>                 $narahubung
 * @var list<array<string, string>>                 $mediaSosial
 * @var string|null                                 $waTanya
 * @var string                                      $waBagikan
 * @var list<array<string, mixed>>                  $carousel
 * @var array<string, mixed>|null                   $angka
 * @var array<string, mixed>|null                   $hasilTerbaru
 * @var list<array<string, mixed>>                  $faq
 * @var bool                                        $hasilDiAtas  sakelar hari lomba: hasil tepat di bawah pembuka (K143)
 */
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?><?= esc(trim($acara['nama'] . ' ' . $acara['seri_tahun'])) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('beranda/_kepala', ['acara' => $acara, 'tampilan' => $tampilan, 'bukaTutup' => $bukaTutup, 'tombol' => $tombol, 'kelompokUmur' => $kelompokUmur, 'carousel' => $carousel], ['saveData' => false]) ?>
<?php if ($hasilDiAtas) : ?>
    <?= view('beranda/_hasil', ['hasilTerbaru' => $hasilTerbaru], ['saveData' => false]) ?>
<?php endif ?>
<?= view('beranda/_pemisah', [], ['saveData' => false]) ?>
<?= view('beranda/_lomba', ['kelompokUmur' => $kelompokUmur, 'tampilkanBiaya' => $tampilkanBiaya], ['saveData' => false]) ?>
<?= view('beranda/_jadwal', ['jadwal' => $jadwal], ['saveData' => false]) ?>
<?= view('beranda/_angka', ['angka' => $angka, 'acara' => $acara], ['saveData' => false]) ?>
<?php if (! $hasilDiAtas) : ?>
    <?= view('beranda/_hasil', ['hasilTerbaru' => $hasilTerbaru], ['saveData' => false]) ?>
<?php endif ?>
<?= view('beranda/_pemisah', [], ['saveData' => false]) ?>
<?= view('beranda/_faq', ['faq' => $faq], ['saveData' => false]) ?>
<?= view('beranda/_unduhan', ['unduhan' => $unduhan], ['saveData' => false]) ?>
<?= view('beranda/_kontak', ['narahubung' => $narahubung, 'mediaSosial' => $mediaSosial, 'waTanya' => $waTanya, 'waBagikan' => $waBagikan], ['saveData' => false]) ?>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<?php if (count($carousel) > 1) : ?>
    <script src="<?= aset('js/carousel-beranda.js') ?>"></script>
<?php endif ?>
<script type="module" src="<?= aset('js/adegan-merek.js') ?>"></script>
<?= $this->endSection() ?>
