# F10-05 Fondasi gaya editorial

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P1 |

## Tujuan

Halaman publik punya karakter editorial yang konsisten: huruf judul tegas, ikon polos, tanpa border penuh berwarna dan titik hiasan, dan kelas elemen berart yang siap menerima art Codex (permintaan C21–C23, D26).

## Hasil

- [x] Barlow Condensed (OFL, K155) berat 600 dan 800, tegak dan miring, latin dan latin-ext, dari paket `@fontsource/barlow-condensed` lewat `alat/vendor.mjs` ke `public/aset/vendor/barlow-condensed/` beserta lisensi; dicatat di `public/aset/vendor/README.md`
- [x] Token `--font-tampilan` dan `--color-krem` (`#F5EFE6`) di `@theme`; kelas di blok F10-05 `masukan.css`: `judul-tampilan`, `alis`, `tombol-utama-berart`, `pemisah-lintasan`, `ornamen-judul`, `bingkai-foto`, `tekstur-kertas`
- [x] Kelas berart memakai gambar G10-11 (`public/aset/gambar/elemen/`) hanya bila kelas penanda `ada-<nama>` terpasang dari `ada_gambar()` di tata letak; tanpa berkas, tampilan tetap rapi tanpa permintaan 404
- [x] `komponen('tombol')` varian utama memakai `tombol-utama-berart`; teks tetap HTML; fokus dan kontras AA
- [x] Sapu view publik dan `app/Views/komponen/`: ikon berlatar kotak/lingkaran menjadi ikon polos, border penuh berwarna diganti (latar, garis tipis abu-abu, atau aksen satu sisi), titik hiasan dihapus (`grep -rn "rounded-full.*bg-\|border-2 border-primary\|border-orange" app/Views`)
- [x] Judul bagian halaman depan memakai `judul-tampilan` dan `alis`; judul Inggris (slogan dan editorial) selalu dengan keterangan Indonesia, tombol dan menu tetap Indonesia (K153)
- [x] `AGENTS.md`/`desain/` tidak disentuh; katalog `admin/gaya` menampilkan kelas baru

## Berkas dan uji

`package.json`, `package-lock.json`, `alat/vendor.mjs`, `public/aset/vendor/`, `public/aset/tailwind/masukan.css`, `public/aset/css/tw.css`, `app/Views/komponen/`, `app/Views/tata_letak/publik_tw.php` (penanda `ada-*`, *preload* huruf), view publik. Uji: `KomponenTampilanTest`, `KatalogTampilanTest`, `KepalaKeamananTest`, `HalamanDepan*`, `Pendaftaran*`, `CekPendaftaranTest`.

## Selesai bila

Halaman depan, wizard, Cek, bukti, jadwal, hasil, peserta, galeri, bantuan, dan unduhan diperiksa di 360/390/1280 (minify) tanpa galat konsol/CSP; **alur daftar → cek → bukti diuji di peramban dan perilakunya sama**; semua uji lulus.

## Catatan

F10-06, F10-07, dan F10-09 bergantung pada tugas ini. Jangan mengubah struktur hero (F10-06) atau menu (F10-07) selain huruf dan kelas.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
