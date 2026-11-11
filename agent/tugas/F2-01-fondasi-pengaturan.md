# F2-01 Fondasi pengaturan

| | |
|---|---|
| Status | dikerjakan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Menyiapkan semua yang dipakai bersama halaman pengaturan, supaya tujuh tugas berikutnya bisa dikerjakan bersamaan tanpa bentrok.

## Hasil

- [ ] Migrasi `2026-11-12-000100_BuatTabelKelompokUmurJenjang` (rencana: "Jenjang")
- [ ] `Config\Acara`, `Config\Pendaftaran`, `Config\Pembayaran`, `Config\HalamanDepan` dengan semua kunci dan nilai awal di rencana
- [ ] Semua rute Tahap 2 di `app/Config/Routes.php` sesuai tabel rute di rencana
- [ ] Butir menu **Pengaturan** (aktif juga untuk `admin/akun`), tautan ke Akun dari nama admin di kepala
- [ ] `/admin/pengaturan`: daftar sembilan halaman pengaturan dan Akun, dalam kartu dengan ikon dan keterangan singkat
- [ ] Potongan tampilan bersama: `admin/_pesan.php` (dipasang di tata letak admin), `admin/_galat_isian.php`, `admin/pengaturan/_navigasi.php`
- [ ] `public/aset/js/konfirmasi.js`: jendela konfirmasi untuk formulir ber-`data-konfirmasi`
- [ ] `App\Libraries\Jenjang`, `App\Libraries\NomorWa`, `App\Libraries\Unggahan`, fungsi `rupiah()` dan `tanggal_id()` di `aplikasi_helper`, beserta uji unit
- [ ] `php spark data:awal` sesuai bagian "Data awal" di rencana, dengan uji
- [ ] `Tests\Support\KirimFormulir` dan pengaturan yang tidak terbawa antar-uji
- [ ] `public/unggahan/` diabaikan git; batas unggah PHP dicatat di `README.md`

## Selesai bila

`composer uji` lulus; `migrate --all` lalu `data:awal` menghasilkan 5 kelompok umur dan semua nomor lomba lampiran; `/admin/pengaturan` terbuka dan tampil baik di lebar 390 dan 1440 piksel; gulung balik migrasi bersih.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
