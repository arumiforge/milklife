# F11-01 Ganti atlet oleh admin

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Admin bisa mengganti atlet di pendaftaran Lunas dengan atlet lain dari sekolah yang sama sebelum babak nomornya dikunci, supaya atlet pengganti di hari lomba tercatat resmi (K172 butir 1).

## Hasil

- [ ] Tombol Ganti atlet di rincian pendaftaran Lunas membuka formulir data atlet baru (nama, NISN, tanggal lahir, jenis kelamin).
- [ ] Atlet baru dari sekolah yang sama, kelompok umur dan jenis kelamin cocok dengan nomor yang diwariskan, NISN belum terdaftar (K06).
- [ ] Nomor lomba, regu, dan status inti/cadangan atlet lama diwariskan; total biaya tidak berubah, pendaftaran tetap Lunas.
- [ ] Atlet baru mendapat BIB baru; BIB atlet lama tidak dipakai ulang (K11).
- [ ] Penggantian ditolak bila babak nomor itu sudah dikunci atau sudah punya hasil atlet lama.
- [ ] Riwayat tindakan admin mencatat siapa diganti siapa; atlet lama disimpan seperti atlet ditolak (K116) agar NISN-nya bisa didaftarkan lagi.

## Berkas dan uji

`Panel/` (pustaka baru `GantiAtlet`), `Admin/PendaftaranController`, `admin/pendaftaran/rincian.php`, `Panel/RiwayatAdmin`. Uji `vendor/bin/phpunit --filter GantiAtlet`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Uji basis data dan fitur lulus; atlet pengganti tampil di daftar peserta, seri, dan lembar juri. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
