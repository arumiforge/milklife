# G10-01 Lembar tokoh

| | |
|---|---|
| Tahap | 10 — Identitas visual anime (K153) |
| Dibuat | 22 November 2026 |
| Dipakai oleh | F10-10 (bagian "Meet the Athletes"), rujukan konsistensi semua brief G10 |
| Menggantikan | — |

## Tujuan

Menetapkan tokoh tetap yang dipakai ulang di semua gambar G10 supaya wajah, rambut, dan kaus konsisten, sekaligus gambar perkenalan atlet di halaman depan.

Gaya: **anime olahraga 2D cel-shaded** sesuai `AGENTS.md` bagian "Gaya bersama" dan rujukan `desain/referensi/gaya-anime-lintasan.webp`.

## Tokoh

| Kode | Tokoh |
|---|---|
| A | Siswi SD (±10 tahun), rambut kuncir kuda, kaus biru `#234987`, celana pendek biru tua |
| B | Siswi SMP (±13 tahun), **berjilbab olahraga** biru tua, kaus lengan panjang biru, celana panjang olahraga |
| C | Siswa SMA (±16 tahun), rambut pendek, kaus oranye `#E85F31`, celana pendek gelap |
| D | Siswa SD (±9 tahun), rambut ikal pendek, kaus biru muda `#9BCCEC` |
| E | Panitia dewasa, kaus panitia biru tua `#021F7B` dengan topi, peluit di leher, papan klip |

## Keluaran

Folder `public/aset/gambar/tokoh/`, WebP alfa (latar transparan).

| Berkas | Ukuran | Paling besar | Isi |
|---|---|---|---|
| `lembar-tokoh.webp` | 2400 × 1200 | 350 KB | Kelima tokoh berdiri berjajar, tampak depan, seluruh badan, pose santai percaya diri, satu sumber cahaya dari kiri atas |
| `tokoh-a.webp` … `tokoh-e.webp` | 900 × 1200 | 150 KB | Tiap tokoh sendiri dalam pose khasnya (A berlari, B lompat jauh melayang, C menolak peluru, D start jongkok, E memegang stopwatch dan papan klip) |

## Logo di kaus (K153)

Tokoh yang memakai kaus peserta atau panitia digambar dengan **area dada polos** (tanpa huruf, angka, atau simbol). Setelah gambar jadi, logo resmi `sumber/logo/logo-milklife-athletics-challenge.webp` **ditempel apa adanya** dengan skrip pasca-proses (misalnya Pillow): diperkecil sekitar 18–22% lebar dada, diberi sedikit perspektif/rotasi mengikuti arah badan, tanpa digambar ulang, tanpa diubah warnanya, dan tidak tertutup lengan. Bila dada tidak terlihat jelas (badan menyamping atau terlalu kecil), logo tidak ditempel. Skrip penempelan tidak di-commit; langkahnya ditulis di `desain/catatan/<ID>.md`.

## Selesai bila

Enam berkas sesuai tabel, logo tertempel rapi di kaus yang dadanya terlihat, dan `desain/catatan/G10-01.md` memuat deskripsi tiap tokoh untuk dipakai ulang di prompt G10 berikutnya.
