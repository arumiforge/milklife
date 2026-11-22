# F11-02 Klasemen per kelompok umur

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Juara umum dihitung per kelompok umur, dengan gabungan kelompok umur yang diatur admin, menggantikan klasemen per jenjang (K172 butir 2).

## Hasil

- [ ] Pengaturan > Hasil punya isian gabungan klasemen: tiap klasemen berisi satu atau lebih kelompok umur; bawaan satu klasemen per kelompok umur.
- [ ] Klasemen admin, publik, PDF, Excel, dan videotron memakai susunan ini; metode Medali atau Poin tetap.
- [ ] Kelompok umur yang belum masuk gabungan mana pun tetap punya klasemen sendiri.
- [ ] FAQ `{cara_juara_umum}` menyebut susunan klasemen.

## Berkas dan uji

`Hasil/Klasemen`, `Hasil/HasilLomba::klasemen()`, `Hasil/MetodeKlasemen`, `Hasil/UnduhanKlasemen`, `Hasil/PapanVideotron`, `admin/pengaturan/hasil.php`. Uji `--filter Klasemen`, `PapanVideotron`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Uji klasemen per kelompok umur dan gabungan lulus; halaman `hasil/klasemen` dan `/layar` menampilkan susunan baru. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
