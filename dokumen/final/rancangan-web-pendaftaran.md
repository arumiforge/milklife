# Rancangan Web Pendaftaran MilkLife Athletics Challenge

| | |
|---|---|
| Status | **Disetujui pemilik pada 10 November 2026** (K17); revisi 1 (K27), revisi 2 (K35), dan revisi 3 (K38) disetujui 11 November 2026; **revisi 4 disetujui 12 November 2026**; **revisi 5 disetujui 14 November 2026** (K96); **revisi 6 disetujui 14 November 2026** (K100); **revisi 7 disetujui 15 November 2026** (K101); **revisi 8, 9, 10, dan 11 15 November 2026** (K105–K108, diubah langsung sesuai K101); **revisi 12, 13, dan 14 16 November 2026** (K109–K113, diubah langsung sesuai K101); **revisi 15 dan 16 17 November 2026** (K114, K115, diubah langsung sesuai K101); **revisi 17 dan 18 17 November 2026** (K116–K125, diubah langsung sesuai K101 dan K120); **revisi 19 18 November 2026** (K134, diubah langsung sesuai K101); **revisi 21 dan 22 18 November 2026** (K135, K136, diubah langsung sesuai K101); **revisi 23 21 November 2026** (K137, diubah langsung sesuai K101); **revisi 24 21 November 2026** (K140 butir 7b, diubah langsung sesuai K101); **revisi 25 21 November 2026** (K140, K141, K143, diubah langsung sesuai K101); **revisi 26 21 November 2026** (K140 butir 7a, diubah langsung sesuai K101); **revisi 27 22 November 2026** (K140 butir 8, diubah langsung sesuai K101); **revisi 28 23 November 2026** (K140 butir 7c dan 7d, diubah langsung sesuai K101); **revisi 29 22 November 2026** (penutupan Tahap 9, K138–K151, diubah langsung sesuai K101); **revisi 30 22 November 2026** (penutupan Tahap 10, K153–K168, diubah langsung sesuai K101); **revisi 31 22 November 2026** (peta halaman: Pengecekan admin, diubah langsung sesuai K101); **revisi 32 sampai 36 22 dan 23 November 2026** (K172–K175, diubah langsung sesuai K101); **revisi 37 23 November 2026** (penutupan Tahap 11, K172–K176, diubah langsung sesuai K101); **revisi 38 23 November 2026** (K179, diubah langsung sesuai K101); **revisi 39 23 November 2026** (K181, diubah langsung sesuai K101); **revisi 40 23 November 2026** (K182, diubah langsung sesuai K101); **revisi 41 24 November 2026** (K184, diubah langsung sesuai K101) |
| Tanggal | 10 November 2026, revisi 11, 12, 14, 15, 16, 17, 18, 21, 22, dan 23 November 2026; revisi 29 dan 30 22 November 2026; revisi 37 sampai 40 23 November 2026; revisi 41 24 November 2026 |
| Revisi 1 | Kelompok umur dibatasi jenjang sekolah yang diatur admin (K23, K24); asumsi 2 dan 3 dikonfirmasi (K21, K22); data awal lewat perintah sekali jalan (K25); halaman Akun hanya untuk akun sendiri (K26). Bagian yang berubah: 3, 5, 6.1, 6.4, 6.5, 7, 11, 17, lampiran |
| Revisi 2 | SLB tidak ikut (K28); web lewat Cloudflare Tunnel (K31); asumsi 4–7 dikonfirmasi dan asumsi 8 diganti (K32); bukti bayar diunggah admin (K34); awalan nomor atlet (BIB) awal per kelompok umur (K33). Bagian yang berubah: 6.2, 6.9, 9, 11, 13, 15, 17, lampiran |
| Revisi 3 | Kelompok umur ditentukan tahun lahir, bukan usia; batas tahun lahir diisi data awal; tanggal patokan usia dihapus, usia hanya ditampilkan (K36, K37). Bagian yang berubah: 5, 6.1, 6.4, 11, 14, lampiran |
| Revisi 4 | Disesuaikan dengan yang dibangun di Tahap 4–7: foto ofisial wajib (K50); batas nomor individu per atlet dan atlet per nomor (K51, K53); konfirmasi tim susulan (K57); tim tanpa pembayar (K68); ID pendaftaran tetap saat diubah (K63); cek dan bukti tanpa tanggal lahir dan nomor atlet (K49, K52); panel tanpa pendaftaran baru, nomor atlet atlet tambahan, saringan status rekap (K58–K60); sesi lain keluar saat ganti sandi dan halaman galat ramah (K67); CI GitHub (K46); cadangan harian (K66); juknis tidak ditunggu (K55). Bagian yang berubah: 5, 6.3, 6.5, 6.6, 6.8, 6.9, 8, 9, 13, 14, 15, 16, 17 |
| Revisi 5 | Kelompok umur ditentukan jenjang dulu, baru tahun lahir; rentang tahun lahir boleh beririsan antar-jenjang, tidak dalam satu jenjang (K95). Bagian yang berubah: 6.4, 17 |
| Revisi 6 | Data sekolah dari Sekolah Kita dan menu Sekolah di panel untuk memberi nama pembeda (K97); kelompok umur dan nomor lomba yang sudah dimatikan tetap boleh dipertahankan saat admin mengubah pendaftaran (K98); usulan nama sekolah dari pendaftar (K99). Disesuaikan juga dengan keputusan yang sudah berjalan: penulisan rupiah (K80), bukti transfer diunggah ofisial dan pendaftaran baru masuk bersama bukti transfer (K83, K90), alamat dan isi bukti PDF serta halaman cek (K77, K78, K87–K89, K91). Bagian yang berubah: 5, 6.2, 6.7, 7, 8, 9, 11, 15, 17 |
| Revisi 41 | Cloudflare Tunnel staging dan produksi memakai cloudflared di host VPS, tunnel yang sama dengan aplikasi lain di server itu (K184): app membuka port hanya di 127.0.0.1, dan container cloudflared menjadi pilihan. Bagian yang berubah: 4, 15 |
| Revisi 40 | Server pindah ke Docker di VPS Ubuntu 24.04 (K182, menggantikan K08 dan K18): PHP 8.5.13+ di FrankenPHP, MySQL 8.4, cloudflared sebagai container, cadangan harian lewat cron host yang disalin ke Cloudflare R2, OPcache tanpa Redis dan Memcached. Bagian yang berubah: 4, 15 |
| Revisi 39 | Peran juri (K181, F11-16): akun di panel berperan admin atau juri; satu akun per juri yang hanya membuka Lembar juri dan Akun miliknya; admin membuat, mengubah, dan menghapus akun juri di Pengaturan > Akun juri atau dengan `admin:buat --peran juri`; juri mengganti nama dan kata sandinya sendiri di Akun, nama penggunanya diganti admin. Bagian yang berubah: 3, 5, 9, 11, 13, 18 |
| Revisi 38 | Kartu KU dan pita fakta halaman depan (K179, F11-15): angka kelompok umur memenuhi kartu dan terpotong kotak di tepi bawah dan kanan, jumlah nomor individu dan beregu berupa ikon dan angka di bawahnya, dan lokasi Supersoccer Arena ditulis SSA di ponsel. Bagian yang berubah: 10 |
| Revisi 37 | Penutupan Tahap 11 (K172–K176, F11-08): bagian yang belum tercatat dilengkapi. **Cadangan** pindah ke Pengaturan > Cadangan berisi semua data, pengaturan, dan berkas unggahan, impor lewat panel mengganti semua setelah cadangan otomatis (K174 butir 10–12, F11-11); **ganti atlet oleh admin** (F11-01); **status nomor Tidak dilombakan dan batas peserta** (F11-03); **cara pencatatan waktu** tangan atau elektronik (F11-04); **nomor acara** per babak (F11-07); kolom dan tabel baru Tahap 11 di model data; pembaruan server Tahap 11 dan tanya-jawab bawaan baru lewat `data:sesuaikan` (59 tanya-jawab). Bagian yang berubah: 9, 11, 15, 18, 19 |
| Revisi 36 | Panel admin ringkas bagian kedua (K174 butir 4–9, F11-10): teks panjang dipotong dengan Selengkapnya, saringan dua isian per baris, tombol aksi ponsel tiga per baris, label Cari tersembunyi di ponsel, tombol unduh Excel/PDF ikon saja, dan label grafik dasbor ponsel tidak bertumpuk. Bagian yang berubah: 9 |
| Revisi 35 | Panel admin ringkas bagian pertama (K174 butir 1–3, F11-10): kepala halaman tanpa judul terlihat dengan kotak cari menu, tombol cari dan Keluar ikon saja di ponsel, jejak seragam diawali Dasbor, dan menu bawah ponsel Dasbor, Pendaftaran, Atlet, Rekap, Pengaturan. Bagian yang berubah: 9 |
| Revisi 34 | Halaman depan dan publik dirapikan (K175, F11-12): nomor lomba satu kelompok umur satu baris dengan kolom Putra dan Putri yang bisa disembunyikan, kartu kelompok umur berangka isian bertekstur (G11-01), latar hero kiri bertepi pudar (G11-02), pita fakta tanpa ikon, titik slide hanya di layar lebar, menu bawah publik Beranda, Bantuan, Daftar/Hasil, Cek, Jadwal/Unduhan, unduhan hasil ikon saja dengan jejak halaman, dan tombol sejenis satu baris di ponsel. Bagian yang berubah: 10, 18 |
| Revisi 33 | Rekor kejuaraan dicatat mulai Seri 2 (K172 butir 3, F11-06): rekor baru dari hasil Final yang terbit, tanda CR di hasil dan videotron, tarik terbitan mengembalikan rekor lama, rekor isian admin, spesifikasi alat per nomor, halaman `/rekor` bersakelar. Bagian yang berubah: 5, 11, 18 |
| Revisi 32 | Videotron menjadi daftar tampilan tetap yang dipilih operator (K173, F11-09): `/layar` berisi tautan per seri, per sesi, klasemen, kelompok umur, dan nomor lomba; tiap tautan satu tampilan layar penuh yang tidak berganti sendiri; papan putar lama pindah ke `/layar/putar`. Bagian yang berubah: 5, 10, 18 |
| Revisi 31 | Peta halaman dilengkapi halaman Pengecekan admin `/admin/pengecekan` (K102), tujuan tombol Buka Pengecekan di dasbor. Bagian yang berubah: 5 |
| Revisi 30 | Penutupan Tahap 10 (K153–K168, F10-11): data bawaan terbaru (`KU 8` tanpa "Seneng Playon", WA 0882-1527-7227 dengan tagar acara, media sosial resmi, logo Djarum Foundation baru, pesan WA bawaan) dan perintah aman `php spark data:sesuaikan` untuk server yang sudah terisi (K153, K154 butir 4, K166 butir 3); peta lokasi Supersoccer Arena dengan CSP `frame-src https://www.google.com`. Panel admin: sidebar biru tua dengan pojok logo biru, sidebar lebih lebar, menu dan submenu bisa dilipat, jejak navigasi di setiap halaman, semua tabel berpaginasi, dua kartu bukti bayar di dasbor, menu bawah ponsel Dasbor, Pendaftaran, Atlet, Rekap, Menu (K154 butir 3, K156 butir 3, K157 butir 1, K168). Gaya editorial: judul Barlow Condensed (K155), judul Inggris hiasan di atas judul Indonesia, rata tengah (K157 butir 3, K167), tanpa alis (K164), tanpa garis atas oranye, garis kiri, dan garis pendek (K160, K168); hero foto asli dengan slideshow 5 detik (K156 butir 4); navigasi publik dengan bilah submenu atas, Masuk panitia di bilah atas, laci kanan, dan menu bawah tanpa Menu dengan Cek paling kanan (K154, K157 butir 2, K168 butir 8–11); unduhan dengan Lihat sebagai modal (K168 butir 6); gambar Codex bergaya anime 2D cel-shaded dengan stadion mirip SSA (K153, K158); tata bahasa dengan skill `bahasa-indonesia-plain` (K159, K165, K166); wizard dengan tombol Kosongkan di judul langkah, jenjang dan kabupaten untuk ponsel, usulan nama yang dirapikan, pilihan terkunci yang jelas (K161–K163, K164 butir 2), dan nomor pendaftaran yang bisa disalin sekali klik (K168 butir 12). Bagian yang berubah: 2, 4, 5, 6.2, 7, 8, 9, 10, 12, 13, 14, 15, 16, 19, lampiran |
| Revisi 29 | Penutupan Tahap 9 (K138–K151, F9-18): tampilan **Flowbite Pro dengan Tailwind CSS 4** menggantikan Bootstrap 5 di semua halaman publik dan panel (ikon Lucide, hiasan Three.js, skeleton dan ilustrasi keadaan kosong); CSS dan JS dibangun dengan Node di komputer pengembang dan CI lalu di-commit, server tetap tanpa Node. Fitur publik baru dan sakelarnya (Bantuan selalu tampil; Jadwal, Seri, Peserta, Galeri, dan Videotron bawaan mati); jadwal pertandingan per babak; panel admin bersidebar berkelompok (Pendaftaran, Hari lomba, Hasil, Konten, Pengaturan); saluran gambar Codex; perintah sekali jalan pembaruan server Tahap 9. Bagian yang berubah: 2, 4, 5, 9, 10, 11, 12, 14, 15, 16, 18, 19 (baru) |
| Revisi 28 | Aturan nomor dan lembar juri (K140 butir 7c dan 7d, F9-09): nomor lompat/lempar punya banyak percobaan (bawaan 3, paling banyak 6) dan prestasi = jarak sah terjauh; prestasi sama dipisah prestasi terbaik kedua (lalu ketiga, dst.) bila percobaannya diisi, bisa dimatikan per nomor; lari dipisah waktu seperseribu detik bila diisi; data lama berperingkat sama. **Lembar juri** `admin/juri` untuk ponsel: pilih babak dan seri, kartu besar per peserta, papan angka, simpan otomatis per baris dengan tanda tersimpan, aman dipakai beberapa admin di babak berbeda. Bagian yang berubah: 9, 11, 18 |
| Revisi 27 | Seri, lintasan, dan alur babak (K140 butir 8, F9-08): urutan pelari kini dalam cakupan. Aturan seri per nomor (lintasan per seri bawaan 8; lompat/lempar urutan tampil), urutan lintasan unggulan bawaan World Athletics, aturan lolos Q/q per babak; bagi otomatis (Lunas dan hadir, sekolah sama dipisah, acak berbenih), geser, kunci; babak berikut terisi dari Q/q; unduhan daftar seri PDF/Excel per nomor dan per hari; halaman publik `jadwal/<babak>` dengan bagan alur babak bersakelar bawaan mati. Bagian yang berubah: 2, 5, 9, 11, 18 |
| Revisi 26 | Metode klasemen juara umum dipilih admin (K140 butir 7a, menggantikan K124 butir 5 sebagai satu-satunya cara): **Medali** (bawaan, juknis bagian 11) atau **Poin per peringkat** dengan poin peringkat 1–8 yang bisa diubah (bawaan 7-5-4-3-2-1-0-0) di Pengaturan > Hasil. Kolom poin tampil di klasemen admin, publik, PDF, dan Excel apa pun metodenya; pemecah seri metode poin: juara I, lalu II, lalu III. Juara umum di halaman depan ikut metode itu. Tanya-jawab bawaan memakai isian `{cara_juara_umum}`. Bagian yang berubah: 5, 10, 18 |
| Revisi 25 | Halaman depan, Unduhan, Hasil, dan Klasemen memakai tampilan baru Flowbite Pro (F9-03): carousel foto acara sebelumnya dengan indikator, tombol jeda, dan geser (foto bawaan dari pemilik, dikelola di Pengaturan > Halaman Depan), angka acara dari pendaftaran Lunas, hasil dan juara umum terbaru dengan sakelar hari lomba, tanya-jawab teratas, dan menu publik baru (K140, K141, K143). Bagian yang berubah: 5, 10, 12 |
| Revisi 24 | Kalkulator poin multi-lomba (K140 butir 7b, menggantikan K124 butir 3): jenis hasil **Poin multi-lomba**, admin mengisi prestasi mentah tiap komponen dan web menghitung poinnya dari tabel skor; tabel bawaan Trilomba O2SN SMP dan Pancalomba SMA/SMK dari tabel penilaian panitia; Pengaturan > Tabel Skor untuk mengubah baris dan mengimpor dari Excel; Backup data membawa tabel skor dan rincian poin. Komponen kosong ditulis "–" dengan 0 poin dan server lama memakai `tabel-skor:impor --trilomba` yang ikut diperiksa `pembaruan:periksa` (K143). Bagian yang berubah: 2, 9, 11, 15, 18 |
| Revisi 23 | Cetak nomor atlet (BIB) per sekolah sebagai PDF dan ZIP gambar JPG 300 dpi, ZIP semua sekolah satu folder per sekolah, satu latar penuh untuk semua BIB dengan pilihan kepala dan warna tulisan, dan gambar contoh (K137). Bagian yang berubah: 5, 18 |
| Revisi 22 | Bukti bayar per pendaftaran bawaan 100 gambar dan paling banyak 200 yang bisa diatur admin, menggantikan bawaan 5 dan paling banyak 20 di revisi 21 (K136). Bagian yang berubah: 9, 11 |
| Revisi 21 | Jumlah bukti bayar paling banyak per pendaftaran diatur admin di Pengaturan > Pembayaran (1–20, bawaan 5), berlaku untuk unggahan ofisial dan admin (K135). Bagian yang berubah: 9, 11 |
| Revisi 20 | CSP menyala sebelum lomba, dengan izin Cloudflare Turnstile; skrip dan gaya sebaris hanya dengan nonce (K127, F7-32). Bagian yang berubah: 13 |
| Revisi 19 | Rentang tahun lahir dua kelompok umur boleh beririsan dalam satu jenjang bila sakelar admin "Pendaftar memilih kelompok umur" menyala (bawaan); atlet yang tahun lahirnya masuk dua kelompok umur dipilihkan salah satunya oleh ofisial di wizard, dan admin bisa memindahnya di Ubah; kelompok umur jenjang lain tetap tidak bisa dipilih; keterangan untuk pendaftar di halaman depan dan wizard, untuk admin di halaman Kelompok Umur (K134). Bagian yang berubah: 5, 6.4, 7, 9, 10, 17 |
| Revisi 18 | Keputusan 17 November 2026: galat CSRF terlihat dan isian dikembalikan, kirim pendaftaran idempoten dengan `kunci_kirim`, Backup data tanpa dimuat utuh ke memori (K118); kesiapan server ditangani pemilik (K119); Cloudflare Turnstile di kirim pendaftaran, Cek Pendaftaran, dan masuk admin (K122); PHPStan (K123); cakupan bertambah dengan hari lomba, hasil lomba semua babak, dan klasemen sekolah per jenjang sebagai Tahap 8 (K124, K125). Fitur yang belum dibangun ditulis sebagai ketentuan rancangan. Bagian yang berubah: 2, 4, 5, 7, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18 (baru) |
| Revisi 17 | Admin menolak atlet satu per satu dari pendaftaran Menunggu; NISN-nya bisa didaftarkan lagi dan Cek Pendaftaran meminta sekolah mendaftarkan ulang (K116). Status tampilan "Semua atlet ditolak", bahasa untuk sekolah, tabel penolakan di bukti PDF, menu Atlet ditolak, dan jejak navigasi di panel (K117). Bagian yang berubah: 5, 6.4, 6.9, 8, 9, 11 |
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

