# Rancangan Web Pendaftaran MilkLife Athletics Challenge

| | |
|---|---|
| Status | **Draf, menunggu tinjauan pemilik** |
| Tanggal | 10 November 2026 |
| Acara | MilkLife Athletics Challenge Seri 2 – 2026, Supersoccer Arena, Kudus, 9–12 Desember 2026 |
| Dasar | Wawancara sesi 1 (lihat `agent/KEPUTUSAN.md`) dan flyer di `sumber/flyer/` |

Dokumen ini menjelaskan *apa* yang dibangun. *Cara* membangunnya per tahap ditulis terpisah sebagai rencana implementasi setelah rancangan ini disetujui.

## 1. Tujuan

Sekolah se-Karesidenan Pati mendaftarkan atletnya secara daring, official bisa memeriksa rekap pendaftarannya, dan panitia mendapat rekap siap pakai.

Berhasil bila:

- official non-teknis bisa menyelesaikan pendaftaran dari ponsel tanpa bantuan,
- biaya, kelompok umur, dan nomor dada dihitung sistem tanpa salah,
- panitia bisa mengunduh rekap Excel/PDF tanpa mengolah ulang data.

## 2. Cakupan

**Termasuk:** halaman depan, pendaftaran, cek pendaftaran, unduhan, bukti pendaftaran PDF, panel admin (pengaturan, data pendaftaran, rekap, ekspor).

**Tidak termasuk:** pelaksanaan lomba (hasil, seri, jadwal lomba per nomor), pembayaran otomatis, akun untuk official, tema gelap, banyak acara sekaligus dalam satu pemasangan.

## 3. Pengguna

| Pengguna | Akses | Yang dilakukan |
|---|---|---|
| Official sekolah | Tanpa akun | Mendaftar, mengirim konfirmasi pembayaran lewat WA, mengunduh bukti, memeriksa rekap |
| Admin (satu peran) | Masuk dengan nama pengguna dan kata sandi | Mengatur semua aturan, memeriksa pembayaran, mengelola data, mengunduh rekap |

## 4. Pendekatan teknis

**Dipilih: aplikasi monolit CodeIgniter 4 dengan halaman dirender server**, Bootstrap 5, dan JavaScript biasa. Tabel admin mengambil data dari titik akhir JSON.

Dua pendekatan lain ditolak:

- **API + aplikasi satu halaman (Vue/React):** butuh Node dan proses bangun, terlalu berat untuk tim dan pemasangan Laragon.
- **Wizard banyak halaman dengan sesi server:** bertentangan dengan permintaan draf tersimpan di peramban.

| Kebutuhan | Pilihan |
|---|---|
| Kerangka kerja | CodeIgniter 4 (rilis stabil terbaru), PHP 8.2+ |
| Basis data | MySQL 8, InnoDB, `utf8mb4` |
| Server | Nginx + PHP dari Laragon, untuk pengembangan dan produksi |
| Tampilan | Bootstrap 5.3 dan Bootstrap Icons, berkas disimpan lokal (tanpa CDN, tanpa Node) |
| Tabel data | DataTables 2 mode sisi-server (halaman, saring, urut) |
| PDF | mPDF |
| Excel | PhpSpreadsheet |
| Pengaturan | `codeigniter4/settings` (pengaturan tersimpan di basis data) |
| Minify | `matthiasmullie/minify` untuk CSS/JS lewat `php spark aset:minify`; berkas asli tetap ada |
| Masuk admin | Sesi bawaan CodeIgniter + `password_hash`, tanpa pustaka tambahan |

HTML diperkecil saat dikirim ke peramban (filter keluaran, aktif di produksi), jadi berkas tampilan asli tidak berubah.

## 5. Peta halaman

Semua URL dan teks antarmuka berbahasa Indonesia.

**Publik**

| URL | Isi |
|---|---|
| `/` | Halaman depan |
| `/pendaftaran` | Wizard pendaftaran |
| `/cek-pendaftaran` | Cek lewat NPSN atau ID pendaftaran |
| `/unduhan` | Flyer, juknis, berkas lain |
| `/bukti/{id-pendaftaran}` | Bukti pendaftaran PDF |

