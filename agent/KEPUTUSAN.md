# Catatan Keputusan

Keputusan pemilik, terbaru di bawah. Keputusan lama tidak dihapus.

## 10 November 2026 — wawancara sesi 1

| No | Keputusan | Catatan |
|---|---|---|
| K01 | Wilayah: 5 kabupaten (Blora, Rembang, Pati, Kudus, Jepara) | Grobogan dan Demak tidak termasuk |
| K02 | Biaya individu dihitung per kelompok umur | Flyer menulis per jenjang; yang berlaku keputusan ini |
| K03 | Halaman cek menampilkan data penuh, termasuk NISN dan tanggal lahir | Atas dasar MoU dengan sekolah. Risiko tercatat di rancangan bagian 13 |
| K04 | Struktur repo dikelompokkan (`agent/`, `dokumen/`, `sumber/`, `data/`), PDF dokumen disimpan di git | |
| K05 | Nomor dada: awalan per kelompok umur, satu kelompok umur boleh punya banyak awalan | Satu awalan hanya menampung 999 atlet |
| K06 | NISN yang sudah terdaftar ditolak | Perubahan atlet terdaftar lewat admin |
| K07 | Satu tim per nomor beregu per sekolah | Susulan hanya menambah anggota, tanpa biaya beregu lagi |
| K08 | Laragon dipakai juga untuk produksi | Karena keterbatasan teknis tim |
| K09 | Nomor mix: sistem hanya memeriksa jumlah putra dan putri | Urutan lari diatur di lapangan |
| K10 | Tim beregu wajib lengkap saat didaftarkan | Minimal sejumlah anggota inti |
| K11 | Nomor dada dibuat setelah admin menandai Lunas | Pendaftar yang tidak membayar tidak memakan nomor |
| K12 | Batas usia kelompok umur kosong pada awalnya | Admin mengisi sesuai juknis; pendaftaran belum bisa dibuka sebelum terisi |
| K13 | Tanpa atribusi alat AI pada commit, PR, kode, dan dokumen; satu-satunya kontributor adalah Arumi Studios | |
| K14 | Cabang utama adalah `main` | |
| K15 | Tanpa CI GitHub; uji dijalankan sendiri | Membatalkan rencana GitHub Actions di rancangan awal |

## 10 November 2026 — wawancara sesi 2 (awal Tahap 1)

| No | Keputusan | Catatan |
|---|---|---|
| K16 | "Pakai CI sendiri, jangan GitHub" berarti dua hal: CodeIgniter 4 dipasang baru dari sumber resmi lewat Composer, dan uji dijalankan sendiri tanpa GitHub Actions | Mempertegas K15. Tidak ada `.github/workflows` |
| K17 | Rancangan web disetujui dan dipindahkan ke `dokumen/final/` | Delapan asumsi di bagian 17 belum dikonfirmasi; wajib dikonfirmasi sebelum Tahap 2 dan Tahap 4 |
| K18 | Laragon pemilik memakai PHP 8.3 dan MySQL 8.x | Versi minimal di `composer.json`: PHP 8.2, sesuai syarat CodeIgniter 4.7 |
| K19 | Tahap 2–7 dikerjakan sesuai urutan di rancangan | Pendaftaran di flyer ditutup 30 November 2026; urutan tidak diubah walau waktu sempit |

## 11 November 2026

| No | Keputusan | Catatan |
|---|---|---|
| K20 | Lisensi repo diputuskan nanti | Sementara `composer.json` menulis `"license": "proprietary"` dan repo belum punya berkas lisensi |
| K21 | Frog Jump dan Turbo Throw (KU 10) adalah nomor individu | Mengonfirmasi asumsi 2 rancangan bagian 17 |
| K22 | Trilomba (KU 12) adalah satu nomor individu: dibayar sekali dan dihitung satu dari batas dua nomor individu | Mengonfirmasi asumsi 3 rancangan bagian 17 |
| K23 | Kelompok umur dibatasi jenjang sekolah. Jenjang yang boleh ikut tiap kelompok umur diatur admin, tanpa nilai tertulis di kode | Mengganti asumsi 1 rancangan bagian 17 (sebelumnya: jenjang tidak membatasi kelompok umur). Usia tetap menentukan kelompok umur; atlet ditolak bila kelompok umurnya tidak dibuka untuk jenjang sekolahnya. Rancangan bagian 6.4, 11, dan 17 perlu direvisi lewat draf sebelum Tahap 2 |

## Dari permintaan awal pemilik

- Satu peran admin; nama dan kata sandi bisa diganti.
- Semua teks antarmuka dan URL berbahasa Indonesia, ramah non-teknis.
- Satu tema terang. Panel admin lebar penuh tanpa teks footer.
- Pembayaran transfer dengan konfirmasi manual lewat WA; tanpa gerbang pembayaran.
- Maksimal 2 nomor individu per atlet; minimal 1 nomor (individu atau beregu).
- Usia dihitung per 30 November.
- Kode jenjang pada ID pendaftaran: A (SD/MI), B (SMP/MTs), C (SMA/SMK/MA).
- Gambar yang belum ada ditampilkan sebagai ikon "tanpa gambar", bukan gambar tiruan.

## 11 November 2026 — wawancara awal Tahap 2

