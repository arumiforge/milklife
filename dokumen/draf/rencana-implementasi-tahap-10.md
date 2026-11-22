# Rencana Implementasi Tahap 10 — Identitas Visual Anime, Panel Admin, dan Data Terbaru

| | |
|---|---|
| Status | Draf, **pelaksanaan disetujui pemilik 22 November 2026 (K153)**; pertanyaan terbuka dijawab koordinator (K154, K155) |
| Tanggal | 22 November 2026 |
| Dasar | Permintaan pemilik 22 November 2026: [`permintaan-tahap-10.md`](permintaan-tahap-10.md) (K153); rujukan gaya `desain/referensi/`; logo `sumber/logo/`; foto `data/halaman-depan/carousel/` (asal `sumber/foto-acara/`) |

## Tujuan

Untuk pelaksana tiap tugas Tahap 10 (sub-sesi Claude dan Codex) dan pemilik yang meninjau. Setelah membaca, pelaksana tahu isi tugasnya, urutannya, berkas yang boleh disentuh, pedoman gaya, dan aturan yang berlaku. Setiap tugas dirancang muat dalam **satu sesi**.

Batas waktu: **pendaftaran tutup 30 November 23.59 WIB**, **pertemuan teknik 6 Desember** (semua tugas Tahap 10 sudah di produksi), **lomba 9–12 Desember**. Setiap PR yang CI-nya hijau digabung dan **langsung tampil di produksi tanpa sakelar tampilan** (K153 butir 4).

## Hasil Tahap 10

1. **Data dan pengaturan bawaan terbaru**: `KU 8` tanpa "Seneng Playon" (juga untuk server yang sudah terisi, sehingga impor backup berhasil), WA `0882-1527-7227`, tagar `#MilkLifeAthleticsChallengeSeri2`, media sosial resmi, logo Djarum Foundation baru, dan peta Supersoccer Arena.
2. **Panel admin** yang lebih padat dan mudah dijelajah: logo di kepala, sidebar berwarna merek dan lebih lebar, menu dan submenu bisa dilipat, jejak navigasi rinci, isi lebar penuh, semua tabel berpaginasi, dasbor tanpa angka kembar, menu bawah ponsel diperbaiki.
3. **Halaman publik berkarakter editorial**: hero foto asli bergaya editorial, slideshow 5 detik dengan titik, bilah submenu atas, laci kiri dan menu bawah ponsel, unduhan bisa dipratinjau, ikon polos, tipografi judul tegas, dan art pendukung dari Codex.
4. **Gaya gambar baru**: semua gambar Codex G9 diganti gaya **anime olahraga 2D cel-shaded** (G10), dengan logo resmi ditempel saat pasca-proses pada kaus tokoh.

## Prioritas

Semua sebelum 6 Desember. Bila waktu tidak cukup, **P2** yang bergeser dan diajukan ke pemilik.

- **P1**: F10-01, F10-02, F10-03, F10-04, F10-05, F10-06, F10-07, F10-11.
- **P2**: F10-08, F10-09, F10-10.

## Tugas dan urutan

