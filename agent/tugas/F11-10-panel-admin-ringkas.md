# F11-10 Panel admin ringkas

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Panel admin lebih ringkas dan mudah dipakai di ponsel: pencarian menu di kepala halaman, jejak halaman seragam, teks panjang dipotong, dan tombol lebih padat (K174 butir 1–9).

## Hasil

- [ ] Kepala halaman tanpa judul menu. Desktop: kotak pencarian menu yang menyaring butir sidebar dan membuka halamannya. Ponsel: kotak cari menu dan tombol keluar berupa ikon saja berlatar dan bergaris tepi. Tombol menu pembuka sidebar tetap.
- [ ] Jejak halaman tanpa ikon rumah, dimulai dari "Dasbor". Semua halaman menu dan submenu diperiksa; logika jejak seragam dan sejalan dengan pil navigasi.
- [ ] Menu bawah ponsel: Dasbor, Pendaftaran, Atlet, Rekap, Pengaturan ("Menu" dihapus).
- [ ] Teks lebih dari dua baris di semua tabel dan kartu panel admin dipotong dengan tautan "Selengkapnya" yang menampilkan teks penuh; tetap terbaca pembaca layar.
- [ ] Saringan dua isian per baris.
- [ ] Ponsel: tombol aksi tiga per baris dengan ikon dan sebagian teks; ditekan baru teks penuh muncul.
- [ ] Ponsel: label "Cari" di Pendaftaran dan halaman lain disembunyikan dari tampilan (tetap ada untuk pembaca layar), cukup kotak cari dan pilihan.
- [ ] Tombol Unduh Excel dan Unduh PDF berupa ikon saja (lembar kerja untuk Excel, PDF untuk PDF) dengan teks saat kursor di atasnya atau ditekan di ponsel; nama aksesibel tetap lengkap.
- [ ] Label grafik dasbor di ponsel tidak bertumpuk.

## Berkas dan uji

Tata letak admin (`app/Views/admin/tata_letak*`, sidebar, kepala, menu bawah), komponen jejak halaman dan pil navigasi, komponen saringan dan tombol aksi, `public/aset/js/` panel admin, dasbor, CSS hasil bangun (`npm run bangun`). Uji `--filter JejakHalamanAdmin`, `TataLetakAdmin`, `Dasbor`; uji tampilan ponsel dengan Playwright bila ada. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Semua halaman admin di `JejakHalamanAdminTest` memakai jejak yang dimulai "Dasbor"; pencarian menu menemukan butir sidebar; tangkapan layar ponsel dan desktop diperiksa. `composer uji` lulus.

## Catatan

Dasar: K174. Bentrok dengan F11-11 (butir menu Pengaturan > Cadangan) dan F11-09 (Pengaturan > Videotron); gabung berurutan dengan rebase. Setiap PR aman langsung dipasang di produksi (K139).
