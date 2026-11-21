# Rencana Implementasi Tahap 9 — Rombak Tampilan dan Fitur Publik Hari Lomba

| | |
|---|---|
| Status | Draf, **disetujui pemilik 21 November 2026 beserta delapan asumsinya (K142)** |
| Tanggal | 21 November 2026 |
| Dasar | Wawancara 21 November 2026 (K138–K141); juknis `sumber/juknis/petunjuk-teknis-seri2-2026.pdf` bagian Tri Lomba dan 11; tabel skor `sumber/tabel-skor/`; foto dan contoh desain `bahan-rombak/gambar-foto/`; rujukan UI `template-flowbites/` |

## Tujuan

Untuk pelaksana tiap tugas Tahap 9 (sesi Claude dan Codex) dan pemilik yang meninjau. Setelah membaca, pelaksana tahu isi tugasnya, urutannya, berkas yang boleh disentuh, dan aturan yang berlaku. Setiap tugas dirancang muat dalam **satu sesi** supaya konteks tidak terlalu panjang.

Batas waktu: **pendaftaran tutup 30 November 23.59**, **pertemuan teknik 6 Desember** (daftar seri dan lintasan dibagikan), **lomba 9–12 Desember**. Semua fitur naik ke produksi **bertahap begitu diterima di staging** (K140 butir 1).

## Hasil Tahap 9

1. **Tampilan baru** berdasar **Flowbite Pro** (Tailwind 4), mode terang, inspirasi railway.com, ikon Lucide, sentuhan Three.js, ilustrasi 3D lembut dari Codex, skeleton saat memuat. Berlaku untuk halaman publik dan panel admin.
2. **Halaman depan baru**: pembuka dengan **carousel foto acara sebelumnya beserta indikator**, bagian yang lebih tertata, dan menu baru: Jadwal, Hasil, Peserta, Galeri, Bantuan.
3. **Bantuan dan FAQ** untuk ofisial dan guru: tanya-jawab per kategori yang bisa dicari, tombol bantuan di tiap langkah wizard, dan panduan bergambar.
4. **Hari lomba**: jadwal pertandingan dari babak, seri dan lintasan dibagi otomatis, alur babak ("bagan"), aturan nomor yang bisa diatur, lembar juri, kalkulator poin Trilomba/Pancalomba, metode klasemen medali atau poin.
5. **Halaman publik**: jadwal, hasil, klasemen, peserta, galeri (tautan luar), dan **papan videotron** layar penuh.
6. **Saluran Codex**: `AGENTS.md` dan `desain/` sehingga Codex bisa membuat gambar dari tugas tertulis lalu membuka PR.

## Prioritas

Pemilik memilih semua sebelum lomba. Bila waktu tidak cukup, tugas **P2** yang bergeser, bukan P1. Penggeseran diajukan ke pemilik, tidak diputuskan sesi pelaksana.

- **P1 (dipakai saat pendaftaran atau hari lomba)**: F9-01, F9-02, F9-03, F9-04, F9-07, F9-08, F9-09, F9-10, F9-12, F9-13.
- **P2 (tampilan dan pelengkap)**: F9-05, F9-06, F9-11, F9-14, F9-15, F9-16, F9-17, F9-19.

## Tugas dan urutan

