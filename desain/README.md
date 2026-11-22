# Desain

Folder kerja aset gambar Tahap 9 (K140), Tahap 10 (K153), dan Tahap 11 (K175). Gambar dibuat **Codex** dari brief di `tugas/`, ditinjau dan digabung Claude, lalu dipakai halaman oleh tugas Claude yang disebut di tiap brief. Aturan Codex ada di [`AGENTS.md`](../AGENTS.md); rencana lengkap di [`dokumen/draf/rencana-implementasi-tahap-10.md`](../dokumen/draf/rencana-implementasi-tahap-10.md) (Tahap 9: [`rencana-implementasi-tahap-9.md`](../dokumen/draf/rencana-implementasi-tahap-9.md)).

| Folder | Isi | Ditulis oleh |
|---|---|---|
| `referensi/` | Rujukan gaya dari pemilik: `gaya-anime-lintasan.webp` (gaya ilustrasi Tahap 10) dan `hero-editorial.webp` (tata hero foto editorial), K153; `stadion-ssa-*.webp` (foto Supersoccer Arena Kudus, rujukan bentuk stadion), K158 | Claude |
| `tugas/` | Brief satu tugas gambar per berkas, `G<tahap>-<urut>-<slug>.md` | Claude |
| `catatan/` | Catatan pembuatan per tugas: berkas, ukuran, ringkasan prompt | Codex |
| `../public/aset/gambar/<folder di brief>/` | Gambar jadi yang dipakai web | Codex |

Status tugas gambar hanya dicatat di `agent/PROGRES.md` oleh koordinator.

## Urutan Tahap 11 (halaman depan, K175)

Gaya tetap anime Tahap 10. Halaman memakai cadangan gradien sampai berkas tiba, lalu gambar langsung tampil tanpa perubahan kode.

| Tugas | Isi | Dipakai oleh | Perlu sebelum |
|---|---|---|---|
| [G11-01](tugas/G11-01-kartu-kelompok-umur.md) | Art kartu dan tekstur isian angka tiap kelompok umur (`ku/ku-<n>-art.webp`, `ku/ku-<n>-isian.webp`) | F11-12 | 28 November |
| [G11-02](tugas/G11-02-latar-hero-kiri.md) | Latar art hero kiri bertepi pudar (`beranda/hero-kiri.webp`, `beranda/hero-kiri-ponsel.webp`) | F11-12 | 28 November |

## Urutan Tahap 10 (gaya anime, K153)

Kerjakan **berurutan dari atas**. G10-01 menetapkan tokoh tetap yang dipakai ulang semua brief berikutnya. Brief pengganti G9 memakai nama berkas yang sama, jadi gambar langsung tampil setelah PR digabung.

| Tugas | Isi | Menggantikan | Perlu sebelum |
|---|---|---|---|
| [G10-01](tugas/G10-01-lembar-tokoh.md) | Lembar tokoh (lima tokoh tetap) | — | 24 November |
| [G10-11](tugas/G10-11-elemen-tombol-ornamen.md) | Latar tombol, tekstur, pemisah, ornamen judul, bingkai | — | 25 November |
| [G10-02](tugas/G10-02-ilustrasi-halaman-depan.md) | Halaman depan dan sapuan hero | G9-02 | 26 November |
| [G10-09](tugas/G10-09-pita-judul.md) | Pita judul halaman publik | G9-09 | 27 November |
| [G10-05](tugas/G10-05-ikon-nomor-lomba.md) | Nomor lomba | G9-05 | 28 November |
| [G10-04](tugas/G10-04-keadaan-kosong-galat.md) | Keadaan kosong, galat, berhasil | G9-04 | 29 November |
| [G10-03](tugas/G10-03-panduan-pendaftaran.md) | Panduan pendaftaran | G9-03 | 29 November |
| [G10-07](tugas/G10-07-bantuan-kategori.md) | Kategori Bantuan | G9-07 | 30 November |
| [G10-06](tugas/G10-06-videotron-podium.md) | Videotron dan podium | G9-06 | 2 Desember |
| [G10-08](tugas/G10-08-panel-admin.md) | Panel admin | G9-08 | 2 Desember |
| [G10-12](tugas/G10-12-pola-hiasan.md) | Pola dan hiasan latar (tanpa titik) | G9-12 | 2 Desember |
| [G10-10](tugas/G10-10-gambar-bagikan.md) | Gambar bagikan | G9-10 | 3 Desember |
| [G10-13](tugas/G10-13-stadion-ssa.md) | Stadion Supersoccer Arena (enam gambar berlatar stadion digambar ulang) | berkas G10-02, G10-04, G10-09, G10-10, G10-12 | 3 Desember |

