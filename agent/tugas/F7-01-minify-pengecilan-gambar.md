# F7-01 Minify dan pengecilan gambar

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Halaman lebih ringan dibuka dari ponsel: CSS, JavaScript, dan HTML diperkecil di produksi, dan gambar unggahan admin tidak lagi berukuran besar.

## Hasil

- [ ] Paket `matthiasmullie/minify` (rancangan 4) dan `"process-timeout": 0` di `composer.json`
- [ ] `php spark aset:minify`: berkas `.min.css`/`.min.js` untuk aset aplikasi di `public/aset/`, melewati `vendor/` dan berkas `.min`, menyebut ukuran sebelum dan sesudah, aman dijalankan ulang
- [ ] Berkas `.min` aset aplikasi diabaikan git
- [ ] Filter `perkecilhtml` (global *after*): hanya bila `aplikasi.minify` aktif dan jawaban `text/html`; isi `<pre>`, `<textarea>`, `<script>`, `<style>` utuh; PDF, Excel, JSON, dan unduhan tidak berubah
- [ ] `Unggahan::simpanGambar()` mengecilkan gambar di atas 1920 piksel (sisi terpanjang), format asli dipertahankan, PNG tetap transparan

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: berkas `.min` lebih kecil dan berisi aturan yang sama, `vendor/` dilewati, HTML diperkecil hanya bila minify aktif, PDF/Excel/JSON tidak berubah, gambar besar dikecilkan dan transparansi PNG bertahan, gambar kecil tidak diubah. Dengan `aplikasi.minify = true`, halaman depan, wizard, dan panel tampil sama seperti tanpa minify.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Minify" dan "Pengecilan gambar". Asumsi 1 dan 2. Gelombang 1.
