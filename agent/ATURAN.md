# Aturan yang Berlaku

Ringkasan keputusan pemilik yang **masih berlaku**, satu baris per aturan dengan nomor K. Alasan dan riwayatnya di `agent/KEPUTUSAN.md` (`grep K<nomor>`). Bila keputusan baru mengubah aturan, baris di sini **diganti** dalam PR yang sama, bukan ditambah. Rincian perilaku ada di `dokumen/final/rancangan-web-pendaftaran.md`.

## Proses dan repo

- Satu-satunya penulis commit Arumi Studios, tanpa atribusi alat AI di commit, PR, kode, dan dokumen (K13). Cabang utama `main` (K14).
- Repo privat, lisensi hak milik di `LICENSE` (K29, K138). Data siswa, foto unggahan, cadangan, `.env`, dan rahasia tetap tidak masuk git.
- CI GitHub menjalankan `composer uji` di setiap PR dan push ke `main`; uji lokal tetap sebelum PR (K46).
- Sesi cloud disiapkan Setup script; agen tidak memasang ulang (K62). Isi skrip: `agent/SETUP-CLOUD.md`.
- Koordinator membuat sub-sesi sendiri, paling banyak 3 sekaligus (K38, K65), di Tahap 9 paling banyak 5 (K145), dan menggabung PR (*squash*) setelah CI hijau; sub-sesi tidak menggabung PR sendiri (K126).
- Dokumen boleh diubah langsung sesuai kondisi terbaru, PDF dibuat ulang (K100, K101).
- Laragon untuk pengembangan dan produksi, PHP 8.2+ (K08, K18); web lewat Cloudflare Tunnel, IP dari `CF-Connecting-IP` (K31).
- Tidak menambah pustaka kecuali yang disetujui: Turnstile tanpa pustaka PHP (K122), PHPStan `require-dev` (K123), Lucide dan Three.js lokal tanpa CDN (K140), dan rantai bangun Flowbite Pro (Tailwind 4, plugin dan JS Flowbite) dengan Node hanya di komputer pengembang dan CI (K141).
- Tampilan pindah ke Tailwind 4 berdasar Flowbite Pro (`template-flowbites/`) per halaman di Tahap 9; CSS hasil bangun di-commit, server tanpa Node; halaman yang belum dipindah tetap Bootstrap 5 (K140, K141, menggantikan K103). Mode terang, ilustrasi 3D lembut. Videotron cair untuk ukuran layar apa pun (K141).
- Codex hanya membuat aset gambar lewat `desain/` dan `AGENTS.md`; kode ditulis Claude, yang juga meninjau dan menggabung PR Codex (K140).
- Staging dan produksi mengambil `main`; setiap PR ke `main` harus aman langsung dipasang di produksi (K139, K140).
- Rencana Tahap 9 dan 8 asumsinya disetujui; tugas dijalankan paralel bila memungkinkan, paling banyak 5 sub-sesi (K142, K145). Koordinator memulai tugas berikutnya dan menggabung PR otomatis sampai Tahap 9 selesai, memakai rekomendasi untuk pertanyaan terbuka, meninjau PR Codex, dan menutup dengan uji lengkap, uji peramban, dan uji asap (K145, K149). F9-05 dikerjakan sebelum pendaftaran tutup, digabung setelah 1 Desember (K149).
- Susunan dokumen agen ringkas: `ATURAN.md`, `PETA-KODE.md`, `SETUP-CLOUD.md`, status hanya di `PROGRES.md` (K120).
- Kesiapan server (`/sehat`, uptime, tembolok, `client_max_body_size`, `memory_limit`, uji pulih) ditangani pemilik (K119).

## Sekolah dan pendaftaran

