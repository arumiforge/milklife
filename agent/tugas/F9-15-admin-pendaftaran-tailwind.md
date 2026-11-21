# F9-15 Halaman admin pendaftaran ke Tailwind

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P2 |

## Tujuan

Halaman admin untuk urusan pendaftaran memakai tampilan baru.

## Hasil

- [ ] Pindah: pendaftaran (daftar, rincian, ubah, tolak atlet, riwayat), bukti bayar, atlet, nomor dada, atlet ditolak, sekolah (usulan, nama desa)
- [ ] Uji yang membaca kelas Bootstrap diganti membaca struktur atau atribut `data-*`

## Berkas dan uji

`app/Views/admin/{pendaftaran,atlet,atlet_ditolak,sekolah}/`, JS admin terkait. Uji: pola panel pendaftaran, tolak/gabung, nomor dada, sekolah di `agent/PETA-KODE.md`.

## Selesai bila

Semua halaman tersebut berjalan di peramban tanpa galat konsol/CSP; semua uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. Bisa dipecah dua PR bila terlalu besar; tetap satu tugas.
