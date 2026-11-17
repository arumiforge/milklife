# Rancangan Web Pendaftaran MilkLife Athletics Challenge

| | |
|---|---|
| Status | **Disetujui pemilik pada 10 November 2026** (K17); revisi 1 (K27), revisi 2 (K35), dan revisi 3 (K38) disetujui 11 November 2026; **revisi 4 disetujui 12 November 2026**; **revisi 5 disetujui 14 November 2026** (K96); **revisi 6 disetujui 14 November 2026** (K100); **revisi 7 disetujui 15 November 2026** (K101); **revisi 8, 9, 10, dan 11 15 November 2026** (K105–K108, diubah langsung sesuai K101); **revisi 12, 13, dan 14 16 November 2026** (K109–K113, diubah langsung sesuai K101); **revisi 15 dan 16 17 November 2026** (K114, K115, diubah langsung sesuai K101) |
| Tanggal | 10 November 2026, revisi 11, 12, 14, 15, 16, dan 17 November 2026 |
| Revisi 1 | Kelompok umur dibatasi jenjang sekolah yang diatur admin (K23, K24); asumsi 2 dan 3 dikonfirmasi (K21, K22); data awal lewat perintah sekali jalan (K25); halaman Akun hanya untuk akun sendiri (K26). Bagian yang berubah: 3, 5, 6.1, 6.4, 6.5, 7, 11, 17, lampiran |
| Revisi 2 | SLB tidak ikut (K28); web lewat Cloudflare Tunnel (K31); asumsi 4–7 dikonfirmasi dan asumsi 8 diganti (K32); bukti bayar diunggah admin (K34); awalan nomor atlet (BIB) awal per kelompok umur (K33). Bagian yang berubah: 6.2, 6.9, 9, 11, 13, 15, 17, lampiran |
| Revisi 3 | Kelompok umur ditentukan tahun lahir, bukan usia; batas tahun lahir diisi data awal; tanggal patokan usia dihapus, usia hanya ditampilkan (K36, K37). Bagian yang berubah: 5, 6.1, 6.4, 11, 14, lampiran |
| Revisi 4 | Disesuaikan dengan yang dibangun di Tahap 4–7: foto ofisial wajib (K50); batas nomor individu per atlet dan atlet per nomor (K51, K53); konfirmasi tim susulan (K57); tim tanpa pembayar (K68); ID pendaftaran tetap saat diubah (K63); cek dan bukti tanpa tanggal lahir dan nomor atlet (K49, K52); panel tanpa pendaftaran baru, nomor atlet atlet tambahan, saringan status rekap (K58–K60); sesi lain keluar saat ganti sandi dan halaman galat ramah (K67); CI GitHub (K46); cadangan harian (K66); juknis tidak ditunggu (K55). Bagian yang berubah: 5, 6.3, 6.5, 6.6, 6.8, 6.9, 8, 9, 13, 14, 15, 16, 17 |
| Revisi 5 | Kelompok umur ditentukan jenjang dulu, baru tahun lahir; rentang tahun lahir boleh beririsan antar-jenjang, tidak dalam satu jenjang (K95). Bagian yang berubah: 6.4, 17 |
| Revisi 6 | Data sekolah dari Sekolah Kita dan menu Sekolah di panel untuk memberi nama pembeda (K97); kelompok umur dan nomor lomba yang sudah dimatikan tetap boleh dipertahankan saat admin mengubah pendaftaran (K98); usulan nama sekolah dari pendaftar (K99). Disesuaikan juga dengan keputusan yang sudah berjalan: penulisan rupiah (K80), bukti transfer diunggah ofisial dan pendaftaran baru masuk bersama bukti transfer (K83, K90), alamat dan isi bukti PDF serta halaman cek (K77, K78, K87–K89, K91). Bagian yang berubah: 5, 6.2, 6.7, 7, 8, 9, 11, 15, 17 |
| Revisi 16 | Perintah `php spark pembaruan:periksa` setelah `git pull`: memeriksa Composer, migrasi, data sekolah, penggabungan pendaftaran, dan aset kecil, lalu menyebut perintah yang perlu dijalankan (K115). Bagian yang berubah: 15 |
| Revisi 15 | Sekolah bernama sama bisa diberi nama desa sekaligus dari menu Sekolah; pendaftaran yang memakai nama lama ikut diganti, dan nama tiap sekolah tetap bisa diubah lagi (K114). Bagian yang berubah: 5, 9 |
| Revisi 14 | Istilah mengikuti juknis: nomor atlet (BIB), ofisial, pertemuan teknik (technical meeting), dan kategori mixed; tanggal tanpa pukul pada keterangan pengurutan dan kaki bukti PDF (K113). Bagian yang berubah: semua bagian yang menyebut istilah itu |
| Revisi 13 | Nomor atlet bisa diurutkan ulang per sekolah oleh admin sebelum pertemuan teknik (technical meeting), dengan keterangan di cek, bukti PDF, dan panel (K111). Juknis resmi diterima dan dicocokkan: KU 12 lahir 2014–2015, juknis jadi berkas unduhan bawaan, jadwal pertemuan teknik dan waktu lomba data awal, istilah "kelompok umur" di halaman depan (K112). Bagian yang berubah: 6.4, 6.9, 8, 9, 10, 17, lampiran |
| Revisi 12 | Pendaftaran Lunas dengan NPSN sama digabung otomatis menjadi satu pendaftaran: ID yang Lunas paling dulu dipakai, ID dan kode QR bukti lama diarahkan ke pendaftaran gabungan, bukti PDF menulis "Gabungan dari", dan perintah sekali jalan untuk data yang sudah ada (K109). Bagian yang berubah: 6.8, 6.9, 8, 9, 11, 15 |
| Revisi 11 | Regu tunggal (satu-satunya regu sekolah di satu nomor) ditulis tanpa nama regu di wizard, cek, dan bukti; pilihan regu di wizard tersembunyi selama sekolah baru punya satu regu, dengan tautan "Daftarkan regu lain", dan tidak pernah tampil bila nomor hanya membolehkan satu regu; bukti PDF dengan kepala dan kaki di setiap halaman, data pendaftaran bergaya formulir resmi (nama sekolah sendiri di atas), dan sel gabungan per regu (K108). Bagian yang berubah: 6.6, 7, 8 |
| Revisi 10 | Regu per sekolah tidak dibatasi secara bawaan: data awal batas regu kosong, daftar nama regu bawaan kosong (huruf A, B, C, … berurutan); awalan nama regu ("Regu", "Tim", …) diatur admin; bukti PDF beregu satu baris per anggota dengan kolom nomor atlet dan peran; daftar sementara beregu dirapikan (K107). Bagian yang berubah: 6.6, 7, 8, lampiran |
| Revisi 9 | Nama regu diatur admin di Pengaturan > Pendaftaran: Ketentuan admin (daftar nama dari admin, bawaan A, B, C) atau Bebas (diketik ofisial); tampilan nomor beregu, inti, dan cadangan di wizard dirapikan (K106). Bagian yang berubah: 6.6, 7, 11 |
| Revisi 8 | Satu sekolah boleh mendaftarkan lebih dari satu regu per nomor beregu (Regu A, B, C, …) dengan batas regu per nomor lomba; setiap anggota ditandai inti atau cadangan; biaya beregu per regu; susulan memilih regu dan boleh menurunkan inti lama menjadi cadangan; tim lama menjadi Regu A (K105, menggantikan K07). Bagian yang berubah: 6.6, 6.7, 7, 8, 9, 11, lampiran |
| Revisi 7 | Disesuaikan dengan keputusan Tahap 7 yang belum masuk: aset kecil dan penanda versi (K72, K86), data awal lengkap (K73), rincian biaya tersembunyi bawaan (K74), tampilan halaman depan (K75, K81–K86, K92, K93), kolom No, pilih massal, dan Backup data di panel (K76), tombol Halaman depan dan rekap gabungan (K85), validasi dan kenyamanan wizard (K88, K89), reset nomor atlet setelah uji coba (K94); Backup data membawa usulan nama dan sekolah yang disunting. Bagian yang berubah: 4, 6.9, 7, 9, 10, lampiran |
| Acara | MilkLife Athletics Challenge Seri 2 – 2026, Supersoccer Arena, Kudus, 9–12 Desember 2026 |
| Dasar | Wawancara sesi 1 (lihat `agent/KEPUTUSAN.md`) dan flyer di `sumber/flyer/` |

