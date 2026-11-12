# F3-03 Jadwal, unduhan, dan kontak

| | |
|---|---|
| Status | dikerjakan |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Pengunjung melihat jadwal, mengunduh berkas, menghubungi narahubung, membuka media sosial, bertanya ke admin, dan membagikan halaman lewat WA.

## Hasil

- [ ] `_jadwal.php`: jadwal yang ditampilkan admin, berurutan, dengan waktu (WIB), tempat, tautan, dan keterangan
- [ ] `_unduhan.php` dan potongan daftar unduhan bersama; bagian di halaman depan bertautan ke `/unduhan`
- [ ] Halaman `/unduhan` (`App\Controllers\UnduhanController`, `app/Views/unduhan.php`) dengan pesan ramah bila kosong; menu Unduhan di kepala aktif (K40)
- [ ] `_kontak.php`: narahubung (tautan WA), media sosial (ikon, alamat ringkas), tombol tanya admin lewat WA (bila nomornya diisi), tombol bagikan ke WA
- [ ] Bagian tanpa isi tidak tampil
- [ ] Gaya di wilayah jadwal–unduhan–kontak `tema.css` saja
- [ ] Uji `tests/fitur/HalamanDepanJadwalKontakTest.php` dan `tests/fitur/HalamanUnduhanTest.php`; pemeriksaan menu Unduhan di `TataLetakHalamanTest` disesuaikan

## Selesai bila

`composer uji` lulus; ketiga bagian dan halaman `/unduhan` tampil benar di lebar 390 dan 1440 piksel tanpa gulir mendatar; tautan WA terbuka dengan pesan yang diatur.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Data dari `IsiHalamanDepan` (F3-01): `jadwal()`, `unduhan()`, `narahubung()`, `mediaSosial()`, `waTanya()`, `waBagikan()`.
