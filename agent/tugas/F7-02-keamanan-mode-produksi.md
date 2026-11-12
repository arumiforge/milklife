# F7-02 Keamanan dan mode produksi

| | |
|---|---|
| Status | selesai |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Web siap dijalankan di mode produksi: kata sandi admin yang diganti langsung mengeluarkan sesi lain, kepala keamanan aktif, dan pengunjung tidak pernah melihat rincian teknis saat terjadi galat.

## Hasil

- [x] Sesi admin menyimpan sidik pendek hash kata sandi; `AdminFilter` mengakhiri sesi yang sidiknya tidak cocok dan mengarahkan ke halaman masuk dengan pesan ramah (K67)
- [x] Sesi yang mengganti kata sandi tetap masuk; `php spark admin:buat` mengeluarkan semua sesi akun itu
- [x] Filter `secureheaders` aktif; PDF, Excel, gambar admin, dan pratinjau tetap terbuka
- [x] Halaman 404, 400, dan galat umum di `CI_ENVIRONMENT = production` ramah dan tanpa jejak teknis, dibuktikan uji
- [x] Bagian Produksi di `.env.example` (berkomentar, tanpa rahasia): mode produksi, `app.baseURL` HTTPS, `aplikasi.minify = true`, kuki aman, dan nilai yang benar untuk `app.forceGlobalSecureRequests` di balik Cloudflare Tunnel beserta alasannya

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: dua sesi akun yang sama, ganti sandi di satu sesi, sesi lain diminta masuk lagi; sesi pengganti tetap masuk; ganti lewat `admin:buat`; kepala keamanan pada halaman publik dan admin; halaman galat produksi tanpa jejak teknis.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Keamanan dan mode produksi". Asumsi 3 dan 5. Gelombang 1. Bawaan Tahap 2–3: ganti kata sandi belum mengeluarkan sesi lain.

### Saat pengerjaan

- **Sidik sesi**: pustaka baru `app/Libraries/SesiAdmin.php` (di luar daftar berkas rencana, tetapi tidak dimiliki tugas lain) memegang kunci sesi admin, sidik, dan pesan. Sidik = 16 karakter pertama SHA-256 dari hash kata sandi; hash-nya sendiri tidak masuk berkas sesi. Tanpa migrasi: hash bcrypt selalu berubah saat kata sandi diganti karena garamnya baru. `AdminModel::sandiTersimpan()` membaca kolom `sandi` saja, jadi `AdminFilter` menambah satu kueri kecil di setiap permintaan halaman admin.
- **Hasil pemeriksaan** di `AdminFilter`: sandi diganti → "Kata sandi akun ini baru saja diganti. Silakan masuk lagi dengan kata sandi baru." (K67); akun dihapus → pesan yang sama dengan halaman Akun; sesi tanpa sidik (masuk sebelum versi ini dipasang) → "Sesi Anda sudah berakhir. Silakan masuk lagi." Jadi setelah versi ini dipasang, admin yang sedang masuk diminta masuk sekali lagi. Sesi diakhiri dengan ID baru; alamat tujuan `GET` tetap diingat. Kiriman formulir masuk dari sesi lama tetap diproses dengan sesi yang sudah dikosongkan.
- **Sesi yang mengganti sandi** memperbarui sidiknya (`SesiAdmin::perbaruiSidik()`) sebelum ID sesi dibuat ulang. Pesan sukses diganti menjadi "Kata sandi sudah diganti. Perangkat lain yang masuk dengan akun ini akan diminta masuk lagi. …", dan keterangan di formulir `app/Views/admin/akun.php` ikut disesuaikan (satu kalimat; berkas di luar daftar rencana).
- **`admin:buat`** tidak perlu kode khusus: hash baru membuat sidik semua sesi akun itu tidak cocok. Keluarannya kini menyebut bahwa semua sesi akun itu akan diminta masuk lagi.
- **Hash diperbarui saat masuk** (`password_needs_rehash`, hanya bila algoritma bawaan PHP berubah) juga mengubah hash, sehingga sesi lain akun itu ikut diminta masuk lagi. Dibiarkan: jarang dan aman.
- **Uji**: sebagian besar uji halaman memakai sesi admin tanpa akun sungguhan (sebagian tanpa basis data). `tests/_support/SesiAdminUji.php` (dipakai `UjiHalaman` dan `UjiBasisData`) memasang `SesiAdminTiruan` lewat `Factories` di setiap uji, dan `UjiHalaman::SESI_ADMIN` kini memuat sidik tiruan. Uji sidik sungguhan (`SesiAdminSandiTest`) memanggil `pakaiSesiAdminAsli()`. `HalamanKerangkaTest` disesuaikan karena menyusun sesi admin sendiri.
- **Kepala keamanan**: `secureheaders` global *after* memberi `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-Download-Options`, `X-Permitted-Cross-Domain-Policies`, dan `Referrer-Policy: same-origin`. Tanpa CSP, jadi pratinjau gambar `blob:` di formulir tetap jalan. Diuji di halaman publik, halaman admin, Excel, PDF rekap, bukti PDF publik (`BuktiPendaftaranTest`), foto penanggung jawab, bukti bayar (lihat dan unduh), dan pratinjau JSON Ubah. Pengalihan yang dibuat filter *before* (misalnya belum masuk) tidak membawa kepala ini karena CodeIgniter tidak menjalankan filter *after* untuk jawaban itu; tidak berdampak karena pengalihan tidak berisi halaman. HSTS tidak dipasang aplikasi; bila diinginkan, nyalakan di dasbor Cloudflare (untuk panduan F7-05).
- **Halaman galat produksi**: `HalamanGalatProduksiTest` menjalankan aplikasi di server bawaan PHP (`php -S`) dengan `CI_ENVIRONMENT = production`, karena `ENVIRONMENT` di proses PHPUnit selalu `testing`. Pengaturan dikirim lewat router `tests/_support/server/router-produksi.php` supaya didahulukan dari `.env`. Diperiksa 404 (alamat tidak ada), 400 (karakter terlarang), dan 500 (pengecualian tak tertangkap karena basis data tidak ada): ramah, berbahasa Indonesia, tanpa jejak teknis. Dari uji ini ketahuan komentar `galat.css` yang menyebut nama berkas PHP ikut terkirim; `_galat.php` kini membuang komentar CSS sebelum disisipkan.
- **`app.forceGlobalSecureRequests = true`** di balik Cloudflare Tunnel, alasannya ditulis di bagian PRODUKSI `.env.example`: `IncomingRequest::isSecure()` CodeIgniter 4.7 menganggap permintaan HTTPS bila `REMOTE_ADDR` ada di `App::$proxyIPs` (127.0.0.1 dan ::1, yaitu `cloudflared` di komputer yang sama) dan kepala `X-Forwarded-Proto` bernilai `https`, yang dikirim Cloudflare dan diteruskan Nginx ke PHP sebagai `HTTP_X_FORWARDED_PROTO`. Tidak ada pengalihan berulang. Syaratnya `cloudflared` diarahkan ke `http://localhost`/`http://127.0.0.1`, bukan alamat jaringan lokal server. Dikunci `tests/unit/HttpsTerowonganTest.php`; isi bagian PRODUKSI dikunci `tests/unit/EnvProduksiTest.php` (berkas uji terpisah dari `EnvContohTest` supaya tidak bertabrakan dengan bagian Cadangan F7-05).
