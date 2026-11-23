# F11-16 Peran juri di panel admin

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 23 November 2026 |

## Tujuan

Setiap juri punya akun sendiri yang hanya bisa mengisi prestasi di Lembar juri, terpisah dari akun admin panitia (K181). Admin membuat, mengubah, dan menghapus akun juri dari panel.

## Hasil

- [x] Kolom `admin.peran` (`admin` bawaan, `juri`) lewat migrasi yang hanya menambah kolom; akun lama tetap admin.
- [x] Akun juri hanya membuka Lembar juri, Akun miliknya, dan keluar. Halaman lain dialihkan ke Lembar juri dengan pesan (GET) atau ditolak 403 (kiriman). Masuk langsung ke Lembar juri.
- [x] Peran dibaca dari basis data di setiap permintaan, bukan dari isi sesi.
- [x] Di Akun, juri mengganti nama dan kata sandinya sendiri; nama pengguna tampil sebagai teks dan isiannya diabaikan server (diganti admin di Akun juri).
- [x] Menu juri hanya Lembar juri dan Akun (sidebar dan menu bawah), pojok logo "Panel juri", jejak dimulai Lembar juri, tanpa tautan ke Hasil, Seri, atau Pengaturan.
- [x] Pengaturan > Akun juri: daftar, tambah, ubah (kata sandi boleh kosong), hapus; hanya akun berperan juri.
- [x] `php spark admin:buat --peran juri`.
- [x] Rancangan web direvisi (K101) dan PDF-nya dibuat ulang.

## Berkas dan uji

Migrasi `2026-11-25-000100_TambahPeranAdmin.php`, `AdminModel`, `SesiAdmin`, `Filters/AdminFilter`, `Admin/MasukController`, `Admin/Pengaturan/AkunJuriController`, `BerandaPengaturanController::daftarHalaman()`, `Panel/MenuAdmin`, `Commands/BuatAdmin`, view `admin/pengaturan/akun_juri/`, `admin/juri/`, `admin/akun.php`, `admin/_jejak_tw.php`, `tata_letak/admin_tw.php`, `tata_letak/_menu_admin_tw.php`, `Admin/AkunController`. Uji `--filter PeranJuri|MasukAdmin|AkunAdmin|SesiAdmin|AdminModel|BuatAdmin|JejakHalaman|LembarJuri`.

## Selesai bila

Akun juri yang dibuat di Pengaturan > Akun juri bisa masuk, langsung sampai di Lembar juri, dan tidak bisa membuka halaman admin lain. `composer uji` lulus.

## Catatan

Bagian 3.1 panduan admin hari lomba disesuaikan di tugas panduan, bukan di tugas ini.
