# Rencana Implementasi Tahap 7 — Penyelesaian

| | |
|---|---|
| Status | Draf, **disetujui pemilik 12 November 2026 termasuk delapan asumsinya (K69)** |
| Tanggal | 12 November 2026 |
| Dasar | Rancangan revisi 3 (`dokumen/final/rancangan-web-pendaftaran.md`) bagian 4, 13, 14, 15, 16; keputusan K01–K68, terutama K31, K46, K62, K65–K68; arsip Tahap 1–6 bagian "Yang dibawa ke tahap berikutnya" |

## Tujuan

Untuk pelaksana tiap tugas Tahap 7 (sub-sesi) dan untuk pemilik yang meninjau. Setelah membaca, pelaksana tahu isi tugasnya, berkas yang boleh disentuh, urutan dan ketergantungan dengan tugas lain, serta aturan yang berlaku.

## Hasil Tahap 7

Setelah Tahap 7 selesai:

1. **Aset kecil**: `php spark aset:minify` membuat versi kecil (`.min.css`, `.min.js`) dari semua CSS dan JavaScript milik aplikasi. Bila `aplikasi.minify = true`, halaman memakai versi kecil dan HTML diperkecil saat dikirim. PDF, Excel, JSON, dan berkas unduhan tidak ikut diperkecil.
2. **Gambar kecil**: gambar yang diunggah admin (gambar kepala, logo, logo bank) dikecilkan saat disimpan, supaya halaman depan tetap ringan dibuka dari ponsel.
3. **Keamanan produksi**: mengganti kata sandi admin mengeluarkan sesi lain milik akun yang sama; kepala keamanan HTTP aktif; halaman galat 404 dan galat umum di mode produksi ramah dan tanpa rincian teknis; isian `.env` untuk produksi dijelaskan di `.env.example`.
4. **Tim tanpa pembayar (K68)**: rincian pendaftaran, dasbor, dan rekap keuangan menandai tim beregu yang kehilangan pembayar biaya timnya, dan admin bisa membebankan biaya tim itu ke salah satu pendaftaran lewat Ubah.
5. **Cadangan harian (K66)**: `php spark cadangan:buat` menulis satu berkas zip berisi basis data serta foto penanggung jawab, bukti bayar, dan unggahan admin ke folder di luar proyek, dan menghapus cadangan yang lebih tua dari 14 hari. Penjadwal Tugas Windows menjalankannya tiap malam.
6. **Panduan pemasangan produksi**: dokumen langkah demi langkah untuk Laragon: virtual host Nginx ke `public/`, HTTPS lewat Cloudflare Tunnel, `.env` produksi, minify, cadangan terjadwal, pemulihan, dan pembaruan versi.
7. **Rancangan revisi 4**: rancangan final disesuaikan dengan keputusan K49–K68, lalu PDF-nya dibuat ulang.
8. **Uji menyeluruh**: seluruh alur dicoba di mode produksi dengan minify aktif, di ukuran ponsel dan komputer, dan dikunci dengan satu uji fitur alur lengkap.

## Tugas dan urutan

| Tugas | Isi singkat | Bergantung pada | Gelombang |
|---|---|---|---|
| F7-01 Minify dan pengecilan gambar | Paket `matthiasmullie/minify`, perintah `aset:minify`, filter perkecil HTML, pengecilan gambar unggahan admin, batas waktu `composer uji` | — | 1 |
| F7-02 Keamanan dan mode produksi | Sesi lain keluar saat ganti sandi, kepala keamanan, halaman galat produksi, bagian produksi `.env.example` | — | 1 |
| F7-04 Rancangan revisi 4 | Draf rancangan dengan K49–K68, persetujuan pemilik, pindah ke final, PDF dibuat ulang | — | 1 |
| F7-03 Tim tanpa pembayar | Penanda di rincian, dasbor, rekap keuangan; bebankan biaya tim lewat Ubah; peringatan di jendela hapus (K68) | — | 2 (atau mengisi tempat kosong gelombang 1) |
| F7-05 Cadangan dan panduan produksi | Perintah `cadangan:buat`, dokumen panduan pemasangan produksi, tautan di `README.md` | F7-01, F7-02 | 2 |
| F7-06 Uji menyeluruh | Pemasangan bersih mengikuti panduan, alur lengkap di peramban (ponsel dan komputer) dengan minify aktif, uji fitur alur lengkap, perbaikan sambungan | F7-01 sampai F7-05 (F7-04 boleh belum final) | 3 |
| F7-07 Penutupan | Arsip `agent/arsip/tahap-7.md`, `PROGRES.md`, `KONTEKS.md`, `README.md` | Semua | 4 (koordinator) |

