# F1-05 Tata letak dan tema

| | |
|---|---|
| Status | selesai |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Halaman publik dan panel admin punya tata letak jadi dengan warna flyer, nyaman di ponsel, dan siap diisi halaman-halaman tahap berikutnya.

Acuan: rancangan bagian 9, 10, dan 12; flyer di `sumber/flyer/`; rencana implementasi Tahap 1 bagian "Aset dan tampilan" dan "Warna tema".

## Hasil

- [x] `public/aset/css/tema.css`: warna flyer sebagai variabel CSS dan penyesuaian variabel Bootstrap (biru utama, biru tua, oranye untuk tombol utama, biru muda untuk bidang informasi, kartu putih); satu tema terang
- [x] Kontras teks pada tombol dan bidang berwarna minimal 4,5 : 1
- [x] Tata letak publik: kepala dengan nama acara, menu (Beranda, Pendaftaran, Cek Pendaftaran, Unduhan; yang halamannya belum ada tidak ditautkan), isi, footer sederhana; dirancang dari ponsel lebih dulu
- [x] Tata letak admin: lebar penuh, menu samping di layar lebar, kepala halaman menempel di atas, menu menempel di bawah pada ponsel, tanpa teks footer; menampilkan `admin_nama` dan tombol Keluar (`POST /admin/keluar` dengan CSRF)
- [x] Menu admin ditulis di satu tempat (dipakai menu samping dan menu bawah); Tahap 1 hanya berisi Dasbor
- [x] Tata letak polos untuk halaman masuk dan galat: kartu di tengah dengan nama acara
- [x] Halaman depan sementara: nama acara, tempat dan tanggal, keterangan "Pendaftaran sedang disiapkan"
- [x] Dasbor sementara dengan keadaan kosong yang ramah
- [x] Komponen "tanpa gambar": kotak ikon untuk gambar yang belum ada (bukan gambar tiruan)
- [x] Bilah kemajuan tipis di atas halaman admin yang bisa dinyalakan dari JavaScript (`public/aset/js/`), untuk pemuatan data di tahap berikutnya
- [x] Halaman galat 404 dan galat umum (mode produksi) berbahasa Indonesia yang ramah dan menyebut langkah berikutnya
- [x] Bagian `judul`, `isi`, `skrip` dan pemakaian `aset()` tetap seperti kesepakatan; tidak ada yang dimuat dari luar
- [x] Uji `tests/fitur/TataLetak*Test.php`: halaman depan memuat tata letak publik; dasbor (dengan sesi admin uji) memuat menu samping, menu bawah, `admin_nama`, dan formulir keluar ber-CSRF; halaman tak dikenal menampilkan 404 berbahasa Indonesia; tidak ada alamat `http` luar pada keluaran HTML
- [x] Tangkapan layar halaman depan, dasbor, dan 404 pada lebar ponsel (390 piksel) dan desktop (1440 piksel) dilampirkan di laporan ke koordinator, tidak masuk git

## Selesai bila

Halaman depan, dasbor, dan 404 tampil rapi pada lebar 390 dan 1440 piksel tanpa gulir mendatar, dan `composer uji` lulus.

## Catatan

- Tidak memakai logo atau gambar dari flyer sebagai aset web; logo diunggah admin di Tahap 3. Flyer hanya acuan warna.
- Tanpa Node, tanpa CDN, tanpa fon daring: memakai fon sistem.
- Menu admin lain ditambahkan bersama halamannya di tahap berikutnya.

### Keputusan kecil saat pengerjaan

