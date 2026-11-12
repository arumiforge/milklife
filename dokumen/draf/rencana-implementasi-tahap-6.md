# Rencana Implementasi Tahap 6 — Panel Data

| | |
|---|---|
| Status | Draf, **disetujui pemilik 12 November 2026 termasuk delapan asumsinya (K61)** |
| Tanggal | 12 November 2026 |
| Dasar | Rancangan revisi 3 (`dokumen/final/rancangan-web-pendaftaran.md`) bagian 5, 6.6, 6.7, 6.9, 9, 11, 13, 14; keputusan K01–K61, terutama K32, K34, K52, K53, K56–K60; arsip Tahap 3, 4, 5 bagian "Yang dibawa ke tahap berikutnya" |

## Tujuan

Untuk pelaksana tiap tugas Tahap 6 (sub-sesi) dan untuk pemilik yang meninjau. Setelah membaca, pelaksana tahu isi tugasnya, berkas yang boleh disentuh, urutan dan ketergantungan dengan tugas lain, serta aturan yang berlaku.

## Hasil Tahap 6

Setelah Tahap 6 selesai, admin bisa:

1. **Pendaftaran** (`/admin/pendaftaran`): melihat tabel semua pendaftaran (data dari titik akhir JSON, halaman, pencarian, saringan status, jenjang, kabupaten, urut), mengunduh Excel yang mengikuti saringan aktif, dan membuka rincian satu pendaftaran: sekolah, penanggung jawab beserta fotonya, atlet dengan kelompok umur, nomor lomba, dan nomor dada, tim beregu, biaya, dan status.
2. **Tandai Lunas** dengan jendela konfirmasi. Nomor dada dibuat untuk atlet yang belum punya (6.9); bila awalan sebuah kelompok umur penuh, penandaan dibatalkan seluruhnya dan admin diminta menambah awalan. Admin juga bisa mengembalikan status ke Menunggu tanpa menghapus nomor dada.
3. **Bukti bayar** (K34): mengunggah paling banyak 5 gambar per pendaftaran (dikecilkan ke sisi terpanjang 1600 piksel, JPEG), melihat, mengunduh, dan menghapusnya.
4. **Ubah pendaftaran**: sekolah, penanggung jawab (termasuk ganti foto), atlet, dan nomor lomba. Aturan bagian 6 tetap berlaku dan biaya dihitung ulang. Bila pendaftaran Lunas dan total berubah, status kembali ke Menunggu (K32); bila total tetap, atlet baru langsung mendapat nomor dada (K59).
5. **Hapus pendaftaran** dengan jendela konfirmasi; foto penanggung jawab dan bukti bayar ikut terhapus, NISN-nya bisa didaftarkan lagi, nomor dada tidak dipakai ulang.
6. **Atlet** (`/admin/atlet`): tabel semua atlet dengan saringan kelompok umur, jenis kelamin, nomor lomba, status, dan Excel.
7. **Rekap** (`/admin/rekap`): peserta per nomor lomba, tim beregu, per sekolah, per kelompok umur, dan keuangan (tagihan, lunas, belum), dengan saringan status (K60), masing-masing bisa diunduh Excel dan PDF.
8. **Dasbor**: ringkasan angka (pendaftaran, atlet, tim, sekolah, tagihan, lunas, belum).
9. **Pengaturan terlindung**: jumlah inti, batas anggota, jumlah putra/putri minimal, jenis, kategori, dan kelompok umur nomor lomba yang sudah dipakai atlet atau tim tidak bisa diubah; kelompok umur yang sudah dipakai atlet tidak bisa dihapus atau diubah batas tahun lahirnya sehingga atlet terdaftar keluar dari rentangnya.

Di sisi publik: official yang mendaftar susulan wajib mencentang konfirmasi untuk setiap tim yang sudah terdaftar sebelum lanjut (K57).

## Tugas dan urutan