**Termasuk:** halaman depan, pendaftaran, cek pendaftaran, unduhan, bukti pendaftaran PDF, panel admin (pengaturan, data pendaftaran, rekap, ekspor). Mulai Tahap 8 (K124): hari lomba (daftar ulang lewat kode QR bukti, tandai hadir, cetak nomor atlet massal, daftar peserta per nomor lomba), hasil lomba semua babak, dan klasemen sekolah per jenjang (18). Mulai Tahap 9 (K140, K141): tampilan baru Flowbite Pro (12), jadwal pertandingan per babak, seri dan lintasan (*start list*) serta alur babak, aturan nomor dan lembar juri, kalkulator poin multi-lomba, metode klasemen, papan videotron (18), serta Bantuan dan FAQ, peserta publik, dan galeri tautan luar (19). Tahap 10 (K153) tidak menambah fitur pendaftaran: data bawaan terbaru dan peta lokasi, panel admin yang lebih padat, gaya editorial halaman publik dengan gambar anime, dan perombakan tata bahasa (9, 10, 12).

**Tidak termasuk:** piagam, pembayaran otomatis, akun untuk ofisial, peran admin terpisah untuk hasil, tema gelap, banyak acara sekaligus dalam satu pemasangan, dan notifikasi WA (K119). Kesiapan server (alamat pemeriksa kesehatan `/sehat`, pemantauan uptime, tembolok halaman depan, `client_max_body_size`, `memory_limit`, uji pulih cadangan) **ditangani pemilik sendiri**, bukan bagian pekerjaan web ini (K119).

## 3. Pengguna

| Pengguna | Akses | Yang dilakukan |
|---|---|---|
| Ofisial sekolah | Tanpa akun | Mendaftar, mengirim konfirmasi pembayaran lewat WA, mengunduh bukti, memeriksa rekap |
| Admin | Masuk dengan nama pengguna dan kata sandi | Mengatur semua aturan, memeriksa pembayaran, mengelola data, mengunduh rekap; mulai Tahap 8 juga daftar ulang, hasil lomba, dan klasemen dengan akun yang sama (K124). Boleh ada lebih dari satu akun; tiap admin hanya mengubah akunnya sendiri dari panel, akun admin lain dibuat lewat perintah `admin:buat` (K26), dan akun juri dibuat di Pengaturan > Akun juri (K181) |
| Juri | Akun sendiri per juri, dibuat admin (K181) | Hanya mengisi prestasi di Lembar juri dan mengganti nama serta kata sandinya sendiri di Akun (nama pengguna juri diganti admin); halaman admin lain tidak bisa dibuka |

## 4. Pendekatan teknis

**Dipilih: aplikasi monolit CodeIgniter 4 dengan halaman dirender server**, Tailwind CSS 4 dengan Flowbite Pro (sejak Tahap 9, K140, K141; sebelumnya Bootstrap 5), dan JavaScript biasa. Tabel admin mengambil data dari titik akhir JSON.

Dua pendekatan lain ditolak:

- **API + aplikasi satu halaman (Vue/React):** butuh Node di server dan proses bangun di setiap pemasangan, terlalu berat untuk tim dan pemasangan Laragon. Node hanya dipakai di komputer pengembang dan CI untuk membangun CSS (K141); hasilnya di-commit.
- **Wizard banyak halaman dengan sesi server:** bertentangan dengan permintaan draf tersimpan di peramban.

| Kebutuhan | Pilihan |
|---|---|
| Kerangka kerja | CodeIgniter 4 (rilis stabil terbaru), PHP 8.5.13+ (K182) |
| Basis data | MySQL 8.4 LTS, InnoDB, `utf8mb4` |
| Server | Docker Compose di VPS Ubuntu 24.04 untuk staging dan produksi: FrankenPHP (mode biasa) dengan OPcache dan MySQL 8.4, tanpa port ke jaringan luar; Cloudflare Tunnel lewat cloudflared di host (K182, K184). Komputer pengembang boleh memakai Laragon dengan PHP 8.5 |
| Tampilan | Flowbite Pro (templat berlisensi pengembang, K138, K141) di atas Tailwind CSS 4, plugin dan JS `flowbite`, `flowbite-typography`, dan `flowbite-datepicker`; ikon Lucide (sprite SVG), hiasan Three.js; huruf badan Inter dan huruf judul Barlow Condensed (OFL, K155), keduanya disimpan lokal tanpa Google Fonts. CSS dibangun `npm run bangun` di komputer pengembang dan CI menjadi `public/aset/css/tw.css`, lalu di-commit bersama pustaka di `public/aset/vendor/`; server produksi tanpa Node dan tanpa CDN. Bootstrap 5 dan Bootstrap Icons dilepas di Tahap 9 (F9-17) |
| Tabel data (gaya) | DataTables tetap (jQuery), bergaya Tailwind lewat `tabel-admin.js` |
| Tabel data | DataTables 2 mode sisi-server (halaman, saring, urut) |
| PDF | mPDF |
| Excel | PhpSpreadsheet |
| Pengaturan | `codeigniter4/settings` (pengaturan tersimpan di basis data) |
| Minify | `matthiasmullie/minify` untuk CSS, JS, dan SVG lewat `php spark aset:minify`; berkas asli tetap ada (K72). Alamat aset diberi penanda versi `?v=<waktu ubah berkas>` supaya Cloudflare dan peramban langsung memakai berkas baru (K86) |
| Masuk admin | Sesi bawaan CodeIgniter + `password_hash`, tanpa pustaka tambahan |
| Penahan bot | Cloudflare Turnstile, tanpa pustaka PHP: satu panggilan HTTP lewat `CURLRequest` bawaan CodeIgniter (K122, pengecualian aturan tidak menambah pustaka) |
| Pemeriksa kode | PHPStan sebagai pustaka pengembangan (`require-dev`, tidak terpasang di produksi), mulai dari tingkat rendah dan ikut di CI (K123) |

HTML diperkecil saat dikirim ke peramban (filter keluaran, aktif di produksi), jadi berkas tampilan asli tidak berubah.

## 5. Peta halaman

Semua URL dan teks antarmuka berbahasa Indonesia.

**Publik**

| URL | Isi |
|---|---|
| `/` | Halaman depan, termasuk peta lokasi lomba (Tahap 10, F10-02) |
| `/pendaftaran` | Wizard pendaftaran |
| `/cek-pendaftaran` | Cek lewat NPSN atau ID pendaftaran |
| `/unduhan` | Flyer, juknis, berkas lain; PDF dan gambar bisa dilihat dulu di modal lewat `unduhan/<id>/lihat` (F10-09, K168 butir 6) |
| `/bukti/{kode-acak}/{id-pendaftaran}` | Bukti pendaftaran PDF; kode acak 24 huruf supaya alamatnya tidak bisa ditebak (K77) |
| `/bantuan` | Bantuan dan tanya-jawab per kategori, bisa dicari, panduan bergambar langkah pendaftaran, tautan WA panitia; selalu tampil (Tahap 9, F9-04) |
| `/hasil` | Hasil lomba per nomor yang sudah diterbitkan admin (Tahap 8, K124) |
| `/hasil/klasemen` | Klasemen sekolah per kelompok umur dari nomor yang sudah terbit (Tahap 8, K124, K172) |
| `/rekor` | Rekor kejuaraan yang berlaku per kelompok umur dan nomor lomba; bersakelar, bawaan mati (Tahap 11, K172, F11-06) |
| `/jadwal` | Jadwal pertandingan per hari dari babak yang diberi tanggal dan jam, status otomatis, PDF per hari; bersakelar, bawaan mati (Tahap 9, F9-07) |
| `/peserta` | Peserta publik: cari nama, sekolah, nomor atlet; saring kelompok umur, nomor lomba, putra/putri; hanya pendaftaran Lunas; bersakelar, bawaan mati (Tahap 9, F9-12, K144) |
| `/galeri` | Album foto bersampul yang menaut ke Instagram atau Google Drive; bersakelar, bawaan mati (Tahap 9, F9-06) |
| `/jadwal/<babak>` | Seri dan lintasan babak yang sudah dikunci dan bagan alur babak nomornya; bersakelar, bawaan mati (Tahap 9, K140 butir 8) |
| `/layar` | Daftar tampilan videotron untuk operator: tautan per hari dan sesi (per seri dan semua hasil sesi), klasemen, kelompok umur, dan nomor lomba; tampil setelah dinyalakan di Pengaturan > Videotron (Tahap 9, K141; K173) |
| `/layar/seri/<babak>/<seri>`, `/layar/sesi/<tanggal>/<bagian>`, `/layar/klasemen/<kunci>`, `/layar/kelompok-umur/<id>`, `/layar/nomor/<id>` | Satu tampilan videotron tetap layar penuh, tidak berganti sendiri; hanya data terbit (K173) |
| `/layar/putar` | Papan videotron putar: hasil terbaru, juara umum, sedang berlangsung, jadwal berikutnya bergantian (Tahap 9, K141; pindah dari `/layar`, K173) |

**Admin** (semua di bawah `/admin`)

| URL | Isi |
|---|---|
| `/admin/masuk` | Halaman masuk |
| `/admin/dasbor` | Ringkasan angka, grafik, dan kartu keadaan pendaftaran dengan tombol Buka Pengecekan |
| `/admin/pengecekan` | Pengecekan: keadaan pendaftaran, daftar centang kelengkapan pengaturan (bagian 6.1), dan gambar kepala halaman depan (K102) |
| `/admin/pendaftaran` | Daftar dan rincian pendaftaran, bukti bayar, tandai lunas, ubah, hapus. Tidak ada pendaftaran baru dari panel (K58) |
| `/admin/pendaftaran/{id}/tolak-atlet` | Tolak atlet satu per satu dari pendaftaran Menunggu (K116) |
| `/admin/atlet` | Daftar semua atlet |
| `/admin/atlet-ditolak` | Semua catatan atlet yang ditolak panitia, dengan saringan sudah atau belum didaftarkan ulang dan unduh Excel (K117) |
| `/admin/riwayat` | Cari riwayat tindakan admin lewat ID pendaftaran, juga untuk pendaftaran yang sudah dihapus (Tahap 8, K124) |
| `/admin/daftar-ulang` | Hari lomba: pindai kode QR bukti atau ketik ID, lihat status dan atlet, tandai hadir (Tahap 8, K124) |
| `/admin/nomor-atlet/cetak` | Cetak nomor atlet (BIB) massal per kelompok umur (PDF) dan per sekolah (PDF, ZIP gambar), ZIP semua sekolah, latar BIB (Tahap 8, K124, K137) |
| `/admin/daftar-peserta` | Daftar peserta per nomor dan per kelompok umur (PDF, Excel) untuk pemanggilan atlet dan juri (Tahap 8) |
| `/admin/jadwal-pertandingan` | Tanggal, jam mulai, dan lokasi semua babak sekaligus, PDF per hari, sakelar halaman Jadwal (Tahap 9, F9-07) |
| `/admin/juri` | Lembar juri ponsel: pilih babak dan seri, isi prestasi per peserta, simpan otomatis (Tahap 9, K140 butir 7d); satu-satunya halaman kerja akun juri (K181) |
| `/admin/hasil` | Babak, prestasi, peringkat, dan Terbitkan per nomor lomba (Tahap 8, K124) |
| `/admin/seri` | Seri dan lintasan: aturan seri per nomor, bagi otomatis, geser, aturan lolos Q/q, kunci, unduhan daftar seri per nomor dan per hari (Tahap 9, K140 butir 8) |
| `/admin/klasemen` | Klasemen sekolah per kelompok umur, termasuk nomor yang belum terbit (Tahap 8, K124, K172) |
| `/admin/rekor` | Rekor kejuaraan (berlaku dan riwayat), tambah dan ubah rekor isian admin, sakelar halaman Rekor (Tahap 11, K172, F11-06) |
| `/admin/rekap` | Rekap per nomor lomba, tim beregu, per sekolah, per kelompok umur, keuangan |
| `/admin/sekolah` | Data sekolah: daftar dengan saringan nama kembar, ubah nama dan data lain (K97) |
| `/admin/sekolah/nama-desa` | Tambahkan nama desa ke semua sekolah bernama sama sekaligus (K114) |
| `/admin/sekolah/usulan` | Usulan nama sekolah dari pendaftar: setujui atau tolak (K99) |
| `/admin/pengaturan` | Daftar semua halaman pengaturan beserta tanda lengkap atau belum |
| `/admin/pengaturan/acara` | Nama, seri, tahun, tempat, tanggal acara |
| `/admin/pengaturan/pendaftaran` | Jadwal buka-tutup, buka manual, tampilan usia, batas-batas |
| `/admin/pengaturan/kelompok-umur` | Batas tahun lahir, jenjang sekolah yang boleh ikut, biaya individu, awalan nomor atlet; sakelar "Pendaftar memilih kelompok umur" dan daftar tahun lahir yang masuk dua kelompok umur per jenjang (K134) |
| `/admin/pengaturan/nomor-lomba` | Nomor lomba, jenis, jumlah anggota, biaya beregu |
| `/admin/pengaturan/pembayaran` | Bank, rekening, WA pemilik rekening, isi pesan |
| `/admin/pengaturan/halaman-depan` | Gambar, logo, foto carousel pembuka, judul, teks, tombol, footer, isi pesan WA tanya dan bagikan, butir bilah submenu atas, alamat peta dan tautan buka peta (F10-02, F10-07) |
| `/admin/pengaturan/jadwal` | **Agenda**: jadwal sosialisasi, pertemuan teknik, dan lainnya (K146) |
| `/admin/pengaturan/unduhan` | Berkas unduhan |
| `/admin/pengaturan/kontak` | Narahubung dan media sosial |
| `/admin/pengaturan/bantuan` | Tanya-jawab Bantuan: tambah, ubah, urutkan, matikan, pasang bawaan (Tahap 9, F9-04) |
| `/admin/pengaturan/galeri` | Album galeri dan sakelar halaman Galeri (Tahap 9, F9-06) |
| `/admin/pengaturan/tabel-skor` | Tabel skor poin multi-lomba: pasang bawaan, ubah baris, impor dari Excel (Tahap 9, K140 butir 7b) |
| `/admin/pengaturan/videotron` | Sakelar semua alamat videotron, gambar latar, isi dan lama tiap tampilan papan putar (Tahap 9, K141; K173) |
| `/admin/pengaturan/hasil` | Metode klasemen juara umum (Medali atau Poin per peringkat) dan poin peringkat 1–8 (K140 butir 7a) |
| `/admin/pengaturan/akun-juri` | Daftar akun juri; tambah, ubah (kata sandi boleh dikosongkan), dan hapus akun juri (K181) |
| `/admin/akun` | Ganti nama, nama pengguna, dan kata sandi akun sendiri; akun juri hanya nama dan kata sandi (K181) |
| `/admin/gaya` | Katalog tampilan (komponen Flowbite Pro) untuk menilai gaya; di luar menu, bisa dibuka langsung (K151) |

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
- **Usulan nama** (K99): bila nama yang tampil juga dipakai sekolah lain, wizard menonjolkan tawaran mengusulkan nama lengkap, misalnya dengan nama desa; untuk sekolah lain tersedia tautan "Nama sekolah kurang lengkap?". Sejak Tahap 10 tawaran itu ada di dalam kotak sekolah yang ditemukan ("Nama sekolah kurang lengkap?" dengan tautan berikon "Usulkan nama lengkap"), dan isian usulan berada di kotak abu-abu "Usulan nama lengkap sekolah" dengan dua butir keterangan singkat dan tombol Batal; tombol Batal tidak ada bila nama sekolah kembar, karena usulan dianjurkan (K163 butir 2). Pendaftaran itu langsung memakai nama usulan, lalu admin memutuskan. Disetujui: nama di data sekolah dan semua pendaftaran dengan NPSN itu diganti. Ditolak: pendaftaran pengusul kembali memakai nama dari data sekolah.
- Sekolah tanpa NPSN tetap bisa mendaftar dengan nama sekolah saja.
- Satu NPSN boleh mendaftar lebih dari sekali. Semua pendaftaran dengan NPSN yang sama dianggap satu sekolah.
- Pendaftaran tanpa NPSN berdiri sendiri dan hanya bisa dicek lewat ID pendaftaran.

### 6.3 Ofisial

Nama, nomor WA, dan foto. **Foto wajib diisi** (K50); foto diperkecil di peramban, disimpan di luar folder publik, dan hanya dilihat admin. Jumlah minimal 1 dan maksimal 5; kedua batas bisa diubah admin.

### 6.4 Atlet

