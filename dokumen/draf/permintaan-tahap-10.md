# Permintaan Tahap 10 — Identitas visual, panel admin, dan data terbaru

| | |
|---|---|
| Status | Draf permintaan pemilik, dasar rencana Tahap 10 |
| Tanggal | 22 November 2026 |
| Keputusan | K153 |
| Untuk | Koordinator Tahap 10 dan sub-sesinya, Codex |

Dokumen ini merangkum permintaan pemilik 22 November 2026 beserta telaah, kritik, dan jawaban wawancara. Koordinator Tahap 10 menyusun `dokumen/draf/rencana-implementasi-tahap-10.md` dan berkas tugas `agent/tugas/F10-*.md` serta brief gambar `desain/tugas/G10-*.md` dari sini.

## Keadaan saat permintaan dibuat

- Tahap 9 sudah ditutup (#151). Logo resmi sudah dipakai sebagai favicon, ikon aplikasi, dan logo merek (K152, #165).
- Gambar Codex Tahap 9 (G9-02 sampai G9-12, kecuali G9-11) sedang digabung sesi pemantau `session_01VqzjGVUzmmy9CbDRLzkWoy`. Semua gambar itu akan **diganti** gaya baru di Tahap 10.
- Pendaftaran tutup 30 November 23.59 WIB, pertemuan teknik 6 Desember, lomba 9–12 Desember 2026. `main` langsung dipakai staging dan produksi (K139, K140).

## Jawaban wawancara (K153)

1. **Backup pendaftaran** (zip dari tombol Backup data, 22 November 10.52) **tidak masuk git**. Zip itu sudah diuji di basis data lokal sesi koordinator lama:
   - impor ditolak selama kelompok umur masih bernama `KU 8 (Seneng Playon)`, karena di produksi namanya `KU 8`;
   - setelah nama disamakan, 57 pendaftaran (42 lunas, 15 menunggu), 286 atlet, dan 214 berkas berhasil diimpor.
   Impor sebenarnya dijalankan pemilik di server. Data uji di repo tetap memakai nama dan NISN karangan.
2. **Bahasa Inggris hanya untuk slogan, judul bagian bergaya editorial, dan art**, misalnya "Race Day", "Results", "Born to Run". Tombol, formulir, pesan, menu fungsional, dan panel admin tetap berbahasa Indonesia.
3. **Logo di kaus tokoh**: Codex menggambar kaus dengan area dada polos, lalu logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` **ditempel apa adanya** saat pasca-proses (tidak digambar ulang oleh model).
4. **Rilis langsung aktif**: setiap PR yang CI-nya hijau digabung dan langsung tampil di produksi, seperti Tahap 9. Karena pendaftaran masih buka, setiap PR yang menyentuh wizard, Cek Pendaftaran, dan unggah bukti wajib menjaga perilaku tetap sama dan diuji alur daftar → cek → bukti di peramban.

## Daftar permintaan

### A. Panel admin

1. Ikon aplikasi (logo resmi) di kepala admin. Sidebar dan pojok kiri atas memakai warna berbeda dari area konten.
2. Sidebar lebih lebar di desktop.
3. Jejak navigasi (breadcrumb) lebih rinci di setiap halaman, termasuk halaman rincian dan formulir.
4. Submenu lebih lengkap. Menu dan submenu bisa dilipat (*collapsible*), keadaan terakhir diingat.
5. Isi di kanan sidebar memakai lebar penuh, tanpa batas `max-w` yang memotong teks dan tabel.
6. Semua tabel berpaginasi, tidak menampilkan semua baris sekaligus. Audit tabel yang belum: rekap, daftar peserta, nomor dada, sekolah, riwayat, dan tabel di Pengaturan.
7. Isi panel lebih padat. Analisis area kosong di tiap halaman, lalu kurangi yang berlebihan.
8. Dasbor, bagian "Angka lain": "Bukti bayar perlu diperiksa" dan "Bukti bayar baru belum diperiksa" cukup satu bila maknanya sama. Periksa definisi keduanya dulu, lalu satukan atau beri label yang jelas bedanya.
9. Menu bawah ponsel diperbaiki: ukuran sentuh, label, butir aktif, dan akses ke menu lengkap.

### B. Data dan pengaturan bawaan

10. KU 8 tanpa tambahan "Seneng Playon": `DataAwal.php`, `beranda/_kepala.php`, flyer atau teks lain yang menyebutnya. Sediakan langkah atau perintah aman untuk server yang sudah terisi supaya nama di produksi tetap `KU 8`.
11. Nomor WA bawaan `0882-1527-7227` (ganti `081234456789`). Isi pesan "tanya admin" dan "bagikan ke WA" memakai `#MilkLifeAthleticsChallengeSeri2`.
12. Media sosial bawaan: TikTok `@milklife_id`, YouTube `@milklife_id`, Instagram `milklife.id`.
13. Logo Djarum Foundation baru: `sumber/logo/logo-djarum-foundation.png` (510 × 100, transparan) menjadi logo bawaan `logo_djarum`.
14. Peta lokasi Supersoccer Arena di halaman depan, dari embed Google Maps:
    `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.743848796379!2d110.85445437499567!3d-6.800985593196346!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e70c5529fe28039%3A0x889ddf1d4265ec0f!2sSupersoccer%20Arena!5e0!3m2!1sid!2sid!4v1795321165239!5m2!1sid!2sid`.
    Perlu CSP `frame-src https://www.google.com`, `loading="lazy"`, tanpa gaya sebaris, dan alamat bisa diubah di Pengaturan.

### C. Halaman depan dan halaman publik

15. **Hero** memakai **foto asli** dari `sumber/foto-acara/` (yang sudah dipasang ke `data/halaman-depan/carousel/`) dengan gaya **editorial** seperti `desain/referensi/hero-editorial.webp`: tipografi besar, foto penuh di kanan dengan gradasi gelap ke kiri, keterangan foto kecil, tiga fakta di bawah (hari lomba, lokasi, batas pendaftaran). Elemen hiasan boleh art Codex, fotonya tetap asli.
16. **Slideshow** hero dan carousel lain: otomatis berganti tiap **5 detik**, hanya penanda titik (*dot*), tanpa tombol maju atau mundur. Berhenti saat `prefers-reduced-motion` dan saat tab tidak terlihat.
17. **Bilah submenu di atas menu utama** untuk tautan yang tidak tampil di menu utama (misalnya Unduhan, Bantuan, Galeri, media sosial), bisa diatur admin.
18. **Menu ponsel halaman depan**: laci dari sisi kiri seperti sidebar, bukan menu yang turun dari atas. Tambahkan menu navigasi ponsel untuk halaman publik (pertimbangkan menu bawah publik dengan 4–5 butir utama).
19. **Unduhan** bisa dipratinjau (PDF dan gambar di peramban) selain diunduh. Jangan tampilkan jenis dan ukuran berkas.
20. "Daftar s.d." ditambah tahun ("s.d. 30 November 2026"). Tahun di hero tetap disembunyikan.
21. Ikon polos tanpa latar berbentuk kotak atau lingkaran. Tidak ada gaya border penuh berwarna dan tidak ada titik hiasan.
22. Pilihan kata lebih ideal. Slogan dan judul editorial boleh berbahasa Inggris (lihat jawaban 2).
23. **Semua elemen punya karakter**, bukan CSS bawaan. Karakter datang dari tipografi editorial, art Codex (tekstur, ornamen, pemisah, latar tombol, bingkai), dan bentuk. Teks tetap HTML.

### D. Gaya visual baru (Codex)

24. **Semua gambar Codex diganti** ke gaya **anime olahraga 2D cel-shaded**. Prompt dasar dari pemilik:

    > 2D cel-shaded sports anime illustration, school-age track athletes, dynamic sprint poses, bold linework, crisp shadow shapes, low-angle track composition, brand-colored jerseys.

    Rujukan: `desain/referensi/gaya-anime-lintasan.webp` (lintasan sekolah, tiga pelari, salah satunya berjilbab) dan inspirasi anime *100 Meters (Hyakuemu)*. **Jangan meniru tokoh, wajah, adegan, atau komposisi khas anime itu**. Yang diambil hanya arah gaya: cel-shading, garis tegas, sudut rendah, rasa kecepatan.
25. Tokoh yang memakai kaus peserta atau panitia diberi logo resmi di dada lewat penempelan pasca-proses (jawaban 3).
26. **Tombol dan elemen khusus**: Codex membuat art pendukung (tekstur kuas, sapuan kecepatan, bingkai, latar tombol, pemisah lintasan, ornamen judul) dalam format yang dipasang lewat CSS. Teks tombol tetap HTML supaya terbaca pembaca layar, bisa diterjemahkan, dan tajam di semua layar.
27. Codex mengerjakan semua art. Koordinator menulis brief `desain/tugas/G10-*.md` dan memperbarui gaya bersama di `AGENTS.md` serta `desain/README.md` dari "3D lembut" ke gaya anime baru.

## Telaah dan saran

1. **Data siswa tetap di luar git** (K138, K153). Pemilik menjalankan `php spark pendaftaran:impor <zip>` di server setelah nama `KU 8` sama.
2. **Logo pada gambar AI** rawan rusak, jadi ditempel dari berkas asli (K153).
3. **Gaya anime dan foto asli** saling melengkapi: foto asli untuk hero dan galeri (kesan resmi dan dokumenter), art anime untuk ilustrasi, keadaan kosong, panduan, videotron, dan elemen.
4. **Tombol bergambar penuh tidak dipakai.** Teks dalam gambar buruk untuk aksesibilitas, lambat, dan pecah di layar tajam. Karakter dibangun dari art pendukung lewat CSS.
5. **Bahasa Inggris dibatasi** (K153), karena pendaftar adalah guru dan sekolah.
6. **Waktu ketat.** Urutan yang disarankan:
   1. Data dan pengaturan bawaan (B), kecil dan langsung berguna.
   2. Panel admin (A), dipakai panitia untuk verifikasi pembayaran.
   3. Brief gambar (D) segera ditulis supaya Codex bekerja sejajar.
   4. Halaman publik (C), lalu pemasangan art.
   Semua harus siap sebelum pertemuan teknik 6 Desember.
7. **Kinerja.** Hero foto dan art baru menambah ukuran halaman. Pakai WebP, `srcset`, `loading="lazy"` di bawah lipatan, dan anggaran ukuran halaman depan di ponsel. Ukur sebelum dan sesudah.
8. **Peta Google** menambah pihak ketiga. Muat iframe hanya saat bagian lokasi mendekati layar, dan sediakan tautan "Buka di Google Maps" sebagai cadangan.

## Pertanyaan terbuka

Koordinator Tahap 10 memakai rekomendasinya bila aman di produksi (K145, K149), lalu mencatatnya di `agent/KEPUTUSAN.md`.

- Isi bilah submenu atas: tautan apa saja bawaannya?
- Menu bawah publik di ponsel: butir apa saja? Rekomendasi: Beranda, Jadwal, Hasil, Daftar/Cek, Menu.
- Warna sidebar admin: biru tua merek `#021F7B` dengan pojok kiri atas `#234987`, atau sebaliknya.
