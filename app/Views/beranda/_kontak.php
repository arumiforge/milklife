<?php
/**
 * Bagian kontak halaman depan (`#kontak`): narahubung, media sosial, tombol
 * tanya admin dan bagikan lewat WA. Diisi F3-03. Sampai saat itu potongan ini
 * tidak menampilkan apa pun.
 *
 * Data yang tersedia (dari `IsiHalamanDepan`):
 *
 * @var list<array{nama: string, nomor: string, alamat: string}> $narahubung
 *      `narahubung()`: `nomor` tampil '0812-3456-7890', `alamat` 'https://wa.me/62…'; [] bila belum ada.
 * @var list<array{label: string, alamat: string, alamat_ringkas: string, ikon: string}> $mediaSosial
 *      `mediaSosial()`: `alamat` http/https, `alamat_ringkas` 'instagram.com/contoh',
 *      `ikon` nama Bootstrap Icons tanpa "bi-"; [] bila belum ada.
 * @var string|null $waTanya   `waTanya()`: 'https://wa.me/62…?text=…', atau null bila nomornya belum diisi.
 * @var string      $waBagikan `waBagikan()`: 'https://wa.me/?text=…' (selalu ada).
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - `<section id="kontak" aria-labelledby="judul-kontak">` dengan `<h2 id="judul-kontak">`.
 * - Tombol tanya admin hanya bila `$waTanya !== null`; tombol bagikan selalu tampil.
 * - Tautan keluar dibuka di tab baru dengan `rel="noopener noreferrer"`.
 * - Gaya hanya di wilayah "Bagian jadwal, unduhan, kontak (F3-03)" di `tema.css`.
 */
