# Panduan Pemasangan Produksi

| | |
|---|---|
| Status | **Disetujui pemilik pada 12 November 2026**; **revisi 1 disetujui 14 November 2026** (K100) |
| Revisi 1 | Data sekolah 4.766 sekolah dari Sekolah Kita, impor sekolah ikut dijalankan saat memperbarui versi, dan pemeriksaan menu Sekolah sebelum dan selama masa pendaftaran (K97, K99). Bagian yang berubah: 6, 10, 11 |
| Tanggal | 12 November 2026 |
| Dasar | Rancangan bagian 15; rencana implementasi Tahap 7 bagian "Cadangan" dan "Panduan pemasangan produksi" (K69); Laragon untuk produksi (K08); Cloudflare Tunnel (K31); cadangan harian 14 hari (K66); bagian PRODUKSI dan CADANGAN di `.env.example` |

## Tujuan

Untuk pemilik atau panitia yang memasang web pendaftaran di komputer server sekolah. Setelah mengikuti panduan ini langkah demi langkah, web bisa dibuka dari internet lewat alamat `https://`, data tercadang otomatis setiap malam, dan Anda tahu cara memulihkan cadangan serta memasang versi baru.

Panduan ini ditulis untuk Windows dengan Laragon. Semua perintah diketik di **terminal Laragon** (tombol **Terminal** di jendela Laragon), kecuali bila disebut lain. Ganti teks di dalam tanda `<…>` dengan isian Anda sendiri, tanpa tanda `<` dan `>`.

## Ringkasan susunan

- Komputer server menjalankan Laragon: Nginx (server web) dan MySQL (basis data). Nginx hanya melayani HTTP di komputer itu sendiri.
- `cloudflared` (Cloudflare Tunnel) berjalan di komputer yang sama sebagai layanan Windows. Ia membuka sambungan keluar ke Cloudflare, jadi router sekolah tidak perlu dibuka port-nya dan komputer tidak perlu alamat IP tetap.
- Pengunjung membuka `https://<alamat-pendaftaran>`. Cloudflare memasang HTTPS, lalu meneruskan permintaan lewat tunnel ke Nginx di komputer server.
- Setiap malam Penjadwal Tugas Windows menjalankan `php spark cadangan:buat`, yang menulis satu berkas zip cadangan ke folder di luar proyek dan menghapus cadangan yang berumur 14 hari atau lebih.

## 1. Yang perlu disiapkan

- Komputer server dengan Windows 10 atau 11 (64 bit), menyala terus selama masa pendaftaran, tersambung ke internet.
- Diska kedua atau partisi lain (misalnya `D:`) untuk cadangan. Bila tidak ada, cadangan tetap bisa di `C:`, tetapi salin rutin ke tempat lain (bagian 8).
- Akun Cloudflare yang sudah mengelola domain yang akan dipakai (misalnya `contoh.sch.id`), dan nama alamat yang dipilih, misalnya `daftar.contoh.sch.id`.
- Laragon **Full** versi terbaru dengan PHP 8.2 atau lebih baru (disarankan 8.3) dan MySQL 8. Laragon Full sudah memuat Composer dan Git.

## 2. Menyiapkan Laragon dan PHP

1. Pasang Laragon Full di `C:\laragon` (letak bawaan).
2. Buka Laragon, klik kanan di jendelanya → **Preferences** → tab **General**. Centang **Run Laragon when Windows starts** dan **Start All automatically**. Dengan begitu Nginx dan MySQL menyala sendiri setiap Windows dinyalakan dan Anda masuk ke Windows.
3. Klik kanan → **PHP** → **Version**, pilih PHP 8.3 (atau 8.2 ke atas).
4. Klik kanan → **PHP** → **Extensions**, pastikan tercentang: `intl`, `mbstring`, `gd`, `zip`, `fileinfo`, `mysqli`, `opcache`. Laragon memuat ulang PHP setiap kali satu ekstensi dicentang.
5. Klik kanan → **PHP** → **php.ini**. Cari baris berikut (tekan Ctrl+F); bila diawali `;`, hapus tanda `;` itu. Ubah nilainya menjadi seperti ini, lalu simpan:

   ```
   upload_max_filesize = 10M
   post_max_size = 12M
   opcache.enable = 1
   ```

   Batas 10 MB untuk berkas unduhan yang diunggah admin (juknis). OPcache membuat PHP lebih cepat.
