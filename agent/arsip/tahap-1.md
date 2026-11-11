# Arsip Tahap 1 — Kerangka

Selesai: 11 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-1.md`](../../dokumen/draf/rencana-implementasi-tahap-1.md), termasuk bagian "Perubahan saat pengerjaan".

| Tugas | Status | PR |
|---|---|---|
| [F1-01 Kerangka CodeIgniter](../tugas/F1-01-kerangka-codeigniter.md) | selesai | #3 |
| [F1-02 Migrasi model data](../tugas/F1-02-migrasi-model-data.md) | selesai | #4 |
| [F1-03 Impor sekolah](../tugas/F1-03-impor-sekolah.md) | selesai | #5 |
| [F1-04 Masuk admin](../tugas/F1-04-masuk-admin.md) | selesai | #6 |
| [F1-05 Tata letak dan tema](../tugas/F1-05-tata-letak-tema.md) | selesai | #7 |
| [F1-06 Uji gabungan dan penutupan](../tugas/F1-06-penutupan-tahap-1.md) | selesai | PR penutupan |

Catatan koordinator: PR #2 (rencana) dan PR #8 (progres, K20).

## Hasil

- CodeIgniter 4.7.6 resmi terpasang lewat Composer, dengan `codeigniter4/settings` dan `codeigniter4/translations`. Bootstrap 5.3.8 dan Bootstrap Icons 1.13.2 disimpan lokal.
- Lima belas tabel: sebelas tabel domain, `sekolah`, `admin`, `settings`, dan `migrations`.
- `php spark sekolah:impor` mengisi 4.729 sekolah dari `data/sekolah/sekolah.json`.
- Masuk dan keluar admin di `/admin/masuk`, penjaga semua halaman `/admin`, pembatasan percobaan masuk, dan `php spark admin:buat`.
- Tata letak publik, admin, dan polos dengan warna flyer; halaman galat berbahasa Indonesia.
- `composer uji`: 197 uji lulus pada `main` setelah penutupan.

## Yang dibawa ke tahap berikutnya

- **Sebelum Tahap 2:** konfirmasi asumsi rancangan bagian 17, terutama nomor 2 dan 3 (jenis Frog Jump, Turbo Throw, dan Trilomba), karena data awal nomor lomba bergantung padanya.
- **Sebelum Tahap 4:** asumsi lain di bagian 17 dan jenjang 19 SLB di data sekolah.
- Batas usia kelompok umur disimpan dalam tahun penuh. Bila juknis memakai bulan atau rentang tanggal lahir, kolomnya diubah di Tahap 2.
- Halaman `/admin/akun` (Tahap 2): aturan `is_unique` nama pengguna di `AdminModel` perlu mengecualikan baris sendiri.
- Satu tim per nomor beregu per sekolah (K07) antar-pendaftaran dijaga aplikasi di Tahap 4.
- Kelompok umur yang masih punya awalan nomor dada tidak bisa dihapus; halaman Tahap 2 menghapus awalannya lebih dulu.
- Nama acara, seri, tanggal, dan tempat masih tertulis langsung di tampilan; diganti pengaturan admin di Tahap 2–3.
- Di cmd Windows kata sandi pada `admin:buat` terlihat saat diketik; jalankan dari Git Bash.
- Pembatasan masuk dihitung per alamat IP; perlu diatur ulang bila web dijangkau lewat terowongan.
- Juknis resmi, cara web dijangkau dari internet, dan lisensi repo (K20) belum diputuskan.