| No | Keputusan | Catatan |
|---|---|---|
| K24 | Jenjang yang boleh ikut tiap kelompok umur diatur lewat kotak centang (SD/MI, SMP/MTs, SMA/SMK/MA) di formulir kelompok umur | Melaksanakan K23. Daftar kelompok umur menampilkan jenjang yang dicentang |
| K25 | Data awal kelompok umur dan nomor lomba dari flyer Seri 2 diisi lewat perintah sekali jalan saat pemasangan | Batas usia, biaya individu, awalan nomor dada, dan jenjang tetap kosong untuk diisi admin (K12, K23). Setelah itu semua diubah dari panel |
| K26 | Halaman Akun hanya untuk akun admin yang sedang masuk: ganti nama, nama pengguna, dan kata sandi sendiri | Akun admin lain tetap dibuat dan diubah lewat perintah `admin:buat` |
| K27 | Revisi 1 rancangan (K23) dan rencana implementasi Tahap 2 disetujui, termasuk tujuh asumsi rencana | Rancangan revisi dipindahkan ke `dokumen/final/`. Kode Tahap 2 boleh mulai, diawali F2-01 |

## 11 November 2026 — jawaban pemilik atas hal terbuka

| No | Keputusan | Catatan |
|---|---|---|
| K28 | Sekolah Luar Biasa (SLB) tidak ikut | Menjawab pertanyaan jenjang 19 SLB di data sekolah. Penanganannya di pendaftaran (Tahap 4): SLB tidak bisa dipilih atau ditolak dengan pesan jelas |
| K29 | Lisensi repo: proprietary | Menggantikan K20. Sesuai `"license": "proprietary"` di `composer.json` |
| K30 | Juknis resmi masih menyusul | Batas usia kelompok umur tetap diisi admin setelah juknis ada (K12) |
| K31 | Web dijangkau dari internet lewat Cloudflare Tunnel | Menjawab rancangan bagian 15. Akibatnya alamat IP pengunjung dibaca dari kepala `CF-Connecting-IP`, supaya pembatasan masuk dan pembatasan laju tidak menghitung semua pengunjung sebagai satu alamat; diatur di tahap pemasangan |
| K32 | Asumsi 4–7 rancangan bagian 17 dikonfirmasi. Asumsi 8 diganti: bila admin mengubah pendaftaran yang sudah Lunas dan totalnya berubah (naik atau turun), status kembali ke Menunggu dan admin menandai Lunas lagi | Selisih tetap diurus panitia di luar sistem. Nomor dada yang sudah dibagikan tetap dipegang atlet |
| K33 | Awalan nomor dada awal: KU 8 = 1, KU 10 = 2, KU 12 = 3, KU 15 = 4, KU 18 = 5 | Diisi `data:awal`; tetap bisa diubah dan ditambah admin. Mengubah K25 sebatas awalan nomor dada |
| K34 | Bukti bayar: admin mengunggah paling banyak 5 gambar per pendaftaran di panel; gambar dikecilkan otomatis dan bisa dilihat, diunduh, dan dihapus | Official tetap mengirim bukti lewat WA dan tidak mengunggah apa pun. Dikerjakan di Tahap 6 (panel data) |
| K35 | Rancangan revisi 2 (K28–K34) disetujui | Dipindahkan ke `dokumen/final/` |
| K36 | Kelompok umur ditentukan tahun lahir, tanpa bulan dan tanggal: KU 8 lahir 2019 ke atas, KU 10 lahir 2016–2018, KU 12 lahir 2015, KU 15 lahir 2011–2014, KU 18 lahir 2008–2010 | Mengganti cara K12 (batas usia tahun penuh, kosong pada awalnya). Diisi `data:awal`, tetap bisa diubah admin bila juknis resmi berbeda |
| K37 | Tanggal patokan usia dihapus. Usia tetap ditampilkan (tahun, atau tahun dan bulan) hanya sebagai keterangan | Usia dihitung pada tanggal mulai acara (keputusan koordinator, rancangan revisi 3) |
| K38 | Rancangan revisi 3 (K36, K37) disetujui. Sub-sesi Tahap 2 dijalankan bertahap, paling banyak 3 sekaligus | Batas pemakaian akun dipakai bersama pemilik dan semua sub-sesi; delapan sub-sesi sekaligus menghabiskannya dalam beberapa menit |

## 12 November 2026 — wawancara awal Tahap 3

| No | Keputusan | Catatan |
|---|---|---|
| K39 | Tombol Pendaftaran dan Cek Pendaftaran di halaman depan tampil nonaktif dengan tulisan "Segera" sampai halamannya dibuat (Tahap 4 dan 5) | Sama seperti menu publik. Setelah halamannya ada, tombol aktif sesuai keadaan pendaftaran (rancangan 6.1) |
| K40 | Unduhan punya halaman sendiri (`/unduhan`) dan juga tampil di halaman depan | Sesuai peta halaman rancangan bagian 5; menu Unduhan di kepala aktif |
| K41 | Gambar kepala dan logo yang belum diunggah disembunyikan di halaman depan | Pengunjung tidak melihat kotak kosong. Di panel admin kotak "tanpa gambar" tetap tampil (permintaan awal pemilik berlaku untuk panel) |
| K42 | Urutan halaman depan: kepala (gambar, logo, judul, data acara, keadaan pendaftaran, tombol) → kelompok umur, nomor lomba, dan biaya → jadwal → unduhan → kontak dan tombol WA → footer | Yang paling dicari sekolah tampil lebih dulu |
| K43 | Rencana implementasi Tahap 3 disetujui, termasuk enam asumsinya | Kode Tahap 3 boleh mulai, diawali F3-01 dan F3-04 bersamaan |

## 12 November 2026 — tinjauan halaman depan Tahap 3