6. Klik **Stop**, lalu **Start All** di Laragon.
7. Di Pengaturan Windows → **System** → **Power**: atur **Sleep** menjadi **Never** saat tersambung listrik, supaya server tidak tidur.
8. Bila Windows bertanya "Allow access" untuk `nginx`, `mysqld`, atau `httpd`, pilih **Cancel** atau jangan beri izin jaringan publik. Web dibuka lewat Cloudflare Tunnel, jadi tidak ada komputer lain yang perlu menyambung langsung.

## 3. Menyalin aplikasi

1. Buka terminal Laragon. Terminal mulai di folder `C:\laragon\www`.
2. Ambil aplikasi dari GitHub:

   ```
   git clone https://github.com/arumiforge/milklife.git
   cd milklife
   ```

3. Pasang pustaka PHP untuk produksi (tanpa alat uji):

   ```
   composer install --no-dev --optimize-autoloader
   ```

4. Klik **Reload** di Laragon. Laragon membuat alamat lokal `http://milklife.test` yang langsung menunjuk ke folder `C:\laragon\www\milklife\public`. Folder `public` saja yang dibuka peramban; kode, `.env`, foto penanggung jawab, dan bukti bayar ada di luar folder itu.

## 4. Basis data

1. Di Laragon klik kanan → **MySQL** → **Change root password**, lalu buat sandi untuk pengguna `root`. Catat sandi ini di tempat aman. Mulai sekarang setiap perintah `mysql -u root -p` menanyakan sandi itu.
2. Buat basis data dan pengguna MySQL khusus aplikasi. Ganti `<sandi-basis-data>` dengan sandi panjang buatan Anda sendiri (huruf dan angka saja paling aman), lalu jalankan baris demi baris:

   ```
   mysql -u root -p -e "CREATE DATABASE milklife CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p -e "CREATE USER 'milklife'@'localhost' IDENTIFIED BY '<sandi-basis-data>'"
   mysql -u root -p -e "CREATE USER 'milklife'@'127.0.0.1' IDENTIFIED BY '<sandi-basis-data>'"
   mysql -u root -p -e "GRANT ALL PRIVILEGES ON milklife.* TO 'milklife'@'localhost', 'milklife'@'127.0.0.1'"
   ```

   Pengguna `milklife` hanya bisa mengubah basis data `milklife`, tidak bisa menyentuh basis data lain.

## 5. Berkas `.env` produksi

`.env` berisi pengaturan dan rahasia server ini. Berkas ini tidak pernah masuk git dan tidak boleh dikirim ke siapa pun.

1. Salin contoh pengaturan, lalu buat kunci rahasia:

   ```
   copy .env.example .env
   php spark key:generate
   ```

2. Buka `C:\laragon\www\milklife\.env` dengan Notepad. Semua baris bertanda `[PRODUKSI]` harus diubah. Nilai yang benar ada di bagian **PRODUKSI** paling bawah berkas itu beserta alasannya. Ringkasnya, ubah baris yang sudah ada menjadi:

   ```
   CI_ENVIRONMENT = production
   app.baseURL = 'https://<alamat-pendaftaran>/'
   app.forceGlobalSecureRequests = true
   database.default.username = milklife
   database.default.password = '<sandi-basis-data>'
   cookie.secure = true
   logger.threshold = 4
   aplikasi.minify = true
   ```

   `app.baseURL` diawali `https://` dan diakhiri garis miring, misalnya `'https://daftar.contoh.sch.id/'`. Setiap kunci cukup ditulis sekali: ubah barisnya, jangan menambah baris kembar.
3. Pengaturan cadangan (bagian **CADANGAN** di `.env`) diisi di bagian 8 panduan ini.
4. Simpan salinan `.env` di flashdisk atau tempat aman lain. Tanpa berkas ini, pemulihan di komputer baru harus mengisi ulang semua pengaturan.

## 6. Mengisi aplikasi

Jalankan berurutan di terminal Laragon, di folder `C:\laragon\www\milklife`:

1. Buat tabel:

   ```
   php spark migrate --all
   ```

2. Isi daftar sekolah (4.766 sekolah di lima kabupaten):

   ```
   php spark sekolah:impor
   ```

   Perintah ini aman diulang kapan saja. Sekolah yang namanya sudah disunting admin di menu Sekolah tidak ditimpa (K97).

3. Isi data awal dari flyer Seri 2. Hanya **sekali** saat pemasangan; bila data sudah ada, perintah menolak supaya isian admin tidak tertimpa:

   ```
   php spark data:awal
   ```

4. Buat akun admin. Perintah menanyakan nama pengguna, nama, dan kata sandi (minimal 8 karakter). Di terminal Laragon biasa kata sandi terlihat saat diketik; supaya tersembunyi, jalankan dari **Git Bash** (klik kanan di folder proyek → **Git Bash Here**):

   ```
   php spark admin:buat
   ```

   Perintah yang sama dipakai untuk mengganti kata sandi admin; semua sesi akun itu langsung keluar.
5. Buat versi kecil CSS dan JavaScript. Wajib karena `aplikasi.minify = true`, dan wajib diulang setiap memperbarui versi (bagian 10):

   ```
   php spark aset:minify
   ```

6. Buka `http://milklife.test` di peramban server. Karena `app.forceGlobalSecureRequests = true`, peramban langsung dialihkan ke `https://<alamat-pendaftaran>`, yang baru bisa dibuka setelah bagian 7. Pengalihan itu tanda aplikasi sudah berjalan. Halaman depan dan masuk admin diuji di akhir bagian 7.

Bila yang tampil daftar berkas dan folder atau halaman "403 Forbidden", bukan pengalihan, Laragon belum mengarahkan alamat ke folder `public`. Klik kanan Laragon → **Nginx** → **sites-enabled** → buka `auto.milklife.test.conf`, ubah baris `root` supaya diakhiri `/milklife/public/";`, simpan dengan nama baru `milklife.test.conf` (tanpa `auto.` supaya tidak ditimpa Laragon), hapus berkas `auto.` yang lama, lalu klik **Reload**.

## 7. HTTPS lewat Cloudflare Tunnel

Langkah di dasbor Cloudflare. Nama menu di dasbor bisa sedikit berbeda dari yang tertulis di sini karena Cloudflare kadang mengubah tampilannya.

1. Masuk ke dasbor Cloudflare → **Zero Trust** → **Networks** → **Tunnels** → **Create a tunnel**.
2. Pilih jenis **Cloudflared**, beri nama `milklife`, lalu **Save tunnel**.
3. Pilih lingkungan **Windows** dan **64-bit**. Unduh pemasang `cloudflared` dari tautan yang diberikan, lalu pasang.
4. Salin perintah yang diberikan dasbor (bentuknya `cloudflared.exe service install <token-panjang>`). Buka **Command Prompt** sebagai administrator (Start → ketik `cmd` → klik kanan → **Run as administrator**), tempel perintah itu, tekan Enter. `cloudflared` kini terpasang sebagai layanan Windows dan menyala sendiri setiap komputer dinyalakan, tanpa perlu masuk Windows.
5. Kembali ke dasbor; di bawah **Connectors** status tunnel harus **Healthy** atau **Connected**. Klik **Next**.
6. Isi **Public hostname** (atau **Published application route**):
   - **Subdomain**: misalnya `daftar`; **Domain**: domain Anda.
   - **Service Type**: `HTTP`; **URL**: `127.0.0.1:80`.
   - Buka **Additional application settings** → **HTTP Settings** → **HTTP Host Header**, isi `milklife.test`. Dengan ini Nginx tahu permintaan untuk aplikasi ini.
7. Simpan. Alamat `https://<alamat-pendaftaran>` harus menampilkan halaman depan dengan ikon gembok. Buka juga `https://<alamat-pendaftaran>/admin/masuk` dan coba masuk dengan akun admin dari bagian 6.
8. Di dasbor Cloudflare untuk domain itu → **SSL/TLS** → **Edge Certificates**, nyalakan **Always Use HTTPS**. Aplikasi tidak memasang HSTS sendiri; bila diinginkan, nyalakan **HSTS** di halaman yang sama setelah semuanya berjalan baik.

