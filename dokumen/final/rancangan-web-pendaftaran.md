# Rancangan Web Pendaftaran MilkLife Athletics Challenge

| | |
|---|---|
| Status | **Disetujui pemilik pada 10 November 2026** (K17); revisi 1 (K27), revisi 2 (K35), dan revisi 3 (K38) disetujui 11 November 2026; **revisi 4 disetujui 12 November 2026**; **revisi 5 disetujui 14 November 2026** (K96) |
| Tanggal | 10 November 2026, revisi 11, 12, dan 14 November 2026 |
| Revisi 1 | Kelompok umur dibatasi jenjang sekolah yang diatur admin (K23, K24); asumsi 2 dan 3 dikonfirmasi (K21, K22); data awal lewat perintah sekali jalan (K25); halaman Akun hanya untuk akun sendiri (K26). Bagian yang berubah: 3, 5, 6.1, 6.4, 6.5, 7, 11, 17, lampiran |
| Revisi 2 | SLB tidak ikut (K28); web lewat Cloudflare Tunnel (K31); asumsi 4–7 dikonfirmasi dan asumsi 8 diganti (K32); bukti bayar diunggah admin (K34); awalan nomor dada awal per kelompok umur (K33). Bagian yang berubah: 6.2, 6.9, 9, 11, 13, 15, 17, lampiran |
| Revisi 3 | Kelompok umur ditentukan tahun lahir, bukan usia; batas tahun lahir diisi data awal; tanggal patokan usia dihapus, usia hanya ditampilkan (K36, K37). Bagian yang berubah: 5, 6.1, 6.4, 11, 14, lampiran |
| Revisi 4 | Disesuaikan dengan yang dibangun di Tahap 4–7: foto penanggung jawab wajib (K50); batas nomor individu per atlet dan atlet per nomor (K51, K53); konfirmasi tim susulan (K57); tim tanpa pembayar (K68); ID pendaftaran tetap saat diubah (K63); cek dan bukti tanpa tanggal lahir dan nomor dada (K49, K52); panel tanpa pendaftaran baru, nomor dada atlet tambahan, saringan status rekap (K58–K60); sesi lain keluar saat ganti sandi dan halaman galat ramah (K67); CI GitHub (K46); cadangan harian (K66); juknis tidak ditunggu (K55). Bagian yang berubah: 5, 6.3, 6.5, 6.6, 6.8, 6.9, 8, 9, 13, 14, 15, 16, 17 |
| Revisi 5 | Kelompok umur ditentukan jenjang dulu, baru tahun lahir; rentang tahun lahir boleh beririsan antar-jenjang, tidak dalam satu jenjang (K95). Bagian yang berubah: 6.4, 17 |
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
| Admin (satu peran) | Masuk dengan nama pengguna dan kata sandi | Mengatur semua aturan, memeriksa pembayaran, mengelola data, mengunduh rekap. Boleh ada lebih dari satu akun; tiap admin hanya mengubah akunnya sendiri dari panel, akun lain dibuat lewat perintah `admin:buat` (K26) |

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
| `/admin/pendaftaran` | Daftar dan rincian pendaftaran, bukti bayar, tandai lunas, ubah, hapus. Tidak ada pendaftaran baru dari panel (K58) |
| `/admin/atlet` | Daftar semua atlet |
| `/admin/rekap` | Rekap per nomor lomba, tim beregu, per sekolah, per kelompok umur, keuangan |
| `/admin/pengaturan` | Daftar semua halaman pengaturan beserta tanda lengkap atau belum |
| `/admin/pengaturan/acara` | Nama, seri, tahun, tempat, tanggal acara |
| `/admin/pengaturan/pendaftaran` | Jadwal buka-tutup, buka manual, tampilan usia, batas-batas |
| `/admin/pengaturan/kelompok-umur` | Batas tahun lahir, jenjang sekolah yang boleh ikut, biaya individu, awalan nomor dada |
| `/admin/pengaturan/nomor-lomba` | Nomor lomba, jenis, jumlah anggota, biaya beregu |
| `/admin/pengaturan/pembayaran` | Bank, rekening, WA pemilik rekening, isi pesan |
| `/admin/pengaturan/halaman-depan` | Gambar, logo, judul, teks, tombol, footer |
| `/admin/pengaturan/jadwal` | Jadwal sosialisasi, technical meeting, dan lainnya |
| `/admin/pengaturan/unduhan` | Berkas unduhan |
| `/admin/pengaturan/kontak` | Narahubung dan media sosial |
| `/admin/akun` | Ganti nama, nama pengguna, dan kata sandi akun sendiri |

