# F3-04 Perapian sisa Tahap 2

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Admin yang mengunggah berkas terlalu besar mendapat pesan ramah, judul tab admin mengikuti nama acara, dan pembatasan per alamat IP tetap per pengunjung saat web dijangkau lewat Cloudflare Tunnel.

## Hasil

- [x] Filter batas unggah sebelum `csrf`: kiriman yang melebihi `post_max_size` dialihkan kembali ke halaman asal dengan `pesan_galat` yang menyebut batasnya dan cara memperbaikinya
- [x] Judul tab admin dan halaman masuk (judul dan teks "Panel panitia …") memakai nama acara dari pengaturan, bukan "MilkLife" tertulis langsung
- [x] `Config\App::$proxyIPs` memercayai `CF-Connecting-IP` hanya dari `127.0.0.1` dan `::1` (K31); cara pemasangan di `README.md`
- [x] Uji `BatasUnggahTest`, `JudulTabAdminTest`, `AlamatPengunjungTest`; uji yang terdampak disesuaikan

## Selesai bila

`composer uji` lulus; mengunggah berkas di atas `post_max_size` di server lokal menampilkan pesan ramah di halaman pengaturan, bukan halaman galat.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Dikerjakan bersamaan dengan F3-01; jangan menyentuh `tata_letak/publik.php`, `_merek.php`, `beranda*`, dan `tema.css`.
- Filter `App\Filters\BatasUnggahFilter` (alias `batasunggah`) dipasang di `Config\Filters::$globals['before']` tepat sebelum `csrf`, karena filter `$methods` baru berjalan setelah filter global. Filter sendiri hanya bertindak pada `POST`.
- Kiriman dianggap kebesaran bila `CONTENT_LENGTH` di atas `post_max_size` dan isian serta berkasnya kosong. `post_max_size = 0` (tanpa batas) tidak pernah dianggap kebesaran. Kiriman besar yang isiannya masih ada tidak disentuh, jadi CSRF tetap memeriksanya.
- Halaman asal: sesi `_ci_previous_url` (sumber yang sama dengan `redirect()->back()`), lalu kepala `Referer`, keduanya hanya bila diawali `base_url()` dan tanpa karakter kendali, `\`, atau `..`. `redirect()->back()` tidak dipakai langsung karena tidak memeriksa asal alamat. Cadangan: `admin/dasbor` untuk kiriman ke `admin` atau `admin/…`, halaman depan untuk yang lain.
- Batas ditulis dengan `Unggahan::ukuranTeks()` ("12 MB", "512 KB"); pesannya: "Kiriman tadi tidak tersimpan karena ukurannya lebih dari batas 12 MB untuk sekali kirim. Perkecil dulu ukuran berkasnya (misalnya kompres gambar atau PDF), atau unggah berkas satu per satu, lalu kirim lagi."
- Pesan kilat hanya tampil di tata letak admin. Tata letak publik belum menampilkannya (Tahap 4), dan halaman masuk (tata letak polos) juga tidak; kiriman kebesaran ke halaman masuk hanya kembali ke formulir masuk tanpa pesan (tidak ada unggahan di sana).
- Dicoba di server lokal (`php -d post_max_size=1M -d upload_max_filesize=1M -S …`; `spark serve` tidak meneruskan `-d` ke `php -S`): gambar 3 MB di Halaman Depan kembali ke halaman itu dengan pesan "batas 1 MB", juga dengan `display_errors=On`.
- Judul tab: halaman admin cukup menulis nama halamannya di bagian `judul`; `tata_letak/admin.php` menambahkan " – Admin <nama acara>", `tata_letak/polos.php` menambahkan " – <nama acara>" (`AcaraController::namaTampil()`, di-escape). Teks "Panel panitia …" di halaman masuk juga memakai nama acara. Tampilan galat tidak diubah.
- Uji Tahap 1–2 yang disesuaikan karena memeriksa judul tab: `BerandaPengaturanTest` (judul tab Pengaturan), `KerangkaTataLetakTest` (pola `<title>` boleh diikuti " – …"), serta baris judul di `tests/_support/Views/contoh_pengaturan.php` dan `tests/_support/Views/tata_letak_polos_formulir.php`.
- Contoh di komentar `BerandaPengaturanController` masih menulis "Data Acara – Admin MilkLife"; berkas itu di luar bagian F3-04, jadi tidak diubah (dilaporkan ke koordinator).
- `getIPAddress()` CodeIgniter 4.7 (`system/HTTP/RequestTrait.php`): kepala dari `$proxyIPs` hanya dibaca bila `REMOTE_ADDR` sama persis dengan kunci (atau masuk subnet); isi kepala diambil sampai koma pertama dan harus alamat IP sah, kalau tidak dipakai `REMOTE_ADDR`. Semua keadaan ini diuji di `AlamatPengunjungTest`, termasuk pembatasan masuk admin yang kini dihitung per pengunjung tunnel dan tidak bisa dihindari dengan kepala palsu.
- Efek samping `$proxyIPs`: di CodeIgniter 4.7, `IncomingRequest::isSecure()` juga memercayai `X-Forwarded-Proto` dari alamat di `$proxyIPs`. `cloudflared` mengirim kepala itu, jadi permintaan lewat tunnel HTTPS dikenali sebagai aman; ini sesuai keadaan sebenarnya.