| Tugas | Isi singkat | Bergantung pada | Gelombang |
|---|---|---|---|
| F6-01 Fondasi panel data | Paket PhpSpreadsheet, aset DataTables 2 lokal, pustaka tabel sisi-server dan ekspor Excel, JS tabel admin dan bilah kemajuan, migrasi `bukti_bayar`, penyimpanan berkas bukti bayar, blok rute Tahap 6, butir menu, data uji panel | — | 1 (sendiri) |
| F6-02 Daftar, rincian, lunas, hapus | Tabel pendaftaran dan Excel, rincian, foto penanggung jawab ke admin, nomor dada, tandai Lunas dan kembali ke Menunggu, hapus pendaftaran beserta berkasnya | F6-01 | 2 |
| F6-03 Bukti bayar dan daftar atlet | Unggah, lihat, unduh, hapus bukti bayar (K34); halaman `/admin/atlet` dan Excel-nya | F6-01 (potongan di rincian: F6-02) | 2 |
| F6-05 Konfirmasi tim susulan | Kotak centang per tim susulan di wizard dan pemeriksaan server (K57) | — | 2 |
| F6-04 Ubah pendaftaran | Formulir ubah di panel, pemeriksaan ulang, biaya dihitung ulang, K32, K59 | F6-02, F6-05 | 3 |
| F6-06 Rekap dan dasbor | Lima rekap dengan saringan status, Excel dan PDF (mPDF), angka dasbor | F6-01 | 3 |
| F6-07 Pengaturan terlindung | Kunci isian nomor lomba dan kelompok umur yang sudah dipakai | — | 3 |
| F6-08 Penutupan | Uji gabungan di salinan `main` terbaru, arsip `agent/arsip/tahap-6.md`, `PROGRES.md` | Semua | 4 (koordinator) |

Paling banyak 3 sub-sesi berjalan bersamaan (K38, K56). Gelombang berikutnya dimulai setelah tugas yang menjadi ketergantungannya digabung ke `main`. F6-07 tidak bergantung pada apa pun dan dipakai untuk mengisi tempat kosong bila sebuah gelombang selesai lebih cepat.

## Aturan untuk semua sub-sesi

1. **`main` adalah sumber kebenaran.** Di awal tugas dan sebelum membuka PR: `git fetch origin main` lalu `git rebase origin/main`, kemudian `composer uji` lagi. Satu tugas, satu PR; digabung *squash* setelah uji lokal dan CI lulus.
2. **Kepemilikan berkas** mengikuti tabel "Pembagian berkas". Bila butuh sesuatu dari berkas milik tugas lain, tambahkan seminimal mungkin hanya bila tidak terhindarkan, sebutkan di deskripsi PR, dan catat di berkas tugas.
3. **Rute** Tahap 6 seluruhnya ditulis F6-01 dalam satu blok berkomentar. Tugas lain tidak menambah rute; bila ternyata perlu, tambahkan satu baris di blok itu dan sebutkan di PR.
4. **Paket Composer**: hanya F6-01 yang menambah (`phpoffice/phpspreadsheet`). DataTables disimpan lokal di `public/aset/vendor/datatables/` (tanpa CDN, tanpa Node; rancangan 4).
5. **`agent/PROGRES.md`**: tiap tugas hanya mengubah baris tugasnya. **`agent/KEPUTUSAN.md`**: sub-sesi tidak menambah keputusan; keputusan baru pemilik dicatat koordinator.
6. **Data uji** memakai nama, NISN, dan NPSN karangan, dan gambar yang dibuat di uji. Data siswa, foto, dan rahasia tidak masuk git.
7. **Bahasa**: semua tulisan berbahasa Indonesia. Commit atas nama Arumi Studios tanpa atribusi alat AI. Berkas `sumber/` tidak diubah.

## Kesepakatan teknis

### Tabel dan ekspor (F6-01)

