# Peta kode

Peta berkas per fitur untuk sesi agen. Perbarui di PR yang sama bila memindah/menambah berkas.
Akar: Pengendali `app/Controllers/`, Pustaka `app/Libraries/`, View `app/Views/`, JS `public/aset/js/`. Uji u/b/f = `tests/unit|basisdata|fitur`.

## Publik

| Fitur | Rute | Pengendali | Pustaka | View | JS | Uji |
|---|---|---|---|---|---|---|
| Halaman depan | `/` | `BerandaController` | `IsiHalamanDepan`, `BukaTutupPendaftaran` | `beranda.php`, `beranda/` | `kemajuan`, `konfirmasi` | f `HalamanDepan*`, u `BukaTutupPendaftaranTest` |
| Wizard pendaftaran | `pendaftaran`, `pendaftaran/periksa`, `…/simpan` | `PendaftaranController` | `Pendaftaran/PemeriksaPendaftaran`, `…/SimpanPendaftaran` | `pendaftaran.php`, `pendaftaran/` | `pendaftaran` | `Pendaftaran*Test` (u/b/f) |
| Cari NPSN/sekolah | `pendaftaran/sekolah` | `PendaftaranController::sekolah` | `Pendaftaran/Kabupaten`, `Jenjang` | `pendaftaran/_langkah_sekolah.php` | `pendaftaran` | f `PendaftaranSekolahTest` |
| Cek pendaftaran | `cek-pendaftaran` | `CekPendaftaranController` | `DataPendaftaran` | `cek_pendaftaran.php`, `cek_pendaftaran/` | - | f `CekPendaftaranTest` |
| Bukti PDF + unggah bayar | `bukti/<24 heksa>/<id>`, `…/bukti-bayar` | `BuktiController` | `BuktiPendaftaran`, `Panel/BerkasBuktiBayar` | `bukti/` | - | f `BuktiPendaftaranTest`, `UnggahBuktiBayarOfficialTest` |
| Unduhan | `unduhan` | `UnduhanController` | `IsiHalamanDepan` | `unduhan.php`, `beranda/_daftar_unduhan.php` | - | f `HalamanUnduhanTest` |

## Admin (`admin/…`, filter `admin`)