- Nama lengkap (otomatis huruf besar), NISN tepat 10 digit, tanggal lahir, jenis kelamin (laki-laki/perempuan).
- **NISN yang sudah terdaftar ditolak**, termasuk pada pendaftaran susulan. Perubahan untuk atlet yang sudah terdaftar dilakukan admin. Atlet yang **ditolak panitia** (9, K116) dikeluarkan dari pendaftarannya, sehingga NISN-nya bisa didaftarkan lagi di kelompok umur yang sesuai.
- **Kelompok umur ditentukan otomatis dari jenjang sekolah lalu tahun lahir** (K95, K36). Tahun lahir dibaca tanpa memperhatikan bulan dan tanggal. Tiap kelompok umur punya tahun lahir paling awal dan paling akhir; salah satunya boleh kosong, artinya tidak dibatasi di sisi itu (contoh KU 8: lahir 2019 ke atas).
- Usia hanya ditampilkan sebagai keterangan, tidak menentukan apa pun (K37). Usia dihitung pada tanggal mulai acara; tampilan "tahun" atau "tahun dan bulan" diatur admin. Tidak ada lagi tanggal patokan usia.
- **Jenjang dulu, baru tahun lahir** (K95). Admin mencentang jenjang yang boleh ikut pada tiap kelompok umur (K23, K24); tidak ada nilai tertulis di kode. Dari jenjang sekolah pendaftaran, hanya kelompok umur yang dicentang untuk jenjang itu yang dipertimbangkan; di antaranya dicari yang rentang tahun lahirnya cocok.
- Bila tidak ada yang cocok, atlet ditolak dengan pesan yang menyebut kelompok umur yang dibuka untuk jenjang itu beserta tahun lahirnya, dan kelompok umur jenjang lain yang menerima tahun lahir atlet bila ada.
- **Rentang tahun lahir boleh beririsan antar-jenjang** (K95). Contoh: bila KU 12 (SD/MI) dan KU 15 (SMP/MTs) sama-sama menerima kelahiran 2012, atlet lahir 2012 dari SD masuk KU 12 dan dari SMP masuk KU 15. Atlet tidak pernah bisa masuk kelompok umur yang tidak dibuka untuk jenjang sekolahnya: atlet SMP/MTs tidak bisa turun ke kelompok umur SD/MI, dan sebaliknya.
- **Dalam satu jenjang, rentang boleh beririsan dan ofisial memilih** (K134), selama sakelar **"Pendaftar memilih kelompok umur"** di Pengaturan > Kelompok Umur menyala (nilai awal menyala). Contoh dari panitia: KU 10 lahir 2016–2018 dan KU 12 lahir 2014–2016, keduanya SD/MI. Atlet SD lahir 2016 boleh ikut KU 10 atau KU 12:
  - Di formulir atlet wizard, setelah tanggal lahir diisi, muncul kotak **"Pilih kelompok umur"** berisi tombol tiap kelompok umur yang cocok beserta tahun lahirnya. Tidak ada yang terpilih; nomor lomba baru tampil setelah ofisial memilih, dan atlet tidak bisa ditambahkan ke daftar sebelum memilih.
  - Satu atlet hanya ikut **satu** kelompok umur. Nomor lomba tetap milik kelompok umurnya masing-masing, jadi atlet hanya bisa memilih nomor di kelompok umur yang dipilih, dan biaya individunya mengikuti kelompok umur itu.
  - Atlet yang tahun lahirnya hanya masuk satu kelompok umur tidak melihat kotak pilihan; kelompok umurnya tetap ditentukan otomatis.
  - Server memeriksa ulang pilihan itu: kiriman tanpa pilihan, atau dengan kelompok umur di luar pilihan yang sah, ditolak dengan pesan yang menyebut pilihannya.
  - Bila sakelar mati, satu tahun lahir hanya boleh masuk satu kelompok umur aktif per jenjang (K95): halaman pengaturan menolak rentang yang beririsan dengan kelompok umur aktif lain yang dibuka untuk jenjang yang sama. Sakelar tidak bisa dimatikan selama irisan seperti itu masih ada. Kelompok umur yang belum dicentang jenjangnya tidak dibandingkan.
- Batas tahun lahir diisi data awal sesuai ketetapan pemilik (K36) dan juknis resmi (KU 12 lahir 2014–2015, K112), dan bisa diubah admin. Perubahan rentang dari panitia (misalnya KU 12 menjadi 2014–2016) diisi admin di panel; data awal tidak diubah (K134).

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
- **Semua atlet ditolak** (K117): pendaftaran Menunggu yang tidak punya atlet lagi karena semua atletnya ditolak panitia (9) tampil berstatus **"Semua atlet ditolak"** (merah) di Cek Pendaftaran, bukti PDF, dan daftar pendaftaran admin. Status tersimpan tetap Menunggu; totalnya Rp 0, dan pendaftaran itu tidak dihitung di kartu dasbor "Menunggu, bukti sudah ada" (dulu "Bukti bayar perlu diperiksa").
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

Wizard satu halaman dengan pil langkah bercentang. Kartu isian hanya berbingkai abu-abu tipis, tanpa garis atas oranye (K160, menggantikan bagian K77 itu). Judul tiap langkah punya tombol **Bantuan** dan, di kanannya, tombol **Kosongkan** (K161); di layar sm ke atas keduanya berikon dan berlabel, di ponsel ikon saja dengan nama tetap terbaca pembaca layar ("Kosongkan isian"). Popover tombol Kosongkan menjelaskan akibatnya, dan jendela konfirmasi tetap muncul sebelum isian dikosongkan (K89). Tombol ini menggantikan tautan "Kosongkan isian dan mulai dari awal" di bawah formulir. Isian dan pilihan yang terkunci karena aturan tampil abu-abu bergaris putus dengan kursor terlarang; tombol pilihan yang terpilih dan terkunci berwarna abu-abu tua (K164 butir 2).

1. **Data Sekolah** — jenjang, kabupaten, NPSN atau nama sekolah, dan usulan nama bila nama sekolah kurang lengkap (6.2). Jenjang yang dipilih di sini menentukan kelompok umur yang boleh diikuti atlet (6.4). Di layar sm ke atas jenjang dipilih dengan tombol SD/MI, SMP/MTs, SMA/SMK/MA; di ponsel berupa daftar pilihan "Pilih jenjang sekolah" (K162). Kabupaten di ponsel dipilih lewat panel berkotak "Cari kabupaten" yang menyaring daftar saat mengetik (Enter memilih hasil pertama, Esc menutup); daftar pilihan biasa tetap menjadi sumber nilai dan tampil di layar sm ke atas atau bila JavaScript mati (K163 butir 1). Bila jenjang dan kabupaten mengikuti data sekolah, keduanya terkunci dengan keterangan bergembok "Terkunci, mengikuti data sekolah." (K164 butir 2).
2. **Ofisial** — 1 sampai 5 orang.
3. **Atlet** — formulir atlet dan daftar sementara.
4. **Biaya** — rincian dan konfirmasi.
5. **Pembayaran** — rekening, unggah bukti transfer, kirim pendaftaran, selesai.

**Langkah 3.** Bila tahun lahir atlet masuk lebih dari satu kelompok umur jenjang sekolahnya, kotak "Pilih kelompok umur" tampil di bawah jenis kelamin dan nomor lomba menunggu pilihan itu (6.4, K134); daftar sementara menulis kelompok umur yang dipilih. Nomor beregu tampil sebagai kartu: nama nomor, lalu keterangan singkat per regu (jumlah inti, cadangan, syarat mixed, batas regu, biaya per regu). Nomor yang dicentang menampilkan pilihan regu (daftar admin, atau kotak isian pada mode Bebas) dan tombol Inti/Cadangan; pilihan bawaannya regu yang intinya belum lengkap, lalu regu baru (K105, K106). Selama sekolah baru punya satu regu di nomor itu, pilihan regu disembunyikan dan hanya tombol Inti/Cadangan yang tampil, dengan tautan "Daftarkan regu lain"; nomor yang hanya membolehkan satu regu tidak menampilkan tautan itu (K108). Setelah atlet ditambahkan, daftar sementara langsung terbentuk dalam dua bagian: daftar individu dan daftar regu beregu. Setiap regu satu kartu: nama nomor, nama regu (tidak ditulis untuk regu tunggal, K108), status (Lengkap/Belum lengkap), jumlah atlet, dan biaya regu; anggotanya satu baris ringkas per atlet bernomor urut, dikelompokkan **Inti** ("4 dari 4") lalu **Cadangan** ("1 dari paling banyak 4") (K107). Setiap baris punya tombol ubah dan hapus. Sebelum lanjut, sistem memeriksa kelengkapan setiap regu.

**Langkah 4.** Dua tabel terpisah:

- Individu: No, Nama, NISN, Kelompok Umur, Nomor Lomba, Biaya, lalu subtotal individu.
- Beregu: No, Nomor Lomba Beregu (dengan regu bila sekolah punya lebih dari satu regu di nomor itu, jumlah inti dan cadangan), Biaya, lalu subtotal beregu.

Di bawahnya total pembayaran: subtotal individu, subtotal beregu, dan jumlah keduanya. Langkah ini belum menyimpan apa pun (K90).

**Langkah 5.** Menampilkan logo bank, nomor rekening, nama pemilik rekening, dan total. Ofisial mentransfer, memilih foto atau tangkapan layar bukti transfer, lalu menekan **Kirim Pendaftaran** dan mengonfirmasi. **Pendaftaran baru masuk sistem saat itu, bersama bukti transfernya** (K90): tanpa bukti tidak ada yang tersimpan, dan isian tetap di draf sehingga ofisial bisa kembali lain waktu dan tinggal mengunggah bukti. Setelah terkirim, ID pendaftaran dibuat, draf dikosongkan, dan layar selesai menampilkan tombol cetak bukti, salin ID, dan konfirmasi lewat WA ke pemilik rekening (pesan berisi nama sekolah, NPSN, ID pendaftaran, subtotal individu, subtotal beregu, dan total). Status tetap Menunggu Konfirmasi Pembayaran sampai panitia memeriksa transfernya.

**Salin ID sekali klik** (K168 butir 12): setiap ID pendaftaran yang tertulis di halaman publik (wizard, layar selesai, Cek Pendaftaran, dan isi jendela konfirmasi) bergaris bawah putus-putus dengan ikon salin. Sekali klik menyalin ID ke papan klip dan memunculkan pesan singkat "ID … tersalin". Tanpa JavaScript ID tampil sebagai teks biasa.

**Kirim ulang aman** (K118): wizard membuat `kunci_kirim` acak sekali untuk setiap pendaftaran yang akan dikirim dan menyimpannya di draf. Bila jaringan putus atau tombol ditekan lagi, kiriman dengan kunci yang sama menjawab pendaftaran yang sudah tersimpan (ID dan layar selesai yang sama) tanpa menulis apa pun lagi, sehingga tidak ada pendaftaran ganda. Kunci dijaga unik oleh basis data. Draf dikosongkan dan kunci baru dibuat setelah pendaftaran selesai.

**Penahan bot** (K122): saat Turnstile aktif (13), tombol Kirim Pendaftaran baru mengirim setelah pemeriksaan Cloudflare Turnstile lulus. Token Turnstile sekali pakai, jadi wizard meminta token baru sebelum mengirim ulang, termasuk pada kirim ulang dengan `kunci_kirim` yang sama.

**Draf.** Isian tersimpan di peramban (`localStorage`) pada setiap perubahan, sehingga tidak hilang saat halaman dimuat ulang. Foto ofisial diperkecil di peramban (sisi terpanjang 800 piksel, JPEG) dan ikut tersimpan di draf.

**Validasi.** Setiap isian diperiksa di peramban untuk kenyamanan dan diperiksa lagi seluruhnya di server. Isian yang ditandai salah kembali normal begitu mulai diubah (K88). Penyimpanan berlangsung dalam satu transaksi: tersimpan semua atau tidak sama sekali.

**Kenyamanan** (K89): tindakan yang tidak bisa dibatalkan (hapus nomor atau atlet, kosongkan isian, kirim pendaftaran, kirim bukti bayar) memakai jendela konfirmasi, bukan kotak bawaan peramban. Isian tanggal memakai pemilih Tanggal, Bulan (nama bulan), dan Tahun. Bilah muat tipis di atas halaman menyala saat wizard menghubungi server.

## 8. Cek pendaftaran dan bukti

**Cek.** Ofisial memasukkan NPSN atau ID pendaftaran.

- NPSN menampilkan semua pendaftaran sekolah itu berurutan per pendaftaran: data sekolah sekali di atas, satu kartu per pendaftaran, dan total keseluruhan di bawah (K49).
- ID pendaftaran menampilkan satu pendaftaran. ID lama yang sudah digabung (6.9) menampilkan pendaftaran gabungannya dengan keterangan "Pendaftaran … sudah digabung ke …" (K109).
- Data tampil sesuai MoU: sekolah, ofisial, atlet (nama, NISN, jenis kelamin, kelompok umur, nomor lomba), regu beregu beserta anggotanya (inti dan cadangan, K105), biaya, dan status pembayaran.
- **Tanggal lahir tidak ditampilkan** (K52); tetap tersimpan dan terlihat di panel admin. Nomor atlet tampil setelah pendaftaran Lunas (K88).
- Pendaftaran yang belum Lunas punya kotak unggah bukti transfer tambahan (K83).
- Saat Turnstile aktif, pencarian Cek Pendaftaran memakai pemeriksaan Turnstile yang sama (13, K122).
- **Atlet ditolak panitia** (K116, K117): bila ada atlet yang ditolak, Cek Pendaftaran (lewat NPSN maupun ID) menampilkan ringkasan "N atlet ditolak panitia" dan, di kartu pendaftarannya, bagian **"Perlu didaftarkan ulang"** dengan bahasa untuk sekolah: nama, NISN, jenis kelamin, tahun lahir (tanggal lahir tetap tidak, K52), yang didaftarkan, alasan panitia, kelompok umur dan pilihan nomor lomba yang disarankan, lalu langkah berikutnya: daftarkan ulang (tombol "Daftarkan ulang") dan hubungi panitia bila uang sudah terlanjur ditransfer (tombol WA tanya admin bila diisi). Total sebelum dan sesudah, waktu penolakan, nama admin, dan biaya per nomor hanya ada di panel admin.
- Begitu NISN atlet yang ditolak terdaftar lagi (wizard, pendaftaran lain, atau Ubah admin), keterangan lengkapnya hilang dan atlet itu hanya disebut namanya sebagai tanda pendaftaran ulangnya sudah masuk. ID pendaftaran barunya tidak ditulis, karena NISN itu bisa didaftarkan sekolah lain (K117).
- Pendaftaran **Semua atlet ditolak** (6.9) tidak menampilkan tabel atlet, regu, rincian biaya, dan kotak unggah bukti bayar; yang tampil total Rp 0 dengan "Tidak ada tagihan untuk pendaftaran ini" (K117).
- Bila nomor atlet sudah diurutkan ulang panitia (6.9, K111), kartu pendaftaran Lunas menulis keterangan di atas tabel atlet: kapan nomor diurutkan ulang, dan bahwa nomor di bukti yang dicetak sebelumnya tidak berlaku.
- Nama nomor lomba ditulis bersama kategorinya, misalnya "Lari 60 m Putra" atau "Estafet 8 × 50 m Mixed".
- Foto ofisial hanya tampil di panel admin.
- Kartu pendaftaran Lunas bergaris atas hijau dan kartu Semua atlet ditolak bergaris atas merah karena warnanya bermakna status; kartu Menunggu hanya berbingkai abu-abu tipis (K160). ID pendaftaran di kartu bisa disalin sekali klik (7, K168 butir 12).

**Bukti PDF.** Kertas F4 tegak (215 × 330 mm) (K77).

- Kepala: logo persegi MilkLife, nama acara, seri, dan tahun, dengan garis oranye di bawahnya. **Kepala diulang di setiap halaman**, begitu juga kaki halaman (ID pendaftaran, waktu cetak, nomor halaman) (K108).
- Data pendaftaran bergaya formulir resmi (K108): **nama sekolah di blok sendiri paling atas** karena bisa panjang, lalu tabel berlabel berisi ID pendaftaran (dengan tulisan kecil "Gabungan dari …" bila ada pendaftaran yang digabung ke situ, K109), tanggal pendaftaran, NPSN, jenjang, kabupaten/kota, status pembayaran, dan ofisial. Ofisial ditulis sebaris: nama dan nomor WA, dipisah koma bila lebih dari satu (K52, K83).
- Tabel individu satu baris per atlet per nomor lomba (dengan kategori), dan tabel beregu **satu baris per anggota regu**: No, nomor lomba, regu, dan biaya regu berupa **sel gabungan setinggi anggota regunya**, lalu nama atlet, NISN, nomor atlet, dan peran (Inti/Cadangan) di kolom masing-masing; inti dulu (K105, K107, K108). Kolom regu hanya ada bila sekolah punya lebih dari satu regu di salah satu nomor; regu tunggal tidak ditulis nama regunya. Lalu total pembayaran.
- Tanggal lahir tidak dicetak. Nomor atlet dicetak setelah pendaftaran Lunas, di tabel individu dan di belakang nama anggota tim; sebelum Lunas kolomnya kosong (K87).
- Bila nomor atlet sudah diurutkan ulang panitia (6.9, K111), bukti pendaftaran Lunas menulis keterangannya beserta waktunya di dasar halaman terakhir, di atas keterangan keaslian.
- Status pembayaran berupa stempel miring di bagian akhir: hijau "LUNAS / PEMBAYARAN DITERIMA" atau oranye "MENUNGGU / KONFIRMASI PANITIA" (K78, K91).
- Judul dokumen ("BUKTI PENDAFTARAN") dan stempel tetap berhuruf besar, sedangkan judul bagian dan label kolom ditulis sentence case, misalnya "Data pendaftaran" dan "Nomor lomba individu"; singkatan kolom sempit (No. Atlet, KU) tetap (K166 butir 4 dan 5).
- **Atlet ditolak** (K117): bukti memuat tabel "Atlet ditolak panitia" (nama, NISN, yang didaftarkan, alasan, dan keterangan "Daftarkan ulang di KU …" atau "Sudah didaftarkan ulang"). Bila semua atlet ditolak, judul dokumen menjadi "KETERANGAN PENOLAKAN ATLET", tabel nomor lomba tidak dicetak, stempelnya merah "DITOLAK", totalnya Rp 0, dan nama berkas berakhir `_Ditolak.pdf`.
- Kode QR berisi alamat bukti di pojok kanan bawah halaman terakhir, dengan keterangan di kirinya untuk memeriksa keaslian (K89). Kode QR bukti pendaftaran yang sudah digabung diarahkan ke bukti pendaftaran gabungannya (K109).
- Nama berkas memakai seluruh nama acara tanpa spasi, ditambah ID pendaftaran dan status (K87): `BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_<NamaSekolah>_2026_<ID>_<Status>.pdf`.

Nama acara, seri, dan tahun pada bukti dan nama berkas diambil dari pengaturan acara.

## 9. Panel admin

