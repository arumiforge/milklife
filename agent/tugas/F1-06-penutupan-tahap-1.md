# F1-06 Uji gabungan dan penutupan Tahap 1

| | |
|---|---|
| Status | selesai |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Setelah F1-01 sampai F1-05 digabung, semuanya terbukti bekerja bersama pada `main`, dan Tahap 1 ditutup di catatan progres.

## Hasil

- [x] Pada salinan baru `main`: `composer install`, `php spark migrate --all`, `php spark sekolah:impor`, `php spark admin:buat`, `composer uji` berjalan tanpa galat
- [x] Alur masuk admin diperiksa di peramban: masuk, dasbor dengan tata letak admin, keluar
- [x] Sambungan antartugas yang perlu dirapikan (misalnya gaya halaman masuk) diperbaiki
- [x] `php spark migrate:rollback` membersihkan semua tabel
- [x] Tidak ada `.github/`, tidak ada `.env`, tidak ada data siswa di repo
- [x] `README.md` sesuai keadaan akhir Tahap 1
- [x] Rencana implementasi Tahap 1 diperbarui dengan hal yang berubah saat pengerjaan
- [x] `agent/PROGRES.md` diperbarui; Tahap 1 dipindahkan ke `agent/arsip/tahap-1.md`
- [x] Daftar pertanyaan untuk pemilik sebelum Tahap 2 (asumsi bagian 17) disiapkan

## Selesai bila

Pemilik menerima laporan Tahap 1 dan PR penutupan digabung.

## Catatan

Dikerjakan koordinator.

- F1-02 sampai F1-05 dikerjakan paralel di cabang masing-masing di atas cabang F1-01, lalu diuji bersama dalam satu gabungan lokal sebelum digabung (196 uji lulus, alur masuk–dasbor–keluar dicoba di peramban).
- Setelah semua digabung, `main` dipasang ulang dari nol: 15 tabel terbentuk, 4.729 sekolah masuk, admin bisa dibuat, 196 uji lulus, dan gulung balik migrasi menyisakan hanya tabel `migrations`.
- Sambungan yang diperbaiki: halaman masuk (F1-04) membawa kartu dan kisi sendiri di dalam kartu tata letak `polos` (F1-05), sehingga di layar lebar tampil sempit dan terpotong. Isinya kini cukup judul, pesan, dan formulir; uji `testHalamanMasukMemakaiKartuTataLetakPolos` menjaga agar tidak terulang.
- `public/favicon.ico` bawaan CodeIgniter diganti ikon yang digambar ulang dari `public/aset/gambar/ikon.svg` (16, 32, dan 48 piksel).
- Di kontainer kerja, `composer install` tidak bisa mengunduh arsip pustaka dari GitHub; folder `vendor/` disalin dari pemasangan pertama. Ini tidak memengaruhi pemasangan di Laragon.
