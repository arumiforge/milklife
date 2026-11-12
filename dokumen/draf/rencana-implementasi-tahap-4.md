# Rencana Implementasi Tahap 4 — Pendaftaran

| | |
|---|---|
| Status | Draf, dikerjakan atas penugasan pemilik 12 November 2026 (K48); menunggu tinjauan pemilik |
| Tanggal | 12 November 2026 |
| Dasar | Rancangan revisi 3 (`dokumen/final/rancangan-web-pendaftaran.md`) bagian 5, 6, 7, 11, 13, 14; keputusan K01–K51; hasil Tahap 3 (`agent/arsip/tahap-3.md`) |

## Tujuan

Untuk pelaksana tiap tugas Tahap 4 dan untuk pemilik yang meninjau. Setelah membaca, pelaksana tahu urutan tugas, berkas yang menjadi tanggung jawab Tahap 4, dan kesepakatan dengan sesi Tahap 5 yang berjalan bersamaan.

## Hasil Tahap 4

Setelah Tahap 4 selesai, official sekolah membuka `/pendaftaran` dari ponsel dan menyelesaikan wizard satu halaman dengan lima pil langkah bercentang (rancangan 7):

1. **Data Sekolah**: jenjang, kabupaten, NPSN (nama sekolah tampil dari data referensi) atau nama sekolah manual. SLB ditolak (K28).
2. **Penanggung Jawab**: 1–5 orang (batas dari pengaturan), masing-masing nama, nomor WA, dan foto wajib (K50). Foto diperkecil di peramban (sisi terpanjang 800 piksel, JPEG).
3. **Atlet**: formulir atlet dan daftar sementara (individu dan tim beregu per nomor), dengan tombol ubah dan hapus. Kelompok umur dihitung dari tahun lahir dan dibatasi jenjang (K36, K23). Sebelum lanjut, kelengkapan setiap tim diperiksa.
4. **Biaya**: tabel individu dan beregu, subtotal, dan total, selalu tampil apa pun sakelar halaman depan (K45). Rincian dihitung server. Tombol lanjut membuka jendela konfirmasi; setelah dikonfirmasi, pendaftaran tersimpan dalam satu transaksi dan ID pendaftaran dibuat (6.8).
5. **Pembayaran**: logo bank, nomor rekening, nama pemilik, total, tombol "Kirim Konfirmasi Pembayaran" (WA), dan tombol "Pendaftaran Selesai" yang menampilkan tombol cetak bukti ke `/bukti/<ID>` dan mengosongkan draf.

Isian tersimpan di `localStorage` pada setiap perubahan. Semua aturan bagian 6 diperiksa ulang di server. Tombol dan menu Pendaftaran aktif hanya saat pendaftaran terbuka, dan tata letak publik menampilkan pesan kilat.

Belum ada di Tahap 4: bukti PDF dan halaman cek (Tahap 5), panel data pendaftaran dan penyajian foto ke admin (Tahap 6).

## Tugas dan urutan

| Tugas | Isi singkat | Urutan |
|---|---|---|
| F4-01 Model, aturan, dan penyimpanan | Lima model pendaftaran, aturan murni dengan uji unit, pemeriksaan seluruh kiriman, simpan dalam transaksi, foto di luar `public/`, pengaturan batas atlet per nomor (K51) | Pertama; digabung secepatnya |
| F4-02 Pencarian NPSN | Rute dan pengendali publik, pencarian NPSN dengan pembatasan laju, penolakan SLB, data susulan (tim dan kuota yang sudah terpakai) | Setelah F4-01 |
| F4-03 Langkah 1–3 | Halaman wizard, pil langkah, draf, pengecilan foto, formulir atlet, daftar sementara, kelengkapan tim di peramban | Setelah F4-02 |
| F4-04 Langkah 4–5 | Pemeriksaan server dan rincian biaya, konfirmasi simpan, pembayaran, WA, Pendaftaran Selesai, tombol cetak bukti | Setelah F4-03 |
| F4-05 Tombol, menu, pesan kilat, dan penutupan | Tombol dan menu Pendaftaran aktif sesuai keadaan, pesan kilat publik, uji gabungan, arsip `agent/arsip/tahap-4.md` | Terakhir |

Semua tugas dikerjakan berurutan di satu sesi, tanpa sub-sesi (K38). Satu tugas, satu PR, digabung *squash* setelah `composer uji` lokal dan CI lulus.

## Kesepakatan bersama