- **Tata letak:** lebar penuh, menu samping, kepala halaman menempel di atas berisi kotak **cari menu**, nama admin (menuju Akun), dan tombol Keluar, tanpa teks footer. Kepala tidak lagi menampilkan judul halaman karena judulnya sudah ada di jejak navigasi; judul tetap ada untuk pembaca layar (K174 butir 1). Kotak cari menu menyaring butir sidebar sambil mengetik dan menampilkan daftar menu yang cocok; Enter atau ketukan membuka halamannya, panah atas dan bawah memilih, dan tombol `/` membuka kotak dari mana saja. Sejak Tahap 9 (F9-14) sidebar berkelompok: **Pendaftaran** (Dasbor, Pendaftaran, Atlet, Atlet ditolak, Sekolah, Rekap), **Hari lomba** (Daftar ulang, Cetak BIB, Daftar peserta, Jadwal pertandingan, Seri dan lintasan, Lembar juri), **Hasil** (Hasil lomba, Klasemen, Videotron), **Konten** (Halaman depan, Bantuan, Galeri, Unduhan), dan **Pengaturan**, lalu Pengecekan dan tautan situs publik "Lihat halaman depan" yang membuka tab baru (K102, K151; tombol "Halaman depan" di kepala, K85, sudah dilepas).
  - **Warna dan logo** (K154 butir 3, F10-03): sidebar biru tua `#021F7B` setinggi layar, pojok kiri atas biru `#234987` berisi logo resmi tanpa wadah putih (K168 butir 3), nama acara, dan "Panel admin"; kepala dan area konten terang. Butir aktif berlatar putih 10% dengan ikon oranye, tanpa garis kiri (K168 butir 1, menggantikan garis oranye K154/F10-03).
  - **Lebar** (F10-03): sidebar `w-72` di layar lebar, dan isi di kanannya memakai lebar penuh tanpa batas lebar yang memotong teks dan tabel. Isi dipadatkan per halaman supaya tidak banyak ruang kosong (F10-08).
  - **Menu lipat** (F10-03): kelompok dan butir yang punya submenu bisa dilipat, keadaan terakhirnya diingat di peramban, dan kelompok butir aktif selalu terbuka. Submenu: Pendaftaran (Riwayat pendaftaran), Atlet (Urutkan nomor atlet (BIB)), Sekolah (Usulan nama sekolah, Tambahkan nama desa), dan Pengaturan (semua halaman pengaturan dan Akun yang belum ada di kelompok Konten dan Hasil, K157 butir 1).
  - **Ponsel**: sidebar menjadi laci yang dibuka tombol menu di kepala. Cari menu dan Keluar berupa tombol ikon saja berlatar dan bergaris tepi; tombol cari membuka kotak cari selebar kepala. **Menu bawah** berisi Dasbor, Pendaftaran, Atlet, Rekap, dan **Pengaturan** (K174 butir 3, menggantikan butir Menu K157); Pengaturan menyala di semua halaman yang berpil Pengaturan. Atlet ditolak hanya ada di sidebar. Butir aktif berikon dan berlabel oranye, target sentuh paling kecil 48 piksel.
- **Tabel:** data dari titik akhir JSON, dengan kolom No otomatis (K76), halaman, pencarian, saringan pada setiap kolom yang relevan, dan pengurutan. Setiap tabel punya tombol unduh Excel yang mengikuti saringan aktif. Sejak Tahap 10 **semua tabel panel berpaginasi**, bawaan 25 baris (pilihan 10, 25, 50, 100), termasuk rekap, daftar peserta, nomor atlet, sekolah, riwayat, dan tabel di Pengaturan; tombol per baris dan pilih massal tetap bekerja (F10-04). Pengecualian: tabel isian prestasi per babak tidak dipaginasi (K156 butir 3).
- **Panel ringkas** (K174 butir 4–9, F11-10): teks lebih dari dua baris di sel tabel dan paragraf kartu admin dipotong dengan tombol **Selengkapnya** (teks penuh tetap terbaca pembaca layar; keterangan isian formulir dan peringatan tidak dipotong). Saringan tabel tersusun dua isian per baris (empat di layar lebar). Tombol **Unduh Excel** dan **Unduh PDF** berupa ikon saja (lembar kerja hijau untuk Excel, ikon PDF merah), teksnya muncul saat kursor di atasnya, saat difokus, atau saat diketuk di ponsel; nama aksesibelnya tetap lengkap. Di ponsel tombol aksi daftar dan rincian (antara lain Kosongkan saringan, Tandai Lunas, Kembalikan ke Menunggu, Ubah, Hapus) tersusun tiga per baris berikon dengan teks terpotong: tekan pertama menampilkan teks penuh, tekan kedua menjalankannya. Label "Cari" dan "Tampilkan … baris" tabel hanya untuk pembaca layar di ponsel. Label sumbu grafik dasbor di ponsel dijarangkan (label lengkap di tooltip) dan atlet per kabupaten digambar mendatar supaya tidak bertumpuk.
- **Keadaan kosong:** pesan ramah yang menjelaskan apa yang belum ada dan langkah berikutnya, dengan ilustrasi bila gambarnya sudah ada (F9-17).
- **Pemuatan:** bagian yang dimuat JavaScript (tabel, grafik dasbor, cari NPSN) menampilkan kerangka (*skeleton*) sampai data pertama tiba; halaman yang dibuat server tidak diberi kerangka (F9-17).
- **Tindakan berisiko** (hapus, tandai lunas, kembalikan ke Menunggu): jendela konfirmasi (modal Flowbite) yang menyebut apa yang akan terjadi. Mencentang "Hapus gambar/logo/sampul/latar" di formulir pengaturan dan menekan "Pakai pesan awal" di Pengaturan > Pembayaran juga meminta konfirmasi dulu (F9-18). Jendela konfirmasi yang tidak diberi judul sendiri berjudul "Lanjutkan?" dengan tombol "Lanjutkan" (K166 butir 6). Daftar pendaftaran punya pilih massal untuk ketiga tindakan itu, paling banyak 100 sekaligus, dengan aturan yang sama seperti di rincian (K76).
- **Cadangan** (K174 butir 10–12, F11-11, menggantikan tombol Backup data di daftar pendaftaran K76): menu **Pengaturan > Cadangan** mengunduh satu zip berisi **semua** tabel basis data (kecuali akun admin dan catatan migrasi), semua berkas unggahan (foto ofisial, bukti bayar, gambar pengaturan, latar videotron), dan pengaturan, ditambah isi Backup data lama sehingga `php spark pendaftaran:impor` tetap bisa memakainya. Zip ditulis per halaman ke berkas sementara lalu dikirim sebagai berkas, **tanpa dimuat utuh ke memori** (K118). Unduhan memakai POST dengan CSRF dan dicatat di riwayat tindakan admin. **Impor lewat panel mengganti semua** data, pengaturan, dan berkas unggahan dengan isi cadangan: admin mengetik ulang kata sandinya; isi diperiksa dulu (manifes, versi, migrasi, tabel, kolom, nama berkas) dan berkas rusak atau dari web versi lebih baru ditolak tanpa perubahan; data lama dicadangkan otomatis ke `writable/cadangan/` (lima terakhir, bisa diunduh); data diganti dalam satu transaksi. Akun admin tidak ikut dan tidak diubah; tabel yang tidak ada di cadangan dikosongkan. Berkas yang terlalu besar diunggah dipulihkan dengan `php spark cadangan:pulihkan <berkas.zip>`. Alamat lama `admin/pendaftaran/ekspor` dialihkan ke menu baru (K176). Cadangan harian lengkap tetap lewat `cadangan:buat` (15).
- **Ganti atlet** (K172 butir 1, F11-01): rincian pendaftaran Lunas punya tombol Ganti atlet. Atlet baru dari sekolah yang sama, kelompok umur dan jenis kelamin cocok dengan nomor yang diwariskan, dan NISN belum terdaftar. Nomor lomba, regu, dan status Inti/Cadangan diwariskan; total biaya dan status Lunas tetap; atlet baru mendapat nomor atlet baru dan nomor lama tidak dipakai ulang (K11). Penggantian ditolak bila atlet lama masuk seri babak yang sudah dikunci atau sudah punya hasil (untuk beregu: regunya sudah punya hasil); tempat atlet lama di seri yang belum dikunci pindah ke atlet baru. Atlet lama disimpan di tabel `atlet_diganti` (tidak tampil sebagai ditolak di Cek Pendaftaran) dan NISN-nya bisa didaftarkan lagi; riwayat tindakan admin mencatat siapa diganti siapa.
- **Urutkan nomor atlet** (K111): tombol di halaman Atlet membuka halaman yang meringkas atlet Lunas per kelompok umur, kecukupan awalan, dan waktu serta admin terakhir yang mengurutkan (6.9). Halaman Atlet dan rincian pendaftaran Lunas menulis waktu pengurutan terakhir.
- **Pendaftaran gabungan** (K109): rincian menulis "Gabungan dari" dengan ID lamanya, dan pencarian daftar pendaftaran menemukan pendaftaran gabungan lewat ID lama.
- **Tanpa pendaftaran baru** (K58): panel hanya melihat, menandai Lunas, mengubah, dan menghapus pendaftaran yang sudah ada. Sekolah yang terlambat didaftarkan lewat wizard setelah admin memakai buka manual (6.1).
- **Ubah pendaftaran:** admin bisa mengubah data sekolah, ofisial, atlet, nomor lomba, serta regu dan Inti/Cadangan tiap atlet beregu. Aturan pada bagian 6 tetap berlaku dan biaya dihitung ulang; regu yang sudah terdaftar boleh disimpan dengan inti kurang dari jumlah inti (tidak lebih), supaya perubahan lain tetap bisa disimpan, dan regu itu ditandai di rincian dan rekap tim beregu (K105). ID pendaftaran tidak berubah (6.8). Akibat pada status dan nomor atlet mengikuti 6.9 (K32, K59).
  - Kelompok umur dan nomor lomba yang sudah dimatikan tetap boleh dipertahankan untuk atlet yang sudah memakainya, ditandai "Sudah ditutup", sehingga perubahan lain (misalnya nomor WA) tetap bisa disimpan. Atlet baru dan nomor baru tidak bisa memakainya (K98).
  - Atlet yang tahun lahirnya masuk lebih dari satu kelompok umur (6.4, K134) punya kotak **Kelompok umur** berisi pilihannya, terisi kelompok umur tersimpan. Admin boleh memindahnya: nomor lomba dipilih ulang dari kelompok umur baru dan biaya dihitung ulang (6.9). Nomor atlet (BIB) yang sudah ada tidak berubah; awalannya mengikuti kelompok umur baru setelah **Urutkan nomor atlet**.
  - Nama sekolah tersimpan yang berbeda dari data sekolah, misalnya nama usulan yang belum diputuskan, dipertahankan selama NPSN tidak diubah.
- **Tim tanpa pembayar** (K68): rincian pendaftaran memberi peringatan pada regu yang kehilangan pembayar biaya regunya, dasbor menampilkan jumlahnya bila ada, dan rekap keuangan mendaftarnya beserta pendaftaran anggotanya. Di Ubah, regu itu punya kotak centang "Bebankan biaya regu ke pendaftaran ini" dengan tarif saat ini; bila pendaftarannya Lunas dan totalnya berubah, berlaku 6.9.
- **Bukti bayar** (K34, K83, K90): ofisial mengunggah bukti transfer saat mengirim pendaftaran, dan bisa menambah bukti lewat Cek Pendaftaran selama belum Lunas. Admin juga bisa mengunggah dari rincian pendaftaran. Jumlah gambar paling banyak per pendaftaran diatur admin di Pengaturan > Pembayaran, 1 sampai 200, bawaan 100 (K135, K136); bila batas diturunkan, gambar yang sudah terunggah tetap tersimpan dan pendaftaran itu hanya tidak bisa menambah lagi. Gambar dikecilkan otomatis sebelum disimpan (sisi terpanjang 1600 piksel, JPEG). Admin bisa melihat, mengunduh, dan menghapus tiap gambar.
- **Rekap:** peserta per nomor lomba, tim beregu (satu baris per regu, dengan jumlah inti dan tanda "Inti kurang", K105), per sekolah, per kelompok umur, dan keuangan (tagihan, lunas, belum). Tersedia Excel dan PDF, ditambah tombol "Unduh Rekap Gabungan": satu Excel berisi semua data, satu sheet per bagian (K85). Rekap peserta memuat semua pendaftaran dengan saringan status (semua, Lunas, Menunggu), dan Excel serta PDF-nya mengikuti saringan itu; rekap keuangan selalu memuat keduanya (K60).
- **Data sekolah** (K97): menu Sekolah menampilkan semua sekolah di data referensi dengan saringan kabupaten, jenjang, nama yang sama dengan sekolah lain, dan sudah disunting, serta unduh Excel. Admin bisa mengubah nama, bentuk, jenjang, status, alamat, desa, kecamatan, dan kabupaten; NPSN tidak bisa diubah. Nama baru yang sama dengan sekolah lain ditolak. Saat menyimpan, admin bisa sekalian mengganti nama di pendaftaran yang sudah masuk dengan NPSN itu. Sekolah yang disunting tidak ditimpa saat data sekolah diimpor ulang.
- **Tambahkan nama desa** (K114): tombol di menu Sekolah membuka daftar nama baru untuk semua sekolah yang namanya sama dengan sekolah lain, misalnya "MIS MUHAMMADIYAH" di desa Jati menjadi "MIS MUHAMMADIYAH JATI". Bila desa kosong, sudah tertulis di nama, atau sama dengan sekolah lain bernama sama, dipakai kecamatan, lalu kabupaten. Admin mencentang yang akan dipakai (bawaannya semua) lalu menerapkannya: nama di data sekolah diganti dan ditandai disunting, dan pendaftaran ber-NPSN itu yang masih memakai nama lama ikut diganti; nama usulan pendaftar yang belum diputuskan tidak diubah. Sekolah yang tidak bisa dibedakan otomatis didaftar terpisah dengan tombol Ubah. Setelah diterapkan, nama tiap sekolah tetap bisa diubah lagi lewat Ubah.
- **Usulan nama** (K99): usulan dari pendaftar (6.2) tampil di halaman Usulan nama dengan formulir Setujui (nama boleh dirapikan dulu) dan Tolak (alasan pilihan). Jumlah usulan yang menunggu tampil di dasbor dan di menu Sekolah, dan rincian pendaftaran pengusul diberi tanda. Menyetujui satu usulan menutup usulan lain yang masih menunggu untuk sekolah yang sama.
- **Tolak atlet** (K116): tombol "Tolak atlet" di rincian pendaftaran Menunggu membuka daftar atlet dengan kotak centang dan isian alasan (10–1.000 huruf, wajib). Atlet yang kelompok umurnya tidak cocok lagi dengan pengaturan sekarang (6.4, K95) ditandai "Seharusnya KU …" (bila tahun lahirnya masuk dua kelompok umur, kelompok umur tersimpan yang termasuk pilihannya tetap cocok, K134) dan langsung tercentang dengan alasan bawaan; tanda yang sama tampil di tabel atlet rincian.
  - Atlet yang ditolak dikeluarkan beserta nomor lombanya, jadi NISN-nya bisa didaftarkan lagi. Biaya dan regu dihitung ulang dengan aturan Ubah (regu yang tidak punya anggota lagi di pendaftaran itu tidak ditagih); bila atlet lain yang tersisa juga tidak cocok, penolakan dibatalkan dengan pesannya. Semua atlet boleh ditolak: pendaftaran tetap ada dengan total Rp 0 supaya ofisial dan bukti bayarnya tersimpan (6.9).
  - Salinan atlet yang ditolak disimpan (11): kelompok umur dan nomor lomba lama, biaya individunya, alasan, kelompok umur yang sesuai saat ditolak, total sebelum dan sesudah, admin, dan waktunya. Rincian pendaftaran mencatatnya dengan keadaan "Belum didaftarkan ulang" atau "Sudah didaftarkan ulang" beserta ID pendaftaran barunya.
  - Hanya untuk pendaftaran Menunggu; pendaftaran Lunas dikembalikan ke Menunggu dulu. Hapus pendaftaran ikut menghapus catatannya, penggabungan (K109) ikut memindahkannya, dan Backup data membawanya.
- **Atlet ditolak** (K117): menu sendiri di sidebar, terpisah dari menu Atlet (sejak K157 tidak lagi di menu bawah ponsel), berisi semua catatan penolakan dari semua pendaftaran, saringan dan kotak jumlah "Belum/Sudah didaftarkan ulang", pencarian, dan unduh Excel.
- **Jejak navigasi** (K117): halaman turunan panel (rincian, Ubah, dan Tolak atlet pendaftaran; Urutkan nomor atlet; Ubah sekolah, Usulan nama, Tambahkan nama desa) memakai jejak navigasi, bukan tautan "Kembali ke …". Rincian pendaftaran mengikuti asal halamannya: dibuka dari Atlet, jejaknya "Atlet › ID" dan menu Atlet yang aktif; dari Atlet ditolak, "Atlet ditolak › ID"; selain itu Pendaftaran. Asal disimpan di sesi per pendaftaran, sehingga halaman Ubah, Tolak atlet, dan halaman sesudah menyimpan tetap memakai asal yang sama. Di ponsel jejak diringkas menjadi satu tombol kembali ke halaman induk. Sejak Tahap 10 **setiap halaman admin** punya jejak (F10-03), dan sejak Tahap 11 jejaknya seragam (K174 butir 2): selalu diawali teks **Dasbor** tanpa ikon rumah; halaman butir menu memakai label menu atau pil navigasinya (misalnya Dasbor › Cetak BIB, Dasbor › Pengaturan › Pendaftaran); halaman yang tidak menulis jejaknya sendiri mendapat jejak dari menu dan judulnya, misalnya Dasbor › Pengaturan › Agenda › Ubah agenda. Semua halaman yang berpil Pengaturan (termasuk Halaman depan, Bantuan, Galeri, Unduhan, Videotron, dan Akun) berjejak Dasbor › Pengaturan › label pil, dan halaman Rekap berjejak Dasbor › Rekap › jenis rekap. Nomor lomba di jejak Hasil, Seri, dan Lembar juri ditulis sama, misalnya Lari 60 m putra KU 10. Tautan kembali yang sama dengan jejak dihapus (antara lain "Semua pengaturan"), sedangkan tombol Kembali di baris tombol formulir tetap (K168 butir 4).
- **Riwayat tindakan admin** (Tahap 8, K124): setiap tindakan admin pada pendaftaran (Lunas satu per satu dan massal, kembali ke Menunggu, Ubah dengan ringkasan perubahannya, Tolak atlet, hapus pendaftaran, unggah dan hapus bukti bayar, Urutkan nomor atlet, gabung) dicatat dalam transaksi yang sama: siapa, kapan, apa, dan ringkasannya. Rincian pendaftaran menampilkan bagian "Riwayat" (terbaru di atas); riwayat pendaftaran yang sudah dihapus dicari lewat ID di halaman Riwayat.
- **Bukti bayar baru** (Tahap 8, K124): bukti dari ofisial (wizard dan Cek Pendaftaran) ditandai belum diperiksa sampai pendaftarannya ditandai Lunas atau admin menekan "Sudah diperiksa"; membuka rincian saja tidak menandai. Bukti yang diunggah admin langsung dianggap diperiksa. Daftar pendaftaran memberi tanda "Bukti baru" dan saringan "Bukti baru belum diperiksa", dan dasbor menampilkan jumlahnya (pendaftaran Semua atlet ditolak tidak dihitung).
- **Dua kartu bukti bayar di dasbor** (K156 butir 3): definisinya berbeda, jadi keduanya tetap. **"Menunggu, bukti sudah ada"** adalah daftar kerja untuk menandai Lunas dan menaut ke daftar pendaftaran bersaringan `status=menunggu&bukti=ada`; **"Bukti bayar baru belum diperiksa"** menghitung bukti yang belum ditandai "Sudah diperiksa", termasuk di pendaftaran Lunas, dan menaut ke saringan `bukti=baru`. Kartu keadaan pendaftaran di dasbor disusun ulang: label dan lencana keadaan, kalimat keadaan, tombol Pengecekan, dan ilustrasi sapaan di kanan bila berkasnya ada (K168 butir 14).
- **Seri dan lintasan** (Tahap 9, K140 butir 8): menu sendiri di kelompok Hari lomba; aturan dan alurnya di 18.
- **Lembar juri** (Tahap 9, K140 butir 7d): menu sendiri di kelompok Hari lomba; isian prestasi cepat dari ponsel, rinciannya di 18. Akun juri (K181) hanya melihat menu Lembar juri dan Akun (juga di menu bawah ponsel), pojok logo bertulis Panel juri, dan jejak yang dimulai dari Lembar juri.
- **Gambar belum ada:** kotak ikon "tanpa gambar", bukan gambar tiruan.

