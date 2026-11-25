# F11-20 Data awal sesuai isian panitia di produksi

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 25 November 2026 |

## Tujuan

Server yang dipasang baru, seperti staging, memakai batas tahun lahir kelompok umur dan agenda yang sama dengan server produksi (K186). Dengan begitu atlet hasil impor tidak tampak salah kelompok umur, dan tanya-jawab bawaan tidak menyebut pertemuan teknik tatap muka.

## Hasil

- [x] `data:awal`: batas tahun lahir KU 8 2018–2021, KU 10 2016–2017, KU 12 2014–2016, KU 15 2010–2014, KU 18 2008–2012.
- [x] `data:awal`: agenda pertemuan teknik daring 5 Desember 2026 pukul 13.00 dan pengambilan nomor atlet (BIB) 8 Desember 2026 pukul 13.00 di Supersoccer Arena, Kudus.
- [x] Tanya-jawab bawaan: pertemuan teknik daring, dan barang bawaan serta pemeriksaan keabsahan dipindah ke pengambilan nomor atlet.
- [x] Teks halaman Nomor atlet di panel tidak lagi menyebut pertemuan teknik.
- [x] K186, ATURAN, KONTEKS, PROGRES, rancangan revisi 43, dan panduan admin hari lomba revisi 2 beserta PDF-nya.

## Berkas dan uji

`app/Commands/DataAwal.php`, `app/Libraries/Bantuan/FaqBawaan.php`, `app/Views/admin/atlet/nomor_dada.php`, uji `DataAwalTest`, `PengaturanKelompokUmurTest`, `HalamanDepanLombaTest`. Uji `vendor/bin/phpunit --filter 'DataAwal|KelompokUmur|HalamanDepan|Faq|Bantuan|NomorDada|Jadwal'`.

## Selesai bila

`php spark data:awal` di basis data kosong menampilkan batas tahun lahir yang sama dengan server produksi, dan uji terkait lulus.

## Catatan

- Server produksi tidak diubah; isiannya ikut pindah lewat cadangan basis data (K185).
- Cara sekolah meminta ganti atlet di panduan admin hari lomba masih menyebut meja pertemuan teknik (K183). Ini ditanyakan ke pemilik karena pertemuan teknik kini daring.