## 6. Aturan

### 6.1 Buka-tutup pendaftaran

Pendaftaran dan cek pendaftaran hanya bisa dipakai bila **pengaturan lengkap**. Pendaftaran terbuka bila pengaturan lengkap **dan** salah satu berlaku:

- waktu sekarang berada di antara tanggal mulai dan tanggal akhir pendaftaran, atau
- admin membuka manual dan batas waktu buka manual belum lewat.

Pengaturan dianggap lengkap bila semua ini terisi:

- data acara (nama, seri, tahun, tempat, tanggal acara),
- tanggal mulai dan tanggal akhir pendaftaran,
- minimal satu kelompok umur aktif, dan setiap kelompok umur aktif punya batas tahun lahir (minimal salah satu dari tahun lahir paling awal atau paling akhir), minimal satu jenjang sekolah yang boleh ikut, biaya individu, dan minimal satu awalan nomor dada,
- minimal satu nomor lomba aktif pada kelompok umur aktif, dan setiap nomor beregu aktif punya jumlah inti, batas anggota, dan biaya beregu (nomor mix juga jumlah putra dan putri minimal),
- data rekening (bank, nomor rekening, nama pemilik) dan nomor WA pembayaran.

Bila belum lengkap atau belum dibuka, halaman depan menampilkan keterangan ramah, misalnya "Pendaftaran sedang disiapkan" atau "Pendaftaran dibuka 1 Oktober 2026". Dasbor admin menampilkan daftar centang hal yang belum lengkap.

### 6.2 Sekolah

- Official memilih jenjang (SD/MI, SMP/MTs, SMA/SMK/MA) dan kabupaten (Blora, Rembang, Pati, Kudus, Jepara).
- Sekolah Luar Biasa (SLB) tidak ikut (K28): NPSN SLB ditolak dengan pesan yang jelas.
- Official mengetik NPSN, sistem menampilkan nama sekolah dari data referensi. Bila tidak ditemukan, nama sekolah diisi manual.
- Sekolah tanpa NPSN tetap bisa mendaftar dengan nama sekolah saja.
- Satu NPSN boleh mendaftar lebih dari sekali. Semua pendaftaran dengan NPSN yang sama dianggap satu sekolah.
- Pendaftaran tanpa NPSN berdiri sendiri dan hanya bisa dicek lewat ID pendaftaran.

### 6.3 Penanggung jawab

Nama, nomor WA, dan foto. **Foto wajib diisi** (K50); foto diperkecil di peramban, disimpan di luar folder publik, dan hanya dilihat admin. Jumlah minimal 1 dan maksimal 5; kedua batas bisa diubah admin.

### 6.4 Atlet

