# Rencana Implementasi Tahap 3 — Halaman depan

| | |
|---|---|
| Status | Disetujui pemilik 12 November 2026 (K43), sudah dilaksanakan (Tahap 3 selesai 12 November 2026) |
| Tanggal | 12 November 2026 |
| Dasar | Rancangan revisi 3 (`dokumen/final/rancangan-web-pendaftaran.md`) bagian 5, 6.1, 10, 12, 13, 15, 16; keputusan K01–K44; hasil Tahap 2 (`agent/arsip/tahap-2.md`) |

## Tujuan

Untuk pelaksana tiap tugas Tahap 3 dan untuk pemilik yang meninjau. Setelah membaca, pelaksana tahu berkas mana yang menjadi tanggung jawabnya dan kesepakatan apa yang tidak boleh diubah, sehingga tugas bisa dikerjakan bersamaan tanpa bentrok.

## Hasil Tahap 3

Setelah Tahap 3 selesai, pengunjung yang membuka halaman depan (`/`) melihat, dari atas ke bawah (K42):

1. **Kepala**: gambar kepala, logo, judul dan teks dari pengaturan, nama, seri, tahun, tempat, dan tanggal acara, keterangan keadaan pendaftaran (sedang disiapkan, belum dibuka, terbuka, ditutup), serta tombol **Pendaftaran** dan **Cek Pendaftaran**.
2. **Lomba**: kelompok umur (tahun lahir, jenjang yang boleh ikut, biaya individu), nomor lomba individu dan beregu (dengan jumlah anggota dan biaya tim).
3. **Jadwal**: sosialisasi, technical meeting, dan jadwal lain yang ditampilkan admin.
4. **Unduhan**: berkas yang ditampilkan admin, dengan tautan ke halaman `/unduhan`.
5. **Kontak**: narahubung (tautan WA), media sosial, tombol tanya admin lewat WA, dan tombol bagikan ke WA.
6. **Footer** dari pengaturan.

Halaman `/unduhan` berisi semua berkas unduhan, dan menu **Unduhan** di kepala aktif (K40).

Juga dirapikan: pesan ramah bila unggahan melebihi batas PHP, judul tab admin dan halaman masuk mengikuti nama acara, dan alamat IP pengunjung dibaca dari `CF-Connecting-IP` saat lewat Cloudflare Tunnel (K31).

Belum ada di Tahap 3: halaman pendaftaran (Tahap 4) dan cek pendaftaran (Tahap 5). Sampai halamannya jadi, kedua tombol tampil nonaktif dengan tulisan "Segera" (K39).

## Tugas dan urutan

| Tugas | Isi singkat | Urutan |
|---|---|---|
| F3-01 Fondasi halaman depan | Pengumpul data publik, pengendali, kerangka halaman, kepala, keadaan pendaftaran, tombol, footer, rute `/unduhan` | Pertama |
| F3-04 Perapian Tahap 2 | Filter batas unggah, judul tab admin dan masuk, alamat IP dari Cloudflare Tunnel | Bersamaan dengan F3-01 (berkasnya tidak bersinggungan) |
| F3-02 Bagian lomba | Kelompok umur, nomor lomba, dan biaya di halaman depan | Setelah F3-01 digabung, paralel |
| F3-03 Jadwal, unduhan, kontak | Bagian jadwal, unduhan, dan kontak; tombol WA; halaman `/unduhan` | Setelah F3-01 digabung, paralel |
| F3-05 Uji gabungan dan penutupan | Uji setelah semua digabung, perbaikan sambungan, arsip tahap | Terakhir, koordinator |

Paling banyak tiga sub-sesi berjalan sekaligus (K38): F3-01 dan F3-04 dulu, lalu F3-02 dan F3-03. Satu tugas = satu sub-sesi = satu cabang `fitur/F3-xx-<slug>` = satu PR. Sebelum digabung, koordinator menguji semua cabang bersama dalam satu salinan lokal.