Dokumen ini menjelaskan *apa* yang dibangun. *Cara* membangunnya per tahap ditulis terpisah sebagai rencana implementasi setelah rancangan ini disetujui.

## 1. Tujuan

Sekolah se-Karesidenan Pati mendaftarkan atletnya secara daring, ofisial bisa memeriksa rekap pendaftarannya, dan panitia mendapat rekap siap pakai.

Berhasil bila:

- ofisial non-teknis bisa menyelesaikan pendaftaran dari ponsel tanpa bantuan,
- biaya, kelompok umur, dan nomor atlet dihitung sistem tanpa salah,
- panitia bisa mengunduh rekap Excel/PDF tanpa mengolah ulang data.

## 2. Cakupan

**Termasuk:** halaman depan, pendaftaran, cek pendaftaran, unduhan, bukti pendaftaran PDF, panel admin (pengaturan, data pendaftaran, rekap, ekspor).

**Tidak termasuk:** pelaksanaan lomba (hasil, seri, jadwal lomba per nomor), pembayaran otomatis, akun untuk ofisial, tema gelap, banyak acara sekaligus dalam satu pemasangan.

## 3. Pengguna

| Pengguna | Akses | Yang dilakukan |
|---|---|---|
| Ofisial sekolah | Tanpa akun | Mendaftar, mengirim konfirmasi pembayaran lewat WA, mengunduh bukti, memeriksa rekap |
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
| Minify | `matthiasmullie/minify` untuk CSS, JS, dan SVG lewat `php spark aset:minify`; berkas asli tetap ada (K72). Alamat aset diberi penanda versi `?v=<waktu ubah berkas>` supaya Cloudflare dan peramban langsung memakai berkas baru (K86) |
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
| `/bukti/{kode-acak}/{id-pendaftaran}` | Bukti pendaftaran PDF; kode acak 24 huruf supaya alamatnya tidak bisa ditebak (K77) |

**Admin** (semua di bawah `/admin`)

| URL | Isi |
|---|---|
| `/admin/masuk` | Halaman masuk |
| `/admin/dasbor` | Ringkasan angka dan daftar kelengkapan pengaturan |
| `/admin/pendaftaran` | Daftar dan rincian pendaftaran, bukti bayar, tandai lunas, ubah, hapus. Tidak ada pendaftaran baru dari panel (K58) |
| `/admin/atlet` | Daftar semua atlet |
| `/admin/rekap` | Rekap per nomor lomba, tim beregu, per sekolah, per kelompok umur, keuangan |
| `/admin/sekolah` | Data sekolah: daftar dengan saringan nama kembar, ubah nama dan data lain (K97) |
| `/admin/sekolah/nama-desa` | Tambahkan nama desa ke semua sekolah bernama sama sekaligus (K114) |
| `/admin/sekolah/usulan` | Usulan nama sekolah dari pendaftar: setujui atau tolak (K99) |
| `/admin/pengaturan` | Daftar semua halaman pengaturan beserta tanda lengkap atau belum |
| `/admin/pengaturan/acara` | Nama, seri, tahun, tempat, tanggal acara |
| `/admin/pengaturan/pendaftaran` | Jadwal buka-tutup, buka manual, tampilan usia, batas-batas |
| `/admin/pengaturan/kelompok-umur` | Batas tahun lahir, jenjang sekolah yang boleh ikut, biaya individu, awalan nomor atlet |
| `/admin/pengaturan/nomor-lomba` | Nomor lomba, jenis, jumlah anggota, biaya beregu |
| `/admin/pengaturan/pembayaran` | Bank, rekening, WA pemilik rekening, isi pesan |
| `/admin/pengaturan/halaman-depan` | Gambar, logo, judul, teks, tombol, footer |
| `/admin/pengaturan/jadwal` | Jadwal sosialisasi, pertemuan teknik, dan lainnya |
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
- minimal satu kelompok umur aktif, dan setiap kelompok umur aktif punya batas tahun lahir (minimal salah satu dari tahun lahir paling awal atau paling akhir), minimal satu jenjang sekolah yang boleh ikut, biaya individu, dan minimal satu awalan nomor atlet,
- minimal satu nomor lomba aktif pada kelompok umur aktif, dan setiap nomor beregu aktif punya jumlah inti, batas anggota, dan biaya beregu (nomor mixed juga jumlah putra dan putri minimal),
- data rekening (bank, nomor rekening, nama pemilik) dan nomor WA pembayaran.

Bila belum lengkap atau belum dibuka, halaman depan menampilkan keterangan ramah, misalnya "Pendaftaran sedang disiapkan" atau "Pendaftaran dibuka 1 Oktober 2026". Dasbor admin menampilkan daftar centang hal yang belum lengkap.

### 6.2 Sekolah

- Ofisial memilih jenjang (SD/MI, SMP/MTs, SMA/SMK/MA) dan kabupaten (Blora, Rembang, Pati, Kudus, Jepara).
- Sekolah Luar Biasa (SLB) tidak ikut (K28): NPSN SLB ditolak dengan pesan yang jelas.
- Ofisial mengetik NPSN, sistem menampilkan nama sekolah beserta desa dan kecamatannya dari data referensi (K97). Bila tidak ditemukan, nama sekolah diisi manual.
- Data referensi diambil dari Sekolah Kita Kemendikdasmen (4.766 sekolah, 14 November 2026; K97). Nama resminya tidak selalu unik: banyak madrasah memakai nama umum seperti "MIS MUHAMMADIYAH". Sekolah selalu dikenali lewat NPSN, dan admin memberi nama pembeda di panel (9).
- **Usulan nama** (K99): bila nama yang tampil juga dipakai sekolah lain, wizard menonjolkan tawaran mengusulkan nama lengkap, misalnya dengan nama desa; untuk sekolah lain tersedia tautan "Nama sekolah kurang lengkap?". Pendaftaran itu langsung memakai nama usulan, lalu admin memutuskan. Disetujui: nama di data sekolah dan semua pendaftaran dengan NPSN itu diganti. Ditolak: pendaftaran pengusul kembali memakai nama dari data sekolah.
- Sekolah tanpa NPSN tetap bisa mendaftar dengan nama sekolah saja.
- Satu NPSN boleh mendaftar lebih dari sekali. Semua pendaftaran dengan NPSN yang sama dianggap satu sekolah.
- Pendaftaran tanpa NPSN berdiri sendiri dan hanya bisa dicek lewat ID pendaftaran.

### 6.3 Ofisial

