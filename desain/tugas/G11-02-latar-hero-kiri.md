# G11-02 Latar art hero kiri

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (K175) |
| Dibuat | 22 November 2026, diperbarui 23 November 2026 dengan ukuran tampil dan area aman hasil ukur |
| Dipakai oleh | F11-12 (hero halaman depan) |
| Menggantikan | — (baru; `beranda/hero-sapuan.webp` G10-02 tetap ada) |

## Tujuan

Sisi kiri hero halaman depan (latar biru tua di belakang judul dan tombol) dibuat **lebih ramai dan hidup**. Sekarang batas antara sisi kiri dan foto kanan terlihat seperti garis lurus yang memotong art. Latar baru ini **memudar halus** ke arah foto, jadi art yang menyentuh tepi tidak terpotong lurus.

Sampai gambar ini ada, hero memakai latar biru tua polos dengan gradasi foto. Setelah berkas digabung, gambar langsung dipakai tanpa perubahan kode.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama". Tanpa teks, huruf, angka, logo, titik hiasan, atau halftone. Tanpa tokoh berwajah jelas (judul besar ada di atasnya).

## Keluaran

Folder `public/aset/gambar/beranda/`. Nama berkas harus persis.

| Berkas | Ukuran | Format | Paling besar | Dipakai |
|---|---|---|---|---|
| `hero-kiri.webp` | 1600 × 1000 | WebP alfa, kualitas 80–85 | 220 KB | Layar mulai 1024 px |
| `hero-kiri-ponsel.webp` | 900 × 1200 | WebP alfa, kualitas 80–85 | 140 KB | Ponsel dan tablet, di bawah 1024 px |

Isi keduanya sama: latar biru tua `#021F7B`, sapuan kuas biru `#234987` dan oranye `#E85F31` miring, garis kecepatan putih, siluet atap tribun SSA dan lampu stadion samar, potongan lintasan merah bata melengkung.

## `hero-kiri.webp` (layar lebar)

**Cara dipasang** (`app/Views/beranda/_kepala.php`): `absolute inset-y-0 left-0 h-full w-[62%] object-cover object-left`, di atas foto. Foto mengisi 66% kanan hero, jadi gambar ini menimpa sisi kiri foto.

| Lebar layar | Kotak gambar | Bagian gambar yang tampil |
|---|---|---|
| 1024 px | 635 × 544 px | 73% kiri, tinggi penuh |
| 1280 px | 794 × 608 px | 82% kiri, tinggi penuh |
| 1440 px | 893 × 608 px | 92% kiri, tinggi penuh |
| 1920 px | 1190 × 608 px | lebar penuh, sekitar 9% atas dan 9% bawah terpotong |

Karena di layar 1024–1440 px bagian kanan gambar terpotong lurus oleh tepi kotak, **tepi pudar harus selesai sebelum 70% lebar gambar**:

| Bagian (lebar gambar) | Isi |
|---|---|
| 0–45% (0–720 px) | Dasar biru tua `#021F7B` penuh, tidak transparan |
| 45–70% (720–1120 px) | Memudar ke transparan dengan **sapuan kuas yang menipis**, tidak rata dan bukan gradasi lurus. Objek yang sampai ke sini menipis atau memudar, tidak terpotong tegak |
| 70–100% (1120–1600 px) | **Transparan penuh** |

**Area judul**: judul putih besar, paragraf biru muda, dan dua tombol menutupi **2–62% lebar dan 22–80% tinggi** gambar. Bagian ini tetap gelap (rata-rata kecerahan di bawah 25%) dan tenang: boleh sapuan biru `#234987` besar berkontras rendah, tanpa garis putih atau oranye terang di belakang huruf.

**Tempat keramaian**: pita atas (tinggi 9–22%) dan pita bawah (tinggi 80–91%) sepanjang 0–60% lebar, ditambah tepi kiri 0–3%. Siluet atap tribun dan lampu stadion di pita atas, lintasan merah bata melengkung di pita bawah kiri. 9% teratas dan terbawah boleh berisi lanjutan hiasan, tetapi bisa terpotong di layar 1920 px.

## `hero-kiri-ponsel.webp` (ponsel dan tablet)

**Cara dipasang**: `absolute inset-x-0 bottom-0 h-[75%] w-full object-cover object-top`, di **belakang** foto. Foto 4:3 (ponsel) atau 16:9 (tablet) ada di atas, judul dan tombol di bawahnya.

| Lebar layar | Kotak gambar | Bagian gambar yang tampil |
|---|---|---|
| 320 px | 320 × 452 px | hampir penuh, sedikit terpotong kiri dan kanan |
| 390 px | 390 × 493 px | 95% atas |
| 768 px | 768 × 572 px | 56% atas |
| sekitar 1000 px | 1000 × 645 px | 48% atas |

| Bagian (tinggi gambar) | Isi |
|---|---|
| 0–25% | **Tertutup foto** bila ada foto. Dasar biru tua polos, dan 0–8% teratas memudar ke transparan untuk hero tanpa foto |
| 25–92% | **Area judul dan tombol**, hampir selebar layar (5–95% lebar). Gelap (rata-rata kecerahan di bawah 25%) dan tenang |
| 25–50%, tepi kiri dan kanan 0–8% dan 92–100% | Keramaian yang tetap terlihat di tablet: ujung sapuan kuas, garis kecepatan |
| 85–100% | Lintasan merah bata melengkung dan sapuan oranye (hanya terlihat di ponsel) |

## Syarat teknis

- **Tepi pudar**: bagian yang menyentuh foto tidak boleh berakhir dengan garis lurus atau tepi terpotong.
- Warna dasar bagian tidak transparan biru tua `#021F7B` (sama dengan `bg-primary-900`), supaya tidak terlihat sambungan dengan latar hero.
- Tanpa orang di depan; bila ada penonton tribun, berupa siluet kecil jauh.

## Catatan

- Pratinjau di **deskripsi PR**, tiga gambar:
  1. Layar 1280 × 608 px: foto lintasan (misalnya `desain/referensi/hero-editorial.webp`) mengisi 66% kanan; `hero-kiri.webp` setinggi 608 px rata kiri, dipotong di lebar 794 px; judul putih "BORN TO RUN" setinggi sekitar 80 px mulai x 16 px, y 145 px.
  2. Layar 1024 × 544 px dengan susunan sama, gambar dipotong di lebar 635 px (memeriksa tepi pudar).
  3. Ponsel 390 px: foto 4:3 (390 × 293 px) di atas, `hero-kiri-ponsel.webp` 390 × 493 px rata bawah, judul di bawah foto.
- Tulis kecerahan rata-rata area judul kedua berkas di `desain/catatan/G11-02.md`.

## Selesai bila

Dua berkas sesuai tabel ada di `public/aset/gambar/beranda/` dan `desain/catatan/G11-02.md` ada.
