# F9-07 Jadwal pertandingan

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Siswa, ofisial, dan penonton melihat jadwal lomba per jam yang selalu sama dengan babak di panel hasil.

## Hasil

- [ ] Kolom `tanggal`, `jam_mulai`, `lokasi` di `babak`, diisi di halaman hasil admin dan di halaman Jadwal pertandingan admin (isi berurutan semua babak)
- [ ] `/jadwal`: tab per hari, kolom waktu, nomor, putra/putri, kelompok umur, babak (seperti `contohjadwal1.jpg`), status Belum mulai/Berlangsung/Selesai, tautan hasil; ikon G9-05 bila ada
- [ ] Unduhan jadwal PDF per hari
- [ ] Agenda lama (`jadwal` pendaftaran) tetap, diberi judul "Agenda"; sakelar tampil jadwal pertandingan (bawaan mati)

## Berkas dan uji

Migrasi kolom `babak`, `app/Libraries/Hasil/` (jadwal), `JadwalController`, `Admin/JadwalPertandinganController`, views. Uji baru `JadwalPertandinganTest`; `HasilLombaTest` tetap lulus.

## Selesai bila

`/jadwal` tampil dengan data babak contoh di ponsel dan komputer; PDF terunduh; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 9.
