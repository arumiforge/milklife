# Arsip Tahap 10 — Identitas visual anime, panel admin, dan data terbaru

Selesai: 22 November 2026

Permintaan: [`dokumen/draf/permintaan-tahap-10.md`](../../dokumen/draf/permintaan-tahap-10.md) (K153). Rencana: [`dokumen/draf/rencana-implementasi-tahap-10.md`](../../dokumen/draf/rencana-implementasi-tahap-10.md), pertanyaan terbukanya dijawab K154 dan K155. Dikerjakan paralel paling banyak 5 sub-sesi oleh koordinator Tahap 10 (K145, K149). Setiap PR langsung aktif di produksi tanpa sakelar tampilan; selama pendaftaran buka, perilaku wizard, Cek Pendaftaran, dan unggah bukti dijaga tetap sama (K153 butir 4). Jawaban pertanyaan PR: K156 (gelombang 1), K157 (gelombang 2), K166 (F10-12), K169 (F10-13). Masukan tampilan pemilik: K160–K165, K167, K168.

| Tugas | Status | Gelombang | PR |
|---|---|---|---|
| [F10-01 Data dan pengaturan bawaan](../tugas/F10-01-data-pengaturan-bawaan.md) | selesai | 1 | #174 |
| [F10-02 Peta lokasi](../tugas/F10-02-peta-lokasi.md) | selesai | 1 | #173 |
| [F10-03 Tata letak panel admin](../tugas/F10-03-tata-letak-admin.md) | selesai | 1 | #180 |
| [F10-04 Tabel berpaginasi dan dasbor](../tugas/F10-04-tabel-paginasi-dasbor.md) | selesai | 1 | #175 |
| [F10-05 Fondasi gaya editorial](../tugas/F10-05-fondasi-gaya-editorial.md) | selesai | 1 | #172 |
| [F10-06 Hero editorial dan slideshow](../tugas/F10-06-hero-editorial-slideshow.md) | selesai | 2 | #176 |
| [F10-07 Navigasi publik](../tugas/F10-07-navigasi-publik.md) | selesai | 2 | #177 |
| [F10-09 Halaman publik dan unduhan](../tugas/F10-09-halaman-publik-unduhan.md) | selesai | 2 | #179 |
| [F10-08 Kepadatan panel admin](../tugas/F10-08-kepadatan-admin.md) | selesai | 2 | #190 |
| [F10-10 Pemasangan art G10](../tugas/F10-10-pemasangan-art-g10.md) | selesai | 3 | #198 |
| [F10-12 Tata bahasa halaman publik](../tugas/F10-12-tata-bahasa-publik.md) | selesai | 3 | #200 |
| [F10-13 Tata bahasa panel admin](../tugas/F10-13-tata-bahasa-admin.md) | selesai | 3 | #203 |
| [F10-11 Penutupan Tahap 10](../tugas/F10-11-penutupan-tahap-10.md) | selesai | akhir | PR penutupan |

PR koordinator dan pendukung: #169 (permintaan, K153), #170 (rencana, tugas, brief G10, K154, K155), #178 (K156), #181 (batas waktu job PHPUnit 50 menit), #185 (K157), #195 (uji tidak bergantung urutan `$_SERVER`), #197 (rujukan SSA, G10-13, K158), #199 (K159, tugas F10-13), #202 (K168).

## Gambar (Codex)

Gaya anime olahraga 2D cel-shaded (K153), stadion mirip Supersoccer Arena (K158), logo resmi ditempel saat pasca-proses. Semua pengganti G9 memakai nama berkas yang sama, jadi langsung tampil begitu digabung (K41, `gambar_opsional()`).

| Tugas gambar | PR |
|---|---|
| G10-01 Lembar tokoh | #182 |
| G10-11 Elemen tombol dan ornamen | #183 |
| G10-02 Ilustrasi halaman depan | #184 |
| G10-09 Pita judul | #186 |
| G10-05 Nomor lomba | #187 |
| G10-04 Keadaan kosong dan galat | #188 |
| G10-03 Panduan pendaftaran | #189 |
| G10-07 Kategori Bantuan | #191 |
| G10-06 Videotron dan podium | #192 |
| G10-08 Panel admin | #193 |
| G10-12 Pola dan hiasan | #194 |
| G10-10 Gambar bagikan | #196 |
| G10-13 Stadion SSA | #201 |

Berkas G9 `pola/titik-halus.webp` dan `nomor/seneng-playon.webp` dihapus (K168 butir 15). Ornamen judul G10-11 (`elemen/ornamen-judul-kiri.webp`, `-kanan.webp`) yang tidak terpakai dihapus setelah penutupan (K172).

## Hasil

