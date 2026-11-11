# Rencana Implementasi Tahap 2 — Pengaturan

| | |
|---|---|
| Status | Disetujui pemilik 11 November 2026 (K27), termasuk asumsi di bagian akhir |
| Tanggal | 11 November 2026 |
| Dasar | Rancangan revisi 1 (`dokumen/final/rancangan-web-pendaftaran.md`) bagian 5, 6, 9, 10, 11, 16, 17 dan lampiran; keputusan K01–K26; hasil Tahap 1 (`agent/arsip/tahap-1.md`) |

## Tujuan

Untuk pelaksana tiap tugas Tahap 2 dan untuk pemilik yang meninjau. Setelah membaca, pelaksana tahu berkas mana yang menjadi tanggung jawabnya dan kesepakatan apa yang tidak boleh diubah, sehingga tujuh tugas bisa dikerjakan bersamaan tanpa bentrok.

## Hasil Tahap 2

Setelah Tahap 2 selesai, admin yang sudah masuk bisa:

- membuka menu **Pengaturan** dan melihat semua halaman pengaturan beserta tanda lengkap atau belum,
- mengisi data acara, jadwal dan aturan pendaftaran, rekening pembayaran, dan isi halaman depan (teks dan gambar),
- mengelola kelompok umur (batas tahun lahir, jenjang yang boleh ikut, biaya individu, awalan nomor dada) dan nomor lomba,
- mengelola jadwal, berkas unduhan, narahubung, dan media sosial,
- mengganti nama, nama pengguna, dan kata sandinya sendiri di **Akun**,
- melihat di dasbor daftar centang hal yang belum lengkap dan keadaan pendaftaran (sedang disiapkan, belum dibuka, terbuka, ditutup).

Saat pemasangan, `php spark data:awal` mengisi data acara, tanggal pendaftaran, lima kelompok umur dengan batas tahun lahirnya, dan semua nomor lomba dari flyer Seri 2 (K25).

Belum ada di Tahap 2: tampilan halaman depan yang memakai isi pengaturan (Tahap 3), wizard pendaftaran (Tahap 4), bukti dan cek (Tahap 5), daftar pendaftaran, rekap, dan angka ringkasan di dasbor (Tahap 6).

## Tugas dan urutan

| Tugas | Isi singkat | Urutan |
|---|---|---|
| F2-01 Fondasi pengaturan | Migrasi jenjang, kelas pengaturan, semua rute, menu, halaman daftar pengaturan, potongan tampilan bersama, unggahan, perintah `data:awal` | Pertama, sendiri |
| F2-02 Acara dan pendaftaran | `/admin/pengaturan/acara`, `/admin/pengaturan/pendaftaran`; nama acara di tata letak dan beranda diambil dari pengaturan | Paralel |
| F2-03 Kelompok umur | `/admin/pengaturan/kelompok-umur`: batas tahun lahir, centang jenjang, biaya, awalan nomor dada | Paralel |
| F2-04 Nomor lomba | `/admin/pengaturan/nomor-lomba` | Paralel |
| F2-05 Pembayaran dan halaman depan | `/admin/pengaturan/pembayaran`, `/admin/pengaturan/halaman-depan` (dengan unggah gambar) | Paralel |
| F2-06 Jadwal dan kontak | `/admin/pengaturan/jadwal`, `/admin/pengaturan/kontak` | Paralel |
| F2-07 Unduhan | `/admin/pengaturan/unduhan` (dengan unggah berkas) | Paralel |
| F2-08 Akun admin | `/admin/akun`, perbaikan aturan unik nama pengguna | Paralel |
| F2-09 Kelengkapan dan dasbor | Aturan kelengkapan dan buka-tutup pendaftaran, daftar centang di dasbor, tanda di halaman daftar pengaturan | Paralel |
| F2-10 Uji gabungan dan penutupan | Uji setelah semua digabung, perbaikan sambungan, arsip tahap | Terakhir |

