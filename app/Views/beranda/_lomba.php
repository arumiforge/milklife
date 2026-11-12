<?php
/**
 * Bagian lomba halaman depan (`#lomba`): kelompok umur, nomor lomba, dan biaya.
 * Diisi F3-02. Sampai saat itu potongan ini tidak menampilkan apa pun.
 *
 * Data yang tersedia (dari `IsiHalamanDepan::kelompokUmur()`, lihat komentar
 * kelasnya untuk bentuk persisnya):
 *
 * @var list<array{
 *     id: int,
 *     nama: string,
 *     tahun_lahir: string,
 *     jenjang: string,
 *     biaya_individu: int|null,
 *     nomor_individu: list<array{id: int, nama: string, kategori: string, kategori_label: string}>,
 *     nomor_beregu: list<array{id: int, nama: string, kategori: string, kategori_label: string, jumlah_inti: int|null, batas_anggota: int|null, jumlah_putra: int|null, jumlah_putri: int|null, biaya_beregu: int|null}>
 * }> $kelompokUmur Kelompok umur aktif dengan nomor aktifnya; [] bila belum ada.
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - Bagian tidak ditampilkan bila `$kelompokUmur === []`.
 * - `<section id="lomba" aria-labelledby="judul-lomba">` dengan `<h2 id="judul-lomba">`.
 * - Uang dengan `rupiah()`; `null` → "Biaya segera diumumkan", `0` → "Gratis".
 * - Gaya hanya di wilayah "Bagian lomba (F3-02)" di `tema.css`.
 */
