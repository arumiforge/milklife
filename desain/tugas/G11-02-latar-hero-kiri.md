# G11-02 Latar art hero kiri

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (K175) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F11-12 (hero halaman depan) |
| Menggantikan | — (baru; `beranda/hero-sapuan.webp` G10-02 tetap ada) |

## Tujuan

Sisi kiri hero halaman depan (latar biru tua di belakang judul dan tombol) dibuat **lebih ramai dan hidup**. Sekarang batas antara sisi kiri dan foto kanan terlihat seperti garis lurus yang memotong art. Latar baru ini **memudar halus** ke arah foto, jadi art yang menyentuh tepi tidak terpotong lurus.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama". Tanpa teks, huruf, angka, logo, titik hiasan, atau halftone. Tanpa tokoh berwajah jelas (judul besar ada di atasnya).

## Keluaran

Folder `public/aset/gambar/beranda/`.

| Berkas | Ukuran | Format | Paling besar | Dipakai | Isi |
|---|---|---|---|---|---|
| `hero-kiri.webp` | 1600 × 1000 | WebP alfa | 220 KB | Layar lebar (mulai 1024 px), menutupi 60% kiri hero, `object-cover` rata kiri | Latar art biru tua `#021F7B` ramai: sapuan kuas biru `#234987` dan oranye `#E85F31` miring, garis kecepatan putih, siluet atap tribun SSA dan lampu stadion samar di kiri atas, potongan lintasan merah bata melengkung di kiri bawah. **Seperempat kanan memudar ke transparan** dengan tepi lembut tak beraturan (sapuan kuas yang menipis, bukan gradasi lurus) |
| `hero-kiri-ponsel.webp` | 900 × 1200 | WebP alfa | 140 KB | Ponsel dan tablet, di bawah foto (foto di atas, judul di bawah) | Isi sama, disusun tegak. **Seperlima atas memudar ke transparan** supaya menyatu dengan foto di atasnya |

## Syarat teknis

- **Kontras judul**: judul putih besar dan teks biru muda ditaruh di atas sepertiga kiri tengah. Bagian itu harus tetap gelap (kecerahan rata-rata di bawah 25%) dan tidak terlalu rinci; keramaian ada di tepi atas, bawah, dan kiri.
- **Tepi pudar**: bagian yang menyentuh foto tidak boleh berakhir dengan garis lurus atau tepi terpotong. Objek yang mendekati tepi menipis atau memudar, tidak terpotong tegak.
- Warna dasar bagian tidak transparan biru tua `#021F7B` (sama dengan `bg-primary-900`), supaya tidak terlihat sambungan dengan latar hero.
- Tanpa orang di depan; bila ada penonton tribun, berupa siluet kecil jauh.

## Catatan

- Pratinjau di **deskripsi PR**: `hero-kiri.webp` ditumpuk di atas foto lintasan (misalnya `desain/referensi/hero-editorial.webp`) yang mengisi 66% kanan, dengan judul putih "BORN TO RUN" besar di kiri; dan `hero-kiri-ponsel.webp` di bawah foto 4:3.
- Tulis kecerahan rata-rata area judul di `desain/catatan/G11-02.md`.

## Selesai bila

Dua berkas sesuai tabel ada di `public/aset/gambar/beranda/` dan `desain/catatan/G11-02.md` ada.