F2-01 digabung ke `main` lebih dulu. F2-02 sampai F2-09 lalu dikerjakan bersamaan, masing-masing satu sub-sesi, satu cabang `fitur/F2-xx-<slug>`, dan satu PR. Sebelum digabung, koordinator menguji semua cabang bersama dalam satu salinan lokal. F2-10 dikerjakan koordinator setelah semuanya digabung.

Pendaftaran di flyer ditutup 30 November 2026 (K19), jadi tugas paralel dibuat kecil dan tidak saling menunggu.

## Kesepakatan bersama

Ditetapkan oleh F2-01, berlaku di atas kesepakatan Tahap 1 (`dokumen/draf/rencana-implementasi-tahap-1.md`). Tugas lain memakainya dan tidak mengubahnya.

### Kunci pengaturan

Pengaturan kunci–nilai disimpan lewat `codeigniter4/settings`, dibaca dengan `setting('Acara.nama')` dan ditulis dengan `setting('Acara.nama', $nilai)`. Nilai awal ada di kelas `Config` milik F2-01. Nilai kosong ditulis `''` (teks) atau `null` (tanggal), bukan dihapus.

| Kelas | Kunci | Isi | Nilai awal di kode |
|---|---|---|---|
| `Config\Acara` | `nama` | Nama acara, contoh `MilkLife Athletics Challenge` | `''` |
| | `seri` | Nomor seri, bilangan bulat | `null` |
| | `tahun` | Tahun acara, bilangan bulat | `null` |
| | `tempat` | Contoh `Supersoccer Arena, Kudus` | `''` |
| | `tanggal_mulai`, `tanggal_selesai` | Tanggal acara, `Y-m-d` | `null` |
| `Config\Pendaftaran` | `mulai`, `akhir` | Waktu buka dan tutup, `Y-m-d H:i:s` (WIB) | `null` |
| | `buka_manual` | Dibuka manual oleh admin, `bool` | `false` |
| | `buka_manual_sampai` | Batas buka manual, `Y-m-d H:i:s`, wajib bila `buka_manual` | `null` |
| | `tampilan_usia` | `tahun` atau `tahun_bulan`; hanya tampilan, usia dihitung pada `Acara.tanggal_mulai` (K37) | `tahun` |
| | `penanggung_jawab_minimal`, `penanggung_jawab_maksimal` | Bilangan bulat | `1`, `5` |
| | `nomor_individu_maksimal` | Bilangan bulat | `2` |
| `Config\Pembayaran` | `bank`, `nomor_rekening`, `pemilik_rekening` | Teks; nomor rekening hanya angka | `''` |
| | `nomor_wa` | Nomor WA pemilik rekening, bentuk baku (lihat "Nomor WA") | `''` |
| | `logo_bank` | Jalur gambar unggahan, boleh kosong | `''` |
| | `templat_pesan` | Isi pesan konfirmasi, dengan penanda `{nama_sekolah}`, `{npsn}`, `{id_pendaftaran}`, `{subtotal_individu}`, `{subtotal_beregu}`, `{total}` | contoh pesan berbahasa Indonesia |
| `Config\HalamanDepan` | `gambar_kepala`, `logo_persegi`, `logo_persegi_panjang`, `logo_djarum` | Jalur gambar unggahan, boleh kosong | `''` |
| | `judul`, `teks`, `footer` | Teks | `''` |
| | `nomor_wa_tanya` | Nomor WA untuk tombol tanya admin | `''` |
| | `pesan_tanya`, `pesan_bagikan` | Teks pesan WA yang sudah diatur | `''` |

Kunci baru di luar tabel ini perlu persetujuan koordinator, supaya F2-09 (kelengkapan) dan Tahap 3–4 membaca kunci yang sama.

### Jenjang

