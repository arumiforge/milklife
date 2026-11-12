# F6-05 Konfirmasi tim susulan di wizard

| | |
|---|---|
| Status | rencana |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Official yang mendaftar susulan menyatakan sendiri bahwa atletnya menjadi tambahan anggota tim sekolah yang sudah terdaftar, tanpa biaya tim (K57).

## Hasil

- [ ] Kotak centang per tim susulan di daftar sementara langkah 3: "Saya mengerti atlet ini ditambahkan ke tim <nomor> sekolah yang sudah terdaftar (<n> atlet), tanpa biaya tim"
- [ ] Lanjut ke langkah 4 ditolak di peramban bila ada yang belum dicentang, dengan pesan yang menyebut timnya
- [ ] Konfirmasi tersimpan di draf; draf lama tanpa konfirmasi tetap terbaca (kotak belum tercentang)
- [ ] Kiriman `periksa` dan `simpan` membawa daftar ID nomor lomba yang dikonfirmasi; `PemeriksaPendaftaran` menolak tim susulan yang belum dikonfirmasi dengan galat langkah 3. Parameter bersifat pilihan supaya pemeriksaan admin (F6-04) tidak memintanya
- [ ] Tampil rapi di ponsel 390 piksel

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: simpan susulan tanpa konfirmasi ditolak, dengan konfirmasi diterima, pendaftaran tanpa tim susulan tidak terpengaruh, pemeriksa tanpa parameter konfirmasi tetap berperilaku seperti sebelumnya.

## Catatan

Tidak bergantung pada tugas lain. Satu-satunya tugas Tahap 6 yang mengubah wizard dan `PemeriksaPendaftaran`.