| Tugas | Isi singkat | Bergantung pada | Gelombang | Target |
|---|---|---|---|---|
| F9-01 Fondasi Flowbite Pro | Rantai bangun CSS templat Pro, token warna, CSS hasil bangun, JS Flowbite, Lucide, Three.js, komponen dari templat Pro, halaman katalog komponen, aturan CSP | — | 0 | 23 Nov |
| F9-02 Saluran Codex | `AGENTS.md`, `desain/`, brief gambar G9-01 sampai G9-07 | — (masuk bersama rencana ini) | 0 | 21 Nov |
| F9-03 Tata letak publik dan halaman depan | Kepala, menu baru, carousel dengan indikator, bagian baru, kaki; unduhan; foto bawaan | F9-01 | A | 26 Nov |
| F9-04 Bantuan dan FAQ | Tabel `faq`, kelola admin, data bawaan 40–60 tanya-jawab, `/bantuan`, tombol bantuan di wizard, panduan bergambar | F9-01 | A | 26 Nov |
| F9-07 Jadwal pertandingan | Babak diberi tanggal dan jam; `/jadwal` publik per hari dengan status dan tautan | F9-01 | A | 28 Nov |
| F9-10 Kalkulator poin multi-lomba | Tabel skor dari PDF panitia ke data, pengaturan tabel skor, isian prestasi per komponen, poin otomatis | — | A | 29 Nov |
| F9-08 Seri, lintasan, dan alur babak | Pembagian otomatis, geser manual, aturan lolos, daftar seri PDF untuk pertemuan teknik, bagan publik | F9-07 | B | 4 Des |
| F9-09 Aturan nomor dan lembar juri | Percobaan lompat/lempar, pemecah seri, lembar juri ponsel | F9-08 (berkas hasil sama) | B | 5 Des |
| F9-12 Peserta publik | `/peserta`: cari nama, sekolah, nomor lomba; hanya Lunas | F9-03 | B | 1 Des |
| F9-13 Papan videotron | `/layar`: layar penuh, putar otomatis, segar berkala, cair untuk ukuran layar apa pun | F9-01, F9-10 | B | 6 Des |
| F9-11 Metode klasemen | Pengaturan Medali atau Poin per peringkat, kolom poin | F9-10 | B | 3 Des |
| F9-06 Galeri tautan luar | Album bersampul dan bertautan, kelola admin, `/galeri` | F9-03 | B | 3 Des |
| F9-05 Wizard, cek, dan bukti ke Tailwind | Wizard 5 langkah, Cek Pendaftaran, unggah bukti | F9-03, F9-04, **setelah pendaftaran tutup** | C | 3 Des |
| F9-14 Tata letak admin dan dasbor | Tata letak, sidebar dengan kelompok menu baru, menu ponsel (K131–K133), dasbor, DataTables gaya Tailwind | F9-01 | C | 3 Des |
| F9-15 Halaman admin pendaftaran | Pendaftaran, rincian, ubah, tolak atlet, atlet, atlet ditolak, sekolah | F9-14 | C | 5 Des |
| F9-16 Halaman admin hari lomba dan pengaturan | Hari lomba, hasil, klasemen, rekap, pengaturan, akun, masuk | F9-14, F9-08, F9-09 | C | 6 Des |
| F9-17 Skeleton, keadaan kosong, dan perapian | Skeleton di bagian yang dimuat JS, ilustrasi kosong/galat, tinjau ulang semua tombol, aksesibilitas | Semua halaman sudah dipindah | C | 7 Des |
| F9-19 Pemasangan visual tambahan | Gambar G9-08 sampai G9-12 (K150): panel admin, pita judul, `og:image`, ikon aplikasi, hiasan; aman bila gambar belum ada | F9-14, F9-16 | C | 5 Des |
| F9-18 Penutupan Tahap 9 | Uji peramban menyeluruh di staging, rancangan final revisi, PETA-KODE, arsip | Semua | Akhir | 8 Des |

Tugas Codex (gambar) berjalan **sejajar** sejak rencana ini masuk `main`; urutannya di `desain/README.md`. Tugas Claude yang memakai gambar tetap bisa selesai sebelum gambarnya ada: gambar tersebut ditampilkan **hanya bila berkasnya ada** (`is_file()`), jadi tidak pernah muncul kotak kosong (K41).

### Sesi dan paralel

Paling banyak 3 sesi Claude berjalan bersamaan (K38, K65); sesi Codex tidak dihitung. Saran pembagian sesi:

| Sesi | Isi | Kapan |
|---|---|---|
| 1 | F9-01 | Begitu rencana disetujui |
| 2a, 2b, 2c | F9-03; F9-04; F9-07 lalu F9-10 | Setelah F9-01 masuk `main` |
| 3a, 3b, 3c | F9-08 lalu F9-09; F9-12 lalu F9-06; F9-11 lalu F9-13 | Setelah gelombang A |
| 4a, 4b, 4c | F9-05; F9-14 lalu F9-15; F9-16 | 1 Desember (setelah pendaftaran tutup) |
| 5 | F9-17, lalu F9-18 | Setelah gelombang C |

