# F11-01 Ganti atlet oleh admin

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Admin bisa mengganti atlet di pendaftaran Lunas dengan atlet lain dari sekolah yang sama sebelum babak nomornya dikunci, supaya atlet pengganti di hari lomba tercatat resmi (K172 butir 1).

## Hasil

- [x] Tombol Ganti atlet di rincian pendaftaran Lunas membuka formulir data atlet baru (nama, NISN, tanggal lahir); jenis kelamin mengikuti atlet lama.
- [x] Atlet baru dari sekolah yang sama, kelompok umur dan jenis kelamin cocok dengan nomor yang diwariskan, NISN belum terdaftar (K06).
- [x] Nomor lomba, regu, dan status inti/cadangan atlet lama diwariskan; total biaya tidak berubah, pendaftaran tetap Lunas.
- [x] Atlet baru mendapat BIB baru; BIB atlet lama tidak dipakai ulang (K11).
- [x] Penggantian ditolak bila atlet lama masuk seri babak yang sudah dikunci atau sudah punya hasil; untuk nomor beregu, bila regunya sudah punya hasil. Tempat atlet lama di seri yang belum dikunci pindah ke atlet baru.
- [x] Riwayat tindakan admin mencatat siapa diganti siapa; atlet lama disimpan di tabel `atlet_diganti` (bukan `atlet_ditolak`, supaya tidak tampil sebagai "ditolak" di Cek Pendaftaran) dan NISN-nya bisa didaftarkan lagi.

## Berkas dan uji

`Panel/GantiAtlet` (baru), migrasi `atlet_diganti`, `Admin/PendaftaranController`, `admin/pendaftaran/rincian.php`, `Panel/RiwayatAdmin`. Uji `vendor/bin/phpunit --filter GantiAtlet` dan `MigrasiStruktur`. Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Uji basis data dan fitur lulus; atlet pengganti tampil di daftar peserta, seri, dan lembar juri. `composer uji` lulus.

## Catatan

Dasar: `dokumen/draf/telaah-hasil-seri-1.md` bagian 5, K172. Setiap PR aman langsung dipasang di produksi (K139).