Mengapa `127.0.0.1` dan bukan alamat jaringan server (misalnya `192.168.1.10`): aplikasi hanya memercayai keterangan "pengunjung memakai HTTPS" dan alamat asli pengunjung bila datang dari komputer itu sendiri. Bila `cloudflared` diarahkan ke alamat jaringan, halaman terus memuat ulang dan pembatasan percobaan masuk menganggap semua pengunjung satu orang. Jangan memasang `cloudflared` di komputer lain.

## 8. Cadangan harian otomatis

`php spark cadangan:buat` menulis satu berkas `cadangan-milklife-<tahun-bulan-tanggal-jammenit>.zip` berisi:

- `basisdata.sql`: seluruh isi basis data (pendaftaran, atlet, pengaturan, akun admin);
- `writable/unggahan/`: foto penanggung jawab dan bukti bayar;
- `public/unggahan/`: gambar halaman depan, logo, dan berkas unduhan yang diunggah admin.

Setelah berhasil, cadangan yang berumur 14 hari atau lebih di folder itu dihapus, jadi yang tersimpan adalah 14 cadangan terakhir. Bila gagal, tidak ada cadangan lama yang dihapus. Berkas zip ini berisi data pribadi siswa: jangan dibagikan dan jangan diunggah ke tempat umum.

### Mengisi pengaturan cadangan

1. Buat folder cadangan di luar folder proyek, sebaiknya di diska lain, misalnya `D:\cadangan-milklife`. Perintah menolak folder di dalam `C:\laragon\www\milklife`.
2. Cari letak `mysqldump.exe` dan `php.exe`. Di terminal Laragon ketik:

   ```
   where mysqldump
   where php
   ```

   Catat kedua jalur yang tampil, misalnya `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe` dan `C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe`. Nama folder versinya di komputer Anda bisa berbeda.
3. Di `.env`, bagian **CADANGAN**, ubah barisnya menjadi (dengan tanda petik tunggal):

   ```
   cadangan.folder = 'D:\cadangan-milklife'
   cadangan.simpanHari = 14
   cadangan.mysqldump = '<jalur mysqldump.exe dari langkah 2>'
   ```

4. Coba sekali dari terminal Laragon:

   ```
   php spark cadangan:buat
   ```

   Harus tampil `Cadangan dibuat: D:\cadangan-milklife\cadangan-milklife-….zip`. Bila tampil pesan galat, ikuti petunjuk di pesan itu.

Sandi basis data tidak pernah tampil di baris perintah: perintah menulisnya ke berkas sementara yang langsung dihapus setelah selesai.

### Menjadwalkan setiap malam

1. Klik Start, ketik **Task Scheduler** (Penjadwal Tugas), buka.
2. Di panel kanan klik **Create Basic Task…**.
3. **Name**: `Cadangan MilkLife` → **Next**.
4. **Trigger**: **Daily** → **Next**. **Start**: tanggal hari ini pukul `02:00:00`, **Recur every**: `1` days → **Next**.
5. **Action**: **Start a program** → **Next**.
6. Isi:
   - **Program/script**: jalur `php.exe` dari langkah 2 di atas;
   - **Add arguments**: `spark cadangan:buat`;
   - **Start in**: `C:\laragon\www\milklife`.
7. **Next**, centang **Open the Properties dialog for this task when I click Finish**, lalu **Finish**.
8. Di jendela Properties, tab **General**: biarkan **Run only when user is logged on** (MySQL Laragon hanya menyala saat Anda masuk Windows). Tab **Settings**: centang **Run task as soon as possible after a scheduled start is missed**, supaya cadangan tetap dibuat bila komputer mati pukul 02.00. Klik **OK**.
9. Uji: klik kanan tugas `Cadangan MilkLife` → **Run**. Tunggu beberapa detik, tekan F5. Kolom **Last Run Result** harus **The operation completed successfully. (0x0)** dan berkas zip baru muncul di folder cadangan.

Bila **Last Run Result** menunjukkan `(0x1)`, cadangan gagal. Jalankan `php spark cadangan:buat` di terminal Laragon untuk membaca pesannya, perbaiki, lalu jalankan tugasnya lagi.