- `App\Libraries\Panel\TabelSisiServer`: membaca parameter DataTables (`draw`, `start`, `length`, `search[value]`, `order`, saringan per kolom dari parameter bernama sendiri), menerapkannya pada query builder dengan daftar kolom yang diizinkan (nama kolom tidak pernah diambil mentah dari permintaan), dan menghasilkan `{draw, recordsTotal, recordsFiltered, data}`.
- `App\Libraries\Panel\EksporExcel`: menulis `.xlsx` dari judul kolom dan baris (PhpSpreadsheet), lebar kolom otomatis, baris judul tebal, angka rupiah sebagai bilangan dengan format `Rp#,##0`. Setiap tabel punya alamat `…/excel` yang memakai saringan yang sama dengan JSON-nya.
- `public/aset/js/tabel-admin.js`: membuat DataTables dari atribut `data-*` pada `<table>` (alamat JSON, kolom, saringan), menyalin saringan aktif ke tombol unduh Excel, dan bahasa Indonesia untuk teks DataTables. Bilah kemajuan di atas halaman saat data dimuat (rancangan 9) di `public/aset/css/admin.css` wilayah berpenanda komentar "Tahap 6".
- Keadaan kosong: pesan ramah yang menjelaskan apa yang belum ada dan langkah berikutnya, misalnya "Belum ada pendaftaran. Pendaftaran yang masuk lewat halaman Pendaftaran akan tampil di sini."

### Nomor dada (F6-02)

- Aturan murni `App\Libraries\Panel\NomorDada` dengan uji unit: dari daftar awalan kelompok umur (berurutan) beserta nomor terakhirnya dan jumlah atlet yang butuh nomor, hasilkan nomor 4 digit (`awalan` + `001`–`999`) atau galat "awalan penuh" bila tidak cukup.
- Pemberian nomor dalam satu transaksi dengan kunci baris `awalan_nomor_dada` (`SELECT … FOR UPDATE`); atlet diberi nomor berurutan menurut urutan simpan (`atlet.id`). Bila satu kelompok umur saja tidak cukup, seluruh penandaan dibatalkan (6.9).
- Satu layanan `App\Libraries\Panel\StatusPembayaran` dengan `tandaiLunas()`, `kembaliMenunggu()`, dan `beriNomorDada()` (dipakai juga F6-04 untuk K59). Nomor dada tidak pernah dihapus atau dipakai ulang.

### Hapus pendaftaran (F6-02)

- Baris anak terhapus lewat kunci asing `CASCADE`. Berkas foto penanggung jawab (`FotoPenanggungJawab::hapus()`) dan bukti bayar (`BerkasBuktiBayar`, F6-01) dihapus setelah transaksi berhasil.
- Jendela konfirmasi menyebut ID pendaftaran, nama sekolah, jumlah atlet, dan bahwa NISN-nya bisa didaftarkan lagi.

### Bukti bayar (F6-01, F6-03)

- Tabel `bukti_bayar` (rancangan 11): `pendaftaran_id` (kunci asing, hapus `CASCADE`), `berkas`, `ukuran`, `waktu_unggah`. Batas 5 per pendaftaran dijaga aplikasi.
- Berkas di `WRITEPATH . 'unggahan/bukti-bayar/'` (di luar `public/`, sudah diabaikan git lewat `/writable/unggahan/`), nama acak, dibaca ulang dan disimpan ulang sebagai JPEG lewat GD, sisi terpanjang 1600 piksel. Disajikan hanya lewat rute admin.

### Ubah pendaftaran (F6-04)

- Memakai `PemeriksaPendaftaran` dan `KelengkapanTim` dengan atlet dan tim pendaftaran yang sedang diubah dikeluarkan dari data "sudah ada". Buka-tutup pendaftaran tidak berlaku untuk admin. Konfirmasi tim susulan (K57) tidak diminta dari admin.
- Biaya: baris atlet-nomor dan tim yang tidak berubah mempertahankan biaya tersimpan (6.7); yang baru memakai tarif saat ini. Subtotal dan total dihitung ulang dari baris-baris itu.
- K32: bila status Lunas dan total berubah, status kembali ke Menunggu; jendela konfirmasi menyebutnya sebelum simpan. K59: bila status Lunas dan total tetap, atlet baru langsung diberi nomor dada lewat `StatusPembayaran::beriNomorDada()`.
- Mengubah atlet yang sudah punya nomor dada mempertahankan nomornya, juga bila kelompok umurnya berubah.
- Simpan dalam satu transaksi dengan kunci bernama per NPSN seperti `SimpanPendaftaran`. Foto baru ditulis sebelum transaksi dan dihapus bila gagal; foto lama dihapus setelah berhasil.

