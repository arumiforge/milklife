# Panduan Pemasangan Produksi

| | |
|---|---|
| Status | **Disetujui pemilik pada 12 November 2026**; **revisi 1 disetujui 14 November 2026** (K100); **revisi 2 16 November 2026** (K109, K112, diubah langsung sesuai K101); **revisi 3 17 November 2026** (K114, diubah langsung sesuai K101); **revisi 4 17 November 2026** (K115, diubah langsung sesuai K101); **revisi 5 17 November 2026** (K122, diubah langsung sesuai K101); **revisi 6 21 November 2026** (K138, diubah langsung sesuai K101); **revisi 7 21 November 2026** (K140 butir 7b, K143, diubah langsung sesuai K101); **revisi 8 21 November 2026** (K140 butir 11, diubah langsung sesuai K101); **revisi 9 22 November 2026** (penutupan Tahap 9, diubah langsung sesuai K101); **revisi 10 22 November 2026** (penutupan Tahap 10, diubah langsung sesuai K101); **revisi 11 22 November 2026** (K174, diubah langsung sesuai K101); **revisi 12 23 November 2026** (penutupan Tahap 11, diubah langsung sesuai K101); **revisi 13 23 November 2026** (K182, ditulis ulang untuk Ubuntu dan Docker, diubah langsung sesuai K101); **revisi 14 24 November 2026** (K184, diubah langsung sesuai K101); **revisi 15 25 November 2026** (K185, diubah langsung sesuai K101); **revisi 16 25 November 2026** (K187, diubah langsung sesuai K101); **revisi 17 25 November 2026** (K189, diubah langsung sesuai K101); **revisi 18 25 November 2026** (K190, diubah langsung sesuai K101) |
| Revisi 18 | Batas unggah PHP di `docker/php.ini` naik menjadi 100 MB (K190), sehingga cadangan panel sampai 100 MB bisa diimpor lewat **Pengaturan → Cadangan**. Bagian yang berubah: 9 |
| Revisi 17 | Perintah `mlk` di server (K189), mengikuti pola `spmb deploy`: image bertag SHA commit dibangun dari `git archive`, `mlk deploy staging` memasangnya setelah cadangan lalu `migrate --all`, `sekolah:impor`, dan cek kesehatan, dan kembali otomatis ke versi sebelumnya bila gagal; `mlk deploy produksi` memakai image staging tanpa membangun ulang; `mlk rollback`, `mlk status`, `mlk pasang` untuk pemasangan pertama; port dicatat di `/opt/apps/PORTS`; singkatan `alias mlk` diganti tautan ke skrip `docker/mlk`. Flyer, banner, dan juknis bawaan ikut masuk image, jadi `data:awal` tidak lagi gagal memasangnya. Bagian yang berubah: 2, 5, 10, asumsi 4 |
| Revisi 16 | Salinan cadangan ke Cloudflare R2 diatur admin di **Pengaturan → Cadangan**, bukan lagi di `docker/.env` (K187): ID akun, bucket, token, dan folder diisi di panel, sambungan diperiksa sebelum disimpan, isi folder di bucket tampil dengan tombol unduh, dan `cadangan:buat` langsung mengirim zip baru ke R2. Layanan `salin-r2` dan variabel `R2_*` dihapus; cron cukup satu perintah. Bagian yang berubah: ringkasan, 7, 11, asumsi 2 |
| Revisi 15 | Pindah dari Laragon dicoba dengan dump dan zip Backup data produksi (K185): zip Backup data ikut diunduh sebagai pembanding dan jalan cadangan bila basis data gagal dimuat, isian `cadangan.folder` bila `cadangan:buat` menolak, pemeriksaan jumlah atlet, dan jalan mundur yang memperbarui kode Laragon dulu. `pembaruan:periksa` kini menyebut `faq:awal`, `halaman-depan:carousel`, dan `galeri:awal` (F11-19). Bagian yang berubah: 8 |
| Revisi 14 | Cloudflare Tunnel memakai cloudflared di host, tunnel yang sama dengan aplikasi lain di VPS, untuk staging dan produksi (K184): container app membuka port hanya di `127.0.0.1:<PORT_LOKAL>`, tunnel diarahkan ke `http://localhost:<PORT_LOKAL>`, dan container cloudflared menjadi pilihan lewat `COMPOSE_PROFILES=tunnel`. Pindah dari Laragon memindahkan nama alamat ke tunnel di host. Bagian yang berubah: ringkasan, 1, 3, 6, 8, 10, 11, asumsi 1 |
| Revisi 13 | Server pindah dari Windows dan Laragon ke VPS Ubuntu 24.04 dengan Docker Compose (K182): PHP 8.5.13 di FrankenPHP, MySQL 8.4, cloudflared di container, cadangan harian lewat cron host yang disalin ke Cloudflare R2, dan langkah pindah dari server Laragon. Panduan ditulis ulang; ringkasan perintah sekali jalan Tahap 9 sampai 11 diringkas di bagian 10. Bagian yang berubah: semua |
| Revisi 12 | Penutupan Tahap 11 (F11-08): ringkasan perintah sekali jalan pembaruan Tahap 11 (`cadangan:buat`, `migrate --all`, `data:sesuaikan`, `aset:minify`, `pembaruan:periksa`), langkah tanya-jawab baru di `data:sesuaikan`, pengaturan yang diperiksa admin setelah pembaruan (susunan klasemen, pelaksanaan nomor lomba, nomor acara, sakelar Rekor dan Videotron), dan alamat `/layar` untuk operator videotron vendor (K172–K176). Bagian yang berubah: 10 |
| Revisi 11 | Cadangan di panel admin (F11-11, K174): menu Pengaturan > Cadangan menggantikan tombol Backup data, cadangannya berisi semua data, pengaturan, dan berkas unggahan; impor lewat panel mengganti semua setelah cadangan otomatis di `writable\cadangan`; perintah `php spark cadangan:pulihkan` untuk berkas yang terlalu besar diunggah; batas unggah PHP untuk impor. Bagian yang berubah: 8, 9, 10 |
| Revisi 10 | Penutupan Tahap 10 (F10-11): ringkasan perintah sekali jalan pembaruan Tahap 10 berurutan (`migrate --all`, `faq:awal`, `tabel-skor:impor --trilomba`, `halaman-depan:carousel`, `galeri:awal`, `data:sesuaikan`, `aset:minify`, lalu `pendaftaran:impor` bila perlu, K157 butir 4), dan catatan bahwa tanya-jawab serta pesan WA yang sudah tersimpan di server tidak berubah otomatis, disunting admin di Pengaturan → Bantuan dan Pengaturan → Halaman depan (K166 butir 3). Bagian yang berubah: 10 |
| Revisi 9 | Penutupan Tahap 9 (F9-18): daftar lengkap perintah sekali jalan pembaruan Tahap 9 (`migrate --all`, `faq:awal`, `tabel-skor:impor --trilomba`, `halaman-depan:carousel`, `galeri:awal`, `aset:minify`), sakelar fitur publik baru yang bawaan mati, dan server tetap tanpa Node. Bagian yang berubah: 10 |
| Revisi 8 | Pembaruan yang membawa Galeri (F9-06): perintah sekali jalan `php spark galeri:awal` memasang album bawaan "Seri sebelumnya" tanpa menimpa album admin; halaman Galeri tetap tersembunyi sampai dinyalakan di Pengaturan > Galeri (K140 butir 11). Bagian yang berubah: 10 |
| Revisi 7 | Pembaruan yang membawa kalkulator poin multi-lomba (F9-10): perintah sekali jalan `php spark tabel-skor:impor --trilomba` memasang tabel skor bawaan dan memindah nomor Trilomba yang belum punya prestasi ke poin multi-lomba; `pembaruan:periksa` ikut memeriksanya (K140 butir 7b, K143). Bagian yang berubah: 10 |
| Revisi 6 | Repo menjadi privat (K138): komputer server memakai deploy key (kunci SSH baca saja) untuk `git clone` dan `git pull`, termasuk server yang sudah terpasang. Bagian yang berubah: 3, 10, asumsi 5 |
| Revisi 5 | Pemeriksaan keamanan Cloudflare Turnstile di kirim pendaftaran, Cek Pendaftaran, dan masuk admin: memasang kunci rahasia dari dasbor Cloudflare, daftar nama host, dan cara mematikan sementara (K122). Bagian yang berubah: 5, 7, 10, 11 |
| Revisi 4 | Perintah `php spark pembaruan:periksa` untuk memeriksa perintah yang masih perlu dijalankan setelah `git pull` (K115). Bagian yang berubah: 10 |
| Revisi 3 | Pemeriksaan menu Sekolah menyebut tombol Tambahkan nama desa untuk sekolah bernama sama (K114). Bagian yang berubah: 11 |
| Revisi 2 | Perintah sekali jalan `php spark pendaftaran:gabung` saat memasang pembaruan penggabungan pendaftaran (K109); pemeriksaan KU 12, juknis, dan jadwal pertemuan teknik (technical meeting) setelah pembaruan (K112). Bagian yang berubah: 10 |
| Revisi 1 | Data sekolah 4.766 sekolah dari Sekolah Kita, impor sekolah ikut dijalankan saat memperbarui versi, dan pemeriksaan menu Sekolah sebelum dan selama masa pendaftaran (K97, K99). Bagian yang berubah: 6, 10, 11 |
| Tanggal | 12 November 2026, revisi 14, 16, 17, 21, 22, dan 23 November 2026 (revisi 3 sampai 13), revisi 14 24 November 2026, revisi 15 dan 16 25 November 2026 |
| Dasar | Rancangan bagian 15; rencana implementasi Tahap 7 bagian "Cadangan" dan "Panduan pemasangan produksi" (K69); Docker di VPS Ubuntu 24.04 (K182, menggantikan Laragon untuk produksi K08); Cloudflare Tunnel (K31, K184); cadangan harian 14 hari (K66); bagian PRODUKSI dan CADANGAN di `.env.example`; `docker/compose.yaml` dan `docker/.env.example` |