Paling banyak 3 sub-sesi berjalan bersamaan (K38, K65). Sub-sesi dibuat dan dipantau koordinator (K65). Gelombang berikutnya dimulai setelah tugas yang menjadi ketergantungannya digabung ke `main`. F7-03 tidak bergantung pada apa pun dan dipakai untuk mengisi tempat kosong bila tugas gelombang 1 selesai lebih cepat. F7-04 hanya menyentuh dokumen dan bisa lama menunggu tinjauan pemilik, jadi dimulai paling awal.

## Aturan untuk semua sub-sesi

1. **`main` adalah sumber kebenaran.** Di awal tugas dan sebelum membuka PR: `git fetch origin main` lalu `git rebase origin/main`, kemudian uji lagi. Satu tugas, satu PR; digabung *squash* setelah uji lokal dan CI lulus.
2. **Uji**: `composer uji`. Di kontainer cloud seluruh uji bisa lebih lama dari batas 300 detik Composer; sampai F7-01 digabung, jalankan `COMPOSER_PROCESS_TIMEOUT=0 composer uji`.
3. **Kepemilikan berkas** mengikuti tabel "Pembagian berkas". Bila butuh sesuatu dari berkas milik tugas lain, tambahkan seminimal mungkin hanya bila tidak terhindarkan, sebutkan di deskripsi PR, dan catat di berkas tugas.
4. **Paket Composer**: hanya F7-01 yang menambah (`matthiasmullie/minify`). Tidak ada CDN dan tidak ada Node (rancangan 4).
5. **`agent/PROGRES.md`**: tiap tugas hanya mengubah baris tugasnya. **`agent/KEPUTUSAN.md`**: sub-sesi tidak menambah keputusan; pertanyaan untuk pemilik ditulis di deskripsi PR dan berkas tugas, lalu dicatat koordinator setelah dijawab.
6. **Data uji** memakai nama, NISN, dan NPSN karangan, dan gambar yang dibuat di uji. Data siswa, foto, cadangan basis data, dan rahasia tidak masuk git.
7. **Bahasa**: semua tulisan berbahasa Indonesia. Commit atas nama Arumi Studios (`226232558+arumiforge@users.noreply.github.com`) tanpa atribusi alat AI di commit, PR, kode, maupun dokumen. Berkas `sumber/` tidak diubah.

## Kesepakatan teknis

### Minify (F7-01)

- Perintah `php spark aset:minify` (`app/Commands/MinifyAset.php`): untuk setiap `*.css` dan `*.js` di `public/aset/` kecuali folder `vendor/` dan berkas yang sudah `.min`, tulis berkas `.min.css`/`.min.js` di sebelahnya dengan `matthiasmullie/minify`. Keluaran menyebut tiap berkas dan ukurannya sebelum dan sesudah. Aman dijalankan ulang.
- Berkas `.min` aset aplikasi **tidak masuk git** (`.gitignore`); dibuat di server setiap pasang dan perbarui (langkah di panduan F7-05). Pustaka di `vendor/` tetap memakai versi kecil yang sudah disimpan.
- `aset()` di `app/Helpers/aplikasi_helper.php` sudah memilih berkas `.min` bila `aplikasi.minify` aktif dan berkasnya ada, jadi tampilan tidak diubah.
- Filter `perkecilhtml` (`app/Filters/PerkecilHtml.php`, global *after*): hanya bekerja bila `aplikasi.minify` aktif dan jawaban berjenis `text/html`. Spasi di antara tag dirapatkan; isi `<pre>`, `<textarea>`, `<script>`, dan `<style>` tidak diubah. PDF, Excel, JSON, gambar, dan unduhan dilewati (catatan Tahap 5).
- `composer.json`: `"process-timeout": 0` di bagian `config`, supaya `composer uji` tidak dihentikan Composer.

