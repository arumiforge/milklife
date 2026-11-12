<?php
/**
 * Bagian unduhan halaman depan (`#unduhan`): daftar berkas (potongan
 * bersama `beranda/_daftar_unduhan`, juga dipakai halaman `/unduhan`) dan
 * tautan ke halaman semua unduhan.
 *
 * Data yang tersedia (dari `IsiHalamanDepan::unduhan()`):
 *
 * @var list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}> $unduhan
 *      Berkas yang ditandai tampil dan ada di disk, berurutan; [] bila belum ada.
 *      `alamat` sudah `base_url()`, `jenis` contoh 'PDF', `ukuran` contoh '1,5 MB'.
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan" dan "Halaman unduhan"):
 * - Bagian tidak ditampilkan bila `$unduhan === []`.
 * - `<section id="unduhan" aria-labelledby="judul-unduhan">` dengan `<h2 id="judul-unduhan">`.
 * - Tautan ke halaman semua unduhan: `site_url('unduhan')`.
 * - Gaya hanya di wilayah "Bagian jadwal, unduhan, kontak (F3-03)" di `tema.css`.
 */

if ($unduhan === []) {
    return;
}
?>
<section id="unduhan" class="bagian-tambahan bagian-unduhan" aria-labelledby="judul-unduhan">
    <div class="container">
        <h2 id="judul-unduhan" class="bagian-tambahan-judul"><i class="bi bi-download" aria-hidden="true"></i> Unduhan</h2>
        <p class="bagian-tambahan-pengantar">Flyer, petunjuk teknis, dan berkas lain dari panitia.</p>

        <?= view('beranda/_daftar_unduhan', ['unduhan' => $unduhan, 'tingkatJudul' => 'h3'], ['saveData' => false]) ?>

        <p class="unduhan-semua">
            <a class="btn btn-outline-primary" href="<?= site_url('unduhan') ?>">
                Lihat semua unduhan <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </p>
    </div>
</section>
