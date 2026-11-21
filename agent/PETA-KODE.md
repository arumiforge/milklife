# Peta kode

Peta berkas per fitur untuk sesi agen. Perbarui di PR yang sama bila memindah/menambah berkas.
Akar: Pengendali `app/Controllers/`, Pustaka `app/Libraries/`, View `app/Views/`, JS `public/aset/js/`. Uji u/b/f = `tests/unit|basisdata|fitur`.

## Publik

| Fitur | Rute | Pengendali | Pustaka | View | JS | Uji |
|---|---|---|---|---|---|---|
| Halaman depan (tata letak baru, F9-03) | `/` | `BerandaController` | `IsiHalamanDepan` (+ `carousel()`, `angkaAcara()`, `hasilTerbaru()`), `CarouselHalamanDepan`, `IlustrasiBeranda`, `BukaTutupPendaftaran` | `beranda.php`, `beranda/` (`_angka`, `_hasil` baru) | `carousel-beranda`, `adegan-merek` | f `HalamanDepan*`, u `BukaTutupPendaftaranTest`, `IlustrasiBerandaTest` |
| Wizard pendaftaran | `pendaftaran`, `pendaftaran/periksa`, `…/simpan` | `PendaftaranController` | `Pendaftaran/PemeriksaPendaftaran` (aturan di `Pendaftaran/Pemeriksa/*`), `…/SimpanPendaftaran` | `pendaftaran.php`, `pendaftaran/` | `pendaftaran`, `pendaftaran/*` | `Pendaftaran*Test` (u/b/f) |
| Cari NPSN/sekolah | `pendaftaran/sekolah` | `PendaftaranController::sekolah` | `Pendaftaran/Kabupaten`, `Jenjang` | `pendaftaran/_langkah_sekolah.php` | `pendaftaran/sekolah` | f `PendaftaranSekolahTest` |
| Cek pendaftaran | `cek-pendaftaran` | `CekPendaftaranController` | `DataPendaftaran` | `cek_pendaftaran.php`, `cek_pendaftaran/` | - | f `CekPendaftaranTest` |
| Bukti PDF + unggah bayar | `bukti/<24 heksa>/<id>`, `…/bukti-bayar` | `BuktiController` | `BuktiPendaftaran`, `Panel/BerkasBuktiBayar` | `bukti/` | - | f `BuktiPendaftaranTest`, `UnggahBuktiBayarOfficialTest` |
| Hasil lomba (F8-04) | `hasil`, `hasil/(:num)[/pdf\|excel]` (hanya nomor terbit) | `HasilController` | `Hasil/HasilLomba`, `Hasil/UnduhanHasil` | `hasil/` (publik `_tabel_tw`, tata letak baru F9-03) | - | f `HasilLombaTest` |
| Klasemen sekolah (F8-05) | `hasil/klasemen[/pdf\|excel]` (hanya nomor terbit) | `HasilController::klasemen` | `Hasil/Klasemen`, `Hasil/UnduhanKlasemen` | `hasil/klasemen.php`, `hasil/_klasemen_tw.php` (tata letak baru F9-03) | - | f `KlasemenSekolahTest`, u `HasilKlasemenTest` |
| Unduhan | `unduhan` | `UnduhanController` | `IsiHalamanDepan` | `unduhan.php` (tata letak baru F9-03), `beranda/_daftar_unduhan.php` | - | f `HalamanUnduhanTest` |
| Bantuan (F9-04) | `bantuan` (Tailwind, `publik_tw`); laci "Bantuan" di tiap langkah wizard (Bootstrap offcanvas sampai F9-05) | `BantuanController` | `Bantuan/IsiBantuan` (kategori, panduan G9-03, kontak WA), `Bantuan/JawabanFaq`, `MenuPublik` | `bantuan/index.php`, `pendaftaran/_laci_bantuan.php`, `pendaftaran/_tombol_bantuan.php` | `bantuan` | f `BantuanFaqTest` |

Pecahan berkas besar (F7-30), pintu masuk tetap sama:

- Wizard JS: bagian di `public/aset/js/pendaftaran/` (`dasar`, `draf`, `langkah`, `sekolah`, `ofisial`, `atlet`, `regu`, `atlet-formulir`, `atlet-daftar`, `biaya`, `turnstile`, `pembayaran`), lalu `pendaftaran.js` yang menjalankan wizard. Urutan muat di `app/Views/pendaftaran.php`; keadaan dan fungsi antarbagian di `window.Pendaftaran` (`dasar.js`). Tanpa alat bangun; `aset:minify` memperkecil subfolder juga.
- `Pendaftaran/Pemeriksa/`: `PeriksaSekolah`, `PeriksaPenanggungJawab`, `PeriksaAtlet` (+ `PeriksaNisn`, `PeriksaKelompokUmur`, `PeriksaNomor`, `PeriksaRegu`), `PeriksaTim`, `PeriksaKuota`, `SusunHasil`; bersama `IndukPeriksa`, `DaftarGalat`, `MasukanPeriksa`.
- `Panel/Ubah/`: `DataTersimpan`, `IsianUbah`, `RencanaUbah`, `BiayaUbah` (K68), `StatusUbah` (K32), `TulisUbah` (transaksi, K59, riwayat F8-01, K109), `MasukanUbah`.

## Admin (`admin/…`, filter `admin`)