### Salinan di luar komputer server

Cadangan di diska yang sama tidak menolong bila komputernya rusak atau hilang. Seminggu sekali, dan setelah pendaftaran ditutup, salin berkas zip terbaru ke flashdisk atau penyimpanan lain yang hanya bisa dibuka panitia.

## 9. Memulihkan cadangan

Lakukan hanya bila data rusak atau terhapus, atau saat pindah ke komputer baru. Pemulihan **mengganti seluruh** isi basis data dan unggahan dengan isi cadangan; pendaftaran yang masuk setelah cadangan itu dibuat akan hilang.

1. Pilih berkas zip yang akan dipulihkan (lihat tanggal dan jam di namanya). Bila masih bisa, buat dulu cadangan keadaan sekarang dengan `php spark cadangan:buat`, supaya bisa kembali bila salah pilih.
2. Klik kanan berkas zip → **Extract All…** → ekstrak ke folder baru, misalnya `C:\pemulihan`. Isinya: `basisdata.sql`, folder `writable`, dan folder `public`.
3. Di terminal Laragon (bukan PowerShell), kosongkan basis data lalu isi dari cadangan. Setiap baris menanyakan sandi `root`:

   ```
   mysql -u root -p -e "DROP DATABASE milklife"
   mysql -u root -p -e "CREATE DATABASE milklife CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p milklife < C:\pemulihan\basisdata.sql
   ```

   Hak pengguna `milklife` tetap ada setelah basis data dibuat ulang.
4. Ganti folder unggahan dengan isi cadangan. Di Windows Explorer:
   - hapus folder `C:\laragon\www\milklife\writable\unggahan`, lalu salin `C:\pemulihan\writable\unggahan` ke `C:\laragon\www\milklife\writable\`;
   - hapus folder `C:\laragon\www\milklife\public\unggahan`, lalu salin `C:\pemulihan\public\unggahan` ke `C:\laragon\www\milklife\public\`.

   Bila salah satu folder tidak ada di cadangan, berarti saat itu belum ada unggahan jenis itu; lewati.
5. Bila kode aplikasi lebih baru dari cadangan, lengkapi tabelnya:

   ```
   php spark migrate --all
   ```

6. Buka panel admin dan periksa: jumlah pendaftaran di dasbor, satu rincian pendaftaran beserta foto penanggung jawab dan bukti bayarnya, dan halaman depan.
7. Hapus folder `C:\pemulihan` setelah selesai, karena isinya data pribadi.

**Pindah ke komputer baru**: ikuti bagian 2 sampai 5 (pakai salinan `.env` lama bila ada), jalankan `php spark migrate --all`, lalu langkah 2–6 di atas. Lewati `sekolah:impor`, `data:awal`, dan `admin:buat`, karena semuanya sudah ada di cadangan. Jalankan `php spark aset:minify`, lalu bagian 7 dan 8. Tunnel yang sudah ada bisa dipakai lagi: copot dulu `cloudflared` di komputer lama (**Apps & features** → `cloudflared` → **Uninstall**), lalu pasang di komputer baru dengan perintah `service install` dari dasbor tunnel itu.

## 10. Memperbarui ke versi baru

Bila ada perbaikan baru di cabang `main` GitHub:

1. Buat cadangan dulu:

   ```
   php spark cadangan:buat
   ```

2. Ambil versi baru, pasang pustakanya, perbarui tabel, dan buat ulang CSS dan JavaScript kecil. Jalankan berurutan di folder `C:\laragon\www\milklife`:

   ```
   git pull
   composer install --no-dev --optimize-autoloader
   php spark migrate --all
   php spark sekolah:impor
   php spark aset:minify
   ```

   `sekolah:impor` memasukkan data sekolah terbaru bila versi baru membawanya. Sekolah yang sudah disunting admin tidak ditimpa, dan tidak ada sekolah yang dihapus.

3. Buka halaman depan dan panel admin, pastikan tampil normal.

Catatan:

- `git pull` tidak menyentuh `.env`, folder unggahan, dan berkas `.min`; ketiganya tidak ada di git.
- Bila `git pull` menolak karena "local changes", ada berkas proyek yang terubah di server. Jangan menyunting berkas proyek di server; hubungi pengembang.
- Admin yang sedang masuk bisa diminta masuk lagi setelah pembaruan tertentu, misalnya pembaruan keamanan sesi. Itu wajar.

## 11. Daftar periksa sebelum membuka pendaftaran

Server dan keamanan:

- [ ] Laragon menyala sendiri setelah komputer dinyalakan ulang dan Anda masuk Windows; Nginx dan MySQL berjalan.
- [ ] Komputer tidak tidur (Sleep: Never), dan jam aktif Windows Update diatur supaya tidak memulai ulang komputer di jam sibuk.
- [ ] Sandi `root` MySQL sudah dibuat, dan aplikasi memakai pengguna `milklife`.
- [ ] `.env`: `CI_ENVIRONMENT = production`, `app.baseURL` berawalan `https://`, `cookie.secure = true`, `aplikasi.minify = true`, dan salinannya tersimpan aman.
- [ ] `https://<alamat-pendaftaran>` terbuka dengan ikon gembok dari ponsel memakai data seluler (bukan Wi-Fi sekolah).
- [ ] `http://<alamat-pendaftaran>` (tanpa s) dialihkan ke `https://`.
- [ ] `https://<alamat-pendaftaran>/halaman-tidak-ada` menampilkan halaman "tidak ditemukan" yang ramah, tanpa tulisan teknis.
- [ ] Hanya akun admin yang diperlukan yang ada, masing-masing dengan kata sandi kuat.