### Pengecilan gambar (F7-01)

- `Unggahan::simpanGambar()` mengecilkan gambar yang sisi terpanjangnya lebih dari 1920 piksel ke 1920 piksel dengan GD. Format asli dipertahankan (PNG tetap transparan); gambar yang sudah lebih kecil disimpan apa adanya. Foto penanggung jawab dan bukti bayar sudah dikecilkan sendiri dan tidak diubah.

### Keamanan dan mode produksi (F7-02)

- **Sesi lain keluar saat ganti sandi**: saat masuk, sesi menyimpan sidik pendek dari hash kata sandi akun. `AdminFilter` membandingkannya dengan hash tersimpan di setiap permintaan; bila berbeda, sesi diakhiri dan admin diarahkan ke halaman masuk dengan pesan "Kata sandi akun ini baru saja diganti. Silakan masuk lagi dengan kata sandi baru." Sesi yang mengganti kata sandi diperbarui sidiknya sehingga tetap masuk. Ganti sandi lewat `php spark admin:buat` juga mengeluarkan semua sesi akun itu. Diusahakan tanpa migrasi.
- **Kepala keamanan**: filter `secureheaders` bawaan CodeIgniter aktif sebagai global *after*. Pastikan PDF, Excel, gambar admin, dan pratinjau tetap terbuka.
- **Halaman galat**: kerangka `_galat.php` sudah ada. Pastikan 404, 400, dan galat umum (termasuk pengecualian tak tertangkap) di `CI_ENVIRONMENT = production` tampil ramah, berbahasa Indonesia, tanpa jejak teknis, dan ada uji yang membuktikannya.
- **`.env.example` bagian Produksi**: berkomentar, tanpa nilai rahasia: `CI_ENVIRONMENT = production`, `app.baseURL` berawalan `https://`, `aplikasi.minify = true`, kuki aman, dan nilai lain yang perlu di balik Cloudflare Tunnel. Periksa bahwa `app.forceGlobalSecureRequests` tidak menimbulkan pengalihan berulang di balik terowongan (Nginx menerima HTTP dari `cloudflared`); tulis nilai yang benar beserta alasannya.

### Tim tanpa pembayar (F7-03, K68)

- Satu tim adalah satu NPSN pada satu nomor beregu (K07; sama dengan cara hitung rekap F6-06). Tim **tanpa pembayar** bila tidak ada satu pun baris `pendaftaran_beregu` tim itu yang `biaya_tim`-nya lebih dari 0, sementara tarif beregu nomor itu saat ini lebih dari 0. Sekolah tanpa NPSN tidak bisa punya tim susulan dan tidak pernah ditandai.
- Pembacaan di satu pustaka `App\Libraries\Panel\TimTanpaPembayar` yang dipakai rincian, dasbor, dan rekap keuangan, supaya angkanya selalu sama.
- Rincian: peringatan di bagian tim beregu pendaftaran yang memuat tim itu. Dasbor: jumlah tim tanpa pembayar (hanya tampil bila ada). Rekap keuangan: daftar tim tanpa pembayar beserta pendaftaran anggotanya; ikut di Excel dan PDF.
- Ubah: pada tim yang ditandai, kotak centang "Bebankan biaya tim ke pendaftaran ini". Biaya memakai tarif saat ini; subtotal dan total dihitung ulang; K32 berlaku (bila Lunas dan total berubah, status kembali ke Menunggu dengan persetujuan).
- Jendela hapus pendaftaran dan simpan Ubah yang menghapus tim memperingatkan bila tindakan itu meninggalkan tim tanpa pembayar.