Tiap sesi membaca `agent/PROGRES.md`, rencana ini, lalu berkas tugasnya saja.

## Aturan untuk semua sesi

1. **Aman untuk produksi.** Staging dan produksi sama-sama mengambil `main` (K139, K140). Setiap PR harus bisa langsung dipasang: halaman dipindah ke Tailwind **utuh dalam satu PR**; fitur baru yang belum siap dipakai disembunyikan dengan sakelar di Pengaturan (bawaan **mati**) sampai pemilik mencobanya di staging. Migrasi basis data hanya **menambah** tabel atau kolom; kolom lama tidak dihapus di Tahap 9.
2. **`main` sumber kebenaran.** `git fetch origin main && git rebase origin/main` di awal dan sebelum PR; `composer uji` dan `composer analisis` lulus. Satu tugas, satu PR, berbahasa Indonesia. Sesi pelaksana tidak menggabung PR; koordinator menggabungnya setelah CI hijau (K126).
3. **CSP tetap menyala** (K127): tanpa `style="…"`, atribut `on…`, atau skrip/gaya sebaris tanpa nonce. Kelas Tailwind dengan nilai bebas (`w-[37px]`) boleh karena dibangun ke berkas CSS. Three.js dan JS Flowbite dimuat dari berkas lokal; komponen Flowbite yang menyuntik gaya sebaris diperiksa terhadap CSP dan diganti bila melanggar; `KepalaKeamananTest` tetap menjaga.
4. **CSS** dibangun dengan `npm run css` (rantai bangun templat Flowbite Pro, F9-01) dan hasilnya di-commit. CI memeriksa bahwa hasil bangun sama dengan sumbernya. Jangan menyunting CSS hasil bangun dengan tangan. Server produksi tidak menjalankan Node.
5. **Bahasa antarmuka** untuk orang non-teknis: tanpa istilah teknis, tombol menyebut tindakannya ("Daftarkan sekolah", bukan "Kirim"), pesan galat menyebut cara memperbaikinya, dan setiap layar punya jalan ke Bantuan.
6. **Ponsel lebih dulu.** Diperiksa di lebar 360, 390, dan 1280 piksel. Target sentuh paling kecil 44 piksel. Kontras teks memenuhi WCAG AA. Animasi Three.js dan carousel menghormati `prefers-reduced-motion`.
7. **Kepemilikan berkas**: berkas bersama (`app/Config/Routes.php`, menu publik dan admin, `agent/PROGRES.md`, `app/Commands/DataAwal.php`) hanya ditambah di blok sendiri, seminimal mungkin. Sumber Tailwind bersama (`public/aset/tailwind/masukan.css`) hanya diubah F9-01 dan F9-17; tugas lain cukup memakai kelas.
8. **Tabel baru** ikut Backup data/impor (K76), cadangan, penggabungan (K109) bila menunjuk pendaftaran, dan hapus pendaftaran.
9. **Keputusan**: sesi pelaksana tidak menambah K; pertanyaan untuk pemilik ditulis di PR dan dicatat koordinator. Aturan yang berubah karena tugas itu (misalnya K124 butir 3 oleh F9-10) **diganti barisnya di `agent/ATURAN.md`** dan rancangan final di PR tugas itu.
10. **Atribusi dan data**: commit atas nama Arumi Studios tanpa atribusi alat AI; data uji karangan; `sumber/` tidak diubah.

## Kesepakatan teknis

### Fondasi Flowbite Pro (F9-01)

