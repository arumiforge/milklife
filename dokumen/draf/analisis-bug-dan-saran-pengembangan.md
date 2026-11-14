# Analisis Bug, Saran Pengembangan, dan Alur Kerja Agen

| | |
|---|---|
| Status | Draf |
| Tanggal | 14 November 2026 |
| Dasar | Pembacaan kode `main` (b77eebc), `composer uji` (1268 uji lulus, 7 menit), `agent/`, `dokumen/` |

## Tujuan

Untuk pemilik dan sesi agen berikutnya: daftar potensi bug di halaman depan (publik) dan panel admin beserta cara memperbaikinya, saran pengembangan setelah Tahap 7, dan usulan susunan dokumen agen supaya setiap sesi membaca lebih sedikit tanpa kehilangan aturan penting.

Kode secara umum rapi: keluaran view di-escape, Excel memakai sel teks eksplisit (aman dari rumus sisipan), tabel admin meng-escape data, unggahan diperiksa dari isi dan disimpan ulang lewat GD, nomor dada dikunci dengan `SELECT … FOR UPDATE`, dan kiriman pendaftaran dikunci per NPSN. Temuan di bawah adalah celah yang tersisa.

## Isi

### 1. Potensi bug

Tingkat: **Tinggi** = bisa membuat data salah atau pengguna gagal tanpa tahu sebabnya; **Sedang** = gagal pada keadaan tertentu (data besar, jaringan buruk); **Rendah** = kenyamanan atau kerapian.

#### B1 — Tinggi — Galat CSRF tidak terlihat; tombol admin "tidak bereaksi"

- Letak: `app/Config/Security.php` (`regenerate = true`, `redirect = true` di produksi), `vendor/.../Filters/CSRF.php` (mengalihkan kembali dengan pesan kilat `error`), `app/Views/admin/_pesan.php` dan `app/Views/tata_letak/_pesan_publik.php` (hanya menampilkan `pesan_sukses` dan `pesan_galat`).
- Skenario 1: admin membuka beberapa rincian pendaftaran di tab berbeda, menekan **Tandai Lunas** di tab pertama (token berganti), lalu menekan **Tandai Lunas** di tab kedua. Di produksi halaman hanya dimuat ulang tanpa pesan apa pun dan status tidak berubah. Di mode pengembangan muncul halaman galat 403.
- Skenario 2: formulir dibiarkan terbuka lebih dari 2 jam (sesi `expiration = 7200`): formulir masuk, pengaturan yang panjang, atau unggah bukti bayar di Cek Pendaftaran. Kiriman ditolak diam-diam dan isian hilang.
- Perbaikan:
  1. `regenerate = false`. Token tetap satu per sesi, perlindungan CSRF tetap berlaku, dan banyak tab tidak saling merusak.
  2. Ganti filter `csrf` dengan filter turunan yang mengalihkan kembali dengan `pesan_galat` berbahasa Indonesia, misalnya "Halaman ini sudah terlalu lama terbuka atau dibuka di tab lain. Muat ulang halaman, lalu ulangi." Isian lama ikut dikembalikan dengan `withInput()`.
  3. Uji fitur: kiriman admin dengan token lama di lingkungan produksi menampilkan pesan itu.

#### B2 — Tinggi — Kirim pendaftaran tidak idempoten

- Letak: `public/aset/js/pendaftaran.js` (`kirimPendaftaran`), `app/Libraries/Pendaftaran/SimpanPendaftaran.php`.
- Skenario: server sudah menyimpan pendaftaran, tetapi jawabannya tidak sampai ke ponsel (sinyal putus, waktu habis). Wizard menampilkan "Belum tersambung ke server". Official menekan **Kirim Pendaftaran** lagi dan mendapat galat "NISN sudah terdaftar" untuk semua atletnya. Official tidak pernah melihat ID pendaftaran atau tautan bukti, lalu mengira pendaftarannya gagal atau mendaftar ulang dengan data yang diubah.
- Perbaikan: wizard membuat `kunci_kirim` acak (disimpan di draf) dan mengirimnya bersama kiriman. Kolom baru `pendaftaran.kunci_kirim` (unik, boleh kosong). Bila kunci yang sama datang lagi, server menjawab 201 dengan ID, rincian, dan tautan bukti pendaftaran yang sudah ada, tanpa menulis apa pun.