- Wilayah 5 kabupaten: Blora, Rembang, Pati, Kudus, Jepara (K01). SLB tidak ikut (K28).
- Data sekolah dari Sekolah Kita; menu Sekolah untuk nama pembeda; sekolah disunting tidak ditimpa `sekolah:impor` (K97).
- Usulan nama sekolah dari pendaftar, diputuskan admin (K99). Nama desa sekaligus untuk sekolah bernama sama (K114).
- Ofisial: foto wajib (K50); tampil sebaris di bukti (K52, K108). Istilah "ofisial", "nomor atlet (BIB)", "pertemuan teknik", "mixed" (K113).
- NISN yang sudah terdaftar ditolak; perubahan atlet terdaftar lewat admin (K06).
- Pendaftaran baru masuk hanya bersama bukti transfer, di langkah 5 (K90). Ofisial bisa menambah bukti lewat Cek (K83).
- Kirim pendaftaran idempoten dengan `kunci_kirim` unik (K118 B2). Galat CSRF terlihat, `regenerate = false` (K118 B1).
- Turnstile di wizard, Cek Pendaftaran, dan masuk admin; kunci kosong = mati; kunci rahasia tidak pernah di git (K122). Cek Pendaftaran tidak diperiksa lagi 30 menit setelah lolos (K128).
- CSP menyala, termasuk izin `challenges.cloudflare.com`; tanpa skrip dan gaya sebaris tanpa nonce (K127).
- Wizard: isian salah kembali normal saat diubah (K88); jendela konfirmasi, bilah muat, pemilih tanggal (K89).
- Admin tidak membuat pendaftaran baru dari panel (K58). Ofisial tidak mengubah pendaftaran tersimpan (K32).

## Kelompok umur

- Ditentukan jenjang dulu, lalu tahun lahir; rentang boleh beririsan antar-jenjang (K95, K23, K24). Atlet tidak bisa masuk kelompok umur jenjang lain.
- Dalam satu jenjang rentang boleh beririsan bila sakelar "Pendaftar memilih kelompok umur" menyala (bawaan); atlet di tahun irisan wajib memilih satu kelompok umur di wizard, admin boleh memindahnya di Ubah; mati = irisan ditolak (K134).
- Batas tahun lahir data awal: KU 8 2019+, KU 10 2016–2018, KU 12 2014–2015, KU 15 2011–2014, KU 18 2008–2010 (K36, K112); perubahan dari panitia diisi admin di panel (K134).
- Usia hanya keterangan, dihitung pada tanggal mulai acara (K37). Juknis resmi sudah dicocokkan (K112).

## Nomor lomba, regu, dan biaya

- Frog Jump, Turbo Throw individu (K21); Trilomba satu nomor individu (K22).
- Paling banyak 2 nomor individu per atlet; atlet per nomor per sekolah tidak dibatasi secara bawaan (K53).
- Mixed: hanya jumlah putra dan putri yang diperiksa, pada inti (K09, K105).
- Lebih dari satu regu per nomor; inti tepat, cadangan sampai batas; biaya per regu; susulan memilih regu dan boleh menurunkan inti lama (K105).
- Regu susulan dikonfirmasi ofisial sendiri (K57). Tim tanpa pembayar tidak dipindah otomatis, ditandai (K68).
- Nama regu: Ketentuan admin atau Bebas (K106); bawaan tanpa batas regu, huruf A, B, C…, awalan diatur admin (K107).
- Regu tunggal ditulis tanpa nama regu untuk ofisial (K108).
- Biaya individu per kelompok umur (K02); data awal biaya, jenjang, rekening contoh (K73). Rupiah `Rp 150.000` (K80).

## ID, status, dan nomor atlet

- ID pendaftaran tetap saat diubah (K63).
- Status Lunas dan Menunggu (K90). Lunas diubah dan total berubah → kembali Menunggu (K32); total tetap → atlet baru langsung dapat nomor (K59).
- Nomor atlet dibuat saat Lunas (K11), awalan per kelompok umur, bisa banyak awalan (K05, K33).
- Reset nomor atlet setelah uji coba (K94); urutkan ulang per sekolah sebelum pertemuan teknik (K111).
- Pendaftaran Lunas ber-NPSN sama digabung otomatis, tidak bisa dipisah (K109, K110).
- Tolak atlet per atlet dari pendaftaran Menunggu; NISN bisa didaftarkan lagi; catatan di `atlet_ditolak` (K116).
- Status tampilan "Semua atlet ditolak", bahasa untuk sekolah, menu Atlet ditolak, jejak navigasi (K117).