- **Dasar: Flowbite Pro** di `template-flowbites/` (lisensi pengembang, K138, K141). Landing Pages v1.1.0 untuk halaman publik, Admin Dashboard v2.2.0 untuk panel. Potongan HTML Pro disalin ke view CodeIgniter dan disesuaikan; templat Hugo-nya tidak dipakai.
- **Rantai bangun sama dengan templat Pro**: `package.json` di akar repo dengan `tailwindcss` 4, `@tailwindcss/cli`, `flowbite`, `flowbite-typography`, dan `flowbite-datepicker`, versinya mengikuti `package.json` templat Pro dan dikunci `package-lock.json`. `public/aset/tailwind/masukan.css` mengikuti `src/app.css` templat Pro (`@plugin "flowbite/plugin"`, tema Flowbite, `@source` ke `app/Views/` dan `public/aset/js/`). `npm run css` membangun `public/aset/css/tw.css` (di-commit); `npm run css:pantau` untuk pengembangan. `node_modules/` tidak masuk git. `aset:minify` tetap memperkecil hasilnya di produksi.
- **Token** di `@theme`: warna `primary` templat Pro diganti palet MilkLife (`--ml-*` di `tema.css`: biru `#234987`, biru tua `#021F7B`, oranye `#E85F31`, oranye tombol `#C4461B`, biru muda `#9BCCEC`, latar `#F3F6FB`) ditambah magenta flyer `#F0005A`; huruf mengikuti templat Pro bila lisensinya bebas, disimpan lokal; radius, bayangan lembut, gradien halus.
- **JS Flowbite** (`flowbite.min.js` dan `datepicker.min.js` dari paket yang dipasang templat Pro) disalin `npm run vendor` ke `public/aset/vendor/flowbite/` dan di-commit: carousel, laci, modal, dropdown, tab, tooltip, pemilih tanggal (atribut `data-*`).
- **Lucide**: sprite SVG lokal `public/aset/vendor/lucide/lucide.svg` dan pembantu `ikon('nama', 'kelas')` yang menulis `<svg><use href="…#nama"/></svg>`. Ikon SVG sebaris di potongan Pro diganti Lucide supaya seragam. Bootstrap Icons tetap dimuat sampai halaman terakhir dipindah (F9-17 melepasnya).
- **Three.js**: modul ES lokal `public/aset/vendor/three/three.module.min.js`, dimuat lewat `<script type="module">` dari berkas di `public/aset/js/`; adegan dimatikan bila `prefers-reduced-motion` atau WebGL tidak ada, dan diganti gambar diam.
- **Komponen dasar** dari templat Pro sebagai potongan view di `app/Views/komponen/` (tombol, kartu, isian, pilihan, sakelar, lencana, peringatan, modal, tabel, skeleton, keadaan kosong, langkah) dengan uji tampilan. Halaman **Katalog komponen** di `admin/gaya` supaya pemilik bisa menilai gaya di staging sebelum halaman lain dipindah.
- **Tata letak baru** `tata_letak/publik_tw.php` (dari Landing Pages Pro) dan `tata_letak/admin_tw.php` (dari Admin Dashboard Pro) berdampingan dengan yang lama; halaman berpindah dengan mengganti `extend`. Bootstrap tidak dimuat di tata letak baru.
- `public/aset/vendor/README.md` mencatat paket, versi, lisensi, dan asal; `public/aset/vendor/flowbite/flowbite-dasbor.css` lama (K102) diganti hasil bangun baru setelah `arumi.php` dipindah.

### Halaman depan (F9-03)

- Pembuka: carousel dengan indikator dan tombol jeda (Flowbite), foto bawaan dari `bahan-rombak/gambar-foto/maclalu*.webp` disalin dan dikecilkan ke `data/halaman-depan/carousel/` lalu dipasang `data:awal` (pemasangan baru) dan perintah `halaman-depan:carousel` (pemasangan lama); admin bisa menambah, mengurutkan, dan menghapus foto di Pengaturan > Halaman Depan. Foto pertama dimuat lebih dulu, sisanya `loading="lazy"`; WebP sisi terpanjang 1600 piksel.
- Judul, keadaan pendaftaran, dan dua tombol besar (Daftar, Cek Pendaftaran) tetap di layar pertama (K81).
- Bagian: Tentang acara, Kategori dan biaya (kartu kelompok umur), Jadwal ringkas, angka acara (seperti `kompetisi1.jpg`: atlet, sekolah, per kelompok umur, setelah ada data Lunas), Hasil dan juara umum terbaru (setelah ada yang terbit), FAQ teratas, Unduhan, Kontak. Urutan K42 dipertahankan untuk bagian lama.
- Menu publik: Beranda, Jadwal, Hasil, Peserta, Galeri, Bantuan, tombol Cek Pendaftaran dan Daftar; butir yang halamannya belum terbuka (sakelar mati) tidak tampil.

