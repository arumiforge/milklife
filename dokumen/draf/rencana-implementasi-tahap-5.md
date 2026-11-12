# Rencana Implementasi Tahap 5 — Bukti dan cek

| | |
|---|---|
| Status | Draf, dikerjakan bersamaan dengan Tahap 4 di sesi lain (K48) |
| Tanggal | 12 November 2026 |
| Dasar | Rancangan revisi 3 (`dokumen/final/rancangan-web-pendaftaran.md`) bagian 5, 6.1, 6.7, 6.8, 6.9, 8, 11, 13, 14; keputusan K01–K49; hasil Tahap 3 (`agent/arsip/tahap-3.md`) |

## Tujuan

Untuk pelaksana Tahap 5 dan pemilik yang meninjau. Setelah membaca, pelaksana tahu apa yang dibuat, berkas mana yang menjadi miliknya, dan kesepakatan apa yang berlaku dengan sesi Tahap 4 yang berjalan di tempat lain.

## Hasil Tahap 5

1. **`/cek-pendaftaran`**: official mengetik NPSN atau ID pendaftaran.
   - NPSN menampilkan semua pendaftaran sekolah itu, berurutan per pendaftaran (K49): data sekolah sekali di atas, lalu satu kartu per pendaftaran (ID, tanggal, penanggung jawab, atlet, tim beregu, biaya, status, tombol cetak bukti), dan total keseluruhan di bawah.
   - ID pendaftaran menampilkan satu pendaftaran dengan bentuk kartu yang sama.
   - Data tampil penuh (rancangan 8, K03): atlet dengan nama, NISN, tanggal lahir, kelompok umur, nomor lomba, dan nomor dada. Foto penanggung jawab tidak tampil.
   - Halaman hanya bisa dipakai bila pengaturan lengkap (6.1); bila belum, halaman menjelaskan bahwa cek sedang disiapkan.
   - Masukan kosong, keliru, atau tidak ditemukan mendapat pesan yang menyebut cara memperbaikinya.
   - Pembatasan laju per alamat IP.
   - Tombol **Cek Pendaftaran** di halaman depan aktif bila pengaturan lengkap; menu **Cek Pendaftaran** di kepala aktif.
2. **`/bukti/{id-pendaftaran}`**: PDF A4 mendatar (rancangan 8, K49).
   - Logo persegi di kiri atas; di kanannya, rata tengah, nama acara berhuruf besar dan `Seri <seri> Tahun <tahun>`.
   - `Nama Sekolah` dan `Tanggal Pendaftaran` sebaris, `Kabupaten / Kota` dan `ID Pendaftaran` sebaris.
   - Penanggung jawab (nama dan nomor WA), tabel individu (No, Nama, NISN, Tanggal Lahir, Kelompok Umur, Nomor Lomba, Nomor Dada, Biaya), tabel beregu (No, Nomor Lomba, Anggota beserta nomor dada, Biaya), subtotal, total, dan status pembayaran.
   - Nama berkas `BuktiPendaftaran_<NamaAcara><Seri>_<NamaSekolah>_<Tahun>.pdf`; nama acara dan nama sekolah tanpa spasi dan tanda baca (K49). Contoh: `BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_SDNegeri1Kudus_2026.pdf`.
   - Pembatasan laju per alamat IP. ID yang tidak ada menghasilkan 404.
3. **PDF dokumen**: `php spark dokumen:pdf` membuat `dokumen/pdf/<nama>.pdf` dari setiap `dokumen/final/<nama>.md`. PDF tidak disunting tangan (ALUR-KERJA bagian Dokumen).

Semua angka biaya dibaca dari nilai yang tersimpan di pendaftaran (`subtotal_individu`, `subtotal_beregu`, `total`, `atlet_nomor.biaya`, `pendaftaran_beregu.biaya_tim`), tidak dihitung ulang (6.7).

## Tugas dan urutan