#### B3 — Tinggi (perlu keputusan pemilik) — Cek lewat NPSN membuka kode acak bukti

- Letak: `app/Views/cek_pendaftaran/_kartu.php` baris 35 dan 120, `app/Controllers/BuktiController.php`.
- K77 dan K83 menjadikan alamat bukti berkode acak sebagai izin ("ID atau NPSN saja tidak cukup"). Kenyataannya, mencari NPSN di Cek Pendaftaran menampilkan tombol bukti dan formulir unggah yang memuat kode itu. NPSN bersifat publik, dan seluruh NPSN wilayah ada di `data/sekolah/sekolah.json` di repo publik ini.
- Akibat:
  - Siapa pun yang tahu NPSN bisa membuka bukti PDF dan data atlet (NISN, tanggal lahir). Ini sesuai K03, tetapi pembatas 10 pencarian per menit per alamat IP mudah dilewati dengan banyak alamat IP, jadi seluruh data bisa dikumpulkan dari daftar NPSN di repo.
  - Siapa pun bisa mengunggah 5 gambar sembarang (`BuktiBayarModel::BATAS`) ke pendaftaran sekolah lain. Setelah itu official tidak bisa mengunggah bukti aslinya.
- Pilihan perbaikan (pilih salah satu atau gabungan):
  1. Formulir unggah dan tombol bukti hanya tampil bila pencarian memakai ID pendaftaran, bukan NPSN.
  2. Cek lewat NPSN meminta 4 angka terakhir nomor WA salah satu penanggung jawab.
  3. Bukti bayar yang diunggah official tidak dihitung dalam batas 5 sebelum dilihat admin, atau batasnya dihitung per hari.
  4. Cloudflare Turnstile di Cek Pendaftaran dan kirim pendaftaran (lihat S5).

#### B4 — Sedang — Ekspor paket pendaftaran memuat seluruh zip ke memori

- Letak: `app/Controllers/Admin/PendaftaranController::ekspor()` (`file_get_contents` lalu `setBody`).
- Skenario: ratusan pendaftaran, masing-masing dengan foto penanggung jawab dan sampai 5 bukti bayar, menghasilkan zip ratusan MB. Isi zip dibaca utuh ke memori PHP, jadi ekspor gagal dengan galat memori tepat saat cadangan paling dibutuhkan (pindah server).
- Perbaikan: kirim berkas dengan `$this->response->download($jalur, null)->setFileName(...)`. Berkas sementara ditaruh di `writable/cache/ekspor/` dan berkas yang lebih tua dari 1 jam dihapus setiap ekspor baru.

#### B5 — Sedang — Bukti bayar besar bisa menghabiskan memori GD

- Letak: `app/Libraries/Panel/BerkasBuktiBayar.php` (`periksaIsi` menerima sampai 40 juta piksel; `simpanIsi` tidak menaikkan `memory_limit`). `app/Libraries/Unggahan.php` sudah punya `cukupkanMemori()`, tetapi bukti bayar tidak memakainya.
- Skenario: admin atau official di Cek Pendaftaran mengunggah foto kamera 24–40 MP tanpa dikecilkan dulu (wizard aman karena gambar dikecilkan di peramban). GD butuh sekitar 5 bita per piksel (120–200 MB). Dengan `memory_limit` bawaan 128M terjadi galat 500. Panduan produksi tidak menyebut `memory_limit`.
- Perbaikan: panggil logika `cukupkanMemori()` yang sama sebelum `imagecreatefromstring`, dan tulis `memory_limit = 256M` di panduan pemasangan produksi.

#### B6 — Sedang — Batas laju per alamat IP dan CGNAT seluler

- Letak: `PendaftaranController` (simpan 10 kali per 10 menit), `CekPendaftaranController` dan `BuktiController` (10 kali per menit).
- Operator seluler Indonesia umumnya memakai CGNAT: banyak ponsel keluar lewat satu alamat IP publik. Saat technical meeting atau menjelang tutup, official yang berbeda bisa mendapat "Terlalu banyak permintaan".
- Perbaikan: kunci pembatas memakai alamat IP ditambah ID sesi (batas per sesi ketat, batas per alamat IP longgar, misalnya 5–10 kali lipat). Catat jawaban 429 di log supaya terlihat bila sering terjadi.

