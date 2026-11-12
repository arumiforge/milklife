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