| Tugas | Isi singkat | Urutan |
|---|---|---|
| F5-01 Pustaka data pendaftaran | Pasang `mpdf/mpdf`, `App\Libraries\DataPendaftaran` (baca-saja) dan ujinya, data uji karangan `tests/_support/CekDataPendaftaran.php` | Pertama |
| F5-02 Halaman cek | `/cek-pendaftaran`, pembatasan laju, tombol dan menu Cek | Setelah F5-01 |
| F5-03 Bukti PDF | `/bukti/{id}`, tata letak PDF, nama berkas, pembatasan laju | Setelah F5-01 (setelah F5-02 bila tombol cetak di halaman cek ikut diuji) |
| F5-04 PDF dokumen | Perintah `dokumen:pdf`, pengubah Markdown sederhana dan ujinya, `dokumen/pdf/rancangan-web-pendaftaran.pdf` | Setelah F5-01 |
| F5-05 Uji gabungan dan penutupan | Uji gabungan (wizard → cek → bukti bila Tahap 4 sudah di `main`), arsip `agent/arsip/tahap-5.md` | Terakhir |

Satu tugas, satu PR, berurutan di sesi ini tanpa sub-sesi (K38).

## Kesepakatan bersama

### Kesepakatan dua sesi (sama persis di prompt Tahap 4 dan Tahap 5)

1. **`main` adalah sumber kebenaran.** Di awal setiap tugas dan sebelum membuka PR, jalankan `git fetch origin main`, lalu `git rebase origin/main`. Setelah itu jalankan ulang `composer uji`. PR digabung secara squash setelah uji lokal dan CI lulus.
2. **Kepemilikan:**

| Hal | Tahap 4 | Tahap 5 |
|---|---|---|
| Model `PendaftaranModel`, `PenanggungJawabModel`, `AtletModel`, `AtletNomorModel`, `PendaftaranBereguModel`, aturan biaya dan ID, penyimpanan | pemilik | tidak membuat dan tidak mengubah. Membaca lewat pustaka baca-saja sendiri `App\Libraries\DataPendaftaran` (query builder langsung ke tabel migrasi Tahap 1). Bila model Tahap 4 sudah ada di `main`, boleh dipakai untuk membaca, tetapi tidak diubah |
| Skema tabel pendaftaran | hanya boleh **menambah** kolom lewat migrasi baru; tidak mengganti nama atau menghapus. Setiap tambahan disebut di deskripsi PR | tidak mengubah |
| Rute di `app/Config/Routes.php` | blok berkomentar sendiri: `/pendaftaran` dan turunannya | blok berkomentar sendiri: `/cek-pendaftaran` dan turunannya, serta `/bukti/(:segment)` |
| `App\Libraries\IsiHalamanDepan` | hanya `ALAMAT_PENDAFTARAN` dan aturan tombol `pendaftaran` di `tombol()` | hanya `ALAMAT_CEK_PENDAFTARAN` dan aturan tombol `cek` (aktif hanya bila pengaturan lengkap) |
| `app/Views/tata_letak/publik.php` | pemilik (pesan kilat, butir menu Pendaftaran) | hanya butir menu Cek. Pesan galat ditampilkan di tampilannya sendiri |
| Pembatasan laju | di pengendalinya sendiri dengan `service('throttler')` seperti `Admin/MasukController`, kunci berawalan `pendaftaran-`, alamat dari `$this->request->getIPAddress()` | sama, kunci berawalan `cek-` dan `bukti-` |
| Paket Composer | tidak menambah paket | satu-satunya yang menambah (`mpdf/mpdf`) |
| CSS/JS | berkas sendiri untuk halaman pendaftaran. Bila sistem aset tidak memungkinkan, pakai wilayah berpenanda komentar sendiri | berkas atau wilayah sendiri untuk halaman cek |
| `app/Helpers/aplikasi_helper.php` | tidak ditambah; fungsi bantu baru masuk pustaka sendiri | sama |
| Data uji di `tests/_support/` | berkas berawalan `Pendaftaran…` | berkas berawalan `Cek…` atau `Bukti…` |
| Dokumen | `rencana-implementasi-tahap-4.md`, tugas `F4-xx` | `rencana-implementasi-tahap-5.md`, tugas `F5-xx` |