### Kesepakatan dua sesi

Disalin persis dari penugasan pemilik (sama di rencana Tahap 5). Tahap 4 dan Tahap 5 dikerjakan bersamaan di dua sesi berbeda tempat (K48); satu-satunya titik temu adalah cabang `main`.

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

Berlaku di atas kesepakatan Tahap 1–3. Bagian di bawah ini kesepakatan internal Tahap 4.

### Aturan murni

Semua aturan bagian 6 yang tidak butuh basis data ditulis sebagai kelas tanpa permintaan HTTP, di `app/Libraries/Pendaftaran/` (namespace `App\Libraries\Pendaftaran`), masing-masing dengan uji unit (`tests/unit/Pendaftaran*Test.php`):

| Kelas | Isi |
|---|---|
| `Kabupaten` | Lima kabupaten (K01) dan pembakuannya dari isian atau data referensi ("Kab. Blora" → "Blora") |
| `KelompokUmurAtlet` | Tahun lahir + jenjang + daftar kelompok umur aktif → kelompok umur, atau galat "tidak masuk kelompok umur mana pun" / "KU 15 hanya untuk SMP/MTs, SMA/SMK/MA" (K36, K23). Bulan dan tanggal lahir tidak diperhatikan |
| `UsiaAtlet` | Usia sebagai keterangan pada tanggal mulai acara, "10 tahun" atau "10 tahun 3 bulan" sesuai `Pendaftaran.tampilan_usia` (K37) |
| `BiayaPendaftaran` | Biaya individu per atlet per nomor dari kelompok umur atlet; biaya per tim dari nomor beregu; tim susulan 0 (K07); subtotal individu, subtotal beregu, total. Bilangan bulat rupiah |
| `IdPendaftaran` | `MAC` + seri + kode jenjang + nomor urut 5 digit + 2 digit tahun (`MAC2A0000126`); seri dan tahun dari pengaturan acara |
| `KelengkapanTim` | Tim baru: anggota ≥ jumlah inti, ≤ batas anggota, nomor mix punya putra dan putri minimal (K09, K10). Tim susulan: anggota baru ≥ 1 dan anggota lama + baru ≤ batas anggota, tanpa syarat inti dan mix |
| `PemeriksaPendaftaran` | Memeriksa seluruh kiriman wizard terhadap data induk dan data yang sudah ada (NISN terdaftar, tim sekolah, kuota terpakai), menghasilkan data bersih dan daftar galat |

`PemeriksaPendaftaran` menerima semua yang dibutuhkannya lewat parameter (data induk, pengaturan, data yang sudah ada), jadi bisa diuji tanpa basis data. Pengumpulnya `App\Libraries\Pendaftaran\DataIndukPendaftaran` (membaca model Tahap 2, pengaturan, dan tabel pendaftaran).

### Aturan yang diperiksa server

Semua dari rancangan bagian 6, ditambah keputusan wawancara Tahap 4:

- **Buka-tutup (6.1):** simpan ditolak bila `bukaTutup()->terbuka()` false; pesannya `kalimat()`.
- **Sekolah (6.2):** jenjang A/B/C, kabupaten salah satu dari lima. NPSN boleh kosong; bila diisi harus 8 angka. NPSN yang ada di data referensi: nama, kabupaten, dan jenjang diambil dari data referensi (isian peramban diabaikan), dan bentuk SLB ditolak (K28). NPSN yang tidak ada di data referensi: nama sekolah wajib diisi manual. Tanpa NPSN: nama sekolah wajib.
- **Penanggung jawab (6.3):** jumlah antara `penanggung_jawab_minimal` dan `penanggung_jawab_maksimal`; nama wajib; nomor WA sah (`NomorWa`); **foto wajib** (K50).
- **Atlet (6.4):** nama wajib, disimpan huruf besar; NISN tepat 10 angka, tidak kembar di kiriman, dan belum terdaftar di basis data (K06); tanggal lahir sah dan tidak di masa depan; jenis kelamin L/P; kelompok umur dari tahun lahir dan dibuka untuk jenjang sekolah (K36, K23).
- **Nomor lomba (6.5):** nomor aktif pada kelompok umur aktif, milik kelompok umur atlet, sesuai jenis kelamin (putra hanya L, putri hanya P, mix keduanya); minimal 1 nomor per atlet; nomor individu paling banyak `nomor_individu_maksimal`.
- **Kuota nomor individu (K51):** jumlah atlet satu sekolah pada satu nomor individu paling banyak `Pendaftaran.atlet_per_nomor_maksimal`, dihitung bersama pendaftaran sebelumnya dari NPSN yang sama. Pendaftaran tanpa NPSN dihitung sendiri. Kosong berarti tidak dibatasi.
- **Tim beregu (6.6):** satu tim per nomor beregu per sekolah. Bila NPSN yang sama sudah punya tim di nomor itu, atlet di nomor itu menjadi tambahan anggota tim tersebut: tanpa biaya tim (baris `pendaftaran_beregu` dengan `biaya_tim` 0) dan dengan syarat kelengkapan tim susulan.
- **Biaya (6.7):** dihitung ulang di server dari tarif saat ini dan disalin ke pendaftaran. Angka dari peramban tidak dipakai.

