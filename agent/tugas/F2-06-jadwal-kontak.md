# F2-06 Pengaturan jadwal dan kontak

| | |
|---|---|
| Status | selesai |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengelola jadwal (sosialisasi, technical meeting, dan lainnya) serta narahubung dan media sosial.

## Hasil

- [x] Jadwal: daftar, tambah, ubah, hapus; judul, keterangan, waktu (boleh kosong), tempat, tautan (alamat web yang sah), urutan, tampil
- [x] Kontak (tabel `tautan`): daftar terpisah untuk narahubung dan media sosial; narahubung berisi label dan nomor WA (dibakukan `NomorWa`), media sosial berisi label dan alamat web; urutan
- [x] `JadwalModel`, `TautanModel`, uji fitur dan uji basis data

## Selesai bila

`composer uji` lulus; jadwal dan kontak bisa dikelola dari ponsel dan layar lebar.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Keputusan kecil saat pengerjaan

- **Jenis kontak lewat parameter kueri.** Satu halaman `/admin/pengaturan/kontak` berisi dua bagian (narahubung, media sosial), masing-masing dengan tombol tambah sendiri: `kontak/tambah?jenis=narahubung` dan `kontak/tambah?jenis=media-sosial` (tanda hubung di alamat, `media_sosial` di basis data; peta di `KontakController::JENIS_ALAMAT`). Formulir tambah membawa jenis dalam kotak tersembunyi `jenis`. Jenis kosong atau tidak dikenal (di alamat maupun kiriman) dialihkan ke daftar dengan `pesan_galat` yang menyuruh memakai tombol tambah. Jenis kontak yang sudah ada tidak bisa diubah: formulir ubah tidak membawa `jenis` dan kiriman `jenis` diabaikan, karena isi `nilai` kedua jenis berbeda (nomor WA dan alamat web).
- **Label dan pesan per jenis** ditulis sekali di `KontakController::TEKS`: narahubung = "Nama narahubung" + "Nomor WA" (kotak `tel`), media sosial = "Nama media sosial" + "Alamat" (kotak `url`). Kotak formulir tetap bernama `label` dan `nilai` seperti kolom tabel.
- **Nomor WA** dibakukan `NomorWa::normalkan()` sebelum disimpan (pesan galat `NomorWa::PESAN_GALAT`), ditampilkan `NomorWa::tampilan()` di daftar (bertautan `https://wa.me/<nomor>`) dan di formulir ubah.
- **Alamat web** (media sosial dan tautan jadwal) diperiksa `TautanModel::rapikanAlamatWeb()`: hanya `http`/`https`, nama situs bertitik, tanpa spasi, tanpa nama pengguna/kata sandi, paling panjang 255 karakter. Alamat tanpa awalan (`instagram.com/contoh`) diberi `https://` karena admin sering menyalinnya begitu; awalan lain (`javascript:`, `mailto:`, `ftp://`) ditolak. Awalan ditulis huruf kecil, sisanya disimpan apa adanya.
- **Kontak kembar ditolak**: nomor WA (setelah dibakukan) atau alamat yang sudah ada di jenis yang sama ditolak dengan pesan yang menyebut nama pemiliknya. Antarjenis tidak diperiksa.
- **Waktu jadwal** memakai satu kotak `datetime-local` (tanggal dan jam, WIB), boleh kosong ("Waktu belum pasti"). Diterima `Y-m-d\TH:i` (juga dengan detik atau spasi), tahun 2000–2100, disimpan `Y-m-d H:i:00`. Tanpa jam ditolak, karena kolom `DATETIME` tidak bisa membedakan "jam belum pasti" dari pukul 00.00. Ditampilkan `tanggal_id(..., true)` + "WIB".
- **Isian jadwal**: hanya judul yang wajib. Tempat dan tautan boleh diisi keduanya atau tidak sama sekali (rancangan: "tempat atau tautan"; tidak dipaksa salah satu karena jadwal bisa diumumkan sebelum tempatnya pasti). Panjang paling banyak: judul 150, tempat 150, tautan 255 (sesuai kolom), keterangan 1000 huruf (kolom `TEXT`, dibatasi supaya ringkas di halaman depan). Isian kosong disimpan `NULL`.
- **Urutan** (asumsi 2 rencana): angka bulat 0–9999. Formulir tambah terisi urutan berikutnya (terbesar + 1); kotak yang dikosongkan berarti paling akhir. Kontak dihitung per jenis. Daftar jadwal diurutkan `urutan`, lalu waktu (yang kosong di belakang), lalu `id`; kontak `urutan` lalu `id`. `JadwalModel::daftar(true)` hanya yang tampil, untuk Tahap 3.
- **Tampil** berupa sakelar "Tampilkan di halaman depan", tercentang untuk jadwal baru; daftar menandai "Tampil" atau "Disembunyikan". Kontak tidak punya kolom tampil (sesuai migrasi).
- **Data yang tidak ada** (`ubah`, `perbarui`, `hapus` dengan `id` yang tidak ada, misalnya sudah dihapus di tab lain) dialihkan ke daftar dengan `pesan_galat`, bukan halaman 404. Jadwal dan kontak tidak dipakai data lain, jadi selalu boleh dihapus (setelah jendela konfirmasi).
- **Daftar di ponsel**: nama dan rinciannya (waktu, tempat, tautan, keterangan; nomor WA; alamat) ditumpuk dalam satu kolom supaya tabel pendek. Kolom itu diberi lebar minimal sehingga tabel tergulir di dalam kartunya. Ikon media sosial dipilih dari nama situs (`TautanModel::ikonMediaSosial()`, `globe2` untuk situs lain); alamat ditampilkan ringkas tanpa `https://www.` (`alamatRingkas()`). Tautan keluar dibuka di tab baru dengan `rel="noopener noreferrer"`.
- **Gulir halaman di ponsel karena teks tersembunyi**: teks `visually-hidden` (berposisi absolut) di dalam tabel membuat halaman ikut bergeser 150–220 piksel di lebar 390, karena `.tabel-gulir` (F2-01) tidak berposisi. Di halaman ini dipasang kelas Bootstrap `position-relative` pada wadahnya. **Untuk koordinator:** sebaiknya `.tabel-gulir` di `public/aset/css/admin.css` diberi `position: relative` supaya halaman daftar lain tidak terkena; berkas itu di luar bagian F2-06, jadi tidak diubah.
- **Aturan murni tanpa basis data** (alamat web, ikon, waktu jadwal) ditaruh sebagai metode statis di `TautanModel` dan `JadwalModel`, karena pembagian berkas tidak memberi F2-06 tempat di `app/Libraries/`. Uji unitnya `tests/unit/KontakAlamatWebTest.php`. Uji model tabel `tautan` bernama `KontakTautanModelTest.php` supaya cocok pola `*Kontak*`.
- Uji: `tests/unit/KontakAlamatWebTest.php`, `tests/basisdata/JadwalModelTest.php`, `tests/basisdata/KontakTautanModelTest.php`, `tests/fitur/PengaturanJadwalTest.php`, `tests/fitur/PengaturanKontakTest.php` (83 uji baru; `composer uji`: 379 uji, 2.732 asersi, lulus). Tampilan diperiksa di Chromium pada lebar 390 dan 1440 piksel: daftar, formulir tambah/ubah, isian salah, dan jendela konfirmasi hapus, tanpa gulir mendatar halaman.
- Cabang ini sudah menggabungkan `main` terbaru (K36–K38); tidak ada perubahan isi F2-06 karenanya.
- Digabung lewat PR #20 pada 12 November 2026.