Nama, nomor WA, dan foto. **Foto wajib diisi** (K50); foto diperkecil di peramban, disimpan di luar folder publik, dan hanya dilihat admin. Jumlah minimal 1 dan maksimal 5; kedua batas bisa diubah admin.

### 6.4 Atlet

- Nama lengkap (otomatis huruf besar), NISN tepat 10 digit, tanggal lahir, jenis kelamin (laki-laki/perempuan).
- **NISN yang sudah terdaftar ditolak**, termasuk pada pendaftaran susulan. Perubahan untuk atlet yang sudah terdaftar dilakukan admin.
- **Kelompok umur ditentukan otomatis dari jenjang sekolah lalu tahun lahir** (K95, K36). Tahun lahir dibaca tanpa memperhatikan bulan dan tanggal. Tiap kelompok umur punya tahun lahir paling awal dan paling akhir; salah satunya boleh kosong, artinya tidak dibatasi di sisi itu (contoh KU 8: lahir 2019 ke atas).
- Usia hanya ditampilkan sebagai keterangan, tidak menentukan apa pun (K37). Usia dihitung pada tanggal mulai acara; tampilan "tahun" atau "tahun dan bulan" diatur admin. Tidak ada lagi tanggal patokan usia.
- **Jenjang dulu, baru tahun lahir** (K95). Admin mencentang jenjang yang boleh ikut pada tiap kelompok umur (K23, K24); tidak ada nilai tertulis di kode. Dari jenjang sekolah pendaftaran, hanya kelompok umur yang dicentang untuk jenjang itu yang dipertimbangkan; di antaranya dicari yang rentang tahun lahirnya cocok.
- Bila tidak ada yang cocok, atlet ditolak dengan pesan yang menyebut kelompok umur yang dibuka untuk jenjang itu beserta tahun lahirnya, dan kelompok umur jenjang lain yang menerima tahun lahir atlet bila ada.
- **Rentang tahun lahir boleh beririsan antar-jenjang, tetapi tidak dalam satu jenjang** (K95). Contoh: bila KU 12 (SD/MI) dan KU 15 (SMP/MTs) sama-sama menerima kelahiran 2012, atlet lahir 2012 dari SD masuk KU 12 dan dari SMP masuk KU 15. Halaman pengaturan menolak rentang yang beririsan dengan kelompok umur aktif lain yang dibuka untuk jenjang yang sama; kelompok umur yang belum dicentang jenjangnya tidak dibandingkan.
- Batas tahun lahir diisi data awal sesuai ketetapan pemilik (K36) dan juknis resmi (KU 12 lahir 2014–2015, K112), dan bisa diubah admin.

### 6.5 Nomor lomba

- Setiap nomor lomba milik satu kelompok umur, berjenis individu atau beregu, dan berkategori putra, putri, atau mixed.
- Pilihan nomor lomba baru muncul setelah kelompok umur atlet diketahui dan kelompok umur itu dibuka untuk jenjang sekolah pendaftaran, dan hanya yang sesuai kelompok umur dan jenis kelaminnya.
- Setiap atlet wajib ikut minimal 1 nomor (individu atau beregu).
- Maksimal 2 nomor individu per atlet (bisa diubah admin). Nomor beregu tidak dibatasi.
- Jumlah atlet satu sekolah pada satu nomor individu **tidak dibatasi** (K53). Admin tetap bisa memasang batas di pengaturan Pendaftaran (K51); batas itu dihitung bersama pendaftaran sebelumnya dari NPSN yang sama, sedangkan pendaftaran tanpa NPSN dihitung sendiri. Kosong berarti tidak dibatasi.

### 6.6 Tim beregu

- **Satu sekolah boleh punya lebih dari satu regu per nomor beregu** (K105). Batas regu per sekolah diatur admin **per nomor lomba** ("Regu per sekolah paling banyak"); kosong berarti tidak dibatasi, dan data awalnya kosong (K107). Regu yang sudah terdaftar tetap boleh dipakai walau batas, mode, atau daftar namanya diubah.
- **Nama regu** (K106) diatur admin di Pengaturan > Pendaftaran, satu pilihan untuk semua nomor beregu:
  - **Ketentuan admin** (bawaan): ofisial memilih dari daftar nama yang ditulis admin, dipisah koma. Yang boleh dipakai adalah nama pertama di daftar sebanyak batas regu nomor itu. Daftar bawaannya kosong: ofisial memilih huruf A, B, C, … berurutan, dan wizard hanya menawarkan regu yang sudah dipakai ditambah satu regu baru berikutnya (K107).
  - **Bebas**: ofisial mengetik nama regu sendiri, misalnya "Merah" atau "Putra 1", paling banyak 30 huruf; banyaknya regu tetap dibatasi nomor lomba.
  - **Awalan nama regu** (K107): kata di depan nama regu, bawaan "Regu", bisa diganti admin misalnya "Tim", sehingga tampil "Tim A" di wizard, cek, bukti PDF, rekap, dan panel. Panjang 2–15 huruf.
  - Nama dirapikan otomatis: kata "Regu" atau awalan di depan dibuang dan huruf awal tiap kata kapital, sehingga "merah" dan "MERAH" adalah regu yang sama.
  - Nama regu selalu tersimpan; wizard mengisinya sendiri (regu pertama "A"), dan server menolak nama regu yang kosong.
- **Regu tunggal** (K108): bila sekolah hanya punya satu regu di satu nomor beregu (dihitung dari semua pendaftaran dengan NPSN yang sama; tanpa NPSN, pendaftaran itu saja), wizard, halaman cek, dan bukti PDF menulis nama nomornya saja, tanpa "Regu A". Panel admin dan rekap tetap menulis nama regu. Bila sekolah kemudian menambah regu kedua, nama regu kembali ditulis untuk semua regunya.
  - Nomor yang hanya membolehkan satu regu untuk sekolah itu (batas regu 1, atau daftar nama admin berisi satu nama) tidak menampilkan pilihan regu di wizard; ofisial hanya memilih Inti atau Cadangan.
  - Pada nomor lain, pilihan regu disembunyikan selama sekolah baru punya satu regu di nomor itu: atlet otomatis masuk regu itu (sebagai cadangan bila inti sudah lengkap) selama masih ada tempat. Tautan **"Daftarkan regu lain"** membuka pilihan regu dan langsung memilih regu baru.
- Ofisial memilih regu untuk setiap atlet di nomor beregu dan menandainya **Inti** atau **Cadangan**. Satu regu berisi **tepat** sejumlah inti nomor tersebut (4, 5, atau 8), ditambah cadangan sampai batas anggota per regu yang diatur admin (data awal dua kali jumlah inti).
- Nomor mixed: admin menetapkan jumlah putra dan jumlah putri minimal, diperiksa pada atlet inti. Urutan lari tidak diatur sistem.
- Pendaftaran susulan dari NPSN yang sama memilih regu: menambah anggota regu yang sudah ada (tanpa biaya regu lagi; anggota lama dan baru bersama-sama tidak boleh melewati batas anggota) atau membuat regu baru (lengkap, dengan biaya regu). Atlet susulan **boleh menjadi inti** dengan menurunkan inti lama menjadi cadangan, walau inti lama itu ada di pendaftaran lain; wizard menampilkan anggota regu lama dengan kotak "Jadikan cadangan".
- **Ofisial mengonfirmasi sendiri setiap regu susulan** (K57): wizard menampilkan kotak centang per regu yang menyebut bahwa atletnya masuk ke regu sekolah yang sudah terdaftar tanpa biaya regu. Server menolak kiriman yang belum dikonfirmasi. Admin yang mengubah pendaftaran di panel tidak perlu konfirmasi ini.
- **Tim tanpa pembayar** (K68): bila pendaftaran yang membayar biaya regu dihapus, atau regunya dihapus lewat Ubah, sementara anggota susulan di pendaftaran lain tetap ada, biaya regu **tidak dipindah otomatis**. Regu itu ditandai di rincian pendaftaran, dasbor, dan rekap keuangan, dan admin membebankan biaya regunya ke salah satu pendaftaran anggotanya lewat Ubah (9). Jendela hapus dan simpan Ubah memperingatkan bila tindakannya meninggalkan regu tanpa pembayar.
- Tim yang terdaftar sebelum ada regu menjadi Regu A; atlet yang paling awal didaftarkan sebanyak jumlah inti menjadi inti, sisanya cadangan (K105).

