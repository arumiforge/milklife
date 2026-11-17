# Aturan yang Berlaku

Ringkasan keputusan pemilik yang **masih berlaku**, satu baris per aturan dengan nomor K. Alasan dan riwayatnya di `agent/KEPUTUSAN.md` (`grep K<nomor>`). Bila keputusan baru mengubah aturan, baris di sini **diganti** dalam PR yang sama, bukan ditambah. Rincian perilaku ada di `dokumen/final/rancangan-web-pendaftaran.md`.

## Proses dan repo

- Satu-satunya penulis commit Arumi Studios, tanpa atribusi alat AI di commit, PR, kode, dan dokumen (K13). Cabang utama `main` (K14).
- Repo publik, lisensi proprietary (K29). Data siswa, foto, cadangan, `.env`, dan rahasia tidak masuk git.
- CI GitHub menjalankan `composer uji` di setiap PR dan push ke `main`; uji lokal tetap sebelum PR (K46).
- Sesi cloud disiapkan Setup script; agen tidak memasang ulang (K62). Isi skrip: `agent/SETUP-CLOUD.md`.
- Koordinator membuat sub-sesi sendiri, paling banyak 3 sekaligus (K38, K65), dan menggabung PR (*squash*) setelah CI hijau; sub-sesi tidak menggabung PR sendiri (K126).
- Dokumen boleh diubah langsung sesuai kondisi terbaru, PDF dibuat ulang (K100, K101).
- Laragon untuk pengembangan dan produksi, PHP 8.2+ (K08, K18); web lewat Cloudflare Tunnel, IP dari `CF-Connecting-IP` (K31).
- Tidak menambah pustaka kecuali yang disetujui: Turnstile tanpa pustaka PHP (K122), PHPStan `require-dev` (K123).
- Panel tetap Bootstrap 5, bukan Tailwind (K103).
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
- Turnstile di wizard, Cek Pendaftaran, dan masuk admin; kunci kosong = mati; kunci rahasia tidak pernah di git (K122).
- Wizard: isian salah kembali normal saat diubah (K88); jendela konfirmasi, bilah muat, pemilih tanggal (K89).
- Admin tidak membuat pendaftaran baru dari panel (K58). Ofisial tidak mengubah pendaftaran tersimpan (K32).

## Kelompok umur

- Ditentukan jenjang dulu, lalu tahun lahir; rentang boleh beririsan antar-jenjang, tidak dalam satu jenjang (K95, K23, K24).
- Batas tahun lahir data awal: KU 8 2019+, KU 10 2016–2018, KU 12 2014–2015, KU 15 2011–2014, KU 18 2008–2010 (K36, K112).
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
- Bukti bayar paling banyak 5 gambar, dikecilkan otomatis (K34, K83).
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
- Juara I–III dari babak Final; Tri Lomba poin akhir diisi admin; regu juara = satu piala (K124).
- Juara umum: juara I, lalu II, lalu III, lalu peringkat bersama; kontingen = NPSN (K124).
- Hasil publik setelah "Terbitkan" per nomor; PDF dan Excel; akun admin yang sama (K124).
- Pecah berkas besar tanpa ubah perilaku setelah F7-26, F7-27, F8-01 (K121).