## 10. Halaman depan

Semua isi diatur dari panel admin. Tampilan baru Flowbite Pro sejak Tahap 9 (K140, K141, F9-03), mode terang; sejak Tahap 10 bergaya editorial dengan judul Barlow Condensed dan gambar anime (12, K153, K155).

- **Kepala** menempel di atas saat digulir: logo persegi (logo persegi panjang hanya bila yang persegi belum ada, K93), nama acara dan label seri, menu utama Beranda, Jadwal, Hasil (setelah ada yang terbit), dan Peserta, lalu tombol **Cek Pendaftaran** dan **Daftar** (hanya saat pendaftaran terbuka). Jadwal dan Peserta hanya tampil bila sakelar halamannya dinyalakan admin. Butir Kategori tidak ada di menu publik (F10-07, K154, K157 butir 2).
- **Bilah submenu atas** di layar komputer, di atas kepala dengan latar biru tua dan teks kecil: Unduhan, Galeri (bila sakelarnya menyala), Bantuan, **Tanya panitia** (WA, bila nomornya diisi; K166 butir 2), **Masuk panitia** (selalu ada, paling akhir; K168 butir 8), lalu ikon media sosial dari Pengaturan > Kontak. Admin memilih empat butir pertama dengan centang di Pengaturan > Halaman Depan; bawaannya semua menyala (F10-07, K154).
- **Di ponsel** kepala hanya berisi logo dan nama acara di kiri serta **tombol menu di kanan** (K157 butir 2, K168 butir 10). Tombol itu membuka **laci dari kanan** berisi menu utama, tombol Cek Pendaftaran dan Daftar, butir bilah atas (termasuk Masuk panitia), dan media sosial; laci ditutup dengan tombol, Esc, atau ketuk latar, dan fokus papan ketik terkunci di dalamnya.
- **Menu bawah publik di ponsel** (K175 butir 6, menggantikan susunan K154 butir 2, K157 butir 2, dan K168 butir 11): **Beranda, Bantuan, Daftar, Cek, Jadwal**. Daftar di tengah berupa lingkaran oranye yang lebih besar dan menonjol; setelah pendaftaran ditutup tombol tengah menjadi **Hasil** dengan tampilan yang sama. Bila Jadwal dimatikan, butir terakhir diganti Unduhan. Hasil dan Menu tidak ada di menu bawah. Butir aktif berikon oranye. Menu bawah tidak tampil di wizard pendaftaran supaya tidak menutupi tombol langkah, dan halaman lain diberi ruang bawah supaya isinya tidak tertutup.
- **Pembuka** bergaya editorial (F10-06, rujukan `desain/referensi/hero-editorial.webp`), tanpa alis dan tanpa slogan "Born to Run" (K164 butir 1, menggantikan K156 butir 4 soal slogan):
  - latar biru tua; judul besar Barlow Condensed huruf besar miring, tiap kalimat (atau tiap kata bila judulnya satu kalimat pendek) satu baris dengan baris terakhir oranye (judul data awal "MilkLife Athletics Challenge", K86); tahun tidak ditulis di judul; lalu teks pengantar;
  - tombol besar **Daftar sekarang** (saat terbuka, K165) dan **Cek Pendaftaran**; tombol tanpa alamat tampil nonaktif dengan keterangannya. Judul dan kedua tombol selalu di layar pertama (K81);
  - **foto asli** dari carousel halaman depan memenuhi sisi kanan dengan gradasi biru tua ke kiri; di ponsel foto di atas (4:3) dengan gradasi ke bawah. Keterangan foto kecil di pojok kanan bawah, tanpa garis (K168 butir 1). Foto bawaan dari pemilik (K140 butir 6) dipasang `php spark data:awal` atau `php spark halaman-depan:carousel`; admin menambah, mengurutkan, dan menghapus foto beserta keterangannya di Pengaturan > Halaman Depan (paling banyak 20), dan foto dikecilkan ke sisi terpanjang 1600 piksel (K143);
  - latar art hero kiri G11-02 bila berkasnya ada; batasnya dengan foto bergradasi halus, dan hiasan yang menyentuh foto memudar, bukan terpotong lurus (K175 butir 5);
  - **slideshow** berganti otomatis tiap **5 detik** dengan penanda titik saja, yang di ponsel disembunyikan (K175 butir 4) (tanpa tombol sebelumnya, berikutnya, atau jeda); berhenti saat difokus, ditunjuk tetikus, tab tersembunyi, atau pengunjung memilih kurangi gerak (K156 butir 4). Tanpa JavaScript hanya foto pertama yang tampil. Bila carousel kosong, gambar kepala yang tampil; bila keduanya kosong, ilustrasi tokoh bila berkasnya ada, atau tanpa gambar sama sekali (K41). Hiasan Three.js hanya untuk pembuka tanpa foto;
  - **pita tiga fakta** berlatar krem tepat di bawah hero, tanpa ikon (K175 butir 2): Hari lomba, Lokasi (menaut ke peta bila tampil; di ponsel "Supersoccer Arena" ditulis "SSA" supaya kotaknya satu baris, nama lengkap tetap dibaca pembaca layar, K179), dan keadaan pendaftaran ("Batas pendaftaran 30 November 2026" saat terbuka; tanggal selalu bertahun). Kalimat lengkap keadaan tetap dibaca pembaca layar; di ponsel batas pendaftaran rata tengah. Di ponsel keadaan pendaftaran ditunjukkan tombol Daftar dan pita fakta ini (K156 butir 4).
- **Kartu kelompok umur** di bawah pita fakta, tanpa label "Kelompok umur" di atasnya (K164 butir 1), menaut ke rinciannya. Angka kelompok umur ditulis sangat besar dengan isian huruf bertekstur: gradien sporty, lalu tekstur dan art khas tiap KU dari G11-01 dipakai otomatis setelah berkasnya ada. Angka itu memenuhi kartu dan ukurannya mengikuti lebar kartu: melewati tepi bawah, digit kedua melewati tepi kanan, dan bagian yang lewat terpotong kotak. Jumlah nomor individu dan beregu di bawah, cukup ikon dan angka berlatar putih kecil yang boleh menimpa angka KU; jumlah 0 tidak tampil, dan nama aksesibel kartu tetap lengkap (K88, K179, menggantikan ikon dan angka bergaris miring K175 butir 3). Julukan dalam kurung hanya tampil bila admin menambahkannya pada nama kelompok umur; data bawaan menulis `KU 8` tanpa julukan (K153).
- Rincian kelompok umur di bagian lomba menulis tahun lahir yang boleh memilih, misalnya "SD/MI lahir 2016 boleh memilih KU 10 atau KU 12, dipilih saat mendaftar", pada kedua kelompok umurnya (K134).
- **Kelompok umur, nomor lomba, dan biaya** ("Choose Your Race") dari data induk. Di layar lebar satu kelompok umur satu baris: kepala (tahun lahir, jenjang, biaya individu) di kiri dan nomor lomba di kanan. Nomor lomba tampil semua secara bawaan dan bisa disembunyikan seluruhnya sehingga hanya kepala kelompok umur yang tersisa. Nomor individu dibagi dua kolom berkepala **Putra** dan **Putri** (nama nomor tanpa akhiran putra/putri), lalu nomor campuran dan beregu di bawahnya dengan lencana kategori Putra, Putri, atau Mixed; ikon nomor lebih besar (K76, K175 butir 1). Rincian biaya **disembunyikan** secara bawaan dan dinyalakan admin lewat sakelar di Pengaturan > Halaman Depan (K74). Ilustrasi nomor lomba tampil bila gambarnya sudah ada.
- **Atlet** ("Our Athletes", K168 butir 13): tokoh anime G10-01, lembar lima tokoh di layar md ke atas dan dua tokoh berdampingan di ponsel; tidak ditulis bila berkasnya belum ada (K41).
- Agenda ("Key Dates"): sosialisasi daring, pertemuan teknik, dan jadwal lain yang ditambahkan admin.
- **Angka acara**: jumlah atlet, sekolah, kabupaten/kota, dan atlet per kelompok umur, hanya dari pendaftaran Lunas; tampil setelah ada datanya.
- **Hasil dan juara umum terbaru** ("Results"): juara umum I–III per klasemen kelompok umur dan nomor yang paling akhir diterbitkan beserta juaranya, hanya dari nomor yang sudah terbit dan mengikuti metode klasemen di Pengaturan > Hasil (18, K124, K140 butir 7a), dengan tautan ke halaman hasil dan klasemen. Selama hari lomba admin bisa menyalakan sakelar "Tampilkan hasil di atas (hari lomba)" di Pengaturan > Halaman Depan (bawaan mati) supaya bagian ini pindah tepat di bawah pembuka (K143). Tombol Lihat semua hasil dan Lihat klasemen sekolah di ponsel tetap satu baris: satu berteks lengkap, lainnya ikon; tekan pertama melebarkan tombol dan menampilkan teksnya, tekan kedua membuka halamannya, sedangkan papan ketik dan pembaca layar langsung membuka halaman (K175 butir 8).
- **Pertanyaan yang sering ditanyakan** ("Got Questions?"): pertanyaan pertama tiap kategori Bantuan, paling banyak enam, dengan tautan ke halaman Bantuan (F9-04).
- **Unduhan** ("Downloads"). Tiap berkas punya tombol **Unduh**; PDF dan gambar juga punya tombol **Lihat** yang membuka berkas di **modal di semua lebar layar** tanpa mengubah alamat unduh. Di ponsel modal PDF berisi tombol **Buka di tab baru** dan **Unduh**; tanpa JavaScript Lihat membuka tab baru. Jenis dan ukuran berkas tidak ditampilkan (F10-09, K168 butir 6).
- **Lokasi** ("The Venue", F10-02): alamat dan peta Google Maps Supersoccer Arena tepat di atas Kontak, dengan tombol **Buka di Google Maps** di bawah alamat (layar lebar) atau di bawah peta (ponsel) (K156 butir 2). Iframe peta baru disisipkan saat bagiannya mendekati layar, rasio 4:3 di ponsel dan 16:9 di layar lebar; tanpa JavaScript hanya tautannya. Alamat embed (hanya `https://www.google.com/maps/embed?…`; kode `<iframe>` utuh dari Google Maps juga diterima) dan tautan buka peta diatur di Pengaturan > Halaman Depan, bawaannya Supersoccer Arena. Bila alamat embed kosong, bagian ini tidak tampil.
- **Kontak** ("Get in Touch"): narahubung dengan tombol ikon WA hijau bernama "Kirim pesan WA" untuk pembaca layar dan judul tetikus (K167 butir 2, K168 butir 7), media sosial, tombol **Tanya panitia lewat WA** (bila nomornya diisi), dan tombol **Bagikan lewat WA** yang selalu ada (K167 butir 2). Pesan bawaannya diatur di Pengaturan > Halaman Depan; pesan bagikan ditambah alamat situs, dan kedua pesan ditambah tagar `#MilkLifeAthleticsChallengeSeri2` di baris baru bila belum tertulis (F10-01).
- **Kaki halaman** dengan teks kaki dan logo Bakti Olahraga Djarum Foundation di semua halaman publik (K84): di layar sm ke atas teks di kiri dan logo di kanan, di ponsel **rata tengah** (K168 butir 9). Logo bawaannya logo Djarum Foundation baru dari pemilik, dipakai selama admin belum mengunggah logo sendiri (K154 butir 4). Tautan Masuk panitia pindah ke bilah atas dan laci (K168 butir 8).
- Semua halaman publik (Unduhan, Hasil, Klasemen, Bantuan, Jadwal, Peserta, Galeri, wizard, Cek Pendaftaran, unggah bukti) memakai tata letak yang sama (F9-03, F9-05). Pita judulnya memuat **judul editorial berbahasa Inggris** sebagai hiasan (`aria-hidden`), misalnya Race Day, Results, Standings, Meet the Athletes, Gallery, Need Help?, Downloads, Check Your Entry, Join the Race, dan Big Screen (daftar tampilan videotron), dengan judul Indonesia sebagai `<h1>`/`<h2>` di bawahnya (K157 butir 3); halaman seri `jadwal/<babak>` belum diberi judul Inggris. Judul editorial, judul Indonesia, dan kalimat pengantar di bawahnya rata tengah, baik di pita judul maupun di bagian halaman depan; kepala bagian bertombol menjadi satu kolom di tengah dengan tombol di bawah judul (K167 butir 1). Tidak ada alis di atas judul (K164 butir 1); di halaman hasil per nomor, kelompok umur yang dulu menjadi alis pindah ke judul, misalnya "Lari 60 m putra KU 10". Tampilan videotron (tampilan tetap dan papan putar) memakai tata letak layar penuh sendiri.
- Teks halaman publik ditulis ulang dengan skill `bahasa-indonesia-plain` (K159): sapaan "Anda", kalimat pendek dan aktif, istilah K113 dan status Lunas/Menunggu tetap. Bahasa Inggris hanya untuk slogan, judul editorial, dan art; tombol, formulir, pesan, dan menu tetap berbahasa Indonesia (K153 butir 2).

## 11. Model data

Nama tabel dan kolom berbahasa Indonesia.

| Tabel | Isi utama |
|---|---|
| `admin` | nama, nama pengguna, peran (`admin` atau `juri`, K181), sandi (hash) |
| `settings` | pengaturan kunci–nilai (dari pustaka settings) |
| `sekolah` | npsn, nama, bentuk, jenjang, status, alamat, desa/kelurahan, kecamatan, kabupaten, waktu disunting admin (impor tidak menimpa baris yang pernah disunting) |
| `usulan_nama_sekolah` | npsn, pendaftaran pengusul (kosong bila pendaftarannya dihapus), nama lama, nama usulan, status (menunggu, disetujui, ditolak), nama yang disetujui, alasan, waktu diputuskan |
| `kelompok_umur` | nama, tahun lahir paling awal, tahun lahir paling akhir (masing-masing boleh kosong), biaya individu, urutan, aktif |
| `kelompok_umur_jenjang` | kelompok umur, jenjang (A, B, atau C); satu baris per jenjang yang boleh ikut, unik per pasangan |
| `awalan_nomor_dada` | kelompok umur, awalan (unik), urutan, nomor terakhir |
| `nomor_lomba` | kelompok umur, nama, jenis, kategori, jumlah inti, batas anggota per regu, regu per sekolah paling banyak (kosong = tidak dibatasi), jumlah putra/putri (mixed), biaya beregu, urutan, aktif |
| `pendaftaran` | ID pendaftaran (unik), kode bukti (acak), kunci kirim dari wizard (unik, boleh kosong untuk data lama, K118), jenjang, npsn, nama sekolah, kabupaten, subtotal individu, subtotal beregu, total, status, waktu lunas |
| `penanggung_jawab` | pendaftaran, nama, nomor WA, foto |
| `atlet` | pendaftaran, nama, NISN (unik), tanggal lahir, jenis kelamin, kelompok umur, nomor atlet (unik) |
| `atlet_nomor` | atlet, nomor lomba, biaya (terisi untuk individu), nama regu (paling banyak 30 huruf) dan peran inti/cadangan (terisi untuk beregu) |
| `pendaftaran_beregu` | pendaftaran, nomor lomba, regu, biaya regu; unik per pendaftaran, nomor, dan regu |
| `bukti_bayar` | pendaftaran, berkas, ukuran, waktu unggah (paling banyak sesuai pengaturan admin, bawaan 100 per pendaftaran, dijaga aplikasi, K135, K136) |
| `pendaftaran_gabungan` | pendaftaran gabungan, ID pendaftaran lama (unik), kode bukti lama (K109); ikut terhapus bila pendaftaran gabungannya dihapus |
| `atlet_ditolak` | pendaftaran, nama, NISN, tanggal lahir, jenis kelamin, kelompok umur dan nomor lomba lama, biaya individu, kelompok umur yang sesuai saat ditolak, alasan, total sebelum dan sesudah, admin, waktu (K116); ikut terhapus bila pendaftarannya dihapus |
| `jadwal` | judul, keterangan, waktu, tempat atau tautan, urutan, tampil |
| `unduhan` | judul, keterangan, berkas, urutan, tampil |
| `tautan` | jenis (narahubung/media sosial), label, nilai, urutan |

Tambahan Tahap 8 (K124, ketentuan rancangan; rincian di `dokumen/draf/rencana-implementasi-tahap-8.md`):

| Tabel atau kolom | Isi utama |
|---|---|
| `riwayat_admin` | pendaftaran (kosong setelah dihapus), ID pendaftaran (teks, tetap ada setelah dihapus), admin dan salinan namanya, kode tindakan, ringkasan, waktu |
| `bukti_bayar` | tambahan: waktu diperiksa dan admin yang memeriksa |
| `atlet` | tambahan: waktu hadir dan admin yang menandai |
| `nomor_lomba` | tambahan: jenis hasil (waktu, jarak, atau poin) dan waktu hasil diterbitkan |
| `babak` | nomor lomba, nama (misalnya "Penyisihan", "Final"), tanda Final (satu per nomor), urutan; jadwal (tanggal, jam, lokasi); seri (banyak seri, aturan lolos Q dan q, benih acak, waktu dikunci) |
| `hasil` | babak, peserta (atlet untuk nomor individu, regu untuk nomor beregu), prestasi (bilangan bulat: perseratus detik, sentimeter, atau poin), keterangan (DNS, DNF, DQ, NM, r; F11-05) dan pasal DQ, peringkat, tanda diubah admin, seri dan lintasan (atau urutan tampil); percobaan lompat/lempar, waktu seperseribu detik, dan versi baris (K140 butir 7c dan 7d) |

Tambahan Tahap 9 (Bantuan dan galeri, F9-04, F9-06):

| Tabel atau kolom | Isi utama |
|---|---|
| `faq` | kategori, pertanyaan, jawaban (Markdown sederhana dengan isian otomatis seperti `{tutup_pendaftaran}`), urutan, aktif |
| `galeri` | judul, keterangan, tautan (`https://`), sampul (gambar WebP unggahan, boleh kosong), urutan, aktif |