- **Data bawaan terbaru** (F10-01): `KU 8` tanpa "Seneng Playon", WA 0882-1527-7227, tagar `#MilkLifeAthleticsChallengeSeri2`, media sosial resmi, logo Djarum Foundation baru, dan perintah aman `php spark data:sesuaikan` untuk server yang sudah terisi (K154 butir 4); pesan galat `pendaftaran:impor` menyebut nama kelompok umur yang tidak cocok.
- **Peta lokasi** (F10-02): bagian Lokasi Supersoccer Arena di atas Kontak, iframe dimuat saat mendekati layar, alamat diatur di Pengaturan > Halaman depan, CSP `frame-src https://www.google.com`.
- **Panel admin** (F10-03, F10-04, F10-08, F10-13): sidebar biru tua dengan pojok logo biru, lebih lebar, menu dan submenu bisa dilipat dan diingat, jejak di semua halaman, isi lebar penuh, semua tabel berpaginasi 25 baris, dua kartu bukti bayar di dasbor, menu bawah ponsel Dasbor, Pendaftaran, Atlet, Rekap, Menu (K157), tampilan lebih padat, teks sentence case.
- **Halaman publik editorial** (F10-05, F10-06, F10-07, F10-09, F10-10, F10-12): judul Barlow Condensed (K155), judul Inggris hiasan dengan `<h1>` Indonesia rata tengah (K157, K167), hero foto asli dengan slideshow 5 detik, pita tiga fakta, bilah submenu atas dengan Masuk panitia, laci kanan dan menu bawah ponsel tanpa Menu (K168), unduhan dengan pratinjau modal, bagian "Our Athletes", tanpa alis, garis atas oranye, garis kiri, dan titik hiasan (K160, K164, K168), nomor pendaftaran bisa disalin sekali klik.
- **Wizard** (F10-12): tombol Bantuan dan Kosongkan di judul langkah (K161), jenjang berupa daftar pilihan dan kabupaten berkotak cari di ponsel (K162, K163), isian terkunci lebih jelas (K164 butir 2). Data yang dikirim dan aturan pemeriksaan tidak berubah.
- **Tata bahasa** (F10-12, F10-13): semua teks mengikuti skill `bahasa-indonesia-plain` (K159), termasuk FAQ bawaan dan pesan WA bawaan (K166).
- **Penutupan** (F10-11): rancangan final revisi 30 (K153–K168), panduan produksi revisi 10, kedua PDF dibuat ulang, `PETA-KODE.md`, K169; `aset:minify` tidak lagi memperkecil masukan bangun `public/aset/tailwind/masukan.css` (tidak dimuat halaman dan hasilnya malah lebih besar).
- Tahap 10 tidak menambah tabel atau migrasi.

## Perintah sekali jalan di server

Server yang dipasang sebelum Tahap 9, sekali setelah `git pull` dan `composer install --no-dev --optimize-autoloader` (panduan produksi bagian 10):

```
php spark migrate --all
php spark faq:awal
php spark tabel-skor:impor --trilomba
php spark halaman-depan:carousel
php spark galeri:awal
php spark data:sesuaikan
php spark aset:minify
php spark pendaftaran:impor <berkas.zip>   (hanya bila memindah data dari zip Backup data)
```

Server yang sudah menjalankan perintah Tahap 9 cukup `migrate --all`, `data:sesuaikan`, dan `aset:minify`. Semua aman diulang dan tidak menimpa isian admin. Tanya-jawab dan pesan WA yang sudah tersimpan di server **tidak berubah otomatis**; admin menyuntingnya di Pengaturan > Bantuan dan Pengaturan > Halaman depan bila ingin memakai teks baru.

## Uji penutupan (F10-11)

- Otomatis: `composer uji` lulus (1870 uji); CI tiga job (PHPUnit, Hasil bangun tampilan, Tampilan videotron) hijau di PR penutupan. `npm run bangun` menghasilkan berkas yang sama persis dengan yang di-commit; `npm run uji:layar` lulus di delapan ukuran dan uji berjalan lama.
- Pemasangan lokal mode produksi (`CI_ENVIRONMENT = production`, `aplikasi.minify = true`, CSP menyala, basis data baru): semua perintah sekali jalan di atas berjalan tanpa galat dan `pembaruan:periksa` menulis "Tidak ada perintah lain yang perlu dijalankan".
- Uji asap di Chromium dengan data karangan: tiga pendaftaran lewat wizard di 390, 360, dan 1280 piksel (ofisial berfoto, dua atlet, bukti transfer) → Cek Pendaftaran dan bukti PDF → masuk admin → Tandai Lunas dua pendaftaran → nomor atlet dan BIB PDF → daftar ulang hadir semua → hasil Final dua nomor, terbit → klasemen (Juara umum I dan II) → seri dan lintasan dikunci → videotron. Tanpa galat konsol atau CSP.
- Sapuan peramban 360, 390, dan 1280 piksel: 237 muatan halaman (16 alamat publik dan 63 alamat admin per lebar, termasuk formulir, rincian, dan baris tabel skor, setelah lima sakelar publik dinyalakan). Tanpa galat konsol, pelanggaran CSP, permintaan gagal, dan gulir mendatar. Satu-satunya 404 adalah alamat tidak ada yang sengaja diuji. Videotron 1920×1080 di aplikasi sebenarnya pas satu layar tanpa galat.
- Anggaran kinerja halaman depan (390 piksel, DPR 3, Fast 4G dan CPU 4×, tanpa kompresi server lokal, rata-rata tiga kali): total sebelum menggulir 807 KB (≤ 900), foto hero pertama 74 KB pada DPR 3 dan 19 KB pada DPR 1 (≤ 160), huruf 3 berkas 93 KB (≤ 3, ≤ 150), LCP sekitar 930 ms (≤ 2,5 detik), CLS 0,000 (≤ 0,1), Three.js tidak dimuat. JS tanpa Three.js 150 KB mentah (sekitar 34 KB gzip): skrip halaman depan sendiri 15 KB, sisanya `flowbite.min.js` 136 KB dari tata letak bersama. Angka mentah melewati batas 120 KB seperti yang sudah dicatat F10-06 (#176); logo kepala yang dulu 132 KB kini salinan 160 piksel. Sesudah penutupan pemilik memutuskan ukuran halaman bukan prioritas, jadi angka ini hanya catatan (K170).

## Yang dibawa ke tahap berikutnya

- Sakelar fitur publik (Jadwal, Seri, Peserta, Galeri, Videotron) tetap dinyalakan admin di produksi setelah dicoba di staging.
- Usulan tugas terpisah: judul kolom Excel admin dan ekspor publik ke sentence case bila pemilik menginginkannya (K169). Pemangkasan pustaka demi ukuran tidak dikerjakan (K170).