### Cadangan (F7-05, K66)

- `php spark cadangan:buat` (`app/Commands/BuatCadangan.php`): menulis `<folder>/cadangan-milklife-<tttt-bb-hh-jjmm>.zip` berisi `basisdata.sql` (dari `mysqldump`, satu transaksi), `writable/unggahan/`, dan `public/unggahan/`. Lalu menghapus berkas `cadangan-milklife-*.zip` di folder itu yang lebih tua dari jumlah hari yang diatur. Kode keluar bukan 0 bila gagal, supaya Penjadwal Tugas mencatatnya.
- Pengaturan di `.env` (`Config\Cadangan`): `cadangan.folder` (wajib, harus di luar folder proyek; perintah menolak folder di dalam proyek), `cadangan.simpanHari = 14`, `cadangan.mysqldump` (jalur `mysqldump.exe` Laragon; bawaan `mysqldump`). Sandi basis data tidak ditulis di baris perintah yang terlihat proses lain (pakai berkas opsi sementara).
- Aturan masa simpan sebagai kelas murni dengan uji unit. Pemulihan dilakukan manual mengikuti panduan; tidak ada perintah pulihkan otomatis.

### Panduan pemasangan produksi (F7-05)

- `dokumen/draf/panduan-pemasangan-produksi.md` dari `dokumen/templat/dokumen.md`, untuk orang non-teknis yang mengikuti langkah: persiapan Laragon dan PHP (`php.ini`: batas unggah, OPcache), letak folder, `composer install --no-dev`, `.env` produksi (mengacu bagian Produksi `.env.example` dari F7-02), `key:generate`, `migrate --all`, `sekolah:impor`, `data:awal`, `admin:buat`, `aset:minify`, virtual host Nginx ke `public/`, HTTPS lewat Cloudflare Tunnel (`cloudflared` sebagai layanan Windows di komputer yang sama, K31), cadangan harian lewat Penjadwal Tugas, cara memulihkan cadangan, cara memperbarui versi (tarik `main`, `composer install --no-dev`, `migrate --all`, `aset:minify`), dan daftar periksa sebelum membuka pendaftaran.
- Setelah pemilik menyetujui di PR, dipindahkan ke `dokumen/final/` dan PDF-nya dibuat dengan `php spark dokumen:pdf` di PR yang sama. `README.md` menautkannya dan baris "Status"-nya diperbarui.

### Rancangan revisi 4 (F7-04)

- Salin `dokumen/final/rancangan-web-pendaftaran.md` ke `dokumen/draf/` dan ubah bagian yang terdampak: 6.3 foto wajib (K50); 6 kuota dan batas nomor individu (K51, K53); 6.6 konfirmasi tim susulan (K57) dan tim tanpa pembayar (K68); 6.8 ID tetap saat diubah (K63); 8 cek dan bukti tanpa tanggal lahir dan nomor dada, nama nomor dengan kategori (K49, K52); 9 panel tanpa pendaftaran baru, nomor dada K59, saringan status rekap (K58–K60), tim tanpa pembayar (K68); 13 sesi keluar saat ganti sandi dan kepala keamanan (K67); 14 CI GitHub (K46); 15 cadangan harian (K66) dan panduan; 16 dan 17 seperlunya. Baris "Revisi 4" di tabel kepala menyebut keputusan dan bagian yang berubah.
- Pemilik meninjau di PR. Setelah disetujui: draf dipindahkan ke `dokumen/final/` (menimpa yang lama), `php spark dokumen:pdf` dijalankan, dan koordinator mencatat keputusan persetujuannya.

### Uji menyeluruh (F7-06)

