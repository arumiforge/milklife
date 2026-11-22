# F11-04 Cara pencatatan waktu

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Admin memilih cara pencatatan waktu per nomor, dan waktu tangan dibulatkan sesuai TR 19 (K172 butir 5).

## Hasil

- [ ] Nomor berjenis waktu punya pilihan Tangan atau Elektronik (bawaan Elektronik, perilaku lama tetap).
- [ ] Waktu tangan dibulatkan ke atas ke 0,1 detik saat disimpan dan ditulis dengan satu angka desimal di hasil, PDF, Excel, lembar juri, dan videotron.
- [ ] Lembar hasil menandai nomor yang memakai waktu tangan.
- [ ] Prestasi yang sudah tersimpan tidak diubah.

## Berkas dan uji

`Hasil/Prestasi`, `NomorLombaModel`, formulir nomor lomba, `Hasil/LembarJuri`, tampilan hasil. Uji `--filter HasilPrestasiPeringkat`, `NomorLombaAturan`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Uji pembulatan 12,34 menjadi 12,4 dan tampilan satu desimal lulus. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