3. **Tombol cetak bukti.** Langkah 5 wizard menaut ke `/bukti/<ID pendaftaran>` (rancangan 5). Tautan ini boleh menghasilkan 404 sampai Tahap 5 masuk `main`.
4. **`agent/PROGRES.md`.** Bagian "Sedang berjalan" berisi satu baris per tahap. Tiap sesi hanya mengubah baris dan baris tabel miliknya. Bila bentrok, pertahankan keduanya.
5. **`agent/KEPUTUSAN.md`.** Nomor K diambil dari `main` terbaru sesaat sebelum commit. Bila saat digabung nomornya sudah dipakai, PR yang digabung belakangan menomori ulang miliknya sendiri, termasuk rujukannya di dokumen lain.
6. **Konflik saat rebase.** Selesaikan sesuai tabel kepemilikan. Bagian milik sesi lain dipertahankan apa adanya. Bila butuh sesuatu dari berkas milik sesi lain, jangan ubah diam-diam. Tambahkan seminimal mungkin hanya bila tidak terhindarkan, sebutkan di deskripsi PR, dan catat di berkas tugas.
7. **Penutupan.** Tahap yang ditutup **belakangan** menjalankan uji gabungan wizard → cek → bukti pada satu salinan `main`, lalu memperbaiki sambungannya. Tahap yang ditutup lebih dulu mencatat hal yang menunggu di arsipnya.
8. **Tanpa sub-sesi.** Dua sesi ini sudah memakai jatah bersama (K38).

### Pustaka data pendaftaran

`App\Libraries\DataPendaftaran` (F5-01) satu-satunya tempat halaman cek dan bukti membaca data pendaftaran. Hanya membaca, lewat pembangun kueri ke tabel migrasi Tahap 1 (`pendaftaran`, `penanggung_jawab`, `atlet`, `atlet_nomor`, `pendaftaran_beregu`, ditambah `kelompok_umur` dan `nomor_lomba` untuk nama). Tidak memakai dan tidak mengubah model Tahap 4.

| Metode | Isi |
|---|---|
| `cariId(string $id): ?array` | Satu pendaftaran lengkap, atau `null`. ID dibandingkan tanpa membedakan huruf besar-kecil |
| `cariNpsn(string $npsn): list<array>` | Semua pendaftaran dengan NPSN itu, urut dari yang pertama dibuat |

Bentuk satu pendaftaran (bentuk persis ditulis di komentar kelas):

- kepala: `id_pendaftaran`, `jenjang` dan labelnya, `npsn`, `nama_sekolah`, `kabupaten`, `dibuat_pada`, `status` (`menunggu`/`lunas`) dan labelnya ("Menunggu Konfirmasi Pembayaran"/"Lunas"), `subtotal_individu`, `subtotal_beregu`, `total` (angka tersimpan);
- `penanggung_jawab`: nama dan nomor WA, tanpa foto;
- `atlet`: nama, NISN, tanggal lahir, jenis kelamin, kelompok umur, nomor dada (`null` bila belum ada), dan nama nomor lomba yang diikuti (individu dan beregu);
- `individu`: satu baris per atlet per nomor individu, dengan biaya tersimpan di `atlet_nomor.biaya`;
- `beregu`: satu baris per nomor beregu yang dipakai pendaftaran itu: nama nomor, biaya tim tersimpan (`pendaftaran_beregu.biaya_tim`, atau `null` bila pendaftaran susulan hanya menambah anggota tanpa baris di `pendaftaran_beregu`), dan anggota dari pendaftaran itu (nama, NISN, nomor dada).

