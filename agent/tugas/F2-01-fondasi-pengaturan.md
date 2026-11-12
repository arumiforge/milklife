# F2-01 Fondasi pengaturan

| | |
|---|---|
| Status | selesai |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Menyiapkan semua yang dipakai bersama halaman pengaturan, supaya tujuh tugas berikutnya bisa dikerjakan bersamaan tanpa bentrok.

## Hasil

- [x] Migrasi `2026-11-12-000100_BuatTabelKelompokUmurJenjang` (rencana: "Jenjang")
- [x] `Config\Acara`, `Config\Pendaftaran`, `Config\Pembayaran`, `Config\HalamanDepan` dengan semua kunci dan nilai awal di rencana
- [x] Semua rute Tahap 2 di `app/Config/Routes.php` sesuai tabel rute di rencana
- [x] Butir menu **Pengaturan** (aktif juga untuk `admin/akun`), tautan ke Akun dari nama admin di kepala
- [x] `/admin/pengaturan`: daftar sembilan halaman pengaturan dan Akun, dalam kartu dengan ikon dan keterangan singkat
- [x] Potongan tampilan bersama: `admin/_pesan.php` (dipasang di tata letak admin), `admin/_galat_isian.php`, `admin/pengaturan/_navigasi.php`
- [x] `public/aset/js/konfirmasi.js`: jendela konfirmasi untuk formulir ber-`data-konfirmasi`
- [x] `App\Libraries\Jenjang`, `App\Libraries\NomorWa`, `App\Libraries\Unggahan`, fungsi `rupiah()` dan `tanggal_id()` di `aplikasi_helper`, beserta uji unit
- [x] `php spark data:awal` sesuai bagian "Data awal" di rencana, dengan uji
- [x] `Tests\Support\KirimFormulir` dan pengaturan yang tidak terbawa antar-uji
- [x] `public/unggahan/` diabaikan git; batas unggah PHP dicatat di `README.md`

## Selesai bila

`composer uji` lulus; `migrate --all` lalu `data:awal` menghasilkan 5 kelompok umur dan semua nomor lomba lampiran; `/admin/pengaturan` terbuka dan tampil baik di lebar 390 dan 1440 piksel; gulung balik migrasi bersih.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Cara memakai fondasi (untuk F2-02 sampai F2-09)

- **Rute** sudah ada semua di `app/Config/Routes.php`; cukup buat pengendalinya di `App\Controllers\Admin\Pengaturan\` (Akun: `App\Controllers\Admin\AkunController`) dengan nama metode persis tabel rencana (`index`/`simpan`; `index`/`tambah`/`buat`/`ubah`/`perbarui`/`hapus`; `simpanSandi`). Selama pengendali belum ada, alamatnya 404.
- **Pola halaman**: contoh lengkap pengendali dan tampilan di komentar `BerandaPengaturanController`, pengendali contoh `tests/_support/Controllers/ContohPengaturanController.php` + `tests/_support/Views/contoh_pengaturan.php`, dan ujinya `tests/fitur/BerandaPengaturanPolaHalamanTest.php`.
- **Tampilan halaman pengaturan**: `extend('tata_letak/admin')`, isi bagian `judul`, `judul_halaman`, `isi`; di dalam `<div class="container-fluid px-3 px-lg-4">` mulai dengan `<?= $this->include('admin/pengaturan/_navigasi') ?>` (tanpa data).
- **Isian salah**: tampilkan ulang (200) dengan `<?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>` di atas formulir; tiap kotak `is-invalid` + `invalid-feedback` dengan `id="<kotak>-galat"`. Kunci `$galat` sama dengan `id` kotak supaya ringkasan menjadi tautan.
- **Pesan kilat**: `redirect()->to(...)->with('pesan_sukses' | 'pesan_galat', '...')`; tata letak yang menampilkannya, halaman tidak.
- **Hapus**: formulir `POST` + `csrf_field()` + `data-konfirmasi="<kalimat>"` (pilihan: `data-konfirmasi-tombol`, `data-konfirmasi-judul`, `data-konfirmasi-jenis="utama"`), lalu muat `<script src="<?= aset('js/konfirmasi.js') ?>"></script>` di bagian `skrip`. Kotak unggah diberi `data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"` (atau `BATAS_BERKAS`) supaya berkas kebesaran ditolak sebelum dikirim.
- **Tabel daftar** dibungkus `<div class="tabel-gulir">` supaya di ponsel tergulir di dalam kartunya.
- **Pengaturan**: `setting('Acara.nama')` / `setting('Acara.nama', $nilai)`; `setting()` sudah dimuat otomatis di mana pun. Nilai awal di `Config\Acara`, `Config\Pendaftaran`, `Config\Pembayaran` (termasuk `Pembayaran::PENANDA` untuk daftar penanda templat pesan), `Config\HalamanDepan`.
- **Fungsi bantu** (`aplikasi_helper`): `rupiah($angka)` → `Rp150.000`; `angka_rupiah($isian)` → bilangan bulat dari isian uang (membuang `Rp`, titik, spasi; `null` bila tidak sah); `tanggal_id($nilai, $denganJam = false)` → `30 November 2026 pukul 23.59`; `rentang_tanggal_id($mulai, $selesai)` → `9–12 Desember 2026`.
- **Jenjang**: `Jenjang::semua()`, `label()`, `sah()`, `rapikan($isianCentang)`, `gabungLabel($kode)`.
- **Nomor WA**: `NomorWa::normalkan($isian)` (baku `628…` atau `null`), `NomorWa::PESAN_GALAT`, `NomorWa::tampilan($baku)` → `0812-3456-7890`.
- **Unggahan**: `Unggahan::dipilih($berkas)`, `periksaGambar()`/`periksaBerkas()` (pesan ramah atau `null`), `simpanGambar($berkas, 'halaman-depan'|'pembayaran')`/`simpanBerkas($berkas, 'unduhan')` → jalur `unggahan/...`, `hapus($jalurLama)` setelah yang baru tersimpan, `ukuran()`, `jenis()`, `Unggahan::ukuranTeks()`. Contoh di komentar kelasnya.
- **Uji**: kelas uji turunan `UjiBasisData` (atau `UjiHalaman`) + `use KirimFormulir;` → `bukaSebagaiAdmin()`, `kirimSebagaiAdmin()`, `assertBelumMasukDialihkan()`, `assertTokenCsrfWajib()`, `assertBerhasilDialihkan()`, `assertGagalDialihkan()`, `assertIsianSalahDitampilkanUlang()`, `xpathDari()`. Nilai pengaturan otomatis bersih tiap uji. Unggahan: tambah `use UnggahanSementara;` → `siapkanBerkas('kotak', 'nama.png', self::isiPng())`, `isiFolderUnggahan()`, `jalurUnggahan()`; tidak menulis ke `public/unggahan/`.

