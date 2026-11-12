# F3-01 Fondasi halaman depan

| | |
|---|---|
| Status | selesai |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Halaman depan memakai isi pengaturan: gambar dan logo, judul dan teks, data acara, keadaan pendaftaran yang sebenarnya, tombol Pendaftaran dan Cek Pendaftaran, dan footer. Tugas lain tinggal mengisi bagian lomba, jadwal, unduhan, dan kontak.

## Hasil

- [x] `App\Libraries\IsiHalamanDepan`: pengumpul data publik sesuai kesepakatan rencana Tahap 3 (semua metode, termasuk untuk bagian milik F3-02 dan F3-03), hanya data yang boleh dilihat pengunjung, bentuk larik ditulis di komentar kelas
- [x] `BerandaController::index` dan `beranda.php` menyusun bagian dengan urutan K42; potongan `app/Views/beranda/_kepala.php`, dan potongan kosong `_lomba.php`, `_jadwal.php`, `_unduhan.php`, `_kontak.php` yang sudah menerima datanya
- [x] Kepala: gambar kepala selebar layar, logo persegi, logo persegi panjang, logo Bakti Olahraga Djarum Foundation, judul (atau nama acara bila kosong), teks berparagraf, seri–tahun, tanggal, tempat; gambar yang belum diunggah disembunyikan (K41)
- [x] Keadaan pendaftaran dari `BukaTutupPendaftaran` (empat keadaan, kalimat dan tanda yang berbeda); teks statis "Pendaftaran sedang disiapkan" hilang
- [x] Tombol Pendaftaran dan Cek Pendaftaran tampil nonaktif "Segera" (K39); alamatnya di satu tempat untuk Tahap 4–5
- [x] Footer dari `HalamanDepan.footer`, dengan cadangan "nama acara tahun"
- [x] Rute publik `GET unduhan` → `UnduhanController::index` (pengendalinya dibuat F3-03)
- [x] Wilayah CSS kosong untuk F3-02 dan F3-03 di akhir `tema.css`
- [x] Uji `tests/basisdata/IsiHalamanDepanTest.php` dan `tests/fitur/HalamanDepanKepalaTest.php`; uji Tahap 1–2 yang terdampak disesuaikan

## Selesai bila

`composer uji` lulus; halaman depan pada lebar 390 dan 1440 piksel menampilkan kepala dengan dan tanpa gambar, keempat keadaan pendaftaran, tanpa gulir mendatar.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
- F3-04 dikerjakan bersamaan; berkasnya tidak bersinggungan (F3-04 memegang `tata_letak/admin.php` dan `tata_letak/polos.php`).

### Cara memakai untuk F3-02 dan F3-03

- **Data** sudah dikirim `BerandaController` ke potongan masing-masing; potongan tidak membaca basis data, model, atau `setting()` sendiri. Bentuk persis tiap larik ada juga di komentar kelas `App\Libraries\IsiHalamanDepan` dan di komentar kepala tiap potongan.
- **Teks** sudah dirapikan (spasi di ujung dibuang, akhir baris `\n`) tetapi belum di-escape: selalu `esc()`. Teks kosong `''`, angka yang belum diisi `null`. Uang (`int`) dan waktu jadwal (`Y-m-d H:i:s`) belum diformat; tanggal acara dan ukuran berkas sudah teks.
- **F3-02** — `_lomba.php` menerima `$kelompokUmur` (`IsiHalamanDepan::kelompokUmur()`), hanya kelompok umur aktif dengan nomor aktifnya, berurutan seperti di panel admin; `[]` bila belum ada:

  ```php
  [
      [
          'id'             => 2,                    // int, untuk jangkar bila perlu
          'nama'           => 'KU 10',
          'tahun_lahir'    => 'Lahir 2016–2018',    // KelompokUmurModel::teksTahunLahir(), '' bila belum diisi
          'jenjang'        => 'SD/MI',              // Jenjang::gabungLabel(), '' bila belum ada
          'biaya_individu' => 150000,               // int (0 = gratis) atau null (belum diisi)
          'nomor_individu' => [
              ['id' => 7, 'nama' => 'Lari 60 m', 'kategori' => 'putra', 'kategori_label' => 'Putra'],
          ],
          'nomor_beregu'   => [
              [
                  'id' => 30, 'nama' => 'Estafet 4 × 50 m', 'kategori' => 'mix', 'kategori_label' => 'Mix',
                  'jumlah_inti' => 4, 'batas_anggota' => 6,   // int atau null
                  'jumlah_putra' => 2, 'jumlah_putri' => 2,   // int atau null; selalu null bila bukan mix
                  'biaya_beregu' => 200000,                   // int atau null
              ],
          ],
      ],
  ]
  ```