Teks belum di-escape dan uang belum diformat; tampilan memakai `esc()`, `rupiah()`, dan `tanggal_id()`.

### Halaman cek

- Rute `GET cek-pendaftaran` → `App\Controllers\CekPendaftaranController::index`. Pencarian memakai parameter `?kode=` (formulir GET: tidak mengubah data, bisa dibuka ulang dan dibagikan sebagai tautan).
- `kode` dirapikan (spasi dibuang). Delapan angka = NPSN. Diawali `MAC` = ID pendaftaran. Selain itu pesan "Tulis NPSN (8 angka) atau ID pendaftaran (contoh MAC2A0000126)…".
- Pembatasan laju hanya dihitung bila `kode` diisi: 10 pencarian per menit per alamat IP, kunci `cek-` + hash alamat. Bila lewat, status 429, kepala `Retry-After`, dan pesan yang menyebut berapa detik harus menunggu.
- Pengaturan belum lengkap: halaman tetap terbuka dengan pesan ramah "Cek pendaftaran belum bisa dipakai karena panitia masih menyiapkan pengaturan", tanpa formulir.
- Tampilan `app/Views/cek_pendaftaran.php` dan potongan `app/Views/cek_pendaftaran/_kartu.php` (satu kartu per pendaftaran). Tabel lebar dibungkus `.table-responsive`; tanpa gulir mendatar pada halaman di lebar 390 piksel.
- Gaya di wilayah `/* ---------- Halaman cek (Tahap 5) ---------- */` di akhir `public/aset/css/tema.css` bila perlu; utamakan kelas Bootstrap.
- Menu **Cek Pendaftaran** di `tata_letak/publik.php` selalu aktif (alamat dari `IsiHalamanDepan::ALAMAT_CEK_PENDAFTARAN`); tombol **Cek Pendaftaran** di halaman depan hanya aktif bila pengaturan lengkap (`tombol()` memberi alamat `null` bila belum).

### Bukti PDF

- Rute `GET bukti/(:segment)` → `App\Controllers\BuktiController::index/$1`.
- `App\Libraries\BuktiPendaftaran` menyusun HTML (tampilan `app/Views/bukti/pdf.php`) dan PDF (mPDF, `A4-L`, folder sementara `WRITEPATH . 'cache/mpdf'`), serta nama berkasnya. PDF dikirim `inline` dengan `Content-Type: application/pdf`, tanpa disimpan di disk.
- Pembatasan laju: 10 bukti per menit per alamat IP, kunci `bukti-` + hash alamat; 429 dengan pesan ramah (HTML).
- Bukti tidak dibatasi kelengkapan pengaturan: pendaftaran yang sudah tersimpan tetap bisa dicetak.
- Logo persegi dari `HalamanDepan.logo_persegi` (jalur berkas lewat `Unggahan`); bila belum ada, kepala tanpa logo.

### PDF dokumen

- Perintah `php spark dokumen:pdf` (`app/Commands/DokumenPdf.php`) membaca semua `dokumen/final/*.md`, mengubahnya menjadi HTML dengan `App\Libraries\MarkdownSederhana` (judul, paragraf, daftar, tabel, tebal, miring, kode, tautan; cukup untuk dokumen repo ini), lalu menulis `dokumen/pdf/<nama>.pdf` dengan mPDF (A4 tegak).
- Tanpa paket Markdown tambahan: bagian Markdown yang dipakai dokumen repo ini kecil, cukup beberapa puluh baris (ALUR-KERJA bagian Kode).
- Setiap dokumen final berubah, PDF dibuat ulang dengan perintah yang sama dalam PR yang sama.

### Uji

