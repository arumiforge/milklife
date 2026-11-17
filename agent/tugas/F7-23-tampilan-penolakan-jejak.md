# F7-23 Tampilan penolakan atlet dan jejak navigasi admin

| | |
|---|---|
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Sekolah yang atletnya ditolak melihat keadaan yang jelas di Cek Pendaftaran dan bukti PDF, dengan bahasa yang mudah; admin punya menu Atlet ditolak sendiri dan jejak navigasi yang tidak membingungkan (K117).

## Hasil

- [x] `DataPendaftaran`: `ditolak_semua`, `atlet_didaftarkan_ulang`, label "Semua atlet ditolak"
- [x] Cek Pendaftaran: kartu merah "Semua atlet ditolak" tanpa tabel atlet, regu, dan unggah bukti bayar; bagian "Perlu didaftarkan ulang" (`cek_pendaftaran/_ditolak`) dengan bahasa untuk sekolah; lencana "N atlet ditolak" di kepala kartu; ringkasan menautkan ke kartunya
- [x] Bukti PDF: tabel "Atlet Ditolak Panitia"; mode "Keterangan Penolakan Atlet" (stempel DITOLAK, Rp 0, nama berkas `_Ditolak.pdf`)
- [x] Menu dan halaman `admin/atlet-ditolak` (tabel sisi server, saringan keadaan, kotak jumlah, Excel)
- [x] Daftar pendaftaran admin: lencana "Semua atlet ditolak"; rincian: peringatan dan tautan ke menu Atlet ditolak; dasbor: "Bukti bayar perlu diperiksa" tidak menghitung pendaftaran tanpa atlet
- [x] Jejak navigasi `admin/_jejak` dan `App\Libraries\Panel\AsalRincian`: rincian, Ubah, Tolak atlet mengikuti asal (Pendaftaran, Atlet, Atlet ditolak); Urutkan nomor atlet dan halaman turunan Sekolah
- [x] Uji: `tests/fitur/PanelTolakAtletHalamanTest.php` (semua ditolak, menu Atlet ditolak, jejak dan menu aktif, PDF)

## Selesai bila

`composer uji` lulus; di server produksi pendaftaran yang semua atletnya ditolak tampil "Semua atlet ditolak" di Cek Pendaftaran tanpa kotak bukti bayar, bukti PDF-nya memuat daftar atlet yang ditolak, dan rincian yang dibuka dari menu Atlet tetap menandai menu Atlet.

## Catatan

- Tanpa migrasi. Di server produksi cukup `git pull`, lalu `php spark pembaruan:periksa` (akan meminta `php spark aset:minify` bila minify dipakai, karena CSS dan JS berubah).
- Halaman Pengaturan tetap memakai navigasi pengaturannya sendiri.
- Rancangan final belum diubah (perlu revisi lewat draf bersama K116).
- PR: [#92](https://github.com/arumiforge/milklife/pull/92).