**Admin** (semua di bawah `/admin`)

| URL | Isi |
|---|---|
| `/admin/masuk` | Halaman masuk |
| `/admin/dasbor` | Ringkasan angka dan daftar kelengkapan pengaturan |
| `/admin/pendaftaran` | Daftar dan rincian pendaftaran, tandai lunas, ubah, hapus |
| `/admin/atlet` | Daftar semua atlet |
| `/admin/rekap` | Rekap per nomor lomba, per sekolah, per kelompok umur, keuangan |
| `/admin/pengaturan/acara` | Nama, seri, tahun, tempat, tanggal acara |
| `/admin/pengaturan/pendaftaran` | Jadwal buka-tutup, buka manual, batas-batas |
| `/admin/pengaturan/kelompok-umur` | Batas usia, biaya individu, awalan nomor dada |
| `/admin/pengaturan/nomor-lomba` | Nomor lomba, jenis, jumlah anggota, biaya beregu |
| `/admin/pengaturan/pembayaran` | Bank, rekening, WA pemilik rekening, isi pesan |
| `/admin/pengaturan/halaman-depan` | Gambar, logo, judul, teks, tombol, footer |
| `/admin/pengaturan/jadwal` | Jadwal sosialisasi, technical meeting, dan lainnya |
| `/admin/pengaturan/unduhan` | Berkas unduhan |
| `/admin/pengaturan/kontak` | Narahubung dan media sosial |
| `/admin/akun` | Ganti nama dan kata sandi |

## 6. Aturan

### 6.1 Buka-tutup pendaftaran

Pendaftaran dan cek pendaftaran hanya bisa dipakai bila **pengaturan lengkap**. Pendaftaran terbuka bila pengaturan lengkap **dan** salah satu berlaku:

- waktu sekarang berada di antara tanggal mulai dan tanggal akhir pendaftaran, atau
- admin membuka manual dan batas waktu buka manual belum lewat.

Pengaturan dianggap lengkap bila semua ini terisi: data acara, tanggal pendaftaran, tanggal patokan usia, setiap kelompok umur aktif punya batas usia, biaya individu, dan awalan nomor dada, minimal satu nomor lomba aktif, serta data rekening dan nomor WA pembayaran.

Bila belum lengkap atau belum dibuka, halaman depan menampilkan keterangan ramah, misalnya "Pendaftaran sedang disiapkan" atau "Pendaftaran dibuka 1 Oktober 2026". Dasbor admin menampilkan daftar centang hal yang belum lengkap.

### 6.2 Sekolah

- Official memilih jenjang (SD/MI, SMP/MTs, SMA/SMK/MA) dan kabupaten (Blora, Rembang, Pati, Kudus, Jepara).
- Official mengetik NPSN, sistem menampilkan nama sekolah dari data referensi. Bila tidak ditemukan, nama sekolah diisi manual.
- Sekolah tanpa NPSN tetap bisa mendaftar dengan nama sekolah saja.
- Satu NPSN boleh mendaftar lebih dari sekali. Semua pendaftaran dengan NPSN yang sama dianggap satu sekolah.
- Pendaftaran tanpa NPSN berdiri sendiri dan hanya bisa dicek lewat ID pendaftaran.

### 6.3 Penanggung jawab

Nama, nomor WA, dan foto. Jumlah minimal 1 dan maksimal 5; kedua batas bisa diubah admin.

### 6.4 Atlet

- Nama lengkap (otomatis huruf besar), NISN tepat 10 digit, tanggal lahir, jenis kelamin (laki-laki/perempuan).
- **NISN yang sudah terdaftar ditolak**, termasuk pada pendaftaran susulan. Perubahan untuk atlet yang sudah terdaftar dilakukan admin.
- Usia dihitung pada tanggal patokan (nilai awal 30 November 2026, bisa diubah admin). Tampilan usia bisa "tahun" atau "tahun dan bulan", diatur admin.
- Kelompok umur ditentukan otomatis dari usia. Bila usia tidak masuk kelompok umur mana pun, atlet ditolak dengan pesan jelas.
- Batas usia tiap kelompok umur **kosong pada awalnya** dan diisi admin sesuai juknis.