#### B7 — Sedang — Tanpa batas jumlah atlet per kiriman dan tanpa penangkal bot

- Letak: `PemeriksaPendaftaran` (tidak ada batas jumlah atlet), `SimpanPendaftaran`.
- Satu kiriman JSON sampai 12 MB bisa memuat ribuan atlet. Semuanya diperiksa dan ditulis dalam satu transaksi sambil memegang kunci NPSN. Kiriman sekolah tanpa NPSN juga tidak terkena kuota per nomor.
- Perbaikan: pengaturan admin "atlet paling banyak per pendaftaran" (misalnya 150), diperiksa di server; pertimbangkan Turnstile (S5).

#### B8 — Rendah — Sekolah tanpa NPSN lolos dari kuota dan aturan tim

- Letak: `SimpanPendaftaran::simpan()` (kunci `null`), `DataIndukPendaftaran::ada()` (dihitung per NPSN).
- Dua pendaftaran tanpa NPSN dari sekolah yang sama dihitung sebagai dua sekolah. Bila memang disengaja, catat sebagai keputusan. Bila tidak, admin perlu peringatan di rincian ("nama sekolah mirip pendaftaran lain tanpa NPSN").

#### B9 — Rendah — Tanpa masa tenggang saat pendaftaran ditutup

Official yang menekan **Kirim** pada 23.59.30 setelah mengisi wizard selama setengah jam langsung mendapat 409 begitu jam tutup lewat. Pertimbangkan tenggang 10–15 menit untuk sesi yang membuka wizard sebelum tutup, dengan waktu buka wizard disimpan di sesi.

#### B10 — Rendah — Dokumen agen tidak sinkron

- `agent/PROGRES.md`: F7-03 berstatus "dikerjakan", tetapi `agent/tugas/F7-03-tim-tanpa-pembayar.md` berstatus "menunggu tinjauan".
- `agent/KONTEKS.md`: rancangan disebut "revisi 1 dan 2", padahal sudah revisi 5 (K96).
- Sebabnya, status dan riwayat ditulis di beberapa tempat sekaligus. Lihat bagian 3.

### 2. Saran pengembangan

Urut menurut manfaat untuk acara Desember 2026.

| No | Saran | Alasan |
|---|---|---|
| S1 | Perbaiki B1–B5 dan masukkan skenarionya ke F7-06 (uji menyeluruh) | B1 dan B2 paling mungkin terjadi di hari ramai |
| S2 | Riwayat tindakan admin: tabel `riwayat` (admin, tindakan, pendaftaran, waktu, ringkasan perubahan) untuk Lunas, Menunggu, Ubah, Hapus, dan hapus bukti; tampil di rincian | Ada beberapa akun admin; sengketa pembayaran perlu jejak |
| S3 | Penanda "bukti baru belum diperiksa" di daftar pendaftaran, dengan saringan dan jumlahnya di dasbor | Sejak K83, bukti masuk lewat sistem; admin perlu tahu mana yang baru |
| S4 | Tembolok halaman depan (60 detik, dibersihkan setiap pengaturan disimpan) | Tombol "Bagikan ke WA" bisa memicu lonjakan kunjungan ke satu komputer Laragon |
| S5 | Cloudflare Turnstile di kirim pendaftaran dan Cek Pendaftaran | Menangkal bot dan pengumpulan data (B3, B7) tanpa pustaka PHP tambahan, cukup satu panggilan HTTP |
| S6 | Pemantauan: alamat `/sehat` (basis data dan folder unggahan bisa ditulis), dipantau layanan uptime gratis; cadangan terjadwal F7-05 beserta uji pulih | Server tunggal di komputer pemilik |
| S7 | Hari H: pindai kode QR bukti untuk daftar ulang (status Lunas, nomor dada, daftar atlet), cetak nomor dada massal per kelompok umur | QR dan nomor dada sudah ada; tinggal dimanfaatkan |
| S8 | Seri berikutnya: data per seri (`seri_id`) dan perintah `spark seri:baru`, bukan mengosongkan tabel | Arsip seri 2 tetap bisa dibuka saat seri 3 berjalan |
| S9 | Pecah berkas terbesar: `UbahPendaftaran` (1044 baris), `PemeriksaPendaftaran` (736), `pendaftaran.js` (1860) per langkah atau per aturan | Lebih mudah ditinjau dan lebih hemat dibaca agen |
| S10 | Percepat uji: cari uji fitur yang lambat (data awal dibuat ulang per uji), siapkan data sekali per kelas; tambah `composer uji:unit` | 7 menit per putaran memperlambat setiap PR |
| S11 | Analisis statis (PHPStan tingkat 5) di CI, sebagai pustaka pengembangan saja | Menangkap salah tipe sebelum uji; perlu keputusan pemilik karena menambah pustaka |