- Nama lengkap (otomatis huruf besar), NISN tepat 10 digit, tanggal lahir, jenis kelamin (laki-laki/perempuan).
- **NISN yang sudah terdaftar ditolak**, termasuk pada pendaftaran susulan. Perubahan untuk atlet yang sudah terdaftar dilakukan admin.
- **Kelompok umur ditentukan otomatis dari jenjang sekolah lalu tahun lahir** (K95, K36). Tahun lahir dibaca tanpa memperhatikan bulan dan tanggal. Tiap kelompok umur punya tahun lahir paling awal dan paling akhir; salah satunya boleh kosong, artinya tidak dibatasi di sisi itu (contoh KU 8: lahir 2019 ke atas).
- Usia hanya ditampilkan sebagai keterangan, tidak menentukan apa pun (K37). Usia dihitung pada tanggal mulai acara; tampilan "tahun" atau "tahun dan bulan" diatur admin. Tidak ada lagi tanggal patokan usia.
- **Jenjang dulu, baru tahun lahir** (K95). Admin mencentang jenjang yang boleh ikut pada tiap kelompok umur (K23, K24); tidak ada nilai tertulis di kode. Dari jenjang sekolah pendaftaran, hanya kelompok umur yang dicentang untuk jenjang itu yang dipertimbangkan; di antaranya dicari yang rentang tahun lahirnya cocok.
- Bila tidak ada yang cocok, atlet ditolak dengan pesan yang menyebut kelompok umur yang dibuka untuk jenjang itu beserta tahun lahirnya, dan kelompok umur jenjang lain yang menerima tahun lahir atlet bila ada.
- **Rentang tahun lahir boleh beririsan antar-jenjang, tetapi tidak dalam satu jenjang** (K95). Contoh: bila KU 12 (SD/MI) dan KU 15 (SMP/MTs) sama-sama menerima kelahiran 2012, atlet lahir 2012 dari SD masuk KU 12 dan dari SMP masuk KU 15. Halaman pengaturan menolak rentang yang beririsan dengan kelompok umur aktif lain yang dibuka untuk jenjang yang sama; kelompok umur yang belum dicentang jenjangnya tidak dibandingkan.
- Batas tahun lahir diisi data awal sesuai ketetapan pemilik (K36) dan bisa diubah admin bila juknis resmi berbeda.

### 6.5 Nomor lomba

- Setiap nomor lomba milik satu kelompok umur, berjenis individu atau beregu, dan berkategori putra, putri, atau mix.
- Pilihan nomor lomba baru muncul setelah kelompok umur atlet diketahui dan kelompok umur itu dibuka untuk jenjang sekolah pendaftaran, dan hanya yang sesuai kelompok umur dan jenis kelaminnya.
- Setiap atlet wajib ikut minimal 1 nomor (individu atau beregu).
- Maksimal 2 nomor individu per atlet (bisa diubah admin). Nomor beregu tidak dibatasi.
- Jumlah atlet satu sekolah pada satu nomor individu **tidak dibatasi** (K53). Admin tetap bisa memasang batas di pengaturan Pendaftaran (K51); batas itu dihitung bersama pendaftaran sebelumnya dari NPSN yang sama, sedangkan pendaftaran tanpa NPSN dihitung sendiri. Kosong berarti tidak dibatasi.

### 6.6 Tim beregu

- **Satu tim per nomor beregu per sekolah.**
- Anggota minimal sama dengan jumlah inti nomor tersebut (4, 5, atau 8). Boleh lebih sebagai cadangan, tanpa label, sampai batas maksimal yang diatur admin.
- Nomor mix: admin menetapkan jumlah putra dan jumlah putri minimal. Urutan lari tidak diatur sistem.
- Pendaftaran susulan dari NPSN yang sama untuk nomor beregu yang sudah ada hanya menambah anggota, tanpa biaya beregu lagi. Anggota lama dan baru bersama-sama tidak boleh melewati batas anggota.
- **Official mengonfirmasi sendiri setiap tim susulan** (K57): wizard menampilkan kotak centang per tim yang menyebut bahwa atletnya masuk ke tim sekolah yang sudah terdaftar tanpa biaya tim. Server menolak kiriman yang belum dikonfirmasi. Admin yang mengubah pendaftaran di panel tidak perlu konfirmasi ini.
- **Tim tanpa pembayar** (K68): bila pendaftaran yang membayar biaya tim dihapus, atau timnya dihapus lewat Ubah, sementara anggota susulan di pendaftaran lain tetap ada, biaya tim **tidak dipindah otomatis**. Tim itu ditandai di rincian pendaftaran, dasbor, dan rekap keuangan, dan admin membebankan biaya timnya ke salah satu pendaftaran anggotanya lewat Ubah (9). Jendela hapus dan simpan Ubah memperingatkan bila tindakannya meninggalkan tim tanpa pembayar.

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
- **ID tidak berubah saat admin mengubah pendaftaran**, juga bila jenjang sekolahnya diubah (K63). Kode jenjang di ID tetap mengikuti data saat mendaftar, supaya ID yang sudah dipegang sekolah, tercetak di bukti, dan dipakai di halaman cek tidak berganti.