- Pemasangan bersih di kontainer cloud mengikuti panduan F7-05 sejauh berlaku di Linux, dengan `CI_ENVIRONMENT = production` dan `aplikasi.minify = true`. Langkah panduan yang keliru atau kurang jelas diperbaiki di PR ini.
- Dicoba di Chromium pada lebar 390 piksel dan 1280 piksel, dengan memeriksa galat konsol JavaScript: halaman depan, unduhan, wizard lengkap (termasuk susulan dan konfirmasi tim), cek, bukti PDF, masuk admin, dasbor, pendaftaran (saring, Excel, rincian, bukti bayar, Lunas, Ubah, hapus), atlet, rekap (Excel dan PDF), semua halaman pengaturan, akun (ganti sandi dengan dua sesi), halaman galat. PDF dan Excel tidak ikut diperkecil.
- Satu uji fitur alur lengkap (`tests/fitur/AlurMenyeluruhTest.php`) dengan minify aktif: simpan pendaftaran dan susulan → cek → bukti → unggah bukti bayar → Lunas dan nomor dada → Ubah → rekap → hapus.
- Hasil (yang dicoba, yang diperbaiki, yang tersisa) ditulis di berkas tugas. Tangkapan layar tidak masuk git.

## Pembagian berkas

| Tugas | Berkas yang disentuh |
|---|---|
| F7-01 | `composer.json`, `composer.lock`; `app/Commands/MinifyAset.php`; `app/Filters/PerkecilHtml.php`; `app/Config/Filters.php` (alias dan satu baris global *after*); `app/Config/Aplikasi.php`; `app/Libraries/Unggahan.php` (pengecilan); `.gitignore`; `tests/*/MinifyAset*`, `tests/*/PerkecilHtml*`, uji `Unggahan` yang terdampak |
| F7-02 | `app/Filters/AdminFilter.php`; `app/Controllers/Admin/MasukController.php`, `AkunController.php`; `app/Commands/BuatAdmin.php`; `app/Models/AdminModel.php` seperlunya; `app/Views/errors/html/*`; `app/Config/Filters.php` (satu baris `secureheaders`); `app/Config/App.php`, `Cookie.php`, `Security.php` seperlunya; `.env.example` (bagian Produksi); uji masuk, akun, filter admin, dan galat yang terdampak |
| F7-03 | `app/Libraries/Panel/TimTanpaPembayar.php`, `UbahPendaftaran.php`, `HapusPendaftaran.php`, `Rekap.php`, `RekapPdf.php`; `app/Controllers/Admin/PendaftaranController.php` (ubah, perbarui, hapus), `DasborController.php`, `RekapController.php`; `app/Views/admin/pendaftaran/rincian.php`, `ubah.php`; `app/Views/admin/dasbor.php` (bagian angka); `app/Views/admin/rekap/*` (keuangan); `public/aset/js/admin-pendaftaran.js`, `admin-ubah-pendaftaran.js`; `tests/*/PanelTimTanpaPembayar*` dan uji Panel yang terdampak |
| F7-04 | `dokumen/draf/rancangan-web-pendaftaran.md`, lalu `dokumen/final/rancangan-web-pendaftaran.md` dan `dokumen/pdf/rancangan-web-pendaftaran.pdf` setelah disetujui |
| F7-05 | `app/Commands/BuatCadangan.php`; `app/Config/Cadangan.php`; `app/Libraries/Cadangan/*`; `.env.example` (bagian Cadangan); `dokumen/draf/panduan-pemasangan-produksi.md`, lalu `dokumen/final/` dan `dokumen/pdf/` setelah disetujui; `README.md`; `tests/*/Cadangan*` |
| F7-06 | `tests/fitur/AlurMenyeluruhTest.php`; perbaikan sambungan seperlunya (disebut satu per satu di PR); panduan F7-05 bila langkahnya keliru |
| F7-07 | `agent/arsip/tahap-7.md`, `agent/PROGRES.md`, `agent/KONTEKS.md`, `README.md` (baris Status), berkas tugas, bagian "Perubahan saat pengerjaan" rencana ini |

