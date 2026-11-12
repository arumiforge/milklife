<?php
/**
 * Bagian jadwal halaman depan (`#jadwal`). Diisi F3-03. Sampai saat itu
 * potongan ini tidak menampilkan apa pun.
 *
 * Data yang tersedia (dari `IsiHalamanDepan::jadwal()`):
 *
 * @var list<array{id: int, judul: string, keterangan: string, waktu: string|null, tempat: string, tautan: string}> $jadwal
 *      Jadwal yang ditandai tampil, berurutan; [] bila belum ada.
 *      `waktu` 'Y-m-d H:i:s' (WIB) atau null (belum pasti); `keterangan`, `tempat`,
 *      `tautan` '' bila kosong; `tautan` selalu alamat http/https.
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - Bagian tidak ditampilkan bila `$jadwal === []`.
 * - `<section id="jadwal" aria-labelledby="judul-jadwal">` dengan `<h2 id="judul-jadwal">`.
 * - Waktu dengan `tanggal_id($waktu, true)` + " WIB"; null → "Waktu segera diumumkan".
 * - Tautan dibuka di tab baru dengan `rel="noopener noreferrer"`.
 * - Gaya hanya di wilayah "Bagian jadwal, unduhan, kontak (F3-03)" di `tema.css`.
 */
