# Rencana Implementasi Tahap 1 — Kerangka

| | |
|---|---|
| Status | Draf |
| Tanggal | 10 November 2026 |
| Dasar | Rancangan bagian 4, 9, 11, 12, 13, 14, dan 16 (`dokumen/final/rancangan-web-pendaftaran.md`); keputusan K01–K19 |

## Tujuan

Untuk pelaksana tiap tugas Tahap 1 dan untuk pemilik yang meninjau. Setelah membaca, pelaksana tahu berkas mana yang menjadi tanggung jawabnya dan kesepakatan apa yang tidak boleh diubah, sehingga beberapa tugas bisa dikerjakan bersamaan tanpa bentrok.

## Hasil Tahap 1

Setelah Tahap 1 selesai:

- CodeIgniter 4.7 resmi terpasang di akar repo lewat Composer dan bisa dibuka di Laragon (`http://milklife.test`).
- `php spark migrate --all` membuat semua tabel pada rancangan bagian 11.
- `php spark sekolah:impor` mengisi 4.729 sekolah dari `data/sekolah/sekolah.json`.
- Admin bisa masuk dan keluar di `/admin/masuk`; halaman admin lain tertutup bagi yang belum masuk.
- Tata letak publik dan admin memakai Bootstrap 5.3 lokal dengan warna dari flyer.
- `composer uji` menjalankan semua uji dan lulus.

Belum ada di Tahap 1: halaman pengaturan, isi halaman depan, wizard pendaftaran, cek, bukti, rekap.

## Tugas dan urutan

| Tugas | Isi singkat | Urutan |
|---|---|---|
| F1-01 Kerangka CodeIgniter | Pemasangan, konfigurasi, aset Bootstrap, tata letak kerangka, perintah uji | Pertama, sendiri |
| F1-02 Migrasi model data | Sebelas tabel domain dan tabel `settings` | Paralel |
| F1-03 Impor sekolah | Tabel `sekolah`, model, perintah `sekolah:impor` | Paralel |
| F1-04 Masuk admin | Tabel `admin`, masuk, keluar, penjaga halaman admin, pembatasan laju, perintah `admin:buat` | Paralel |
| F1-05 Tata letak dan tema | Tata letak publik dan admin yang jadi, tema warna, halaman galat | Paralel |
| F1-06 Uji gabungan dan penutupan | Uji setelah semua digabung, perbaikan sambungan, arsip tahap | Terakhir |

F1-01 harus digabung ke `main` lebih dulu, karena empat tugas berikutnya berdiri di atas kerangkanya. F1-02 sampai F1-05 lalu dikerjakan bersamaan, masing-masing satu cabang dan satu PR. F1-06 dikerjakan koordinator setelah keempatnya digabung.

Supaya empat tugas paralel tidak bentrok, tiap tugas hanya menyentuh berkas miliknya (lihat "Pembagian berkas") dan memegang kesepakatan di bawah.

## Kesepakatan bersama

Ditetapkan oleh F1-01. Tugas lain memakainya dan tidak mengubahnya.

### Versi

| Bagian | Versi |
|---|---|
| PHP | minimal 8.2 (Laragon pemilik 8.3) |
| CodeIgniter | `codeigniter4/framework` ^4.7, dari `codeigniter4/appstarter` |
| Pengaturan | `codeigniter4/settings` ^2.4 |
| Terjemahan pesan bawaan | `codeigniter4/translations` ^4.7 (pesan validasi berbahasa Indonesia) |
| Bootstrap | 5.3.8, berkas `dist` disalin ke `public/aset/vendor/bootstrap/` |
| Bootstrap Icons | 1.13.2, CSS dan fon disalin ke `public/aset/vendor/bootstrap-icons/` |

Bootstrap dan ikon diunduh sekali sebagai berkas jadi, tanpa Node dan tanpa CDN. Versi dan asal unduhan dicatat di `public/aset/vendor/README.md`.

### Konfigurasi

- Bahasa bawaan `id`, zona waktu `Asia/Jakarta`, `indexPage` kosong.
- CSRF aktif untuk semua permintaan `POST`, token disimpan di sesi.
- Sesi berkas di `writable/session`, lama 7200 detik.
- `Config\Aplikasi` memuat pengaturan khusus aplikasi dari `.env` (`aplikasi.minify`).
- Grup basis data `tests` dibaca dari `database.tests.*` di `.env`, tanpa awalan tabel.

