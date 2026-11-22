# F10-03 Tata letak panel admin

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P1 |

## Tujuan

Panitia bekerja di panel yang jelas berwarna merek, mudah dijelajah, dan memakai seluruh lebar layar (permintaan A1–A5, A9).

## Hasil

- [x] Logo resmi (`IsiHalamanDepan::logoMerek()`) di pojok kiri atas; pojok kiri atas biru `#234987`, sidebar biru tua `#021F7B`, area konten terang (K154); kontras teks menu AA
- [x] Sidebar desktop lebih lebar (`lg:w-72`), konten menyesuaikan
- [x] Menu dan submenu bisa dilipat; keadaan terakhir diingat (`localStorage` dalam `try/catch`); kelompok yang memuat halaman aktif selalu terbuka; tanpa skrip sebaris
- [x] Submenu lebih lengkap dari `Panel/MenuAdmin` (misalnya semua halaman Pengaturan, Hari lomba, Hasil)
- [x] Jejak navigasi rinci di **setiap** halaman admin termasuk rincian dan formulir (`admin/_jejak_tw.php`); uji memeriksa semua rute admin GET punya jejak
- [x] Isi kanan sidebar lebar penuh: hapus `max-w-*` pembatas di tata letak dan halaman (formulir panjang boleh dibatasi di dalam kartu)
- [x] Jarak dalam tata letak dirapatkan (padding kepala, sidebar, area isi) sebagai dasar F10-08
- [x] Menu bawah ponsel: target sentuh ≥ 48 px, label terbaca, butir aktif jelas (K133), butir "Menu" membuka sidebar

## Berkas dan uji

`app/Views/tata_letak/admin_tw.php`, `_menu_admin_tw.php`, `app/Libraries/Panel/MenuAdmin.php`, `admin/_jejak_tw.php`, view admin yang membatasi lebar, JS baru `public/aset/js/menu-admin.js`, blok F10-03 di `masukan.css`. Uji: `TataLetakHalamanTest`, `KatalogTampilanTest`, `KepalaKeamananTest`.

## Selesai bila

Semua halaman admin diperiksa di 360/390/1280 (minify) tanpa galat konsol/CSP dan tanpa gulir mendatar; menu lipat diingat setelah muat ulang; semua uji lulus.

## Catatan

Berkas bersama dengan F10-04 dan F10-08 (view admin). F10-04 hanya menyentuh tabel; F10-03 hanya tata letak, menu, jejak, dan lebar.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