Isi:

- [ ] Dasbor admin tidak lagi menampilkan peringatan pengaturan yang belum lengkap.
- [ ] Halaman depan: acara, jadwal, nomor lomba, biaya, rekening pembayaran, kontak, dan unduhan (juknis) benar.
- [ ] Jadwal buka dan tutup pendaftaran benar.
- [ ] Menu **Sekolah**: sekolah bernama sama dengan sekolah lain (saringan Nama → "Sama dengan sekolah lain") yang diperkirakan ikut sudah diberi nama pembeda, misalnya dengan nama desa (K97). Sisanya bisa diusulkan pendaftar sendiri saat mendaftar (K99).
- [ ] Coba satu pendaftaran dengan data karangan sampai selesai, buka halaman cek dan bukti PDF-nya, lalu hapus pendaftaran itu dari panel. **Jangan** menandainya Lunas: nomor dada yang sudah terpakai tidak pernah dipakai ulang.

Cadangan:

- [ ] `php spark cadangan:buat` berhasil dari terminal, dan berkas zip muncul di folder cadangan.
- [ ] Tugas `Cadangan MilkLife` di Task Scheduler sudah diuji dengan **Run** dan hasilnya `(0x0)`.
- [ ] Tempat salinan di luar komputer server sudah disiapkan.

Selama masa pendaftaran, periksa sesekali bahwa folder cadangan berisi berkas hari ini, dan putuskan usulan nama sekolah dari pendaftar di **Sekolah** → **Usulan nama dari pendaftar** (jumlahnya juga tampil di dasbor).

## Asumsi yang perlu ditinjau

1. Cadangan dijalankan pukul 02.00 setiap hari dan hanya bila ada pengguna yang masuk Windows di komputer server, karena MySQL Laragon menyala saat Laragon dibuka. Bila komputer mati pada jamnya, cadangan dibuat begitu komputer menyala lagi.
2. Panduan meminta sandi untuk pengguna `root` MySQL, selain pengguna khusus `milklife` dari bagian PRODUKSI `.env.example`. Akibatnya setiap perintah `mysql -u root -p` menanyakan sandi.
3. `.env` tidak ikut dalam berkas cadangan (berisi rahasia); pemilik menyimpan salinannya sendiri.
4. Nginx Laragon dipakai dengan virtual host otomatis `milklife.test`; Cloudflare Tunnel mengirim kepala Host `milklife.test` ke `127.0.0.1:80`, jadi berkas pengaturan Nginx tidak perlu disunting.
5. Kode diambil dan diperbarui dengan `git` dari repo publik; tidak ada paket rilis terpisah.