- **Warna.** Nilai flyer dipakai apa adanya untuk `--ml-biru` `#234987`, `--ml-biru-tua` `#021F7B`, `--ml-oranye` `#E85F31`, `--ml-biru-muda` `#9BCCEC`. Teks putih di atas `#E85F31` hanya 3,44 : 1 (teks biru tua 4,12 : 1), jadi oranye flyer hanya untuk hiasan (pita, garis, penanda aktif). Tombol utama memakai `--ml-oranye-tombol` `#C4461B` (rona sama, digelapkan): teks putih 4,95 : 1; disorot `#A93E18` 6,20 : 1; ditekan `#963714` 7,36 : 1. Pasangan lain: putih di biru 8,84; putih di biru tua 14,15; biru tua di biru muda 8,25; biru muda di biru 5,16; teks redup `#5B6475` di latar `#F3F6FB` 5,50 (warna `secondary` bawaan Bootstrap hanya 4,33 di latar ini, jadi diganti). Semua dihitung ulang di `TataLetakTemaTest`.
- **Peran tombol.** `.btn-primary` = oranye (ajakan utama: Masuk, Daftar, Simpan); `.btn-outline-primary` = biru (tindakan kedua). Selain tombol, "primary" Bootstrap (tautan, fokus, centang, halaman aktif, `progress`, `pagination`) memakai biru utama. Warna yang tertulis mati di `bootstrap.min.css` (`#0d6efd`, `#86b7fe`) ditimpa di `tema.css`; berkas Bootstrap tidak diubah.
- **Tema terang.** `data-bs-theme="light"` dipasang di `<body>`, bukan `<html>`, karena uji kerangka F1-01 mencocokkan `<html lang="id">` persis. Ditambah `<meta name="color-scheme" content="light">`.
- **Menu publik** ditulis sebagai larik `$menuPublik` di `tata_letak/publik.php`. Butir tanpa `alamat` tampil sebagai `<a class="nav-link disabled" aria-disabled="true">` tanpa `href`, dengan label "Segera". Tahap berikutnya cukup mengisi `alamat`-nya.
- **Menu admin** ditulis di `tata_letak/_menu_admin.php` dan disertakan dua kali (`$this->include()`, yang tidak menerima data). Markupnya sama; bentuk samping/bawah diatur dari wadahnya di `admin.css`. Butir aktif: alamatnya sendiri atau halaman di bawahnya (`url_is('admin/dasbor')` atau `url_is('admin/dasbor/*')`).
- **Judul di kepala admin.** Bagian pilihan baru `judul_halaman` (teks pendek di kepala sebagai `<h1>`); bila kosong, isi `judul` yang dipakai. Isi halaman admin dimulai dari `<h2>`. Tiga bagian bersama (`judul`, `isi`, `skrip`) tidak berubah.
- **Tata letak polos** sudah menyediakan kartu: `main#isi.polos-bingkai > .polos-kolom > p.polos-merek + .card.polos-kartu > .card-body`. Halaman masuk cukup berisi `<h1 class="h4">`, pesan, dan formulir Bootstrap biasa, tanpa `.card`/`.container` lagi. Nama acara di atas kartu berupa `<p>`, jadi `<h1>` milik halaman.
- **Halaman galat** (`error_404.php`, `error_400.php`, `production.php`) memakai kerangka bersama `errors/html/_galat.php` lewat `require __DIR__`, tanpa `extend`, `aset()`, maupun `esc()`. CSS-nya (`errors/html/galat.css`) disisipkan langsung ke `<style>` (seperti `production.php` bawaan menyisipkan `debug.css`), sehingga tetap bergaya walau fungsi bantu atau berkas statis bermasalah, tanpa permintaan berkas. Alamat beranda memakai `site_url('/')` bila tersedia, dengan cadangan `/`. Warna di `galat.css` diuji sama dengan `tema.css`. Pesan teknis CodeIgniter hanya tampil di luar mode produksi, di-escape. `error_exception.php` dan berkas CLI tidak diubah.
- **Komponen tanpa gambar**: `tata_letak/_tanpa_gambar.php` (`rasio` `1x1`/`4x3`/`16x9`/`21x9`, `label`), dipanggil dengan `view(..., [...], ['saveData' => false])` supaya `label` tidak terbawa ke pemakaian berikutnya. Contoh pemakaiannya di dasbor ("Gambar kepala halaman depan"), bukan di beranda, supaya pengunjung tidak melihat kotak kosong.
- **Bilah kemajuan**: `aset/js/kemajuan.js` → `Kemajuan.mulai()`, `Kemajuan.selesai()`, `Kemajuan.sedangBerjalan()`. Bisa bertumpuk (beberapa pemuatan sekaligus). Selama berjalan, `<main>` diberi `aria-busy="true"`. Dengan `prefers-reduced-motion` bilah langsung penuh tanpa animasi.
- **Ruang menu bawah.** Isi admin diberi ruang bawah `tinggi menu + env(safe-area-inset-bottom)`. `viewport-fit=cover` tidak dipasang karena uji F1-01 mencocokkan meta viewport persis; tanpa itu peramban sendiri menjaga area aman, dan `env()` bernilai 0.
- **Ikon tab**: `public/aset/gambar/ikon.svg`, bentuk sederhana buatan sendiri (bukan dari flyer).
- **Isi tertulis langsung** (nama acara, seri, tanggal, tempat, tahun di footer) diberi komentar bahwa Tahap 2–3 menggantinya dengan pengaturan admin.
- Digabung lewat PR #7 pada 11 November 2026.
