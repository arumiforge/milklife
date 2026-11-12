# F6-05 Konfirmasi tim susulan di wizard

| | |
|---|---|
| Status | selesai |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Official yang mendaftar susulan menyatakan sendiri bahwa atletnya menjadi tambahan anggota tim sekolah yang sudah terdaftar, tanpa biaya tim (K57).

## Hasil

- [x] Kotak centang per tim susulan di daftar sementara langkah 3: "Saya mengerti atlet ini ditambahkan ke tim <nomor> sekolah yang sudah terdaftar (<n> atlet), tanpa biaya tim"
- [x] Lanjut ke langkah 4 ditolak di peramban bila ada yang belum dicentang, dengan pesan yang menyebut timnya
- [x] Konfirmasi tersimpan di draf; draf lama tanpa konfirmasi tetap terbaca (kotak belum tercentang)
- [x] Kiriman `periksa` dan `simpan` membawa daftar ID nomor lomba yang dikonfirmasi; `PemeriksaPendaftaran` menolak tim susulan yang belum dikonfirmasi dengan galat langkah 3. Parameter bersifat pilihan supaya pemeriksaan admin (F6-04) tidak memintanya
- [x] Tampil rapi di ponsel 390 piksel

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: simpan susulan tanpa konfirmasi ditolak, dengan konfirmasi diterima, pendaftaran tanpa tim susulan tidak terpengaruh, pemeriksa tanpa parameter konfirmasi tetap berperilaku seperti sebelumnya.

## Catatan

Tidak bergantung pada tugas lain. Satu-satunya tugas Tahap 6 yang mengubah wizard dan `PemeriksaPendaftaran`.

## Hasil pengerjaan

- Daftar konfirmasi dikirim sebagai kunci `konfirmasi_susulan` di dalam kiriman JSON, bukan argumen metode, karena `SimpanPendaftaran` (hanya dibaca di Tahap 6) meneruskan kiriman apa adanya ke `PemeriksaPendaftaran`. Tanpa kunci itu pemeriksa berperilaku seperti sebelumnya (jalur F6-04). Titik akhir publik selalu mengisi kunci itu lewat `PemeriksaPendaftaran::wajibKonfirmasiSusulan()`, jadi kiriman tanpa kunci tetap ditolak bila ada tim susulan.
- Galat server berjalur `konfirmasi.<id nomor>`, langkah 3, menyebut nama tim dan cara memperbaikinya.
- `PendaftaranController::kiriman()` berubah satu baris (memanggil `wajibKonfirmasiSusulan()`).
- Draf peramban menyimpan `konfirmasiSusulan` (list id nomor). Draf lama tanpa kunci itu tetap terbaca dengan kotak belum tercentang; versi draf tidak dinaikkan. Konfirmasi gugur bila data tim sekolah berubah (NPSN diganti atau jumlah anggota lama berbeda saat dicari ulang).
- Bila server menemukan tim susulan yang belum diketahui wizard (sekolah yang sama baru saja mendaftar lagi), wizard mengambil ulang data sekolah supaya kotak konfirmasinya tampil.
- Diperiksa di Chromium 390 piksel: kotak tampil belum tercentang untuk draf lama, lanjut ditolak dengan pesan yang menyebut tim, centang tersimpan setelah muat ulang, langkah 4 terbuka setelah dicentang, tanpa gulir mendatar.