| No | Keputusan | Catatan |
|---|---|---|
| K44 | Gambar kepala halaman depan menjadi latar di belakang judul, bukan pita gambar terpisah di atas judul | Gambar diberi lapisan biru tua supaya tulisan tetap terbaca. Keterangan unggah gambar kepala di panel menyarankan foto tanpa tulisan. Dikerjakan di F3-05 |
| K45 | Rincian biaya di bagian lomba halaman depan bisa disembunyikan atau ditampilkan lewat panel admin (sakelar "Tampilkan biaya di halaman depan" di pengaturan Halaman Depan, nilai awal tampil) | Alasan pemilik: rincian biaya tetap muncul di bagian pembayaran saat mendaftar (Tahap 4). Kunci baru `HalamanDepan.tampilkan_biaya`. Diminta langsung ke sub-sesi F3-02, dibawa lewat PR penutupan F3-05 |

## 12 November 2026 — CI GitHub

| No | Keputusan | Catatan |
|---|---|---|
| K46 | Repo ini boleh memakai CI GitHub (GitHub Actions). Sekarang dipakai untuk menjalankan `composer uji` (PHP 8.3, MySQL 8) pada setiap PR ke `main` dan setiap push ke `main`; uji lokal tetap dijalankan sebelum PR | Menggantikan K15 dan bagian "uji tanpa GitHub Actions" di K16; bagian K16 tentang pemasangan CodeIgniter 4 lewat Composer tetap berlaku. Menggantikan juga kalimat "Tanpa CI GitHub" di rancangan bagian 14. Workflow hanya berlaku untuk repo ini, jadi tidak mengganggu CI repo lain di akun yang sama |

## 12 November 2026 — gambar dari pemilik

| No | Keputusan | Catatan |
|---|---|---|
| K47 | Gambar latar dan logo MilkLife Athletics Challenge dari pemilik disimpan di repo (`data/halaman-depan/`) dan dipasang `php spark data:awal` sebagai gambar kepala dan logo persegi | Repo publik; pemilik menyatakan tidak masalah. Disalin ke `public/unggahan/halaman-depan/` supaya admin tetap bisa mengganti atau menghapusnya. Pengaturan yang sudah diisi admin tidak ditimpa. Bila gambar latar terpasang, bidang miring tema disembunyikan karena gambarnya sudah punya hiasan sendiri; logo persegi tampil lebih besar dari logo lain |

## 12 November 2026 — Tahap 4 dan 5 bersamaan

| No | Keputusan | Catatan |
|---|---|---|
| K48 | Tahap 4 dan Tahap 5 dikerjakan bersamaan di dua sesi berbeda tempat, dengan pembagian berkas sesuai kesepakatan dua sesi | Kesepakatan disalin ke `dokumen/draf/rencana-implementasi-tahap-5.md` bagian "Kesepakatan bersama". Titik temu satu-satunya adalah `main` |
| K49 | Wawancara Tahap 5: (1) cek lewat NPSN menampilkan pendaftaran sekolah itu berurutan per pendaftaran (data sekolah sekali di atas, satu kartu per pendaftaran, total keseluruhan di bawah); (2) bukti PDF juga mencetak nama anggota tiap tim beregu, nomor dada (tertulis "–" sampai Lunas), dan tanggal lahir atlet; (3) nama berkas bukti memakai seluruh nama acara tanpa spasi, misalnya `BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_<NamaSekolah>_2026.pdf` | Rancangan bagian 8 hanya memberi contoh nama berkas dan tidak merinci isi tabel bukti maupun arti "digabung" |

## 12 November 2026 — wawancara awal Tahap 4

| No | Keputusan | Catatan |
|---|---|---|
| K50 | Foto penanggung jawab wajib diisi di wizard pendaftaran | Menjawab rancangan 6.3. Foto diperkecil di peramban (800 piksel, JPEG), disimpan di luar `public/`, dan hanya dilihat admin. Kolom `penanggung_jawab.foto` tetap boleh kosong di basis data |
| K51 | Ada batas jumlah atlet per sekolah pada satu nomor individu, diatur admin di pengaturan Pendaftaran; berlaku juga untuk pendaftaran susulan dari NPSN yang sama | Kunci baru `Pendaftaran.atlet_per_nomor_maksimal`. Pemilik tidak menyebut angkanya: nilai awal 2 (contoh di pertanyaan wawancara), kosong berarti tidak dibatasi. Pendaftaran tanpa NPSN dihitung sendiri. Disesuaikan bila juknis resmi (K30) berbeda |

## 12 November 2026 — tinjauan bukti pendaftaran

| No | Keputusan | Catatan |
|---|---|---|
| K52 | Bukti pendaftaran PDF dirapikan: logo persegi tampil transparan (bukan berlatar hitam) dan tidak gepeng; penanggung jawab ditulis sebaris "Penanggung Jawab (Official) : Nama (ikon WA nomor WA)", dipisah koma bila lebih dari satu, tanpa tabel; tanggal lahir dan nomor dada **tidak** dicetak; anggota tim beregu ditulis dalam satu baris dipisah koma; status pembayaran diberi bingkai berwarna seperti tombol (Lunas hijau, Menunggu Konfirmasi Pembayaran kuning) | Menggantikan K49 butir 2 (anggota tim tetap dicetak, nomor dada dan tanggal lahir tidak). **Halaman cek juga tidak menampilkan tanggal lahir dan nomor dada**; ini menyimpang dari daftar isi cek di rancangan 8 dan dimasukkan ke revisi rancangan berikutnya. Nomor dada tetap tersimpan dan terlihat di panel admin (Tahap 6). Tinjauan pemilik atas bukti F5-03, dibawa PR penutupan F5-05 |

## 12 November 2026 — tinjauan Tahap 4 dan arah Tahap 6

