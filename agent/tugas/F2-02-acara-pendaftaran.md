# F2-02 Pengaturan acara dan pendaftaran

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengisi data acara serta jadwal dan aturan pendaftaran, dan nama acara di seluruh tampilan mengikuti pengaturan.

## Hasil

- [x] `/admin/pengaturan/acara`: nama, seri, tahun, tempat, tanggal mulai dan selesai acara (selesai tidak sebelum mulai)
- [x] `/admin/pengaturan/pendaftaran`: waktu mulai dan akhir (akhir setelah mulai), buka manual dan batasnya, tampilan usia (keterangan saja, dihitung pada tanggal mulai acara; K36, K37), batas penanggung jawab (minimal 1, maksimal tidak kurang dari minimal), batas nomor individu
- [x] Nama, seri, tahun, tempat, dan tanggal acara di tata letak publik, admin, polos, dan `beranda.php` diambil dari pengaturan, dengan teks pengganti yang wajar bila belum diisi
- [x] Kunci `tanggal_patokan_usia` dihapus dari `Config\Pendaftaran` (K37)
- [x] Uji fitur kedua halaman dan uji tata letak yang terdampak

## Selesai bila

`composer uji` lulus; kedua halaman bisa diisi dan disimpan; mengubah nama acara langsung terlihat di beranda dan kepala admin.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Keputusan kecil saat pengerjaan

- **K36–K38 diterapkan:** kunci `Pendaftaran.tanggal_patokan_usia` dihapus dari `Config\Pendaftaran` (berkas ini menjadi bagian F2-02 setelah F2-01 digabung). Halaman pendaftaran tidak punya kotak patokan usia; pilihan tampilan usia diberi keterangan bahwa usia hanya informasi, dihitung pada tanggal mulai acara (tanggalnya ditampilkan, atau tautan ke Data Acara bila belum diisi), dan kelompok umur ditentukan tahun lahir.
- **Semua kotak Data Acara wajib diisi**, karena semuanya dibutuhkan sebelum pendaftaran dibuka (rancangan 6.1) dan sudah diisi `data:awal`. Seri 1–99, tahun 2000–2100 (supaya salah ketik tertangkap), nama paling banyak 100 huruf, tempat 150 huruf. Spasi berlebih di semua kotak dirapikan. Seri dan tahun disimpan sebagai bilangan bulat. Acara sehari boleh (selesai sama dengan mulai).
- **Waktu pendaftaran** memakai kotak `datetime-local`, disimpan `Y-m-d H:i:00` (detik dibuang, sama dengan `data:awal`). Waktu buka dan tutup wajib; tutup harus sesudah buka (sama persis ditolak).
- **Buka manual**: sakelar "Buka pendaftaran sekarang" dan kotak "Dibuka sampai". Bila dicentang, batasnya wajib dan harus belum lewat (dibandingkan dengan `Time::now()`, WIB). Batas boleh sebelum waktu buka atau sesudah waktu tutup, karena buka manual memang di luar jadwal. Bila centang dihilangkan, batas yang terisi tetap disimpan (tidak dipakai), supaya admin tidak perlu mengetik ulang; yang kosong disimpan `null`.
- **Batas angka**: penanggung jawab 1–20 (paling banyak boleh sama dengan paling sedikit), nomor individu per atlet 1–10. Hanya angka bulat tanpa tanda lain.
- **Pesan isian salah** diurutkan seperti urutan kotak di formulir.
- **Nama acara di tata letak**: potongan baru `tata_letak/_merek.php` (kata pertama `.merek-utama`, sisanya `.merek-sambungan`) dipakai kepala publik, menu samping admin, tata letak polos, dan judul halaman depan. Nama satu kata tampil tanpa sambungan.
- **Teks pengganti** selama data acara belum diisi: nama "Pendaftaran Lomba" (`AcaraController::NAMA_PENGGANTI`, dibaca lewat `AcaraController::namaTampil()`); pita seri–tahun disembunyikan; bagian yang kosong dari tanggal dan tempat tidak ditampilkan, dan bila keduanya kosong tampil "Tanggal dan tempat acara segera diumumkan"; kaki publik "nama tahun" tanpa tahun bila kosong. Judul tab halaman depan "nama Seri 2 – 2026" dengan bagian kosong dibuang.
- **Judul tab halaman admin** ("… – Admin MilkLife") dan halaman masuk ("Masuk Admin – MilkLife", "Panel panitia MilkLife Athletics Challenge") ada di tampilan milik tugas lain/F1, jadi tidak diubah.
- **Uji Tahap 1 yang disesuaikan**: `tests/fitur/TataLetakHalamanTest.php` mengisi data acara dulu (`isiDataAcara()`) pada uji halaman depan dan tata letak polos, karena nama, seri, tanggal, dan tempat kini dari pengaturan. Teks pengganti, nama satu kata, data sebagian, dan perubahan nama yang langsung terlihat di beranda, menu samping admin, dan halaman masuk diuji di `tests/fitur/PengaturanAcaraTest.php`.
- Kotak isian kedua formulir dibuat lewat fungsi kecil di dalam tampilan masing-masing (label, kotak, keterangan, pesan salah dengan `aria-describedby`), karena belum ada potongan tampilan bersama untuk kotak isian.

### Untuk koordinator

- `BerandaPengaturanController::daftarHalaman()` (milik F2-09 sekarang) masih menulis keterangan kartu Pendaftaran "Jadwal buka dan tutup, tanggal patokan usia, dan batas-batas pendaftaran." Usul: "Jadwal buka dan tutup, buka manual, tampilan usia, dan batas-batas pendaftaran."
- Komentar migrasi `2026-11-11-001000_BuatTabelKelompokUmur.php` masih menyebut tanggal patokan usia; mungkin dirapikan F2-03 bersama migrasi tahun lahir.