Galat dikembalikan sebagai daftar `{langkah, jalur, pesan}` (contoh `{3, "atlet.2.nisn", "NISN 0012345678 sudah terdaftar. …"}`), supaya wizard bisa membuka langkah yang salah dan menandai isiannya. Pesan menyebut cara memperbaikinya.

### Penyimpanan

`App\Libraries\Pendaftaran\SimpanPendaftaran` (F4-01) satu-satunya jalan menulis pendaftaran baru:

1. Ambil kunci bernama MySQL per NPSN (`GET_LOCK`) supaya dua kiriman bersamaan dari sekolah yang sama tidak sama-sama membuat tim di nomor yang sama atau melewati kuota.
2. Baca ulang data yang sudah ada dan periksa seluruh kiriman (`PemeriksaPendaftaran`).
3. Simpan foto ke `WRITEPATH . 'unggahan/penanggung-jawab/'` (di luar `public/`), nama acak, setelah dibaca ulang dan disimpan ulang sebagai JPEG lewat GD (membuang data tambahan berkas).
4. Dalam satu transaksi: `pendaftaran` (status `menunggu`), `id_pendaftaran` dari `id` baris, `penanggung_jawab`, `atlet`, `atlet_nomor` (biaya untuk individu), `pendaftaran_beregu`.
5. Bila transaksi gagal, foto yang sudah ditulis dihapus. NISN yang ternyata sudah terdaftar pada detik yang sama ditolak oleh kunci unik basis data dan dilaporkan sebagai galat NISN biasa.

Model hanya berisi tabel, kolom, dan pembacaan sederhana; tidak ada aturan di model.

### Foto penanggung jawab

- Peramban: `<input type="file" accept="image/*" capture>` → kanvas, sisi terpanjang 800 piksel, JPEG kualitas 0,8 → data URL di draf.
- Dikirim sebagai data URL `data:image/jpeg;base64,…` di dalam JSON.
- Server: diterima hanya JPEG yang terbaca GD, sisi terpanjang ≤ 800 piksel, ukuran ≤ 500 KB setelah base64 dibuka. Selain itu ditolak dengan pesan "Foto … belum bisa dibaca. Ambil ulang fotonya."
- Jalur tersimpan di `penanggung_jawab.foto` relatif terhadap `WRITEPATH . 'unggahan/'`, contoh `penanggung-jawab/3f9c….jpg`. Penyajian ke admin dikerjakan Tahap 6.

### Rute dan pengendali

Blok berkomentar sendiri di `app/Config/Routes.php`, ditulis F4-02, pengendali publik `App\Controllers\PendaftaranController` (beda namespace dengan pengaturan admin):

| Rute | Metode | Isi | Tugas |
|---|---|---|---|
| `GET pendaftaran` | `index` | Halaman wizard, atau keterangan ramah bila pendaftaran belum terbuka | F4-03 |
| `GET pendaftaran/sekolah?npsn=…` | `sekolah` | JSON: nama, kabupaten, jenjang, atau penolakan SLB; tim beregu dan kuota yang sudah terpakai oleh NPSN itu | F4-02 |
| `POST pendaftaran/periksa` | `periksa` | JSON: galat, atau rincian biaya untuk langkah 4 | F4-04 |
| `POST pendaftaran/simpan` | `simpan` | JSON: galat, atau ID pendaftaran, total, dan alamat WA | F4-04 |

