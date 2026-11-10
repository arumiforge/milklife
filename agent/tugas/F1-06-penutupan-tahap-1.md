# F1-06 Uji gabungan dan penutupan Tahap 1

| | |
|---|---|
| Status | rencana |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Setelah F1-01 sampai F1-05 digabung, semuanya terbukti bekerja bersama pada `main`, dan Tahap 1 ditutup di catatan progres.

## Hasil

- [ ] Pada salinan baru `main`: `composer install`, `php spark migrate --all`, `php spark sekolah:impor`, `php spark admin:buat`, `composer uji` berjalan tanpa galat
- [ ] Alur masuk admin diperiksa di peramban: masuk, dasbor dengan tata letak admin, keluar
- [ ] Sambungan antartugas yang perlu dirapikan (misalnya gaya halaman masuk) diperbaiki
- [ ] `php spark migrate:rollback` membersihkan semua tabel
- [ ] Tidak ada `.github/`, tidak ada `.env`, tidak ada data siswa di repo
- [ ] `README.md` sesuai keadaan akhir Tahap 1
- [ ] Rencana implementasi Tahap 1 diperbarui dengan hal yang berubah saat pengerjaan
- [ ] `agent/PROGRES.md` diperbarui; Tahap 1 dipindahkan ke `agent/arsip/tahap-1.md`
- [ ] Daftar pertanyaan untuk pemilik sebelum Tahap 2 (asumsi bagian 17) disiapkan

## Selesai bila

Pemilik menerima laporan Tahap 1 dan PR penutupan digabung.

## Catatan

Dikerjakan koordinator.