| Tugas | Isi singkat | Bergantung pada | Gelombang | Target |
|---|---|---|---|---|
| F10-01 Data dan pengaturan bawaan | KU 8, WA dan tagar, media sosial, logo Djarum bawaan, perintah `data:sesuaikan` untuk server terisi, pesan galat impor | — | 1 | 24 Nov |
| F10-02 Peta lokasi | Peta Supersoccer Arena di halaman depan, CSP `frame-src`, muat saat mendekati layar, alamat di Pengaturan | — | 1 | 25 Nov |
| F10-03 Tata letak panel admin | Logo, warna sidebar, sidebar lebih lebar, menu lipat diingat, submenu, jejak navigasi, isi lebar penuh, menu bawah ponsel | — | 1 | 26 Nov |
| F10-04 Tabel berpaginasi dan dasbor | Audit dan paginasi semua tabel admin, satukan angka bukti bayar di dasbor | — | 1 | 26 Nov |
| F10-05 Fondasi gaya editorial | Huruf judul, token, kelas elemen berart (kait gambar opsional G10-11), ikon polos, tanpa border penuh berwarna dan titik hiasan, pedoman bahasa | — | 1 | 26 Nov |
| F10-06 Hero editorial dan slideshow | Hero foto asli editorial, tiga fakta, slideshow 5 detik dengan titik untuk semua carousel, anggaran kinerja | F10-05 | 2 | 28 Nov |
| F10-07 Navigasi publik | Bilah submenu atas yang diatur admin, laci kiri ponsel, menu bawah publik | F10-05 | 2 | 28 Nov |
| F10-08 Kepadatan panel admin | Analisis ruang kosong tiap halaman admin, kurangi yang berlebihan | F10-03, F10-04 | 2 | 30 Nov |
| F10-09 Halaman publik dan unduhan | Pratinjau unduhan, "s.d. 30 November 2026", karakter dan pilihan kata halaman publik lain | F10-05 | 2 | 30 Nov |
| F10-10 Pemasangan art G10 | Periksa dan rapikan pemasangan gambar G10 di semua halaman, tokoh berlogo, videotron | F10-05, F10-06, PR G10 | 3 | 4 Des |
| F10-11 Penutupan Tahap 10 | Uji lengkap, uji peramban 360/390/1280 dan videotron 1920×1080, uji asap, rancangan final revisi 30, arsip | Semua | Akhir | 5 Des |

Tugas Codex (G10-01 sampai G10-12) berjalan **sejajar** sejak rencana ini masuk `main`; urutan dan tenggatnya di `desain/README.md`. Gambar G10 yang menggantikan G9 memakai **nama berkas dan folder yang sama**, jadi langsung tampil begitu PR Codex digabung tanpa kode baru. Gambar baru (elemen, hero) dipasang F10-05 dan F10-06 lewat `gambar_opsional()`/`ada_gambar()`, aman selama berkas belum ada (K41).

### Sesi dan paralel

Paling banyak **5 sub-sesi Claude** sekaligus (K145); sesi Codex tidak dihitung.

| Gelombang | Sub-sesi | Kapan |
|---|---|---|
| 1 | F10-01, F10-02, F10-03, F10-04, F10-05 | Begitu rencana ini masuk `main` |
| 2 | F10-06, F10-07, F10-09 (setelah F10-05); F10-08 (setelah F10-03 dan F10-04) | 25–27 Nov |
| 3 | F10-10 | Setelah sebagian besar PR G10 masuk, paling lambat 2 Des |
| Akhir | F10-11 | Setelah semua tugas lain, paling lambat 5 Des |

## Aturan untuk semua sesi