### Aset dan tampilan

| Hal | Tempat |
|---|---|
| CSS tema bersama | `public/aset/css/tema.css` |
| CSS khusus admin | `public/aset/css/admin.css` |
| JavaScript aplikasi | `public/aset/js/` |
| Tata letak publik | `app/Views/tata_letak/publik.php` |
| Tata letak admin | `app/Views/tata_letak/admin.php` |
| Tata letak polos (halaman masuk, galat) | `app/Views/tata_letak/polos.php` |

- Setiap tata letak punya tiga bagian: `judul`, `isi`, dan `skrip`. Nama bagian tidak berubah.
- Alamat aset selalu lewat fungsi `aset('css/tema.css')` dari `app/Helpers/aplikasi_helper.php`. Fungsi ini memilih berkas `.min` bila `aplikasi.minify` aktif dan berkasnya ada. Perintah `aset:minify` sendiri baru dibuat di Tahap 7.
- Halaman tidak memuat apa pun dari luar (CDN, fon daring).

### Rute dan sesi admin

| Rute | Pemilik |
|---|---|
| `GET /` → `BerandaController::index` | F1-01 (kerangka), F1-05 (tampilan) |
| `GET /admin` → alihkan ke `/admin/dasbor` | F1-01 |
| `GET /admin/dasbor` → `Admin\DasborController::index` | F1-01 (kerangka), F1-05 (tampilan) |
| `GET` dan `POST /admin/masuk` | F1-04 |
| `POST /admin/keluar` | F1-04 |

- Sesi admin yang sudah masuk berisi `admin_id` (bilangan) dan `admin_nama` (teks). Tata letak admin menampilkan `admin_nama`.
- Tombol keluar adalah formulir `POST` ke `/admin/keluar` dengan token CSRF.
- Uji yang membuka halaman di bawah `/admin` selalu memakai sesi `['admin_id' => 1, 'admin_nama' => 'Admin Uji']`, supaya tetap lulus setelah penjaga dari F1-04 terpasang.

### Basis data

- Nama tabel dan kolom berbahasa Indonesia, huruf kecil dengan garis bawah.
- Kunci utama `id` (bilangan bulat tak bertanda, naik otomatis). Kunci tamu bernama `<tabel>_id`.
- Kolom waktu baris: `dibuat_pada` dan `diubah_pada` (`DATETIME`).
- Uang: bilangan bulat rupiah tak bertanda. Tidak ada kolom desimal.
- Mesin InnoDB, `utf8mb4`, `utf8mb4_unicode_ci`.
- Awalan nama berkas migrasi dibagi per tugas supaya urutannya pasti:

| Tugas | Awalan berkas migrasi |
|---|---|
| F1-03 | `2026-11-11-000100_` (`sekolah`) |
| F1-04 | `2026-11-11-000200_` (`admin`) |
| F1-02 | `2026-11-11-001000_` sampai `2026-11-11-001999_` (tabel domain) |

Tabel `settings` dibuat oleh migrasi bawaan pustaka `codeigniter4/settings`, jadi migrasi dijalankan dengan `php spark migrate --all`.

### Uji

- Satu perintah: `composer uji` (menjalankan PHPUnit).
- Folder: `tests/unit/` untuk aturan murni, `tests/basisdata/` untuk migrasi dan model, `tests/fitur/` untuk halaman dan rute. Tiap tugas menambah berkas uji sendiri dan tidak mengubah uji tugas lain.
- Uji basis data memakai MySQL (grup `tests`), bukan SQLite, karena keunikan dan kunci tamu harus diuji pada mesin yang sama dengan produksi.
- Data uji memakai nama dan NISN karangan.

## Pembagian berkas