### 6.7 Biaya

- **Individu:** biaya per atlet per nomor, besarnya mengikuti kelompok umur atlet. Dua nomor individu berarti dua kali biaya.
- **Beregu:** biaya per regu, besarnya ditetapkan pada tiap nomor lomba. Dua regu di nomor yang sama berarti dua kali biaya (K105).
- Biaya disimpan bersama pendaftaran saat pendaftaran dibuat. Perubahan tarif oleh admin tidak mengubah pendaftaran yang sudah ada.
- Semua angka dihitung ulang di server. Angka dari peramban tidak dipercaya.
- Rupiah ditampilkan tanpa angka di belakang koma, dengan spasi setelah Rp, contoh `Rp 150.000` (K80).

### 6.8 ID pendaftaran

Format: `MAC` + seri + kode jenjang + nomor urut 5 digit + 2 digit tahun. Contoh: `MAC2A0000126`.

- Kode jenjang: `A` untuk SD/MI, `B` untuk SMP/MTs, `C` untuk SMA/SMK/MA.
- Nomor urut berlaku untuk seluruh pendaftaran (bukan per jenjang) dan diambil dari nomor baris basis data. Nomor bisa melompat bila ada pendaftaran yang gagal tersimpan atau dihapus.
- **ID tidak berubah saat admin mengubah pendaftaran**, juga bila jenjang sekolahnya diubah (K63). Kode jenjang di ID tetap mengikuti data saat mendaftar, supaya ID yang sudah dipegang sekolah, tercetak di bukti, dan dipakai di halaman cek tidak berganti.
- ID pendaftaran yang sudah digabung ke pendaftaran lain (6.9, K109) tidak dipakai lagi untuk pendaftaran baru, tetapi tetap dikenali: halaman cek dan kode QR bukti lama mengarah ke pendaftaran gabungannya.

### 6.9 Status pembayaran dan nomor atlet

- Status awal: **Menunggu Konfirmasi Pembayaran**. Admin mengubahnya menjadi **Lunas** setelah memeriksa transfer.
- **Nomor atlet dibuat saat pendaftaran ditandai Lunas**, satu nomor per atlet, tidak bergantung pada nomor lomba.
- Nomor atlet 4 digit: 1 digit awalan + 3 digit urut (`001`–`999`).
- Tiap kelompok umur boleh punya lebih dari satu awalan. Bila awalan pertama penuh, nomor berlanjut ke awalan berikutnya.
- Bila semua awalan sebuah kelompok umur penuh, penandaan Lunas dibatalkan seluruhnya dan admin diminta menambah awalan.
- Nomor atlet tidak dipakai ulang. Mengembalikan status ke Menunggu tidak menghapus nomor atlet.
- Satu-satunya pengecualian (K94): perintah `php spark nomor-dada:reset` mengosongkan semua nomor atlet dan penghitung awalan setelah uji coba, dan pendaftaran Lunas kembali Menunggu. Perintah meminta konfirmasi dan tidak boleh dijalankan setelah nomor atlet dibagikan ke peserta.
- **Bila admin mengubah pendaftaran yang sudah Lunas dan totalnya berubah (naik atau turun), status kembali ke Menunggu Konfirmasi Pembayaran** (K32). Admin menandai Lunas lagi setelah selisihnya diurus panitia di luar sistem. Atlet yang sudah punya nomor atlet tetap memegangnya; atlet baru mendapat nomor saat ditandai Lunas lagi. Jendela konfirmasi simpan menyebut akibat ini.
- **Bila admin menambah atlet ke pendaftaran Lunas dan totalnya tetap** (misalnya tambahan anggota tim beregu), status tetap Lunas dan atlet baru **langsung mendapat nomor atlet saat disimpan** (K59).
- Tidak ada status "dibatalkan". Pendaftaran yang batal dihapus admin, sehingga NISN-nya bisa didaftarkan lagi.
- **Pendaftaran Lunas dengan NPSN sama digabung otomatis menjadi satu** (K109). Begitu pendaftaran kedua sebuah sekolah ditandai Lunas (satu per satu, massal, atau karena admin mengganti NPSN pendaftaran Lunas di Ubah), isinya pindah ke pendaftaran sekolah itu yang **Lunas paling dulu**, lalu pendaftaran yang pindah dihapus:
  - Atlet (dengan nomor atlet dan nomor lombanya), regu beregu, bukti bayar, dan usulan nama sekolah pindah. Regu yang sama dijumlahkan biaya regunya. Ofisial dengan nomor WA yang sama ditulis sekali.
  - Subtotal dan total dijumlahkan. ID, nama sekolah, dan waktu lunas pendaftaran tujuan tetap.
  - ID dan kode bukti lama dicatat, sehingga ID lama tetap bisa dicari di halaman cek dan kode QR di bukti yang sudah dicetak membuka bukti gabungan (8).
  - Pendaftaran yang masih Menunggu tidak ikut digabung sampai ia sendiri Lunas. Pendaftaran tanpa NPSN tidak pernah digabung.
  - Penggabungan tidak bisa dipisah lagi. Bila pendaftaran gabungan dikembalikan ke Menunggu, diubah, atau dihapus, seluruhnya ikut.
  - Pesan setelah tandai Lunas menyebut ID mana yang digabung ke mana, dan admin dibawa ke rincian pendaftaran gabungan.
  - Penggabungan memang tidak perlu dipisah: aturan ini untuk ofisial yang mendaftar berulang kali untuk satu sekolah, dan selalu lewat admin yang menandai Lunas (K110).
- **Nomor atlet bisa diurutkan ulang per sekolah** (K111). Nomor atlet dibagikan saat pertemuan teknik; sebelum itu admin bisa membuat ulang semua nomor lewat tombol "Urutkan nomor atlet" di halaman Atlet, supaya nomor satu sekolah berurutan walau pendaftarannya masuk tidak berurutan.
  - Hanya atlet pendaftaran Lunas yang diberi nomor, mulai lagi dari `001` di awalan pertama tiap kelompok umur (berlanjut ke awalan berikutnya bila penuh).
  - Sekolah (NPSN; tanpa NPSN: pendaftaran itu sendiri) diurutkan menurut atlet yang paling dulu didaftarkan; di dalam satu sekolah atlet diurutkan menurut urutan mendaftar.
  - Nomor atlet pendaftaran yang kembali Menunggu dikosongkan dan dibuat lagi saat ditandai Lunas. Pendaftaran yang Lunas sesudahnya mendapat nomor lanjutan, jadi admin bisa mengurutkan lagi sebelum pertemuan teknik.
  - Admin mengetik URUTKAN dan menyetujui jendela konfirmasi. Bila awalan sebuah kelompok umur tidak cukup, tidak ada yang berubah.
  - Ini pengecualian kedua aturan nomor atlet tidak dipakai ulang (selain K94), dan tidak boleh dijalankan setelah nomor atlet dibagikan. `nomor-dada:reset` juga menghapus keterangan pengurutan.
  - Pendaftaran Lunas ber-NPSN sama yang sudah ada sebelum aturan ini dipasang digabung sekali dengan perintah `php spark pendaftaran:gabung` (15).

