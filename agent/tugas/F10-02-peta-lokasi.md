# F10-02 Peta lokasi

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P1 |

## Tujuan

Pengunjung halaman depan melihat peta Supersoccer Arena dan bisa membukanya di Google Maps (permintaan B14).

## Hasil

- [ ] Pengaturan > Halaman Depan: isian "Alamat peta (embed Google Maps)" (hanya `https://www.google.com/maps/embed?…`) dan "Tautan buka peta"; bawaan alamat embed dari dokumen permintaan dan `https://maps.google.com/?q=Supersoccer+Arena+Kudus`; kosong = bagian peta tidak tampil
- [ ] Bagian Lokasi di halaman depan (dekat Kontak): judul, alamat singkat, iframe `loading="lazy"` dengan `title`, rasio 4:3 ponsel dan 16:9 desktop lewat kelas Tailwind, tanpa gaya sebaris
- [ ] Iframe disisipkan JS saat bagian mendekati layar (IntersectionObserver); tanpa JS atau sebelum dimuat tampil tautan "Buka di Google Maps"
- [ ] CSP `frame-src` ditambah `https://www.google.com`; Turnstile tetap; `KepalaKeamananTest` diperbarui
- [ ] Data awal dan `data:sesuaikan` (bila sudah ada dari F10-01) mengisi bawaan peta hanya bila kosong; bila F10-01 belum masuk, bawaan cukup dari `Config/HalamanDepan`

## Berkas dan uji

`app/Config/ContentSecurityPolicy.php`, `app/Config/HalamanDepan.php`, `Admin/Pengaturan/HalamanDepanController.php`, `admin/pengaturan/halaman_depan.php`, view baru `beranda/_lokasi.php`, `beranda.php`, JS baru `public/aset/js/peta-lokasi.js`. Uji: `PengaturanHalamanDepanTest`, `HalamanDepan*`, `KepalaKeamananTest`.

## Selesai bila

Peta muncul di Chromium 360/390/1280 (minify) tanpa galat konsol/CSP setelah digulir ke bagian Lokasi; tidak ada permintaan ke google.com sebelum bagian itu mendekati layar; semua uji lulus.

## Catatan

Berkas bersama dengan F10-06 (beranda): tambahkan bagian di partial sendiri dan satu baris di `beranda.php`.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