Tambahan Tahap 9 (K140 butir 7b, kalkulator poin multi-lomba):

| Tabel atau kolom | Isi utama |
|---|---|
| `tabel_skor` | kode unik, nama, keterangan sumber, komponen (kode, nama, jenis waktu atau jarak) |
| `tabel_skor_baris` | tabel, komponen, kategori (putra/putri), prestasi (perseratus detik atau sentimeter), poin; satu baris per prestasi |
| `nomor_lomba` | tambahan: tabel skor dan komponen yang dipakai (jenis hasil poin multi-lomba) |
| `hasil` | tambahan: rincian prestasi dan poin per komponen; prestasi berisi total poin |

Tambahan Tahap 9 (K140 butir 7c dan 7d, aturan nomor dan lembar juri):

| Tabel atau kolom | Isi utama |
|---|---|
| `nomor_lomba` | tambahan: banyak percobaan lompat/lempar (kosong = 3) dan sakelar pemecah seri prestasi terbaik kedua (bawaan menyala) |
| `hasil` | tambahan: prestasi tiap percobaan (jarak, gagal, lewat), waktu dalam seperseribu detik bila diisi, dan versi baris (naik setiap kali disimpan) |

Tambahan Tahap 11 (K172 butir 3, rekor kejuaraan, F11-06):

| Tabel atau kolom | Isi utama |
|---|---|
| `nomor_lomba` | tambahan: spesifikasi alat (teks bebas, misalnya "Peluru 3 kg") |
| `rekor` | nomor lomba (kosong bila nomornya dihapus) dan salinan nama nomor, kelompok umur, kategori, jenis hasil; cara pencatatan waktu (nomor waktu), spesifikasi alat, prestasi, atlet, sekolah, seri, tanggal; hasil asal (terisi untuk rekor dari hasil terbit) |

Tambahan Tahap 11 lainnya (F11-01, F11-03, F11-04, F11-05, F11-07):

| Tabel atau kolom | Isi utama |
|---|---|
| `atlet_diganti` | pendaftaran, data atlet lama (nama, NISN, tanggal lahir, jenis kelamin, kelompok umur, nomor atlet, nomor lomba), nama, NISN, dan nomor atlet pengganti, alasan, admin, waktu (F11-01) |
| `nomor_lomba` | tambahan: tidak dilombakan (bawaan tidak), alasan tidak dilombakan, peserta minimal (kosong = tanpa batas), cara pencatatan waktu (elektronik bawaan, atau tangan) |
| `babak` | tambahan: nomor acara (boleh kosong, tidak kembar antarbabak) |
| `hasil` | tambahan: pasal DQ (paling banyak 12 huruf) |

Keunikan NISN, nomor atlet, dan kunci kirim dijaga oleh basis data, bukan hanya oleh aplikasi.

## 12. Tampilan

- Satu tema terang.
- Warna dari flyer: biru utama, biru tua, oranye sebagai aksen dan tombol utama, biru muda untuk bidang informasi, putih untuk kartu.
- Responsif, dirancang dari ponsel lebih dulu.
- Bahasa ramah untuk orang non-teknis: tanpa istilah teknis, pesan galat menjelaskan cara memperbaikinya.
- Sejak Tahap 9 semua halaman memakai tampilan Flowbite Pro (Tailwind CSS 4, ikon Lucide, hiasan Three.js), mode terang dengan inspirasi railway.com: ruang lega, kartu bersih, gradien halus (K140, K141); Tahap 10 menambah gaya editorial di bawah. Warna merek menjadi token Tailwind (biru `#234987`, biru tua `#021F7B`, oranye `#E85F31`, oranye tombol `#C4461B`, biru muda `#9BCCEC`, magenta flyer `#F0005A`). Bootstrap 5 dan Bootstrap Icons sudah dilepas (F9-17).
- Diperiksa di lebar 360, 390, dan 1280 piksel; target sentuh paling kecil 44 piksel; kontras teks WCAG AA; animasi Three.js dan slideshow mengikuti pilihan kurangi gerak.
- **Kerangka dan keadaan kosong** (F9-17): bagian yang dimuat JavaScript menampilkan kerangka; keadaan kosong dan halaman galat memakai ilustrasi.
- **Gaya editorial Tahap 10** (F10-05, K153, K155):
  - **Huruf**: judul tampilan (hero, judul bagian, judul editorial, angka besar, label fakta) memakai **Barlow Condensed** berat 600 dan 800, tegak dan miring, berkas lokal di `public/aset/vendor/barlow-condensed/` dari paket `@fontsource/barlow-condensed` lewat rantai bangun yang sama dengan Inter (K141, K155); teks badan tetap Inter, tanpa huruf ketiga.
  - **Palet**: token merek di atas ditambah **krem** `#F5EFE6` untuk pita fakta dan bagian editorial terang; magenta dipakai sangat hemat.
  - **Ikon polos**: ikon Lucide tanpa latar kotak atau lingkaran berwarna; boleh berwarna biru atau oranye dan lebih besar.
  - **Tanpa hiasan garis dan titik**: tanpa border penuh berwarna di keempat sisi; kotak info biru tanpa garis, sedangkan kotak sukses, peringatan, dan bahaya tetap bergaris tipis warna muda karena bermakna status (K156 butir 1). Tanpa garis atas oranye pada kartu (K160), tanpa garis kiri berwarna (K168 butir 1), tanpa garis pendek oranye atau latar sebelum tulisan (`ornamen-judul` dihapus, K168 butir 2), tanpa alis di halaman publik (kelas `.alis` tetap di CSS untuk katalog tampilan, K164), dan tanpa pola atau titik hiasan; titik penanda slideshow tetap karena fungsional. Garis atas hijau dan merah kartu Cek Lunas dan Semua atlet ditolak tetap (K160).
  - **Karakter** dari tipografi, art, dan bentuk: tombol utama dengan latar sapuan kuas, pemisah lintasan, tekstur kertas, bingkai foto, dan garis kecepatan dari art G10-11 lewat CSS. Teks selalu HTML, dan kelas berart hanya memakai gambar bila kelas penanda `ada-<nama>` terpasang karena berkasnya ada. Sudut membulat kecil dan bayangan hemat.
- **Gambar dari Codex** (K140 butir 5, K150, K153): sejak Tahap 10 semua gambar bergaya **anime olahraga 2D cel-shaded** (G10, menggantikan 3D lembut G9) dengan nama berkas yang sama, tanpa teks; tokoh berkaus peserta atau panitia diberi logo resmi yang ditempel apa adanya saat pasca-proses (K153 butir 3). Stadion di gambar dibuat dikenali sebagai **Supersoccer Arena (SSA) Kudus** tetapi tetap bergaya anime (K158, G10-13). Berkas G9 `pola/titik-halus.webp` dan `nomor/seneng-playon.webp` dihapus (K168 butir 15). Hero tetap memakai foto asli. Kode pemasangannya ditulis Claude dan setiap gambar hanya tampil bila berkasnya sudah ada, jadi halaman tidak pernah menampilkan kotak kosong (K41).
- **Bahasa** (K153 butir 2, K159): teks antarmuka, pesan, dan panduan ditulis dengan skill `bahasa-indonesia-plain` (sapaan "Anda"), termasuk panel admin (F10-13). Keputusan yang sudah ada tetap berlaku: `Rp 150.000` (K80), istilah K113, status Lunas/Menunggu (K90), Bantuan dan FAQ, dan judul editorial Inggris.

## 13. Keamanan dan privasi

- Perlindungan CSRF pada semua formulir. Token **tidak diganti setiap kiriman** (`regenerate = false`), supaya beberapa tab dan tombol kembali peramban tidak memicu galat (K118). Bila token kedaluwarsa atau tidak cocok, pengguna melihat pesan berbahasa Indonesia yang menyebut cara memperbaikinya (muat ulang halaman lalu kirim lagi), bukan halaman galat kosong, dan isian yang sudah diketik dikembalikan.
- **Cloudflare Turnstile** (K122) di kirim pendaftaran (wizard), Cek Pendaftaran, dan masuk admin, masing-masing dengan `data-action` sendiri:
  - Kunci di `.env`: `turnstile.kunciSitus`, `turnstile.kunciRahasia`, dan `turnstile.namaHost` (daftar nama host yang diizinkan; produksi tanpa `localhost`). **Bila kunci kosong, Turnstile mati**, sehingga admin tidak terkunci saat Cloudflare bermasalah. Kunci situs publik boleh ditulis di `.env.example` dan panduan produksi; **kunci rahasia tidak pernah masuk git, chat, log, atau diff** dan diisi pemilik sendiri di `.env` produksi.
  - Server menolak token kosong, bukan teks, atau lebih dari 2048 huruf, lalu memeriksa ke siteverify dengan alamat IP pengunjung (15, K31) dan batas waktu 10 detik. Kiriman hanya diterima bila hasilnya berhasil, `action` cocok, dan `hostname` diizinkan. Gagal = kode 403 dengan pesan berbahasa Indonesia yang menyebut cara memperbaikinya.
  - Kebijakan keamanan konten (CSP) menyala (K127) dan mengizinkan `challenges.cloudflare.com` untuk script dan frame; skrip dan gaya sebaris hanya dengan nonce.
- **Peta lokasi** (F10-02): CSP `frame-src` ditambah `https://www.google.com` (bersama `self` dan Turnstile) supaya iframe Google Maps bisa tampil. Pengaturan hanya menyimpan alamat yang diawali `https://www.google.com/maps/embed?`, iframe dimuat malas tanpa gaya sebaris, dan tidak ada permintaan ke google.com sebelum bagian Lokasi mendekati layar.
- Pembatasan laju pada cek pendaftaran, bukti, pencarian NPSN, simpan pendaftaran, dan halaman masuk.
- Semua keluaran di-escape; semua masukan divalidasi di server.
- Foto ofisial dan bukti bayar disimpan di luar folder publik dan hanya disajikan ke admin.
- Sandi admin di-hash; sesi admin berakhir otomatis.
- **Akun juri** (K181) hanya bisa membuka Lembar juri, Akun miliknya, dan keluar. Peran dibaca dari basis data di setiap permintaan; halaman lain dialihkan ke Lembar juri dengan pesan, dan kiriman formulir ke halaman lain ditolak. Mengganti kata sandi atau menghapus akun juri di Pengaturan > Akun juri mengeluarkan juri yang sedang masuk.
- **Mengganti kata sandi mengeluarkan sesi lain milik akun yang sama** pada permintaan berikutnya (K67); sesi yang mengganti tetap masuk. Mengganti kata sandi lewat perintah `admin:buat` mengeluarkan semua sesi akun itu.
- Kepala keamanan HTTP aktif di setiap halaman.
- Di mode produksi, halaman galat (halaman tidak ditemukan, permintaan keliru, galat umum) tampil ramah dalam bahasa Indonesia tanpa rincian teknis (K67).

**Risiko yang diterima pemilik:** NPSN bersifat publik dan ID pendaftaran berurutan, jadi orang yang menebaknya bisa melihat data siswa (nama, NISN, tanggal lahir). Pemilik memilih tampilan penuh berdasarkan MoU dengan sekolah. Pembatasan laju mengurangi pengambilan massal, tetapi tidak menutup risikonya.

## 14. Pengujian

- Uji unit untuk aturan murni: tahun lahir dan kelompok umur, biaya, ID pendaftaran, nomor atlet, kelengkapan tim.
- Uji fitur untuk alur pendaftaran, cek, bukti, panel, dan penandaan lunas, ditambah satu uji alur lengkap dari pendaftaran sampai hapus.
- Turnstile diuji dengan kunci uji resmi Cloudflare dan tiruan HTTP, tanpa panggilan jaringan (K122). Kirim ulang dengan `kunci_kirim` yang sama diuji tidak menulis pendaftaran kedua (K118).
- Tahap 8: uji unit untuk urutan peringkat dan seri, klasemen dan pemecah seri, dan penguraian format prestasi; uji fitur untuk setiap halaman dan tindakan baru (K124).
- PHPStan dijalankan di CI bersama uji (K123).
- Tahap 9: uji tampilan komponen dan tata letak (struktur dan atribut `data-*`, bukan kelas CSS), uji `KepalaKeamananTest` yang menolak gaya dan skrip sebaris (K127), dan uji tampilan videotron dengan Playwright di delapan ukuran layar (`npm run uji:layar`). CI punya tiga job: PHPUnit, Hasil bangun tampilan (CSS dan sprite ikon sama dengan sumbernya), dan Tampilan videotron. Batas waktu job PHPUnit 50 menit (K157 butir 5).
- Tahap 10: uji tampilan tetap memakai atribut `data-*` (menu publik dan admin, peta, pratinjau unduhan, salin ID); `KepalaKeamananTest` ikut memeriksa `frame-src https://www.google.com`. Setiap PR yang menyentuh wizard, Cek Pendaftaran, atau unggah bukti diuji alur daftar → cek → bukti di peramban karena pendaftaran sedang buka (K153 butir 4).
- Semua uji dijalankan dengan satu perintah (`composer uji`) di komputer pengembang sebelum PR dibuka. **CI GitHub** (GitHub Actions) menjalankan uji yang sama di setiap PR dan setiap perubahan di `main` (K46); PR digabung setelah keduanya lulus.

## 15. Pemasangan

Staging dan produksi berjalan di Docker Compose pada VPS Ubuntu 24.04 (K182, menggantikan Laragon untuk produksi K08). Berkasnya di `docker/`: image FrankenPHP dengan PHP 8.5.13, ekstensi, klien MySQL 8.4, pustaka Composer, dan aset kecil; container app dan db per lingkungan, app hanya membuka port di 127.0.0.1 untuk cloudflared di host (K184); volume untuk unggahan, sesi, log, dan basis data. Panduan pemasangan produksi (revisi 14) mencakup: pengaturan `docker/.env` dan `.env`, HTTPS lewat Cloudflare Tunnel, cadangan terjadwal, pemulihan, pembaruan versi (bangun ulang image), dan pindah dari server Laragon. Perintah `php spark …` di bawah dijalankan di container app (`docker compose -f docker/compose.yaml exec app php spark …`); `aset:minify` sudah dijalankan saat image dibangun.

Daftar sekolah diisi dengan `php spark sekolah:impor` dan aman diulang setiap data sekolah diperbarui. Sekolah yang sudah disunting admin tidak ditimpa (K97).

Tabel skor multi-lomba bawaan (K140 butir 7b) dipasang `php spark data:awal` pada pemasangan baru. Pada pemasangan lama, admin menekan **Pasang tabel bawaan** di Pengaturan > Tabel Skor, atau server menjalankan `php spark tabel-skor:impor` (`--trilomba` sekaligus memindah nomor Trilomba yang belum punya prestasi ke poin multi-lomba; `--timpa` mengganti baris yang sudah diubah admin dengan isi berkas).

**Pembaruan Tahap 9** (sekali di server yang sudah berjalan, setelah `git pull`): `php spark migrate --all`, `php spark faq:awal` (tanya-jawab bawaan), `php spark tabel-skor:impor --trilomba`, `php spark halaman-depan:carousel` (foto carousel bawaan), `php spark galeri:awal` (album bawaan), lalu `php spark aset:minify`. Semua aman diulang. Fitur publik baru (Jadwal, Seri, Peserta, Galeri, Videotron) tetap tersembunyi sampai admin menyalakan sakelarnya. Server tidak memasang Node: CSS dan pustaka tampilan sudah di-commit.

**Pembaruan Tahap 10** (sekali di server yang sudah berjalan, setelah `git pull`): `php spark migrate --all`, lalu `php spark data:sesuaikan`, lalu `php spark aset:minify`. Bila server juga memindah data pendaftaran dari zip Backup data, urutannya `migrate` → `data:sesuaikan` → `pendaftaran:impor` (K157 butir 4). Tahap 10 tidak menambah migrasi dan langsung tampil tanpa sakelar (K153 butir 4); logo Djarum Foundation bawaan dan huruf Barlow Condensed sudah di git tanpa langkah server.

**`php spark data:sesuaikan`** (F10-01, K154 butir 4) menyamakan data bawaan `data:awal` lama dengan data bawaan terbaru, hanya bila nilainya masih bawaan lama: nama kelompok umur `KU 8 (Seneng Playon)` menjadi `KU 8` (dilewati bila `KU 8` sudah ada), nomor WA contoh di Pengaturan > Pembayaran dan Pengaturan > Halaman Depan menjadi 0882-1527-7227, dan media sosial bawaan lama menjadi Instagram `milklife.id`, TikTok `@milklife_id`, dan YouTube `@milklife_id`. Isian yang sudah diubah admin dan data pendaftaran tidak disentuh, perubahan ditulis dalam satu transaksi, dan di akhir tercetak apa yang diubah dan dilewati; menjalankannya lagi tidak mengubah apa pun. Pesan WA bawaan baru (K166 butir 3) hanya dipasang `data:awal` pada pemasangan baru; di server yang sudah terisi admin mengubahnya di Pengaturan > Halaman Depan. Bila `pendaftaran:impor` menolak zip karena nama kelompok umur tidak ditemukan, pesannya menyebut nama yang tidak cocok dan menyarankan menyamakan nama di Pengaturan > Kelompok Umur atau menjalankan `data:sesuaikan`. Data pendaftaran dari backup tidak pernah masuk git; impornya dijalankan pemilik di server (K153 butir 1).

**Pembaruan Tahap 11** (sekali di server yang sudah berjalan, setelah `git pull`): `php spark cadangan:buat`, `php spark migrate --all` (tabel `atlet_diganti` dan `rekor`, kolom baru nomor lomba, babak, dan hasil; semua berbawaan perilaku lama), `php spark data:sesuaikan`, `php spark aset:minify`, lalu `php spark pembaruan:periksa`. Di Tahap 11 `data:sesuaikan` juga menghapus kalimat "per jenjang sekolah" dari tanya-jawab juara umum (F11-02), mengganti jawaban bawaan lama tanya-jawab ganti atlet yang belum diubah admin, dan menambahkan **sekali** lima tanya-jawab bawaan baru (nomor acara, arti kode hasil, waktu tangan, tidak dilombakan, rekor) bila pertanyaannya belum ada; penanda `Bantuan.faqTahap11` mencegah tanya-jawab yang dihapus admin kembali, dan `faq:awal` pada pemasangan baru langsung menyalakannya (F11-08). Halaman Rekor dan semua alamat videotron tetap mati sampai admin menyalakan sakelarnya; alamat `/layar` diberikan kepada operator videotron vendor (K173).

Setelah pembaruan yang membawa penggabungan pendaftaran (K109), `php spark pendaftaran:gabung` dijalankan sekali untuk menggabungkan pendaftaran Lunas ber-NPSN sama yang sudah ada. Perintah menampilkan daftar sekolahnya dan meminta konfirmasi; menjalankannya lagi tidak mengubah apa pun.