## 7. Alur pendaftaran

Wizard satu halaman dengan pil langkah bercentang:

1. **Data Sekolah** — jenjang, kabupaten, NPSN atau nama sekolah, dan usulan nama bila nama sekolah kurang lengkap (6.2). Jenjang yang dipilih di sini menentukan kelompok umur yang boleh diikuti atlet (6.4).
2. **Ofisial** — 1 sampai 5 orang.
3. **Atlet** — formulir atlet dan daftar sementara.
4. **Biaya** — rincian dan konfirmasi.
5. **Pembayaran** — rekening, unggah bukti transfer, kirim pendaftaran, selesai.

**Langkah 3.** Nomor beregu tampil sebagai kartu: nama nomor, lalu keterangan singkat per regu (jumlah inti, cadangan, syarat mixed, batas regu, biaya per regu). Nomor yang dicentang menampilkan pilihan regu (daftar admin, atau kotak isian pada mode Bebas) dan tombol Inti/Cadangan; pilihan bawaannya regu yang intinya belum lengkap, lalu regu baru (K105, K106). Selama sekolah baru punya satu regu di nomor itu, pilihan regu disembunyikan dan hanya tombol Inti/Cadangan yang tampil, dengan tautan "Daftarkan regu lain"; nomor yang hanya membolehkan satu regu tidak menampilkan tautan itu (K108). Setelah atlet ditambahkan, daftar sementara langsung terbentuk dalam dua bagian: daftar individu dan daftar regu beregu. Setiap regu satu kartu: nama nomor, nama regu (tidak ditulis untuk regu tunggal, K108), status (Lengkap/Belum lengkap), jumlah atlet, dan biaya regu; anggotanya satu baris ringkas per atlet bernomor urut, dikelompokkan **Inti** ("4 dari 4") lalu **Cadangan** ("1 dari paling banyak 4") (K107). Setiap baris punya tombol ubah dan hapus. Sebelum lanjut, sistem memeriksa kelengkapan setiap regu.

**Langkah 4.** Dua tabel terpisah:

- Individu: No, Nama, NISN, Kelompok Umur, Nomor Lomba, Biaya, lalu subtotal individu.
- Beregu: No, Nomor Lomba Beregu (dengan regu bila sekolah punya lebih dari satu regu di nomor itu, jumlah inti dan cadangan), Biaya, lalu subtotal beregu.

Di bawahnya total pembayaran: subtotal individu, subtotal beregu, dan jumlah keduanya. Langkah ini belum menyimpan apa pun (K90).

**Langkah 5.** Menampilkan logo bank, nomor rekening, nama pemilik rekening, dan total. Ofisial mentransfer, memilih foto atau tangkapan layar bukti transfer, lalu menekan **Kirim Pendaftaran** dan mengonfirmasi. **Pendaftaran baru masuk sistem saat itu, bersama bukti transfernya** (K90): tanpa bukti tidak ada yang tersimpan, dan isian tetap di draf sehingga ofisial bisa kembali lain waktu dan tinggal mengunggah bukti. Setelah terkirim, ID pendaftaran dibuat, draf dikosongkan, dan layar selesai menampilkan tombol cetak bukti, salin ID, dan konfirmasi lewat WA ke pemilik rekening (pesan berisi nama sekolah, NPSN, ID pendaftaran, subtotal individu, subtotal beregu, dan total). Status tetap Menunggu Konfirmasi Pembayaran sampai panitia memeriksa transfernya.

**Draf.** Isian tersimpan di peramban (`localStorage`) pada setiap perubahan, sehingga tidak hilang saat halaman dimuat ulang. Foto ofisial diperkecil di peramban (sisi terpanjang 800 piksel, JPEG) dan ikut tersimpan di draf.

**Validasi.** Setiap isian diperiksa di peramban untuk kenyamanan dan diperiksa lagi seluruhnya di server. Isian yang ditandai salah kembali normal begitu mulai diubah (K88). Penyimpanan berlangsung dalam satu transaksi: tersimpan semua atau tidak sama sekali.

**Kenyamanan** (K89): tindakan yang tidak bisa dibatalkan (hapus nomor atau atlet, mulai dari awal, kirim pendaftaran, kirim bukti bayar) memakai jendela konfirmasi, bukan kotak bawaan peramban. Isian tanggal memakai pemilih Tanggal, Bulan (nama bulan), dan Tahun. Bilah muat tipis di atas halaman menyala saat wizard menghubungi server.

## 8. Cek pendaftaran dan bukti

**Cek.** Ofisial memasukkan NPSN atau ID pendaftaran.

- NPSN menampilkan semua pendaftaran sekolah itu berurutan per pendaftaran: data sekolah sekali di atas, satu kartu per pendaftaran, dan total keseluruhan di bawah (K49).
- ID pendaftaran menampilkan satu pendaftaran. ID lama yang sudah digabung (6.9) menampilkan pendaftaran gabungannya dengan keterangan "Pendaftaran … sudah digabung ke …" (K109).
- Data tampil sesuai MoU: sekolah, ofisial, atlet (nama, NISN, jenis kelamin, kelompok umur, nomor lomba), regu beregu beserta anggotanya (inti dan cadangan, K105), biaya, dan status pembayaran.
- **Tanggal lahir tidak ditampilkan** (K52); tetap tersimpan dan terlihat di panel admin. Nomor atlet tampil setelah pendaftaran Lunas (K88).
- Pendaftaran yang belum Lunas punya kotak unggah bukti transfer tambahan (K83).
- Bila nomor atlet sudah diurutkan ulang panitia (6.9, K111), kartu pendaftaran Lunas menulis keterangan di atas tabel atlet: kapan nomor diurutkan ulang, dan bahwa nomor di bukti yang dicetak sebelumnya tidak berlaku.
- Nama nomor lomba ditulis bersama kategorinya, misalnya "Lari 60 m Putra" atau "Estafet 8 × 50 m Mixed".
- Foto ofisial hanya tampil di panel admin.

**Bukti PDF.** Kertas F4 tegak (215 × 330 mm) (K77).