## Tujuan

Untuk pengembang atau admin server yang memasang web pendaftaran di VPS. Setelah mengikuti panduan ini langkah demi langkah, web bisa dibuka dari internet lewat alamat `https://`, data tercadang otomatis setiap malam, dan Anda tahu cara memulihkan cadangan, memasang versi baru, serta memindahkan web dari server Laragon lama.

Panduan ini ditulis untuk **Ubuntu 24.04 dengan Docker** (K182). Semua perintah diketik di terminal SSH (misalnya lewat Bitvise) sebagai pengguna admin non-root, kecuali bila disebut lain. Ganti teks di dalam tanda `<…>` dengan isian Anda sendiri, tanpa tanda `<` dan `>`.

## Ringkasan susunan

- Setiap lingkungan (produksi dan staging) punya folder kode sendiri di server, misalnya `/srv/milklife-produksi` dan `/srv/milklife-staging`, dan berjalan sebagai satu proyek Docker Compose dari `docker/compose.yaml`. Keduanya boleh di VPS yang sama, berdampingan dengan aplikasi Docker lain.
- Satu proyek berisi dua container: **app** (FrankenPHP dengan PHP 8.5.13 dan OPcache) dan **db** (MySQL 8.4).
- Pengunjung membuka `https://<alamat-pendaftaran>`, Cloudflare memasang HTTPS, lalu meneruskan permintaan lewat **cloudflared yang berjalan di host**, yaitu tunnel yang juga dipakai aplikasi lain di VPS (K184), ke `http://localhost:<PORT_LOKAL>`. Container app hanya membuka port itu di `127.0.0.1`, jadi tidak bisa dijangkau dari jaringan luar. Port yang terbuka di VPS hanya SSH.
- Bila VPS belum punya cloudflared di host, tunnel bisa dijalankan sebagai container ketiga di proyek ini (bagian 6, "Tunnel sebagai container").
- Data yang harus bertahan ada di tiga volume Docker (`writable`, `unggahan`, `mysql`) dan satu folder cadangan di host. Kode ada di dalam image, yang dibangun ulang setiap memperbarui versi.
- Setiap malam cron di host menjalankan `php spark cadangan:buat` di container app, yang menulis satu berkas zip cadangan ke folder cadangan dan menghapus cadangan yang berumur 14 hari atau lebih. Bila salinan Cloudflare R2 sudah diatur admin di **Pengaturan → Cadangan**, perintah yang sama mengirim zip baru ke bucket R2 panitia (K187).

## 1. Yang perlu disiapkan

- VPS Ubuntu 24.04 dengan Docker Engine dan plugin Compose (`docker compose version` menampilkan versi 2). Minimal 2 GB RAM dan 20 GB ruang kosong untuk satu lingkungan.
- Masuk SSH hanya dengan kunci publik (Bitvise), lewat pengguna admin non-root yang bisa `sudo`. Di `/etc/ssh/sshd_config`: `PasswordAuthentication no` dan `PermitRootLogin no`. Coba masuk dengan kunci di jendela kedua sebelum menutup sesi yang sedang terbuka.
- Firewall UFW hanya membuka SSH. Docker melewati aturan UFW untuk port yang dibuka container, karena itu `docker/compose.yaml` sengaja tidak membuka port apa pun.
- Pengguna admin masuk grup `docker` (`sudo usermod -aG docker <pengguna>`, lalu keluar dan masuk lagi). Anggota grup `docker` sama kuatnya dengan root, jadi jangan masukkan pengguna lain.
- Akun Cloudflare yang mengelola domainnya, misalnya `mac2026.web.id`, dan nama alamat yang dipakai. cloudflared di host (`systemctl is-active cloudflared` menampilkan `active`) memakai tunnel di akun yang sama.

## 2. Menyalin aplikasi

1. Buat folder dan deploy key (kunci baca saja khusus server ini, K138, K148). Tekan Enter di setiap pertanyaan `ssh-keygen`, tanpa kata sandi:

   ```
   sudo mkdir -p /srv/milklife-produksi /srv/cadangan/milklife-produksi
   sudo chown $USER:$USER /srv/milklife-produksi
   sudo chown 33:33 /srv/cadangan/milklife-produksi
   sudo chmod 700 /srv/cadangan/milklife-produksi
   ssh-keygen -t ed25519 -C "milklife-produksi" -f ~/.ssh/milklife-produksi
   cat ~/.ssh/milklife-produksi.pub
   ```

   Uid `33` adalah pengguna `www-data` di dalam container app, yang menulis berkas cadangan.

   Pengembang menambahkan baris yang tampil (diawali `ssh-ed25519`) di GitHub → repo `arumiforge/milklife` → **Settings** → **Deploy keys** → **Add deploy key**, dengan judul `Server produksi` dan **tanpa** *Allow write access*. Staging memakai kunci lain dengan judul `Server staging`.