## Cek pendaftaran dan bukti PDF

- Cek lewat NPSN atau ID; data penuh sesuai MoU kecuali tanggal lahir (K03, K49, K52). Nomor atlet tampil setelah Lunas (K88).
- Alamat bukti `/bukti/<kode acak>/<ID>`, F4 tegak, kode QR (K77). Stempel LUNAS atau MENUNGGU (K78, K91).
- Nomor atlet di bukti setelah Lunas; nama berkas memuat ID dan status; tanpa tembolok (K87).
- Kode QR di pojok kanan bawah halaman terakhir dengan keterangan keaslian (K89). Bukti formal, kepala dan kaki tiap halaman (K108).

## Panel admin

- Satu peran admin; halaman Akun hanya untuk akun sendiri; akun lain lewat `admin:buat` (K26).
- Bukti bayar paling banyak sesuai Pengaturan > Pembayaran (1–200, bawaan 100), dikecilkan otomatis (K34, K83, K135, K136).
- Rekap memuat semua pendaftaran dengan saringan status; keuangan selalu keduanya (K60); Rekap Gabungan (K85).
- Kolom No, pilih massal (100), Backup data dan `pendaftaran:impor` (K76, K77); Backup data tanpa dimuat ke memori (K118 B4).
- Antarmuka tanpa teks teknis (K79). Ubah boleh mempertahankan KU dan nomor yang sudah ditutup (K98).
- Monitoring `arumi.php`, catatan pengunjung, menu Pengecekan, dasbor bergrafik (K102, K104).
- `pembaruan:periksa` setelah `git pull` (K115). Cadangan harian 14 hari (K66).

## Halaman depan

- Pembuka dan kepala mengikuti K81–K86, K92, K93; kartu kelompok umur bergambar (K85, K88).
- Rincian biaya disembunyikan bawaan (K74). Gambar kepala dan logo yang belum diunggah disembunyikan (K41).
- Aset kecil dan penanda versi `?v=` (K72, K86). Juknis jadi unduhan bawaan (K112). Logo Djarum di kaki (K84).

## Tahap 8: hari lomba dan hasil

- Cakupan: riwayat tindakan admin, penanda bukti baru, daftar ulang QR dan hadir, BIB massal, daftar peserta, hasil semua babak, klasemen per jenjang (K124). Rencana dan 8 asumsinya disetujui (K125).
- Juara I–III dari babak Final; regu juara = satu piala (K124).
- Poin multi-lomba (Trilomba): admin mengisi prestasi mentah tiap komponen, web menghitung poin dari tabel skor yang bisa diubah admin (bawaan tabel panitia Trilomba O2SN SMP dan Pancalomba SMA/SMK); di antara dua baris memakai baris yang lebih buruk, di luar tabel 0 poin dengan peringatan; total = jumlah poin komponen (K140 butir 7b, menggantikan K124 butir 3).
- Juara umum per jenjang dengan metode pilihan admin di Pengaturan > Hasil: **Medali** (bawaan, juknis: juara I, lalu II, lalu III) atau **Poin per peringkat** 1–8 (bawaan 7-5-4-3-2-1-0-0; poin, lalu juara I, II, III); masih sama = peringkat bersama; kolom poin selalu tampil; kontingen = NPSN (K124, K140 butir 7a).
- Hasil publik setelah "Terbitkan" per nomor; PDF dan Excel; akun admin yang sama (K124). Hasil dan klasemen punya butir menu admin sendiri (K130). Di ponsel semua butir ada di sidebar yang dibuka tombol menu di kepala halaman; menu bawah 5 butir urusan pendaftaran: Dasbor, Pendaftaran, Atlet, Ditolak, Rekap; butir aktif cukup ikon oranye tanpa garis bawah (K131–K133).
- Tandai hadir di daftar ulang hanya untuk pendaftaran Lunas (K129).
- Cetak BIB per kelompok umur (PDF) dan per sekolah (PDF, ZIP gambar JPG 300 dpi + PDF); ZIP semua sekolah satu folder per sekolah; satu latar penuh untuk semua BIB, kepala dan warna tulisan (gelap/terang) dipilih admin (K137).
- Pecah berkas besar tanpa ubah perilaku setelah F7-26, F7-27, F8-01 (K121).