- Kepala: logo persegi MilkLife, nama acara, seri, dan tahun, dengan garis oranye di bawahnya. **Kepala diulang di setiap halaman**, begitu juga kaki halaman (ID pendaftaran, waktu cetak, nomor halaman) (K108).
- Data pendaftaran bergaya formulir resmi (K108): **nama sekolah di blok sendiri paling atas** karena bisa panjang, lalu tabel berlabel berisi ID pendaftaran (dengan tulisan kecil "Gabungan dari …" bila ada pendaftaran yang digabung ke situ, K109), tanggal pendaftaran, NPSN, jenjang, kabupaten/kota, status pembayaran, dan ofisial. Ofisial ditulis sebaris: nama dan nomor WA, dipisah koma bila lebih dari satu (K52, K83).
- Tabel individu satu baris per atlet per nomor lomba (dengan kategori), dan tabel beregu **satu baris per anggota regu**: No, nomor lomba, regu, dan biaya regu berupa **sel gabungan setinggi anggota regunya**, lalu nama atlet, NISN, nomor atlet, dan peran (Inti/Cadangan) di kolom masing-masing; inti dulu (K105, K107, K108). Kolom regu hanya ada bila sekolah punya lebih dari satu regu di salah satu nomor; regu tunggal tidak ditulis nama regunya. Lalu total pembayaran.
- Tanggal lahir tidak dicetak. Nomor atlet dicetak setelah pendaftaran Lunas, di tabel individu dan di belakang nama anggota tim; sebelum Lunas kolomnya kosong (K87).
- Bila nomor atlet sudah diurutkan ulang panitia (6.9, K111), bukti pendaftaran Lunas menulis keterangannya beserta waktunya di dasar halaman terakhir, di atas keterangan keaslian.
- Status pembayaran berupa stempel miring di bagian akhir: hijau "LUNAS / PEMBAYARAN DITERIMA" atau oranye "MENUNGGU / KONFIRMASI PANITIA" (K78, K91).
- Kode QR berisi alamat bukti di pojok kanan bawah halaman terakhir, dengan keterangan di kirinya untuk memeriksa keaslian (K89). Kode QR bukti pendaftaran yang sudah digabung diarahkan ke bukti pendaftaran gabungannya (K109).
- Nama berkas memakai seluruh nama acara tanpa spasi, ditambah ID pendaftaran dan status (K87): `BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_<NamaSekolah>_2026_<ID>_<Status>.pdf`.

Nama acara, seri, dan tahun pada bukti dan nama berkas diambil dari pengaturan acara.

## 9. Panel admin

- **Tata letak:** lebar penuh, menu samping, kepala halaman menempel di atas dengan tombol "Halaman depan" yang membuka halaman depan di tab baru (K85), menu menempel di bawah pada ponsel, tanpa teks footer.
- **Tabel:** data dari titik akhir JSON, dengan kolom No otomatis (K76), halaman, pencarian, saringan pada setiap kolom yang relevan, dan pengurutan. Setiap tabel punya tombol unduh Excel yang mengikuti saringan aktif.
- **Keadaan kosong:** pesan ramah yang menjelaskan apa yang belum ada dan langkah berikutnya.
- **Pemuatan:** bilah kemajuan di atas halaman saat data dimuat.
- **Tindakan berisiko** (hapus, tandai lunas, kembalikan ke Menunggu): jendela konfirmasi yang menyebut apa yang akan terjadi. Daftar pendaftaran punya pilih massal untuk ketiga tindakan itu, paling banyak 100 sekaligus, dengan aturan yang sama seperti di rincian (K76).
- **Backup data** (K76, K77): tombol di daftar pendaftaran mengunduh satu zip berisi data pendaftaran, foto ofisial, bukti bayar, usulan nama sekolah (K99), sekolah yang namanya disunting admin (K97), dan ID lama pendaftaran gabungan (K109), untuk dimasukkan ke server baru dengan `php spark pendaftaran:impor`. Cadangan harian lengkap tetap lewat `cadangan:buat` (15).
- **Urutkan nomor atlet** (K111): tombol di halaman Atlet membuka halaman yang meringkas atlet Lunas per kelompok umur, kecukupan awalan, dan waktu serta admin terakhir yang mengurutkan (6.9). Halaman Atlet dan rincian pendaftaran Lunas menulis waktu pengurutan terakhir.
- **Pendaftaran gabungan** (K109): rincian menulis "Gabungan dari" dengan ID lamanya, dan pencarian daftar pendaftaran menemukan pendaftaran gabungan lewat ID lama.
- **Tanpa pendaftaran baru** (K58): panel hanya melihat, menandai Lunas, mengubah, dan menghapus pendaftaran yang sudah ada. Sekolah yang terlambat didaftarkan lewat wizard setelah admin memakai buka manual (6.1).
- **Ubah pendaftaran:** admin bisa mengubah data sekolah, ofisial, atlet, nomor lomba, serta regu dan Inti/Cadangan tiap atlet beregu. Aturan pada bagian 6 tetap berlaku dan biaya dihitung ulang; regu yang sudah terdaftar boleh disimpan dengan inti kurang dari jumlah inti (tidak lebih), supaya perubahan lain tetap bisa disimpan, dan regu itu ditandai di rincian dan rekap tim beregu (K105). ID pendaftaran tidak berubah (6.8). Akibat pada status dan nomor atlet mengikuti 6.9 (K32, K59).
  - Kelompok umur dan nomor lomba yang sudah dimatikan tetap boleh dipertahankan untuk atlet yang sudah memakainya, ditandai "Sudah ditutup", sehingga perubahan lain (misalnya nomor WA) tetap bisa disimpan. Atlet baru dan nomor baru tidak bisa memakainya (K98).
  - Nama sekolah tersimpan yang berbeda dari data sekolah, misalnya nama usulan yang belum diputuskan, dipertahankan selama NPSN tidak diubah.
- **Tim tanpa pembayar** (K68): rincian pendaftaran memberi peringatan pada regu yang kehilangan pembayar biaya regunya, dasbor menampilkan jumlahnya bila ada, dan rekap keuangan mendaftarnya beserta pendaftaran anggotanya. Di Ubah, regu itu punya kotak centang "Bebankan biaya regu ke pendaftaran ini" dengan tarif saat ini; bila pendaftarannya Lunas dan totalnya berubah, berlaku 6.9.
- **Bukti bayar** (K34, K83, K90): ofisial mengunggah bukti transfer saat mengirim pendaftaran, dan bisa menambah bukti lewat Cek Pendaftaran selama belum Lunas. Admin juga bisa mengunggah dari rincian pendaftaran. Paling banyak 5 gambar per pendaftaran. Gambar dikecilkan otomatis sebelum disimpan (sisi terpanjang 1600 piksel, JPEG). Admin bisa melihat, mengunduh, dan menghapus tiap gambar.
- **Rekap:** peserta per nomor lomba, tim beregu (satu baris per regu, dengan jumlah inti dan tanda "Inti kurang", K105), per sekolah, per kelompok umur, dan keuangan (tagihan, lunas, belum). Tersedia Excel dan PDF, ditambah tombol "Unduh Rekap Gabungan": satu Excel berisi semua data, satu sheet per bagian (K85). Rekap peserta memuat semua pendaftaran dengan saringan status (semua, Lunas, Menunggu), dan Excel serta PDF-nya mengikuti saringan itu; rekap keuangan selalu memuat keduanya (K60).
- **Data sekolah** (K97): menu Sekolah menampilkan semua sekolah di data referensi dengan saringan kabupaten, jenjang, nama yang sama dengan sekolah lain, dan sudah disunting, serta unduh Excel. Admin bisa mengubah nama, bentuk, jenjang, status, alamat, desa, kecamatan, dan kabupaten; NPSN tidak bisa diubah. Nama baru yang sama dengan sekolah lain ditolak. Saat menyimpan, admin bisa sekalian mengganti nama di pendaftaran yang sudah masuk dengan NPSN itu. Sekolah yang disunting tidak ditimpa saat data sekolah diimpor ulang.
- **Tambahkan nama desa** (K114): tombol di menu Sekolah membuka daftar nama baru untuk semua sekolah yang namanya sama dengan sekolah lain, misalnya "MIS MUHAMMADIYAH" di desa Jati menjadi "MIS MUHAMMADIYAH JATI". Bila desa kosong, sudah tertulis di nama, atau sama dengan sekolah lain bernama sama, dipakai kecamatan, lalu kabupaten. Admin mencentang yang akan dipakai (bawaannya semua) lalu menerapkannya: nama di data sekolah diganti dan ditandai disunting, dan pendaftaran ber-NPSN itu yang masih memakai nama lama ikut diganti; nama usulan pendaftar yang belum diputuskan tidak diubah. Sekolah yang tidak bisa dibedakan otomatis didaftar terpisah dengan tombol Ubah. Setelah diterapkan, nama tiap sekolah tetap bisa diubah lagi lewat Ubah.
- **Usulan nama** (K99): usulan dari pendaftar (6.2) tampil di halaman Usulan nama dengan formulir Setujui (nama boleh dirapikan dulu) dan Tolak (alasan pilihan). Jumlah usulan yang menunggu tampil di dasbor dan di menu Sekolah, dan rincian pendaftaran pengusul diberi tanda. Menyetujui satu usulan menutup usulan lain yang masih menunggu untuk sekolah yang sama.
- **Gambar belum ada:** kotak ikon "tanpa gambar", bukan gambar tiruan.