### Konfirmasi tim susulan (F6-05)

- Wizard langkah 3 menampilkan, pada setiap tim susulan di daftar sementara, kotak centang "Saya mengerti atlet ini ditambahkan ke tim <nomor> sekolah yang sudah terdaftar (<n> atlet), tanpa biaya tim". Tombol lanjut ke langkah 4 menolak bila ada yang belum dicentang.
- Kiriman membawa daftar ID nomor lomba yang sudah dikonfirmasi. Server (`PemeriksaPendaftaran`) menolak tim susulan yang tidak ada di daftar itu dengan galat langkah 3. Parameter konfirmasi bersifat pilihan supaya F6-04 bisa memanggil pemeriksa tanpa konfirmasi.
- Draf lama tanpa daftar konfirmasi tetap terbaca; kotaknya tampil belum tercentang.

### Rekap (F6-06)

- Lima rekap: peserta per nomor lomba (nomor dada, nama, NISN, sekolah, kabupaten, status), tim beregu (nomor, sekolah, anggota), per sekolah (jumlah atlet, tim, tagihan), per kelompok umur (jumlah atlet putra dan putri, tim), dan keuangan (tagihan, lunas, belum; per pendaftaran dan jumlah keseluruhan).
- Saringan status: semua (bawaan), Lunas, Menunggu (K60). Rekap keuangan selalu memuat keduanya.
- PDF memakai mPDF dan pola `BuktiPendaftaran` (A4, judul dari pengaturan acara, logo persegi, nama berkas `Rekap<Jenis>_<NamaAcaraTanpaSpasi>_<Tahun>.pdf`). Excel lewat `EksporExcel`.

### Pengaturan terlindung (F6-07)

- Nomor lomba yang sudah dipakai (`NomorLombaModel::dipakai()`): isian kelompok umur, jenis, kategori, jumlah inti, batas anggota, dan jumlah putra/putri minimal tampil terkunci dengan keterangan, dan server menolak perubahannya. Nama, biaya beregu, urutan, dan aktif tetap bisa diubah (biaya baru hanya berlaku untuk pendaftaran berikutnya, 6.7).
- Kelompok umur yang sudah dipakai atlet: tidak bisa dihapus; batas tahun lahir tidak bisa diubah sehingga ada atlet terdaftar yang keluar dari rentangnya.

## Pembagian berkas

