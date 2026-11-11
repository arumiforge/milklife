# F2-09 Kelengkapan pengaturan dan dasbor

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Dasbor menampilkan daftar centang hal yang belum lengkap dan keadaan pendaftaran, dan halaman daftar pengaturan menandai halaman yang belum lengkap.

## Hasil

- [x] `KelengkapanPengaturan` (murni): butir sesuai rancangan revisi 3 bagian 6.1 (batas tahun lahir K36, tanpa tanggal patokan usia K37), tiap butir punya label, lengkap atau belum, keterangan yang menyebut apa yang kurang (misalnya nama kelompok umur yang belum punya jenjang), dan alamat halaman untuk melengkapinya
- [x] `BukaTutupPendaftaran` (murni): keadaan `disiapkan`, `belum_dibuka`, `terbuka`, `ditutup` beserta kalimat ramahnya (contoh: "Pendaftaran dibuka 1 Oktober 2026")
- [x] `DataKelengkapan`: mengumpulkan potret data dari pengaturan dan tabel lewat pembangun kueri
- [x] Dasbor: daftar centang dengan tautan, keadaan pendaftaran, keadaan kosong pendaftaran tetap ada
- [x] Halaman daftar pengaturan: tanda lengkap atau belum per halaman
- [x] Uji unit kedua kelas murni (termasuk batas waktu tepat dan buka manual yang lewat), uji fitur dasbor

## Selesai bila

`composer uji` lulus; pada basis data kosong semua butir belum lengkap; setelah `data:awal` dan pengisian semua halaman, semua butir bercentang dan keadaan pendaftaran sesuai waktu.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Cara memakai (untuk Tahap 3 dan 4)

- `(new DataKelengkapan())->bukaTutup()` → `BukaTutupPendaftaran` pada jam sekarang (WIB): `terbuka()`, `keadaan()` (konstanta `DISIAPKAN`, `BELUM_DIBUKA`, `TERBUKA`, `DITUTUP`), `kalimat()` ("Pendaftaran dibuka 1 Oktober 2026."), `label()` ("Belum dibuka"), `batas()`, `lewatManual()`. Halaman depan menampilkan `kalimat()`; wizard menolak bila `terbuka()` false. Contoh lengkap di komentar kelasnya.
- `(new DataKelengkapan())->kelengkapan()` → `KelengkapanPengaturan`: `lengkap()`, `butir()`, `belumLengkap()`, `untukHalaman($kunci)`, `jumlahLengkap()`. Kunci butir sama dengan kunci halaman di `BerandaPengaturanController::daftarHalaman()`.
- Di uji, waktu dasbor diatur dengan `Time::setTestNow(...)`; kelas murninya menerima waktu sekarang sebagai argumen.

### Keputusan kecil saat pengerjaan

