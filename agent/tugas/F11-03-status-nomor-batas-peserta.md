# F11-03 Status nomor dan batas peserta

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Panitia bisa menandai nomor "Tidak dilombakan" dan diberi peringatan bila peserta di bawah batas, mengikuti juknis (K172 butir 4).

## Hasil

- [ ] Nomor lomba punya status "Tidak dilombakan" dengan alasan singkat; nomor itu keluar dari jadwal, seri, klasemen, dan videotron, dan halaman hasil menulis keterangannya.
- [ ] Isian batas peserta minimal per nomor (kosong = tanpa batas) memunculkan peringatan di Hasil dan Seri bila peserta Lunas atau hadir kurang dari batas.
- [ ] Medali tetap sejumlah atlet atau regu yang berprestasi; web tidak mengurangi medali otomatis.

## Berkas dan uji

`nomor_lomba` (migrasi), `admin/pengaturan/nomor_lomba/formulir.php`, `Hasil/HasilLomba`, `Hasil/JadwalPertandingan`, `Hasil/SeriLintasan`, `Hasil/Klasemen`. Uji `--filter HasilLomba`, `JadwalPertandingan`, `SeriLintasan`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Nomor bertanda tidak dilombakan hilang dari jadwal dan klasemen dengan keterangan di hasil; peringatan batas tampil. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