1. **Aman untuk produksi, langsung aktif** (K139, K153). Tidak ada sakelar tampilan; setiap PR harus rapi di produksi saat digabung. Migrasi hanya **menambah**. Fitur publik yang benar-benar baru dan berisiko tetap boleh bersakelar.
2. **Pendaftaran masih buka sampai 30 November.** PR yang menyentuh wizard, Cek Pendaftaran, unggah bukti, tata letak publik, atau CSS bersama wajib menjaga perilakunya sama dan **diuji alur daftar → cek → bukti di peramban** (Chromium, mode produksi dengan minify); hasilnya ditulis di deskripsi PR.
3. **`main` sumber kebenaran.** Sub-sesi tidak rebase atau force push; menyusul `main` dengan `git merge origin/main`. `composer uji` lulus (CI tiga job: PHPUnit, Hasil bangun tampilan, Tampilan videotron). Satu tugas, satu PR berbahasa Indonesia. Sub-sesi tidak menggabung PR; koordinator menggabung *squash* setelah CI hijau (K126).
4. **CSP tetap menyala** (K127): tanpa `style="…"`, atribut `on…`, atau skrip/gaya sebaris tanpa nonce. `KepalaKeamananTest` tetap menjaga.
5. **CSS hasil bangun**: `public/aset/css/tw.css` dan `public/aset/vendor/lucide/lucide.svg` dibangun `npm install && npm run bangun` setelah merge atau kelas/ikon baru, tidak dipilih salah satu sisi saat konflik. Gaya tambahan masuk `public/aset/tailwind/masukan.css` di **blok bertanda nama tugas**.
6. **Gambar hanya lewat `gambar_opsional()`/`ada_gambar()`** (`App\Libraries\GambarOpsional`). Latar CSS dari art (tombol, tekstur, pemisah) dipasang dengan **kelas penanda yang hanya ditulis bila berkasnya ada**, misalnya `<body class="<?= ada_gambar('elemen/kuas-tombol.webp') ? 'ada-kuas-tombol' : '' ?>">`, supaya tidak ada permintaan 404.
7. **Ponsel lebih dulu**: diperiksa di 360, 390, dan 1280 piksel tanpa gulir mendatar, target sentuh paling kecil 44 piksel, kontras WCAG AA, animasi berhenti saat `prefers-reduced-motion`.
8. **Berkas bersama** ditambah di blok sendiri dan kedua sisi dipertahankan saat merge: `Routes.php`, `DataAwal.php`, `MenuPublik`, `Panel/MenuAdmin.php`, `PROGRES.md`, `PETA-KODE.md`, kartu Pengaturan, uji jumlah kartu, nomor revisi rancangan final (sekarang 29; F10-11 menaikkan ke 30).
9. **Keputusan**: sub-sesi tidak menambah K; pertanyaan untuk pemilik ditulis di PR beserta rekomendasi, dan rekomendasi dipakai bila aman (K145, K149). Status tugas hanya diubah koordinator di `PROGRES.md`.
10. **Atribusi dan data**: commit atas nama Arumi Studios tanpa atribusi alat AI; data uji karangan; backup pendaftaran dan data siswa tidak pernah masuk git (K138, K153); `sumber/` tidak diubah.

## Pedoman gaya

### Tipografi editorial

- **Judul tampilan** memakai **Barlow Condensed** (OFL, K155) berat 600 dan 800, tegak dan miring, berkas `woff2` lokal di `public/aset/vendor/barlow-condensed/` (subset latin dan latin-ext), dimuat `font-display: swap`. Dipakai untuk judul hero, judul bagian, angka besar, dan label fakta; huruf besar (`uppercase`) dengan jarak huruf rapat untuk judul hero, seperti `desain/referensi/hero-editorial.webp`.
- **Teks badan** tetap **Inter** (sudah lokal). Tidak ada huruf ketiga.
- Skala judul cair dengan `clamp()`: hero 2,75–5,5 rem, judul bagian 2–3,25 rem, label kecil 0,75 rem huruf besar berjarak lebar (*eyebrow*) dengan garis pendek oranye di depannya.
- Satu kata kunci di judul hero boleh berwarna oranye (seperti "CHALLENGE" pada rujukan).

### Palet

| Token | Kode | Pemakaian |
|---|---|---|
| Biru tua | `#021F7B` | latar hero gelap, sidebar admin, teks judul di latar terang |
| Biru | `#234987` | warna utama, tautan, pojok kiri atas admin |
| Oranye | `#E85F31` | aksen, tombol utama (teks putih pada `#C4461B` untuk AA) |
| Magenta | `#F0005A` | aksen sekunder, sangat hemat |
| Biru muda | `#9BCCEC` | cahaya, garis tipis di latar gelap |
| Krem | `#F5EFE6` | pita fakta dan bagian editorial terang (baru, dari rujukan hero) |
| Latar | `#F3F6FB` | dasar halaman |

### Bentuk, ikon, dan hiasan

- **Ikon polos**: ikon Lucide tampil tanpa latar kotak atau lingkaran berwarna. Ikon boleh berwarna (biru atau oranye) dan berukuran lebih besar, tetapi tanpa "lencana" di belakangnya.
- **Tanpa border penuh berwarna** (kartu atau kotak dengan garis tepi oranye/biru di keempat sisi). Pemisah memakai ruang, latar, garis tipis abu-abu, satu garis aksen pendek di satu sisi, atau art pemisah G10-11.
- **Tanpa titik hiasan**: tidak ada pola titik, butir bulat dekoratif, atau titik di depan label. Penanda slideshow (titik kecil untuk berpindah foto) tetap boleh karena fungsional.
- **Karakter dari tipografi, art, dan bentuk**: tombol utama dengan latar sapuan kuas (art G10-11) di bawah teks HTML, pemisah lintasan, ornamen judul, bingkai foto bersudut miring, sudut potong (*clip-path*) sederhana. Teks selalu HTML.
- Bayangan hemat; sudut membulat kecil (`rounded-lg` ke bawah) supaya kesan tegas.