2. Beri tahu Git kunci mana yang dipakai, lalu ambil kodenya:

   ```
   printf 'Host github-milklife-produksi\n  HostName github.com\n  User git\n  IdentityFile ~/.ssh/milklife-produksi\n  IdentitiesOnly yes\n' >> ~/.ssh/config
   git clone github-milklife-produksi:arumiforge/milklife.git /srv/milklife-produksi
   cd /srv/milklife-produksi
   ```

   Jawab `yes` bila ditanya keaslian github.com. Kunci `~/.ssh/milklife-produksi` (tanpa `.pub`) adalah rahasia: jangan dikirim ke siapa pun dan jangan disalin ke folder proyek.
3. Pasang perintah `mlk` (skrip `docker/mlk` di repo) dan singkatan `spark`:

   ```
   sudo ln -sf /srv/milklife-produksi/docker/mlk /usr/local/bin/mlk
   sed -i '/alias mlk=/d; /alias spark=/d' ~/.bashrc
   echo "alias spark='mlk spark'" >> ~/.bashrc
   unalias mlk 2>/dev/null; source ~/.bashrc
   ```

   Di dalam folder kode, `mlk` meneruskan perintah ke Docker Compose (misalnya `mlk ps`), dan `spark` menjalankan perintah aplikasi di container app (misalnya `spark migrate --all`). Dari folder mana pun, sebutkan lingkungannya: `mlk produksi ps`, `mlk staging spark pembaruan:periksa`. `mlk` mencari folder lingkungan di `/opt/apps/milklife/<lingkungan>`, lalu `/srv/milklife-<lingkungan>`. `mlk deploy` (bagian 10) dan `mlk pasang` (bagian 5) menjalankan beberapa langkah sekaligus. Selanjutnya setiap perintah di panduan ini dijalankan dari `/srv/milklife-produksi`.

## 3. Pengaturan Docker (`docker/.env`)

`docker/.env` berisi pengaturan dan rahasia container untuk lingkungan ini. Berkas ini tidak pernah masuk git.

```
cp docker/.env.example docker/.env
chmod 600 docker/.env
nano docker/.env
```

Isi setiap baris bertanda `[ISI]` dan periksa yang lain:

- `COMPOSE_PROJECT_NAME`: `milklife-produksi` (staging: `milklife-staging`).
- `SUBNET`: rentang alamat jaringan internal yang belum dipakai jaringan Docker lain di VPS. Lihat yang sudah dipakai dengan `docker network inspect $(docker network ls -q) | grep Subnet`. Contoh: produksi `10.201.0.0/24`, staging `10.201.1.0/24`.
- `DB_SANDI` dan `DB_SANDI_ROOT`: dua sandi panjang buatan sendiri, huruf dan angka saja, berbeda dari staging.
- `FOLDER_CADANGAN`: `/srv/cadangan/milklife-produksi` dari bagian 2.
- `PORT_LOKAL`: port di `127.0.0.1` host yang belum dipakai (cek dengan `ss -tln`), berbeda untuk setiap lingkungan. Contoh: staging `8102`, produksi `8103`.
- `COMPOSE_PROFILES` dan `TUNNEL_TOKEN`: dibiarkan kosong bila memakai cloudflared di host (bagian 6).

Basis data dan penggunanya dibuat otomatis saat container db pertama kali menyala. Mengubah sandi di `docker/.env` setelah itu tidak mengganti sandi yang sudah ada.

## 4. Berkas `.env` aplikasi

`.env` di folder kode berisi pengaturan aplikasi. Berkas ini juga tidak pernah masuk git dan tidak boleh dikirim ke siapa pun.

1. Salin contoh pengaturan dan buka:

   ```
   cp .env.example .env
   chmod 644 .env
   nano .env
   ```

   Untuk staging, salin `.env.staging.example`, bukan `.env.example`.
2. Semua baris bertanda `[PRODUKSI]` harus diubah. Nilai yang benar ada di bagian **PRODUKSI** paling bawah berkas itu beserta alasannya. Ringkasnya, ubah baris yang sudah ada menjadi:

   ```
   CI_ENVIRONMENT = production
   app.baseURL = 'https://<alamat-pendaftaran>/'
   app.forceGlobalSecureRequests = true
   database.default.hostname = db
   database.default.database = milklife
   database.default.username = milklife
   database.default.password = '<DB_SANDI dari docker/.env>'
   cookie.secure = true
   logger.threshold = 4
   aplikasi.minify = true
   cadangan.folder = '/cadangan'
   cadangan.mysqldump = mysqldump
   ```

   `app.baseURL` diawali `https://` dan diakhiri garis miring, misalnya `'https://mac2026.web.id/'`. Nama basis data, pengguna, dan sandinya **harus sama** dengan `DB_NAMA`, `DB_PENGGUNA`, dan `DB_SANDI` di `docker/.env`. Setiap kunci cukup ditulis sekali: ubah barisnya, jangan menambah baris kembar.
3. Kunci rahasia Cloudflare Turnstile (bagian **CLOUDFLARE TURNSTILE**) diisi di akhir bagian 6.
4. Simpan salinan `.env` dan `docker/.env` di tempat aman di luar server. Tanpa keduanya, pemulihan di server baru harus mengisi ulang semua pengaturan.

`.env` dipasang ke container dalam mode baca saja. Perubahan `.env` langsung berlaku di permintaan berikutnya, tanpa perlu menyalakan ulang container.

## 5. Menyalakan dan mengisi aplikasi

Pada pemasangan baru, langkah 1 sampai 5 di bawah bisa dijalankan sekaligus dengan `mlk pasang produksi` (atau `mlk pasang staging`). Perintah itu memeriksa `DB_SANDI` sama dengan sandi di `.env`, mencatat port di `/opt/apps/PORTS`, membangun image dari commit folder kode, menyalakan container, mengisi `encryption.key` bila masih kosong, menjalankan `migrate --all` dan `sekolah:impor`, menjalankan `data:awal` hanya bila basis data belum berisi kelompok umur, menanyakan akun admin, lalu cek kesehatan dan `pembaruan:periksa`. **Jangan** dipakai saat pindah dari server lama: kunci dan data diambil dari server lama (bagian 8).

1. Bangun image dan nyalakan app serta basis data. Pembangunan pertama makan beberapa menit:

   ```
   mlk build app
   mlk up -d app db
   mlk ps
   ```

   Container `db` harus `healthy` dan `app` harus `Up`. Image sudah berisi pustaka PHP dan CSS/JavaScript kecil, jadi `composer install` dan `aset:minify` tidak perlu dijalankan sendiri.
2. Buat kunci rahasia aplikasi, lalu tempel hasilnya di baris `encryption.key` di `.env`:

   ```
   spark key:generate --show
   ```

   Saat pindah dari server lama, pakai kunci dari `.env` lama (bagian 8), jangan membuat kunci baru.
3. Buat tabel dan isi data. Langkah ini dilewati saat pindah dari server lama, karena semuanya sudah ada di cadangan (bagian 8):

   ```
   spark migrate --all
   spark sekolah:impor
   spark data:awal
   ```

   `sekolah:impor` mengisi 4.766 sekolah dan aman diulang kapan saja; sekolah yang sudah disunting admin tidak ditimpa (K97). `data:awal` hanya **sekali** saat pemasangan; bila data sudah ada, perintah menolak supaya isian admin tidak tertimpa.