### 6.5 Nomor lomba

- Setiap nomor lomba milik satu kelompok umur, berjenis individu atau beregu, dan berkategori putra, putri, atau mix.
- Pilihan nomor lomba baru muncul setelah kelompok umur atlet diketahui, dan hanya yang sesuai kelompok umur dan jenis kelaminnya.
- Setiap atlet wajib ikut minimal 1 nomor (individu atau beregu).
- Maksimal 2 nomor individu per atlet (bisa diubah admin). Nomor beregu tidak dibatasi.

### 6.6 Tim beregu

- **Satu tim per nomor beregu per sekolah.**
- Anggota minimal sama dengan jumlah inti nomor tersebut (4, 5, atau 8). Boleh lebih sebagai cadangan, tanpa label, sampai batas maksimal yang diatur admin.
- Nomor mix: admin menetapkan jumlah putra dan jumlah putri minimal. Urutan lari tidak diatur sistem.
- Pendaftaran susulan dari NPSN yang sama untuk nomor beregu yang sudah ada hanya menambah anggota, tanpa biaya beregu lagi.

### 6.7 Biaya

- **Individu:** biaya per atlet per nomor, besarnya mengikuti kelompok umur atlet. Dua nomor individu berarti dua kali biaya.
- **Beregu:** biaya per tim, besarnya ditetapkan pada tiap nomor lomba.
- Biaya disimpan bersama pendaftaran saat pendaftaran dibuat. Perubahan tarif oleh admin tidak mengubah pendaftaran yang sudah ada.
- Semua angka dihitung ulang di server. Angka dari peramban tidak dipercaya.
- Rupiah ditampilkan tanpa angka di belakang koma, contoh `Rp150.000`.

### 6.8 ID pendaftaran

Format: `MAC` + seri + kode jenjang + nomor urut 5 digit + 2 digit tahun. Contoh: `MAC2A0000126`.

- Kode jenjang: `A` untuk SD/MI, `B` untuk SMP/MTs, `C` untuk SMA/SMK/MA.
- Nomor urut berlaku untuk seluruh pendaftaran (bukan per jenjang) dan diambil dari nomor baris basis data. Nomor bisa melompat bila ada pendaftaran yang gagal tersimpan atau dihapus.

### 6.9 Status pembayaran dan nomor dada

- Status awal: **Menunggu Konfirmasi Pembayaran**. Admin mengubahnya menjadi **Lunas** setelah memeriksa transfer.
- **Nomor dada dibuat saat pendaftaran ditandai Lunas**, satu nomor per atlet, tidak bergantung pada nomor lomba.
- Nomor dada 4 digit: 1 digit awalan + 3 digit urut (`001`–`999`).
- Tiap kelompok umur boleh punya lebih dari satu awalan. Bila awalan pertama penuh, nomor berlanjut ke awalan berikutnya.
- Bila semua awalan sebuah kelompok umur penuh, penandaan Lunas dibatalkan seluruhnya dan admin diminta menambah awalan.
- Nomor dada tidak dipakai ulang. Mengembalikan status ke Menunggu tidak menghapus nomor dada.
- Tidak ada status "dibatalkan". Pendaftaran yang batal dihapus admin, sehingga NISN-nya bisa didaftarkan lagi.

## 7. Alur pendaftaran

Wizard satu halaman dengan pil langkah bercentang:

1. **Data Sekolah** — jenjang, kabupaten, NPSN atau nama sekolah.
2. **Penanggung Jawab** — 1 sampai 5 orang.
3. **Atlet** — formulir atlet dan daftar sementara.
4. **Biaya** — rincian dan konfirmasi.
5. **Pembayaran** — rekening, konfirmasi WA, selesai.

