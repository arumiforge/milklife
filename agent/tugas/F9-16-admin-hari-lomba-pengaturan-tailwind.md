# F9-16 Halaman admin hari lomba dan pengaturan ke Tailwind

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Halaman admin hari lomba, hasil, rekap, dan pengaturan memakai tampilan baru, termasuk pilihan latar BIB bawaan.

## Hasil

- [ ] Pindah: daftar ulang, cetak BIB, daftar peserta, hasil, klasemen, rekap, semua pengaturan, akun, masuk
- [ ] Cetak BIB: pilihan latar bawaan G9-01 (bila ada) di samping unggah latar sendiri (K137)

## Berkas dan uji

`app/Views/admin/{hari_lomba,hasil,rekap,pengaturan}/`, `admin/akun.php`, `admin/masuk.php`, `app/Libraries/HariLomba/TampilanBib.php`. Uji: hari lomba, hasil, rekap, pengaturan, admin.

## Selesai bila

Semua halaman tersebut berjalan di peramban tanpa galat konsol/CSP; semua uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. Setelah F9-08 dan F9-09 supaya halaman hasil tidak disentuh dua tugas bersamaan.