4. Buat akun admin. Perintah menanyakan nama pengguna, nama, dan kata sandi (minimal 8 karakter):

   ```
   spark admin:buat
   ```

   Perintah yang sama dipakai untuk mengganti kata sandi admin; semua sesi akun itu langsung keluar.
5. Periksa bahwa tidak ada yang terlewat:

   ```
   spark pembaruan:periksa
   ```

   Semua baris harus `[beres]`.

## 6. HTTPS lewat Cloudflare Tunnel

cloudflared yang sudah berjalan di host VPS meneruskan alamat pendaftaran ke port lokal app (K184). Langkah di dasbor Cloudflare; nama menu bisa sedikit berbeda karena Cloudflare kadang mengubah tampilannya.

1. Pastikan app menjawab di port lokalnya. Tanpa kepala HTTPS hasilnya `307` (dialihkan ke `https://`), dengan kepala HTTPS hasilnya `200`:

   ```
   curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:<PORT_LOKAL>/admin/masuk
   curl -s -o /dev/null -w '%{http_code}\n' -H 'X-Forwarded-Proto: https' http://127.0.0.1:<PORT_LOKAL>/admin/masuk
   ```

   Bila yang kedua juga `307`, aplikasi tidak memercayai alamat asal permintaan: periksa `SUBNET` di `docker/.env`.
2. Cari tahu cara tunnel di host diatur:

   ```
   systemctl cat cloudflared | grep ExecStart | sed 's/--token [^ ]*/--token [disembunyikan]/'
   ls /etc/cloudflared 2>/dev/null
   ```

   Bila baris `ExecStart` berisi `--token`, tunnel diatur dari dasbor (langkah 3). Bila ada `/etc/cloudflared/config.yml` berisi `ingress:`, tunnel diatur dari berkas itu (langkah 4).
3. **Diatur dari dasbor.** Dasbor Cloudflare → **Zero Trust** → **Networks** → **Tunnels** → tunnel yang menyala di VPS → **Public Hostname** (atau **Published application routes**) → **Add**:
   - **Subdomain** dan **Domain**: alamat pendaftaran, misalnya domain `mac2026.web.id` tanpa subdomain, atau subdomain `staging`.
   - **Service Type**: `HTTP`; **URL**: `localhost:<PORT_LOKAL>`.
   - Kosongkan **HTTP Host Header** di **Additional application settings**.

   Perubahan langsung berlaku tanpa menyalakan ulang cloudflared, dan alamat aplikasi lain di tunnel yang sama tidak terganggu. Bila dasbor menolak karena catatan DNS untuk alamat itu sudah ada, hapus dulu catatan `CNAME` alamat itu di **DNS** → **Records**.
4. **Diatur dari berkas.** Tambahkan satu baris layanan di `/etc/cloudflared/config.yml`, **di atas** baris terakhir `- service: http_status:404`:

   ```
     - hostname: <alamat-pendaftaran>
       service: http://localhost:<PORT_LOKAL>
   ```

   Lalu daftarkan alamatnya dan nyalakan ulang cloudflared (aplikasi lain di tunnel itu terputus beberapa detik):

   ```
   sudo cloudflared tunnel route dns <nama-tunnel> <alamat-pendaftaran>
   sudo systemctl restart cloudflared
   ```

5. Alamat `https://<alamat-pendaftaran>` harus menampilkan halaman depan dengan ikon gembok. Buka juga `https://<alamat-pendaftaran>/admin/masuk` dan coba masuk dengan akun admin.
6. Di dasbor Cloudflare untuk domain itu → **SSL/TLS** → **Edge Certificates**, nyalakan **Always Use HTTPS**. Aplikasi tidak memasang HSTS sendiri; bila diinginkan, nyalakan **HSTS** di halaman yang sama setelah semuanya berjalan baik.

Mengapa `localhost:<PORT_LOKAL>`: Docker meneruskan permintaan dari port itu ke container app dari alamat gateway jaringan proyek, yang ada di dalam `SUBNET`. Aplikasi hanya memercayai keterangan "pengunjung memakai HTTPS" serta alamat asli pengunjung (`CF-Connecting-IP`) bila datang dari subnet itu (K31, K182). Port itu hanya terbuka di `127.0.0.1`, jadi hanya program di VPS sendiri yang bisa memakainya.

### Tunnel sebagai container

Untuk VPS yang belum punya cloudflared di host, tunnel bisa dijalankan sebagai container proyek ini:

1. Dasbor Cloudflare → **Zero Trust** → **Networks** → **Tunnels** → **Create a tunnel** → **Cloudflared** → nama, misalnya `milklife-produksi` → lingkungan **Docker**. Salin bagian setelah `--token` dari perintah yang tampil (jangan jalankan perintahnya).
2. Di `docker/.env`: `COMPOSE_PROFILES=tunnel` dan `TUNNEL_TOKEN=<token>`. Lalu `mlk up -d` dan `mlk logs --tail 20 cloudflared`; di log harus ada `Registered tunnel connection`.
3. **Public Hostname** tunnel itu: **Service Type** `HTTP`, **URL** `app:8080`, **HTTP Host Header** kosong.

### Pemeriksaan keamanan Cloudflare Turnstile

Turnstile adalah kotak pemeriksaan "bukan robot" dari Cloudflare (K122). Kotak ini tampil di tiga tempat: sebelum **Kirim Pendaftaran** di langkah 5 pendaftaran, di formulir **Cek Pendaftaran**, dan di halaman **Masuk Admin**. Biasanya kotak ini mencentang sendiri dalam beberapa detik. Tujuannya menahan program otomatis yang mengirim pendaftaran palsu, menebak NPSN di Cek Pendaftaran, atau menebak kata sandi admin.

Turnstile hanya menyala bila **kunci situs dan kunci rahasia sama-sama terisi** di `.env`. Kunci situs sudah terisi dari `.env.example` (`0x4AAAAAAGFuKUT0k64YcOX5`, publik). Kunci rahasia tidak pernah ditulis di git atau di pesan obrolan; Anda sendiri yang menyalinnya dari dasbor Cloudflare ke `.env` server. Selama kunci rahasia kosong, web berjalan seperti biasa tanpa pemeriksaan ini.

1. Masuk ke dasbor Cloudflare → menu **Turnstile** (di beberapa tampilan di bawah **Application security**). Pilih widget yang kunci situsnya `0x4AAAAAAGFuKUT0k64YcOX5`.
2. Buka **Settings** (atau **Manage widget**) widget itu:
   - **Hostname Management**: pastikan alamat pendaftaran ada di daftar. Untuk server produksi cukup alamat itu; `localhost` tidak perlu.
   - **Widget Mode**: biarkan **Managed**.
3. Salin **Secret Key**, lalu di `.env` bagian **CLOUDFLARE TURNSTILE** ubah dua baris ini:

   ```
   turnstile.kunciRahasia = <kunci-rahasia-dari-dasbor>
   turnstile.namaHost = '<alamat-pendaftaran>'
   ```

   `turnstile.namaHost` berisi nama alamat tanpa `https://` dan tanpa garis miring. Bila lebih dari satu alamat, pisahkan dengan koma. Di server produksi **jangan** tulis `localhost` atau `127.0.0.1`. Bila dikosongkan, aplikasi memakai nama alamat dari `app.baseURL`.