### 6.9 Status pembayaran dan nomor dada

- Status awal: **Menunggu Konfirmasi Pembayaran**. Admin mengubahnya menjadi **Lunas** setelah memeriksa transfer.
- **Nomor dada dibuat saat pendaftaran ditandai Lunas**, satu nomor per atlet, tidak bergantung pada nomor lomba.
- Nomor dada 4 digit: 1 digit awalan + 3 digit urut (`001`–`999`).
- Tiap kelompok umur boleh punya lebih dari satu awalan. Bila awalan pertama penuh, nomor berlanjut ke awalan berikutnya.
- Bila semua awalan sebuah kelompok umur penuh, penandaan Lunas dibatalkan seluruhnya dan admin diminta menambah awalan.
- Nomor dada tidak dipakai ulang. Mengembalikan status ke Menunggu tidak menghapus nomor dada.
- **Bila admin mengubah pendaftaran yang sudah Lunas dan totalnya berubah (naik atau turun), status kembali ke Menunggu Konfirmasi Pembayaran** (K32). Admin menandai Lunas lagi setelah selisihnya diurus panitia di luar sistem. Atlet yang sudah punya nomor dada tetap memegangnya; atlet baru mendapat nomor saat ditandai Lunas lagi. Jendela konfirmasi simpan menyebut akibat ini.
- **Bila admin menambah atlet ke pendaftaran Lunas dan totalnya tetap** (misalnya tambahan anggota tim beregu), status tetap Lunas dan atlet baru **langsung mendapat nomor dada saat disimpan** (K59).
- Tidak ada status "dibatalkan". Pendaftaran yang batal dihapus admin, sehingga NISN-nya bisa didaftarkan lagi.

## 7. Alur pendaftaran

Wizard satu halaman dengan pil langkah bercentang:

1. **Data Sekolah** — jenjang, kabupaten, NPSN atau nama sekolah. Jenjang yang dipilih di sini menentukan kelompok umur yang boleh diikuti atlet (6.4).
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

- NPSN menampilkan semua pendaftaran sekolah itu berurutan per pendaftaran: data sekolah sekali di atas, satu kartu per pendaftaran, dan total keseluruhan di bawah (K49).
- ID pendaftaran menampilkan satu pendaftaran.
- Data tampil sesuai MoU: sekolah, penanggung jawab, atlet (nama, NISN, jenis kelamin, kelompok umur, nomor lomba), tim beregu beserta anggotanya, biaya, dan status pembayaran.
- **Tanggal lahir dan nomor dada tidak ditampilkan** (K52). Keduanya tetap tersimpan dan terlihat di panel admin.
- Nama nomor lomba ditulis bersama kategorinya, misalnya "Lari 60 m Putra" atau "Estafet 8 × 50 m Mix".
- Foto penanggung jawab hanya tampil di panel admin.

**Bukti PDF.** Kertas A4 mendatar.