| Tugas | Berkas yang boleh disentuh |
|---|---|
| F1-01 | Semua berkas kerangka |
| F1-02 | `app/Database/Migrations/2026-11-11-001*`, `tests/basisdata/Migrasi*`, berkas tugasnya |
| F1-03 | `app/Database/Migrations/2026-11-11-000100_*`, `app/Models/SekolahModel.php`, `app/Commands/ImporSekolah.php`, `tests/**/*Sekolah*`, berkas tugasnya |
| F1-04 | `app/Database/Migrations/2026-11-11-000200_*`, `app/Models/AdminModel.php`, `app/Controllers/Admin/MasukController.php`, `app/Filters/AdminFilter.php`, `app/Commands/BuatAdmin.php`, `app/Views/admin/masuk.php`, `app/Config/Routes.php`, `app/Config/Filters.php`, `tests/**/*Admin*`, `tests/**/*Masuk*`, berkas tugasnya |
| F1-05 | `app/Views/tata_letak/*`, `app/Views/beranda.php`, `app/Views/admin/dasbor.php`, `app/Views/errors/html/*`, `public/aset/css/*`, `public/aset/js/*`, `public/aset/gambar/*`, `tests/fitur/TataLetak*`, berkas tugasnya |

Bila sebuah tugas ternyata perlu mengubah berkas di luar bagiannya, pelaksana berhenti dan melapor ke koordinator, bukan mengubahnya sendiri.

`agent/PROGRES.md` hanya diubah koordinator selama tugas paralel berjalan. Tiap PR tugas memperbarui status di berkas tugasnya sendiri.

## Warna tema

Diambil dari `sumber/flyer/flyer-seri2-2026.jpg` dan `banner-seri2-2026.jpg`. Nilai awal; F1-05 boleh menggeser sedikit demi keterbacaan (kontras teks minimal 4,5 : 1).

| Peran | Nilai awal | Asal di flyer |
|---|---|---|
| Biru utama | `#234987` | Lencana logo |
| Biru tua | `#021F7B` | Latar flyer |
| Oranye (aksen, tombol utama) | `#E85F31` | Pita "Athletics Challenge" |
| Biru muda (bidang informasi) | `#9BCCEC` | Kotak tanggal |
| Putih (kartu) | `#FFFFFF` | |

## Yang ditunda

| Hal | Ke tahap | Alasan |
|---|---|---|
| Data awal kelompok umur dan nomor lomba | 2 | Bergantung pada asumsi 2 dan 3 di rancangan bagian 17 yang belum dikonfirmasi |
| Halaman ganti nama dan kata sandi (`/admin/akun`) | 2 | Bagian dari halaman pengaturan. Di Tahap 1 kata sandi diganti lewat perintah `admin:buat` |
| Perintah `aset:minify` dan pengecilan HTML | 7 | Sesuai rancangan bagian 16 |
| PDF dokumen final di `dokumen/pdf/` | 5 | Menunggu mPDF dipasang untuk bukti pendaftaran, supaya tidak ada dua pembuat PDF |
| DataTables, PhpSpreadsheet, mPDF | 5–6 | Belum dipakai di Tahap 1 |

## Cara menguji di komputer pemilik

Setelah Tahap 1 digabung, di terminal Laragon pada folder proyek:

1. `composer install`
2. Salin `.env.example` menjadi `.env`, lalu `php spark key:generate`
3. Buat basis data `milklife` dan `milklife_uji` (HeidiSQL atau `mysql`)
4. `php spark migrate --all`
5. `php spark sekolah:impor`
6. `php spark admin:buat`
7. `composer uji`
8. Buka `http://milklife.test` dan `http://milklife.test/admin/masuk`

## Asumsi yang perlu ditinjau

1. Boleh ada lebih dari satu akun admin (semuanya satu peran yang sama). Akun dibuat lewat perintah `admin:buat`, tidak ada akun bawaan dengan kata sandi tertulis di repo.
2. Kata sandi admin minimal 8 karakter.
3. Halaman masuk dibatasi 5 percobaan per menit per alamat IP.
4. Menu admin di Tahap 1 hanya berisi Dasbor dan Keluar; menu lain muncul bersama halamannya di tahap berikutnya.
5. Kolom `kabupaten` di tabel `sekolah` berisi nama tanpa awalan "Kab." (Blora, Rembang, Pati, Kudus, Jepara), supaya sama dengan pilihan di formulir.
6. Pembuatan PDF dokumen dipindah dari Tahap 1 ke Tahap 5.