### Gaya gambar (Codex)

**Anime olahraga 2D cel-shaded**, menggantikan 3D lembut (K153). Prompt dasar dari pemilik:

> 2D cel-shaded sports anime illustration, school-age track athletes, dynamic sprint poses, bold linework, crisp shadow shapes, low-angle track composition, brand-colored jerseys.

Rujukan `desain/referensi/gaya-anime-lintasan.webp`. Inspirasi *100 Meters (Hyakuemu)* hanya untuk arah gaya (cel-shading, garis tegas, sudut rendah, rasa kecepatan), **tanpa meniru tokoh, wajah, adegan, atau komposisi khasnya**. Rincian dan larangan di `AGENTS.md` bagian "Gaya bersama".

### Bahasa (K153)

- **Bahasa Inggris hanya untuk slogan, judul bagian bergaya editorial, dan art**, misalnya "Race Day", "Results", "Born to Run", "Meet the Athletes". Setiap judul Inggris didampingi keterangan berbahasa Indonesia di bawahnya.
- Tombol, formulir, pesan, menu fungsional, dan seluruh panel admin tetap berbahasa Indonesia ("Daftar Sekarang", "Cek Pendaftaran", "Unduh", "Lihat").
- Pilihan kata untuk guru dan ofisial: singkat, aktif, tanpa istilah teknis.

## Anggaran kinerja halaman depan (ponsel)

Diukur di Chromium lebar 390 piksel, mode produksi dengan minify, jaringan disimulasikan *Fast 4G* (Playwright atau Lighthouse), **sebelum dan sesudah** di setiap PR yang menyentuh halaman depan; angkanya ditulis di deskripsi PR.

| Ukuran | Batas |
|---|---|
| Total unduhan sebelum pengguna menggulir | ≤ 900 KB |
| Foto hero pertama (WebP, `srcset` 640/960/1600, `fetchpriority="high"`) | ≤ 160 KB pada lebar 390 |
| Huruf (Inter + Barlow Condensed, hanya yang dipakai di layar pertama di-*preload*) | ≤ 3 berkas, ≤ 150 KB |
| JS halaman depan (tanpa Three.js) | ≤ 120 KB setelah minify |
| Largest Contentful Paint | ≤ 2,5 detik |
| Cumulative Layout Shift | ≤ 0,1 |

Gambar di bawah lipatan `loading="lazy"` dan `decoding="async"`; foto slideshow selain yang pertama dimuat saat akan tampil; iframe peta hanya dimuat saat bagiannya mendekati layar (IntersectionObserver) dengan tautan "Buka di Google Maps" sebagai cadangan; Three.js tidak dimuat di ponsel bila mengganggu LCP.

## Kesepakatan teknis

### Data bawaan (F10-01)

- `DataAwal.php` menulis `KU 8` (bukan `KU 8 (Seneng Playon)`); `beranda/_kepala.php` tidak lagi memisah nama dengan tanda kurung bila tidak ada; uji yang memuat nama lama diperbarui. Rancangan final bagian kelompok umur ikut diubah.
- Perintah baru **`php spark data:sesuaikan`**, aman dijalankan berkali-kali di server yang sudah terisi, dan setiap langkah hanya berjalan bila data masih nilai bawaan lama:
  - ganti nama kelompok umur `KU 8 (Seneng Playon)` menjadi `KU 8` (lewati bila `KU 8` sudah ada);
  - `Pembayaran.nomor_wa` dan `HalamanDepan.nomor_wa_tanya` bila masih contoh `628123456789` → `6288215277227`;
  - media sosial bawaan lama (`milklife.athletics`, `@milklife.athletics`, `@milklifeathletics`) → Instagram `https://www.instagram.com/milklife.id`, TikTok `https://www.tiktok.com/@milklife_id`, YouTube `https://www.youtube.com/@milklife_id`;
  - mencetak ringkasan apa yang diubah dan dilewati.