### Bantuan dan FAQ (F9-04)

- Tabel `faq` (`kategori`, `pertanyaan`, `jawaban` Markdown sederhana, `urutan`, `aktif`); kelola di Pengaturan > Bantuan. Kategori: Sebelum mendaftar, Mengisi pendaftaran, Pembayaran dan bukti, Cek pendaftaran dan bukti PDF, Hari lomba, Hasil dan juara. Data bawaan **40–60** tanya-jawab disusun dari rancangan final, `agent/ATURAN.md`, dan juknis, termasuk pertanyaan sederhana ("Apakah harus punya akun?", "Foto ofisial dari HP boleh?", "Salah ketik nama atlet, bagaimana?").
- `/bantuan`: cari langsung (tanpa muat ulang), kategori, tautan WA panitia di bawah. Wizard: tombol "?" di tiap langkah membuka laci bantuan berisi FAQ kategori langkah itu. Panduan bergambar langkah pendaftaran memakai gambar G9-03 bila ada.

### Jadwal pertandingan (F9-07)

- Kolom baru di `babak`: `tanggal`, `jam_mulai`, `lokasi` (boleh kosong). Diisi di halaman hasil admin per babak, atau sekaligus di halaman **Jadwal pertandingan** admin (tabel semua babak, bisa diisi berurutan).
- `/jadwal`: tab per hari, kolom seperti `contohjadwal1.jpg` (waktu, nomor, putra/putri, kelompok umur, babak), status otomatis (Belum mulai, Berlangsung, Selesai = sudah ada hasil terbit), tautan ke daftar seri (F9-08) dan hasil. Jadwal pendaftaran lama (`jadwal`) tetap ada sebagai "Agenda".

### Seri, lintasan, dan alur babak (F9-08)

- Pengaturan per nomor: jumlah lintasan per seri (bawaan 8; lompat/lempar = urutan tampil tanpa lintasan), aturan lolos ke babak berikut (**Q** = n terbaik tiap seri, **q** = m waktu terbaik berikutnya), urutan lintasan unggulan (bawaan sesuai urutan lintasan World Athletics untuk 8 lintasan).
- Pembagian otomatis: atlet/regu Lunas (dan hadir bila daftar ulang dipakai) dibagi rata ke seri, **sekolah sama dipisah sebisanya**, urutan acak dengan benih tersimpan supaya bisa diulang; admin bisa menggeser atlet antarseri/lintasan sebelum babak dikunci. Kelas murni dengan uji unit.
- Babak berikut terisi otomatis dari Q/q setelah hasil babak sebelumnya lengkap; admin tetap bisa mengoreksi.
- Unduhan **daftar seri** PDF/Excel per nomor dan per hari untuk pertemuan teknik dan juri. Publik: `/jadwal/<babak>` menampilkan seri dan lintasan; **bagan** per nomor menampilkan alur Penyisihan seri 1…n → (Semifinal) → Final dengan status dan siapa yang lolos.

### Aturan nomor dan lembar juri (F9-09)

- Per nomor lompat/lempar: jumlah percobaan (bawaan 3), prestasi terbaik dipakai, pemecah seri prestasi terbaik kedua (bisa dimatikan). Lari: pemecah seri seperseribu bila diisi, selain itu bersama.
- **Lembar juri** `admin/juri`: pilih babak → seri → baris besar per atlet (BIB, nama, lintasan), isian angka dengan papan angka ponsel, simpan otomatis per baris, tanda tersimpan, bisa dipakai beberapa admin bersamaan di babak berbeda (kunci baris per babak). Memakai akun admin yang sama (K140 7d).

### Kalkulator poin multi-lomba (F9-10)