| Fitur | Rute | Pengendali | Pustaka | View | JS | Uji |
|---|---|---|---|---|---|---|
| Masuk/akun | `masuk`, `keluar`, `akun`, `akun/sandi` | `Admin/MasukController`, `…/AkunController` | `SesiAdmin` | `admin/masuk.php`, `admin/akun.php` | - | f `MasukAdminTest`, `AkunAdminTest` |
| Dasbor/pengecekan | `dasbor`, `pengecekan` | `Admin/DasborController`, `…/PengecekanController` | `Panel/RingkasanDasbor`, `DataKelengkapan` | `admin/dasbor.php`, `admin/pengecekan.php` | `dasbor` | b `RingkasanDasborTest`, f `DasborKelengkapanTest` |
| Pendaftaran: daftar, rincian, ekspor, massal | `pendaftaran[/data\|/excel\|/ekspor\|/massal\|/(:num)]` | `Admin/PendaftaranController` | `Panel/DataPanelPendaftaran`, `Panel/TabelSisiServer`, `Panel/PaketPendaftaran`, `Panel/AsalRincian` | `admin/pendaftaran/index.php`, `rincian.php` | `admin-pendaftaran`, `tabel-admin` | f `PanelPendaftaranHalamanTest`, `PanelPaketPendaftaranTest` |
| Lunas/menunggu, hapus | `…/(:num)/lunas\|menunggu\|hapus` | idem | `Panel/StatusPembayaran`, `Panel/HapusPendaftaran` | - | - | b `PanelPendaftaranStatusTest`, `PanelPendaftaranHapusTest` |
| Ubah | `…/(:num)/ubah`, POST `…/(:num)` | idem `ubah`, `perbarui` | `Panel/UbahPendaftaran` | `admin/pendaftaran/ubah.php` | `admin-ubah-pendaftaran`, `pendaftaran` | b/f `PanelUbahPendaftaran*Test` |
| Bukti bayar | `…/(:num)/bukti-bayar[/(:num)[/unduh\|/hapus]]` | `Admin/BuktiBayarController` | `Panel/BerkasBuktiBayar` | `admin/pendaftaran/_bukti_bayar.php` | - | `PanelBuktiBayar*Test` |
| Bukti bayar baru (F8-02) | POST `…/(:num)/bukti-bayar/diperiksa`, saringan `pendaftaran?bukti=baru` | `Admin/BuktiBayarController::diperiksa` | `Panel/BuktiDiperiksa` (dipanggil `StatusPembayaran`), `Panel/DataPanelPendaftaran`, `Panel/RingkasanDasbor` | `admin/pendaftaran/_bukti_bayar.php`, `index.php`, `admin/dasbor.php` | `admin-pendaftaran` | f `PanelBuktiBaruTest` |
| Tolak atlet (K116) | `…/(:num)/tolak-atlet` | `Admin/TolakAtletController` | `Panel/TolakAtlet` | `admin/pendaftaran/tolak_atlet.php` | - | b/f `PanelTolakAtlet*Test` |
| Riwayat tindakan admin (F8-01) | bagian Riwayat di `…/(:num)`, cari `riwayat` | `Admin/PendaftaranController::riwayat` | `Panel/RiwayatAdmin` (dipanggil `StatusPembayaran`, `UbahPendaftaran`, `TolakAtlet`, `HapusPendaftaran`, `BerkasBuktiBayar`, `BuktiBayarController`, `UrutkanNomorDada`, `GabungPendaftaran`, `PaketPendaftaran`) | `admin/pendaftaran/_riwayat.php`, `riwayat.php` | - | f `PanelRiwayatAdminTest` |
| Gabung NPSN (K109) | otomatis saat lunas; spark `pendaftaran:gabung` | `Commands/GabungPendaftaran` | `Panel/GabungPendaftaran` | - | - | b/f `PanelGabungPendaftaran*Test` |
| Atlet | `atlet[/data\|/excel]` | `Admin/AtletController` | `Panel/TabelSisiServer`, `Panel/EksporExcel` | `admin/atlet/index.php` | `tabel-admin` | f `PanelAtletTest` |
| Urutkan nomor dada (K111) | `atlet/nomor-dada` | `Admin/NomorDadaController` | `Panel/UrutkanNomorDada`, `Panel/NomorDada` | `admin/atlet/nomor_dada.php` | - | `PanelUrutkanNomorDada*Test`, `PanelNomorDadaTest` |
| Atlet ditolak (K117) | `atlet-ditolak[/data\|/excel]` | `Admin/AtletDitolakController` | `AtletDitolak` | `admin/atlet_ditolak/` | `tabel-admin` | f `PanelTolakAtletHalamanTest` |
| Rekap | `rekap[/<jenis>[/data\|/excel\|/pdf]]`, `rekap/gabungan/excel` | `Admin/RekapController` | `Panel/Rekap`, `Panel/RekapPdf`, `Panel/EksporExcel` | `admin/rekap/` | `tabel-admin` | f `PanelRekapTest`, u `PanelEksporExcelTest` |
| Hari lomba (F8-03): daftar ulang QR + hadir, BIB massal, daftar peserta | `daftar-ulang[/(:num)/hadir\|batal-hadir]`, `nomor-atlet/cetak[/pdf]`, `daftar-peserta[/(:num)/pdf\|excel]`, `daftar-peserta/kelompok-umur/(:num)/pdf\|excel` | `Admin/DaftarUlangController`, `…/CetakBibController`, `…/DaftarPesertaController`; tautan admin di `BuktiController` | `HariLomba/KodeDaftarUlang`, `DaftarUlang`, `CetakBib`, `DaftarPeserta`, `DaftarPesertaPdf`, `PdfHariLomba` | `admin/hari_lomba/` | `daftar-ulang`, `konfirmasi` | f `HariLombaDaftarUlangTest`, `HariLombaCetakTest`, u `HariLombaKodeDaftarUlangTest` |
| Sekolah, usulan nama (K99), nama desa (K114) | `sekolah[/data\|/excel\|/(:num)/ubah]`, `sekolah/usulan…`, `sekolah/nama-desa` | `Admin/SekolahController` | `Panel/UsulanNamaSekolah`, `Panel/NamaDesaSekolah` | `admin/sekolah/` | `centang-semua` | f `PanelSekolahTest`, `PanelUsulanNamaSekolahTest`, `PanelNamaDesaSekolah*Test` |
| Pengaturan | `pengaturan/{acara,pendaftaran,pembayaran,halaman-depan}` + CRUD `{kelompok-umur,nomor-lomba,jadwal,kontak,unduhan}` | `Admin/Pengaturan/*Controller` | `KelengkapanPengaturan`, `MarkdownSederhana` | `admin/pengaturan/` | `konfirmasi`, `pilih-tanggal` | f `Pengaturan*Test`, b `*ModelTest` |

