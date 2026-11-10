# F1-02 Migrasi model data

| | |
|---|---|
| Status | rencana |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Semua tabel domain pada rancangan bagian 11 bisa dibuat dan dihapus dengan migrasi, dan keunikan NISN, nomor dada, serta ID pendaftaran dijaga basis data.

Acuan: rancangan bagian 6 dan 11; rencana implementasi Tahap 1 bagian "Basis data". Tabel `sekolah` (F1-03) dan `admin` (F1-04) bukan bagian tugas ini.

## Hasil

Migrasi dengan awalan `2026-11-11-001000_` sampai `2026-11-11-001999_`, satu berkas per tabel, urut sesuai ketergantungan:

- [ ] `kelompok_umur`: `nama`, `usia_minimal` dan `usia_maksimal` (boleh kosong, K12), `biaya_individu` (boleh kosong), `urutan`, `aktif`
- [ ] `awalan_nomor_dada`: `kelompok_umur_id`, `awalan` (1 karakter, unik), `urutan`, `nomor_terakhir` (awal 0)
- [ ] `nomor_lomba`: `kelompok_umur_id`, `nama`, `jenis` (`individu`/`beregu`), `kategori` (`putra`/`putri`/`mix`), `jumlah_inti`, `batas_anggota`, `jumlah_putra`, `jumlah_putri`, `biaya_beregu`, `urutan`, `aktif`
- [ ] `pendaftaran`: `id_pendaftaran` (unik, boleh kosong sampai dibuat dari `id`), `jenjang` (`A`/`B`/`C`), `npsn` (boleh kosong, berindeks, bukan kunci tamu), `nama_sekolah`, `kabupaten`, `subtotal_individu`, `subtotal_beregu`, `total`, `status` (`menunggu`/`lunas`), `waktu_lunas`
- [ ] `penanggung_jawab`: `pendaftaran_id`, `nama`, `nomor_wa`, `foto`
- [ ] `atlet`: `pendaftaran_id`, `nama`, `nisn` (10 karakter, unik), `tanggal_lahir`, `jenis_kelamin` (`L`/`P`), `kelompok_umur_id`, `nomor_dada` (4 karakter, unik, boleh kosong)
- [ ] `atlet_nomor`: `atlet_id`, `nomor_lomba_id`, `biaya` (boleh kosong untuk beregu); pasangan atlet–nomor unik
- [ ] `pendaftaran_beregu`: `pendaftaran_id`, `nomor_lomba_id`, `biaya_tim`; pasangan pendaftaran–nomor unik
- [ ] `jadwal`: `judul`, `keterangan`, `waktu`, `tempat`, `tautan`, `urutan`, `tampil`
- [ ] `unduhan`: `judul`, `keterangan`, `berkas`, `urutan`, `tampil`
- [ ] `tautan`: `jenis` (`narahubung`/`media_sosial`), `label`, `nilai`, `urutan`
- [ ] Semua tabel punya `id`, `dibuat_pada`, `diubah_pada`
- [ ] Kunci tamu: menghapus pendaftaran ikut menghapus penanggung jawab, atlet, nomor atlet, dan beregu miliknya; kelompok umur dan nomor lomba yang masih dipakai tidak bisa dihapus
- [ ] Tabel `settings` terbentuk oleh migrasi pustaka saat `php spark migrate --all`
- [ ] Uji `tests/basisdata/Migrasi*Test.php`: semua tabel dan kolom ada; NISN ganda ditolak; nomor dada ganda ditolak tetapi banyak nomor dada kosong diterima; ID pendaftaran ganda ditolak; hapus berantai berjalan; `migrate:rollback` membersihkan semuanya

## Selesai bila

`php spark migrate --all` dan `php spark migrate:rollback` berjalan bersih di MySQL 8, dan `composer uji` lulus.

## Catatan

- Uang disimpan sebagai bilangan bulat rupiah tak bertanda.
- Tanpa data awal: kelompok umur dan nomor lomba diisi di Tahap 2 setelah asumsi 2 dan 3 (rancangan bagian 17) dikonfirmasi.
- Kelas model untuk tabel-tabel ini dibuat di tahap yang memakainya.
