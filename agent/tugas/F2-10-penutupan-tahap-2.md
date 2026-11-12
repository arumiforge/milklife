# F2-10 Uji gabungan dan penutupan Tahap 2

| | |
|---|---|
| Status | selesai |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Memastikan semua tugas Tahap 2 bekerja bersama di `main` dan menutup tahap.

## Hasil

- [x] Uji gabungan semua cabang sebelum digabung
- [x] Pemasangan ulang `main` dari nol: migrasi, impor sekolah, `data:awal`, `composer uji`
- [x] Alur lengkap di peramban (390 dan 1440 piksel): isi semua pengaturan sampai dasbor bercentang semua
- [x] Perbaikan sambungan bila ada
- [x] Rancangan revisi dipindah ke `dokumen/final/` (bila belum), bagian "Perubahan saat pengerjaan" di rencana, arsip `agent/arsip/tahap-2.md`, `PROGRES.md`

## Selesai bila

Semua uji lulus di salinan baru `main`, arsip tahap ada, dan pemilik sudah menerima laporan dan tangkapan layar.

## Catatan

Dikerjakan koordinator.

- Cabang F2-02 sampai F2-09 digabung bersama di satu salinan lokal sebelum PR digabung, bertahap seiring tugas selesai: lima cabang 559 uji lulus, enam cabang 642, delapan cabang 704. Tidak ada konflik gabungan. Isi `main` setelah delapan PR digabung sama persis dengan salinan gabungan yang diuji.
- Salinan baru `main` (201ada7): `migrate --all` membuat semua tabel, `sekolah:impor` 4.729 sekolah, `data:awal` 5 kelompok umur dan 69 nomor lomba dengan batas tahun lahir K36, `composer uji` 704 uji lulus, gulung balik migrasi menyisakan hanya tabel `migrations`.
- Alur di peramban pada salinan baru `main`, semuanya lewat formulir: jenjang dan biaya lima kelompok umur, batas anggota sebelas nomor beregu (dan jumlah putra/putri tiga nomor mix), rekening dan WA pembayaran. Setelah itu dasbor menampilkan "Semua pengaturan wajib sudah lengkap" dan keadaan **Terbuka** ("Pendaftaran dibuka sampai 30 November 2026 pukul 23.59"). Data yang diisi adalah data karangan untuk uji.
- Sambungan yang diperbaiki: `.tabel-gulir` di `admin.css` diberi `position: relative` (laporan F2-06); halaman yang sudah memakai kelas `position-relative` tetap benar.
- Basis data uji dipakai bergantian oleh beberapa salinan; catatan migrasi dari cabang lain bisa membuat uji gagal ("celah dalam urutan migrasi"). Kosongkan `milklife_uji` sebelum menguji cabang lain.
