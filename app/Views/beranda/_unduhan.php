<?php
/**
 * Bagian unduhan halaman depan (`#unduhan`). Diisi F3-03, bersama potongan
 * daftar unduhan yang juga dipakai halaman `/unduhan`. Sampai saat itu
 * potongan ini tidak menampilkan apa pun.
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