**Langkah 3.** Setelah atlet ditambahkan, daftar sementara langsung terbentuk dalam dua bagian: daftar individu dan daftar tim beregu (dikelompokkan per nomor). Setiap baris punya tombol ubah dan hapus. Sebelum lanjut, sistem memeriksa kelengkapan setiap tim.

**Langkah 4.** Dua tabel terpisah:

- Individu: No, Nama, NISN, Kelompok Umur, Nomor Lomba, Biaya, lalu subtotal individu.
- Beregu: No, Nomor Lomba Beregu, Biaya, lalu subtotal beregu.

Di bawahnya total pembayaran: subtotal individu, subtotal beregu, dan jumlah keduanya. Tombol lanjut membuka jendela konfirmasi. **Data tersimpan ke server saat official mengonfirmasi di langkah ini**, dan ID pendaftaran dibuat.

**Langkah 5.** Menampilkan logo bank, nomor rekening, nama pemilik rekening, dan total. Tombol "Kirim Konfirmasi Pembayaran" membuka WA ke pemilik rekening dengan pesan berisi nama sekolah, NPSN, ID pendaftaran, subtotal individu, subtotal beregu, dan total. Tombol "Pendaftaran Selesai" menampilkan tombol cetak bukti dan mengosongkan draf.

**Draf.** Isian tersimpan di peramban (`localStorage`) pada setiap perubahan, sehingga tidak hilang saat halaman dimuat ulang. Foto penanggung jawab diperkecil di peramban (sisi terpanjang 800 piksel, JPEG) dan ikut tersimpan di draf.

**Validasi.** Setiap isian diperiksa di peramban untuk kenyamanan dan diperiksa lagi seluruhnya di server. Penyimpanan berlangsung dalam satu transaksi: tersimpan semua atau tidak sama sekali.

## 8. Cek pendaftaran dan bukti

**Cek.** Official memasukkan NPSN atau ID pendaftaran.

- NPSN menampilkan semua pendaftaran sekolah itu, digabung.
- ID pendaftaran menampilkan satu pendaftaran.
- Data tampil penuh sesuai MoU: sekolah, penanggung jawab, atlet (nama, NISN, tanggal lahir, kelompok umur, nomor lomba, nomor dada), tim beregu, biaya, dan status pembayaran.
- Foto penanggung jawab hanya tampil di panel admin.

**Bukti PDF.** Kertas A4 mendatar.

- Kiri atas: logo persegi MilkLife. Di kanannya, rata tengah: `MILKLIFE ATHLETICS CHALLENGE` dan `Seri 2 Tahun 2026`.
- Baris berikutnya: `Nama Sekolah : …` di kiri, `Tanggal Pendaftaran : …` rata kanan.
- Baris berikutnya: `Kabupaten / Kota : …` di kiri, `ID Pendaftaran : …` rata kanan.
- Isi: daftar penanggung jawab, tabel individu, tabel beregu, total, dan status pembayaran.
- Nama berkas: `BuktiPendaftaran_MilkLifeSeri2_<NamaSekolah>_2026.pdf`.

Nama acara, seri, dan tahun pada bukti dan nama berkas diambil dari pengaturan acara.

## 9. Panel admin

- **Tata letak:** lebar penuh, menu samping, kepala halaman menempel di atas, menu menempel di bawah pada ponsel, tanpa teks footer.
- **Tabel:** data dari titik akhir JSON, dengan halaman, pencarian, saringan pada setiap kolom yang relevan, dan pengurutan. Setiap tabel punya tombol unduh Excel yang mengikuti saringan aktif.
- **Keadaan kosong:** pesan ramah yang menjelaskan apa yang belum ada dan langkah berikutnya.
- **Pemuatan:** bilah kemajuan di atas halaman saat data dimuat.
- **Tindakan berisiko** (hapus, tandai lunas): jendela konfirmasi yang menyebut apa yang akan terjadi.
- **Ubah pendaftaran:** admin bisa mengubah data sekolah, penanggung jawab, atlet, dan nomor lomba. Aturan pada bagian 6 tetap berlaku dan biaya dihitung ulang.
- **Rekap:** peserta per nomor lomba, tim beregu, per sekolah, per kelompok umur, dan keuangan (tagihan, lunas, belum). Tersedia Excel dan PDF.
- **Gambar belum ada:** kotak ikon "tanpa gambar", bukan gambar tiruan.