- Kode jenjang mengikuti ID pendaftaran: `A` = SD/MI, `B` = SMP/MTs, `C` = SMA/SMK/MA. Kode dan labelnya hanya ditulis di `App\Libraries\Jenjang` (F2-01).
- Tabel baru `kelompok_umur_jenjang` (F2-01): `id`, `kelompok_umur_id` (kunci tamu, hapus berantai), `jenjang` `ENUM('A','B','C')`, `dibuat_pada`, `diubah_pada`; unik per pasangan `kelompok_umur_id` + `jenjang`. Satu baris berarti jenjang itu boleh ikut kelompok umur tersebut. Tanpa baris berarti belum ada jenjang yang dibuka.
- Migrasi Tahap 2 memakai awalan `2026-11-12-000100_`. Tugas lain tidak membuat migrasi; bila perlu, berhenti dan lapor ke koordinator.

### Rute

Semua rute Tahap 2 ditulis F2-01 di `app/Config/Routes.php`, di dalam grup `admin` yang sudah dijaga filter `admin`. Pengendali ada di `App\Controllers\Admin\Pengaturan\` (kecuali Akun di `App\Controllers\Admin\AkunController`).

| Halaman | Rute | Pengendali | Tugas |
|---|---|---|---|
| Daftar pengaturan | `GET admin/pengaturan` | `Pengaturan\BerandaPengaturanController::index` | F2-01, tanda oleh F2-09 |
| Satu formulir | `GET` dan `POST admin/pengaturan/acara` | `AcaraController::index`, `::simpan` | F2-02 |
| | `GET` dan `POST admin/pengaturan/pendaftaran` | `PendaftaranController` | F2-02 |
| | `GET` dan `POST admin/pengaturan/pembayaran` | `PembayaranController` | F2-05 |
| | `GET` dan `POST admin/pengaturan/halaman-depan` | `HalamanDepanController` | F2-05 |
| | `GET` dan `POST admin/akun` | `AkunController::index`, `::simpan` | F2-08 |
| Daftar data | `admin/pengaturan/kelompok-umur` | `KelompokUmurController` | F2-03 |
| | `admin/pengaturan/nomor-lomba` | `NomorLombaController` | F2-04 |
| | `admin/pengaturan/jadwal` | `JadwalController` | F2-06 |
| | `admin/pengaturan/kontak` | `KontakController` | F2-06 |
| | `admin/pengaturan/unduhan` | `UnduhanController` | F2-07 |

Setiap halaman daftar data memakai pola yang sama:

| Rute | Metode pengendali | Isi |
|---|---|---|
| `GET <dasar>` | `index` | Daftar |
| `GET <dasar>/tambah` | `tambah` | Formulir kosong |
| `POST <dasar>` | `buat` | Simpan baru |
| `GET <dasar>/(:num)/ubah` | `ubah/$1` | Formulir terisi |
| `POST <dasar>/(:num)` | `perbarui/$1` | Simpan perubahan |
| `POST <dasar>/(:num)/hapus` | `hapus/$1` | Hapus, setelah jendela konfirmasi |

Halaman akun memakai dua formulir di satu halaman: `POST admin/akun` (nama dan nama pengguna) dan `POST admin/akun/sandi` (kata sandi), metode `simpan` dan `simpanSandi`.

### Pola halaman

- **Simpan berhasil:** alihkan (`redirect`) ke halaman daftar atau halaman formulir itu sendiri dengan pesan kilat `pesan_sukses`, misalnya "Data acara tersimpan.".
- **Isian salah:** formulir langsung ditampilkan lagi (status 200, tanpa pengalihan) dengan isian yang diketik dan pesan per kotak (`is-invalid` + `invalid-feedback`), ditambah ringkasan di atas formulir. Pesan menyebut cara memperbaikinya.
- **Gagal karena keadaan data** (misalnya hapus yang tidak diizinkan): alihkan dengan pesan kilat `pesan_galat` yang menyebut sebabnya dan jalan keluarnya, misalnya "Kelompok umur ini sudah dipakai atlet, jadi tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran."
- Pesan kilat ditampilkan tata letak admin lewat `admin/_pesan.php` (F2-01); halaman tidak menampilkannya sendiri.
- Hapus selalu lewat formulir `POST` dengan token CSRF dan atribut `data-konfirmasi="<kalimat yang menyebut apa yang terjadi>"`; `aset/js/konfirmasi.js` (F2-01) menampilkan jendela konfirmasi Bootstrap.
- Setiap halaman pengaturan menampilkan navigasi pengaturan `admin/pengaturan/_navigasi.php` (F2-01) dan judul di kepala lewat bagian `judul_halaman`.
- Keadaan kosong: pesan ramah yang menjelaskan apa yang belum ada dan tombol langkah berikutnya.
- Di ponsel (390 piksel) tabel daftar boleh digulir mendatar di dalam kartunya, halaman tidak.
- Uang diketik sebagai angka rupiah tanpa titik (titik dan `Rp` yang ikut diketik dibuang server), ditampilkan dengan `rupiah()` dari `aplikasi_helper` (F2-01): `Rp150.000`.
- Tanggal ditampilkan dalam bahasa Indonesia, contoh `30 November 2026` dan `30 November 2026 pukul 23.59`, lewat `tanggal_id()` dari `aplikasi_helper` (F2-01).

### Unggahan

- `App\Libraries\Unggahan` (F2-01) menyimpan berkas ke `public/unggahan/<folder>/<nama acak>.<ekstensi>` dan mengembalikan jalur relatif `unggahan/<folder>/<nama>` yang disimpan di pengaturan atau tabel. Alamatnya `base_url($jalur)`.
- Folder: `halaman-depan`, `pembayaran`, `unduhan`. `public/unggahan/` tidak masuk git.
- Gambar: JPG, PNG, atau WEBP, paling besar 2 MB, diperiksa isi berkasnya, bukan hanya namanya. Berkas unduhan: PDF, JPG, PNG, DOC/DOCX, XLS/XLSX, paling besar 10 MB.
- Ekstensi disimpan dari daftar yang diizinkan, tidak pernah dari nama berkas pengunggah, sehingga tidak ada berkas yang bisa dijalankan di folder publik.
- Mengganti atau menghapus gambar/berkas menghapus berkas lama.
- Gambar yang belum diunggah ditampilkan dengan `tata_letak/_tanpa_gambar`.
- Batas unggah PHP di Laragon perlu minimal `upload_max_filesize = 10M` dan `post_max_size = 12M`; dicatat di `README.md` oleh F2-01. Unggahan yang melebihi batas PHP ditolak dengan pesan ramah, bukan galat.

### Nomor WA

`App\Libraries\NomorWa::normalkan()` (F2-01) mengubah `0812-3456-7890`, `+62 812 3456 7890`, dan `6281234567890` menjadi `6281234567890`; isian yang bukan nomor ponsel Indonesia (10–15 angka setelah dibakukan, diawali `628`) menghasilkan `null`. Semua nomor WA disimpan dalam bentuk baku ini.

### Basis data dan model

- Kesepakatan basis data Tahap 1 tetap berlaku.
- Setiap tugas membuat model untuk tabel halamannya sendiri (`KelompokUmurModel`, `AwalanNomorDadaModel`, `KelompokUmurJenjangModel` oleh F2-03; `NomorLombaModel` oleh F2-04; `JadwalModel` dan `TautanModel` oleh F2-06; `UnduhanModel` oleh F2-07). Tugas lain yang perlu membaca tabel itu memakai pembangun kueri (`db_connect()->table(...)`), bukan model tugas lain.
- Aturan menghapus:
  - Kelompok umur hanya bisa dihapus bila belum punya nomor lomba dan atlet, dan semua awalannya belum pernah dipakai (`nomor_terakhir = 0`). Awalan dan jenjangnya dihapus dalam satu transaksi.
  - Awalan yang sudah dipakai tidak bisa dihapus.
  - Nomor lomba hanya bisa dihapus bila belum dipakai atlet atau tim.
  - Selain itu, admin disarankan mematikan (`aktif = 0`) supaya tidak muncul di pendaftaran.
- Rentang tahun lahir kelompok umur aktif tidak boleh tumpang tindih (rancangan 6.4). Batas yang kosong berarti tidak terbatas di sisi itu.
- Migrasi `2026-11-12-000200_UbahBatasUsiaMenjadiTahunLahir` (F2-03) mengganti `usia_minimal`/`usia_maksimal` dengan `tahun_lahir_awal`/`tahun_lahir_akhir` (`SMALLINT UNSIGNED`, boleh kosong) (K36).

### Aturan domain

Kelas murni tanpa ketergantungan pada permintaan HTTP, di `app/Libraries/`, dengan uji unit:

| Kelas | Isi | Tugas |
|---|---|---|
| `Jenjang` | Kode dan label jenjang | F2-01 |
| `NomorWa` | Pembakuan nomor WA | F2-01 |
| `KelengkapanPengaturan` | Dari potret data pengaturan, hasilkan daftar butir kelengkapan (label, lengkap atau belum, keterangan, alamat halaman) sesuai rancangan 6.1 | F2-09 |
| `BukaTutupPendaftaran` | Dari kelengkapan, waktu mulai/akhir, buka manual, dan waktu sekarang, hasilkan keadaan: `disiapkan`, `belum_dibuka`, `terbuka`, `ditutup`, beserta kalimat ramahnya | F2-09 |

`BukaTutupPendaftaran` juga dipakai halaman depan (Tahap 3) dan wizard (Tahap 4).

### Uji

- `composer uji` tetap satu-satunya perintah. Uji Tahap 1 tidak diubah, kecuali yang terdampak langsung (misalnya uji tata letak saat nama acara diambil dari pengaturan); perubahannya disebut di laporan.
- F2-01 menambah sifat (trait) `Tests\Support\KirimFormulir` untuk mengirim `POST` sebagai admin dengan token CSRF sah, dan memastikan nilai pengaturan tidak terbawa dari satu uji ke uji berikutnya.
- Nama berkas uji diawali nama tugasnya supaya tidak bertabrakan: `Pengaturan<Halaman>Test.php`, `KelompokUmur*`, `NomorLomba*`, `Akun*`, `Kelengkapan*`, `BukaTutup*`, `Unggahan*`, `NomorWa*`, `DataAwal*`.
- Setiap halaman diuji: terbuka untuk admin, tertutup untuk yang belum masuk, simpan yang benar, isian salah ditolak dengan pesan, token CSRF wajib. Setiap aturan hapus diuji.

## Pembagian berkas

| Tugas | Berkas yang boleh disentuh |
|---|---|
| F2-01 | `app/Database/Migrations/2026-11-12-*`, `app/Config/Acara.php`, `app/Config/Pendaftaran.php`, `app/Config/Pembayaran.php`, `app/Config/HalamanDepan.php`, `app/Config/Routes.php`, `app/Libraries/Jenjang.php`, `app/Libraries/NomorWa.php`, `app/Libraries/Unggahan.php`, `app/Helpers/aplikasi_helper.php`, `app/Commands/DataAwal.php`, `app/Controllers/Admin/Pengaturan/BerandaPengaturanController.php`, `app/Views/admin/pengaturan/index.php`, `app/Views/admin/pengaturan/_navigasi.php`, `app/Views/admin/_pesan.php`, `app/Views/admin/_galat_isian.php`, `app/Views/tata_letak/*`, `public/aset/js/konfirmasi.js`, `public/aset/css/admin.css`, `.gitignore`, `README.md`, `tests/_support/*`, `tests/**/*Jenjang*`, `tests/**/*NomorWa*`, `tests/**/*Unggahan*`, `tests/**/*DataAwal*`, `tests/**/*BerandaPengaturan*`, `tests/**/*MigrasiTahap2*`, berkas tugasnya |
| F2-02 | `app/Controllers/Admin/Pengaturan/AcaraController.php`, `.../PendaftaranController.php`, `app/Views/admin/pengaturan/acara.php`, `.../pendaftaran.php`, `app/Views/tata_letak/publik.php`, `app/Views/tata_letak/admin.php`, `app/Views/tata_letak/polos.php`, `app/Views/beranda.php`, `tests/**/*PengaturanAcara*`, `tests/**/*PengaturanPendaftaran*`, uji tata letak Tahap 1 yang terdampak, berkas tugasnya |
| F2-03 | `app/Controllers/Admin/Pengaturan/KelompokUmurController.php`, `app/Models/KelompokUmurModel.php`, `app/Models/AwalanNomorDadaModel.php`, `app/Models/KelompokUmurJenjangModel.php`, `app/Views/admin/pengaturan/kelompok_umur/*`, `tests/**/*KelompokUmur*`, berkas tugasnya |
| F2-04 | `app/Controllers/Admin/Pengaturan/NomorLombaController.php`, `app/Models/NomorLombaModel.php`, `app/Views/admin/pengaturan/nomor_lomba/*`, `tests/**/*NomorLomba*`, berkas tugasnya |
| F2-05 | `app/Controllers/Admin/Pengaturan/PembayaranController.php`, `.../HalamanDepanController.php`, `app/Views/admin/pengaturan/pembayaran.php`, `.../halaman_depan.php`, `tests/**/*PengaturanPembayaran*`, `tests/**/*PengaturanHalamanDepan*`, berkas tugasnya |
| F2-06 | `app/Controllers/Admin/Pengaturan/JadwalController.php`, `.../KontakController.php`, `app/Models/JadwalModel.php`, `app/Models/TautanModel.php`, `app/Views/admin/pengaturan/jadwal/*`, `.../kontak/*`, `tests/**/*Jadwal*`, `tests/**/*Kontak*`, berkas tugasnya |
| F2-07 | `app/Controllers/Admin/Pengaturan/UnduhanController.php`, `app/Models/UnduhanModel.php`, `app/Views/admin/pengaturan/unduhan/*`, `tests/**/*Unduhan*`, berkas tugasnya |
| F2-08 | `app/Controllers/Admin/AkunController.php`, `app/Models/AdminModel.php`, `app/Views/admin/akun.php`, `tests/**/*Akun*`, `tests/basisdata/AdminModelTest.php` (tambahan uji), berkas tugasnya |
| F2-09 | `app/Libraries/KelengkapanPengaturan.php`, `app/Libraries/BukaTutupPendaftaran.php`, `app/Libraries/DataKelengkapan.php` (pengumpul data dari basis data dan pengaturan), `app/Controllers/Admin/DasborController.php`, `app/Views/admin/dasbor.php`, `app/Controllers/Admin/Pengaturan/BerandaPengaturanController.php`, `app/Views/admin/pengaturan/index.php`, `tests/**/*Kelengkapan*`, `tests/**/*BukaTutup*`, `tests/**/*Dasbor*`, berkas tugasnya |

`app/Views/tata_letak/*` milik F2-01 hanya sampai F2-01 digabung; setelah itu milik F2-02. Halaman daftar pengaturan milik F2-01 sampai digabung; setelah itu F2-09 menambahkan tanda lengkap atau belum.

Bila sebuah tugas ternyata perlu mengubah berkas di luar bagiannya, pelaksana berhenti dan melapor ke koordinator, bukan mengubahnya sendiri.

`agent/PROGRES.md` hanya diubah koordinator. Tiap PR tugas memperbarui status di berkas tugasnya sendiri.

## Data awal

`php spark data:awal` (F2-01) dijalankan sekali saat pemasangan, setelah `migrate --all`:

- Data acara: `MilkLife Athletics Challenge`, seri 2, tahun 2026, `Supersoccer Arena, Kudus`, 9–12 Desember 2026.
- Pendaftaran: mulai 1 Oktober 2026 pukul 00.00, akhir 30 November 2026 pukul 23.59.
- Lima kelompok umur dan nomor lomba sesuai lampiran rancangan, dengan satu awalan nomor dada per kelompok umur: KU 8 = 1, KU 10 = 2, KU 12 = 3, KU 15 = 4, KU 18 = 5 (K33). Batas tahun lahir (K36): KU 8 2019 ke atas, KU 10 2016–2018, KU 12 2015, KU 15 2011–2014, KU 18 2008–2010. Jenjang, biaya individu, serta batas anggota dan jumlah putra/putri nomor beregu dikosongkan untuk diisi admin.
- Perintah menolak berjalan bila tabel `kelompok_umur` sudah berisi atau pengaturan acara sudah terisi, sehingga tidak menimpa isian admin. Hasilnya diringkas di layar.

Data ini berasal dari flyer, bukan data siswa, jadi boleh ada di repo.

## Yang ditunda

| Hal | Ke tahap | Alasan |
|---|---|---|
| Halaman depan yang memakai gambar, logo, teks, jadwal, unduhan, dan kontak dari pengaturan | 3 | Rancangan bagian 16. Tahap 2 hanya menyimpan isinya; nama, seri, tahun, tempat, dan tanggal acara di tata letak dan beranda sudah diambil dari pengaturan (F2-02) |
| Angka ringkasan di dasbor | 6 | Baru bermakna setelah ada pendaftaran |
| Pengecilan gambar unggahan di server | 7 bila perlu | Batas 2 MB cukup untuk sekarang |

## Cara menguji di komputer pemilik

Setelah Tahap 2 digabung, di terminal Laragon pada folder proyek:

1. `composer install`
2. `php spark migrate --all`
3. `php spark data:awal`
4. `composer uji`
5. Buka `http://milklife.test/admin/masuk`, masuk, lalu buka menu **Pengaturan**. Dasbor menampilkan hal yang belum lengkap; isi satu per satu sampai semuanya bercentang.

## Asumsi yang perlu ditinjau

1. Awalan nomor dada berupa satu angka 1–9 (angka 0 tidak dipakai supaya nomor dada tidak kehilangan angka depan saat dibuka di Excel).
2. Urutan tampil diatur dengan kotak angka "Urutan" (angka kecil tampil lebih dulu), bukan seret-lepas.
3. Halaman Akun meminta kata sandi saat ini sebelum mengganti nama pengguna atau kata sandi.
4. Gambar paling besar 2 MB dan berkas unduhan paling besar 10 MB.
5. Templat pesan WA pembayaran punya isi awal yang bisa diubah admin.
6. Nomor beregu yang belum punya batas anggota, atau nomor mix yang belum punya jumlah putra dan putri, membuat pengaturan belum lengkap (rancangan revisi 6.1).
7. Data awal mengisi juga data acara dan tanggal pendaftaran dari flyer, bukan hanya kelompok umur dan nomor lomba; admin tetap memeriksanya di halaman pengaturan.

## Perubahan saat pengerjaan

| Bagian | Rencana | Yang berlaku | Alasan |
|---|---|---|---|
| Batas kelompok umur | Usia minimal dan maksimal dalam tahun penuh, kosong pada awalnya | Tahun lahir paling awal dan paling akhir, diisi `data:awal` | K36 |
| Tanggal patokan usia | Kunci `Pendaftaran.tanggal_patokan_usia`, diisi `data:awal`, butir kelengkapan | Dihapus; usia hanya tampilan, dihitung pada tanggal mulai acara | K37 |
| Pembagian berkas | Migrasi, `data:awal`, dan `Config\Pendaftaran` milik F2-01 | Migrasi tahun lahir, `app/Commands/DataAwal.php`, `tests/basisdata/DataAwalTest.php`, uji migrasi terdampak: F2-03. `app/Config/Pendaftaran.php`: F2-02 | F2-01 sudah digabung saat K36–K37 diputuskan |