- JSON memakai CSRF lewat kepala `X-CSRF-TOKEN`; karena token berganti setiap kiriman (`Security::$regenerate`), setiap jawaban JSON menyertakan token baru.
- **Pembatasan laju** per alamat IP (`$this->request->getIPAddress()`), kunci `pendaftaran-sekolah-…`, `pendaftaran-periksa-…`, `pendaftaran-simpan-…` (alamat di-hash). Batasnya longgar karena satu sekolah atau operator seluler bisa berbagi alamat: pencarian NPSN 60 per menit, periksa 30 per menit, simpan 10 per 10 menit. Lewat batas: status 429 dan pesan berapa detik harus menunggu.
- Data yang dibutuhkan wizard (kelompok umur aktif dengan jenjang dan tahun lahir, nomor lomba aktif, biaya, batas-batas, tampilan usia, tanggal mulai acara, data rekening) disematkan di halaman sebagai JSON, dikumpulkan `DataIndukPendaftaran`.

### Draf di peramban

- Satu kunci `localStorage`: `milklife-pendaftaran-draf`, berisi versi, langkah aktif, sekolah, penanggung jawab (termasuk foto data URL), atlet, dan keadaan simpan.
- Disimpan setiap ada perubahan; bila penyimpanan peramban penuh atau ditolak, wizard tetap jalan dan menampilkan peringatan bahwa isian tidak tersimpan bila halaman dimuat ulang.
- Setelah tersimpan di server, draf ditandai tersimpan beserta ID, total, dan alamat WA: wizard terkunci di langkah 5 sampai "Pendaftaran Selesai", supaya muat ulang tidak membuat pendaftaran ganda.
- "Pendaftaran Selesai" mengosongkan draf dan menampilkan tombol cetak bukti `/bukti/<ID>`.
- Draf lama yang merujuk kelompok umur atau nomor lomba yang sudah tidak aktif ditandai lewat galat pemeriksaan server, bukan dibuang diam-diam.

### Tampilan dan aset

- Tampilan `app/Views/pendaftaran.php` dan potongan di `app/Views/pendaftaran/`, tata letak publik.
- Aset sendiri: `public/aset/js/pendaftaran.js` dan `public/aset/css/pendaftaran.css` (lewat `aset()`, jadi ikut minify Tahap 7). JavaScript biasa tanpa pustaka tambahan.
- Ponsel lebih dulu: tanpa gulir mendatar pada 390 piksel; tabel langkah 4 boleh digulir di dalam kotaknya sendiri.
- Uang ditampilkan `Rp150.000`, tanpa angka di belakang koma.
- Teks antarmuka untuk orang non-teknis; pesan galat menyebut cara memperbaikinya.

### Tombol, menu, dan pesan kilat (F4-05)

- `IsiHalamanDepan::ALAMAT_PENDAFTARAN = 'pendaftaran'`. Di `tombol()`, tombol `pendaftaran` hanya punya alamat bila `bukaTutup()->terbuka()`; selain itu nonaktif dengan keterangan label keadaan ("Sedang disiapkan", "Belum dibuka", "Ditutup"), bukan "Segera".
- Menu Pendaftaran di `tata_letak/publik.php` mengikuti aturan yang sama.
- Tata letak publik menampilkan pesan kilat `pesan` dan `pesan_galat` (termasuk dari filter batas unggah) di atas isi.

### Pengaturan baru (K51)

- `Pendaftaran.atlet_per_nomor_maksimal`: bilangan bulat atau `null` (tidak dibatasi), nilai awal `2`. Diisi di halaman pengaturan Pendaftaran ("Atlet per sekolah pada satu nomor individu, paling banyak"; kosong berarti tidak dibatasi). Tidak memengaruhi kelengkapan pengaturan.

### Uji

- `composer uji` tetap satu-satunya perintah.
- Nama berkas uji Tahap 4 diawali `Pendaftaran`: `tests/unit/Pendaftaran*Test.php`, `tests/basisdata/Pendaftaran*Test.php`, `tests/fitur/Pendaftaran*Test.php`. Pembantu dan data uji di `tests/_support/Pendaftaran…`. Uji pengaturan admin yang sudah ada (`PengaturanPendaftaranTest`) disesuaikan untuk kotak baru.
- Yang diuji paling sedikit: setiap aturan di "Aturan yang diperiksa server" (lulus dan ditolak), susulan tim dan kuota dari NPSN yang sama, ID pendaftaran, biaya tersalin, transaksi gagal tidak meninggalkan baris atau foto, NISN kembar bersamaan ditolak, SLB ditolak, pembatasan laju (429), CSRF, wizard tertutup saat pendaftaran tidak terbuka, tombol dan menu sesuai keadaan, pesan kilat.
- Data uji memakai nama, NISN, NPSN, dan foto karangan (gambar dibuat di uji, bukan berkas foto orang).