4. Simpan `.env`. Buka `https://<alamat-pendaftaran>/admin/masuk` dari ponsel: kotak pemeriksaan keamanan tampil di atas tombol **Masuk** dan tercentang. Masuk dengan akun admin untuk memastikan kunci rahasianya benar. Bila muncul "Pemeriksaan keamanan gagal", periksa lagi kunci rahasia (tanpa spasi tambahan) dan `turnstile.namaHost`.

**Mematikan sementara** (misalnya Cloudflare sedang bermasalah dan kotak pemeriksaan tidak muncul): kosongkan nilai kunci rahasia di `.env` menjadi `turnstile.kunciRahasia =`, lalu simpan. Sejak permintaan berikutnya web berjalan tanpa Turnstile. Isi lagi setelah Cloudflare normal; kunci rahasia bisa disalin ulang kapan saja dari dasbor. Kunci rahasia yang bocor diganti dari dasbor yang sama dengan **Rotate Secret Key**, lalu nilai baru diisi di `.env`.

## 7. Cadangan harian otomatis

`spark cadangan:buat` menulis satu berkas `cadangan-milklife-<tahun-bulan-tanggal-jammenit>.zip` di folder cadangan host berisi:

- `basisdata.sql`: seluruh isi basis data (pendaftaran, atlet, pengaturan, akun admin), dibuat dengan klien MySQL 8.4 di dalam image;
- `writable/unggahan/`: foto ofisial dan bukti bayar;
- `public/unggahan/`: gambar halaman depan, logo, galeri, dan berkas unduhan yang diunggah admin.

Setelah berhasil, cadangan yang berumur 14 hari atau lebih di folder itu dihapus. Bila gagal, tidak ada cadangan lama yang dihapus. Berkas zip ini berisi data pribadi siswa: jangan dibagikan dan jangan diunggah ke tempat umum. Sandi basis data tidak pernah tampil di baris perintah.

1. Coba sekali:

   ```
   spark cadangan:buat
   ls -lh /srv/cadangan/milklife-produksi
   ```

   Harus tampil `Cadangan dibuat: /cadangan/cadangan-milklife-….zip`, dan berkasnya ada di folder host. Bila salinan R2 sudah diatur (di bawah), baris berikutnya berbunyi `Dikirim ke Cloudflare R2: <bucket>/<folder>/cadangan-milklife-….zip.` Bila tampil pesan galat, ikuti petunjuk di pesan itu. Bila zip sudah dibuat tetapi kiriman ke R2 gagal, perintah berakhir dengan kode 1 supaya tercatat di log, dan zip tetap ada di server.
2. Jadwalkan setiap pukul 02.00 dengan cron di host:

   ```
   echo '0 2 * * * root cd /srv/milklife-produksi && docker compose -f docker/compose.yaml exec -T app php spark cadangan:buat >> /var/log/milklife-cadangan.log 2>&1' | sudo tee /etc/cron.d/milklife-produksi
   ```

   Keesokan harinya, periksa bahwa ada berkas baru di folder cadangan dan di daftar isi bucket di **Pengaturan → Cadangan**. Bila tidak ada, baca `/var/log/milklife-cadangan.log`. Staging tidak perlu dijadwalkan.

### Salinan di luar server (Cloudflare R2)

Cadangan di VPS yang sama tidak menolong bila VPS-nya rusak atau hilang. Karena itu setiap cadangan harian juga dikirim ke bucket Cloudflare R2 oleh `cadangan:buat` sendiri (K187). Pengaturannya diisi admin di panel web, bukan di berkas server, dan web tidak pernah menghapus apa pun di bucket.

Di dasbor Cloudflare (sekali saja):

1. **R2** → bucket untuk cadangan. Bila belum ada, **Create bucket**. Di **Settings** bucket itu, pastikan **Public access** mati dan tidak ada domain publik.
2. Masih di **Settings** → **Object lifecycle rules** → **Add rule**: hapus objek setelah, misalnya, 90 hari. Tanpa aturan ini, semua cadangan tetap tersimpan di bucket. Token khusus bucket di langkah 3 tidak bisa mengubah aturan ini, jadi aturan dipasang di dasbor.
3. **R2** → **Manage API tokens** → **Create API token** (Account API token), izin **Object Read & Write**, **Apply to specific buckets only** dengan bucket itu saja. Setelah **Create**, salin **Access Key ID** dan **Secret Access Key**; kunci rahasia hanya tampil sekali. Bila token yang sudah ada punya izin lebih luas (semua bucket atau Admin), buat token baru khusus MilkLife.
4. Catat **ID akun** (Account ID) di halaman R2, atau salin alamat **S3 API** bucket (`https://<id-akun>.r2.cloudflarestorage.com/...`).

Di panel admin produksi, **Pengaturan → Cadangan → Salinan di Cloudflare R2 → Atur salinan R2**:

1. Isi **ID akun Cloudflare** (atau tempel alamat S3 API), **Nama bucket**, **Access Key ID**, dan **Secret Access Key**. **Folder di bucket** bawaannya nama alamat web, misalnya `mac2026.web.id`, jadi staging dan produksi bisa memakai bucket yang sama tanpa tercampur.
2. Ketik kata sandi Anda, lalu tekan **Periksa dan simpan**. Web menulis berkas kecil `uji-sambungan.txt` ke folder itu dan membaca isinya. Pengaturan hanya disimpan bila keduanya berhasil; bila gagal, pesannya menyebut yang perlu diperbaiki (kunci token salah, bucket tidak ada, atau token tidak punya izin untuk bucket itu).
3. Jalankan `spark cadangan:buat` sekali di server, lalu muat ulang halaman. Daftar **Isi folder** harus menampilkan zip yang baru dibuat. Tombol **Unduh** di daftar itu mengunduh langsung dari R2 dan berlaku 10 menit.

Secret Access Key disimpan terenkripsi dengan `encryption.key` di `.env` dan tidak pernah ditampilkan lagi. Pengaturan R2 tidak ikut cadangan dari panel dan tidak berubah saat cadangan diimpor. Bila `encryption.key` berganti, isi ulang Secret Access Key. **Periksa sambungan** menguji pengaturan tersimpan kapan saja, dan **Kiriman terakhir** menunjukkan hasil kiriman malam sebelumnya. **Matikan salinan R2** menghapus pengaturan dari web tanpa menghapus berkas di bucket.

Berkas di R2 berisi data pribadi siswa seperti berkas aslinya. Hanya panitia yang memegang akun Cloudflare atau akun admin web yang boleh mengunduhnya. Setelah lomba selesai dan data tidak diperlukan lagi, hapus folder itu di bucket.

### Cadangan dari panel admin

Selain cadangan harian, admin bisa mengunduh cadangan kapan saja dari **Pengaturan → Cadangan → Unduh cadangan** (K174). Berkas `Cadangan_MilkLife_<tanggal>_<jam>.zip` berisi semua data, semua pengaturan, dan semua berkas unggahan; akun admin tidak ikut. Berkas yang sama bisa dimasukkan ke server baru yang masih kosong dengan `spark pendaftaran:impor`, atau dipulihkan seluruhnya (bagian 9).

## 8. Pindah dari server Laragon