- Kiri atas: logo persegi MilkLife, transparan dan tidak gepeng. Di kanannya, rata tengah: `MILKLIFE ATHLETICS CHALLENGE` dan `Seri 2 Tahun 2026`.
- Baris berikutnya: `Nama Sekolah : …` di kiri, `Tanggal Pendaftaran : …` rata kanan.
- Baris berikutnya: `Kabupaten / Kota : …` di kiri, `ID Pendaftaran : …` rata kanan.
- Penanggung jawab ditulis sebaris tanpa tabel: `Penanggung Jawab (Official) : Nama (ikon WA nomor WA)`, dipisah koma bila lebih dari satu (K52).
- Tabel individu: No, Nama, NISN, Kelompok Umur, Nomor Lomba (dengan kategori), Biaya. Tabel beregu: No, Nomor Lomba (dengan kategori), Anggota (nama dalam satu baris dipisah koma), Biaya. Lalu total pembayaran.
- Tanggal lahir dan nomor dada tidak dicetak (K52).
- Status pembayaran diberi bingkai berwarna seperti tombol: Lunas hijau, Menunggu Konfirmasi Pembayaran kuning.
- Nama berkas memakai seluruh nama acara tanpa spasi (K49): `BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_<NamaSekolah>_2026.pdf`.

Nama acara, seri, dan tahun pada bukti dan nama berkas diambil dari pengaturan acara.

## 9. Panel admin

- **Tata letak:** lebar penuh, menu samping, kepala halaman menempel di atas, menu menempel di bawah pada ponsel, tanpa teks footer.
- **Tabel:** data dari titik akhir JSON, dengan halaman, pencarian, saringan pada setiap kolom yang relevan, dan pengurutan. Setiap tabel punya tombol unduh Excel yang mengikuti saringan aktif.
- **Keadaan kosong:** pesan ramah yang menjelaskan apa yang belum ada dan langkah berikutnya.
- **Pemuatan:** bilah kemajuan di atas halaman saat data dimuat.
- **Tindakan berisiko** (hapus, tandai lunas, kembalikan ke Menunggu): jendela konfirmasi yang menyebut apa yang akan terjadi.
- **Tanpa pendaftaran baru** (K58): panel hanya melihat, menandai Lunas, mengubah, dan menghapus pendaftaran yang sudah ada. Sekolah yang terlambat didaftarkan lewat wizard setelah admin memakai buka manual (6.1).
- **Ubah pendaftaran:** admin bisa mengubah data sekolah, penanggung jawab, atlet, dan nomor lomba. Aturan pada bagian 6 tetap berlaku dan biaya dihitung ulang. ID pendaftaran tidak berubah (6.8). Akibat pada status dan nomor dada mengikuti 6.9 (K32, K59).
- **Tim tanpa pembayar** (K68): rincian pendaftaran memberi peringatan pada tim yang kehilangan pembayar biaya timnya, dasbor menampilkan jumlahnya bila ada, dan rekap keuangan mendaftarnya beserta pendaftaran anggotanya. Di Ubah, tim itu punya kotak centang "Bebankan biaya tim ke pendaftaran ini" dengan tarif saat ini; bila pendaftarannya Lunas dan totalnya berubah, berlaku 6.9.
- **Bukti bayar** (K34): admin mengunggah bukti transfer yang dikirim official lewat WA ke pendaftaran yang bersangkutan, paling banyak 5 gambar per pendaftaran. Gambar dikecilkan otomatis sebelum disimpan (sisi terpanjang 1600 piksel, JPEG). Admin bisa melihat, mengunduh, dan menghapus tiap gambar. Official tidak mengunggah apa pun.
- **Rekap:** peserta per nomor lomba, tim beregu, per sekolah, per kelompok umur, dan keuangan (tagihan, lunas, belum). Tersedia Excel dan PDF. Rekap peserta memuat semua pendaftaran dengan saringan status (semua, Lunas, Menunggu), dan Excel serta PDF-nya mengikuti saringan itu; rekap keuangan selalu memuat keduanya (K60).
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
| `kelompok_umur` | nama, tahun lahir paling awal, tahun lahir paling akhir (masing-masing boleh kosong), biaya individu, urutan, aktif |
| `kelompok_umur_jenjang` | kelompok umur, jenjang (A, B, atau C); satu baris per jenjang yang boleh ikut, unik per pasangan |
| `awalan_nomor_dada` | kelompok umur, awalan (unik), urutan, nomor terakhir |
| `nomor_lomba` | kelompok umur, nama, jenis, kategori, jumlah inti, batas anggota, jumlah putra/putri (mix), biaya beregu, urutan, aktif |
| `pendaftaran` | ID pendaftaran (unik), jenjang, npsn, nama sekolah, kabupaten, subtotal individu, subtotal beregu, total, status, waktu lunas |
| `penanggung_jawab` | pendaftaran, nama, nomor WA, foto |
| `atlet` | pendaftaran, nama, NISN (unik), tanggal lahir, jenis kelamin, kelompok umur, nomor dada (unik) |
| `atlet_nomor` | atlet, nomor lomba, biaya (terisi untuk individu) |
| `pendaftaran_beregu` | pendaftaran, nomor lomba, biaya tim |
| `bukti_bayar` | pendaftaran, berkas, ukuran, waktu unggah (paling banyak 5 per pendaftaran, dijaga aplikasi) |
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
- Foto penanggung jawab dan bukti bayar disimpan di luar folder publik dan hanya disajikan ke admin.
- Sandi admin di-hash; sesi admin berakhir otomatis.
- **Mengganti kata sandi mengeluarkan sesi lain milik akun yang sama** pada permintaan berikutnya (K67); sesi yang mengganti tetap masuk. Mengganti kata sandi lewat perintah `admin:buat` mengeluarkan semua sesi akun itu.
- Kepala keamanan HTTP aktif di setiap halaman.
- Di mode produksi, halaman galat (halaman tidak ditemukan, permintaan keliru, galat umum) tampil ramah dalam bahasa Indonesia tanpa rincian teknis (K67).

