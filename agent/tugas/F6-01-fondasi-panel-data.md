# F6-01 Fondasi panel data

| | |
|---|---|
| Status | selesai |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Menyiapkan bahan bersama panel data supaya tugas Tahap 6 lain bisa berjalan bersamaan tanpa bentrok: tabel sisi-server, ekspor Excel, bukti bayar di basis data, rute, dan menu.

## Hasil

- [x] `phpoffice/phpspreadsheet` di `composer.json` dan `composer.lock`
- [x] DataTables 2 (inti dan gaya Bootstrap 5) disimpan lokal di `public/aset/vendor/datatables/`, tanpa CDN
- [x] `App\Libraries\Panel\TabelSisiServer` dengan daftar kolom yang diizinkan, pencarian, saringan per kolom, urut, dan halaman
- [x] `App\Libraries\Panel\EksporExcel` (judul tebal, lebar otomatis, format rupiah)
- [x] `public/aset/js/tabel-admin.js`: DataTables dari atribut `data-*`, teks berbahasa Indonesia, tombol Excel mengikuti saringan aktif, keadaan kosong ramah
- [x] Bilah kemajuan di atas halaman saat data dimuat (wilayah "Tahap 6" di `admin.css`)
- [x] Migrasi tabel `bukti_bayar` dan `BuktiBayarModel`
- [x] `App\Libraries\Panel\BerkasBuktiBayar`: simpan (GD, JPEG, sisi terpanjang 1600 piksel, nama acak di `writable/unggahan/bukti-bayar/`), jalur penuh, hapus, hapus semua milik satu pendaftaran
- [x] Blok rute Tahap 6 di `app/Config/Routes.php` untuk semua tugas (pendaftaran, atlet, rekap, bukti bayar, foto, Excel, PDF), seperti pola Tahap 2: alamat yang pengendalinya belum ada menampilkan halaman tidak ditemukan
- [x] Butir menu Pendaftaran, Atlet, dan Rekap di `_menu_admin.php`
- [x] Pembantu data uji `tests/_support/Panel*` (pendaftaran, atlet, tim karangan) untuk dipakai tugas lain

## Selesai bila

`composer uji` lulus lokal dan di CI, termasuk uji unit `TabelSisiServer` (kolom tak dikenal diabaikan, saringan dan urut benar), uji `EksporExcel` (berkas terbaca ulang), uji migrasi `bukti_bayar`, dan uji `BerkasBuktiBayar` (gambar dikecilkan, berkas bukan gambar ditolak).

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-6.md`. Satu-satunya tugas yang menambah paket Composer dan menulis rute Tahap 6. Gelombang 1, dikerjakan sendiri.

### Yang dibuat (untuk F6-02, F6-03, F6-04, F6-06)

- **`TabelSisiServer`**: `new TabelSisiServer(kolom, saringan, urutBawaan)`; `jawab($pembuat, $this->request->getGet())` untuk JSON, `semuaBaris(...)` untuk Excel, `parameterExcel(...)`, `bentukBaris(fn)`. `$pembuat` fungsi yang menghasilkan query builder baru. Kolom `['sql' => …, 'cari' => bool, 'urut' => bool]`; saringan kolom SQL, `['sql' => …, 'nilai' => [...]]`, atau fungsi `(BaseBuilder, string)`. Alamat Excel memakai `cari`, `urut`, `arah`, dan nama saringan yang sama. 25 baris per halaman, paling banyak 100.
- **`EksporExcel`**: `new EksporExcel(judulLembar, [kunci => judul | ['judul' => …, 'jenis' => teks|angka|rupiah]])`, `tambahBaris($baris)`, `unduh('Nama.xlsx')`, `isi()`, `lembar()`.
- **`BerkasBuktiBayar`**: `periksa(UploadedFile)`, `simpan(UploadedFile)` dan `simpanIsi(string)` → `['berkas', 'ukuran']`, `jalurPenuh`, `hapus`, `berkasMilik`, `hapusBerkas`, `hapusMilik`, `gantiFolderDasar` (uji). `BuktiBayarModel::BATAS = 5`, `milikPendaftaran`, `jumlahMilik`, `masihBolehTambah`, `milik($pendaftaranId, $id)`.
- **`tabel-admin.js`**: `<table data-tabel-admin data-alamat data-saringan data-excel data-kosong data-kosong-saring data-urut data-arah data-panjang>`, `<th data-kolom data-bentuk data-urut="tidak">`; bentuk `teks`, `angka`, `rupiah`, `tanggal`, `status`, `tautan`, `html`, dan `TabelAdmin.bentuk.<nama>` dari halaman. Halaman mengisi bagian `tabel` di tata letak admin supaya asetnya dimuat (contoh: `tests/_support/Views/PanelContohTabel.php`).
- **Rute**: blok "Panel data (Tahap 6)" di `app/Config/Routes.php`; daftar lengkapnya di `tests/fitur/PanelRuteTest.php`.
- **Data uji**: sifat `Tests\Support\PanelDataContoh` (`siapkanDataPanel`, `buatPendaftaranPanel`, `atletPanel`, `timPanel`, `buatBuktiBayarPanel`, `gambarPanel`, `bersihkanBerkasPanel`).

### Penyimpangan dari rencana dan asumsi

- DataTables 2 membutuhkan jQuery, jadi jQuery 3.7.1 ikut disimpan di `public/aset/vendor/jquery/` (di luar daftar berkas rencana). Keduanya hanya dimuat di halaman yang memakai tabel. DataTables 3 (tanpa jQuery) sudah terbit, tetapi rencana menyebut DataTables 2.
- Format rupiah Excel ditulis `"Rp"#,##0` (huruf diberi tanda kutip supaya Excel tidak membacanya sebagai kode format).
- Asumsi: batas unggah satu gambar bukti bayar 10 MB sebelum dikecilkan; JPG, PNG, dan WEBP diterima.
- Asumsi: rekap punya alamat halaman per jenis (`/admin/rekap/<jenis>`), selain `/admin/rekap`.
- Rute foto penanggung jawab: `/admin/pendaftaran/<id>/foto/<id penanggung jawab>`.
