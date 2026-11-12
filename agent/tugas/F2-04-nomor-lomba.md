# F2-04 Pengaturan nomor lomba

| | |
|---|---|
| Status | selesai |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengelola nomor lomba per kelompok umur, termasuk nomor beregu dan mix.

## Hasil

- [x] Daftar nomor lomba dikelompokkan atau bisa disaring per kelompok umur, dengan jenis, kategori, biaya beregu, dan aktif
- [x] Tambah dan ubah: kelompok umur, nama, jenis (individu/beregu), kategori (putra/putri/mix); untuk beregu: jumlah inti, batas anggota (tidak kurang dari inti), biaya beregu; untuk mix: jumlah putra dan putri minimal (jumlahnya tidak lebih dari inti); urutan, aktif
- [x] Nomor individu tidak boleh berkategori mix; isian tim dikosongkan untuk nomor individu
- [x] Kelompok umur nomor lomba yang sudah dipakai tidak bisa dipindah; hapus sesuai aturan rencana
- [x] `NomorLombaModel`, uji fitur dan uji basis data

## Selesai bila

`composer uji` lulus; nomor lomba dari `data:awal` tampil rapi dan bisa dilengkapi.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Keputusan kecil saat pengerjaan

- **Nomor beregu wajib lengkap bila disimpan lewat formulir** (arahan koordinator): jumlah inti, batas anggota, dan biaya beregu; nomor mix juga jumlah putra dan putri minimal. Nomor beregu dari `data:awal` yang belum lengkap tetap tersimpan apa adanya dan ditandai "Belum lengkap: … belum diisi." di daftar, dengan tombol **Lengkapi** dan ringkasan jumlahnya di atas daftar. Formulir ubahnya menyebut isian yang belum diisi.
- **Arti "belum lengkap"** ditulis sekali di `NomorLombaModel::kekuranganTim()`: nomor beregu tanpa jumlah inti, batas anggota, atau biaya beregu, dan nomor mix tanpa jumlah putra atau putri. Sama dengan rancangan 6.1; F2-09 perlu memakai aturan yang sama (dengan pembangun kueri).
- **Batas angka**: jumlah inti 2–50, batas anggota sampai 50 dan tidak kurang dari inti (sama dengan inti berarti tanpa cadangan), jumlah putra dan putri minimal masing-masing 1–50 dan jumlahnya tidak lebih dari inti, biaya beregu lebih dari 0 (diketik seperti uang lain lewat `angka_rupiah()`), urutan 0–9999.
- **Isian yang tidak berlaku dibuang server**: nomor individu menyimpan semua kolom tim sebagai `NULL`; nomor beregu putra/putri menyimpan jumlah putra dan putri sebagai `NULL`, walau kotaknya ikut terkirim.
- **Nomor yang sudah dipakai** atlet (`atlet_nomor`) atau tim (`pendaftaran_beregu`): selain kelompok umur, **jenis dan kategorinya juga dikunci**, karena mengubahnya merusak biaya dan isi pendaftaran yang sudah ada. Di formulir ketiganya tampil terkunci (nilainya dikirim lewat isian tersembunyi) dengan keterangan; kiriman yang tetap mengubahnya ditolak per kotak. Nama, isian tim, urutan, dan aktif tetap bisa diubah. Tombol hapus tidak tampil untuk nomor yang dipakai; server tetap memeriksa dan menolak dengan `pesan_galat`.
- **Nama kembar ditolak**: nama yang sama (huruf besar-kecil diabaikan) dengan kategori yang sama di kelompok umur yang sama. Nama nomor ditulis tanpa kategori, seperti data awal (`Lari 60 m` + kategori `putra`).
- **Urutan kosong**: nomor baru ditaruh paling akhir di kelompok umurnya (urutan terbesar + 1); saat diubah tetap di tempatnya, atau paling akhir bila dipindah ke kelompok umur lain.
- **Kelompok umur tidak aktif** tetap bisa dipilih (ditandai "tidak aktif"), supaya nomor bisa disiapkan sebelum kelompok umurnya dinyalakan. Kelompok umur dibaca dengan `db_connect()->table('kelompok_umur')` di pengendali dan lewat `join` di `daftarLengkap()`, tanpa `KelompokUmurModel`.
- **Daftar**: satu kartu per kelompok umur (urutan kelompok umur, lalu urutan nomor), masing-masing dengan tombol "Tambah nomor" yang memilihkan kelompok umurnya. Saringan `?kelompok-umur=<id>` dan `?belum-lengkap=1` lewat formulir `GET` (langsung diterapkan saat diganti; tombol Tampilkan tetap ada tanpa skrip); id kelompok umur yang tidak ada diabaikan. Di ponsel jenis, kategori, tim, dan biaya diringkas di bawah nama nomor, sehingga tabel 390 piksel tidak perlu digulir mendatar; di layar ≥ 768 piksel tampil sebagai kolom.
- **Setelah simpan** dialihkan ke daftar yang disaring ke kelompok umur nomor itu dan digulir ke barisnya (`?kelompok-umur=<id>#nomor-<id>`), dengan pesan "Nomor Lari 60 m putra di KU 10 tersimpan." Hapus berhasil kembali ke daftar kelompok umurnya.
- **Hapus** tersedia di daftar (ikon tempat sampah) dan di bagian bawah halaman ubah, keduanya dengan `data-konfirmasi`. Ubah/perbarui nomor yang tidak ada → 404; hapus nomor yang tidak ada → `pesan_galat`.
- **Tampil-sembunyi isian tim dan mix** diatur skrip kecil di dalam tampilan `formulir.php` (tidak ada berkas JS di bagian F2-04). Server sudah menandai bagian itu `hidden` sesuai isian, jadi isian salah selalu tampil dengan bagiannya. Pilihan mix dimatikan untuk nomor individu.
- **Aturan isian** ada di `NomorLombaModel` sebagai fungsi statis tanpa basis data (`rapikanIsian()`, `periksaIsian()`, `untukDisimpan()`, `isianDariBaris()`, `kekuranganTim()`), dengan uji unit, karena `app/Libraries/` di luar bagian F2-04.
- **Uji**: `tests/unit/NomorLombaAturanTest.php`, `tests/basisdata/NomorLombaModelTest.php`, `tests/fitur/PengaturanNomorLombaTest.php` (memakai `data:awal` untuk menguji 69 nomor), dengan pembuat baris uji `tests/_support/BarisUjiNomorLomba.php` (nama sekolah, atlet, dan NISN karangan).
- Belum dicegah: mengubah jumlah inti atau batas anggota nomor yang sudah dipakai, sehingga tim yang sudah terdaftar bisa tidak lagi memenuhi. Pemeriksaannya di tahap pendaftaran dan panel data (Tahap 4 dan 6).
- Digabung lewat PR #15 pada 12 November 2026.