### Keputusan kecil saat pengerjaan

- **Awalan nomor dada di data awal (K33, perubahan dari pemilik lewat koordinator):** `data:awal` juga mengisi satu awalan per kelompok umur, KU 8 → 1, KU 10 → 2, KU 12 → 3, KU 15 → 4, KU 18 → 5 (`urutan` 1, `nomor_terakhir` 0), dalam transaksi yang sama. Perintah juga menolak bila tabel `awalan_nomor_dada` sudah berisi. Ringkasan di layar menyebut awalan tiap kelompok umur. Batas usia, jenjang, dan biaya individu tetap kosong.
- **KU 8 bernama "KU 8 (Seneng Playon)"** di data awal, seperti di flyer, supaya sekolah mengenalinya; admin bisa mengubahnya. Tidak ada kolom keterangan terpisah.
- **Data awal**: akhir pendaftaran disimpan `2026-11-30 23:59:00` (ketelitian menit, sama dengan formulir pengaturan nanti). Nomor individu dibuat putra lalu putri per nomor, lalu nomor beregu; `urutan` berjalan 1, 2, 3, … per kelompok umur. Nomor beregu "putra/putri" dibuat dua baris, nomor mix satu baris. Nama nomor tanpa kategori (`Lari 60 m`); kategorinya di kolom `kategori`. Tanda kali ditulis `×` (`Estafet 8 × 50 m`). Hasil: 5 kelompok umur, 69 nomor lomba (58 individu, 11 beregu).
- **`data:awal` menolak** bila sudah ada kelompok umur, awalan nomor dada, atau nomor lomba, atau salah satu kunci acara, `Pendaftaran.mulai`, `Pendaftaran.akhir`, `Pendaftaran.tanggal_patokan_usia` sudah terisi. Pengaturan lain (rekening, halaman depan) tidak menghalangi dan tidak ditimpa. Semua ditulis dalam satu transaksi (termasuk tabel `settings`); gagal di tengah tidak menyimpan apa pun. Kode keluar 1 saat menolak.
- **`helper('setting')` dipanggil di `aplikasi_helper.php`**, supaya `setting()` tersedia di pengendali, tampilan, perintah spark, dan uji tanpa mengubah `app/Config/Autoload.php` (di luar bagian F2-01). Sebelumnya `setting()` tidak terdefinisi di perintah spark.
- **Kunci tamu `kelompok_umur_jenjang`**: hapus berantai, ubah RESTRICT (sama dengan kunci tamu lain). Indeks unik `kelompok_umur_id` + `jenjang`.
- **Daftar halaman pengaturan** ditulis sekali di `BerandaPengaturanController::daftarHalaman()` (kunci, label, ikon, alamat, keterangan, kelompok, `lengkap`, `keterangan_kelengkapan`) dan dipakai halaman daftar serta `_navigasi.php`. Kartu dikelompokkan: "Wajib diisi sebelum pendaftaran dibuka" (Acara, Pendaftaran, Kelompok Umur, Nomor Lomba, Pembayaran), "Isi halaman depan" (Halaman Depan, Jadwal, Unduhan, Kontak), "Akun Anda". Tanda lengkap/belum sudah disiapkan di tampilan: `lengkap` `true`/`false`/`null`.
- **Menu**: butir Pengaturan (ikon `gear`) aktif untuk `admin/pengaturan`, di bawahnya, dan `admin/akun` (kunci `aktif_juga` di `_menu_admin.php`). Nama admin di kepala menjadi tautan ke Akun; di ponsel (< 576 piksel) hanya ikonnya yang tampil supaya judul halaman tidak terpotong, namanya tetap terbaca pembaca layar.
- **Navigasi pengaturan**: tautan "Semua pengaturan" dan deretan pil semua halaman (termasuk Akun); di ponsel digeser mendatar, di layar ≥ 992 piksel melipat ke baris berikutnya.
- **`_galat_isian`**: kunci galat yang berbentuk `id` HTML menjadi tautan `#kunci` ke kotaknya; kunci seperti `jenjang.*` tetap teks biasa. Pesan kosong dibuang.
- **`konfirmasi.js`**: jendela Bootstrap dibuat sekali; formulir dikirim ulang dengan `requestSubmit()` (tombol pengirim ikut). Tanpa Bootstrap memakai `window.confirm`. Skrip yang sama memeriksa `data-ukuran-maksimal` pada kotak unggah.
- **Unggahan**: nama berkas 32 huruf heksadesimal acak. Jenis dibaca dari isi (`finfo`); gambar juga harus terbaca `getimagesize`. Isi berjenis OLE/ZIP (DOC/XLS, DOCX/XLSX) memakai ekstensi nama berkas hanya untuk memilih di antara ekstensi yang diizinkan; ZIP bernama selain `.docx`/`.xlsx` ditolak. `jalurPenuh()` hanya menerima `unggahan/<folder yang dikenal>/<32 heksadesimal>.<ekstensi>`, jadi `hapus()` tidak bisa keluar folder. Di luar uji, hanya berkas hasil unggahan HTTP sungguhan (`is_uploaded_file`) yang diterima. Folder dibuat otomatis (0755), berkas 0644.
- **Pengaturan bersih antar-uji** (`Tests\Support\PengaturanBersih`, dipasang di `UjiBasisData` dan `UjiHalaman`): layanan `settings` dibuat ulang sebelum dan sesudah tiap uji. `UjiBasisData` memakai tabel `settings` (kosong karena migrasi ulang); `UjiHalaman` memakai penangan `array` di memori, sehingga tidak menyentuh basis data dan tidak terkena sisa uji basis data.
- **Fungsi bantu tambahan** selain `rupiah()` dan `tanggal_id()`: `angka_rupiah()` (aturan rencana "titik dan `Rp` yang ikut diketik dibuang server", supaya F2-03/F2-04 memakai satu aturan) dan `rentang_tanggal_id()` (untuk tanggal acara di tata letak, F2-02).
- **Uji Tahap 1 yang disesuaikan** karena terdampak langsung: `tests/basisdata/MigrasiStrukturTest.php` (daftar kunci tamu ditambah `kelompok_umur_jenjang.kelompok_umur_id`) dan `tests/fitur/TataLetakHalamanTest.php` (menu kini berisi Dasbor dan Pengaturan).
- Uji fungsi bantu bernama `tests/unit/FungsiBantuRupiahTanggalTest.php` karena tidak ada pola nama untuk `aplikasi_helper` di rencana.
- Pengendali contoh khusus uji ada di `tests/_support/Controllers/`, dipasang lewat `withRoutes()` hanya di ujinya; rute dan penentu rute dibuat ulang setelahnya.
- Bila unggahan melebihi `post_max_size` PHP, isian formulir (termasuk token CSRF) kosong, sehingga ditolak pemeriksaan CSRF sebelum sampai ke pengendali. Pemeriksaan `data-ukuran-maksimal` di peramban mencegahnya untuk pengguna biasa; penanganan di server memerlukan perubahan filter di luar bagian F2-01 (dilaporkan ke koordinator).
- Digabung lewat PR #13 pada 11 November 2026.