## Tahap 9 (rombak tampilan dan hari lomba)

- Biaya di FAQ dirinci hanya bila "Tampilkan biaya di halaman depan" menyala; bila mati, FAQ menunjuk langkah Biaya (K74, K143).
- Poin multi-lomba: prestasi di luar tabel = 0 poin dengan peringatan; komponen kosong = 0 poin, atlet tetap masuk peringkat; server lama memindah Trilomba lewat `tabel-skor:impor --trilomba` (K143).
- Halaman depan: sakelar Hasil di atas untuk hari lomba (bawaan mati); foto carousel unggahan 1600 px; menu Kategori dipertahankan (K143).
- Peserta publik: nama, sekolah, nomor atlet, kelompok umur, putra/putri, nomor lomba; hanya Lunas; dibatasi 60 permintaan per menit per IP (K140, K144).
- Seri dan lintasan (urutan pelari kini dalam cakupan): lintasan per seri bawaan 8, lompat/lempar urutan tampil; urutan lintasan unggulan bawaan World Athletics `3,4,5,6;7,8;1,2`; Q = n terbaik tiap seri, q = m prestasi terbaik berikutnya, kosong = otomatis; babak pertama acak berbenih dengan sekolah sama dipisah, babak lanjutan diunggulkan; babak berikut terisi dari Q/q setelah hasil lengkap; publik hanya babak yang dikunci, sakelar bawaan mati (K140 butir 8).
- Aturan nomor (K140 butir 7c): lompat/lempar bawaan 3 percobaan (paling banyak 6), prestasi = jarak sah terjauh; prestasi sama dipisah prestasi terbaik kedua lalu ketiga (sakelar per nomor, bawaan menyala); lari dipisah seperseribu detik bila diisi, prestasi dibulatkan ke atas ke perseratus; pemecah seri hanya bila semua yang sama punya datanya, jadi data lama tidak berubah; ikut menentukan Q/q.
- Lembar juri `admin/juri` (K140 butir 7d, akun admin yang sama): simpan per baris, babak dikunci saat menyimpan, baris yang diubah admin lain sejak dibuka ditolak (juga di halaman hasil); koreksi peringkat Final tetap; peringkat dan Terbitkan di halaman hasil.
- Galeri: album berupa sampul dan tautan luar (Instagram/Google Drive panitia), tautan hanya `https://`, dibuka di tab baru; album tanpa tautan atau disembunyikan tidak tampil; halaman `/galeri` dan menunya bersakelar bawaan mati; album bawaan "Seri sebelumnya" dipasang sekali (K140).
- Papan videotron `/layar`: hanya hasil nomor terbit dan klasemen dari nomor terbit; sakelar tampil bawaan mati; tata letak cair untuk ukuran layar apa pun (K141); 6 nomor terbaru, klasemen sampai peringkat 10 (K146).
- Jadwal pertandingan: status Berlangsung sampai hasil terbit; peringkat sama di klasemen Poin mendapat poin penuh masing-masing; galeri paling banyak 60 album, sampul boleh kosong; Lompat Tinggi tanpa aturan kegagalan, pemecah seri bisa dimatikan per nomor (K146).