- **F3-03** — `_jadwal.php` menerima `$jadwal` (`jadwal()`): `[['id' => 1, 'judul' => '…', 'keterangan' => '…' atau '', 'waktu' => 'Y-m-d H:i:s' atau null, 'tempat' => '…' atau '', 'tautan' => 'https://…' atau '']]`, hanya yang `tampil`, urutan `JadwalModel::daftar(true)`; tautan selalu `http`/`https` (diperiksa ulang `TautanModel::rapikanAlamatWeb()`).
- **F3-03** — `_unduhan.php` menerima `$unduhan` (`unduhan()`): `[['id' => 1, 'judul' => '…', 'keterangan' => '…' atau '', 'alamat' => base_url($berkas), 'jenis' => 'PDF', 'ukuran' => '1,5 MB']]`, hanya yang `tampil` dan berkasnya ada di disk. Halaman `/unduhan` memakai `(new IsiHalamanDepan())->unduhan()` yang sama.
- **F3-03** — `_kontak.php` menerima `$narahubung` (`[['nama' => '…', 'nomor' => '0812-3456-7890', 'alamat' => 'https://wa.me/6281234567890']]`, nomor yang tidak sah dilewati), `$mediaSosial` (`[['label' => '…', 'alamat' => 'https://…', 'alamat_ringkas' => 'instagram.com/contoh', 'ikon' => 'instagram']]`, ikon tanpa awalan `bi-`, alamat tidak sah dilewati), `$waTanya` (`'https://wa.me/62…?text=…'`, tanpa `?text=` bila pesan tanya kosong, atau `null` bila nomor belum diisi), dan `$waBagikan` (`'https://wa.me/?text=…'`, selalu ada). Pesan WA dikodekan `rawurlencode()`.
- **Halaman `/unduhan`**: rute `GET unduhan` → `UnduhanController::index` sudah ada. Tata letak publik sudah memasang kaki dari `IsiHalamanDepan::kaki()`, jadi pengendali cukup mengirim judul tab dan isi. Bila memakai judul tab dari nama acara: `(new IsiHalamanDepan())->acara()['nama']`.
- **CSS**: dua wilayah kosong di akhir `public/aset/css/tema.css`, persis seperti rencana, dipisahkan blok komentar. Gaya kepala halaman depan (F3-01) ada di atasnya, bagian "Kepala halaman depan".
- **Uji**: kerangka pengisian data karangan (gambar di folder unggahan uji, `data:awal` + isian wajib, keadaan dengan `Time::setTestNow`) ada di `tests/fitur/HalamanDepanKepalaTest.php`. Di uji, tampilan potongan mengandung komentar penanda `<!-- DEBUG-VIEW … -->`; buang dulu bila membandingkan teks persis.

### Keputusan kecil saat pengerjaan

