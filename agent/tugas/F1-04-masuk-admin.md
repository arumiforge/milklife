# F1-04 Masuk admin

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Admin bisa masuk dan keluar dengan nama pengguna dan kata sandi, dan semua halaman di bawah `/admin` tertutup bagi yang belum masuk.

Acuan: rancangan bagian 4 (masuk admin), 5, dan 13; rencana implementasi Tahap 1 bagian "Rute dan sesi admin".

## Hasil

- [x] Migrasi `2026-11-11-000200_BuatTabelAdmin`: `nama`, `nama_pengguna` (unik), `sandi` (hash), `dibuat_pada`, `diubah_pada`
- [x] `App\Models\AdminModel`; kata sandi selalu disimpan lewat `password_hash`, tidak pernah teks asli
- [x] `GET /admin/masuk`: formulir nama pengguna dan kata sandi, memakai tata letak `polos`
- [x] `POST /admin/masuk`: validasi di server, `password_verify`, hash diperbarui bila perlu (`password_needs_rehash`), ID sesi dibuat ulang, sesi diisi `admin_id` dan `admin_nama`, lalu ke `/admin/dasbor` atau halaman admin yang tadi dituju
- [x] Pesan gagal tidak membedakan nama pengguna salah atau kata sandi salah
- [x] Pembatasan laju pada `POST /admin/masuk`: 5 percobaan per menit per alamat IP; setelah itu pesan ramah yang menyebut kapan boleh mencoba lagi
- [x] `POST /admin/keluar`: sesi dihapus, kembali ke halaman masuk
- [x] `App\Filters\AdminFilter` menjaga semua rute `/admin/*` kecuali `/admin/masuk`; yang belum masuk dialihkan ke halaman masuk; yang sudah masuk dan membuka halaman masuk dialihkan ke dasbor
- [x] Sesi admin berakhir otomatis sesuai `session.expiration`
- [x] Perintah `php spark admin:buat`: menanyakan nama, nama pengguna, dan kata sandi (minimal 8 karakter, tidak tampil saat diketik bila terminal mendukung); bila nama pengguna sudah ada, menawarkan mengganti kata sandinya
- [x] Tidak ada akun atau kata sandi bawaan di repo
- [x] Semua teks berbahasa Indonesia untuk orang non-teknis
- [x] Uji: masuk berhasil; kata sandi salah; nama pengguna tak dikenal; tanpa token CSRF ditolak; percobaan ke-6 dalam semenit ditolak; `/admin/dasbor` tanpa sesi dialihkan; keluar menghapus sesi; `admin:buat` menyimpan hash, bukan teks asli

## Selesai bila

Setelah `php spark admin:buat`, admin bisa masuk di `/admin/masuk`, melihat dasbor, dan keluar. Membuka `/admin/dasbor` tanpa masuk mengalihkan ke halaman masuk. `composer uji` lulus.

## Catatan

- Tanpa pustaka tambahan: sesi dan pembatas laju bawaan CodeIgniter.
- Halaman ganti nama dan kata sandi (`/admin/akun`) dikerjakan di Tahap 2.
- Tampilan halaman masuk hanya memakai kelas Bootstrap; gaya tata letak `polos` dikerjakan F1-05.

### Keputusan kecil saat pelaksanaan