- **Logo Djarum bawaan**: berkas `sumber/logo/logo-djarum-foundation.png` disalin (tanpa diubah isinya, boleh dikonversi WebP lossless bila lebih kecil) ke `public/aset/gambar/logo-djarum.png` dan dipakai bila admin belum mengunggah logo Djarum sendiri, seperti `IsiHalamanDepan::logoMerek()` (K152). Tidak perlu langkah server.
- Pesan "Tanya admin" dan "Bagikan ke WA" memuat `#MilkLifeAthleticsChallengeSeri2`.
- `pendaftaran:impor`: bila nama kelompok umur di zip tidak ditemukan, pesan galat menyebut nama yang tidak cocok dan menyarankan menyamakan nama di Pengaturan > Kelompok Umur atau menjalankan `php spark data:sesuaikan`.

### Peta lokasi (F10-02)

- Pengaturan > Halaman Depan: isian **Alamat peta (embed Google Maps)** dan **Tautan buka peta**; bawaan alamat embed Supersoccer Arena dari dokumen permintaan dan tautan `https://maps.google.com/?q=Supersoccer+Arena+Kudus`. Isian hanya menerima `https://www.google.com/maps/embed?…`.
- Bagian **Lokasi** di halaman depan (dekat Kontak): iframe `loading="lazy"`, `referrerpolicy="no-referrer-when-downgrade"`, `title`, ukuran dari kelas Tailwind (rasio 4:3 di ponsel, 16:9 di desktop), disisipkan JS saat bagian mendekati layar; tanpa JS tampil tautan saja.
- CSP `frame-src` ditambah `https://www.google.com` (Turnstile tetap); `KepalaKeamananTest` diperbarui.

### Panel admin (F10-03, F10-04, F10-08)

- **Warna (K154)**: sidebar biru tua `#021F7B`, pojok kiri atas (blok logo di atas sidebar) biru `#234987`, area konten tetap terang. Teks menu putih/biru muda, butir aktif berlatar putih 10% dengan garis oranye di sisi kiri. Logo resmi (`IsiHalamanDepan::logoMerek()`) di pojok kiri atas.
- Sidebar desktop `lg:w-72` (dari `w-64`), konten `lg:ps-72`; menu dan submenu memakai Flowbite *collapse* dengan keadaan terakhir disimpan di `localStorage` (dibungkus `try/catch`); butir aktif selalu terbuka.
- Submenu lebih lengkap dari `Panel/MenuAdmin` (misalnya Pengaturan: Acara, Pendaftaran, Pembayaran, Halaman Depan, Kelompok Umur, Nomor Lomba, Jadwal, Kontak, Unduhan, Bantuan, Galeri, Hasil, Tabel Skor, Videotron).
- Jejak navigasi `admin/_jejak_tw.php` dipakai di **setiap** halaman admin, sampai halaman rincian dan formulir (misalnya Dasbor › Pendaftaran › MAC2A0000126 › Ubah).
- Isi di kanan sidebar tanpa `max-w-*` pembatas; formulir panjang boleh tetap dibatasi lebar baca di dalam kartu.
- Menu bawah ponsel tetap 5 butir K133, dengan target sentuh ≥ 48 piksel, label terbaca, butir aktif jelas (ikon dan label oranye, K133), dan butir "Menu" membuka sidebar.
- **Semua tabel berpaginasi** (DataTables sisi server atau paginasi CodeIgniter), bawaan 25 baris: rekap, daftar peserta, nomor dada, sekolah, riwayat, tabel di Pengaturan, dan tabel lain yang ditemukan audit. Daftar audit ditulis di deskripsi PR.
- **Dasbor "Angka lain"**: "Bukti bayar perlu diperiksa" dan "Bukti bayar baru belum diperiksa" diperiksa definisinya di `Panel/RingkasanDasbor`. Bila hitungannya sama, satukan menjadi satu butir; bila beda, beri label yang menyebut bedanya (misalnya "Menunggu, bukti sudah ada" dan "Bukti baru sejak terakhir diperiksa").