**Risiko yang diterima pemilik:** NPSN bersifat publik dan ID pendaftaran berurutan, jadi orang yang menebaknya bisa melihat data siswa (nama, NISN, tanggal lahir). Pemilik memilih tampilan penuh berdasarkan MoU dengan sekolah. Pembatasan laju mengurangi pengambilan massal, tetapi tidak menutup risikonya.

## 14. Pengujian

- Uji unit untuk aturan murni: tahun lahir dan kelompok umur, biaya, ID pendaftaran, nomor dada, kelengkapan tim.
- Uji fitur untuk alur pendaftaran, cek, bukti, panel, dan penandaan lunas, ditambah satu uji alur lengkap dari pendaftaran sampai hapus.
- Semua uji dijalankan dengan satu perintah (`composer uji`) di komputer pengembang sebelum PR dibuka. **CI GitHub** (GitHub Actions) menjalankan uji yang sama di setiap PR dan setiap perubahan di `main` (K46); PR digabung setelah keduanya lulus.

## 15. Pemasangan

Laragon dipakai untuk pengembangan dan produksi. Panduan pemasangan produksi ditulis sebagai dokumen tersendiri pada Tahap 7 dan mencakup: virtual host Nginx ke folder `public/`, HTTPS lewat Cloudflare Tunnel, mode produksi di `.env`, perkecil aset, cadangan terjadwal, cara memulihkan cadangan, dan cara memperbarui versi.

**Cadangan** (K66): dibuat **harian** oleh Penjadwal Tugas Windows ke folder di luar proyek. Satu berkas zip berisi basis data serta foto penanggung jawab, bukti bayar, dan unggahan admin. Cadangan yang lebih tua dari **14 hari** dihapus otomatis. Pemulihan dilakukan manual mengikuti panduan.

Peserta menjangkau komputer Laragon dari internet lewat **Cloudflare Tunnel** (K31). Pengaturannya berada di luar kode aplikasi, kecuali satu hal: alamat IP pengunjung dibaca dari kepala `CF-Connecting-IP` yang hanya dipercaya dari terowongan, supaya pembatasan laju tetap menghitung per pengunjung.

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
| 7. Penyelesaian | Minify dan pengecilan gambar, keamanan produksi, tim tanpa pembayar, cadangan harian, panduan pemasangan produksi, revisi rancangan ini, uji menyeluruh (K67) |

