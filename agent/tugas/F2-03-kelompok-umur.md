# F2-03 Pengaturan kelompok umur

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengelola kelompok umur lengkap dengan batas tahun lahir (K36), jenjang yang boleh ikut (K23, K24), biaya individu, dan awalan nomor dada.

## Hasil

- [x] Daftar kelompok umur: nama, batas tahun lahir ("Lahir 2016–2018", "Lahir 2019 ke atas"), jenjang, biaya, awalan, aktif; keadaan kosong yang ramah
- [x] Tambah dan ubah: nama, tahun lahir paling awal dan paling akhir (tahun 4 angka, salah satu atau keduanya boleh kosong, awal tidak lebih dari akhir), centang SD/MI, SMP/MTs, SMA/SMK/MA, biaya individu, awalan nomor dada (angka 1–9, unik di semua kelompok umur, urutan sesuai ketikan), urutan, aktif
- [x] Rentang tahun lahir kelompok umur aktif tidak boleh tumpang tindih (batas kosong = tidak terbatas)
- [x] Awalan yang sudah dipakai (`nomor_terakhir > 0`) tidak bisa dihapus
- [x] Hapus sesuai aturan rencana, awalan dan jenjang ikut terhapus dalam satu transaksi
- [x] `KelompokUmurModel`, `AwalanNomorDadaModel`, `KelompokUmurJenjangModel`, uji fitur dan uji basis data
- [x] K36, K37: migrasi `2026-11-12-000200_UbahBatasUsiaMenjadiTahunLahir`, `data:awal` mengisi batas tahun lahir dan tidak lagi mengisi tanggal patokan usia, uji migrasi dan data awal disesuaikan

## Selesai bila

`composer uji` lulus; kelompok umur dari `data:awal` bisa dilengkapi sampai siap dipakai; semua aturan tolak dan hapus teruji.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Keputusan kecil saat pengerjaan

