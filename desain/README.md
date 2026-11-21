# Desain

Folder kerja aset gambar Tahap 9 (K140). Gambar dibuat **Codex** dari brief di `tugas/`, ditinjau dan digabung Claude, lalu dipakai halaman oleh tugas Claude yang disebut di tiap brief. Aturan Codex ada di [`AGENTS.md`](../AGENTS.md); rencana lengkap di [`dokumen/draf/rencana-implementasi-tahap-9.md`](../dokumen/draf/rencana-implementasi-tahap-9.md).

| Folder | Isi | Ditulis oleh |
|---|---|---|
| `tugas/` | Brief satu tugas gambar per berkas, `G<tahap>-<urut>-<slug>.md` | Claude |
| `catatan/` | Catatan pembuatan per tugas: berkas, ukuran, ringkasan prompt | Codex |
| `../public/aset/gambar/<folder di brief>/` | Gambar jadi yang dipakai web | Codex |

Status tugas gambar hanya dicatat di `agent/PROGRES.md` oleh koordinator.

## Urutan

| Tugas | Isi | Dipakai oleh | Perlu sebelum |
|---|---|---|---|
| [G9-01](tugas/G9-01-latar-bib.md) | Latar nomor atlet (BIB), empat pilihan | F9-16 (pilihan latar bawaan di Cetak BIB) | 5 Desember |
| [G9-02](tugas/G9-02-ilustrasi-halaman-depan.md) | Ilustrasi pembuka dan latar bagian halaman depan | F9-03 | 25 November |
| [G9-03](tugas/G9-03-panduan-pendaftaran.md) | Panduan bergambar langkah pendaftaran | F9-04 | 25 November |
| [G9-04](tugas/G9-04-keadaan-kosong-galat.md) | Keadaan kosong, galat, berhasil | F9-17 | 4 Desember |
| [G9-05](tugas/G9-05-ikon-nomor-lomba.md) | Ilustrasi nomor lomba dan kelompok umur | F9-03, F9-07, F9-12 | 27 November |
| [G9-06](tugas/G9-06-videotron-podium.md) | Latar videotron, podium, medali | F9-13 | 2 Desember |
| [G9-07](tugas/G9-07-bantuan-kategori.md) | Ilustrasi kategori Bantuan dan FAQ | F9-04 | 25 November |

## Tinjauan PR gambar (untuk Claude)

1. Hanya berkas di folder keluaran brief dan `desain/catatan/<ID>.md` yang berubah.
2. Nama, ukuran piksel, format, dan besar berkas sesuai brief (`python3 -c "from PIL import Image; …"` atau `identify`).
3. Tidak ada teks, angka, logo, merek, atau wajah realistis; gaya 3D lembut dan palet sesuai.
4. Commit atas nama Arumi Studios tanpa atribusi alat AI.
5. CI hijau, lalu digabung *squash* (K126). Gambar yang tidak sesuai diminta ulang lewat komentar PR, menyebut berkas dan alasannya.