| Fitur | Rute | Pengendali | Pustaka | View | JS | Uji |
|---|---|---|---|---|---|---|
| Masuk/akun | `masuk`, `keluar`, `akun`, `akun/sandi` | `Admin/MasukController`, `…/AkunController` | `SesiAdmin` | `admin/masuk.php`, `admin/akun.php` | - | f `MasukAdminTest`, `AkunAdminTest` |
| Dasbor/pengecekan | `dasbor`, `pengecekan` | `Admin/DasborController`, `…/PengecekanController` | `Panel/RingkasanDasbor`, `DataKelengkapan` | `admin/dasbor.php`, `admin/pengecekan.php` | `dasbor` | b `RingkasanDasborTest`, f `DasborKelengkapanTest` |
| Pendaftaran: daftar, rincian, ekspor, massal | `pendaftaran[/data\|/excel\|/ekspor\|/massal\|/(:num)]` | `Admin/PendaftaranController` | `Panel/DataPanelPendaftaran`, `Panel/TabelSisiServer`, `Panel/PaketPendaftaran`, `Panel/AsalRincian` | `admin/pendaftaran/index.php`, `rincian.php` | `admin-pendaftaran`, `tabel-admin` | f `PanelPendaftaranHalamanTest`, `PanelPaketPendaftaranTest` |
| Lunas/menunggu, hapus | `…/(:num)/lunas\|menunggu\|hapus` | idem | `Panel/StatusPembayaran`, `Panel/HapusPendaftaran` | - | - | b `PanelPendaftaranStatusTest`, `PanelPendaftaranHapusTest` |
| Ubah | `…/(:num)/ubah`, POST `…/(:num)` | idem `ubah`, `perbarui` | `Panel/UbahPendaftaran` (aturan di `Panel/Ubah/*`) | `admin/pendaftaran/ubah.php` | `admin-ubah-pendaftaran`, `pendaftaran` | b/f `PanelUbahPendaftaran*Test` |
| Bukti bayar | `…/(:num)/bukti-bayar[/(:num)[/unduh\|/hapus]]` | `Admin/BuktiBayarController` | `Panel/BerkasBuktiBayar` | `admin/pendaftaran/_bukti_bayar.php` | - | `PanelBuktiBayar*Test` |
| Bukti bayar baru (F8-02) | POST `…/(:num)/bukti-bayar/diperiksa`, saringan `pendaftaran?bukti=baru` | `Admin/BuktiBayarController::diperiksa` | `Panel/BuktiDiperiksa` (dipanggil `StatusPembayaran`), `Panel/DataPanelPendaftaran`, `Panel/RingkasanDasbor` | `admin/pendaftaran/_bukti_bayar.php`, `index.php`, `admin/dasbor.php` | `admin-pendaftaran` | f `PanelBuktiBaruTest` |
| Tolak atlet (K116) | `…/(:num)/tolak-atlet` | `Admin/TolakAtletController` | `Panel/TolakAtlet` | `admin/pendaftaran/tolak_atlet.php` | - | b/f `PanelTolakAtlet*Test` |
| Riwayat tindakan admin (F8-01) | bagian Riwayat di `…/(:num)`, cari `riwayat` | `Admin/PendaftaranController::riwayat` | `Panel/RiwayatAdmin` (dipanggil `StatusPembayaran`, `UbahPendaftaran`, `TolakAtlet`, `HapusPendaftaran`, `BerkasBuktiBayar`, `BuktiBayarController`, `UrutkanNomorDada`, `GabungPendaftaran`, `PaketPendaftaran`) | `admin/pendaftaran/_riwayat.php`, `riwayat.php` | - | f `PanelRiwayatAdminTest` |
| Gabung NPSN (K109) | otomatis saat lunas; spark `pendaftaran:gabung` | `Commands/GabungPendaftaran` | `Panel/GabungPendaftaran` | - | - | b/f `PanelGabungPendaftaran*Test` |
| Atlet | `atlet[/data\|/excel]` | `Admin/AtletController` | `Panel/TabelSisiServer`, `Panel/EksporExcel` | `admin/atlet/index.php` | `tabel-admin` | f `PanelAtletTest` |
| Urutkan nomor dada (K111) | `atlet/nomor-dada` | `Admin/NomorDadaController` | `Panel/UrutkanNomorDada`, `Panel/NomorDada` | `admin/atlet/nomor_dada.php` | - | `PanelUrutkanNomorDada*Test`, `PanelNomorDadaTest` |
| Atlet ditolak (K117) | `atlet-ditolak[/data\|/excel]` | `Admin/AtletDitolakController` | `AtletDitolak` | `admin/atlet_ditolak/` | `tabel-admin` | f `PanelTolakAtletHalamanTest` |
| Rekap | `rekap[/<jenis>[/data\|/excel\|/pdf]]`, `rekap/gabungan/excel` | `Admin/RekapController` | `Panel/Rekap`, `Panel/RekapPdf`, `Panel/EksporExcel` | `admin/rekap/` | `tabel-admin` | f `PanelRekapTest`, u `PanelEksporExcelTest` |
| Hari lomba (F8-03): daftar ulang QR + hadir, BIB massal, daftar peserta; BIB per sekolah, gambar JPG, latar, ZIP (K137) | `daftar-ulang[/(:num)/hadir\|batal-hadir]`, `nomor-atlet/cetak[/pdf\|/contoh\|/zip\|/tampilan]`, `nomor-atlet/cetak/sekolah/<npsn\|p<id>>/(pdf\|zip)`, `daftar-peserta[/(:num)/pdf\|excel]`, `daftar-peserta/kelompok-umur/(:num)/pdf\|excel` | `Admin/DaftarUlangController`, `…/CetakBibController`, `…/DaftarPesertaController`; tautan admin di `BuktiController` | `HariLomba/KodeDaftarUlang`, `DaftarUlang`, `CetakBib`, `TampilanBib`, `GambarBib`, `ZipAlir`, `UnduhanZipAlir`, `DaftarPeserta`, `DaftarPesertaPdf`, `PdfHariLomba`; `Config/Bib` | `admin/hari_lomba/` | `daftar-ulang`, `konfirmasi` | f `HariLombaDaftarUlangTest`, `HariLombaCetakTest`, `HariLombaBibSekolahTest`, u `HariLombaKodeDaftarUlangTest` |
| Hasil lomba (F8-04): babak, prestasi, peringkat, koreksi Final, Terbitkan | `hasil`, `hasil/nomor/(:num)[/babak\|terbitkan\|tarik\|pdf\|excel]`, `hasil/babak/(:num)[/peserta\|ubah\|hapus]` | `Admin/HasilController` | `Hasil/HasilLomba`, `Hasil/Prestasi`, `Hasil/Peringkat`, `Hasil/JenisHasil`, `Hasil/UnduhanHasil`; jenis hasil di `NomorLombaModel`; ikut `PaketPendaftaran` dan `GabungPendaftaran` | `admin/hasil/`, `hasil/_tabel.php`, `hasil/pdf.php` | `konfirmasi` | f `HasilLombaTest`, u `HasilPrestasiPeringkatTest` |
| Klasemen sekolah (F8-05): juara umum per jenjang, semua nomor | `klasemen[/pdf\|excel]` | `Admin/KlasemenController` | `Hasil/Klasemen` (murni), `Hasil/HasilLomba::juara()`, `Hasil/UnduhanKlasemen` | `admin/hasil/klasemen.php`, `hasil/_klasemen.php`, `hasil/klasemen_pdf.php` | - | f `KlasemenSekolahTest`, u `HasilKlasemenTest` |
| Sekolah, usulan nama (K99), nama desa (K114) | `sekolah[/data\|/excel\|/(:num)/ubah]`, `sekolah/usulan…`, `sekolah/nama-desa` | `Admin/SekolahController` | `Panel/UsulanNamaSekolah`, `Panel/NamaDesaSekolah` | `admin/sekolah/` | `centang-semua` | f `PanelSekolahTest`, `PanelUsulanNamaSekolahTest`, `PanelNamaDesaSekolah*Test` |
| Pengaturan | `pengaturan/{acara,pendaftaran,pembayaran,halaman-depan}` + CRUD `{kelompok-umur,nomor-lomba,jadwal,kontak,unduhan}`, sakelar `kelompok-umur/pilihan` (K134), foto carousel `halaman-depan/carousel[/(:num)/(naik\|turun\|hapus)]` (F9-03) | `Admin/Pengaturan/*Controller` (carousel: `CarouselController`) | `KelengkapanPengaturan`, `MarkdownSederhana` | `admin/pengaturan/` | `konfirmasi`, `pilih-tanggal` | f `Pengaturan*Test`, b `*ModelTest` |
| Bantuan dan FAQ (F9-04) | `pengaturan/bantuan[/tambah\|/bawaan\|/(:num)[/ubah\|/hapus\|/aktif\|/naik\|/turun]]` | `Admin/Pengaturan/FaqController` | `Bantuan/JawabanFaq` (Markdown + isian otomatis `{tutup_pendaftaran}` …), `Bantuan/FaqBawaan` (54 bawaan, dipasang `data:awal`, `faq:awal`, tombol bawaan); tabel `faq` ikut `PaketPendaftaran` | `admin/pengaturan/faq/` | `konfirmasi` | f `PengaturanFaqTest`, b `FaqAwalTest`, u `BantuanFaqBawaanTest`, `BantuanJawabanFaqTest` |