- **Lima butir, satu per halaman wajib**: `acara` (Data acara), `pendaftaran` (Jadwal pendaftaran), `kelompok-umur`, `nomor-lomba`, `pembayaran` (Rekening pembayaran). Tanggal patokan usia bukan butir (K37). Batas tahun lahir cukup salah satu dari `tahun_lahir_awal`/`tahun_lahir_akhir` (K36).
- **Terisi** berarti: teks tidak kosong setelah dipangkas; seri, tahun, dan tahun lahir bilangan bulat > 0; biaya individu bilangan bulat ≥ 0 (0 = gratis tetap terisi); tanggal harus terbaca (`Y-m-d` atau `Y-m-d H:i[:s]`).
- **Nomor beregu memakai aturan F2-04 persis** (`NomorLombaModel::kekuranganTim()`, disalin karena F2-09 tidak memakai model tugas lain): jumlah inti, batas anggota, biaya beregu, dan untuk mix jumlah putra dan putri minimal dianggap kosong hanya bila `null` atau `''`; labelnya sama ("jumlah putra dan putri minimal"). Biaya beregu 0 tetap terisi, supaya tanda di daftar nomor lomba dan dasbor tidak bertentangan; formulir F2-04 sudah menolak 0. Urutan tanggal (akhir sesudah mulai) tidak diperiksa di sini; itu tugas validasi formulir F2-02.
- **Hanya yang aktif dinilai**: kelompok umur mati diabaikan; nomor lomba dinilai hanya bila nomornya aktif dan kelompok umurnya aktif, karena nomor pada kelompok umur mati tidak muncul di pendaftaran. Nomor individu tidak punya syarat tambahan.
- **Keterangan spesifik**: satu kalimat per hal yang kurang, contoh "KU 10, KU 12 belum punya jenjang sekolah." Nomor beregu disebut `<nama> <kategori> (<kelompok umur>)`. Daftar lebih dari 5 nama dipendekkan: tiga pertama lalu "dan N nomor/kelompok umur lainnya". Butir lengkap diberi ringkasan pendek ("5 kelompok umur aktif sudah lengkap.").
- **Batas waktu** dibaca dengan ketelitian menit: terbuka sejak detik ke-0 menit mulai sampai detik ke-59 menit akhir (akhir 23.59 → tutup pukul 00.00.00). Buka manual berlaku sampai detik ke-59 menit batasnya, juga sebelum jadwal mulai; buka manual tanpa batas yang terbaca tidak berlaku. Bila jadwal dan buka manual sama-sama berlaku, batasnya yang paling akhir. Setelah buka manual lewat sebelum jadwal mulai, keadaannya kembali `belum_dibuka`. Kalimat "dibuka" menyebut jam hanya bila bukan 00.00.
- **Zona waktu**: teks waktu tersimpan dibaca di `Asia/Jakarta` (`Config\App::$appTimezone`); waktu sekarang zona lain dibandingkan dengan benar.
- **`DataKelengkapan` tahan tabel yang belum ada**: bila tabel tak terbaca (basis data belum dimigrasi), data tabel dianggap kosong dan galatnya dicatat di log, sehingga dasbor tetap terbuka. Ini juga membuat uji halaman tanpa basis data (`UjiHalaman`) yang membuka dasbor dan daftar pengaturan tetap lulus pada basis data uji kosong. Kolom tahun lahir dibaca dengan `?? null`, jadi kode jalan sebelum dan sesudah migrasi F2-03.
- **Dasbor**: kartu "Keadaan pendaftaran" (lencana, kalimat, jadwal, buka manual, tombol ke halaman Pendaftaran), kartu "Kelengkapan pengaturan" (ringkasan "N dari 5", bilah kemajuan, daftar centang bertautan, baris "Langkah berikutnya" selama belum lengkap), keadaan kosong "Belum ada pendaftaran", dan gambar kepala halaman depan (gambar bila sudah diunggah, selain itu `_tanpa_gambar`). Kartu "Langkah berikutnya" lama diganti daftar centang; teks "Langkah berikutnya" tetap ada karena diperiksa `TataLetakHalamanTest`.
- **Halaman daftar pengaturan**: hanya lima kartu wajib yang diberi tanda; Halaman Depan, Jadwal, Unduhan, Kontak, dan Akun tanpa tanda (`lengkap` tetap `null`). Keterangan kartu disesuaikan K36/K37: Kelompok Umur "Batas tahun lahir, …", Pendaftaran "Jadwal buka dan tutup, tampilan usia, dan batas-batas pendaftaran."
- **Uji di luar bagian tugas (disetujui koordinator)**: `tests/fitur/BerandaPengaturanTest.php` — pemeriksaan "0 tanda-kelengkapan" diganti pemeriksaan bahwa tanda hanya ada pada lima kartu wajib, dan teks kartu Kelompok Umur menjadi "Batas tahun lahir, …".
- **Uji bergantung pada F2-03**: `tests/basisdata/DataKelengkapanTest.php` dan `tests/fitur/DasborKelengkapanTest.php` menambahkan kolom `tahun_lahir_awal`/`tahun_lahir_akhir` sendiri di persiapan bila migrasi F2-03 belum ada (dicek dengan `SHOW COLUMNS`, karena `fieldExists()` menyimpan hasil lama). Pemeriksaan "setelah `data:awal`" tidak bergantung pada apakah data awal sudah mengisi tahun lahir. Setelah F2-03 digabung, persiapan itu tidak melakukan apa-apa dan bisa dihapus di F2-10.