## Perintah spark (`app/Commands/`)

`admin:buat`, `cadangan:buat`, `data:awal`, `dokumen:pdf`, `pendaftaran:gabung`, `pendaftaran:impor`, `sekolah:impor`, `aset:minify`, `pembaruan:periksa`, `nomor-dada:reset` (mis. `nomor-dada:reset` = `ResetNomorDada.php`).

## Invarian kritis

- NISN, `nomor_dada` unik di DB (`Migrations/…BuatTabelAtlet.php`); `sekolah.npsn` dan `pendaftaran.kode_bukti` juga unik. Bentrok NISN → galat NISN biasa.
- Simpan: `GET_LOCK('milklife-pendaftaran-<npsn>')`, periksa ulang, satu transaksi; gagal → rollback + hapus berkas (`Pendaftaran/SimpanPendaftaran`). Ubah/tolak/lunas: `GET_LOCK` + `SELECT … FOR UPDATE` (`Panel/UbahPendaftaran`, `TolakAtlet`, `StatusPembayaran`).
- Nomor dada dibagi saat Lunas, dalam transaksi; `awalan_nomor_dada` dikunci `FOR UPDATE`; `nomor_terakhir` hanya naik; awalan penuh → penandaan batal (`Panel/StatusPembayaran`).
- Uang = bilangan bulat rupiah; tim susulan biaya 0 (`Pendaftaran/BiayaPendaftaran`). Biaya dihitung ulang di server dari data DB (`PemeriksaPendaftaran::susun`).
- Bukti transfer wajib (K90). Foto penanggung jawab dan bukti bayar di `WRITEPATH/unggahan/…`, di luar `public/` (`Pendaftaran/FotoPenanggungJawab`, `Panel/BerkasBuktiBayar`).
- `CF-Connecting-IP` hanya dipercaya dari 127.0.0.1/::1 (tunnel) (`Config/App.php` `$proxyIPs`).
- Buka/tutup dari pengaturan, kelas murni (`BukaTutupPendaftaran`), dicek pengendali sebelum simpan.
- Bukti PDF hanya lewat kode acak 24 heksa + ID (K77), dibatasi `throttler`.
- Perutean otomatis mati (`Config/Routing.php`); admin dijaga `Filters/AdminFilter`.

## Perintah uji per area

- Semua: `composer uji` (= `phpunit`, `phpunit.dist.xml`). Suite: `vendor/bin/phpunit --testsuite unit|basisdata|fitur`.
- Satu area: `vendor/bin/phpunit --filter '<pola>'`, pola:
  wizard `Pendaftaran(Halaman|Kirim|Simpan|Pemeriksa|Sekolah)` · cek/bukti `CekPendaftaran|BuktiPendaftaran|UnggahBuktiBayar` · panel pendaftaran `PanelPendaftaran|PanelUbahPendaftaran|BuktiBayar|PanelRiwayatAdmin|PanelBuktiBaru` · tolak/gabung `PanelTolakAtlet|PanelGabungPendaftaran` · nomor dada `NomorDada` · rekap `PanelRekap` · sekolah `Sekolah` · pengaturan `Pengaturan` · admin `MasukAdmin|AkunAdmin` · hari lomba `HariLomba` · alur Tahap 6 `PanelGabunganTest`.