`app/Config/Filters.php` dan `.env.example` disentuh dua tugas di bagian yang berbeda; yang digabung belakangan menyesuaikan saat *rebase*.

## Uji

- `composer uji` tetap satu-satunya perintah, dan CI menjalankannya di setiap PR (K46).
- Paling sedikit diuji: `aset:minify` membuat berkas `.min` yang lebih kecil dan melewati `vendor/` serta berkas `.min`; HTML diperkecil hanya bila minify aktif, isi `<pre>`/`<textarea>`/`<script>` utuh, PDF/Excel/JSON tidak berubah; gambar besar dikecilkan dan PNG tetap transparan; sesi lain keluar setelah ganti sandi lewat panel dan lewat `admin:buat`, sesi yang mengganti tetap masuk; kepala keamanan ada; halaman galat produksi tanpa jejak teknis; tim tanpa pembayar ditandai sama di rincian, dasbor, dan rekap, dan hilang setelah dibebankan lewat Ubah; masa simpan cadangan 14 hari; cadangan menolak folder di dalam proyek; alur lengkap F7-06.

## Yang ditunda

| Hal | Ke | Alasan |
|---|---|---|
| Pemasangan nyata di komputer produksi Laragon dan Cloudflare Tunnel | Pemilik, mengikuti panduan F7-05 | Agen tidak menjangkau komputer produksi |
| Perintah pulihkan cadangan otomatis | Tidak dikerjakan | Pemulihan jarang dan berisiko menimpa data; langkah manual di panduan |

## Cara menguji di komputer pemilik

Setelah Tahap 7 digabung, di terminal Laragon pada folder proyek:

1. `composer install`
2. `php spark migrate --all`
3. `composer uji`
4. Isi `.env`: `aplikasi.minify = true`, lalu `php spark aset:minify`. Buka halaman depan dan panel; tampilan harus sama seperti sebelumnya.
5. Masuk admin di dua peramban, ganti kata sandi di salah satunya; peramban lain harus diminta masuk lagi.
6. Isi `cadangan.folder` di `.env` dengan folder di luar proyek, lalu `php spark cadangan:buat`; berkas zip harus muncul di folder itu.
7. Ikuti panduan pemasangan produksi untuk komputer server.

## Asumsi yang perlu ditinjau

1. Berkas `.min` aset aplikasi tidak masuk git; dibuat di server dengan `php spark aset:minify` setiap pasang dan perbarui.
2. Gambar unggahan admin dikecilkan ke sisi terpanjang 1920 piksel saat disimpan; format asli dipertahankan, gambar yang lebih kecil tidak diubah. Gambar yang sudah terunggah sebelumnya tidak diubah.
3. HTTPS ditangani Cloudflare lewat Tunnel. Nginx Laragon tetap HTTP di komputer server dan tidak memasang sertifikat sendiri.
4. Cadangan berupa satu zip per hari (basis data dan tiga folder unggahan) di folder dari `.env` yang harus di luar folder proyek; pemulihan manual mengikuti panduan.
5. Setelah ganti kata sandi, sesi lain keluar pada permintaan berikutnya; sesi yang mengganti tetap masuk. Ganti sandi lewat `php spark admin:buat` mengeluarkan semua sesi akun itu.
6. Tim tanpa pembayar dibebankan lewat kotak centang di Ubah dengan tarif saat ini (K32 berlaku); jendela hapus dan simpan Ubah memperingatkan bila tindakannya meninggalkan tim tanpa pembayar.
7. Uji menyeluruh dijalankan agen di kontainer cloud (Linux, Chromium) dalam mode produksi dengan minify aktif; pemasangan nyata di Laragon tetap dicoba pemilik mengikuti panduan.
8. Panduan pemasangan produksi ditulis sebagai dokumen (draf → disetujui → final dan PDF); `README.md` hanya menautkannya.

## Perubahan saat pengerjaan

| Bagian | Rencana | Yang berlaku | Alasan |
|---|---|---|---|
