# F5-03 Bukti pendaftaran PDF

| | |
|---|---|
| Status | rencana |
| Tahap | 5 — Bukti dan cek |
| Dibuat | 12 November 2026 |

## Tujuan

Official bisa mengunduh bukti pendaftaran PDF A4 mendatar di `/bukti/{id-pendaftaran}`.

## Hasil

- [ ] Rute, pengendali, dan pustaka `BuktiPendaftaran`
- [ ] Kepala (logo, nama acara, seri, tahun, empat baris kepala), penanggung jawab, tabel individu, tabel beregu dengan anggota, total, status (K49)
- [ ] Nama berkas dari pengaturan acara (K49)
- [ ] Pembatasan laju (kunci `bukti-`), 404 untuk ID yang tidak ada
- [ ] `tests/fitur/BuktiPendaftaranTest.php`

## Selesai bila

`composer uji` dan CI lulus; PDF contoh dengan data karangan diperiksa di luar repo.

## Catatan

Rincian di `dokumen/draf/rencana-implementasi-tahap-5.md`.
