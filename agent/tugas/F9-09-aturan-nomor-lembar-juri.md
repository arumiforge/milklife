# F9-09 Aturan nomor dan lembar juri

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Juri di lapangan mengisi prestasi cepat dari ponsel, dan peringkat mengikuti aturan percobaan dan pemecah seri yang diatur.

## Hasil

- [ ] Per nomor lompat/lempar: jumlah percobaan (bawaan 3), prestasi terbaik dipakai, pemecah seri prestasi kedua (bisa dimatikan)
- [ ] Lembar juri `admin/juri`: pilih babak dan seri, baris besar per atlet, papan angka ponsel, simpan otomatis per baris dengan tanda tersimpan, aman dipakai beberapa admin di babak berbeda
- [ ] Peringkat dan Terbitkan tetap di halaman hasil (F8-04)

## Berkas dan uji

Migrasi kolom percobaan, `app/Libraries/Hasil/Prestasi.php`, `Peringkat.php`, `Admin/JuriController`, `public/aset/js/lembar-juri.js`, views. Uji: `HasilPrestasiPeringkatTest`, uji baru `LembarJuriTest`.

## Selesai bila

Lembar juri dipakai di ponsel 360 piksel untuk satu seri contoh; peringkat dengan pemecah seri benar; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 7c dan 7d; akun admin yang sama.
