# F11-07 Nomor acara

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Setiap babak punya nomor acara (Event 101, 102, dan seterusnya) seperti lembar Seri 1, untuk pembawa acara, juri, dan lembar hasil.

## Hasil

- [ ] Nomor acara diisi otomatis dari urutan jadwal (tanggal dan jam) dengan nomor awal yang diatur admin, dan bisa diubah per babak.
- [ ] Tampil di jadwal, daftar seri, lembar juri, halaman hasil, PDF, dan Excel.

## Berkas dan uji

`babak` (migrasi), `Hasil/JadwalPertandingan`, `Hasil/SeriLintasan`, `Hasil/LembarJuri`, tampilan hasil. Uji `--filter JadwalPertandingan`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Nomor acara tampil di semua dokumen hari lomba. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