Langkah ini dijalankan sekali, saat produksi pindah dari komputer Windows dengan Laragon ke VPS (K182). Kerjakan dulu bagian 1 sampai 5 di VPS (lewati langkah 3 di bagian 5) dan coba seluruhnya di staging. Pilih waktu sepi, misalnya malam hari. Web dimatikan dulu sebelum cadangan dibuat, jadi tidak ada pendaftaran yang tertinggal di server lama. Perkiraan jeda 15–30 menit.

1. **Di server Laragon**, matikan web dengan menghentikan cloudflared. Buka Command Prompt sebagai administrator:

   ```
   sc stop cloudflared
   sc config cloudflared start= demand
   ```

   Baris kedua mencegah cloudflared Laragon menyala lagi sendiri saat komputer dinyalakan ulang. Lalu buat cadangan lengkap di terminal Laragon. Server Laragon tidak perlu diperbarui lebih dulu; `spark migrate --all` di VPS melengkapi tabelnya:

   ```
   php spark cadangan:buat
   ```

   Bila perintah menolak karena `cadangan.folder` belum diisi, buat foldernya (misalnya `D:\cadangan-milklife`), isi `cadangan.folder` dengan folder itu dan `cadangan.mysqldump` dengan jalur `mysqldump.exe` di `C:\laragon\bin\mysql\…\bin\` di `.env` Laragon, lalu ulangi.

   Unduh juga zip **Backup data** dari menu Pendaftaran di panel admin lama, dibuka di komputer Laragon (`http://milklife.test/admin`). Zip ini hanya berisi pendaftaran dan berkasnya, tanpa pengaturan; gunanya sebagai pembanding dan jalan cadangan bila basis data gagal dimuat (K185). Catat juga jumlah pendaftaran dan atlet di dasbor lama.

   Salin juga nilai `encryption.key`, `turnstile.kunciRahasia`, dan `arumi.sandiHash` (bila ada) dari `.env` lama ke `.env` baru di VPS. `encryption.key` harus sama supaya hitungan pengunjung unik tetap bersambung.
2. Kirim berkas zip cadangan dan zip Backup data ke VPS lewat jendela SFTP Bitvise, misalnya ke `/srv/pindahan/`, lalu ekstrak:

   ```
   sudo apt-get install -y unzip
   cd /srv/pindahan && unzip cadangan-milklife-<tanggal>.zip -d isi
   cd /srv/milklife-produksi
   ```

3. Isi basis data baru dari cadangan. Sandi root diambil dari `DB_SANDI_ROOT` di `docker/.env`, jadi tidak perlu diketik:

   ```
   mlk exec -T db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" milklife' < /srv/pindahan/isi/basisdata.sql
   ```

4. Salin unggahan ke volume container app, lalu serahkan kepemilikannya ke `www-data`:

   ```
   mlk cp /srv/pindahan/isi/writable/unggahan/. app:/app/writable/unggahan/
   mlk cp /srv/pindahan/isi/public/unggahan/. app:/app/public/unggahan/
   mlk exec -u root app chown -R www-data:www-data /app/writable/unggahan /app/public/unggahan
   ```

   Bila salah satu folder tidak ada di cadangan, lewati barisnya.
5. Lengkapi tabel dan periksa:

   ```
   spark migrate --all
   spark pembaruan:periksa
   ```

   Jalankan perintah yang diminta `pembaruan:periksa` sampai tertulis "Tidak ada perintah lain yang perlu dijalankan". Dari server Laragon versi lama, biasanya yang diminta `tabel-skor:impor --trilomba`, `faq:awal`, `halaman-depan:carousel`, dan `galeri:awal`.
6. **Pindahkan alamat ke tunnel VPS.** Di dasbor Cloudflare → **Zero Trust** → **Networks** → **Tunnels**, buka tunnel Laragon → **Public Hostname**, lalu hapus baris alamat pendaftaran. Setelah itu tambahkan alamat itu di tunnel yang menyala di VPS, mengikuti bagian 6 langkah 3 atau 4 (`localhost:<PORT_LOKAL>`). Bila dasbor menolak karena catatan DNS sudah ada, hapus dulu catatan `CNAME` alamat itu di **DNS** → **Records**.
7. Buka `https://<alamat-pendaftaran>` dan panel admin dari ponsel: jumlah pendaftaran dan atlet di dasbor (sama dengan catatan di langkah 1), satu rincian pendaftaran beserta foto ofisial dan bukti bayarnya, dan halaman depan harus sama dengan server lama. Jadwalkan cadangan harian (bagian 7).
8. Biarkan server Laragon utuh, tetapi cloudflared-nya tetap mati, sampai lomba selesai sebagai jalan mundur. Hapus `/srv/pindahan` di VPS setelah semuanya benar, karena isinya data pribadi.

**Bila basis data cadangan gagal dimuat** di langkah 3, pakai zip Backup data (K185). Kosongkan basis data dengan baris `DROP DATABASE` di bagian 9 langkah 2, lalu kerjakan bagian 5 langkah 3 (`migrate --all`, `sekolah:impor`, `data:awal`). Samakan pengaturan dengan server lama lewat panel admin, karena zip tidak membawanya: batas tahun lahir dan biaya tiap kelompok umur, nomor lomba, pembayaran (rekening dan logo bank), halaman depan dan gambarnya, aturan pendaftaran, BIB, jadwal, dan berkas unduhan. Setelah itu salin zip ke folder cadangan host dan masukkan:

```
spark pendaftaran:impor /cadangan/Backup_Pendaftaran_<tanggal>.zip
```

Perintah hanya mau berjalan di server yang belum punya pendaftaran. Bila menolak karena nama kelompok umur atau nomor lomba tidak ditemukan, samakan namanya di Pengaturan lalu ulangi. Akun admin dibuat dengan `spark admin:buat`, dan catatan kunjungan lama tidak ikut.

**Mundur ke server Laragon** bila VPS bermasalah: di dasbor Cloudflare hapus alamat pendaftaran dari tunnel VPS, lalu tambahkan kembali di tunnel Laragon dengan URL `127.0.0.1:80` dan HTTP Host Header `milklife.test`; di komputer Laragon `sc start cloudflared`. Pendaftaran yang masuk ke VPS sejak pindah dibawa kembali lewat **Pengaturan → Cadangan**: unduh cadangan di VPS, lalu impor di Laragon. Laragon versi lama belum punya menu itu, jadi perbarui dulu kodenya ke versi yang sama dengan VPS di terminal Laragon: `git pull`, `composer install --no-dev --optimize-autoloader --ignore-platform-req=php` (PHP Laragon 8.3), `php spark migrate --all`, dan `php spark aset:minify`.

## 9. Memulihkan cadangan

### Dari Pengaturan → Cadangan

Cara ini untuk berkas `Cadangan_MilkLife_….zip` yang diunduh dari panel admin. Impor **mengganti semua** data, pengaturan, dan berkas unggahan dengan isi cadangan; akun admin tidak berubah.

1. Buka **Pengaturan → Cadangan**, pilih berkasnya di **Impor cadangan**, ketik kata sandi Anda, lalu tekan **Impor dan ganti semua data**.
2. Sebelum mengganti, web menyimpan cadangan data sekarang di `writable/cadangan/Sebelum_Impor_….zip` di volume app. Lima yang terakhir disimpan dan bisa diunduh di bagian **Cadangan otomatis** di halaman yang sama. Bila impor keliru, unduh cadangan otomatis itu lalu impor lagi.
3. Berkas rusak, berkas dari web versi lebih baru, dan Backup data versi lama ditolak tanpa mengubah apa pun. Bila tertulis versi lebih baru, perbarui web dulu (bagian 10), lalu impor lagi.