| No | Keputusan | Catatan |
|---|---|---|
| K53 | Jumlah atlet satu sekolah pada satu nomor individu **tidak dibatasi**. Yang dibatasi adalah jumlah nomor individu per atlet: satu nama siswa paling banyak 2 nomor individu. Keduanya tetap bisa diatur admin di pengaturan Pendaftaran | Menggantikan nilai awal 2 di K51: `Pendaftaran.atlet_per_nomor_maksimal` kini bawaannya kosong (tidak dibatasi), pengaturannya tetap ada. Batas per atlet memakai `Pendaftaran.nomor_individu_maksimal` (nilai awal 2, sudah ada sejak Tahap 2). Pemasangan yang pernah menyimpan pengaturan Pendaftaran tetap memakai angka tersimpan; kosongkan kotak "Atlet per sekolah pada satu nomor individu" di panel |
| K54 | Asumsi rencana Tahap 4 nomor 1 (data referensi NPSN menentukan nama, kabupaten, jenjang), 2 (pemeriksaan server saat pindah ke langkah 4 dan saat simpan), 5 (batas laju 60/30/10) dan 6 (nama penanggung jawab disimpan seperti diketik) disetujui | Asumsi 4 digantikan K53. Asumsi 3 (atlet susulan di nomor beregu otomatis menjadi tambahan anggota tim yang sudah ada, tanpa biaya tim) belum dijawab |
| K55 | Juknis resmi tidak ditunggu. Batas yang sudah disepakati dipakai (tahun lahir K36, kuota K53); perubahan aturan berikutnya lewat wawancara dengan pemilik | Menggantikan K30. Pemasangan di Laragon sesuai `README.md` sudah dicoba pemilik dan berjalan |
| K56 | Tahap 6 dikerjakan sesi koordinator baru yang membagi tugas ke sub-sesi paralel | Batas K38 tetap berlaku: paling banyak 3 sub-sesi sekaligus, karena batas pemakaian akun dipakai bersama |

## 12 November 2026 — wawancara awal Tahap 6

| No | Keputusan | Catatan |
|---|---|---|
| K57 | Asumsi 3 rencana Tahap 4 **tidak disetujui apa adanya**: atlet pendaftaran susulan di nomor beregu yang timnya sudah terdaftar tetap menjadi tambahan anggota tim itu tanpa biaya tim (K07), tetapi official harus **mengonfirmasinya sendiri** sebelum lanjut. Wizard menampilkan kotak centang per tim susulan, dan server menolak kiriman yang belum mengonfirmasi | Menjawab K54 (asumsi 3 terbuka). Dikerjakan di Tahap 6 sebagai perubahan wizard (F6-05). Admin yang mengubah pendaftaran di panel tidak perlu konfirmasi ini |
| K58 | Admin **tidak** membuat pendaftaran baru dari panel. Sekolah yang terlambat didaftarkan lewat wizard setelah admin memakai buka manual | Panel hanya melihat, menandai Lunas, mengubah, dan menghapus pendaftaran yang sudah ada (rancangan 9) |
| K59 | Atlet yang ditambahkan admin ke pendaftaran Lunas yang totalnya tidak berubah (misalnya tambahan anggota tim beregu) **langsung mendapat nomor dada saat disimpan** | Melengkapi K32: bila total berubah, status kembali ke Menunggu dan nomor dada atlet baru dibuat saat ditandai Lunas lagi |
| K60 | Rekap peserta (per nomor lomba, tim beregu, per sekolah, per kelompok umur) beserta Excel dan PDF-nya memuat **semua pendaftaran**, dengan saringan status (semua, Lunas, Menunggu). Rekap keuangan selalu memuat keduanya | |
| K61 | Rencana implementasi Tahap 6 disetujui, termasuk delapan asumsinya | Kode Tahap 6 boleh mulai, diawali F6-01 (gelombang 1) |

## 12 November 2026 — persiapan sesi agen di cloud

| No | Keputusan | Catatan |
|---|---|---|
| K62 | Sesi agen di cloud disiapkan oleh Setup script di setelan lingkungan cloud: MySQL 8, basis data `milklife_uji`, `.env`, dan `composer install` dipasang sekali di awal sesi. Agen tidak memasang ulang selama sesi, cukup `composer uji` | Pemilik melihat pemasangan berulang di sesi cloud. Isi skrip ada di `agent/ALUR-KERJA.md` bagian "Sesi agen di cloud". Diuji di sesi ini: pemasangan pertama sekitar 7,5 menit, jalan ulang 1 detik, 929 uji lulus. Unduhan zip dari GitHub ditolak jaringan sesi, jadi Composer mengambil lewat git dan lebih lambat |

## 12 November 2026 — pelaksanaan gelombang Tahap 6