### 3. Alur kerja dan dokumen untuk agen

#### Keadaan sekarang

Urutan "Mulai sesi" membaca kira-kira 60–80 KB (18–25 ribu token) sebelum menyentuh kode:

| Berkas | Ukuran | Masalah |
|---|---|---|
| `CLAUDE.md` → `agent/ALUR-KERJA.md` | 0,5 + 6,6 KB | 1,8 KB berisi skrip setup cloud yang jarang dibutuhkan |
| `agent/KONTEKS.md` | 3 KB | Daftar 7 rencana tahap (sebagian besar sudah selesai); info revisi usang |
| `agent/PROGRES.md` | 2,4 KB | Status ganda dengan berkas tugas |
| Berkas tugas | 4–11 KB | "Hasil pengerjaan" panjang mengulang deskripsi PR |
| `agent/KEPUTUSAN.md` | **41 KB** | Log 96 keputusan; keputusan yang sudah digantikan tetap harus dibaca untuk tahu mana yang berlaku |
| Rencana tahap (`dokumen/draf/`) | 11–22 KB per berkas | Sering dibaca utuh untuk satu bagian |

Selain itu belum ada peta kode, jadi setiap sesi menghabiskan token untuk `grep` dan membaca berkas besar demi mencari tempat aturan.

#### Susunan yang diusulkan

| Berkas | Isi | Kapan dibaca | Target ukuran |
|---|---|---|---|
| `CLAUDE.md` | Aturan wajib (bahasa, git, rahasia, uji) dan tabel "baca berkas X bila mengerjakan Y" | Selalu, otomatis | ≤ 3 KB |
| `agent/PROGRES.md` | Hanya tahap berjalan; **satu-satunya** tempat status tugas | Awal sesi | ≤ 2 KB |
| `agent/ATURAN.md` (baru) | Ringkasan keputusan yang **masih berlaku**, dikelompokkan per domain (pendaftaran, biaya, kelompok umur, nomor dada, bukti dan cek, admin, tampilan, proses), satu baris per aturan dengan nomor K | Saat menyentuh aturan domain | ≤ 8 KB |
| `agent/PETA-KODE.md` (baru) | Fitur → rute → pengendali → pustaka → view → JS → uji; invarian kritis (kunci NPSN, transaksi nomor dada, berkas di luar `public/`); perintah uji per area | Sebelum mengubah kode | ≤ 6 KB |
| `agent/KEPUTUSAN.md` | Tetap log lengkap, hanya ditambah | Hanya dicari (`grep K87`) bila butuh alasan | — |
| `agent/SETUP-CLOUD.md` (baru) | Skrip setup dari ALUR-KERJA | Hanya bila lingkungan belum siap | — |
| `agent/ALUR-KERJA.md` | Prosedur lengkap (tugas, dokumen, PR) | Hanya saat membuat tugas, menutup tahap, atau memindahkan dokumen | ≤ 4 KB |
| Berkas tugas | Tujuan, hasil (centang), **berkas yang disentuh**, **uji terkait**, keputusan terkait (nomor K saja) | Tugas yang dikerjakan | ≤ 3 KB |

Perkiraan bacaan awal sesi turun dari 60–80 KB menjadi 10–15 KB (3–5 ribu token).

#### Aturan tambahan supaya tetap ringkas