### Gaya editorial (F10-05)

- Barlow Condensed dipasang lewat `alat/vendor.mjs` dari paket `@fontsource/barlow-condensed` (devDependency, K155) ke `public/aset/vendor/barlow-condensed/` beserta lisensinya, dicatat di `public/aset/vendor/README.md`.
- Token baru di `@theme` (`--font-tampilan`, `--color-krem`) dan kelas komponen di blok F10-05 `masukan.css`: `judul-tampilan`, `alis` (eyebrow), `tombol-utama-berart`, `pemisah-lintasan`, `ornamen-judul`, `bingkai-foto`, `tekstur-kertas`. Kelas berart hanya memakai gambar bila kelas penanda `ada-<nama>` terpasang (aturan 6).
- Sapu semua view publik dan komponen: ikon berlatar menjadi ikon polos, border penuh berwarna diganti, titik hiasan dihapus. Perubahan visual saja; perilaku wizard, Cek, dan bukti tetap.

### Hero dan slideshow (F10-06)

- Hero mengikuti `desain/referensi/hero-editorial.webp`: latar biru tua, judul besar Barlow Condensed huruf besar di kiri, slogan Inggris kecil di atasnya (alis), keterangan Indonesia, dua tombol (Daftar Sekarang, Cek Pendaftaran; mengikuti keadaan buka/tutup K81); **foto asli** dari carousel halaman depan memenuhi sisi kanan dengan gradasi biru tua ke kiri; keterangan foto kecil di pojok kanan bawah (dari keterangan foto carousel, bila ada); **pita tiga fakta** di bawah: Hari lomba, Lokasi, Batas pendaftaran ("30 November 2026"). Tahun di judul hero tetap disembunyikan (permintaan 20).
- Di ponsel: foto di atas (rasio 4:3) dengan gradasi ke bawah, judul di bawahnya, fakta bertumpuk.
- **Slideshow**: berganti otomatis tiap 5 detik, hanya penanda titik yang bisa diklik/disentuh (tombol aksesibel, `aria-label` "Foto 2 dari 5"), tanpa tombol maju/mundur; berhenti saat `prefers-reduced-motion`, saat tab tidak terlihat (`visibilitychange`), dan saat fokus papan ketik ada di dalamnya. Berlaku untuk semua carousel publik.
- Hiasan art (sapuan kecepatan G10-02 `beranda/hero-sapuan.webp`) lewat `gambar_opsional()`.

### Navigasi publik (F10-07)

- **Bilah submenu atas (K154)**: di atas menu utama pada layar `lg` ke atas, latar biru tua, teks kecil. Bawaan: Unduhan, Galeri (bila sakelarnya menyala), Bantuan, Tanya Panitia (WA), lalu ikon media sosial dari Pengaturan > Kontak. Menu utama menjadi: Beranda, Jadwal, Hasil, Peserta (butir yang sakelarnya mati tidak tampil), tombol Cek Pendaftaran dan Daftar. Admin memilih butir bilah atas di Pengaturan > Halaman Depan (centang per butir; media sosial otomatis dari Kontak).
- **Laci kiri ponsel**: tombol menu membuka laci dari kiri (Flowbite drawer) berisi menu utama, bilah atas, dan media sosial; menutup dengan tombol, Esc, atau ketuk latar; fokus terkunci di laci.
- **Menu bawah publik (K154)** di bawah `lg`: Beranda, Jadwal, Hasil, Daftar (selama pendaftaran buka; setelah tutup menjadi Cek), Menu (membuka laci). Butir yang halamannya mati diganti Bantuan. Tidak tampil di wizard pendaftaran dan videotron supaya tidak menutupi tombol langkah.

### Halaman publik lain (F10-09)