## 10. Halaman depan

Semua isi diatur dari panel admin.

- **Kepala** menempel di atas saat digulir, dengan pita bawah oranye (K75). Isinya logo persegi (logo persegi panjang hanya bila yang persegi belum ada) tanpa latar, label seri, menu Tentang, Kategori, Jadwal, Unduhan, dan tombol Panitia (K81, K93).
- **Pembuka** berlatar krem (K81–K83, K92):
  - judul besar huruf besar, satu kata per baris dengan kata terakhir oranye (judul data awal "MilkLife Athletics Challenge"; judul panjang mengalir biasa, K86);
  - gambar kepala di kanan yang memudar ke latar dengan tepi kiri setengah lingkaran;
  - kapsul info berisi tanggal, tempat, dan keadaan pendaftaran (6.1), dengan ujung keadaan biru saat pendaftaran terbuka (K82, K92);
  - tombol **Daftar Sekarang** (saat terbuka) dan **Cek Pendaftaran**.
- **Kartu kelompok umur** (label "Kelompok umur", sama dengan istilah di bagian lain, K112) bergambar pelari dan lintasan, menaut ke rinciannya, dengan julukan dan jumlah nomor individu dan beregu (K85, K88).
- **Kelompok umur, nomor lomba, dan biaya** dari data induk, paling banyak 2 kolom dengan lencana kategori Putra, Putri, atau Mixed (K76). Rincian biaya **disembunyikan** secara bawaan dan dinyalakan admin lewat sakelar di Pengaturan > Halaman Depan (K74).
- Jadwal: sosialisasi daring, pertemuan teknik, dan jadwal lain yang ditambahkan admin.
- Unduhan, narahubung, media sosial.
- Tombol bagikan ke WA dan tombol tanya admin lewat WA dengan teks yang sudah diatur.
- **Kaki halaman** dengan logo Bakti Olahraga Djarum Foundation di kanan, di semua halaman publik (K84).

## 11. Model data

Nama tabel dan kolom berbahasa Indonesia.

| Tabel | Isi utama |
|---|---|
| `admin` | nama, nama pengguna, sandi (hash) |
| `settings` | pengaturan kunci–nilai (dari pustaka settings) |
| `sekolah` | npsn, nama, bentuk, jenjang, status, alamat, desa/kelurahan, kecamatan, kabupaten, waktu disunting admin (impor tidak menimpa baris yang pernah disunting) |
| `usulan_nama_sekolah` | npsn, pendaftaran pengusul (kosong bila pendaftarannya dihapus), nama lama, nama usulan, status (menunggu, disetujui, ditolak), nama yang disetujui, alasan, waktu diputuskan |
| `kelompok_umur` | nama, tahun lahir paling awal, tahun lahir paling akhir (masing-masing boleh kosong), biaya individu, urutan, aktif |
| `kelompok_umur_jenjang` | kelompok umur, jenjang (A, B, atau C); satu baris per jenjang yang boleh ikut, unik per pasangan |
| `awalan_nomor_dada` | kelompok umur, awalan (unik), urutan, nomor terakhir |
| `nomor_lomba` | kelompok umur, nama, jenis, kategori, jumlah inti, batas anggota per regu, regu per sekolah paling banyak (kosong = tidak dibatasi), jumlah putra/putri (mixed), biaya beregu, urutan, aktif |
| `pendaftaran` | ID pendaftaran (unik), kode bukti (acak), jenjang, npsn, nama sekolah, kabupaten, subtotal individu, subtotal beregu, total, status, waktu lunas |
| `penanggung_jawab` | pendaftaran, nama, nomor WA, foto |
| `atlet` | pendaftaran, nama, NISN (unik), tanggal lahir, jenis kelamin, kelompok umur, nomor atlet (unik) |
| `atlet_nomor` | atlet, nomor lomba, biaya (terisi untuk individu), nama regu (paling banyak 30 huruf) dan peran inti/cadangan (terisi untuk beregu) |
| `pendaftaran_beregu` | pendaftaran, nomor lomba, regu, biaya regu; unik per pendaftaran, nomor, dan regu |
| `bukti_bayar` | pendaftaran, berkas, ukuran, waktu unggah (paling banyak 5 per pendaftaran, dijaga aplikasi) |
| `pendaftaran_gabungan` | pendaftaran gabungan, ID pendaftaran lama (unik), kode bukti lama (K109); ikut terhapus bila pendaftaran gabungannya dihapus |
| `jadwal` | judul, keterangan, waktu, tempat atau tautan, urutan, tampil |
| `unduhan` | judul, keterangan, berkas, urutan, tampil |
| `tautan` | jenis (narahubung/media sosial), label, nilai, urutan |

Keunikan NISN dan nomor atlet dijaga oleh basis data, bukan hanya oleh aplikasi.

## 12. Tampilan

- Satu tema terang.
- Warna dari flyer: biru utama, biru tua, oranye sebagai aksen dan tombol utama, biru muda untuk bidang informasi, putih untuk kartu.
- Responsif, dirancang dari ponsel lebih dulu.
- Bahasa ramah untuk orang non-teknis: tanpa istilah teknis, pesan galat menjelaskan cara memperbaikinya.

## 13. Keamanan dan privasi

- Perlindungan CSRF pada semua formulir.
- Pembatasan laju pada cek pendaftaran, bukti, pencarian NPSN, simpan pendaftaran, dan halaman masuk.
- Semua keluaran di-escape; semua masukan divalidasi di server.
- Foto ofisial dan bukti bayar disimpan di luar folder publik dan hanya disajikan ke admin.
- Sandi admin di-hash; sesi admin berakhir otomatis.
- **Mengganti kata sandi mengeluarkan sesi lain milik akun yang sama** pada permintaan berikutnya (K67); sesi yang mengganti tetap masuk. Mengganti kata sandi lewat perintah `admin:buat` mengeluarkan semua sesi akun itu.
- Kepala keamanan HTTP aktif di setiap halaman.
- Di mode produksi, halaman galat (halaman tidak ditemukan, permintaan keliru, galat umum) tampil ramah dalam bahasa Indonesia tanpa rincian teknis (K67).

**Risiko yang diterima pemilik:** NPSN bersifat publik dan ID pendaftaran berurutan, jadi orang yang menebaknya bisa melihat data siswa (nama, NISN, tanggal lahir). Pemilik memilih tampilan penuh berdasarkan MoU dengan sekolah. Pembatasan laju mengurangi pengambilan massal, tetapi tidak menutup risikonya.

## 14. Pengujian

