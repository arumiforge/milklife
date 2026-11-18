# F8-04 Hasil lomba

| | |
|---|---|
| Status | selesai |
| Tahap | 8 — Hari lomba dan hasil |
| Dibuat | 17 November 2026 |

## Tujuan

Admin mencatat hasil semua babak setiap nomor lomba dan menerbitkannya ke halaman depan.

## Hasil

- [x] Jenis hasil per nomor lomba (waktu, jarak, poin) di Pengaturan dan data awal
- [x] Babak per nomor (satu Final), isian prestasi dan keterangan DNS/DNF/DQ, peringkat otomatis, koreksi peringkat Final
- [x] Tri Lomba: poin akhir diisi admin
- [x] Terbitkan dan Tarik kembali per nomor; halaman publik `hasil`
- [x] Unduhan PDF dan Excel hasil
- [x] Uji unit urutan peringkat dan format prestasi; uji fitur

## Selesai bila

Uji lulus dan `composer uji` lulus lokal dan di CI.

## Catatan

K124. Harus siap sebelum 9 Desember 2026.

## Hasil pengerjaan

- Migrasi `2026-11-18-000100_BuatTabelHasilLomba`: `nomor_lomba.jenis_hasil` (diisi tebakan dari nama nomor), `nomor_lomba.hasil_terbit_pada`, tabel `babak` dan `hasil`.
- Panel `admin/hasil` (butir menu samping sendiri, tidak di menu bawah ponsel; menu ponsel diatur F8-07). Halaman publik `hasil` dan `hasil/<id>`, tombol "Hasil lomba" di halaman depan setelah ada nomor terbit.
- Regu dikenali dari kontingen (NPSN, atau `p<id>` untuk pendaftaran tanpa NPSN) dan huruf regu.
- Hasil tidak ikut paket pindah server (`PaketPendaftaran`); cadangan `mysqldump` sudah membawanya. Lihat pertanyaan di PR.
- Di server: `php spark migrate --all` dan `php spark aset:minify` (CSS medali di `tema.css`).
