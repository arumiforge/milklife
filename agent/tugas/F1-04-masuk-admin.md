# F1-04 Masuk admin

| | |
|---|---|
| Status | rencana |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Admin bisa masuk dan keluar dengan nama pengguna dan kata sandi, dan semua halaman di bawah `/admin` tertutup bagi yang belum masuk.

Acuan: rancangan bagian 4 (masuk admin), 5, dan 13; rencana implementasi Tahap 1 bagian "Rute dan sesi admin".

## Hasil

- [ ] Migrasi `2026-11-11-000200_BuatTabelAdmin`: `nama`, `nama_pengguna` (unik), `sandi` (hash), `dibuat_pada`, `diubah_pada`
- [ ] `App\Models\AdminModel`; kata sandi selalu disimpan lewat `password_hash`, tidak pernah teks asli
- [ ] `GET /admin/masuk`: formulir nama pengguna dan kata sandi, memakai tata letak `polos`
- [ ] `POST /admin/masuk`: validasi di server, `password_verify`, hash diperbarui bila perlu (`password_needs_rehash`), ID sesi dibuat ulang, sesi diisi `admin_id` dan `admin_nama`, lalu ke `/admin/dasbor` atau halaman admin yang tadi dituju
- [ ] Pesan gagal tidak membedakan nama pengguna salah atau kata sandi salah
- [ ] Pembatasan laju pada `POST /admin/masuk`: 5 percobaan per menit per alamat IP; setelah itu pesan ramah yang menyebut kapan boleh mencoba lagi
- [ ] `POST /admin/keluar`: sesi dihapus, kembali ke halaman masuk
- [ ] `App\Filters\AdminFilter` menjaga semua rute `/admin/*` kecuali `/admin/masuk`; yang belum masuk dialihkan ke halaman masuk; yang sudah masuk dan membuka halaman masuk dialihkan ke dasbor
- [ ] Sesi admin berakhir otomatis sesuai `session.expiration`
- [ ] Perintah `php spark admin:buat`: menanyakan nama, nama pengguna, dan kata sandi (minimal 8 karakter, tidak tampil saat diketik bila terminal mendukung); bila nama pengguna sudah ada, menawarkan mengganti kata sandinya
- [ ] Tidak ada akun atau kata sandi bawaan di repo
- [ ] Semua teks berbahasa Indonesia untuk orang non-teknis
- [ ] Uji: masuk berhasil; kata sandi salah; nama pengguna tak dikenal; tanpa token CSRF ditolak; percobaan ke-6 dalam semenit ditolak; `/admin/dasbor` tanpa sesi dialihkan; keluar menghapus sesi; `admin:buat` menyimpan hash, bukan teks asli

## Selesai bila

Setelah `php spark admin:buat`, admin bisa masuk di `/admin/masuk`, melihat dasbor, dan keluar. Membuka `/admin/dasbor` tanpa masuk mengalihkan ke halaman masuk. `composer uji` lulus.

## Catatan

- Tanpa pustaka tambahan: sesi dan pembatas laju bawaan CodeIgniter.
- Halaman ganti nama dan kata sandi (`/admin/akun`) dikerjakan di Tahap 2.
- Tampilan halaman masuk hanya memakai kelas Bootstrap; gaya tata letak `polos` dikerjakan F1-05.