- **Tombol Pendaftaran dan Cek Pendaftaran**: alamatnya di konstanta `IsiHalamanDepan::ALAMAT_PENDAFTARAN` dan `ALAMAT_CEK_PENDAFTARAN` (sekarang `null`), dipakai tombol di kepala (`tombol()`) **dan** butir menu publik di `tata_letak/publik.php`, jadi Tahap 4–5 cukup mengisi konstanta itu. Selama `null`, tombol berupa `<a>` tanpa `href` dengan `aria-disabled="true"`, kelas `disabled`, dan label "Segera" (seperti menu). Aturan aktif sesuai keadaan (rancangan 6.1) ditulis Tahap 4–5 di `tombol()`.
- **Keadaan pendaftaran**: kartu putih di kepala dengan `data-keadaan` dan kelas `keadaan-<keadaan>`; label (`label()`) dan kalimat (`kalimat()`) dari `BukaTutupPendaftaran`, ikon sama dengan dasbor admin (jam pasir, kalender, pintu terbuka, pintu tertutup) dan warna garis/ikon per keadaan dari warna tema (biru muda, biru, oranye, abu redup). Tanpa kalimat tambahan, supaya tidak bertentangan dengan tombol yang masih "Segera". Teks statis "Pendaftaran sedang disiapkan" + bidang info lama dihapus.
- **Judul**: bila `HalamanDepan.judul` diisi, judul itu menjadi `<h1 id="judul-acara">` dan nama acara tampil kecil di atasnya (`.pembuka-nama`); bila kosong, nama acara menjadi `<h1>` seperti sebelumnya (merek dua warna). Judul dibatasi lebar 15em supaya tidak menimpa bidang miring hiasan di layar lebar.
- **Teks**: dipecah menjadi paragraf pada baris kosong (baris berisi spasi saja juga dihitung kosong); baris baru di dalam paragraf menjadi `<br>`.
- **Gambar**: gambar kepala selebar layar di atas judul (tinggi paling besar 70% layar, `object-fit: cover`), tiga logo dalam kartu putih setinggi sama di atas pita seri. `width`/`height` dari ukuran asli berkas (`getimagesize`, kunci tambahan `ukuran` di `tampilan()`), sehingga halaman tidak melompat. `alt`: "Gambar kepala <nama acara>", "Logo <nama acara>" (persegi dan persegi panjang), "Logo Bakti Olahraga Djarum Foundation". Gambar yang jalurnya kosong, tidak sah, atau berkasnya hilang tidak ditampilkan (K41).
- **Footer**: `IsiHalamanDepan::kaki()` (statis) dipakai tata letak publik untuk semua halaman publik, bukan hanya halaman depan; baris baru menjadi `<br>`. Cadangan "nama acara tahun" tetap.
- **`waBagikan()`**: alamat halaman depan (`site_url('/')`) dianggap sudah tertulis bila pesan memuat bentuk ringkasnya (tanpa `http(s)://`, `www.`, dan garis miring akhir), tanpa membedakan huruf besar-kecil; bila belum, ditambahkan pada baris baru.
- **Tabel yang belum ada**: pembacaan tabel yang gagal (`DatabaseException`) dicatat di log dan dianggap kosong, sama seperti `DataKelengkapan`, sehingga halaman depan tetap terbuka sebelum migrasi (juga uji tanpa basis data `UjiHalaman`).
- **Uji Tahap 1–2 yang disesuaikan**: `HalamanKerangkaTest` (kalimat "Pendaftaran sedang disiapkan." dari keadaan), `TataLetakHalamanTest` (bidang info lama diganti pemeriksaan `section#pembuka`), `TataLetakTemaTest` (dua pasangan kontras baru: biru tua dan oranye gelap di atas putih). `PengaturanAcaraTest` lulus tanpa diubah.
- Uji baru: `tests/basisdata/IsiHalamanDepanTest.php` (16 uji) dan `tests/fitur/HalamanDepanKepalaTest.php` (13 uji). `composer uji`: 735 uji, 4.770 asersi, lulus.
- Diperiksa di Chromium pada lebar 390 dan 1440 piksel: tanpa gambar dan teks, dengan gambar kepala, tiga logo, judul, teks, dan footer dari panel admin, serta keadaan disiapkan, belum dibuka, terbuka, dan ditutup; tanpa gulir mendatar.

### Untuk koordinator

- Keterangan kotak "Teks pengantar" di halaman pengaturan Halaman Depan (milik F2-05) berbunyi "Tekan Enter untuk membuat paragraf baru.", sedangkan kesepakatan Tahap 3 memecah paragraf pada baris kosong (satu Enter = baris baru di paragraf yang sama). Tampilannya mirip, tetapi kalimatnya bisa diganti "Tekan Enter dua kali untuk membuat paragraf baru." oleh pemilik berkasnya.
- Digabung lewat PR #25 pada 12 November 2026.