Pendaftaran di flyer ditutup 30 November 2026, jadi tahap ini dibuat ramping supaya Tahap 4 segera mulai.

## Kesepakatan bersama

Berlaku di atas kesepakatan Tahap 1 dan 2 (`dokumen/draf/rencana-implementasi-tahap-1.md`, `dokumen/draf/rencana-implementasi-tahap-2.md`). Tugas lain memakainya dan tidak mengubahnya.

### Pengumpul data publik

`App\Libraries\IsiHalamanDepan` (F3-01) satu-satunya tempat halaman publik membaca pengaturan dan tabel. Tampilan menerima larik siap tampil dan tidak memanggil model, `setting()`, atau basis data sendiri.

- Membaca lewat model Tahap 2 yang sudah ada, tanpa mengubahnya: `KelompokUmurModel::daftarLengkap()`, `NomorLombaModel::daftarLengkap()`, `JadwalModel::daftar(true)`, `UnduhanModel::daftar(true)`, `TautanModel::daftarJenis()`. Keadaan pendaftaran dari `(new DataKelengkapan())->bukaTutup()`.
- Hanya data yang boleh dilihat pengunjung: kelompok umur aktif; nomor lomba aktif pada kelompok umur aktif; jadwal dan unduhan yang `tampil`; unduhan yang berkasnya ada di disk; gambar yang berkasnya ada (`Unggahan::ada()`).
- Teks sudah dirapikan (spasi di ujung dibuang); uang dan tanggal belum diformat bila tampilan perlu memilih bentuknya sendiri, kecuali yang disebut di bawah.
- Bentuk persis larik ditulis F3-01 di komentar kelas dan di bagian Catatan berkas tugasnya. Paling sedikit:

| Metode | Isi |
|---|---|
| `acara()` | nama tampil (`AcaraController::namaTampil()`), seri–tahun (`Seri 2 – 2026`), tempat, tanggal (`rentang_tanggal_id`) |
| `tampilan()` | judul, teks (daftar paragraf), footer, alamat gambar kepala dan tiga logo (`base_url($jalur)` atau `null` bila belum ada) |
| `bukaTutup()` | objek `BukaTutupPendaftaran` |
| `kelompokUmur()` | per kelompok umur: nama, teks tahun lahir (`KelompokUmurModel::teksTahunLahir()`), label jenjang yang boleh ikut (`Jenjang::gabungLabel()`, `''` bila belum ada), biaya individu (`int` atau `null`), nomor individu, nomor beregu (nama, kategori, jumlah inti, batas anggota, jumlah putra/putri untuk mix, biaya beregu) |
| `jadwal()` | judul, keterangan, waktu (`Y-m-d H:i:s` atau `null`), tempat, tautan |
| `unduhan()` | judul, keterangan, alamat berkas, jenis, ukuran (teks `Unggahan::ukuranTeks()`) |
| `narahubung()` | nama, nomor tampil (`NomorWa::tampilan()`), alamat `https://wa.me/<nomor>` |
| `mediaSosial()` | label, alamat, alamat ringkas, ikon (`TautanModel::alamatRingkas()`, `ikonMediaSosial()`) |
| `waTanya()` | alamat `https://wa.me/<nomor_wa_tanya>?text=<pesan_tanya>`, atau `null` bila nomor belum diisi |
| `waBagikan()` | alamat `https://wa.me/?text=<pesan>`; pesan = `pesan_bagikan` (atau nama acara bila kosong), ditambah alamat halaman depan bila belum tertulis di pesan |

### Halaman depan

- `BerandaController::index` mengisi data dari `IsiHalamanDepan` dan menampilkan `beranda.php`. `beranda.php` hanya menyusun bagian; tiap bagian satu potongan tampilan di `app/Views/beranda/`:

| Potongan | Bagian | `id` jangkar | Tugas |
|---|---|---|---|
| `_kepala.php` | Gambar kepala, logo, judul, teks, data acara, keadaan, tombol | `pembuka` | F3-01 |
| `_lomba.php` | Kelompok umur, nomor lomba, biaya | `lomba` | F3-02 |
| `_jadwal.php` | Jadwal | `jadwal` | F3-03 |
| `_unduhan.php` | Unduhan | `unduhan` | F3-03 |
| `_kontak.php` | Narahubung, media sosial, tombol WA | `kontak` | F3-03 |

  F3-01 membuat `_lomba.php`, `_jadwal.php`, `_unduhan.php`, dan `_kontak.php` sebagai potongan kosong yang sudah menerima datanya (komentar di kepala potongan menyebut variabel yang tersedia), lalu F3-02 dan F3-03 mengisinya.
- Tiap bagian adalah `<section>` dengan `id` jangkar di atas dan `aria-labelledby` ke `<h2>`-nya. Halaman hanya punya satu `<h1>` (di kepala).
- **Bagian tanpa isi tidak ditampilkan** (misalnya belum ada jadwal yang ditampilkan). Halaman `/unduhan` yang kosong menampilkan pesan ramah.
- **Gambar dan logo yang belum diunggah disembunyikan** di halaman publik (K41); `_tanpa_gambar` hanya dipakai di panel admin. Gambar memakai `alt` yang bermakna dan atribut `width`/`height` atau rasio supaya halaman tidak melompat saat dimuat.
- **Keadaan pendaftaran**: kalimat `BukaTutupPendaftaran::kalimat()` dan labelnya, dengan warna/ikon per keadaan. Teks statis "Pendaftaran sedang disiapkan" di `beranda.php` diganti.
- **Tombol Pendaftaran dan Cek Pendaftaran**: selama halamannya belum ada, tampil nonaktif dengan tulisan "Segera" (K39), seperti menu publik. Alamat keduanya ditulis di satu tempat (milik F3-01), sehingga Tahap 4 dan 5 cukup mengisi alamatnya. Aturan setelah halamannya ada (Pendaftaran hanya aktif bila `terbuka()`, Cek hanya bila pengaturan lengkap, rancangan 6.1) dikerjakan Tahap 4 dan 5.
- **Teks dari admin** selalu di-escape. Teks halaman depan dipecah menjadi paragraf pada baris kosong; baris baru di dalam paragraf menjadi `<br>`. Tidak ada HTML dari admin yang dijalankan.
- **Uang** dengan `rupiah()`; biaya `null` ditulis "Biaya segera diumumkan", biaya `0` ditulis "Gratis". **Tanggal** dengan `tanggal_id()`; waktu jadwal dengan jam ditambah "WIB", waktu kosong "Waktu segera diumumkan".
- **Tautan keluar** (WA, media sosial, tautan jadwal) dibuka di tab baru dengan `rel="noopener noreferrer"`. Hanya alamat `http`/`https` yang sudah dirapikan Tahap 2 yang dipakai sebagai `href`.
- **Footer**: teks `HalamanDepan.footer` (baris baru menjadi `<br>`); bila kosong, tetap "nama acara tahun" seperti sekarang.
- **Ponsel lebih dulu** (rancangan 12): tanpa gulir mendatar pada lebar 390 piksel; daftar panjang (nomor lomba) memakai komponen yang bisa dilipat atau tab, bukan tabel lebar.

### Halaman unduhan

- Rute publik `GET unduhan` → `App\Controllers\UnduhanController::index` ditulis F3-01 di `app/Config/Routes.php`; pengendali dan tampilannya `app/Views/unduhan.php` milik F3-03. Selama pengendalinya belum ada, alamatnya 404 dan menu Unduhan tetap "Segera".
- F3-03 mengisi `alamat` butir **Unduhan** di `$menuPublik` (`tata_letak/publik.php`) setelah halamannya jadi. Hanya baris itu yang boleh diubah F3-03 di berkas tersebut.
- Bagian unduhan di halaman depan dan halaman `/unduhan` memakai satu potongan daftar yang sama (milik F3-03).