- **Unduhan**: tombol "Lihat" membuka pratinjau PDF dan gambar di peramban (modal dengan `<iframe>`/`<img>` dari berkas sendiri, atau tab baru di ponsel) selain "Unduh"; jenis dan ukuran berkas tidak ditampilkan. Berkas lain hanya "Unduh". CSP `frame-src 'self'` sudah cukup.
- "Daftar s.d. 30 November 2026" di semua tempat tanggal tutup tampil (bukan hanya tanggal dan bulan); tahun di judul hero tetap disembunyikan.
- Terapkan gaya editorial ke Jadwal, Hasil, Klasemen, Peserta, Galeri, Bantuan, Unduhan, Cek Pendaftaran, dan wizard (visual saja): judul editorial Inggris + keterangan Indonesia sesuai pedoman bahasa, pilihan kata ditinjau.

### Pemasangan art G10 (F10-10)

- Gambar pengganti G9 tampil otomatis (nama sama). F10-10 memeriksa semua halaman dengan gambar G10 di 360/390/1280 dan videotron, menyesuaikan potongan, ukuran, gradasi, dan `object-position` bila gaya anime berbeda komposisi dengan 3D lama, serta memasang gambar G10 yang belum punya tempat (lembar tokoh G10-01, elemen G10-11 yang belum dipakai F10-05).

## Saluran Codex (G10)

Brief di `desain/tugas/G10-*.md`; urutan di `desain/README.md`. Codex hanya menulis ke folder keluaran brief dan `desain/catatan/<ID>.md` (`AGENTS.md`). Tokoh berkaus peserta atau panitia digambar dengan dada polos, lalu logo resmi ditempel saat pasca-proses (K153). Koordinator meninjau dengan daftar periksa `desain/README.md` dan menggabung setelah CI hijau.

| Brief | Isi | Folder | Perlu sebelum |
|---|---|---|---|
| G10-01 | Lembar tokoh (rujukan konsistensi dan gambar "Meet the Athletes") | `tokoh/` | 24 Nov |
| G10-11 | Elemen: latar tombol, tekstur, pemisah, ornamen judul, bingkai | `elemen/` | 25 Nov |
| G10-02 | Halaman depan (pengganti G9-02) dan sapuan hero | `beranda/` | 26 Nov |
| G10-09 | Pita judul halaman publik (pengganti G9-09) | `judul/` | 27 Nov |
| G10-05 | Nomor lomba (pengganti G9-05) | `nomor/` | 28 Nov |
| G10-04 | Keadaan kosong, galat, berhasil (pengganti G9-04) | `keadaan/` | 29 Nov |
| G10-03 | Panduan pendaftaran (pengganti G9-03) | `panduan/` | 29 Nov |
| G10-07 | Kategori Bantuan (pengganti G9-07) | `bantuan/` | 30 Nov |
| G10-06 | Videotron dan podium (pengganti G9-06) | `layar/` | 2 Des |
| G10-08 | Panel admin (pengganti G9-08) | `admin/` | 2 Des |
| G10-12 | Pola dan hiasan latar (pengganti G9-12, tanpa pola titik) | `pola/` | 2 Des |
| G10-10 | Gambar bagikan (pengganti G9-10) | `bagikan/` | 3 Des |

## Asumsi

1. Huruf judul Barlow Condensed lokal (OFL) adalah aset, bukan pustaka kode; dipasang lewat rantai bangun yang sama dengan Inter (K141, K155).
2. Gambar G10 pengganti memakai nama berkas yang sama dengan G9 sehingga kode pemasangan Tahap 9 tetap berlaku; `nomor/seneng-playon.webp` dan `pola/titik-halus.webp` tidak dibuat ulang (KU 8 tanpa "Seneng Playon"; titik hiasan dilarang).
3. Foto hero hanya dari foto carousel yang sudah dipasang admin atau bawaan; tidak ada foto baru yang di-commit.
4. `data:sesuaikan` tidak menyentuh data pendaftaran; impor backup tetap dijalankan pemilik di server.
5. Gambar G9 lama tertimpa oleh G10; tidak ada folder G9 terpisah yang disimpan.
