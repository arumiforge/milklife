# F11-06 Rekor kejuaraan

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Web mencatat rekor kejuaraan MilkLife mulai Seri 2 dan menandai rekor baru di hasil (K172 butir 3).

## Hasil

- [ ] Tabel rekor per nomor lomba, kelompok umur, jenis kelamin, berat alat (teks), dan cara pencatatan waktu; berisi prestasi, atlet, sekolah, seri, dan tanggal.
- [ ] Saat hasil final diterbitkan, prestasi yang melampaui rekor menjadi rekor baru dan ditandai CR di hasil dan videotron; menarik terbitan mengembalikan rekor lama.
- [ ] Admin bisa mengisi atau mengoreksi rekor dengan tangan; hasil Seri 1 tidak diimpor.
- [ ] Halaman Rekor publik bersakelar bawaan mati; ikut `PaketPendaftaran`.

## Berkas dan uji

Migrasi tabel rekor, pustaka `Hasil/Rekor`, `Admin/HasilController`, pengendali publik baru, `PapanVideotron`. Uji `--filter Rekor`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Rekor baru terdeteksi dan dibatalkan dengan benar di uji fitur. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