| Tugas | Berkas yang disentuh |
|---|---|
| F6-01 | `composer.json`, `composer.lock`; `public/aset/vendor/datatables/*`; `public/aset/js/tabel-admin.js`; `public/aset/css/admin.css` (wilayah Tahap 6); `app/Libraries/Panel/TabelSisiServer.php`, `EksporExcel.php`, `BerkasBuktiBayar.php`; migrasi `bukti_bayar` dan `app/Models/BuktiBayarModel.php`; blok rute Tahap 6 di `app/Config/Routes.php`; `app/Views/tata_letak/_menu_admin.php` (butir Pendaftaran, Atlet, Rekap); `app/Views/tata_letak/admin.php` (memuat aset tabel bila halaman memintanya, seminimal mungkin); `tests/_support/Panel*`; `tests/unit/Panel*`, `tests/basisdata/Panel*`; uji menu dan struktur migrasi yang terdampak |
| F6-02 | `app/Controllers/Admin/PendaftaranController.php`; `app/Views/admin/pendaftaran/*` (kecuali `_bukti_bayar.php` dan `ubah.php`); `app/Libraries/Panel/NomorDada.php`, `StatusPembayaran.php`, `HapusPendaftaran.php`, `DataPanelPendaftaran.php`; `public/aset/js/admin-pendaftaran.js`; `tests/*/PanelPendaftaran*`, `tests/unit/PanelNomorDada*` |
| F6-03 | `app/Controllers/Admin/BuktiBayarController.php`; `app/Views/admin/pendaftaran/_bukti_bayar.php` (satu baris `include` di `rincian.php` setelah F6-02 digabung); `app/Controllers/Admin/AtletController.php`; `app/Views/admin/atlet/*`; `tests/*/PanelBuktiBayar*`, `tests/*/PanelAtlet*` |
| F6-04 | `PendaftaranController::ubah`, `perbarui` (metode baru saja); `app/Views/admin/pendaftaran/ubah.php`; `app/Libraries/Panel/UbahPendaftaran.php`; `public/aset/js/admin-ubah-pendaftaran.js`; tombol Ubah di `rincian.php`; `tests/*/PanelUbah*` |
| F6-05 | `public/aset/js/pendaftaran.js`, `public/aset/css/pendaftaran.css`, `app/Views/pendaftaran/*`; `app/Libraries/Pendaftaran/PemeriksaPendaftaran.php` (parameter konfirmasi); `tests/*/Pendaftaran*` yang terdampak |
| F6-06 | `app/Controllers/Admin/RekapController.php`; `app/Views/admin/rekap/*`; `app/Libraries/Panel/Rekap*.php`; `app/Controllers/Admin/DasborController.php` dan `app/Views/admin/dasbor.php` (bagian angka saja; daftar kelengkapan tidak diubah); `tests/*/PanelRekap*`, `tests/fitur/Dasbor*` yang terdampak |
| F6-07 | `app/Controllers/Admin/Pengaturan/NomorLombaController.php`, `KelompokUmurController.php` dan tampilan formulirnya; `app/Models/NomorLombaModel.php`, `KelompokUmurModel.php` (pembacaan "dipakai"); `tests/fitur/PengaturanNomorLomba*`, `PengaturanKelompokUmur*` |
| F6-08 | Perbaikan sambungan seperlunya; `agent/arsip/tahap-6.md`, `agent/PROGRES.md`, `agent/KONTEKS.md`, berkas tugas, bagian "Perubahan saat pengerjaan" rencana ini |

Berkas Tahap 4 dan 5 lain (`SimpanPendaftaran`, `DataPendaftaran`, `BuktiPendaftaran`, halaman cek) hanya dibaca. Pendaftaran yang diubah admin langsung tampil di cek dan bukti karena keduanya membaca nilai tersimpan.

## Uji

- `composer uji` tetap satu-satunya perintah, dan CI menjalankannya di setiap PR (K46).
- Nama berkas uji Tahap 6 diawali `Panel` (`tests/unit/Panel*Test.php`, `tests/basisdata/Panel*Test.php`, `tests/fitur/Panel*Test.php`), kecuali uji Tahap 2–4 yang terdampak langsung.
- Paling sedikit diuji: parameter tabel tidak bisa menyuntik kolom; Excel mengikuti saringan; nomor dada berurutan, berlanjut ke awalan berikutnya, dan membatalkan seluruh penandaan bila penuh; dua penandaan bersamaan tidak memberi nomor ganda; kembali ke Menunggu tidak menghapus nomor dada; hapus menghapus baris dan berkas; foto dan bukti bayar hanya bisa dibuka admin; batas 5 bukti bayar; ubah menolak NISN milik pendaftaran lain dan menerima NISN miliknya sendiri; K32 dan K59; konfirmasi tim susulan (K57) di server; rekap per saringan status; isian nomor lomba yang dipakai tidak bisa diubah; semua halaman admin menolak yang belum masuk; CSRF pada semua formulir.

## Yang ditunda

| Hal | Ke | Alasan |
|---|---|---|
| Minify `tabel-admin.js`, `admin-pendaftaran.js`, aset DataTables | Tahap 7 | Rancangan bagian 16 |
| Revisi rancangan: 6.3 foto wajib (K50), 6 kuota (K51, K53), 8 cek dan bukti (K49, K52), 6.6 konfirmasi tim susulan (K57), 9 panel (K58–K60) | Revisi rancangan berikutnya | Dokumen final tidak diubah diam-diam |

## Cara menguji di komputer pemilik

Setelah Tahap 6 digabung, di terminal Laragon pada folder proyek:

1. `composer install`
2. `php spark migrate --all`
3. `composer uji`
4. Daftarkan dua sekolah karangan lewat `/pendaftaran`, satu di antaranya susulan dari NPSN yang sama.
5. Buka `/admin/pendaftaran`: saring, unduh Excel, buka rincian, unggah bukti bayar, tandai Lunas, ubah, lalu hapus satu pendaftaran.
6. Buka `/admin/atlet` dan `/admin/rekap`, unduh Excel dan PDF.

## Asumsi yang perlu ditinjau

1. Nomor dada dalam satu pendaftaran diberikan menurut urutan atlet disimpan, bukan menurut nama atau nomor lomba.
2. Admin bisa mengembalikan pendaftaran Lunas ke Menunggu lewat tombol di rincian (dengan konfirmasi); nomor dada tetap (6.9).
3. Pendaftaran Lunas boleh dihapus, dengan konfirmasi yang menyebut bahwa nomor dada atletnya tidak dipakai ulang.
4. Saat admin mengubah pendaftaran, nomor lomba dan tim yang tidak berubah mempertahankan biaya tersimpan; yang baru memakai tarif saat ini.
5. Tabel admin menampilkan 25 baris per halaman; Excel memuat semua baris yang lolos saringan, tanpa batas halaman.
6. Rekap PDF berkertas A4 mendatar seperti bukti pendaftaran.
7. Halaman Atlet hanya untuk melihat dan mengunduh; mengubah atlet dilakukan dari rincian pendaftarannya.
8. Isian nomor lomba dan kelompok umur yang sudah dipakai dikunci (F6-07); nama, biaya, urutan, dan aktif tetap bisa diubah.

## Perubahan saat pengerjaan