- **Tahun lahir (K36):** kolom `tahun_lahir_awal` (paling awal, atlet tertua) dan `tahun_lahir_akhir` (paling akhir, atlet termuda), `SMALLINT UNSIGNED` boleh kosong, diletakkan setelah `nama`. Migrasi tidak memindahkan isi kolom usia lama (selalu kosong sebelumnya dan tidak bisa diubah ke tahun lahir tanpa tanggal patokan); `down()` mengembalikan `usia_minimal`/`usia_maksimal` kosong di tempat semula.
- **Isian tahun lahir:** tahun 4 angka antara 1950 dan tahun berjalan. Keduanya boleh kosong saat disimpan (admin mengisi bertahap; kelengkapan "minimal salah satu terisi" diperiksa F2-09). Bila keduanya diisi, awal ≤ akhir.
- **Tumpang tindih:** hanya antar-kelompok umur aktif; batas kosong dianggap tak terbatas di sisi itu. Kelompok umur yang kedua batasnya kosong belum punya rentang, jadi tidak dibandingkan (bila dianggap "semua tahun", admin tidak bisa menambah kelompok umur baru tanpa langsung mengisi tahunnya). Kelompok umur seperti ini membuat pengaturan belum lengkap, jadi tidak pernah dipakai pendaftaran. Mengaktifkan kelompok umur yang bertabrakan juga ditolak. Pesan galat ditaruh di kotak tahun pertama, kedua kotak ditandai, dan menyebut kelompok umur yang ditabrak beserta tahunnya.
- **Data awal:** KU 12 disimpan 2015–2015 (tampil "Lahir 2015"), KU 8 2019 dan akhir kosong ("Lahir 2019 ke atas"). Ringkasan di layar menyebut batas tahun lahir tiap kelompok umur; baris "Patokan usia" dan kunci `Pendaftaran.tanggal_patokan_usia` (di daftar isian dan syarat tolak) dihapus. Uji `DataAwalTest` tidak lagi memeriksa nilai awal kunci itu, supaya tetap lulus sebelum dan sesudah F2-02 menghapusnya dari `Config\Pendaftaran`.
- **Awalan nomor dada** diketik dalam satu kotak, dipisah koma (juga spasi, titik koma, atau garis miring), contoh `2, 6`; urutan ketikan menjadi `urutan`. Tiap awalan tepat satu angka 1–9; `46` ditolak (bukan dibaca 4 dan 6) dan awalan ganda ditolak. Saat disimpan: awalan yang tidak ditulis lagi dihapus, yang tetap hanya diubah urutannya (`nomor_terakhir` tidak disentuh), yang baru ditambahkan dengan `nomor_terakhir` 0. Awalan milik kelompok umur lain ditolak dengan menyebut pemiliknya; memindahkan awalan berarti menghapusnya dulu dari kelompok umur lama. Formulir menampilkan awalan yang sudah dipakai kelompok umur lain dan awalan sendiri yang terkunci ("sudah dipakai sampai nomor dada 2045").
- **Awalan, jenjang, dan biaya boleh kosong saat disimpan**, sama seperti tahun lahir; kelengkapannya diperiksa F2-09. Biaya 0 diterima. Biaya memakai `angka_rupiah()` (titik dan `Rp` dibuang).
- **Nama** wajib, paling banyak 50 huruf (sesuai kolom), spasi ganda dirapikan, dan tidak boleh sama dengan kelompok umur lain (tanpa membedakan huruf besar-kecil).
- **Urutan** angka 0–999; kosong berarti satu lebih besar dari urutan terbesar (tambah) atau tetap (ubah). Formulir tambah sudah terisi urutan berikutnya. Kelompok umur baru aktif secara bawaan.
- **Jenjang** dari kotak centang `jenjang[]`; kode di luar `A`/`B`/`C` ditolak dengan pesan (bukan dibuang diam-diam). Baris jenjang yang tetap tidak dihapus-tambah ulang.
- **Satu transaksi** di `KelompokUmurModel::simpanLengkap()` (baris kelompok umur, jenjang, awalan) dan `hapusLengkap()` (awalan, jenjang, kelompok umur). Model jenjang dan awalan tidak membuka transaksi sendiri. Gagal di tengah melempar `RuntimeException` dan tidak menyimpan apa pun.
- **Aturan hapus** diperiksa berurutan: dipakai atlet, awalan sudah dipakai, masih punya nomor lomba; pesan menyebut sebab dan jalan keluarnya (matikan, atau hapus nomor lombanya dulu). Di daftar, tombol Hapus hanya memakai jendela konfirmasi bila kelompok umur boleh dihapus; bila tidak, formulir langsung terkirim dan server menjelaskan sebabnya lewat `pesan_galat`, supaya admin tidak mengonfirmasi hal yang pasti ditolak. Akibatnya kelima kelompok umur `data:awal` tidak bisa dihapus selama nomor lombanya ada.
- **Aturan murni** (`teksTahunLahir()`, `rentangBertabrakan()`, `AwalanNomorDadaModel::urai()`) ditulis sebagai metode statis di model, karena `app/Libraries/` di luar bagian berkas F2-03; uji unitnya `tests/unit/KelompokUmurAturanTest.php`. Tahap 4 bisa memakai `rentangBertabrakan()`/aturan yang sama untuk mencari kelompok umur dari tahun lahir.
- **Pesan galat** diurutkan seperti kotak di formulir. Pesan galat tahun lahir ditampilkan di bawah kedua kotak, bukan di bawah kotak pertama, supaya tata letak "… sampai …" tidak bergeser.
- **Tabel daftar** dibungkus `tabel-gulir position-relative`: tanpa `position-relative`, teks `visually-hidden` (posisi absolut) di kolom tindakan lolos dari wadah gulir dan membuat halaman bergulir mendatar di ponsel.
- Kelompok umur yang tidak ada (`ubah`, `perbarui`, `hapus`) → `PageNotFoundException` dengan pesan "Kelompok umur ini tidak ditemukan. Mungkin sudah dihapus."
- **Uji migrasi yang disesuaikan** (K36): `MigrasiStrukturTest` (kolom `kelompok_umur`), `MigrasiAturanDataTest` (batas tahun lahir boleh kosong; diganti dari batas usia), `MigrasiTahap2Test` (struktur kolom baru dan gulung balik satu langkah). Uji baru: `tests/unit/KelompokUmurAturanTest.php`, `tests/basisdata/KelompokUmurModelTest.php`, `tests/fitur/PengaturanKelompokUmurTest.php`.

### Untuk koordinator (di luar bagian F2-03, tidak diubah)

- Teks "Batas usia" masih tertulis di `BerandaPengaturanController::daftarHalaman()` (keterangan kartu Kelompok Umur dan kartu Pendaftaran menyebut "tanggal patokan usia") beserta ujinya `tests/fitur/BerandaPengaturanTest.php`, dan di `app/Views/admin/dasbor.php` ("Batas usia, biaya, dan awalan nomor dada"). Perlu diganti ke "batas tahun lahir" oleh pemilik berkasnya (F2-09).
- Komentar migrasi lama `2026-11-11-001000_BuatTabelKelompokUmur.php` masih menyebut batas usia dan tanggal patokan; migrasi itu tidak diubah (sudah pernah dijalankan). Penjelasan penggantinya (K36, K37) ada di komentar kepala migrasi `2026-11-12-000200_UbahBatasUsiaMenjadiTahunLahir`, atas permintaan koordinator.
