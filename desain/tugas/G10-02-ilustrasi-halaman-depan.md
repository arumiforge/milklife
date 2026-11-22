# G10-02 Ilustrasi halaman depan

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F9-03 (sudah terpasang), F10-06 (sapuan hero) |
| Menggantikan | G9-02 |

## Tujuan

Ilustrasi halaman depan berganti ke gaya anime tanpa perubahan kode: nama berkas sama dengan G9-02.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama" dan rujukan `desain/referensi/gaya-anime-lintasan.webp`. Tokoh memakai tokoh A, B, C dari G10-01.

## Keluaran

Folder `public/aset/gambar/beranda/`.

| Berkas | Ukuran | Format | Paling besar | Isi |
|---|---|---|---|---|
| `tokoh-lari.webp` | 1200 × 1200 | WebP alfa | 250 KB | Tokoh A sprint menghadap kanan, sudut rendah, garis kecepatan tipis di belakang |
| `tokoh-lompat.webp` | 1200 × 1200 | WebP alfa | 250 KB | Tokoh B (berjilbab) melayang lompat jauh |
| `tokoh-lempar.webp` | 1200 × 1200 | WebP alfa | 250 KB | Tokoh C menolak peluru, badan memutar |
| `latar-pita.webp` | 1920 × 640 | WebP | 150 KB | Sapuan kecepatan biru-oranye-magenta miring di kanan bergaya cat kuas anime; kiri terang kosong untuk tulisan |
| `latar-lintasan.webp` | 1920 × 640 | WebP | 150 KB | Lintasan merah bata melengkung dari sudut rendah, garis putih tegas, sangat terang dan samar (dipakai di belakang kartu) |
| `latar-angka.webp` | 1920 × 900 | WebP | 200 KB | Tribun stadion sekolah siang hari bergaya latar anime (langit biru, awan bergaris), tanpa orang dekat, tidak terlalu ramai |
| `kartu-ku.webp` | 800 × 600 | WebP alfa | 120 KB | Perlengkapan lomba (kerucut, gawang mini, turbo, peluru, tongkat estafet) bergaya cel-shaded |
| `hero-sapuan.webp` | 1600 × 900 | WebP alfa | 90 KB | **Baru**: sapuan kecepatan putih dan oranye tipis miring dari kiri bawah ke kanan atas, sebagian besar transparan, untuk ditumpuk di atas foto hero gelap |

## Logo di kaus (K153)

Tokoh yang memakai kaus peserta atau panitia digambar dengan **area dada polos** (tanpa huruf, angka, atau simbol). Setelah gambar jadi, logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` **ditempel apa adanya** dengan skrip pasca-proses (misalnya Pillow): diperkecil sekitar 18–22% lebar dada, diberi sedikit perspektif/rotasi mengikuti arah badan, tanpa digambar ulang, tanpa diubah warnanya, dan tidak tertutup lengan. Bila dada tidak terlihat jelas (badan menyamping atau terlalu kecil), logo tidak ditempel. Skrip penempelan tidak di-commit; langkahnya ditulis di `desain/catatan/<ID>.md`.

## Selesai bila

Delapan berkas sesuai tabel dan `desain/catatan/G10-02.md` ada.