### CSS

- Gaya halaman publik di `public/aset/css/tema.css` (milik F3-01). F3-01 menambahkan dua wilayah kosong di akhir berkas, dipisahkan beberapa baris komentar:
  - `/* ---------- Bagian lomba (F3-02) ---------- */` … `/* ---------- Akhir bagian lomba ---------- */`
  - `/* ---------- Bagian jadwal, unduhan, kontak (F3-03) ---------- */` … `/* ---------- Akhir bagian jadwal, unduhan, kontak ---------- */`
- F3-02 dan F3-03 hanya menulis di dalam wilayahnya sendiri, supaya gabungan tidak bentrok. Utamakan kelas Bootstrap dan variabel warna tema (`--ml-…`) yang sudah ada; tanpa warna baru di luar rancangan 12.

### Perapian Tahap 2 (F3-04)

- **Batas unggah**: filter baru dijalankan sebelum `csrf` pada semua `POST`. Bila isi kiriman melebihi `post_max_size` (isian dan berkas kosong padahal `CONTENT_LENGTH` besar), pengunjung dialihkan kembali ke halaman asal dengan `pesan_galat` yang menyebut batas ukurannya dan cara memperbaikinya, bukan halaman galat umum. Kiriman biasa tidak terpengaruh, dan CSRF tetap memeriksa semua kiriman lain.
- **Judul tab**: judul tab admin dan halaman masuk memakai nama acara (`AcaraController::namaTampil()`), bukan tulisan "MilkLife" langsung. Tampilan galat (`app/Views/errors/`) tidak diubah karena harus tetap jalan tanpa basis data.
- **Alamat IP pengunjung (K31)**: `Config\App::$proxyIPs` memercayai kepala `CF-Connecting-IP` hanya bila permintaan datang dari komputer itu sendiri (`127.0.0.1` dan `::1`, tempat `cloudflared` berjalan). Pengunjung yang langsung datang dari alamat lain tidak bisa memalsukan alamatnya lewat kepala itu. Pembatasan masuk admin yang sudah ada otomatis memakai alamat ini; pembatasan laju Tahap 4–5 memakai `$this->request->getIPAddress()` juga. Cara pemasangan dicatat di `README.md`.

### Uji

- `composer uji` tetap satu-satunya perintah. Uji Tahap 1–2 tidak diubah, kecuali yang terdampak langsung (disebut di pembagian berkas dan di laporan).
- Nama berkas uji per tugas: `IsiHalamanDepanTest`, `HalamanDepanKepalaTest` (F3-01); `HalamanDepanLombaTest` (F3-02); `HalamanDepanJadwalKontakTest`, `HalamanUnduhanTest` (F3-03); `BatasUnggahTest`, `AlamatPengunjungTest`, `JudulTabAdminTest` (F3-04). Jangan memakai pola `*HalamanDepan*` untuk uji admin; `PengaturanHalamanDepanTest` milik Tahap 2.
- Yang diuji paling sedikit: data tersembunyi tidak tampil (kelompok umur atau nomor mati, jadwal/unduhan disembunyikan, gambar yang berkasnya hilang), bagian kosong tidak tampil, teks admin di-escape, keempat keadaan pendaftaran tampil dengan kalimatnya, tombol nonaktif, alamat WA benar, `/unduhan` berisi dan kosong, kiriman kebesaran mendapat pesan ramah, kepala `CF-Connecting-IP` hanya dipercaya dari `127.0.0.1`/`::1`.
- Data uji memakai nama dan nomor karangan.

## Pembagian berkas