Batas ukuran unggah di halaman itu mengikuti `upload_max_filesize` dan `post_max_size` (100 MB, K190) di `docker/php.ini`, sama dengan batas isi kiriman Cloudflare paket gratis. Bila cadangan lebih besar, salin berkasnya ke folder cadangan host lewat SFTP, lalu:

```
spark cadangan:pulihkan /cadangan/Cadangan_MilkLife_20261124_101500.zip
```

Perintah meminta Anda mengetik `GANTI`, lalu bekerja sama persis dengan impor di panel, termasuk cadangan otomatis, tanpa batas ukuran dan waktu.

### Dari cadangan harian `cadangan:buat`

Lakukan hanya bila data rusak atau terhapus. Pemulihan **mengganti seluruh** isi basis data dan unggahan dengan isi cadangan; pendaftaran yang masuk setelah cadangan itu dibuat akan hilang.

1. Pilih berkas zip yang akan dipulihkan (lihat tanggal dan jam di namanya). Bila masih bisa, buat dulu cadangan keadaan sekarang dengan `spark cadangan:buat`.
2. Ekstrak ke folder sementara, kosongkan basis data, lalu isi dari cadangan. Sandi root diambil dari `DB_SANDI_ROOT` di `docker/.env`:

   ```
   sudo unzip /srv/cadangan/milklife-produksi/cadangan-milklife-<tanggal>.zip -d /srv/pemulihan
   mlk exec -T db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE milklife; CREATE DATABASE milklife CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'
   mlk exec -T db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" milklife' < /srv/pemulihan/basisdata.sql
   ```

   Hak pengguna `milklife` tetap ada setelah basis data dibuat ulang.
3. Ganti unggahan dengan isi cadangan:

   ```
   mlk exec -u root app sh -c 'rm -rf /app/writable/unggahan/* /app/public/unggahan/*'
   mlk cp /srv/pemulihan/writable/unggahan/. app:/app/writable/unggahan/
   mlk cp /srv/pemulihan/public/unggahan/. app:/app/public/unggahan/
   mlk exec -u root app chown -R www-data:www-data /app/writable/unggahan /app/public/unggahan
   ```

4. Bila kode aplikasi lebih baru dari cadangan, lengkapi tabelnya dengan `spark migrate --all`.
5. Buka panel admin dan periksa jumlah pendaftaran, satu rincian pendaftaran beserta foto dan bukti bayarnya, dan halaman depan. Hapus `/srv/pemulihan` setelah selesai (`sudo rm -rf /srv/pemulihan`).

**Pindah ke VPS baru**: ikuti bagian 1 sampai 5 (pakai salinan `.env` dan `docker/.env` lama), lalu langkah 2–5 di atas, cadangan harian di bagian 7, dan pindahkan tunnel seperti bagian 8 langkah 6.

## 10. Memperbarui ke versi baru

Pembaruan memakai perintah `mlk` (K189). Staging selalu lebih dulu, lalu produksi memakai image yang sama persis:

```
mlk deploy staging
mlk deploy produksi
```

`mlk deploy staging` mengambil `origin/main` (atau versi lain: `mlk deploy staging <commit atau tag>`) dan menjalankan berurutan:

1. Memeriksa `docker/.env` dan `.env` ada dan sandinya sama, tidak ada berkas proyek yang terubah di server, dan `PORT_LOKAL` tercatat di `/opt/apps/PORTS` (daftar port bersama aplikasi lain di server).
2. `git fetch`, lalu membangun image `milklife-app:<12 huruf SHA>` dari isi commit itu persis (`git archive`). Bila image itu sudah ada, tidak dibangun ulang.
3. `spark cadangan:buat` bila container app sedang menyala. Bila cadangan gagal, misalnya kiriman ke R2 ditolak, belum ada yang diubah dan deploy berhenti; perbaiki sebabnya, atau ulangi dengan `--tanpa-cadangan` bila cadangan sudah dibuat dengan cara lain.
4. Memindah folder kode ke commit itu (`git checkout --detach`), sehingga `docker/compose.yaml` ikut versi kodenya, lalu mencatat `TAG` dan `TAG_SEBELUM` di `docker/.env`.
5. `docker compose up -d --remove-orphans --wait`: container diganti dan ditunggu sampai sehat. Web berhenti beberapa detik.
6. `spark migrate --all` dan `spark sekolah:impor`.
7. Cek kesehatan: `/admin/masuk` di port lokal harus menjawab `307`, dan `200` dengan kepala `X-Forwarded-Proto: https`.
8. Bila langkah 5, 6, atau 7 gagal, versi sebelumnya otomatis dinyalakan lagi. Perubahan basis data dari migrate yang sudah berjalan tidak dibatalkan.
9. Mencatat deploy di `/opt/apps/milklife/deploy-<lingkungan>.log`, menghapus image lama (5 terbaru dan yang dipakai staging atau produksi disimpan), lalu menjalankan `spark pembaruan:periksa`.

`mlk deploy produksi` tanpa versi memakai image yang sedang jalan di staging, tanpa membangun ulang, sehingga produksi menjalankan yang sudah dicoba di staging. Perintah ini menanyakan "Ketik ya untuk lanjut" (lewati dengan `--ya`), lalu menjalankan langkah 3 sampai 9.

`pembaruan:periksa` memeriksa pustaka Composer, migrasi, data sekolah, pendaftaran Lunas ber-NPSN sama, tabel skor Trilomba, data bawaan, dan CSS/JavaScript kecil, lalu menulis `[beres]` atau `[PERLU]` (K115). Bila ada `[PERLU]`, `mlk deploy` selesai dengan kode 2 dan menyebut perintahnya; jalankan dengan `mlk produksi spark <perintah>`, lalu `mlk produksi spark pembaruan:periksa` lagi sampai tertulis "Tidak ada perintah lain yang perlu dijalankan". Bila tertulis basis data tidak bisa dihubungi, periksa `mlk produksi ps`: container `db` harus `healthy`.

Perintah lain:

| Perintah | Gunanya |
|---|---|
| `mlk rollback <lingkungan>` | Kembali ke image sebelumnya (`TAG_SEBELUM`). Basis data tidak ikut kembali; pulihkan cadangan bila perlu (bagian 9) |
| `mlk status <lingkungan>` | Versi yang jalan, container, dan 5 deploy terakhir |
| `mlk <lingkungan> logs --tail 100 app` | Catatan container app |
| `mlk <lingkungan> spark <perintah>` | Perintah aplikasi di container app |

Hanya satu perintah `deploy`, `pasang`, atau `rollback` yang bisa berjalan pada satu waktu. Setelah selesai, buka halaman depan dan panel admin, pastikan tampil normal. Jangan menjalankan `git pull` sendiri di folder kode: folder itu berada di commit yang sedang dipasang, dan `mlk deploy` yang memindahkannya.

### Perintah sekali jalan dari pembaruan lama

`pembaruan:periksa` menyebut sendiri perintah mana yang masih perlu. Rinciannya, untuk server yang datanya berasal dari versi lama:

| Perintah | Dari pembaruan | Gunanya |
|---|---|---|
| `spark pendaftaran:gabung` | K109 | Menggabungkan pendaftaran Lunas ganda per sekolah; meminta Anda mengetik `GABUNG` |
| `spark faq:awal` | Tahap 9 | Tanya-jawab bawaan halaman Bantuan, tanpa menimpa isian admin |
| `spark tabel-skor:impor --trilomba` | K143 | Tabel skor multi-lomba dan nomor Trilomba ke Poin multi-lomba |
| `spark halaman-depan:carousel` | Tahap 9 | Foto bawaan carousel halaman depan |
| `spark galeri:awal` | K140 | Album bawaan "Seri sebelumnya"; halaman Galeri tetap tersembunyi sampai dinyalakan |
| `spark data:sesuaikan` | K154, Tahap 11 | Menyamakan data bawaan lama yang belum diubah admin (KU 8, WA, media sosial, tanya-jawab Tahap 11) |
| `spark pendaftaran:impor <berkas.zip>` | K157 | Memasukkan pendaftaran dari cadangan panel ke server kosong; jalankan paling akhir, setelah `data:sesuaikan` |

Semua perintah di atas aman dijalankan lagi dan tidak menimpa isian admin. Teks tanya-jawab dan pesan WA yang sudah tersimpan tidak berubah otomatis; admin menyuntingnya di **Pengaturan → Bantuan** dan **Pengaturan → Halaman depan**.

Setelah pembaruan Tahap 11 (K172–K176), admin memeriksa dan mengatur:

- **Pengaturan → Hasil → Susunan klasemen**: kelompok umur yang digabung dalam satu klasemen, misalnya KU 8 dan KU 10.
- **Pengaturan → Nomor Lomba**, bagian Pelaksanaan lomba: batas peserta minimal, status Tidak dilombakan, spesifikasi alat, dan cara pencatatan waktu.
- **Jadwal pertandingan**: tekan **Beri nomor acara** setelah jadwal lengkap.
- **Hasil → Rekor**: halaman Rekor publik (`/rekor`) **bawaan mati**. Nyalakan setelah isi rekor diperiksa.
- **Pengaturan → Videotron**: sakelar tampil untuk semua alamat videotron **bawaan mati**. Setelah menyala, berikan alamat **`/layar`** situs produksi kepada operator videotron vendor (K173).
- **Pengaturan → Cadangan**: unduh cadangan lengkap sebelum dan sesudah hari lomba.

Catatan:

- `mlk deploy` tidak menyentuh `.env`, volume, dan folder cadangan; dari `docker/.env` hanya `TAG` dan `TAG_SEBELUM` yang diubah. Semuanya tidak ada di git.
- Bila `mlk deploy` menolak karena ada berkas proyek yang terubah di server, jangan menyunting berkas proyek di server; hubungi pengembang.
- Admin yang sedang masuk bisa diminta masuk lagi setelah pembaruan tertentu. Itu wajar.

## 11. Daftar periksa sebelum membuka pendaftaran

Server dan keamanan:

- [ ] `mlk ps`: app dan db `healthy`, dan keduanya menyala lagi sendiri setelah VPS dinyalakan ulang (`sudo reboot`, lalu `mlk ps`). Kolom PORTS app hanya `127.0.0.1:<PORT_LOKAL>->8080/tcp`.
- [ ] SSH hanya dengan kunci, `PermitRootLogin no`, UFW hanya membuka SSH, dan `mlk ps` tidak menampilkan port yang dibuka ke jaringan luar (kolom PORTS tanpa `0.0.0.0`).
- [ ] `docker/.env` dan `.env`: izin berkas benar (`600` dan `644`), salinannya tersimpan aman di luar server.
- [ ] `.env`: `CI_ENVIRONMENT = production`, `app.baseURL` berawalan `https://`, `cookie.secure = true`, `aplikasi.minify = true`, `turnstile.kunciRahasia` terisi.
- [ ] `https://<alamat-pendaftaran>` terbuka dengan ikon gembok dari ponsel memakai data seluler.
- [ ] `http://<alamat-pendaftaran>` (tanpa s) dialihkan ke `https://`.
- [ ] `https://<alamat-pendaftaran>/halaman-tidak-ada` menampilkan halaman "tidak ditemukan" yang ramah, tanpa tulisan teknis.
- [ ] Hanya akun admin yang diperlukan yang ada, masing-masing dengan kata sandi kuat.
- [ ] Turnstile menyala di Masuk Admin, Cek Pendaftaran, dan langkah 5 pendaftaran; `turnstile.namaHost` hanya berisi alamat pendaftaran.

Isi:

- [ ] Dasbor admin tidak lagi menampilkan peringatan pengaturan yang belum lengkap.
- [ ] Halaman depan: acara, jadwal, nomor lomba, biaya, rekening pembayaran, kontak, dan unduhan (juknis) benar.
- [ ] Jadwal buka dan tutup pendaftaran benar.
- [ ] Menu **Sekolah**: sekolah bernama sama dengan sekolah lain sudah diberi nama pembeda, misalnya dengan nama desa (K97, K114). Sisanya bisa diusulkan pendaftar sendiri (K99).
- [ ] Coba satu pendaftaran dengan data karangan sampai selesai, buka halaman cek dan bukti PDF-nya, lalu hapus pendaftaran itu dari panel. **Jangan** menandainya Lunas: nomor atlet (BIB) yang sudah terpakai tidak pernah dipakai ulang.

Cadangan:

- [ ] `spark cadangan:buat` berhasil, dan berkas zip muncul di folder cadangan host.
- [ ] `/etc/cron.d/milklife-produksi` ada, dan keesokan paginya ada cadangan baru.
- [ ] Salinan R2 diatur di **Pengaturan → Cadangan**, berkas zip tampil di **Isi folder**, bucket tidak publik, dan aturan lifecycle sudah dipasang.

Selama masa pendaftaran, periksa sesekali bahwa folder cadangan berisi berkas hari ini, dan putuskan usulan nama sekolah dari pendaftar di **Sekolah** → **Usulan nama dari pendaftar**.

## Asumsi yang perlu ditinjau

1. VPS sudah menjalankan Docker dan cloudflared di host untuk aplikasi lain. MilkLife memakai tunnel itu lewat port `127.0.0.1` sendiri per lingkungan, tanpa reverse proxy, dan subnet serta port-nya dipilih supaya tidak bentrok (K182, K184).
2. Cadangan dijalankan pukul 02.00 waktu server oleh cron host, dan `cadangan:buat` langsung mengirimnya ke bucket Cloudflare R2 yang sudah dimiliki panitia, dengan pengaturan dari panel admin (K187). Container app dan db memakai zona waktu `Asia/Jakarta`.
3. `.env` dan `docker/.env` tidak ikut dalam berkas cadangan (berisi rahasia); pengembang menyimpan salinannya sendiri.
4. Image dibangun di server dari isi commit (`git archive`) oleh `mlk deploy staging`, dan produksi memakai image yang sama; tidak ada registry image terpisah (K189).
5. Kode diambil dan diperbarui dengan `git` dari repo privat lewat deploy key baca saja per server (K138, K148).
6. FrankenPHP berjalan dalam mode biasa. Worker mode, Redis, dan Memcached tidak dipakai selama web hanya satu container app (K182).