## Urutan Tahap 9 (selesai, digantikan G10)

| Tugas | Isi | Dipakai oleh | Perlu sebelum |
|---|---|---|---|
| [G9-01](tugas/G9-01-latar-bib.md) | Latar nomor atlet (BIB), empat pilihan | F9-16 (pilihan latar bawaan di Cetak BIB) | 5 Desember |
| [G9-02](tugas/G9-02-ilustrasi-halaman-depan.md) | Ilustrasi pembuka dan latar bagian halaman depan | F9-03 | 25 November |
| [G9-03](tugas/G9-03-panduan-pendaftaran.md) | Panduan bergambar langkah pendaftaran | F9-04 | 25 November |
| [G9-04](tugas/G9-04-keadaan-kosong-galat.md) | Keadaan kosong, galat, berhasil | F9-17 | 4 Desember |
| [G9-05](tugas/G9-05-ikon-nomor-lomba.md) | Ilustrasi nomor lomba dan kelompok umur | F9-03, F9-07, F9-12 | 27 November |
| [G9-06](tugas/G9-06-videotron-podium.md) | Latar videotron (potongan untuk layar ukuran apa pun), podium, medali | F9-13 | 2 Desember |
| [G9-07](tugas/G9-07-bantuan-kategori.md) | Ilustrasi kategori Bantuan dan FAQ | F9-04 | 25 November |
| [G9-08](tugas/G9-08-panel-admin.md) | Halaman masuk dan panel admin | F9-19 | 1 Desember |
| [G9-09](tugas/G9-09-pita-judul.md) | Pita judul halaman publik | F9-19 | 1 Desember |
| [G9-10](tugas/G9-10-gambar-bagikan.md) | Gambar bagikan (Open Graph) | F9-19 | 1 Desember |
| [G9-11](tugas/G9-11-ikon-aplikasi.md) | Ikon aplikasi dan favicon (**tidak dipakai**: diganti logo resmi pemilik, K152) | F9-19 | — |
| [G9-12](tugas/G9-12-pola-hiasan.md) | Pola dan hiasan latar | F9-19 | 3 Desember |

## Tinjauan PR gambar (untuk Claude)

1. Hanya berkas di folder keluaran brief dan `desain/catatan/<ID>.md` yang berubah.
2. Nama, ukuran piksel, format, dan besar berkas sesuai brief (`python3 -c "from PIL import Image; …"` atau `identify`).
3. Tidak ada teks, angka, merek, atau wajah realistis yang digambar model; logo hanya hasil tempel berkas resmi bila brief memintanya (K153).
4. Gaya anime 2D cel-shaded dan palet sesuai `AGENTS.md`; tokoh konsisten dengan G10-01; tidak meniru tokoh atau adegan anime lain; tanpa latar ikon kotak/lingkaran, bingkai penuh berwarna, dan titik hiasan.
5. Commit atas nama Arumi Studios tanpa atribusi alat AI.
6. CI hijau, lalu digabung *squash* (K126). Gambar yang tidak sesuai diminta ulang lewat komentar PR, menyebut berkas dan alasannya.