## Pembagian berkas

| Tugas | Berkas yang disentuh |
|---|---|
| F4-01 | `app/Models/PendaftaranModel.php`, `PenanggungJawabModel.php`, `AtletModel.php`, `AtletNomorModel.php`, `PendaftaranBereguModel.php`; `app/Libraries/Pendaftaran/*`; `app/Config/Pendaftaran.php` (kunci baru); pengaturan admin Pendaftaran (pengendali, tampilan, uji) untuk kotak K51; `tests/unit/Pendaftaran*`, `tests/basisdata/Pendaftaran*`, `tests/_support/Pendaftaran*` |
| F4-02 | Blok `/pendaftaran` di `app/Config/Routes.php`; `app/Controllers/PendaftaranController.php` (`sekolah`); `tests/fitur/PendaftaranSekolahTest.php` |
| F4-03 | `PendaftaranController::index`; `app/Views/pendaftaran.php`, `app/Views/pendaftaran/*`; `public/aset/js/pendaftaran.js`; `public/aset/css/pendaftaran.css`; `tests/fitur/PendaftaranHalamanTest.php` |
| F4-04 | `PendaftaranController::periksa`, `simpan`; potongan langkah 4–5; JS dan CSS pendaftaran; `tests/fitur/PendaftaranSimpanTest.php` |
| F4-05 | `IsiHalamanDepan` (`ALAMAT_PENDAFTARAN`, tombol `pendaftaran`), `app/Views/beranda/_kepala.php` (keterangan tombol nonaktif, seminimal mungkin), `app/Views/tata_letak/publik.php` (pesan kilat, butir menu Pendaftaran); uji halaman depan dan tata letak yang terdampak; arsip dan dokumen penutupan |

Berkas milik Tahap 5 (blok rute cek dan bukti, `DataPendaftaran`, aturan tombol `cek`, butir menu Cek) tidak disentuh. `agent/PROGRES.md` hanya baris Tahap 4.

## Yang ditunda

| Hal | Ke tahap | Alasan |
|---|---|---|
| Bukti PDF di `/bukti/<ID>` | 5 | Milik sesi Tahap 5; tautan boleh 404 sampai digabung |
| Menampilkan foto penanggung jawab ke admin | 6 | Panel data |
| Mencegah perubahan jumlah inti atau batas anggota nomor beregu yang sudah dipakai | 6 | Ikut aturan ubah data di panel (catatan arsip Tahap 3) |
| Minify `pendaftaran.css` dan `pendaftaran.js` | 7 | Rancangan bagian 16 |

## Cara menguji di komputer pemilik

Setelah Tahap 4 digabung, di terminal Laragon pada folder proyek:

1. `composer install`
2. `php spark migrate --all`
3. `composer uji`
4. Lengkapi pengaturan di `/admin/pengaturan` dan buka pendaftaran (jadwal atau buka manual).
5. Buka `http://milklife.test/pendaftaran` dari ponsel atau mode ponsel peramban, lalu daftarkan sekolah dengan data karangan.

## Asumsi yang perlu ditinjau

1. NPSN yang ada di data referensi menentukan nama, kabupaten, dan jenjang sekolah; pilihan official di langkah 1 menyesuaikan, bukan ditolak.
2. NISN dan aturan lintas pendaftaran (tim, kuota) diperiksa server saat pindah dari langkah 3 ke langkah 4 dan sekali lagi saat simpan, bukan setiap atlet ditambahkan.
3. Pada pendaftaran susulan, atlet yang dipilih di nomor beregu yang timnya sudah ada otomatis menjadi tambahan anggota tim itu, tanpa biaya tim. Wizard memberi tahu hal ini di daftar sementara.
4. Nilai awal batas atlet per sekolah per nomor individu adalah 2 (K51). Pemilik tidak menyebut angkanya; admin bisa mengubah atau mengosongkannya.
5. Batas pembatasan laju (pencarian NPSN 60 per menit, periksa 30 per menit, simpan 10 per 10 menit per alamat IP) cukup longgar untuk sekolah yang berbagi jaringan.
6. Nama penanggung jawab disimpan seperti diketik (hanya nama atlet yang dijadikan huruf besar, rancangan 6.4).

## Perubahan saat pengerjaan

| Bagian | Rencana | Yang berlaku | Alasan |
|---|---|---|---|
