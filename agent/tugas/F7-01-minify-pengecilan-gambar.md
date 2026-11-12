# F7-01 Minify dan pengecilan gambar

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Halaman lebih ringan dibuka dari ponsel: CSS, JavaScript, dan HTML diperkecil di produksi, dan gambar unggahan admin tidak lagi berukuran besar.

## Hasil

- [x] Paket `matthiasmullie/minify` (rancangan 4) dan `"process-timeout": 0` di `composer.json`
- [x] `php spark aset:minify`: berkas `.min.css`/`.min.js` untuk aset aplikasi di `public/aset/`, melewati `vendor/` dan berkas `.min`, menyebut ukuran sebelum dan sesudah, aman dijalankan ulang
- [x] Berkas `.min` aset aplikasi diabaikan git
- [x] Filter `perkecilhtml` (global *after*): hanya bila `aplikasi.minify` aktif dan jawaban `text/html`; isi `<pre>`, `<textarea>`, `<script>`, `<style>` utuh; PDF, Excel, JSON, dan unduhan tidak berubah
- [x] `Unggahan::simpanGambar()` mengecilkan gambar di atas 1920 piksel (sisi terpanjang), format asli dipertahankan, PNG tetap transparan

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: berkas `.min` lebih kecil dan berisi aturan yang sama, `vendor/` dilewati, HTML diperkecil hanya bila minify aktif, PDF/Excel/JSON tidak berubah, gambar besar dikecilkan dan transparansi PNG bertahan, gambar kecil tidak diubah. Dengan `aplikasi.minify = true`, halaman depan, wizard, dan panel tampil sama seperti tanpa minify.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Minify" dan "Pengecilan gambar". Asumsi 1 dan 2. Gelombang 1.

PR: [#60](https://github.com/arumiforge/milklife/pull/60).

Keputusan kecil:

- Paket: `matthiasmullie/minify` 1.3.76 (beserta `matthiasmullie/path-converter` 1.1.3). Tidak ada paket lain yang ikut diperbarui di `composer.lock`.
- `aset:minify` mengolah semua `*.css`/`*.js` di bawah `public/aset/` (saat ini 9 berkas, 166 KB menjadi 103 KB). Folder bernama `vendor` di tingkat mana pun dilewati. Logikanya di `MinifyAset::perkecil($folder)` supaya bisa diuji di folder sementara.
- `.gitignore`: `/public/aset/**/*.min.css` dan `*.min.js` diabaikan, kecuali `public/aset/vendor/**` (pustaka tetap menyimpan versi kecilnya).
- Filter `perkecilhtml`: deretan spasi di teks antar-tag dirapatkan menjadi **satu spasi**, tidak dibuang seluruhnya, karena spasi di antara elemen sebaris (tombol, tautan, lencana) ikut menentukan jarak tampilan. Spasi dibuang seluruhnya hanya di sekitar tag yang spasinya tidak pernah tampil (`head`, `meta`, `link`, `title`, tag tabel, `option`, `optgroup`). Komentar HTML dibuang kecuali komentar bersyarat `<!--[if …]>`. Isi tag (atribut dan nilainya) tidak diubah, jadi nilai isian tetap persis. Jenis jawaban diperiksa dari kepala `Content-Type`; selain `text/html` dilewati. Bila HTML gagal diurai, isi asli dikirim.
- `Config\Filters`: hanya alias `perkecilhtml` dan satu baris di `globals['after']` (berkomentar di baris yang sama), supaya mudah digabung dengan `secureheaders` dari F7-02.
- Pengecilan gambar: hanya `simpanGambar()` (gambar kepala, logo, logo bank). Berkas unduhan bergambar lewat `simpanBerkas()` tidak diubah. PNG disimpan ulang dengan kompresi 9, JPG dan WEBP mutu 85; PNG bertabel warna dengan warna bening juga tetap bening (dicoba manual). Tambahan di luar rencana: arah foto JPG dari kamera (EXIF) diterapkan saat disimpan ulang, karena data EXIF hilang dan foto ponsel bisa tampil miring. Bila memori PHP kurang untuk gambar besar, batas memori dinaikkan sementara; bila gambar tetap gagal diolah, berkas asli (yang sudah lolos batas 2 MB) disimpan apa adanya.
- `bitaDariIni()` dari `BatasUnggahFilter` dipakai ulang untuk membaca `memory_limit`; berkas itu tidak diubah.


Pemeriksaan tampilan (Chromium, kontainer cloud, `CI_ENVIRONMENT = production`, basis data pengembangan berisi `data:awal` dan pengaturan lengkap): halaman depan, wizard (dibuka dan diisi langkah 1), cek pendaftaran, unduhan, serta panel (dasbor, daftar pendaftaran, rekap, pengaturan halaman depan, jadwal) ditangkap di ukuran ponsel (390 piksel) dan komputer (1366 piksel), sekali dengan `aplikasi.minify = false` dan sekali dengan `true` setelah `php spark aset:minify`. Semua tangkapan sama persis, kecuali dua halaman yang berbeda paling banyak 2 tingkat warna di sebagian piksel (gambar latar dan bayangan kotak isian, perbedaan gambar biasa dari peramban, tidak terlihat mata). Tidak ada galat JavaScript. Halaman depan 41 KB menjadi 19 KB, halaman pendaftaran 43 KB menjadi 37 KB (belum termasuk CSS/JS yang ikut mengecil).