## 10. Halaman depan

Semua isi diatur dari panel admin.

- Gambar kepala mendatar selebar layar.
- Logo persegi, logo persegi panjang, dan logo Bakti Olahraga Djarum Foundation.
- Judul, teks, seri, tahun, tempat, dan tanggal acara.
- Dua tombol utama: **Pendaftaran** dan **Cek Pendaftaran**.
- Keterangan status pendaftaran (lihat 6.1).
- Kelompok umur, nomor lomba, dan biaya, diambil dari data induk.
- Jadwal: sosialisasi daring, technical meeting, dan jadwal lain yang ditambahkan admin.
- Unduhan, narahubung, media sosial.
- Tombol bagikan ke WA dan tombol tanya admin lewat WA dengan teks yang sudah diatur.
- Footer.

## 11. Model data

Nama tabel dan kolom berbahasa Indonesia.

| Tabel | Isi utama |
|---|---|
| `admin` | nama, nama pengguna, sandi (hash) |
| `settings` | pengaturan kunci–nilai (dari pustaka settings) |
| `sekolah` | npsn, nama, bentuk, jenjang, status, alamat, kecamatan, kabupaten |
| `kelompok_umur` | nama, usia minimal, usia maksimal, biaya individu, urutan, aktif |
| `awalan_nomor_dada` | kelompok umur, awalan (unik), urutan, nomor terakhir |
| `nomor_lomba` | kelompok umur, nama, jenis, kategori, jumlah inti, batas anggota, jumlah putra/putri (mix), biaya beregu, urutan, aktif |
| `pendaftaran` | ID pendaftaran (unik), jenjang, npsn, nama sekolah, kabupaten, subtotal individu, subtotal beregu, total, status, waktu lunas |
| `penanggung_jawab` | pendaftaran, nama, nomor WA, foto |
| `atlet` | pendaftaran, nama, NISN (unik), tanggal lahir, jenis kelamin, kelompok umur, nomor dada (unik) |
| `atlet_nomor` | atlet, nomor lomba, biaya (terisi untuk individu) |
| `pendaftaran_beregu` | pendaftaran, nomor lomba, biaya tim |
| `jadwal` | judul, keterangan, waktu, tempat atau tautan, urutan, tampil |
| `unduhan` | judul, keterangan, berkas, urutan, tampil |
| `tautan` | jenis (narahubung/media sosial), label, nilai, urutan |

Keunikan NISN dan nomor dada dijaga oleh basis data, bukan hanya oleh aplikasi.

## 12. Tampilan

- Satu tema terang.
- Warna dari flyer: biru utama, biru tua, oranye sebagai aksen dan tombol utama, biru muda untuk bidang informasi, putih untuk kartu.
- Responsif, dirancang dari ponsel lebih dulu.
- Bahasa ramah untuk orang non-teknis: tanpa istilah teknis, pesan galat menjelaskan cara memperbaikinya.

## 13. Keamanan dan privasi

- Perlindungan CSRF pada semua formulir.
- Pembatasan laju pada cek pendaftaran, bukti, pencarian NPSN, simpan pendaftaran, dan halaman masuk.
- Semua keluaran di-escape; semua masukan divalidasi di server.
- Foto disimpan di luar folder publik dan hanya disajikan ke admin.
- Sandi admin di-hash; sesi admin berakhir otomatis.

**Risiko yang diterima pemilik:** NPSN bersifat publik dan ID pendaftaran berurutan, jadi orang yang menebaknya bisa melihat data siswa (nama, NISN, tanggal lahir). Pemilik memilih tampilan penuh berdasarkan MoU dengan sekolah. Pembatasan laju mengurangi pengambilan massal, tetapi tidak menutup risikonya.

## 14. Pengujian

