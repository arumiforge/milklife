# F11-05 Kode NM dan r

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Hasil lompat dan lempar bisa ditulis NM (tanpa percobaan sah) dan r (mundur), dan DQ bisa disertai pasal, sesuai CR 25 (temuan M7).

## Hasil

- [ ] Keterangan NM: tanpa peringkat; dipilih otomatis bila semua percobaan X.
- [ ] Keterangan r: prestasi sebelum mundur tetap dinilai dan diberi peringkat.
- [ ] Isian pasal pendek untuk DQ (misalnya TR16.8) tampil di hasil dan PDF.
- [ ] Lembar juri, halaman hasil, PDF, dan Excel mengenali kode baru.

## Berkas dan uji

`Hasil/Peringkat::KETERANGAN`, `Hasil/HasilLomba`, `Hasil/LembarJuri`, `Hasil/Percobaan`, tampilan hasil. Uji `--filter HasilPrestasiPeringkat`, `LembarJuri`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Uji peringkat dengan NM dan r lulus. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