Setelah `git pull`, `php spark pembaruan:periksa` (K115) memeriksa tanpa mengubah apa pun: paket Composer yang belum sesuai `composer.lock`, migrasi yang belum dijalankan, sekolah di `data/sekolah/sekolah.json` yang belum ada atau berubah (kecuali yang disunting admin), NPSN dengan lebih dari satu pendaftaran Lunas, tabel skor bawaan yang belum terpasang atau nomor Trilomba yang masih berjenis hasil poin tanpa prestasi (`tabel-skor:impor --trilomba`, K143), data bawaan lama yang belum disamakan (`data:sesuaikan`, F10-01), dan berkas CSS/JS/SVG yang versi kecilnya belum ada atau lebih tua. Hasilnya daftar perintah yang perlu dijalankan, berurutan. Pemeriksaan yang gagal, misalnya karena MySQL mati, tidak dianggap beres.

**Turnstile** (K122): panduan produksi memuat langkah memasang kunci rahasia dari dasbor Cloudflare ke `.env`, mengisi daftar nama host, dan cara mematikan Turnstile sementara dengan mengosongkan kuncinya.

**Kesiapan server ditangani pemilik** (K119): alamat pemeriksa kesehatan `/sehat`, pemantauan uptime, tembolok halaman depan, batas unggah dan `memory_limit` PHP (`docker/php.ini`), dan uji pulih cadangan diatur pemilik sendiri di server, bukan bagian pekerjaan web dan tidak dikerjakan agen.

**Cadangan** (K66, K182): dibuat **harian** oleh cron host yang menjalankan `cadangan:buat` di container app, ke folder host di luar proyek. Satu berkas zip berisi basis data serta foto ofisial, bukti bayar, dan unggahan admin. Cadangan yang lebih tua dari **14 hari** dihapus otomatis. Setiap cadangan baru juga disalin ke bucket Cloudflare R2 milik panitia, supaya tetap ada bila VPS rusak. Pemulihan dilakukan manual mengikuti panduan.

Peserta menjangkau server dari internet lewat **Cloudflare Tunnel** (K31) dengan cloudflared yang berjalan di host VPS, tunnel yang sama dengan aplikasi lain di server itu (K184). Pengaturannya berada di luar kode aplikasi, kecuali satu hal: alamat IP pengunjung dibaca dari kepala `CF-Connecting-IP` yang hanya dipercaya dari terowongan (127.0.0.1, ::1, dan subnet compose `MILKLIFE_SUBNET_PROXY`, K182), supaya pembatasan laju tetap menghitung per pengunjung.

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
| 7. Penyelesaian | Minify dan pengecilan gambar, keamanan produksi, tim tanpa pembayar, cadangan harian, panduan pemasangan produksi, revisi rancangan ini, uji menyeluruh (K67); galat CSRF, kirim idempoten, Backup data tanpa memori (K118), Turnstile (K122), susunan dokumen agen (K120), pecah berkas besar (K121), PHPStan (K123) |
| 8. Hari lomba dan hasil | Riwayat tindakan admin, penanda bukti bayar baru, daftar ulang dan tandai hadir, cetak nomor atlet massal, daftar peserta per nomor, hasil lomba, klasemen sekolah (K124, K125); siap sebelum pertemuan teknik 6 Desember 2026 dan lomba 9 Desember 2026 |
| 9. Rombak tampilan dan fitur publik hari lomba | Flowbite Pro dan Tailwind 4 menggantikan Bootstrap di semua halaman, halaman depan baru dengan carousel, Bantuan dan FAQ, jadwal pertandingan, seri dan lintasan, aturan nomor dan lembar juri, kalkulator poin multi-lomba, metode klasemen, peserta publik, galeri, papan videotron, panel admin baru, saluran gambar Codex (K138–K151) |
| 10. Identitas visual, panel admin, dan data terbaru | Data bawaan terbaru dan `data:sesuaikan`, peta lokasi, panel admin bersidebar berwarna dan lipat dengan jejak di setiap halaman dan tabel berpaginasi, gaya editorial (Barlow Condensed, judul Inggris), hero foto asli dan slideshow 5 detik, navigasi publik baru, pratinjau unduhan, gambar anime G10, perombakan tata bahasa, kenyamanan wizard, revisi 30 rancangan ini (K153–K168) |

## 17. Asumsi yang perlu ditinjau

Hal berikut saya putuskan sendiri karena tidak disebut dalam wawancara. Mohon dikoreksi bila keliru.

1. ~~Kelompok umur ditentukan dari usia saja; atlet tidak bisa "naik" ke kelompok umur di atasnya, dan jenjang sekolah tidak membatasi kelompok umur.~~ **Diganti K23:** kelompok umur tetap ditentukan dari usia saja dan atlet tidak bisa "naik", tetapi jenjang sekolah membatasi kelompok umur sesuai centang admin (6.4). **Diganti K95:** jenjang sekolah menyaring kelompok umur lebih dulu, lalu tahun lahir menentukan di antaranya; rentang boleh beririsan antar-jenjang (6.4). **Ditambah K134:** dalam satu jenjang rentang boleh beririsan, dan ofisial memilih kelompok umur atlet yang lahir di tahun irisan (6.4).
2. Frog Jump dan Turbo Throw (KU 10) berjenis **individu**. **Dikonfirmasi K21.**
3. Trilomba (KU 12) adalah **satu nomor individu** dan dihitung satu dari batas dua nomor individu. **Dikonfirmasi K22.**
4. Nomor urut ID pendaftaran berlaku global, bukan per jenjang. **Dikonfirmasi K32.**
5. ~~Data tersimpan di langkah Biaya (saat konfirmasi), bukan saat tombol "Pendaftaran Selesai".~~ **Dikonfirmasi K32, lalu diganti K90:** data tersimpan di langkah Pembayaran saat ofisial mengirim pendaftaran bersama bukti transfer (7).
6. Ofisial tidak bisa mengubah pendaftaran yang sudah tersimpan; perubahan lewat admin. **Dikonfirmasi K32.**
7. Foto ofisial tidak tampil di halaman cek. **Dikonfirmasi K32.**
8. ~~Bila admin mengubah pendaftaran yang sudah lunas sehingga totalnya berubah, status tetap Lunas dan selisihnya diurus panitia di luar sistem.~~ **Diganti K32:** status kembali ke Menunggu dan admin menandai Lunas lagi (6.9).

Semua asumsi sudah diputuskan pemilik. Delapan asumsi Tahap 8 (riwayat pendaftaran terhapus, bukti dianggap diperiksa, pemindai QR, hadir per atlet, ukuran BIB, satuan prestasi, peringkat sama di Final, alamat halaman publik) ada di `dokumen/draf/rencana-implementasi-tahap-8.md` dan disetujui bersama rencananya (K125). Juknis resmi tidak ditunggu (K55); juknis yang kemudian diterima 16 November 2026 disimpan di `sumber/juknis/` dan dicocokkan dengan aturan ini (K112): hanya batas tahun lahir KU 12 yang berbeda, dan sudah disesuaikan.

## 18. Hari lomba, hasil, dan klasemen

Ketentuan Tahap 8 (K124, K125). Bagian ini menjadi aturan saat dibangun; rincian teknis dan pembagian tugas ada di `dokumen/draf/rencana-implementasi-tahap-8.md`. Semua halaman memakai akun admin yang sama, tanpa peran baru (K124 butir 9), kecuali Lembar juri yang juga dibuka akun juri (K181). Tidak ada pustaka baru: PDF lewat mPDF dan Excel lewat PhpSpreadsheet.

**Daftar ulang.** Di hari lomba admin memindai kode QR bukti pendaftaran dengan kamera ponsel (fitur pemindai bawaan peramban, tersedia di Chrome Android) atau mengetik ID pendaftaran bila peramban tidak mendukung. Halaman menampilkan nama sekolah, status (Lunas, Menunggu, Semua atlet ditolak), dan atlet dengan nomor atlet, nomor lomba, dan regu, serta tombol **Tandai hadir** per atlet dan untuk semua atlet; batal hadir tersedia untuk salah tekan. Kode QR pendaftaran yang sudah digabung (K109) diarahkan ke pendaftaran gabungannya. Bila admin yang sedang masuk membuka alamat kode QR dengan aplikasi kamera, halaman bukti menampilkan tautan "Buka daftar ulang"; bukti PDF tetap bisa dibuka.

**Cetak nomor atlet (BIB) massal.** Admin memilih kelompok umur (atau semua) dan mengunduh PDF: satu BIB per setengah A4 mendatar, dua per halaman, berisi nomor atlet besar, nama atlet, nama sekolah, dan kelompok umur, urut nomor atlet. Hanya atlet pendaftaran Lunas.

**BIB per sekolah, gambar, dan latar (K137).** Di halaman yang sama ada daftar sekolah (per NPSN; pendaftaran tanpa NPSN sekolah sendiri) dengan unduhan PDF dan ZIP per sekolah. ZIP sekolah berisi gambar JPG per atlet (2480 × 1754 piksel, 300 dpi, nama `<nomor atlet> - <NAMA>.jpg`) dan PDF sekolah itu; **Unduh semua sekolah (ZIP)** berisi satu folder per sekolah (`<nama sekolah> - <NPSN>`). Admin bisa mengunggah **satu latar** untuk semua BIB (JPG, PNG, atau WEBP, paling besar 5 MB) yang mengisi penuh satu BIB; gambar yang bukan 210 × 148,5 mm dipotong di tengah. Selain mengunggah sendiri, admin bisa memilih salah satu **latar bawaan** (dua terang, dua gelap, G9-01) yang hanya ditawarkan bila berkasnya sudah ada di `public/aset/gambar/bib/`; memilih latar bawaan menggantikan latar unggahan (F9-16). Di atas latar admin memilih kepala (logo, nama acara, kelompok umur) dicetak atau tidak, dan tulisan gelap atau terang. Tanpa latar BIB tetap putih dengan kepala dan tulisan gelap. Gambar contoh dengan tampilan yang berlaku tampil di halaman itu; PDF dan gambar memakai tata letak yang sama. ZIP dibuat sambil dikirim, jadi unduhan langsung mulai walau ZIP semua sekolah butuh beberapa menit dan web berada di balik Cloudflare.

**Daftar peserta per nomor lomba.** PDF dan Excel untuk pemanggilan atlet dan juri: No, nomor atlet, nama, sekolah, tanda hadir, dan kolom kosong untuk catatan juri. Nomor beregu ditulis per regu dengan anggota inti dan cadangan. Satu PDF bisa memuat semua nomor satu kelompok umur, satu nomor per halaman.

**Hasil lomba** (K124 butir 2–4):

- Setiap nomor lomba punya jenis hasil yang diatur admin di Pengaturan > Nomor lomba: **waktu** (makin kecil makin baik), **jarak**, **poin** (poin akhir ditulis langsung), atau **poin multi-lomba** (makin besar makin baik; K140 butir 7b). Data awal mengikuti juknis: lari dan estafet waktu, lompat dan lempar jarak, Trilomba poin multi-lomba dengan tabel Trilomba O2SN SMP.
- **Hasil dicatat untuk semua babak.** Admin membuat babak per nomor lomba, misalnya Penyisihan seri 1, seri 2, …, lalu Final; nomor yang langsung final (misalnya 1000 m dan Turbo Throw) cukup satu babak Final. Peserta babak dipilih dari atlet Lunas di nomor itu, dan babak berikutnya bisa disalin dari yang lolos.
- Admin mengisi prestasi; server memeriksa formatnya (waktu `m:ss,cc` atau `ss,cc`, jarak `m,cc`) dan pesan galat menyebut contoh yang benar. Keterangan DNS (tidak start), DNF (tidak finis), DQ (diskualifikasi, boleh disertai pasal pendek seperti TR16.8), dan NM (tanpa lompatan atau lemparan sah) tidak diberi peringkat. Keterangan r (mundur) tetap diberi peringkat dari prestasi sebelum mundur. NM dan r hanya untuk nomor lompat dan lempar; NM terisi sendiri bila semua percobaan X (CR 25, F11-05).
- Web mengurutkan peringkat dari prestasi menurut jenis hasilnya; prestasi sama = peringkat sama, kecuali dipisah pemecah seri di bawah. Admin bisa mengoreksi peringkat Final secara manual (misalnya dari foto finis), dengan tanda "diubah admin".
- **Aturan nomor** (K140 butir 7c, F9-09): nomor lompat/lempar (jenis hasil jarak) diatur **banyak percobaannya** di Pengaturan > Nomor Lomba (bawaan 3, paling banyak 6). Juri mengisi jarak tiap percobaan, `X` bila gagal, `-` bila dilewati; **prestasi = jarak sah terjauh**. **Pemecah seri**: prestasi sama dipisah **prestasi terbaik kedua**, lalu ketiga, dan seterusnya (yang tidak punya jarak sah kedua di bawah); sakelar per nomor, bawaan menyala. **Lari**: waktu boleh ditulis sampai **seperseribu detik** (misalnya 12,345 dari foto finis); prestasi tetap ditulis perseratus, dibulatkan ke atas, dan waktu yang sama di perseratus dipisah seperseribunya **bila diisi**; selain itu bersama. Pemecah seri hanya dipakai bila semua yang berprestasi sama punya datanya, jadi hasil lama (tanpa percobaan dan seperseribu) berperingkat persis seperti sebelumnya. Pemecah seri juga dipakai menentukan Q/q.
- **Lembar juri** (K140 butir 7d, F9-09) `admin/juri`, dibuka akun admin dan akun juri (K181): daftar babak per hari jadwal (hari ini di atas) → pilih seri → satu kartu besar per peserta (lintasan atau urutan, nomor atlet besar, nama, sekolah) dengan isian berpapan angka ponsel: waktu, jarak tiap percobaan dengan tombol X, poin, atau prestasi tiap komponen poin multi-lomba, ditambah keterangan DNS/DNF/DQ (NM dan r untuk lompat dan lempar) dan pasal DQ. **Setiap baris tersimpan sendiri** begitu diisi, dengan tanda Belum tersimpan, Menyimpan, Tersimpan (beserta prestasi terbaik atau total poin), atau pesan galat yang menyebut cara memperbaikinya; sambungan putus dicoba lagi otomatis. Penyimpanan **mengunci babaknya**, jadi beberapa juri bisa mengisi babak berbeda bersamaan; baris yang sudah diubah admin lain sejak dibuka ditolak dengan pesan untuk memuat ulang (juga di halaman isi hasil). Koreksi peringkat Final tetap. **Peringkat dan Terbitkan** tetap di halaman hasil; halaman hasil menampilkan percobaan, dan mengganti prestasi di sana melepas percobaan baris itu.
- **Juara I–III diambil dari babak Final.** Peringkat sama di Final memberi piala yang sama kepada masing-masing.
- **Poin multi-lomba** (K140 butir 7b, menggantikan K124 butir 3): nomor berjenis hasil ini memilih **tabel skor** dan komponennya (Trilomba KU 12: Lari 60 m, Lompat Jauh, Tolak Peluru, sesuai juknis). Admin mengisi **prestasi mentah tiap komponen** di isian hasil babak; web mencari poinnya di baris tabel sesuai kategori nomor (putra/putri). Prestasi di antara dua baris memakai **baris yang lebih buruk** (kebiasaan scoring table IAAF/PASI dan World Athletics); prestasi di luar tabel bernilai 0 poin dan ditandai peringatan supaya salah ketik terlihat. Komponen yang kosong bernilai 0 dan atletnya tetap masuk peringkat; di halaman hasil, PDF, dan Excel ditulis "–" dengan 0 poin (K143). **Total = jumlah poin komponen**, disimpan sebagai prestasi nomor itu, jadi peringkat, juara, klasemen, halaman publik, dan unduhan memakai total itu; halaman hasil, PDF, dan Excel juga menulis prestasi dan poin tiap komponen. Jenis hasil, tabel, dan komponen nomor tidak bisa diganti setelah ada prestasi.
- **Tabel skor** di Pengaturan > Tabel Skor: tabel bawaan **Trilomba O2SN SMP** (60 m, lompat jauh, tolak peluru) dan **Pancalomba SMA/SMK** (100 m, 800 m, lompat jauh, tolak peluru, lempar lembing), putra dan putri, diambil dari tabel penilaian panitia (`sumber/tabel-skor/`) dengan alat di `data/alat/` yang mencocokkan baris sampel PDF (misalnya 60 m putra 7,00 = 882 poin). Admin bisa mengubah, menambah, dan menghapus baris, mengganti semua baris satu komponen dan kategori dari Excel (kolom prestasi dan poin), mengunduhnya sebagai Excel, dan menambah tabel lain dari panitia. Setiap perubahan baris langsung menghitung ulang poin dan peringkat hasil yang memakai tabel itu. Isian poin langsung (jenis hasil Poin) tetap bisa dipakai.
- **Nomor beregu**: juara per regu; satu regu juara = satu piala untuk sekolahnya.
- **Terbitkan** (K124 butir 7): hasil satu nomor tampil di halaman publik `hasil` setelah admin menekan "Terbitkan" untuk nomor itu, dan bisa ditarik kembali.
- **Cara pencatatan waktu** (K172 butir 5, F11-04): nomor berjenis waktu memilih **elektronik** (bawaan) atau **tangan** di Pengaturan > Nomor lomba. Waktu tangan dibulatkan ke atas ke 0,1 detik saat disimpan (TR 19: 12,34 menjadi 12,4; 12,30 tetap 12,3), tanpa seperseribu (juga sebagai pemecah seri), dan ditulis satu angka desimal di semua keluaran. Halaman hasil publik, PDF, halaman nomor admin, dan judul kolom Excel menandai waktu tangan; videotron tidak. Prestasi lama di nomor yang diubah ke tangan tidak ditulis ulang, tetapi dibaca bulat ke atas untuk tampilan, peringkat, dan Q/q. Cara pencatatan tetap bisa diubah setelah ada prestasi; satu cara berlaku untuk semua babak nomor itu (K176).
- **Status nomor dan batas peserta** (K172 butir 4, F11-03): di Pengaturan > Nomor lomba, bagian Pelaksanaan lomba, nomor bisa ditandai **Tidak dilombakan** dengan alasan wajib, terpisah dari sakelar Aktif pendaftaran. Nomor itu keluar dari jadwal, seri, lembar juri, videotron, dan klasemen; halaman hasil publik menulis "Tidak dilombakan." beserta alasannya tanpa juara dan unduhan, walau belum terbit. **Batas peserta minimal** (1–99; atlet untuk individu, regu untuk beregu) memunculkan peringatan di Hasil dan Seri admin bila peserta Lunas kurang dari batas, atau peserta hadir kurang dari batas setelah daftar ulang dipakai. Medali tidak dikurangi otomatis (K176).