## 17. Asumsi yang perlu ditinjau

Hal berikut saya putuskan sendiri karena tidak disebut dalam wawancara. Mohon dikoreksi bila keliru.

1. ~~Kelompok umur ditentukan dari usia saja; atlet tidak bisa "naik" ke kelompok umur di atasnya, dan jenjang sekolah tidak membatasi kelompok umur.~~ **Diganti K23:** kelompok umur tetap ditentukan dari usia saja dan atlet tidak bisa "naik", tetapi jenjang sekolah membatasi kelompok umur sesuai centang admin (6.4). **Diganti K95:** jenjang sekolah menyaring kelompok umur lebih dulu, lalu tahun lahir menentukan di antaranya; rentang boleh beririsan antar-jenjang (6.4).
2. Frog Jump dan Turbo Throw (KU 10) berjenis **individu**. **Dikonfirmasi K21.**
3. Trilomba (KU 12) adalah **satu nomor individu** dan dihitung satu dari batas dua nomor individu. **Dikonfirmasi K22.**
4. Nomor urut ID pendaftaran berlaku global, bukan per jenjang. **Dikonfirmasi K32.**
5. Data tersimpan di langkah Biaya (saat konfirmasi), bukan saat tombol "Pendaftaran Selesai". **Dikonfirmasi K32.**
6. Official tidak bisa mengubah pendaftaran yang sudah tersimpan; perubahan lewat admin. **Dikonfirmasi K32.**
7. Foto penanggung jawab tidak tampil di halaman cek. **Dikonfirmasi K32.**
8. ~~Bila admin mengubah pendaftaran yang sudah lunas sehingga totalnya berubah, status tetap Lunas dan selisihnya diurus panitia di luar sistem.~~ **Diganti K32:** status kembali ke Menunggu dan admin menandai Lunas lagi (6.9).

Semua asumsi sudah diputuskan pemilik. Juknis resmi tidak ditunggu; batas yang sudah disepakati tetap dipakai dan perubahan aturan berikutnya lewat wawancara dengan pemilik (K55).

## Lampiran: data awal nomor lomba

Diambil dari flyer Seri 2 – 2026 dan diisi lewat perintah sekali jalan saat pemasangan (K25). Semua bisa diubah admin. Jenjang yang boleh ikut dan biaya individu dikosongkan. Batas tahun lahir (K36): KU 8 lahir 2019 ke atas; KU 10 lahir 2016–2018; KU 12 lahir 2015; KU 15 lahir 2011–2014; KU 18 lahir 2008–2010. Awalan nomor dada diisi satu per kelompok umur (K33): KU 8 = 1, KU 10 = 2, KU 12 = 3, KU 15 = 4, KU 18 = 5; admin bisa mengubah atau menambahnya. Nomor individu dibuat dua kali, putra dan putri; nomor beregu mengikuti kategori di tabel.

| Kelompok umur | Individu (putra dan putri) | Beregu |
|---|---|---|
| KU 8 (Seneng Playon) | Lari 40 m | – |
| KU 10 | Lari 60 m, Frog Jump, Turbo Throw | Kanga's Escape putra/putri (4 orang, Rp100.000); Formula 1 putra/putri (4 orang, Rp100.000); Estafet 8 × 50 m mix (8 orang, Rp150.000) |
| KU 12 | Lompat Jauh, Tolak Peluru, Lari 60 m, Lari 1000 m, Trilomba | Estafet 8 × 50 m mix (8 orang, Rp150.000) |
| KU 15 | Jalan Cepat 2000 m, Lari 80 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 5 × 80 m putra/putri (5 orang, Rp100.000) |
| KU 18 | Jalan Cepat 3000 m, Lari 100 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 4 × 100 m putra/putri (4 orang, Rp100.000); Estafet 4 × 400 m mix (4 orang, Rp100.000) |