- `composer uji` tetap satu-satunya perintah.
- Data uji karangan di `tests/_support/CekDataPendaftaran.php`: diisi langsung ke tabel (tanpa model Tahap 4), nama dan NISN karangan.
- Berkas uji: `tests/basisdata/DataPendaftaranTest.php` (F5-01), `tests/fitur/CekPendaftaranTest.php` (F5-02), `tests/fitur/BuktiPendaftaranTest.php` (F5-03), `tests/unit/MarkdownSederhanaTest.php` (F5-04).
- PDF diperiksa di memori (awalan `%PDF`, kepala respons, nama berkas); teks isi diperiksa pada HTML sebelum diubah menjadi PDF. PDF berisi data siswa tidak pernah ditulis ke repo.
- Yang diuji paling sedikit: NPSN dan ID ditemukan/tidak ditemukan, masukan keliru, foto tidak tampil, data di-escape, angka biaya dari nilai tersimpan (bukan dihitung ulang), pengaturan belum lengkap, pembatasan laju cek dan bukti, tombol Cek aktif/nonaktif, nama berkas bukti, 404 untuk ID yang tidak ada.

## Pembagian berkas

| Tugas | Berkas |
|---|---|
| F5-01 | `composer.json`, `composer.lock`, `app/Libraries/DataPendaftaran.php`, `tests/_support/CekDataPendaftaran.php`, `tests/basisdata/DataPendaftaranTest.php` |
| F5-02 | `app/Controllers/CekPendaftaranController.php`, `app/Views/cek_pendaftaran.php`, `app/Views/cek_pendaftaran/*`, blok rute cek di `Routes.php`, `ALAMAT_CEK_PENDAFTARAN` dan aturan tombol `cek` di `IsiHalamanDepan`, wilayah halaman cek di `tema.css`, uji Tahap 3 yang terdampak langsung (tombol dan menu "Segera"), `tests/fitur/CekPendaftaranTest.php` |
| F5-03 | `app/Controllers/BuktiController.php`, `app/Libraries/BuktiPendaftaran.php`, `app/Views/bukti/*`, blok rute bukti di `Routes.php`, `tests/fitur/BuktiPendaftaranTest.php` |
| F5-04 | `app/Commands/DokumenPdf.php`, `app/Libraries/MarkdownSederhana.php`, `dokumen/pdf/*.pdf`, `tests/unit/MarkdownSederhanaTest.php` |
| F5-05 | `agent/arsip/tahap-5.md`, rencana ini (bagian perubahan), sambungan yang ditemukan uji gabungan |

Setiap tugas juga memperbarui berkas tugasnya dan baris Tahap 5 di `agent/PROGRES.md`.

## Yang ditunda

| Hal | Ke tahap | Alasan |
|---|---|---|
| Uji gabungan wizard → cek → bukti | Tahap yang ditutup belakangan (kesepakatan 7) | Wizard dibuat Tahap 4 di sesi lain |
| Rekap PDF panel admin | 6 | Rancangan 9 |

## Cara menguji di komputer pemilik

Setelah Tahap 5 digabung, di terminal Laragon pada folder proyek:

1. `composer install`
2. `php spark migrate --all`
3. `composer uji`
4. Buka `http://milklife.test/cek-pendaftaran`, cari NPSN atau ID pendaftaran yang sudah didaftarkan lewat wizard, lalu tekan "Cetak bukti".
5. `php spark dokumen:pdf` membuat ulang PDF di `dokumen/pdf/`.

## Asumsi yang perlu ditinjau

1. Menu **Cek Pendaftaran** di kepala selalu aktif; bila pengaturan belum lengkap, halamannya menjelaskan bahwa cek sedang disiapkan. Tombol di halaman depan mengikuti rancangan 6.1 (aktif hanya bila lengkap).
2. Bukti tetap bisa dicetak walau pengaturan menjadi tidak lengkap.
3. Batas laju: 10 pencarian cek dan 10 bukti per menit per alamat IP.
4. Tim beregu dari pendaftaran susulan yang hanya menambah anggota ditulis dengan biaya "tanpa biaya tim (tim sudah terdaftar)".
5. Nama sekolah di nama berkas ditulis tanpa spasi dan tanda baca, huruf apa adanya.