| Tugas | Berkas yang boleh disentuh |
|---|---|
| F3-01 | `app/Libraries/IsiHalamanDepan.php`, `app/Controllers/BerandaController.php`, `app/Config/Routes.php`, `app/Views/beranda.php`, `app/Views/beranda/*` (sampai digabung; setelah itu hanya `_kepala.php`), `app/Views/tata_letak/publik.php`, `app/Views/tata_letak/_merek.php`, `public/aset/css/tema.css` (di luar wilayah F3-02 dan F3-03), `tests/basisdata/IsiHalamanDepanTest.php`, `tests/fitur/HalamanDepanKepalaTest.php`, uji Tahap 1–2 yang terdampak langsung oleh beranda dan tata letak publik (`HalamanKerangkaTest`, `TataLetakHalamanTest`, `TataLetakTemaTest`, `PengaturanAcaraTest`), berkas tugasnya |
| F3-02 | `app/Views/beranda/_lomba.php`, wilayah lomba di `tema.css`, `tests/fitur/HalamanDepanLombaTest.php`, berkas tugasnya |
| F3-03 | `app/Views/beranda/_jadwal.php`, `_unduhan.php`, `_kontak.php`, potongan daftar unduhan baru di `app/Views/beranda/`, `app/Controllers/UnduhanController.php`, `app/Views/unduhan.php`, baris butir Unduhan di `$menuPublik` (`tata_letak/publik.php`), wilayah jadwal–unduhan–kontak di `tema.css`, `tests/fitur/HalamanDepanJadwalKontakTest.php`, `tests/fitur/HalamanUnduhanTest.php`, pemeriksaan menu Unduhan di `tests/fitur/TataLetakHalamanTest.php`, berkas tugasnya |
| F3-04 | `app/Filters/` (filter baru), `app/Config/Filters.php`, `app/Config/App.php` (`proxyIPs` saja), `app/Views/tata_letak/admin.php`, `app/Views/tata_letak/polos.php`, baris bagian `judul` di `app/Views/admin/**`, `app/Views/admin/masuk.php`, `tests/_support/Views/contoh_pengaturan.php` (baris judul), pemeriksaan judul tab di uji Tahap 1–2 yang terdampak langsung (misalnya `tests/fitur/BerandaPengaturanTest.php`), `README.md`, `tests/**/*BatasUnggah*`, `tests/**/*AlamatPengunjung*`, `tests/**/*JudulTabAdmin*`, berkas tugasnya |

Model Tahap 2 tidak diubah di Tahap 3. Bila sebuah tugas ternyata perlu mengubah berkas di luar bagiannya, pelaksana berhenti dan melapor ke koordinator, bukan mengubahnya sendiri.

`agent/PROGRES.md` dan `agent/KEPUTUSAN.md` hanya diubah koordinator. Tiap PR tugas memperbarui status di berkas tugasnya sendiri.

## Yang ditunda

| Hal | Ke tahap | Alasan |
|---|---|---|
| Tombol Pendaftaran dan Cek Pendaftaran aktif | 4 dan 5 | Halamannya dibuat di tahap itu (K39) |
| Menampilkan pesan kilat di tata letak publik | 4 | Belum ada formulir publik di Tahap 3 |
| Ganti kata sandi mengeluarkan sesi lain | 7 bila perlu | Sesi lain berakhir sendiri setelah 2 jam |
| Pengecilan gambar dan minify | 7 | Rancangan bagian 16 |

## Cara menguji di komputer pemilik

Setelah Tahap 3 digabung, di terminal Laragon pada folder proyek:

1. `composer install`
2. `php spark migrate --all`
3. `composer uji`
4. Buka `http://milklife.test/`. Isi halaman depan, jadwal, unduhan, dan kontak di `/admin/pengaturan`, lalu muat ulang halaman depan untuk melihat hasilnya.

## Asumsi yang perlu ditinjau