| Bagian | Rencana | Yang berlaku | Alasan |
|---|---|---|---|
| Pembagian berkas F6-01 | DataTables 2 di `public/aset/vendor/datatables/` | jQuery 3.7.1 ikut disimpan di `public/aset/vendor/jquery/`, dimuat hanya di halaman bertabel | DataTables 2 membutuhkan jQuery (F6-01, #47) |
| Tabel dan ekspor | Format rupiah Excel `Rp#,##0` | `"Rp"#,##0` | Huruf diberi tanda kutip supaya tidak dibaca sebagai kode format (F6-01) |
| Uji F6-01 | Pembantu uji `tests/_support/Panel*` | Halaman contoh tabel di `tests/_support/Views/PanelContohTabel.php`; uji kueri bernama `PanelTabelSisiServerQueryTest` | Kelas uji tanpa namespace tidak boleh bernama sama dengan uji unitnya (F6-01) |
| Rute | Alamat rekap tidak dirinci | `/admin/rekap/<jenis>` per jenis, selain `/admin/rekap`; foto penanggung jawab `/admin/pendaftaran/<id>/foto/<id penanggung jawab>` | Asumsi F6-01, dipakai F6-02 dan F6-06 |
| Bukti bayar | Batas ukuran dan jenis berkas tidak disebut | Paling besar 10 MB sebelum dikecilkan; JPG, PNG, WEBP | Asumsi F6-01 |
| Nomor dada (F6-02) | Galat "awalan penuh" | `StatusPembayaran::beriNomorDada()` mengembalikan hasil berisi `penuh`, tanpa melempar galat; pemanggil bertransaksi membatalkan sendiri | Kelas galat baru di luar daftar berkas F6-02 (#50) |
| Nomor dada (F6-02) | Nomor dari `nomor_terakhir` | Nomor baru juga selalu di atas nomor tertinggi yang tersimpan di `atlet.nomor_dada` dengan awalan sama | Pengaman bila `nomor_terakhir` tertinggal (#50) |
| Hapus (F6-02) | Berkas dihapus setelah transaksi | `BerkasBuktiBayar::berkasMilik()` + `hapusBerkas()`, bukan `hapusMilik()`; daftar berkas dibaca setelah baris pendaftaran dikunci | `hapusMilik()` menghapus sebelum transaksi selesai (#50) |
| Excel pendaftaran | Kolom sama dengan tabel | Ditambah subtotal individu, subtotal beregu, waktu lunas; status ditulis lengkap | Ditambahkan F6-02 (#50) |
| Bukti bayar (F6-03) | Unggah beberapa gambar | Satu gambar per kiriman | Mengikuti `BerkasBuktiBayar` (#49) |
| Bukti bayar (F6-03) | `include` di `rincian.php` | `view('admin/pendaftaran/_bukti_bayar', ['pendaftaran' => $p])`; dua kueri di uji milik F6-02 tidak menghitung `#bukti-bayar` | Rincian menamai datanya `$p` (#49) |
| Atlet (F6-03) | — | Tanggal lahir di Excel ditulis teks (`12 April 2017`) | `EksporExcel` hanya punya jenis teks, angka, rupiah (#49) |
| Konfirmasi tim susulan (F6-05) | Parameter konfirmasi pada pemeriksa | Kunci `konfirmasi_susulan` di dalam kiriman JSON; titik akhir publik mewajibkannya lewat `wajibKonfirmasiSusulan()`; `PendaftaranController::kiriman()` berubah satu baris | `SimpanPendaftaran` hanya dibaca dan meneruskan kiriman di dalam kunci per NPSN, jadi tidak ada celah balapan (#51) |
| Konfirmasi tim susulan (F6-05) | Kalimat "atlet ini" | "<k> atlet ini" bila tambahan lebih dari satu; tidak ada perubahan di `app/Views/pendaftaran/*` | Kalimat tetap benar; kotak dibuat skrip (#51) |
| Ubah (F6-04) | K32 lewat jendela konfirmasi | K32 juga dijaga server: simpan hanya berjalan dengan `setuju_menunggu=1` | Supaya status tidak berubah tanpa persetujuan (#52) |
| Ubah (F6-04) | Rancangan 6.8: kode jenjang di ID | ID pendaftaran tidak berubah walau jenjang diubah | **K63** (#52) |
| Ubah (F6-04) | — | Nomor lomba tidak aktif tampil tercentang dengan keterangan dan ditolak sampai centangnya dihapus; `ubah()` punya dua parameter pilihan | Nomor tidak hilang diam-diam; formulir bisa ditampilkan ulang (#52) |
| Ubah (F6-04) | — | **Batasan:** bila pendaftaran pemilik tim pertama menghapus timnya, tim susulan di pendaftaran lain dengan NPSN sama tetap Rp0 | Di luar cakupan F6-04; dibawa ke tahap berikutnya (#52) |
| Rekap (F6-06) | Rekap lewat tabel | Tabel biasa tanpa DataTables; satu pustaka `Rekap` untuk halaman, JSON, Excel, PDF; tim dihitung per NPSN per nomor; saringan status per atlet | Baris sedikit; angka selalu sama; K07 (#54) |
| Rekap (F6-06) | Pola `BuktiPendaftaran` | Pembacaan logo disalin ke `RekapPdf` | Berkas Tahap 5 hanya dibaca (#54) |
| Dasbor (F6-06) | Bagian angka saja | Kartu "Belum ada pendaftaran" hanya tampil bila memang belum ada pendaftaran | Disesuaikan dengan angka dasbor yang baru (#54) |
| Pengaturan terlindung (F6-07) | Semua isian tim terkunci bila dipakai | Isian tim yang masih kosong boleh dilengkapi; batas tahun lahir dibandingkan dengan atlet tertua dan termuda, batas kosong selalu diterima | Nomor dari data awal tidak terkunci dalam keadaan belum lengkap (#53) |
| Gelombang | Koordinator menjalankan gelombang 2–4 (K56) | Pemilik menjalankan gelombang 2–4 di sesi berbeda | **K64** (semula tercatat ganda sebagai K62) |
| Penutupan (F6-08) | — | Dua baris CSS di wilayah Tahap 6 `admin.css` (milik F6-01): wadah gulir tabel `position: relative`, tombol saringan boleh membungkus | Di Chromium 390 piksel halaman Pendaftaran bergulir mendatar (teks `visually-hidden` judul kolom lolos dari wadah gulir) dan tombol Unduh Excel keluar kartu |