## Tampilan baru (Tahap 9, K140, K141)

| Bagian | Letak | Uji |
|---|---|---|
| Tata letak | `tata_letak/publik_tw.php` (menu `Libraries/MenuPublik`; dipakai halaman depan, Unduhan, Hasil, Klasemen sejak F9-03; pita judul `tata_letak/_judul_halaman_tw.php`), `tata_letak/admin_tw.php` (menu `tata_letak/_menu_admin_tw.php`), pesan kilat `tata_letak/_pesan_tw.php`; halaman pindah dengan mengganti `extend` | f `KatalogTampilanTest` |
| Komponen | `app/Views/komponen/` (tombol, kartu, peringatan, lencana, isian, pilihan, sakelar, modal, tabel, kerangka, kosong, langkah) lewat `komponen()`; ikon `ikon()`, atribut `atribut_html()` di `Helpers/tampilan_helper.php` | u `KomponenTampilanTest` |
| Katalog tampilan | `admin/gaya`, `Admin/GayaController`, `admin/gaya.php`; adegan Three.js `public/aset/js/adegan-merek.js` (modul ES) | f `KatalogTampilanTest`, `KepalaKeamananTest` |
| Bangun | `package.json`, `alat/vendor.mjs`, `alat/ikon.mjs`, `public/aset/tailwind/masukan.css` → `public/aset/css/tw.css`; job CI "Hasil bangun tampilan" | CI |

Ikon Lucide baru: tulis `ikon('nama')` (atau `'ikon' => 'nama'` di berkas yang memakai `ikon()`, atau komentar `// ikon-sprite: nama-1 nama-2` untuk daftar biasa), lalu `npm run ikon` dan `npm run css`.

## Perintah spark (`app/Commands/`)

`admin:buat`, `cadangan:buat`, `data:awal`, `dokumen:pdf`, `faq:awal`, `halaman-depan:carousel` (`PasangCarousel.php`), `pendaftaran:gabung`, `pendaftaran:impor`, `sekolah:impor`, `aset:minify`, `pembaruan:periksa`, `nomor-dada:reset` (mis. `nomor-dada:reset` = `ResetNomorDada.php`).

## Invarian kritis