- **Pembatasan laju menghitung semua percobaan**, berhasil maupun gagal, termasuk isian kosong. Kunci pembatas: `masuk_admin_` + hash SHA-256 alamat IP (karakter `:` pada IPv6 tidak boleh di kunci cache). Diperiksa sebelum validasi dan sebelum kata sandi diperiksa, sehingga percobaan ke-6 ditolak walau kata sandinya benar. Pembatas bawaan CodeIgniter berbentuk "ember token": 5 percobaan, lalu satu percobaan baru tiap 12 detik. Jawaban saat terlampaui: status 429, kepala `Retry-After`, pesan "Terlalu banyak percobaan masuk. Tunggu N detik lagi, lalu coba masuk kembali." Alasan: paling sederhana, tidak ada celah antara "mengintip" dan "mencatat" percobaan, dan admin jarang masuk lebih dari 5 kali semenit.
- **Pesan gagal sama** untuk nama pengguna salah dan kata sandi salah: "Nama pengguna atau kata sandi salah. Periksa lagi lalu coba sekali lagi." Bila nama pengguna tidak ada, `password_verify` tetap dijalankan terhadap hash tiruan tetap (`MasukController::HASH_TIRUAN`, hash dari nilai acak; bukan kata sandi akun mana pun) supaya lama waktunya serupa. Hash tiruan ditulis tetap, bukan dibuat saat permintaan, karena membuatnya justru menambah waktu.
- **Gagal masuk tidak dialihkan**: halaman masuk langsung ditampilkan lagi dengan pesan (status 200; 429 untuk pembatasan). Nama pengguna yang diketik dipertahankan, kata sandi tidak, dan fokus pindah ke kotak kata sandi.
- **Tujuan setelah masuk**: penjaga menyimpan jalur + kueri permintaan `GET` di sesi `admin_tujuan`. Hanya jalur `admin` atau `admin/...` dengan karakter aman yang diterima (tanpa skema, host, `//`, `\`, `..`, baris baru; paling panjang 500 karakter; bukan `admin/masuk` atau `admin/keluar`), diperiksa saat disimpan dan sekali lagi saat dipakai (`AdminFilter::tujuanAman()`). Tujuan lain diabaikan dan admin dibawa ke `/admin/dasbor`. Tidak ada parameter `?tujuan=` di alamat.
- **Penjaga** dipasang sebagai filter rute `admin` pada `GET /admin` dan seluruh grup `admin` (termasuk `masuk` dan `keluar`). Penjaga hanya membaca sesi: sudah masuk bila `admin_id` bilangan bulat positif. Belum masuk dialihkan ke halaman masuk dengan pesan "Silakan masuk dulu untuk membuka halaman admin." Jawaban rute admin dikirim dengan `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` dan `Pragma: no-cache`.
- **Keluar** mengosongkan seluruh isi sesi (bukan hanya kunci admin), lalu `regenerate(true)`, lalu ke halaman masuk dengan pesan "Anda sudah keluar." (`session()->destroy()` tidak dipakai karena pesan tidak bisa dibawa ke halaman berikutnya, dan di lingkungan uji tidak melakukan apa-apa sehingga tidak teruji.)
- **Sesi berakhir otomatis** lewat `session.expiration` = 7200 detik dari F1-01; tidak ada mekanisme tambahan. Ujinya di `tests/unit/AdminFilterTest.php`.
- **Model**: nama pengguna dinormalkan (huruf kecil, tanpa spasi di tepi) di `transformDataToArray()`, yaitu sebelum validasi model berjalan, sehingga `insert()`/`update()` selalu menormalkan. Kata sandi di-hash di callback `beforeInsert`/`beforeUpdate` (`hashSandi`); `simpanSandi()` hanya memanggil `update()`. Kata sandi 8–72 karakter (batas atas mengikuti batas bcrypt 72 bita; huruf di luar ASCII bisa memakan lebih dari 1 bita). Nama paling panjang 100 karakter. Aturan `is_unique` nama pengguna belum mengecualikan baris sendiri; bila halaman `/admin/akun` (Tahap 2) mengizinkan ganti nama pengguna, aturan itu perlu disesuaikan.
- **`admin:buat`** menanyakan nama pengguna lebih dulu, baru nama, karena bila nama pengguna sudah ada nama tidak perlu ditanyakan; perintah langsung menawarkan ganti kata sandi ("Ketik ya atau tidak"). Kata sandi tidak pernah diterima lewat argumen atau variabel lingkungan. Dari terminal Linux/macOS/Git Bash ketikan disembunyikan dengan `stty -echo` (dipulihkan juga bila dihentikan dengan Ctrl+C). Bila `stty` tidak ada (cmd Windows), perintah memperingatkan bahwa kata sandi akan terlihat. Bila masukan standar bukan terminal (skrip), tiap jawaban dibaca satu baris: [jawaban ya/tidak bila nama pengguna sudah ada], kata sandi, ulangi kata sandi. Tiap pertanyaan diulang paling banyak 3 kali. Kelompok perintah di `php spark list`: `Aplikasi`.
- Berkas `.gitkeep` di `app/Database/Migrations/`, `app/Filters/`, dan `app/Models/` dihapus karena folder kini berisi.
