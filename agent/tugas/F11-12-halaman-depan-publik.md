# F11-12 Halaman depan dan publik dirapikan

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Halaman depan dan halaman publik lebih rapi dan mudah dipakai di ponsel: nomor lomba per kelompok umur yang ringkas, kartu KU berart, hero yang lebih halus, dan menu bawah dengan tombol Daftar yang menonjol (K175).

## Hasil

- [ ] Bagian nomor lomba: di desktop satu KU satu baris; isi tiap KU tampil semua secara bawaan dan bisa disembunyikan sehingga hanya kepala KU yang tampil; ikon nomor lebih besar; kolom Putra dan Putri terpisah berkepala sendiri (Campuran atau beregu di bawahnya), nama nomor tanpa akhiran putra/putri.
- [ ] Di bawah hero tanpa ikon tanggal, lokasi, dan pengeras suara; batas pendaftaran di ponsel rata tengah.
- [ ] Kartu KU: angka besar dengan isian huruf bertekstur (`background-clip: text`), cadangan gradien sporty, dan gambar G11-01 dipakai otomatis bila berkasnya ada. Keterangan jumlah nomor individu dan beregu diganti ikon dan angka yang dipisah garis miring kecil (ikon individu 6 / ikon beregu 2; nama aksesibel tetap lengkap), supaya judul KU tetap paling menonjol.
- [ ] Titik penanda slide di ponsel disembunyikan.
- [ ] Hero kiri memakai latar G11-02 bila ada; batas dengan foto kanan bergradasi halus, art yang menyentuh tepi tidak terpotong lurus.
- [ ] Menu bawah publik ponsel: Beranda, Bantuan, Daftar (tengah, lebih besar dan menonjol), Cek, Jadwal. Setelah pendaftaran tutup tombol tengah menjadi Hasil; bila Jadwal mati diganti Unduhan.
- [ ] Halaman hasil: Unduh PDF dan Excel ikon saja (nama aksesibel lengkap, teks saat kursor di atasnya atau ditekan); jejak halaman di kiri atas dan lengkap.
- [ ] Tombol sejenis di ponsel (Lihat semua hasil, Lihat klasemen, dan lainnya) satu baris: satu berteks lengkap, lainnya ikon; tekan pertama melebarkan dan menampilkan teks, tekan kedua membuka halaman. Tetap bisa dipakai dengan papan ketik dan pembaca layar.
- [ ] Brief gambar Codex G11-01 (art dan tekstur isian huruf tiap KU) dan G11-02 (latar hero kiri lebih ramai dengan tepi gradasi) ditulis di `desain/tugas/` dan didaftarkan di `desain/README.md`, dengan nama berkas yang dipakai kode.

## Berkas dan uji

Tampilan halaman depan (`app/Views/beranda*` atau setara), bagian nomor lomba dan kartu KU, tata letak publik dan menu bawah, halaman hasil publik, `public/aset/js/` publik, CSS hasil bangun (`npm run bangun`), `desain/tugas/G11-01-*.md`, `desain/tugas/G11-02-*.md`. Uji `--filter HalamanDepan`, `TataLetakPublik`, `HasilPublik`; tangkapan layar ponsel dan desktop dengan Playwright. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Halaman depan, menu bawah, dan halaman hasil sesuai K175 di ponsel dan desktop; tanpa gambar G11 halaman tetap rapi dengan cadangan gradien. `composer uji` lulus.

## Catatan

Dasar: K175. Brief gambar sebaiknya dibuka sebagai PR kecil lebih dulu supaya Codex bisa mulai. Bentrok dengan F11-09 (halaman videotron) dan F11-10 (komponen tombol unduh ikon dan tombol aksi panel admin; samakan komponen ikon unduh bila memungkinkan). Setiap PR aman langsung dipasang di produksi (K139).