- Uji unit untuk aturan murni: tahun lahir dan kelompok umur, biaya, ID pendaftaran, nomor atlet, kelengkapan tim.
- Uji fitur untuk alur pendaftaran, cek, bukti, panel, dan penandaan lunas, ditambah satu uji alur lengkap dari pendaftaran sampai hapus.
- Semua uji dijalankan dengan satu perintah (`composer uji`) di komputer pengembang sebelum PR dibuka. **CI GitHub** (GitHub Actions) menjalankan uji yang sama di setiap PR dan setiap perubahan di `main` (K46); PR digabung setelah keduanya lulus.

## 15. Pemasangan

Laragon dipakai untuk pengembangan dan produksi. Panduan pemasangan produksi ditulis sebagai dokumen tersendiri pada Tahap 7 dan mencakup: virtual host Nginx ke folder `public/`, HTTPS lewat Cloudflare Tunnel, mode produksi di `.env`, perkecil aset, cadangan terjadwal, cara memulihkan cadangan, dan cara memperbarui versi.

Daftar sekolah diisi dengan `php spark sekolah:impor` dan aman diulang setiap data sekolah diperbarui. Sekolah yang sudah disunting admin tidak ditimpa (K97).

Setelah pembaruan yang membawa penggabungan pendaftaran (K109), `php spark pendaftaran:gabung` dijalankan sekali untuk menggabungkan pendaftaran Lunas ber-NPSN sama yang sudah ada. Perintah menampilkan daftar sekolahnya dan meminta konfirmasi; menjalankannya lagi tidak mengubah apa pun.

Setelah `git pull`, `php spark pembaruan:periksa` (K115) memeriksa tanpa mengubah apa pun: paket Composer yang belum sesuai `composer.lock`, migrasi yang belum dijalankan, sekolah di `data/sekolah/sekolah.json` yang belum ada atau berubah (kecuali yang disunting admin), NPSN dengan lebih dari satu pendaftaran Lunas, dan berkas CSS/JS/SVG yang versi kecilnya belum ada atau lebih tua. Hasilnya daftar perintah yang perlu dijalankan, berurutan. Pemeriksaan yang gagal, misalnya karena MySQL mati, tidak dianggap beres.

**Cadangan** (K66): dibuat **harian** oleh Penjadwal Tugas Windows ke folder di luar proyek. Satu berkas zip berisi basis data serta foto ofisial, bukti bayar, dan unggahan admin. Cadangan yang lebih tua dari **14 hari** dihapus otomatis. Pemulihan dilakukan manual mengikuti panduan.

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
| 6. Panel data | Daftar pendaftaran, lunas dan nomor atlet, ubah, hapus, rekap, ekspor |
| 7. Penyelesaian | Minify dan pengecilan gambar, keamanan produksi, tim tanpa pembayar, cadangan harian, panduan pemasangan produksi, revisi rancangan ini, uji menyeluruh (K67) |

## 17. Asumsi yang perlu ditinjau

Hal berikut saya putuskan sendiri karena tidak disebut dalam wawancara. Mohon dikoreksi bila keliru.

1. ~~Kelompok umur ditentukan dari usia saja; atlet tidak bisa "naik" ke kelompok umur di atasnya, dan jenjang sekolah tidak membatasi kelompok umur.~~ **Diganti K23:** kelompok umur tetap ditentukan dari usia saja dan atlet tidak bisa "naik", tetapi jenjang sekolah membatasi kelompok umur sesuai centang admin (6.4). **Diganti K95:** jenjang sekolah menyaring kelompok umur lebih dulu, lalu tahun lahir menentukan di antaranya; rentang boleh beririsan antar-jenjang (6.4).
2. Frog Jump dan Turbo Throw (KU 10) berjenis **individu**. **Dikonfirmasi K21.**
3. Trilomba (KU 12) adalah **satu nomor individu** dan dihitung satu dari batas dua nomor individu. **Dikonfirmasi K22.**
4. Nomor urut ID pendaftaran berlaku global, bukan per jenjang. **Dikonfirmasi K32.**
5. ~~Data tersimpan di langkah Biaya (saat konfirmasi), bukan saat tombol "Pendaftaran Selesai".~~ **Dikonfirmasi K32, lalu diganti K90:** data tersimpan di langkah Pembayaran saat ofisial mengirim pendaftaran bersama bukti transfer (7).
6. Ofisial tidak bisa mengubah pendaftaran yang sudah tersimpan; perubahan lewat admin. **Dikonfirmasi K32.**
7. Foto ofisial tidak tampil di halaman cek. **Dikonfirmasi K32.**
8. ~~Bila admin mengubah pendaftaran yang sudah lunas sehingga totalnya berubah, status tetap Lunas dan selisihnya diurus panitia di luar sistem.~~ **Diganti K32:** status kembali ke Menunggu dan admin menandai Lunas lagi (6.9).

Semua asumsi sudah diputuskan pemilik. Juknis resmi tidak ditunggu (K55); juknis yang kemudian diterima 16 November 2026 disimpan di `sumber/juknis/` dan dicocokkan dengan aturan ini (K112): hanya batas tahun lahir KU 12 yang berbeda, dan sudah disesuaikan.

## Lampiran: data awal nomor lomba

Diambil dari flyer Seri 2 – 2026, dicocokkan dengan juknis resmi (K112), dan diisi lewat perintah sekali jalan saat pemasangan (K25, diperluas K73). Semua bisa diubah admin. Jenjang yang boleh ikut: KU 8–12 SD/MI, KU 15 SMP/MTs, KU 18 SMA/SMK/MA. Biaya individu: KU 8–12 Rp 25.000, KU 15 Rp 35.000, KU 18 Rp 50.000 per nomor. Batas anggota nomor beregu dua kali jumlah inti per regu, dan regu per sekolah tidak dibatasi (K107, sebelumnya 3 menurut K105). Perintah yang sama juga mengisi rekening, isi halaman depan, jadwal, kontak, dan berkas unduhan; rekening, nomor WA, dan sebagian unduhan masih contoh yang diganti admin. Batas tahun lahir (K36, K112): KU 8 lahir 2019 ke atas; KU 10 lahir 2016–2018; KU 12 lahir 2014–2015 (juknis; sebelumnya 2015); KU 15 lahir 2011–2014; KU 18 lahir 2008–2010. Awalan nomor atlet diisi satu per kelompok umur (K33): KU 8 = 1, KU 10 = 2, KU 12 = 3, KU 15 = 4, KU 18 = 5; admin bisa mengubah atau menambahnya. Nomor individu dibuat dua kali, putra dan putri; nomor beregu mengikuti kategori di tabel.

| Kelompok umur | Individu (putra dan putri) | Beregu |
|---|---|---|
| KU 8 (Seneng Playon) | Lari 40 m | – |
| KU 10 | Lari 60 m, Frog Jump, Turbo Throw | Kanga's Escape putra/putri (4 orang, Rp 100.000); Formula 1 putra/putri (4 orang, Rp 100.000); Estafet 8 × 50 m mixed (8 orang, Rp 150.000) |
| KU 12 | Lompat Jauh, Tolak Peluru, Lari 60 m, Lari 1000 m, Trilomba | Estafet 8 × 50 m mixed (8 orang, Rp 150.000) |
| KU 15 | Jalan Cepat 2000 m, Lari 80 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 5 × 80 m putra/putri (5 orang, Rp 100.000) |
| KU 18 | Jalan Cepat 3000 m, Lari 100 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 4 × 100 m putra/putri (4 orang, Rp 100.000); Estafet 4 × 400 m mixed (4 orang, Rp 100.000) |
