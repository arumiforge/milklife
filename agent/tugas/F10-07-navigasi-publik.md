# F10-07 Navigasi publik

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P1 |

## Tujuan

Pengunjung menemukan semua halaman dengan mudah di desktop dan ponsel (permintaan C17, C18).

## Hasil

- [x] Bilah submenu atas (layar `lg` ke atas, latar biru tua, teks kecil) dengan bawaan K154: Unduhan, Galeri (bila sakelar menyala), Bantuan, Tanya Panitia (WA), dan ikon media sosial dari Pengaturan > Kontak
- [x] Menu utama: Beranda, Jadwal, Hasil, Peserta (yang sakelarnya mati tidak tampil), tombol Cek Pendaftaran dan Daftar
- [x] Pengaturan > Halaman Depan: centang butir bilah atas (pengaturan baru, bawaan semua menyala); media sosial otomatis
- [x] Laci kiri ponsel (Flowbite drawer) menggantikan menu turun: menu utama, butir bilah atas, media sosial; tutup dengan tombol, Esc, atau ketuk latar; fokus terkunci; ikon polos
- [x] Menu bawah publik di bawah `lg` (K154): Beranda, Jadwal, Hasil, Daftar (setelah tutup: Cek), Menu; butir yang halamannya mati diganti Bantuan; tidak tampil di wizard dan videotron; konten diberi ruang bawah supaya tidak tertutup
- [x] Butir aktif jelas; target sentuh ≥ 48 px

## Berkas dan uji

`app/Libraries/MenuPublik.php`, `app/Views/tata_letak/publik_tw.php`, partial menu baru, pengaturan halaman depan, JS bila perlu, blok F10-07 `masukan.css`. Uji: uji menu publik di `HalamanDepan*`, `PengaturanHalamanDepanTest`, `KepalaKeamananTest`.

## Selesai bila

Semua halaman publik diperiksa di 360/390/1280 (minify) tanpa galat konsol/CSP dan tanpa gulir mendatar; laci bisa dipakai papan ketik; alur daftar → cek → bukti tetap; semua uji lulus.

## Catatan

Bergantung pada F10-05.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
