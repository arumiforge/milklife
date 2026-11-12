# F3-03 Jadwal, unduhan, dan kontak

| | |
|---|---|
| Status | selesai |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Pengunjung melihat jadwal, mengunduh berkas, menghubungi narahubung, membuka media sosial, bertanya ke admin, dan membagikan halaman lewat WA.

## Hasil

- [x] `_jadwal.php`: jadwal yang ditampilkan admin, berurutan, dengan waktu (WIB), tempat, tautan, dan keterangan
- [x] `_unduhan.php` dan potongan daftar unduhan bersama; bagian di halaman depan bertautan ke `/unduhan`
- [x] Halaman `/unduhan` (`App\Controllers\UnduhanController`, `app/Views/unduhan.php`) dengan pesan ramah bila kosong; menu Unduhan di kepala aktif (K40)
- [x] `_kontak.php`: narahubung (tautan WA), media sosial (ikon, alamat ringkas), tombol tanya admin lewat WA (bila nomornya diisi), tombol bagikan ke WA
- [x] Bagian tanpa isi tidak tampil
- [x] Gaya di wilayah jadwal–unduhan–kontak `tema.css` saja
- [x] Uji `tests/fitur/HalamanDepanJadwalKontakTest.php` dan `tests/fitur/HalamanUnduhanTest.php`; pemeriksaan menu Unduhan di `TataLetakHalamanTest` disesuaikan

## Selesai bila

`composer uji` lulus; ketiga bagian dan halaman `/unduhan` tampil benar di lebar 390 dan 1440 piksel tanpa gulir mendatar; tautan WA terbuka dengan pesan yang diatur.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Data dari `IsiHalamanDepan` (F3-01): `jadwal()`, `unduhan()`, `narahubung()`, `mediaSosial()`, `waTanya()`, `waBagikan()`.

### Keputusan kecil saat pengerjaan

- **Bagian kontak selalu tampil** karena tombol "Bagikan ke WA" selalu ada (asumsi 3 rencana; disetujui koordinator). Subjudul Narahubung dan Media sosial serta tombol tanya admin hanya tampil bila isinya ada. Kalimat pengantar menyesuaikan: "Ada pertanyaan … Hubungi panitia lewat WhatsApp." bila ada narahubung atau nomor tanya admin, selain itu "Bantu sekolah lain mengetahui lomba ini dengan membagikan halaman ini."
- **Uji di luar bagian F3-03 yang disesuaikan (izin koordinator)**: `HalamanDepanKepalaTest::testBagianKosongTidakTampil` kini mengharapkan `['pembuka', 'kontak']`; `TataLetakHalamanTest::assertTanpaAlamatLuar` hanya memeriksa aset yang dimuat peramban (`src`, `<link href>`, `url()`), bukan `<a href>`, karena maksudnya "tanpa CDN" sedangkan tombol WA menautkan ke `wa.me`. Perubahan yang sama, juga atas izin koordinator, di `KerangkaTataLetakTest::assertTanpaAlamatLuar` (dipakai `testHalamanNyataTanpaAlamatLuar`, yang membuka `/`).
- **Kerangka bersama ketiga bagian**: kelas `bagian-tambahan` (jarak, judul biru tua bergaris oranye miring dengan ikon, kalimat pengantar redup). Latar berselang: jadwal di latar halaman, unduhan putih, kontak biru muda pucat. Hanya warna tema yang ada.
- **Jadwal**: kartu dengan kotak tanggal (angka hari dan tiga huruf bulan dari `tanggal_id()`; jam pasir bila waktu kosong), judul `<h3>`, waktu `tanggal_id(…, true)` + " WIB" atau "Waktu segera diumumkan", tempat, keterangan (baris baru → `<br>`), tombol "Buka tautan" (tab baru). Dua kolom mulai 768 piksel.
- **Unduhan**: potongan bersama `beranda/_daftar_unduhan.php` (parameter `tingkatJudul`: `h3` di halaman depan, `h2` di `/unduhan`). Ikon per jenis (PDF, gambar, Word, Excel). Tombol "Unduh" memakai atribut `download` dengan nama berkas dari judul (tanda `\ / : * ? " < > |` diganti `-`) ditambah ekstensi, karena nama berkas tersimpan acak. Halaman depan menampilkan **semua** berkas yang tampil (biasanya sedikit), ditambah tombol "Lihat semua unduhan" ke `/unduhan`.
- **Halaman `/unduhan`**: judul tab "Unduhan – <nama acara>" (pola sama dengan judul tab admin), pita kepala bergradasi dengan `<h1>` "Unduhan", pesan kosong "Belum ada berkas unduhan … Silakan kembali lagi nanti." dengan tombol "Kembali ke beranda". Menu Unduhan berisi `'unduhan'` dan ditandai aktif oleh `url_is()` yang sudah ada.
- **Kontak**: narahubung berupa kartu (nama, nomor tampil, tombol "Chat WA"); media sosial berupa kartu tautan (ikon `bi-<ikon>`, label, alamat ringkas). Semua tautan keluar `target="_blank" rel="noopener noreferrer"` dengan keterangan "(di tab baru)" untuk pembaca layar.
- Teks panjang tanpa spasi dipatahkan (`overflow-wrap: anywhere`) supaya tidak ada gulir mendatar.
- Uji baru: `tests/fitur/HalamanDepanJadwalKontakTest.php` (10 uji) dan `tests/fitur/HalamanUnduhanTest.php` (4 uji); pemeriksaan menu Unduhan di `TataLetakHalamanTest` disesuaikan. `composer uji`: 790 uji, 5.135 asersi, lulus.
- Diperiksa di Chromium pada lebar 390 dan 1440 piksel (halaman depan dan `/unduhan`) dengan data karangan yang diisi lewat panel admin (tiga jadwal, dua PDF contoh, dua narahubung, tiga media sosial, nomor dan pesan WA tanya admin, pesan bagikan), juga keadaan kosong (hanya tombol bagikan; `/unduhan` kosong); tanpa gulir mendatar.
- Digabung lewat PR #28 pada 12 November 2026.
