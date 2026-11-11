# F1-02 Migrasi model data

| | |
|---|---|
| Status | selesai |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Semua tabel domain pada rancangan bagian 11 bisa dibuat dan dihapus dengan migrasi, dan keunikan NISN, nomor dada, serta ID pendaftaran dijaga basis data.

Acuan: rancangan bagian 6 dan 11; rencana implementasi Tahap 1 bagian "Basis data". Tabel `sekolah` (F1-03) dan `admin` (F1-04) bukan bagian tugas ini.

## Hasil

Migrasi dengan awalan `2026-11-11-001000_` sampai `2026-11-11-001999_`, satu berkas per tabel, urut sesuai ketergantungan:

- [x] `kelompok_umur`: `nama`, `usia_minimal` dan `usia_maksimal` (boleh kosong, K12), `biaya_individu` (boleh kosong), `urutan`, `aktif`
- [x] `awalan_nomor_dada`: `kelompok_umur_id`, `awalan` (1 karakter, unik), `urutan`, `nomor_terakhir` (awal 0)
- [x] `nomor_lomba`: `kelompok_umur_id`, `nama`, `jenis` (`individu`/`beregu`), `kategori` (`putra`/`putri`/`mix`), `jumlah_inti`, `batas_anggota`, `jumlah_putra`, `jumlah_putri`, `biaya_beregu`, `urutan`, `aktif`
- [x] `pendaftaran`: `id_pendaftaran` (unik, boleh kosong sampai dibuat dari `id`), `jenjang` (`A`/`B`/`C`), `npsn` (boleh kosong, berindeks, bukan kunci tamu), `nama_sekolah`, `kabupaten`, `subtotal_individu`, `subtotal_beregu`, `total`, `status` (`menunggu`/`lunas`), `waktu_lunas`
- [x] `penanggung_jawab`: `pendaftaran_id`, `nama`, `nomor_wa`, `foto`
- [x] `atlet`: `pendaftaran_id`, `nama`, `nisn` (10 karakter, unik), `tanggal_lahir`, `jenis_kelamin` (`L`/`P`), `kelompok_umur_id`, `nomor_dada` (4 karakter, unik, boleh kosong)
- [x] `atlet_nomor`: `atlet_id`, `nomor_lomba_id`, `biaya` (boleh kosong untuk beregu); pasangan atlet–nomor unik
- [x] `pendaftaran_beregu`: `pendaftaran_id`, `nomor_lomba_id`, `biaya_tim`; pasangan pendaftaran–nomor unik
- [x] `jadwal`: `judul`, `keterangan`, `waktu`, `tempat`, `tautan`, `urutan`, `tampil`
- [x] `unduhan`: `judul`, `keterangan`, `berkas`, `urutan`, `tampil`
- [x] `tautan`: `jenis` (`narahubung`/`media_sosial`), `label`, `nilai`, `urutan`
- [x] Semua tabel punya `id`, `dibuat_pada`, `diubah_pada`
- [x] Kunci tamu: menghapus pendaftaran ikut menghapus penanggung jawab, atlet, nomor atlet, dan beregu miliknya; kelompok umur dan nomor lomba yang masih dipakai tidak bisa dihapus
- [x] Tabel `settings` terbentuk oleh migrasi pustaka saat `php spark migrate --all`
- [x] Uji `tests/basisdata/Migrasi*Test.php`: semua tabel dan kolom ada; NISN ganda ditolak; nomor dada ganda ditolak tetapi banyak nomor dada kosong diterima; ID pendaftaran ganda ditolak; hapus berantai berjalan; `migrate:rollback` membersihkan semuanya

## Selesai bila

`php spark migrate --all` dan `php spark migrate:rollback` berjalan bersih di MySQL 8, dan `composer uji` lulus.

## Catatan

- Uang disimpan sebagai bilangan bulat rupiah tak bertanda.
- Tanpa data awal: kelompok umur dan nomor lomba diisi di Tahap 2 setelah asumsi 2 dan 3 (rancangan bagian 17) dikonfirmasi.
- Kelas model untuk tabel-tabel ini dibuat di tahap yang memakainya.
- Pilihan tetap (`jenis`, `kategori`, `status`, `jenjang`, `jenis_kelamin`, `tautan.jenis`) memakai `ENUM`: nilai di luar daftar ditolak basis data dalam mode ketat, tanpa tabel atau pemeriksaan tambahan. Akibatnya, menambah pilihan baru perlu migrasi; untuk pilihan-pilihan ini hal itu memang diharapkan karena nilainya tetap dari rancangan.
- `kabupaten` di `pendaftaran` berupa `VARCHAR(50)`, bukan `ENUM`, karena wilayah bisa berbeda antar-seri.
- Uang: `INT UNSIGNED` (sampai sekitar 4,29 miliar rupiah), sehingga nilai negatif ditolak. Uang yang wajib ada (`subtotal_individu`, `subtotal_beregu`, `total`, `biaya_tim`) tidak punya nilai awal, supaya lupa mengisi langsung ketahuan; `biaya_individu`, `biaya_beregu`, dan `atlet_nomor.biaya` boleh kosong.
- Batas usia `TINYINT UNSIGNED` dalam tahun penuh; `urutan` dan `nomor_terakhir` `SMALLINT UNSIGNED`; `aktif`/`tampil` `TINYINT UNSIGNED` bernilai awal 1 (MySQL 8 tidak menampilkan lebar `(1)` pada kolom tak bertanda).
- `awalan_nomor_dada` → `kelompok_umur` memakai RESTRICT, sama dengan rujukan lain ke `kelompok_umur`: catatan `nomor_terakhir` tidak boleh hilang tanpa sengaja. Menghapus kelompok umur yang belum dipakai berarti menghapus awalannya dulu (Tahap 2 bisa melakukannya dalam satu transaksi).
- ON UPDATE RESTRICT pada semua kunci tamu, karena `id` tidak pernah diubah.
- `id_pendaftaran` `VARCHAR(20)` (formatnya 12 karakter); `npsn` `CHAR(8)` sesuai tabel `sekolah` di F1-03, berindeks, bukan kunci tamu.
- `penanggung_jawab.foto` boleh kosong (ditampilkan sebagai ikon "tanpa gambar"); kewajiban foto diperiksa aplikasi.
- `jadwal.waktu` `DATETIME` boleh kosong; `keterangan` di `jadwal` dan `unduhan` `TEXT`.
- Aturan "satu tim per nomor beregu per sekolah" lintas pendaftaran tidak bisa dijaga basis data karena NPSN boleh kosong; basis data hanya menjaga satu tim per nomor dalam satu pendaftaran. Sisanya dijaga aplikasi di Tahap 3.
- Berkas migrasi berjarak 10 (`001000`, `001010`, …, `001100`) supaya ada ruang bila nanti perlu migrasi di antaranya.
- Uji: `tests/basisdata/MigrasiStrukturTest.php` (tabel, kolom, tipe, nilai awal, mesin dan kolasi, kunci tamu, indeks, rollback penuh) dan `tests/basisdata/MigrasiAturanDataTest.php` (keunikan, hapus berantai, larangan hapus, nilai awal, uang negatif, pilihan di luar daftar).
- Digabung lewat PR #4 pada 11 November 2026.