| No | Keputusan | Catatan |
|---|---|---|
| K64 | Gelombang Tahap 6 setelah F6-01 dijalankan pemilik sendiri di sesi berbeda, bukan oleh sesi koordinator | Mengubah cara kerja K56 untuk gelombang 2–4. Rencana, pembagian berkas, dan batas 3 sesi sekaligus (K38) tetap berlaku. **Semula tercatat sebagai K62**, sama dengan nomor keputusan persiapan sesi agen di cloud; dinomori ulang menjadi K64 atas keputusan pemilik 12 November 2026 di penutupan Tahap 6 ([#55](https://github.com/arumiforge/milklife/pull/55)). K62 kini hanya untuk persiapan sesi agen di cloud |

## 12 November 2026 — ubah pendaftaran (F6-04)

| No | Keputusan | Catatan |
|---|---|---|
| K63 | ID pendaftaran tidak berubah saat admin mengubah pendaftaran, juga bila jenjang sekolahnya diubah. Kode jenjang di ID (rancangan 6.8) tetap mengikuti data saat mendaftar | Supaya lebih aman dan sederhana: ID yang sudah dipegang sekolah, tercetak di bukti, dan dipakai di halaman cek tidak berganti. Dikonfirmasi pemilik di sesi F6-04 ([#52](https://github.com/arumiforge/milklife/pull/52)) dan dicatat sesi koordinator |

## 12 November 2026 — wawancara awal Tahap 7

| No | Keputusan | Catatan |
|---|---|---|
| K65 | Tahap 7 dikerjakan sesi koordinator yang **membuat sendiri** sub-sesi paralel per tugas, memantau PR-nya, lalu menjalankan penutupan | Kembali ke cara K56 (bukan K64). Batas K38 tetap: paling banyak 3 sub-sesi sekaligus |
| K66 | Cadangan produksi dibuat **harian** oleh Penjadwal Tugas Windows ke folder di luar proyek, berisi basis data serta foto penanggung jawab, bukti bayar, dan unggahan admin. Cadangan yang lebih tua dari **14 hari** dihapus otomatis | Menjawab rancangan 15 ("cadangan basis data terjadwal") |
| K67 | Cakupan Tahap 7 ditambah: revisi rancangan ke-4 (K49–K63 dan keputusan Tahap 7), ganti kata sandi admin mengeluarkan sesi lain milik akun yang sama, halaman galat ramah di mode produksi, dan aturan tim susulan tanpa pembayar (K68) | Selain isi rancangan 16: minify, uji menyeluruh, panduan pemasangan produksi |
| K68 | Bila tim beregu kehilangan pendaftaran yang membayar biaya timnya (pendaftaran dihapus atau timnya dihapus lewat Ubah), biaya **tidak dipindah otomatis**. Rincian pendaftaran, dasbor, dan rekap keuangan menandai tim tanpa pembayar supaya admin menagih lewat Ubah | Menjawab batasan F6-04 di arsip Tahap 6 |
| K69 | Rencana implementasi Tahap 7 disetujui, termasuk delapan asumsinya | Kode Tahap 7 boleh mulai, diawali gelombang 1 (F7-01, F7-02, F7-04) |
| K70 | Koordinator Tahap 7 boleh menggabungkan (*squash*) PR rencana dan PR sub-sesi Tahap 7 setelah uji lokal dan CI lulus | Kecuali persetujuan isi dokumen (rancangan revisi 4, panduan pemasangan produksi), yang tetap diberikan pemilik di PR |

## 12 November 2026 — rancangan revisi 4 (F7-04)

| No | Keputusan | Catatan |
|---|---|---|
| K71 | Rancangan revisi 4 (K49–K68) disetujui dan dipindahkan ke `dokumen/final/`; PDF dibuat ulang | Disetujui pemilik di sesi F7-04 setelah draf [#57](https://github.com/arumiforge/milklife/pull/57) digabung; dipindahkan lewat [#59](https://github.com/arumiforge/milklife/pull/59). Dicatat koordinator |

## 12 November 2026 — cakupan minify

| No | Keputusan | Catatan |
|---|---|---|
| K72 | Minify berlaku untuk seluruh aset aplikasi, tidak hanya HTML, dan berkas asli tidak dihapus | `php spark aset:minify` kini juga membuat `.min.svg` dari SVG di `public/aset/` (aturan perkecil HTML dipakai ulang), dan `aset()` memilihnya bila `aplikasi.minify` aktif. CSS/JS sudah sejak F7-01. Gambar unggahan tetap dikecilkan saat disimpan (F7-01); `favicon.ico` dan `robots.txt` tidak diolah |

## 12 November 2026 — data awal lengkap dan tampilan publik

| No | Keputusan | Catatan |
|---|---|---|
| K73 | `php spark data:awal` mengisi data awal yang lengkap: biaya individu KU 8–12 Rp25.000, KU 15 Rp35.000, KU 18 Rp50.000; jenjang KU 8–12 SD/MI, KU 15 SMP/MTs, KU 18 SMA/SMK/MA; batas anggota beregu dua kali jumlah inti (mix minimal separuh putra, separuh putri); rekening BANK BPD JATENG a.n. Nama Panitia 0123456789 dengan logo bank dari pemilik; WA pembayaran dan tanya admin 08123456789; isi halaman depan (judul, teks pengantar, teks kaki, pesan tanya "Tanya admin", pesan bagikan "Bagikan ke WA"); contoh jadwal (Zoom sosialisasi, batas pendaftaran, technical meeting, pengambilan nomor dada, pelaksanaan); narahubung dari flyer (Just, Iqbal); media sosial karangan; dan enam berkas unduhan (flyer dan banner asli, empat contoh berkas kosong) | Menggantikan K25 (biaya, jenjang, dan batas anggota tidak lagi dikosongkan). Rekening, nomor WA, tautan Zoom, media sosial, dan berkas kosong masih contoh yang wajib diganti admin sebelum produksi. Pengaturan, jadwal, kontak, gambar, dan unduhan yang sudah diisi admin tidak ditimpa. Logo bank disimpan di `data/pembayaran/`, contoh unduhan di `data/unduhan/` |
| K74 | Rincian biaya di halaman depan **disembunyikan** secara bawaan; admin menyalakannya lewat sakelar di Pengaturan > Halaman Depan | Menggantikan nilai awal "tampil" di K45 |
| K75 | Tampilan publik: kepala menempel di atas saat digulir (ponsel dan komputer) dengan pita bawah oranye penuh tanpa gradasi; penanda menu aktif di atas tulisan; logo halaman depan tanpa kartu putih; garis miring setelah judul bagian dihapus; tombol Pendaftaran dan Cek Pendaftaran pindah dari kepala ke bagian paling atas bagian lomba, di layar lebar membagi dua lebar halaman; keadaan kosong (unduhan, cek, pendaftaran tertutup) memakai kartu bergaris oranye dengan ikon besar | Tombol tetap tampil walau bagian lomba belum ada. Keadaan pendaftaran tetap di kepala |
| K76 | Panel admin: kolom No otomatis di semua tabel; pilih massal di daftar pendaftaran untuk Tandai Lunas, Kembalikan ke Menunggu, dan Hapus (dengan konfirmasi, aturan sama seperti di rincian, paling banyak 100 sekaligus); foto penanggung jawab dibuka di jendela; bukti bayar di layar lebar galeri kiri dan panel unggah kanan; teks pengantar dan catatan panel menjadi kotak info berikon selebar halaman. Halaman depan: bagian lomba paling banyak 2 kolom, teks lebih besar, pengantar selebar halaman, kategori Putra/Putri/Mix berupa lencana berwarna. Data pendaftaran bisa diekspor (zip: data, foto, bukti bayar) dari tombol "Ekspor untuk pindah server" dan dimasukkan di server baru dengan `php spark pendaftaran:impor <berkas.zip>` | Pilihan ekspor dijawab pemilik: ekspor + impor. Impor hanya ke server yang belum punya pendaftaran; kelompok umur dan nomor lomba dicocokkan menurut nama, jadi pengaturan server baru harus sama. ID pendaftaran, status, nomor dada, dan urutan nomor dada/ID berikutnya dipertahankan. Panduan produksi final belum menyebut perintah impor |
| K77 | Tombol ekspor bernama "Backup data". Alamat bukti PDF menjadi `/bukti/<kode acak 24 huruf>/<ID pendaftaran>` (kolom baru `pendaftaran.kode_bukti`); alamat lama `/bukti/<ID>` tidak berlaku lagi. Bukti PDF memakai kertas F4 tegak (215 × 330 mm) dengan margin atas-bawah 2 cm, kode QR estetik (modul bulat, sudut biru tua bertitik oranye, logo persegi di tengah, pustaka `chillerlan/php-qrcode`) di pojok kanan atas sejajar logo, berisi alamat bukti itu; isi bukti didesain ulang hemat tempat (satu baris per atlet, kepala tabel berulang, nomor halaman). Jendela foto penanggung jawab kecil di tengah layar. Halaman cek pendaftaran dan formulir pendaftaran didesain ulang | Menggantikan ukuran A4 mendatar di K49. Kode bukti ikut di Backup data supaya kode QR pada bukti yang sudah dicetak tetap berlaku setelah pindah server. Klarifikasi pemilik: "redesain halaman pendaftaran dan cek" berarti file bukti, halaman cek, dan formulir pendaftaran |
| K78 | Bukti PDF: margin atas 1,5 cm (bawah tetap 2 cm); kode QR tanpa tulisan keterangan; status pembayaran di bagian akhir berupa stempel miring berbingkai ganda, hijau "LUNAS / PEMBAYARAN DITERIMA" atau oranye "BELUM LUNAS / MENUNGGU KONFIRMASI", dengan jarak catatan yang lebih longgar | Mengubah K77 sebatas margin atas dan keterangan QR |

## 12 November 2026 — catatan daftar pendaftaran

| No | Keputusan | Catatan |
|---|---|---|
| K79 | Catatan di bawah tabel daftar pendaftaran panel tidak lagi menyebut centang yang hanya berlaku untuk halaman tampil, isi Backup data, maupun perintah `php spark pendaftaran:impor`. Antarmuka tidak memuat teks teknis dan memakai ejaan bahasa Indonesia baku | Cara memasukkan Backup data di server baru tetap tercatat di README (bagian Perintah) |

## 12 November 2026 — rupiah dan halaman depan

| No | Keputusan | Catatan |
|---|---|---|
| K80 | Rupiah ditulis dengan spasi antara Rp dan angkanya (`Rp 150.000`) di semua tempat: antarmuka, bukti PDF, pesan WA, dan Excel. Ikon uang ber-dolar diganti tanda "Rp". Di halaman depan, keterangan nomor beregu tidak lagi menyebut batas anggota ("paling banyak …"); nama nomor, lencana kategori, dan keterangan tim diperbesar di layar lebar dengan jarak yang lebih lega; ringkasan "Lihat nomor lomba" berupa pita oranye pucat bergaris oranye. Waktu acara dan keadaan pendaftaran di kepala menjadi dua kartu sejajar di layar lebar, bertumpuk dengan lebar sama di ponsel | Menggantikan aturan contoh `Rp150.000` di rancangan final bagian aturan umum; rancangan final belum diubah dan menunggu revisi lewat draf. Batas anggota tetap berlaku dan diperiksa saat mendaftar |

## 12 November 2026 — desain ulang pembuka halaman depan

| No | Keputusan | Catatan |
|---|---|---|
| K81 | Bagian pembuka halaman depan didesain ulang mengikuti konsep visual pemilik: latar krem dengan biru tua, oranye, dan hijau muda; judul besar miring per kalimat dengan kalimat terakhir oranye; foto di kanan memudar ke latar dan dipotong otomatis; nomor seri besar bergaris ("02"); kartu info tanggal, tempat, dan keadaan pendaftaran; tombol "Daftar Sekarang" (saat terbuka) dan "Cek Pendaftaran" pindah ke dalam pembuka; deret kartu kelompok umur ("Kelompok usia — Setiap langkah punya peluang") menaut ke rinciannya. Di layar lebar pembuka mengisi tepat satu layar di bawah kepala. Menu publik menjadi Tentang, Kategori, Jadwal, Unduhan, ditambah tombol Panitia (biru tua, tanpa panah) ke halaman masuk admin. Kepala terang dengan logo acara dan label seri hanya di halaman depan; halaman lain tetap biru. Data awal judul "Berlari. Bermain. Berprestasi." dan teks "Ajang atletik untuk generasi muda Indonesia." | Dijawab pemilik: foto atlet diunggah admin lewat Pengaturan > Halaman Depan dan tidak masuk git (repo publik, foto siswa, hak cipta); data awal tetap gambar bawaan dari pemilik. Menu mengikuti konsep. Gaya baru hanya untuk halaman depan. Menggantikan K44 (gambar sebagai latar di belakang judul) dan letak tombol di K75. Logo persegi panjang dipakai di kepala bila ada, selain itu logo persegi; logo Djarum di bawah tombol |

## 13 November 2026 — kartu info pembuka

| No | Keputusan | Catatan |
|---|---|---|
| K82 | Kartu info di pembuka halaman depan dibuat persis seperti contoh pemilik: satu kapsul putih berisi tanggal ("9–12" besar di atas "Desember 2026") dan tempat (dipisah di koma terakhir: "Supersoccer Arena" / "Kudus") bersekat garis tegak, lalu ujung kanan berwarna keadaan berisi dua baris pendek: "Daftar s.d. / 30 November" (terbuka, ikon pengeras suara), "Dibuka / 1 Oktober", "Pendaftaran / Ditutup", atau "Pendaftaran / Segera dibuka". Di ponsel ujung keadaan turun ke baris kedua | Mengubah tampilan keadaan dari K81. Kalimat lengkap ("Pendaftaran dibuka sampai 30 November 2026 pukul 23.59.") tetap ada untuk pembaca layar. Ringkasan dari `BukaTutupPendaftaran::ringkas()` |

## 13 November 2026 — judul pembuka dan unggah bukti bayar oleh official

| No | Keputusan | Catatan |
|---|---|---|
| K83 | Pembuka halaman depan: nomor seri besar "02" dan tulisan "Setiap langkah punya peluang" dihapus; judul data awal menjadi "MilkLife Athletics Challenge", tampil huruf besar dengan satu kata per baris dan kata terakhir oranye (judul berkalimat bertitik tetap satu kalimat per baris), tetap bisa diganti di Pengaturan > Halaman Depan; tepi kiri foto berupa setengah lingkaran yang memudar lebih halus ke latar krem, bukan garis lurus. Official mengunggah bukti transfer sendiri di langkah Pembayaran wizard atau di kartu Cek Pendaftaran (alamat `POST /bukti/<kode acak>/<ID>/bukti-bayar`), jadi bukti tidak perlu dikirim lewat WA; admin tetap bisa mengunggah dari rincian. Di bukti PDF, nomor WA penanggung jawab tidak lagi diapit tanda kurung | Permintaan panitia: bukti yang masuk lewat sistem tidak perlu dikirim ke WA, supaya admin tidak mengunggah semua bukti sendiri. Unggah memakai alamat bukti berkode acak sebagai izin (ID atau NPSN saja tidak cukup), dibatasi 10 kiriman per menit per alamat IP, ditolak untuk pendaftaran Lunas, dan memakai batas 5 gambar yang sama dengan panel. Official tidak bisa melihat atau menghapus gambar yang sudah diunggah; salah unggah dihapus admin. Tombol WA di langkah Pembayaran menjadi tombol sekunder "Konfirmasi lewat WA". Judul yang sudah diisi admin tidak ditimpa data awal. Rancangan final (langkah 5 lewat WA) belum diubah dan menunggu revisi lewat draf |

## 13 November 2026 — kartu kelompok usia, logo kaki, dan kepala

| No | Keputusan | Catatan |
|---|---|---|
| K84 | Logo Bakti Olahraga Djarum Foundation pindah dari pembuka ke kanan kaki halaman di semua halaman publik, di atas kapsul putih. Label "Kelompok usia" diberi jarak lebih lega dari kartu di bawahnya. Kartu kelompok umur mengikuti contoh pemilik: latar krem, ombak lintasan di bawah (hijau muda dan oranye bergantian, tiga bentuk ombak bergiliran supaya tiap kartu berbeda) dengan garis lintasan putih, dan pelari biru tua bergaris laju di kiri. Garis tegak pemisah antara logo dan label seri di kepala dihapus | Mengubah letak logo Djarum dari K81. Logo tetap diunggah admin di Pengaturan > Halaman Depan; bila belum diunggah, kaki hanya berisi teks. Ombak dan pelari digambar SVG di kode (bukan gambar contoh), supaya tajam di semua ukuran |

## 13 November 2026 — gambar kartu kelompok usia, tautan halaman depan, rekap gabungan

| No | Keputusan | Catatan |
|---|---|---|
| K85 | Kartu kelompok usia memakai gambar dari pemilik (pelari biru tua dan lintasan hijau muda atau oranye, bergantian), tinggal diisi nama kelompok umur; pelari dan ombak SVG dari K84 dihapus. Kepala panel admin punya tombol "Halaman depan" yang membuka halaman depan di tab baru. Menu Rekap punya tombol "Unduh Rekap Gabungan": satu berkas Excel berisi semua data, satu sheet per bagian (Pendaftaran, Atlet, Peserta per Nomor Lomba, Tim Beregu, Per Sekolah, Per Kelompok Umur, Keuangan, Tim Tanpa Pembayar) | Mengubah tampilan kartu di K84. Gambar dipotong dari contoh pemilik ke `public/aset/gambar/kartu-ku-hijau.webp` dan `kartu-ku-oranye.webp` (tanpa data siswa). Rekap gabungan tidak memakai saringan status: semua pendaftaran ikut, sama dengan rekap Keuangan |

## 13 November 2026 — penanda versi aset dan judul panjang

| No | Keputusan | Catatan |
|---|---|---|
| K86 | Alamat CSS, JS, dan SVG aplikasi dari `aset()` diberi penanda versi `?v=<waktu ubah berkas>`, supaya Cloudflare dan peramban langsung memakai berkas baru sesudah `php spark aset:minify` atau pembaruan kode. Pustaka `vendor/` tanpa penanda. Judul pembuka satu kalimat hanya dipecah per kata bila paling banyak 4 kata; judul satu kalimat yang lebih panjang mengalir biasa | Temuan di situs produksi: Cloudflare menyimpan `tema.min.css` lama selama 4 jam (`cache-control: max-age=14400`), sehingga halaman baru memakai CSS lama dan tampil berantakan. Mengubah aturan judul di K83 untuk judul panjang |

## 13 November 2026 — bukti Lunas, nomor dada di bukti, dan daftar atlet wizard

| No | Keputusan | Catatan |
|---|---|---|
| K87 | Bukti PDF mencetak nomor dada bila pendaftaran sudah Lunas: kolom "No. Dada" di tabel individu dan nomor dada di belakang nama anggota tim beregu; selama belum Lunas kolomnya kosong. Nama berkas bukti ditambah ID pendaftaran dan status, contoh `BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_<NamaSekolah>_2026_MAC2A0000126_Lunas.pdf`, dan jawaban PDF dikirim dengan `Cache-Control: no-store, no-cache` serta `Pragma: no-cache`. Di wizard, tombol Hapus pada daftar atlet hanya menghapus nomor lomba di baris itu (daftar individu kini satu baris per nomor); atlet ikut terhapus bila tidak punya nomor lain. Kotak nama atlet tidak lagi ditulis ulang saat mengetik | Menggantikan K52 sebatas nomor dada di bukti dan K49 butir 3 (nama berkas). Laporan pemilik: tautan dan kode QR bukti tetap menampilkan "Belum Lunas" setelah ditandai Lunas. Server sudah membaca status terbaru dan Cloudflare tidak menyimpan PDF (`DYNAMIC`); penyebab yang paling mungkin adalah ponsel membuka berkas lama yang namanya sama, jadi nama berkas kini berbeda per status. Teks nama hilang setelah spasi di keyboard ponsel karena isian diubah ke huruf besar setiap ketikan; huruf besar tetap lewat CSS dan saat disimpan |

## 13 November 2026 — peringatan form, kode QR, nomor dada di cek, dan kartu kelompok usia

| No | Keputusan | Catatan |
|---|---|---|
| K88 | Di wizard pendaftaran, isian yang ditandai salah kembali normal begitu mulai diisi atau diubah (tanda merah dan pesannya hilang; ringkasan di atas langkah tersembunyi bila tidak ada isian salah lagi); semua isian tetap diperiksa ulang saat Lanjut. Kode QR bukti PDF pindah dari kepala ke pojok kanan bawah, setelah total, dengan ukuran 38 mm. Halaman Cek Pendaftaran menampilkan nomor dada setelah Lunas (tanda pisah sebelum Lunas): kolom "No. Dada" di tabel atlet, dan anggota tim beregu satu nama per baris dengan NISN dan nomor dada. Kartu kelompok usia di halaman depan menampilkan julukan (kecil, oranye) dan jumlah nomor individu dan beregu (kecil, tidak tebal, satu baris per jenis, jumlah 0 tidak ditulis); di ponsel kartu menjadi satu kolom | Menggantikan K52 untuk halaman cek (nomor dada kini tampil; tanggal lahir tetap tidak). Letak kode QR menggantikan K77. Di layar lebar gambar kartu dikecilkan ke 82 % tinggi kartu supaya teks muat di lima kolom |

## 13 November 2026 — jendela konfirmasi, bilah muat, pemilih tanggal, dan letak kode QR

| No | Keputusan | Catatan |
|---|---|---|
| K89 | Tindakan yang tidak bisa dibatalkan memakai jendela konfirmasi (bukan kotak bawaan peramban): di wizard hapus nomor/atlet, mulai dari awal, dan kirim bukti bayar; di Cek Pendaftaran kirim bukti bayar (beserta pemeriksaan ukuran 10 MB). Admin sudah memakai jendela yang sama (`konfirmasi.js`, kini juga `window.Konfirmasi.tanya()`). Bilah muat oranye tipis di atas halaman tampil di halaman publik dan admin saat formulir dikirim, saat tautan ke halaman lain dibuka, dan saat wizard menghubungi server (cari sekolah, periksa, simpan, unggah bukti); tidak untuk unduhan dan tab baru. Semua isian tanggal diganti pemilih Tanggal, Bulan (nama bulan), Tahun, ditambah Jam dan Menit untuk tanggal-waktu (`pilih-tanggal.js`, tanpa pustaka baru); isian asli tetap terkirim apa adanya. Kode QR bukti PDF di pojok kanan bawah halaman terakhir dan keterangannya di pojok kiri bawah sejajar QR, berbunyi "Keasliannya dapat diperiksa dengan memindai kode QR di sebelah kanan"; bila sisa ruang halaman tidak cukup, keduanya pindah ke halaman baru | Mengubah letak QR di K88 (tadinya langsung di bawah total). Rentang tahun pemilih tanggal: tanggal 25 tahun ke belakang, tanggal-waktu setahun ke belakang sampai dua tahun ke depan, kecuali isian punya `min`/`max` |