- Uji unit untuk aturan murni: usia dan kelompok umur, biaya, ID pendaftaran, nomor dada, kelengkapan tim.
- Uji fitur untuk alur pendaftaran, cek, dan penandaan lunas.
- Tanpa CI GitHub. Semua uji dijalankan sendiri di komputer pengembang dengan satu perintah, sebelum PR digabung.

## 15. Pemasangan

Laragon dipakai untuk pengembangan dan produksi. Panduan pemasangan produksi ditulis pada tahap akhir dan mencakup: virtual host Nginx ke folder `public/`, HTTPS, mode produksi di `.env`, dan cadangan basis data terjadwal.

Cara peserta menjangkau komputer Laragon dari internet (domain, IP publik, atau terowongan) **belum diputuskan** dan berada di luar kode aplikasi.

## 16. Tahapan

Setiap tahap punya wawancara singkat bila perlu, rencana implementasi, dan PR sendiri.

| Tahap | Hasil |
|---|---|
| 0. Fondasi | Aturan repo, data sekolah, rancangan ini, `.env.example` |
| 1. Kerangka | CodeIgniter terpasang, migrasi, impor sekolah, masuk admin, tata letak, tema, uji dasar |
| 2. Pengaturan | Semua halaman pengaturan dan data induk, daftar kelengkapan |
| 3. Halaman depan | Halaman depan, jadwal, unduhan, kontak |
| 4. Pendaftaran | Wizard lengkap sampai tersimpan |
| 5. Bukti dan cek | Bukti PDF, halaman cek |
| 6. Panel data | Daftar pendaftaran, lunas dan nomor dada, ubah, hapus, rekap, ekspor |
| 7. Penyelesaian | Minify, uji menyeluruh, panduan pemasangan produksi |

## 17. Asumsi yang perlu ditinjau

Hal berikut saya putuskan sendiri karena tidak disebut dalam wawancara. Mohon dikoreksi bila keliru.

1. Kelompok umur ditentukan dari usia saja; atlet tidak bisa "naik" ke kelompok umur di atasnya, dan jenjang sekolah tidak membatasi kelompok umur.
2. Frog Jump dan Turbo Throw (KU 10) berjenis **individu**, karena tidak ada di daftar biaya beregu.
3. Trilomba (KU 12) adalah **satu nomor individu** dan dihitung satu dari batas dua nomor individu.
4. Nomor urut ID pendaftaran berlaku global, bukan per jenjang.
5. Data tersimpan di langkah Biaya (saat konfirmasi), bukan saat tombol "Pendaftaran Selesai".
6. Official tidak bisa mengubah pendaftaran yang sudah tersimpan; perubahan lewat admin.
7. Foto penanggung jawab tidak tampil di halaman cek.
8. Bila admin mengubah pendaftaran yang sudah lunas sehingga totalnya berubah, status tetap Lunas dan selisihnya diurus panitia di luar sistem.

## Lampiran: data awal nomor lomba

Diambil dari flyer Seri 2 – 2026. Semua bisa diubah admin. Biaya individu per kelompok umur dan batas usia dikosongkan.

| Kelompok umur | Individu (putra dan putri) | Beregu |
|---|---|---|
| KU 8 (Seneng Playon) | Lari 40 m | – |
| KU 10 | Lari 60 m, Frog Jump, Turbo Throw | Kanga's Escape putra/putri (4 orang, Rp100.000); Formula 1 putra/putri (4 orang, Rp100.000); Estafet 8 × 50 m mix (8 orang, Rp150.000) |
| KU 12 | Lompat Jauh, Tolak Peluru, Lari 60 m, Lari 1000 m, Trilomba | Estafet 8 × 50 m mix (8 orang, Rp150.000) |
| KU 15 | Jalan Cepat 2000 m, Lari 80 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 5 × 80 m putra/putri (5 orang, Rp100.000) |
| KU 18 | Jalan Cepat 3000 m, Lari 100 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 4 × 100 m putra/putri (4 orang, Rp100.000); Estafet 4 × 400 m mix (4 orang, Rp100.000) |
