# F2-08 Akun admin

| | |
|---|---|
| Status | rencana |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin yang sedang masuk bisa mengganti nama, nama pengguna, dan kata sandinya sendiri (K26).

## Hasil

- [ ] `/admin/akun`: formulir nama dan nama pengguna; formulir kata sandi (sandi saat ini, sandi baru, ulangi)
- [ ] Kata sandi saat ini wajib untuk mengganti nama pengguna dan kata sandi
- [ ] Aturan `is_unique` nama pengguna di `AdminModel` mengecualikan baris sendiri
- [ ] Setelah simpan, `admin_nama` di sesi ikut berubah; setelah ganti kata sandi, sesi dibuat ulang
- [ ] Uji fitur dan uji model

## Selesai bila

`composer uji` lulus; admin bisa mengganti nama dan kata sandi lalu masuk lagi dengan kata sandi baru.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