1. **Satu fakta, satu tempat.** Status hanya di `PROGRES.md`; aturan berlaku hanya di `ATURAN.md`; alasan hanya di `KEPUTUSAN.md`; riwayat pengerjaan di deskripsi PR, bukan di berkas tugas.
2. **Setiap keputusan baru** ditulis di `KEPUTUSAN.md` dan, bila mengubah aturan, baris di `ATURAN.md` diganti (bukan ditambah) dalam PR yang sama.
3. **Arsipkan yang selesai.** Berkas tugas tahap selesai pindah ke `agent/arsip/tugas/`; rencana tahap selesai pindah ke `dokumen/arsip/`. Folder aktif hanya berisi yang sedang berjalan.
4. **Komentar kode tanpa riwayat.** Docblock menjelaskan perilaku sekarang dan nomor K-nya; kalimat seperti "sebelumnya A4 mendatar" dihapus karena riwayat ada di git dan KEPUTUSAN.
5. **Uji terarah.** Selama bekerja: `vendor/bin/phpunit --filter <NamaUji>` atau `--testsuite unit`. Seluruh uji (`composer uji`) sekali sebelum PR; CI mengulanginya.
6. **Uji penjaga dokumen.** Satu uji unit kecil memeriksa bahwa setiap tugas di tabel `PROGRES.md` punya berkas di `agent/tugas/` dan setiap nomor K di `ATURAN.md` ada di `KEPUTUSAN.md`, supaya B10 tidak terulang.

#### Bantuan Claude Code (opsional)

- **Hook SessionStart** (`.claude/settings.json`): menampilkan bagian "Sedang berjalan" dari `PROGRES.md` dan `git status` singkat (sekitar 300 token), menggantikan langkah membaca beberapa berkas.
- **Perintah atau skill** di `.claude/commands/`: `tugas-baru`, `catat-keputusan`, `tutup-tahap`. Prosedur panjang hanya dimuat saat perintahnya dipanggil, jadi tidak membebani setiap sesi.
- **Subagen peninjau** (`.claude/agents/peninjau.md`): memeriksa diff terhadap `ATURAN.md` dan aturan bahasa sebelum PR, dengan konteksnya sendiri.

#### Contoh isi `CLAUDE.md` baru

```markdown
# Petunjuk untuk agen

Web pendaftaran MilkLife Athletics Challenge (CodeIgniter 4.7, PHP 8.2+, MySQL 8).

## Wajib
- Semua tulisan berbahasa Indonesia: dokumen, antarmuka, URL, commit, judul PR.
- Repo publik: data siswa, foto, cadangan, dan `.env` tidak pernah masuk git.
- Commit atas nama Arumi Studios saja, tanpa atribusi alat AI.
- Keputusan pemilik dicatat di `agent/KEPUTUSAN.md` dan `agent/ATURAN.md` hari itu juga.
- Berkas di `sumber/` dan `dokumen/final/` tidak diubah langsung.
- Sebelum PR: `composer uji` lulus.

## Baca bila perlu
| Mengerjakan | Baca |
|---|---|
| Apa pun | `agent/PROGRES.md` |
| Aturan domain (biaya, KU, nomor dada, cek) | `agent/ATURAN.md` |
| Mengubah kode | `agent/PETA-KODE.md`, berkas tugas di `agent/tugas/` |
| Membuat tugas, dokumen, menutup tahap | `agent/ALUR-KERJA.md` |
| Lingkungan cloud belum siap | `agent/SETUP-CLOUD.md` |
| Alasan sebuah keputusan | `grep K<nomor> agent/KEPUTUSAN.md` |
```

## Asumsi yang perlu ditinjau

1. **B3** menyentuh K03, K77, dan K83. Pilihan perbaikannya perlu keputusan pemilik sebelum dikerjakan.
2. **B8** (sekolah tanpa NPSN) dan **B9** (masa tenggang) mungkin memang disengaja; perlu dikonfirmasi.
3. **S11** dan Turnstile (**S5**) menambah ketergantungan; perlu persetujuan sesuai aturan "tidak menambah pustaka".
4. Ukuran dan perkiraan token di bagian 3 dihitung dari ukuran berkas, bukan diukur dari sesi sungguhan.
5. Susunan dokumen baru mengubah `agent/ALUR-KERJA.md` dan `CLAUDE.md`; perlu persetujuan pemilik sebelum diterapkan.
