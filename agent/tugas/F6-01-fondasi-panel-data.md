# F6-01 Fondasi panel data

| | |
|---|---|
| Status | rencana |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Menyiapkan bahan bersama panel data supaya tugas Tahap 6 lain bisa berjalan bersamaan tanpa bentrok: tabel sisi-server, ekspor Excel, bukti bayar di basis data, rute, dan menu.

## Hasil

- [ ] `phpoffice/phpspreadsheet` di `composer.json` dan `composer.lock`
- [ ] DataTables 2 (inti dan gaya Bootstrap 5) disimpan lokal di `public/aset/vendor/datatables/`, tanpa CDN
- [ ] `App\Libraries\Panel\TabelSisiServer` dengan daftar kolom yang diizinkan, pencarian, saringan per kolom, urut, dan halaman
- [ ] `App\Libraries\Panel\EksporExcel` (judul tebal, lebar otomatis, format rupiah)
- [ ] `public/aset/js/tabel-admin.js`: DataTables dari atribut `data-*`, teks berbahasa Indonesia, tombol Excel mengikuti saringan aktif, keadaan kosong ramah
- [ ] Bilah kemajuan di atas halaman saat data dimuat (wilayah "Tahap 6" di `admin.css`)
- [ ] Migrasi tabel `bukti_bayar` dan `BuktiBayarModel`
- [ ] `App\Libraries\Panel\BerkasBuktiBayar`: simpan (GD, JPEG, sisi terpanjang 1600 piksel, nama acak di `writable/unggahan/bukti-bayar/`), jalur penuh, hapus, hapus semua milik satu pendaftaran
- [ ] Blok rute Tahap 6 di `app/Config/Routes.php` untuk semua tugas (pendaftaran, atlet, rekap, bukti bayar, foto, Excel, PDF), seperti pola Tahap 2: alamat yang pengendalinya belum ada menampilkan halaman tidak ditemukan
- [ ] Butir menu Pendaftaran, Atlet, dan Rekap di `_menu_admin.php`
- [ ] Pembantu data uji `tests/_support/Panel*` (pendaftaran, atlet, tim karangan) untuk dipakai tugas lain

## Selesai bila

`composer uji` lulus lokal dan di CI, termasuk uji unit `TabelSisiServer` (kolom tak dikenal diabaikan, saringan dan urut benar), uji `EksporExcel` (berkas terbaca ulang), uji migrasi `bukti_bayar`, dan uji `BerkasBuktiBayar` (gambar dikecilkan, berkas bukan gambar ditolak).

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-6.md`. Satu-satunya tugas yang menambah paket Composer dan menulis rute Tahap 6. Gelombang 1, dikerjakan sendiri.
