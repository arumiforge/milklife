# F9-10 Kalkulator poin multi-lomba

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Poin Trilomba dihitung web dari prestasi mentah dengan tabel resmi panitia, tidak lagi dihitung tangan.

## Hasil

- [x] Alat `data/alat/` mengubah `sumber/tabel-skor/*.pdf` menjadi `data/tabel-skor/*.json` (Trilomba O2SN SMP: 60 m, lompat jauh, tolak peluru; Pancalomba SMA/SMK: 100 m, 800 m, lompat jauh, tolak peluru, lempar lembing; putra dan putri) dengan uji sampel baris
- [x] Tabel `tabel_skor`, `tabel_skor_baris`; perintah `tabel-skor:impor`; `data:awal` mengisinya
- [x] Jenis hasil **Poin multi-lomba** di Pengaturan > Nomor lomba: pilih tabel dan komponen; Trilomba data awal memakai tabel Trilomba
- [x] Isian prestasi per komponen di hasil (lembar juri F9-09 memakai `TabelSkor::hitung()` yang sama); poin per komponen dan total otomatis; prestasi di antara baris memakai baris yang lebih buruk; di luar tabel = 0 dengan peringatan
- [x] Admin bisa mengubah baris tabel dan mengimpor tabel dari Excel
- [x] `agent/ATURAN.md` (K124 butir 3) dan rancangan final diperbarui

## Berkas dan uji

`data/alat/`, `data/tabel-skor/`, migrasi, `app/Libraries/Hasil/TabelSkor.php` (murni), `Admin/Pengaturan/TabelSkorController`, `Hasil/JenisHasil.php`, views. Uji baru `HasilTabelSkorTest` (unit, mencocokkan contoh dari PDF), `TabelSkorTest`.

## Selesai bila

Contoh: putra 60 m 7,00 → 882 poin, dan baris sampel lain dari PDF cocok; Trilomba contoh menghasilkan total dan peringkat benar; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 7b, menggantikan K124 butir 3. `sumber/` tidak diubah.