- Data: tabel di `sumber/tabel-skor/` diubah menjadi `data/tabel-skor/<nama>.json` (prestasi → poin per komponen dan jenis kelamin) oleh alat di `data/alat/` dengan uji kecocokan sampel. Tabel `tabel_skor` dan `tabel_skor_baris` diisi `data:awal`/perintah `tabel-skor:impor`.
- Nomor lomba berjenis hasil **Poin multi-lomba** memilih tabel skor dan komponennya (Trilomba: Lari 60 m, Lompat Jauh, Tolak Peluru). Admin/juri mengisi prestasi mentah tiap komponen; poin dicari di tabel (prestasi di antara dua baris memakai baris yang lebih buruk, sesuai kebiasaan tabel). Total = jumlah poin; peringkat dari total; pemecah seri diatur.
- Admin bisa mengubah baris tabel atau mengimpor tabel lain dari Excel (kolom prestasi, poin). Isian poin manual lama tetap bisa dipakai (nomor berjenis hasil Poin biasa).
- Mengganti K124 butir 3: `agent/ATURAN.md` dan rancangan final diperbarui di PR ini.

### Metode klasemen (F9-11)

- Pengaturan > Hasil: **Medali** (bawaan, juknis) atau **Poin per peringkat** (daftar poin peringkat 1–8, bawaan 7-5-4-3-2-1, bisa diubah). Kolom poin tampil di klasemen apa pun metodenya; pemecah seri metode poin: jumlah juara I, lalu II, lalu III. `Hasil/Klasemen` tetap murni dengan uji unit.

### Peserta publik (F9-12)

- `/peserta`: cari nama, sekolah, atau nomor atlet; saring kelompok umur, nomor lomba, jenis kelamin. Isi: nama, sekolah, nomor atlet, nomor lomba. Hanya pendaftaran Lunas. Tanpa NISN, tanggal lahir, foto, dan data ofisial. Sakelar tampil di Pengaturan (bawaan mati). Paginasi sisi server dengan skeleton.

### Papan videotron (F9-13)

- `/layar` tanpa masuk, hanya data terbit: berganti otomatis antara hasil nomor yang baru terbit, juara umum per jenjang, jadwal berikutnya, dan "Sedang berlangsung"; segar setiap 30 detik lewat JSON `/layar/data` (bisa ditembolok); tombol layar penuh; latar Three.js halus (gambar G9-06 sebagai cadangan).
- **Fleksibel untuk ukuran apa pun** (K141): tata letak cair dengan *container query* dan satuan layar. Huruf diatur `clamp()` dari ukuran layar, dan jumlah baris tabel per halaman dihitung dari tinggi layar. Susunan berpindah otomatis menurut rasio: **mendatar lebar** (≥ 2:1, pita LED; hasil dan juara umum berdampingan), **mendatar** (16:9 sampai 4:3), **tegak** (≤ 3:4; satu kolom), dan **sangat kecil** (sisi pendek < 400 piksel; hanya nomor, nama, prestasi). Parameter `?skala=`, `?susunan=`, dan `?baris=` untuk memaksa bila perlu.
- Uji tampilan otomatis di 3840×2160, 1920×1080, 1280×720, 1024×768, 3840×1080, 1920×480, 1080×1920, dan 512×256: tidak ada teks terpotong atau saling tindih. Pilihan isi dan lama tiap tampilan di Pengaturan > Videotron.

### Galeri (F9-06)

- Tabel `galeri` (`judul`, `keterangan`, `tautan` ke Instagram/Google Drive, `sampul` gambar unggahan, `urutan`, `aktif`); kelola di Pengaturan > Galeri; `/galeri` kartu bersampul yang membuka tautan di tab baru. Album bawaan "Seri sebelumnya" memakai foto `bahan-rombak` sebagai sampul, tautannya diisi admin.

### Panel admin (F9-14 sampai F9-16)