**Rekor kejuaraan** (K172 butir 3, F11-06): dicatat mulai Seri 2; hasil Seri 1 tidak diimpor.

- Rekor dicatat per **nomor lomba** (kelompok umur dan jenis kelamin ikut nomornya), **spesifikasi alat** (teks bebas per nomor di Pengaturan > Nomor lomba, huruf besar-kecil tidak dibedakan), dan **cara pencatatan waktu**. Rekor waktu tangan dan waktu elektronik tidak saling memecahkan; rekor dengan alat berbeda juga tidak.
- Saat hasil nomor diterbitkan, prestasi babak Final yang **lebih baik** dari rekor yang berlaku menjadi rekor baru; bila belum ada rekor, prestasi terbaik Final menjadi rekor pertama. Prestasi yang sama dengan rekor tidak memecahkannya. NM, DNS, DNF, dan DQ tidak membuat rekor; r tetap dinilai. Peserta dengan prestasi terbaik yang sama dicatat semua.
- Hasil yang membuat rekor ditandai **CR** di halaman hasil admin dan publik, PDF, Excel, dan videotron.
- **Tarik kembali** terbitan menghapus rekor dari nomor itu sehingga rekor lama berlaku lagi. Hasil terbit yang dikoreksi, babak yang dihapus, atau peserta yang dikeluarkan menghitung ulang rekornya.
- Admin bisa **mengisi rekor dengan tangan** di `admin/rekor` (misalnya rekor awal dari panitia), lalu mengubah dan menghapusnya. Rekor dari hasil hanya berubah lewat hasilnya.
- Halaman publik `/rekor` menampilkan rekor yang berlaku; sakelarnya di `admin/rekor`, bawaan mati. Tanda CR tidak bergantung pada sakelar ini.
- Rekor isian admin dan spesifikasi alat ikut Backup data; rekor dari hasil dihitung ulang setelah impor.

**Klasemen sekolah** (K124 butir 5–6, K140 butir 7a, K172 butir 2):

- **Kontingen = sekolah per NPSN** (sejalan K109); pendaftaran tanpa NPSN dihitung sebagai kontingen sendiri.
- **Klasemen dihitung per kelompok umur** (K172, menggantikan per jenjang K124). Di **Pengaturan > Hasil** admin memberi setiap kelompok umur nomor klasemen; kelompok umur bernomor sama digabung dalam satu klasemen (misalnya KU 8 dan KU 10 seperti Seri 1). Bawaannya satu klasemen per kelompok umur, dan kelompok umur yang tidak masuk gabungan mana pun (termasuk yang baru ditambah) tetap punya klasemen sendiri. Juara umum I–III per klasemen ditandai. Metodenya juga dipilih admin di Pengaturan > Hasil (K140 butir 7a):
  - **Medali** (bawaan, juknis bagian 11): juara I terbanyak; bila sama, juara II, lalu juara III; bila masih sama, peringkat bersama. Hanya sekolah yang punya piala yang tampil.
  - **Poin per peringkat**: setiap atlet atau regu di peringkat 1–8 babak Final menyumbang poin untuk sekolahnya menurut daftar poin peringkat 1–8 yang bisa diubah (bawaan 7-5-4-3-2-1-0-0; angka bulat 0–999, tidak boleh naik untuk peringkat berikutnya, peringkat 1 lebih dari 0). Poin terbanyak menang; bila sama, juara I, lalu II, lalu III; bila masih sama, peringkat bersama. Sekolah yang punya piala atau poin tampil. Peringkat sama di Final memberi poin yang sama kepada masing-masing.
  - **Kolom poin** tampil di klasemen admin, publik, PDF, dan Excel apa pun metodenya, dan kalimat susunan klasemen dan cara penentuan di halaman, PDF, serta tanya-jawab (`{cara_juara_umum}`) mengikuti pengaturan yang berlaku. Perubahan metode langsung berlaku tanpa menghitung ulang data.
- Perhitungannya kelas tersendiri tanpa ketergantungan pada permintaan HTTP, dengan uji unit.
- Halaman admin menghitung semua nomor (yang belum terbit diberi tanda); halaman publik `hasil/klasemen` hanya menghitung nomor yang sudah terbit.

**Jadwal pertandingan** (Tahap 9, F9-07, K146): setiap babak boleh diberi tanggal, jam mulai, dan lokasi, satu per satu di halaman hasil babak atau sekaligus di halaman admin Jadwal pertandingan. Halaman publik `jadwal` menampilkan tab per hari (waktu, nomor, putra/putri, kelompok umur, babak) dengan status otomatis: Belum mulai, Berlangsung (sejak jam mulai sampai hasil nomornya terbit), Selesai (hasil terbit), serta tautan Daftar seri dan Hasil; PDF per hari. Sakelar tampil bawaan mati, ada di Pengaturan > Halaman Depan dan di halaman Jadwal pertandingan. Jadwal pendaftaran lama bernama **Agenda**.

**Nomor acara** (K172 butir 7, F11-07): setiap babak bisa punya nomor acara (Acara 101, 102, …) untuk pembawa acara, juri, dan lembar hasil. Di Jadwal pertandingan admin mengisi nomor awal (bawaan 101; nomor terakhir yang dipakai diingat) lalu menekan **Beri nomor acara**: semua babak bertanggal diberi nomor berurutan menurut tanggal, jam, kelompok umur, nomor, lalu babak, dan nomor lama diganti. Babak tanpa tanggal tidak diberi nomor. Nomor bisa diubah per babak di Jadwal pertandingan dan di isian jadwal babak; angka 1–9999, tidak boleh kembar antarbabak. Nomor tampil di jadwal publik dan PDF-nya, daftar seri (publik, admin, PDF, Excel), lembar juri, halaman hasil, PDF, dan Excel hasil (kolom Acara); kolom Acara hanya muncul bila ada babak yang bernomor. Videotron belum menampilkannya (K176).

**Seri, lintasan, dan alur babak** (K140 butir 8, F9-08):

- **Aturan per nomor** di halaman admin Seri dan lintasan: lintasan per seri (bawaan 8) untuk nomor waktu; nomor lompat, lempar, dan poin memakai **urutan tampil** tanpa lintasan dengan peserta per kelompok (bawaan satu kelompok). **Urutan lintasan unggulan** ditulis berkelompok, kelompok undian dipisah titik koma; bawaan World Athletics untuk 8 lintasan `3,4,5,6;7,8;1,2` (empat terbaik diundi di lintasan 3–6, dua berikutnya 7–8, dua terakhir 1–2).
- **Aturan lolos per babak**: **Q** = n terbaik tiap seri, **q** = m prestasi terbaik berikutnya dari semua seri; peringkat sama di batas ikut lolos; DNS/DNF/DQ/NM tidak lolos, r dinilai dari prestasinya. Kosong = otomatis mengisi penuh babak berikutnya (lintasan × seri babak berikutnya; nomor tanpa lintasan delapan besar).
- **Bagi otomatis**: babak pertama mengambil atlet atau regu Lunas nomor itu (pilihan: hanya yang hadir di daftar ulang; regu hadir bila ada anggotanya yang hadir), dibagi **rata** ke seri paling sedikit yang muat (admin boleh minta lebih banyak), **peserta satu sekolah (NPSN) dipisah** ke seri berbeda sebisanya, lalu lintasan diacak dengan lintasan tengah dipakai lebih dulu. Tidak ada unggulan di babak pertama karena pendaftaran tidak membawa prestasi. Babak lanjutan mengambil yang lolos, diurutkan dari prestasi, dibagi zig-zag ke seri, dan diberi lintasan menurut urutan lintasan unggulan. Acak memakai **benih yang disimpan** sehingga pembagian bisa diulang persis.
- Admin **menggeser** peserta antarseri dan lintasan (bentrok ditolak), lalu **mengunci** daftar seri; babak yang dikunci atau sudah berprestasi tidak dibagi ulang.
- **Babak berikut terisi otomatis**: setelah hasil babak yang sudah dibagi lengkap (semua peserta berprestasi atau berketerangan), babak berikutnya yang belum dibagi, belum dikunci, dan belum berprestasi diisi dari Q/q dan diunggulkan. Koreksi admin tidak ditimpa; bila peserta berbeda dari yang lolos, halaman admin memberi peringatan.
- **Unduhan daftar seri** PDF dan Excel per nomor dan per hari (babak terjadwal hari itu, urut jam) untuk pertemuan teknik dan juri: lintasan, nomor atlet, nama, sekolah, dan kolom prestasi kosong; babak yang belum dikunci ditandai.
- **Publik** `jadwal/<babak>`: seri dan lintasan babak yang sudah dikunci, serta **bagan alur babak** nomor itu (Penyisihan seri 1…n → Semifinal → Final) dengan status; tanda Q/q dan prestasi tampil setelah hasil nomornya terbit. Halaman Jadwal menautkan "Daftar seri" untuk babak yang dikunci. Sakelar tampil **bawaan mati**.

**Unduhan** (K124 butir 8): hasil per nomor dan klasemen tersedia sebagai PDF dan Excel. Tombol Unduh PDF dan Unduh Excel di halaman hasil dan klasemen berupa ikon saja (ikon PDF dan ikon lembar kerja) dengan nama aksesibel lengkap; teksnya muncul saat kursor di atasnya, saat difokus, atau saat ditekan. Halaman Hasil, hasil per nomor, dan Klasemen punya jejak halaman lengkap di kiri atas, misalnya Beranda › Hasil › KU 10 › Lari 60 m putra (K175 butir 7). Piagam tidak dibuat web.

**Videotron** (Tahap 9, K141; diubah K173, F11-09). Videotron di arena dijalankan operator vendor, jadi web menyediakan **daftar tampilan** di `/layar` (tanpa masuk) dan operator memilih yang dibutuhkan pada saat itu. Daftar dikelompokkan per hari dan sesi (Pagi sebelum 11.00, Siang 11.00 sampai sebelum 15.00, Sore mulai 15.00, menurut jam mulai babak), lalu klasemen juara umum, kelompok umur dan nomor lomba, dan papan putar. Alamat lengkap tiap tautan tertulis dan bisa disalin. Tiap tautan membuka **satu tampilan tetap** layar penuh yang tidak berganti sendiri: **per seri** (daftar start urut lintasan setelah daftar seri dikunci, lalu hasil seri itu dengan peringkat di dalam seri setelah hasil nomornya terbit), **per sesi** (semua hasil terbit di sesi itu), **per klasemen**, **per kelompok umur**, dan **per nomor lomba** (semua babak terbit, Final di atas, dengan tanda Q/q). Hanya data terbit. Isi tampilan diambil ulang diam-diam setiap 30 detik; bila jaringan putus, data terakhir tetap tampil dengan tanda "Sambungan terputus". Layar tidak menampilkan jam, waktu pembaruan, nomor halaman, atau nama sesi; baris tabel paling pendek setinggi dua baris teks. Tabel yang tidak muat satu layar dibagi halaman: operator memilih dengan `?halaman=2` atau panah kiri/kanan; di layar mendatar lebar dua halaman tampil berdampingan. Latar bisa diganti admin dengan mengunggah gambar (JPG, PNG, atau WEBP) di Pengaturan > Videotron; latar unggahan tampil tanpa animasi, dan bila kosong memakai latar bawaan. **Papan putar** lama ada di `/layar/putar`: berganti sendiri antara hasil nomor yang baru terbit (babak Final atau babak terakhir, 6 nomor), juara umum per klasemen kelompok umur (sampai peringkat 10), babak yang sedang berlangsung, dan jadwal berikutnya; isi dan lama tiap tampilan (5–120 detik) diatur di Pengaturan > Videotron. Semua tampilan memakai tata letak cair untuk ukuran layar apa pun: mendatar lebar (pita LED), mendatar, tegak, dan sangat kecil (hanya peringkat, nama, prestasi), dengan huruf mengikuti ukuran layar dan parameter `?skala=`, `?susunan=`, `?baris=` untuk memaksa. Sakelar tampil di Pengaturan > Videotron (bawaan mati) berlaku untuk daftar dan semua tampilan; selama mati alamatnya tidak ditemukan.

**Data ikut terbawa**: tabel baru (11) ikut di Backup data dan pindah server (K76) serta cadangan; penggabungan pendaftaran (K109) memindahkan baris yang menunjuk pendaftaran; hapus pendaftaran menangani baris yang menunjuknya.

## 19. Bantuan, peserta publik, dan galeri

Ketentuan Tahap 9 (K140, K141, K143, K144, K146); rincian di `dokumen/draf/rencana-implementasi-tahap-9.md`.

**Bantuan dan FAQ** (F9-04, K143): halaman `bantuan` berisi tanya-jawab per kategori (Sebelum mendaftar, Mengisi pendaftaran, Pembayaran dan bukti, Cek pendaftaran dan bukti PDF, Hari lomba, Hasil dan juara) yang bisa dicari tanpa muat ulang, panduan bergambar langkah pendaftaran, dan tautan WA panitia. Data bawaan 59 tanya-jawab (termasuk ganti atlet, nomor acara, arti kode DNS/DNF/DQ/NM/r/CR, waktu tangan, nomor tidak dilombakan, dan rekor kejuaraan sejak Tahap 11) dipasang `data:awal` atau `faq:awal`, dan admin mengubahnya di Pengaturan > Bantuan. Jawaban memakai isian otomatis dari pengaturan (misalnya `{tutup_pendaftaran}`, `{daftar_biaya}` yang hanya dirinci bila biaya tampil di halaman depan, `{cara_juara_umum}`). Setiap langkah wizard punya tombol **Bantuan** yang membuka laci berisi tanya-jawab kategori langkah itu. Bantuan tampil di bilah atas dan laci menu publik selama butirnya dicentang di Pengaturan > Halaman Depan (bawaan menyala, F10-07).

**Peserta publik** (F9-12, K144): halaman `peserta` mencari nama, sekolah, atau nomor atlet dan menyaring kelompok umur, nomor lomba, dan putra/putri. Isinya nama, sekolah, nomor atlet, kelompok umur, putra/putri, dan nomor lomba, **hanya dari pendaftaran Lunas**; NISN, tanggal lahir, foto, dan data ofisial tidak pernah tampil. Data dimuat per halaman dari server dengan kerangka saat memuat, dibatasi 60 permintaan per menit per pengunjung. Sakelar di Pengaturan > Halaman Depan, bawaan mati.

**Galeri tautan luar** (F9-06, K146): album bersampul yang membuka tautan Instagram atau Google Drive di tab baru; foto tidak disimpan di server selain sampulnya (WebP 1200 piksel). Album bawaan "Seri sebelumnya" bersampul foto acara sebelumnya, tautannya diisi admin; paling banyak 60 album. Sakelar di Pengaturan > Galeri, bawaan mati.

**Gambar dan Codex** (K140 butir 5, K150): Codex hanya membuat aset gambar dari brief di `desain/tugas/` sesuai `AGENTS.md` (sejak Tahap 10 anime olahraga 2D cel-shaded, K153; sebelumnya 3D lembut; palet MilkLife, tanpa teks; logo resmi di kaus ditempel saat pasca-proses) dan membuka PR; Claude menulis kode pemasangannya, meninjau, dan menggabung PR setelah CI hijau. Foto acara sebelumnya dan contoh desain dari pemilik disimpan di `sumber/foto-acara/`. Rujukan gaya Tahap 10 (hero editorial, gaya anime lintasan, dan empat foto Supersoccer Arena tanpa metadata) ada di `desain/referensi/` (K153, K158).

## Lampiran: data awal nomor lomba

Diambil dari flyer Seri 2 – 2026, dicocokkan dengan juknis resmi (K112), dan diisi lewat perintah sekali jalan saat pemasangan (K25, diperluas K73). Semua bisa diubah admin. Jenjang yang boleh ikut: KU 8–12 SD/MI, KU 15 SMP/MTs, KU 18 SMA/SMK/MA. Biaya individu: KU 8–12 Rp 25.000, KU 15 Rp 35.000, KU 18 Rp 50.000 per nomor. Batas anggota nomor beregu dua kali jumlah inti per regu, dan regu per sekolah tidak dibatasi (K107, sebelumnya 3 menurut K105). Perintah yang sama juga mengisi rekening, isi halaman depan, jadwal, kontak, dan berkas unduhan; rekening dan sebagian unduhan masih contoh yang diganti admin. Nomor WA pembayaran dan tanya admin 0882-1527-7227, media sosial Instagram milklife.id, TikTok @milklife_id, dan YouTube @milklife_id (K153). Pesan WA bawaan: tanya "Halo panitia, saya ingin bertanya tentang lomba." dan bagikan "Yuk ikut lomba atletik pelajar ini. Info dan pendaftaran:" (alamat situs ditambahkan otomatis), keduanya ditutup tagar `#MilkLifeAthleticsChallengeSeri2` (K166 butir 3). Logo Djarum Foundation bawaan dari berkas pemilik, dan alamat peta bawaan Supersoccer Arena (F10-01, F10-02). Server yang diisi versi lama (nama `KU 8 (Seneng Playon)`, WA contoh, media sosial lama) disamakan dengan `php spark data:sesuaikan`. Batas tahun lahir (K36, K112): KU 8 lahir 2019 ke atas; KU 10 lahir 2016–2018; KU 12 lahir 2014–2015 (juknis; sebelumnya 2015); KU 15 lahir 2011–2014; KU 18 lahir 2008–2010. Awalan nomor atlet diisi satu per kelompok umur (K33): KU 8 = 1, KU 10 = 2, KU 12 = 3, KU 15 = 4, KU 18 = 5; admin bisa mengubah atau menambahnya. Nomor individu dibuat dua kali, putra dan putri; nomor beregu mengikuti kategori di tabel.

| Kelompok umur | Individu (putra dan putri) | Beregu |
|---|---|---|
| KU 8 | Lari 40 m | – |
| KU 10 | Lari 60 m, Frog Jump, Turbo Throw | Kanga's Escape putra/putri (4 orang, Rp 100.000); Formula 1 putra/putri (4 orang, Rp 100.000); Estafet 8 × 50 m mixed (8 orang, Rp 150.000) |
| KU 12 | Lompat Jauh, Tolak Peluru, Lari 60 m, Lari 1000 m, Trilomba | Estafet 8 × 50 m mixed (8 orang, Rp 150.000) |
| KU 15 | Jalan Cepat 2000 m, Lari 80 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 5 × 80 m putra/putri (5 orang, Rp 100.000) |
| KU 18 | Jalan Cepat 3000 m, Lari 100 m, Lari 400 m, Lari 800 m, Lari 1500 m, Lompat Jauh, Lompat Tinggi, Lempar Lembing, Lempar Cakram, Tolak Peluru | Estafet 4 × 100 m putra/putri (4 orang, Rp 100.000); Estafet 4 × 400 m mixed (4 orang, Rp 100.000) |
