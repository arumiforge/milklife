# F7-03 Tim tanpa pembayar

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Admin tahu bila sebuah tim beregu kehilangan pendaftaran yang membayar biaya timnya, dan bisa menagihkannya ke salah satu pendaftaran (K68).

## Hasil

- [x] `App\Libraries\Panel\TimTanpaPembayar`: tim (NPSN + nomor beregu) tanpa satu pun baris `biaya_tim` > 0 sementara tarif beregu saat ini > 0
- [x] Peringatan di rincian pendaftaran yang memuat tim itu
- [x] Jumlah tim tanpa pembayar di dasbor (hanya bila ada)
- [x] Daftar tim tanpa pembayar di rekap keuangan, ikut di Excel dan PDF
- [x] Ubah: kotak centang "Bebankan biaya tim ke pendaftaran ini" pada tim yang ditandai; tarif saat ini, total dihitung ulang, K32 berlaku
- [x] Jendela hapus pendaftaran dan simpan Ubah yang menghapus tim memperingatkan bila tindakannya meninggalkan tim tanpa pembayar

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: tim ditandai setelah pendaftaran pembayar dihapus dan setelah timnya dihapus lewat Ubah; tidak ditandai bila tarif beregu 0 atau sekolah tanpa NPSN; angka sama di rincian, dasbor, dan rekap; tanda hilang setelah dibebankan lewat Ubah; K32 saat membebankan ke pendaftaran Lunas.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Tim tanpa pembayar". Asumsi 6. Mengisi tempat kosong gelombang 1, atau gelombang 2. Menjawab batasan F6-04 di `agent/arsip/tahap-6.md`.

## Hasil pengerjaan

- `TimTanpaPembayar` dipakai rincian (`untukPendaftaran`, `ditinggalkan` untuk jendela hapus), dasbor (`Rekap::ringkasan()['tim_tanpa_pembayar']`), rekap keuangan (`Rekap::susun('keuangan')['tim_tanpa_pembayar']`: tabel di halaman, lembar kedua "Tim Tanpa Pembayar" di Excel, tabel kedua di PDF), dan `UbahPendaftaran`.
- Ubah: kiriman `bebankan_tim[]` (`UbahPendaftaran::KUNCI_BEBANKAN`). Tim pendaftaran ini yang biaya timnya Rp0 diberi tarif saat ini (`tarifBebankan`, dibaca di bawah kunci NPSN); bila tim itu sudah punya pembayar lain, kiriman ditolak dengan galat `bebankan_tim.<nomor>`; nomor lain diabaikan. Subtotal, total, dan K32 memakai alur yang sudah ada.
- Pratinjau dan simpan Ubah mengembalikan `tim_ditinggalkan`; jendela konfirmasi berjudul "Tim akan kehilangan pembayar" (bila K32 tidak berlaku) dan kalimatnya ditambah peringatan. Pesan kilat sesudah hapus dan simpan menyebut tim yang kini tanpa pembayar.
- Uji: `tests/basisdata/PanelTimTanpaPembayarTest.php`, `tests/fitur/PanelTimTanpaPembayarHalamanTest.php`.

### Penyimpangan dari pembagian berkas

- `app/Libraries/Panel/EksporExcel.php`: metode `tambahLembar()` supaya Excel rekap keuangan punya lembar kedua. Rekap lain tetap satu lembar.
- `app/Libraries/Panel/DataPanelPendaftaran.php`: `rincian()['beregu'][]` ditambah `nomor_lomba_id` supaya peringatan menempel di kartu tim yang benar.
- `HapusPendaftaran.php` dan kedua berkas JS tidak diubah: tim yang ditinggalkan dibaca pengendali sebelum menghapus, dan `FormData` di `admin-ubah-pendaftaran.js` sudah membawa kotak centang `bebankan_tim[]`.