- NISN, `nomor_dada` unik di DB (`Migrations/…BuatTabelAtlet.php`); `sekolah.npsn` dan `pendaftaran.kode_bukti` juga unik. Bentrok NISN → galat NISN biasa.
- Simpan: `GET_LOCK('milklife-pendaftaran-<npsn>')`, periksa ulang, satu transaksi; gagal → rollback + hapus berkas (`Pendaftaran/SimpanPendaftaran`). Ubah/tolak/lunas: `GET_LOCK` + `SELECT … FOR UPDATE` (`Panel/UbahPendaftaran` dan `Panel/Ubah/TulisUbah`, `TolakAtlet`, `StatusPembayaran`).
- Nomor dada dibagi saat Lunas, dalam transaksi; `awalan_nomor_dada` dikunci `FOR UPDATE`; `nomor_terakhir` hanya naik; awalan penuh → penandaan batal (`Panel/StatusPembayaran`).
- Uang = bilangan bulat rupiah; tim susulan biaya 0 (`Pendaftaran/BiayaPendaftaran`). Biaya dihitung ulang di server dari data DB (`Pendaftaran/Pemeriksa/SusunHasil`).
- Bukti transfer wajib (K90). Foto penanggung jawab dan bukti bayar di `WRITEPATH/unggahan/…`, di luar `public/` (`Pendaftaran/FotoPenanggungJawab`, `Panel/BerkasBuktiBayar`).
- `CF-Connecting-IP` hanya dipercaya dari 127.0.0.1/::1 (tunnel) (`Config/App.php` `$proxyIPs`).
- Buka/tutup dari pengaturan, kelas murni (`BukaTutupPendaftaran`), dicek pengendali sebelum simpan.
- Bukti PDF hanya lewat kode acak 24 heksa + ID (K77), dibatasi `throttler`.
- Kelompok umur atlet: `Pendaftaran/KelompokUmurAtlet::tentukan()` (jenjang → tahun lahir → pilihan bila lebih dari satu cocok, K95, K134), kembarannya di JS `pendaftaran/atlet.js` dan `admin-ubah-pendaftaran.js`; irisan per jenjang `KelompokUmurAtlet::irisan()`.
- Perutean otomatis mati (`Config/Routing.php`); admin dijaga `Filters/AdminFilter`.
- Tampilan baru: CSS `tw.css` dan pustaka di `public/aset/vendor/` hasil `npm run bangun`, di-commit; jangan disunting tangan (K141). Modul ES (`<script type="module" src>`) dan `import()` dari berkas lokal lolos CSP.
- CSP menyala (`Config/App.php`, `Config/ContentSecurityPolicy.php`, K127): view tanpa `style="…"`, atribut `on…`, atau `<script>`/`<style>` sebaris tanpa nonce (dijaga `KepalaKeamananTest`). Ukuran kecil memakai kelas di `tema.css` (`min-w-*`, `maks-w-*`, `lebar-*` lewat `lebar_bilah()`); skrip halaman di `public/aset/js/`; halaman galat memakai `csp_style_nonce()`; ApexCharts menerima nonce dari `data-nonce-gaya`.

## Perintah uji per area

- Analisis statis: `composer analisis` (PHPStan tingkat 1, `phpstan.neon`, K123). Jalan di CI sebelum uji.
- Semua: `composer uji` (= `phpunit`, `phpunit.dist.xml`). Suite: `vendor/bin/phpunit --testsuite unit|basisdata|fitur`.
- Satu area: `vendor/bin/phpunit --filter '<pola>'`, pola:
  wizard `Pendaftaran(Halaman|Kirim|Simpan|Pemeriksa|Sekolah)` · cek/bukti `CekPendaftaran|BuktiPendaftaran|UnggahBuktiBayar` · panel pendaftaran `PanelPendaftaran|PanelUbahPendaftaran|BuktiBayar|PanelRiwayatAdmin|PanelBuktiBaru` · tolak/gabung `PanelTolakAtlet|PanelGabungPendaftaran` · nomor dada `NomorDada` · rekap `PanelRekap` · sekolah `Sekolah` · pengaturan `Pengaturan` · bantuan `Faq|Bantuan` · admin `MasukAdmin|AkunAdmin` · hari lomba `HariLomba` · hasil `HasilLomba|HasilPrestasi|Klasemen` · alur Tahap 6 `PanelGabunganTest` · alur menyeluruh minify + Tahap 8 `AlurMenyeluruhTest`.