- Sidebar berkelompok: **Pendaftaran** (Dasbor, Pendaftaran, Atlet, Atlet ditolak, Sekolah, Rekap), **Hari lomba** (Daftar ulang, Cetak BIB, Daftar peserta, Jadwal pertandingan, Seri dan lintasan, Lembar juri), **Hasil** (Hasil, Klasemen, Videotron), **Konten** (Halaman depan, Bantuan, Galeri, Unduhan), **Pengaturan**. Menu bawah ponsel tetap K133.
- DataTables tetap (jQuery), gaya Tailwind lewat CSS sendiri; uji yang membaca kelas Bootstrap diganti membaca struktur/atribut `data-*` supaya tidak bergantung pada kerangka CSS.

### Skeleton dan perapian (F9-17)

- Skeleton di bagian yang dimuat JS: tabel admin, cari NPSN, grafik dasbor, peserta publik, videotron, carousel (latar warna rata-rata foto). Halaman yang dibuat server tidak diberi skeleton palsu.
- Keadaan kosong dan halaman galat memakai ilustrasi G9-04. Daftar periksa tombol: satu tombol utama per layar, label kata kerja, ikon Lucide konsisten, keadaan memuat/nonaktif, konfirmasi untuk tindakan yang tidak bisa dibatalkan.
- Bootstrap dan Bootstrap Icons dilepas setelah tidak ada halaman yang memakainya.

## Saluran Codex (F9-02)

- `AGENTS.md` di akar repo dibaca Codex: Codex **hanya** mengerjakan tugas di `desain/tugas/`, **hanya menulis** ke folder keluaran yang disebut tugasnya (`public/aset/gambar/…`) dan `desain/catatan/<ID>.md`, satu tugas satu PR ke `main`, commit atas nama Arumi Studios tanpa atribusi alat AI, judul dan isi PR berbahasa Indonesia.
- Gambar: **3D lembut** dengan palet MilkLife, **tanpa teks, angka, logo, dan merek** di dalam gambar (teks dari HTML), wajah tidak realistis, tidak meniru foto acara sebagai "foto asli". WebP (atau PNG transparan bila perlu), ukuran dan nama berkas persis sesuai tugas, berkas paling besar sesuai tugas.
- Claude meninjau PR Codex: ukuran dan nama berkas, besar berkas, kesesuaian brief, tidak ada teks/logo, lalu menggabung setelah CI hijau. Gambar yang tidak sesuai dikembalikan dengan komentar PR.
- Status tugas gambar dicatat di `agent/PROGRES.md` oleh koordinator, bukan oleh Codex.

## Asumsi yang perlu disetujui

1. Node (sudah ada di Laragon dan sesi cloud) dipakai hanya untuk `npm run css` dan `npm run vendor` di komputer pengembang dan CI; server produksi tidak memasang Node karena CSS dan JS hasil bangun di-commit.
2. Huruf mengikuti templat Flowbite Pro bila berlisensi OFL, disimpan lokal tanpa Google Fonts; bila tidak, Plus Jakarta Sans (OFL) untuk judul dan huruf sistem untuk badan teks.
3. Fitur hari lomba baru (seri dan lintasan, lembar juri, kalkulator poin, videotron, peserta publik) disembunyikan sakelar **mati** sampai pemilik mencobanya di staging, lalu dinyalakan di produksi oleh admin.
4. Semifinal hanya dibuat bila admin menambahkannya; bawaan alur babak Penyisihan → Final sesuai F8-04.
5. Pembagian seri tidak memakai prestasi terbaik (tidak ada data prestasi pendaftaran); unggulan hanya untuk babak lanjutan, dari hasil babak sebelumnya.
6. Galat tabel skor (prestasi di luar rentang tabel) diberi poin 0 dengan peringatan di layar admin.
7. Foto `bahan-rombak/` yang dipakai disalin dalam ukuran web ke `data/halaman-depan/carousel/`; berkas asli di `bahan-rombak/` tetap sebagai rujukan sampai penutupan Tahap 9, lalu dipindah ke `sumber/foto-acara/`.
8. Uji yang memeriksa kelas Bootstrap diubah bersama halaman yang dipindah; jumlah uji tidak boleh berkurang tanpa alasan di deskripsi PR.