1. Jenjang yang boleh ikut ditampilkan di tiap kelompok umur (misalnya "KU 10 · lahir 2016–2018 · SD/MI"), karena sekolah perlu tahu siswanya boleh ikut atau tidak (K23).
2. Bagian yang belum punya isi tidak ditampilkan di halaman depan, sejalan dengan K41.
3. Tombol tanya admin hanya tampil bila nomor WA tanya admin sudah diisi; tombol bagikan selalu tampil.
4. Menu kepala tidak ditambah butir Jadwal dan Kontak; bagian itu bisa dibuka dengan menggulir atau lewat jangkar (`/#jadwal`, `/#kontak`).
5. Daftar nomor lomba per kelompok umur dilipat (bisa dibuka satu per satu) supaya halaman tidak terlalu panjang di ponsel.
6. Alamat IP dari `CF-Connecting-IP` dikerjakan sekarang (F3-04), bukan Tahap 7, karena pembatasan masuk admin sudah memakainya dan Tahap 4–5 menambah pembatasan laju.

## Perubahan saat pengerjaan

| Bagian | Rencana | Yang berlaku | Alasan |
|---|---|---|---|
| Pengumpul data | Metode di tabel "Pengumpul data publik" | Ditambah `tombol()` (tombol Pendaftaran dan Cek), `kaki()` (statis, dipakai tata letak publik), dan kunci `ukuran` di `tampilan()` (lebar dan tinggi asli gambar) | Satu tempat untuk alamat tombol dan menu; `width`/`height` supaya halaman tidak melompat saat gambar dimuat (F3-01) |
| Alamat tombol Pendaftaran dan Cek | "Di satu tempat" | Konstanta `IsiHalamanDepan::ALAMAT_PENDAFTARAN` dan `ALAMAT_CEK_PENDAFTARAN`, dipakai tombol di kepala dan menu publik | Tahap 4–5 cukup mengisi konstanta (F3-01) |
| Judul halaman depan | Judul atau nama acara | Bila judul diisi, judul menjadi `<h1>` dan nama acara tampil kecil di atasnya | Nama acara tetap terbaca (F3-01) |
| Bagian kontak | Bagian tanpa isi tidak ditampilkan | Bagian kontak selalu tampil karena tombol bagikan selalu ada | Asumsi 3 (F3-03, disetujui koordinator) |
| Uji milik tugas lain | Hanya uji di pembagian berkas | F3-03 menyesuaikan `HalamanDepanKepalaTest` (bagian kosong kini juga `kontak`) serta pemeriksaan "tanpa alamat luar" di `TataLetakHalamanTest` dan `KerangkaTataLetakTest` (hanya aset yang dimuat: `src`, `<link href>`, `url()`; tautan biasa seperti WA tidak dihitung). F3-04 menyesuaikan pola judul di `KerangkaTataLetakTest` dan `tests/_support/Views/tata_letak_polos_formulir.php` | Terdampak langsung; disetujui koordinator |
| Judul tab admin | Tidak dirinci | Halaman cukup menulis nama halamannya; tata letak admin menambah " – Admin <nama acara>", tata letak polos " – <nama acara>" | Satu tempat (F3-04) |
| Halaman asal filter batas unggah | `redirect()->back()` | Sesi `_ci_previous_url`, lalu `Referer`, keduanya hanya bila masih di situs sendiri; cadangan dasbor admin atau halaman depan | Mencegah pengalihan ke situs lain (F3-04) |
| Keterangan kotak "Teks pengantar" (panel Halaman Depan) | Tidak disebut | "Tekan Enter dua kali untuk membuat paragraf baru." | Paragraf dipecah pada baris kosong (laporan F3-01, diubah F3-05) |
| Gambar kepala | Gambar kepala selebar layar di kepala halaman | Gambar kepala menjadi latar di belakang judul dengan lapisan biru tua; keterangan unggahnya di panel disesuaikan | K44, tinjauan pemilik setelah F3-03 (diubah F3-05) |
