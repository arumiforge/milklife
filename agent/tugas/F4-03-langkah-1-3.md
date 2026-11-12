# F4-03 Langkah 1–3, daftar sementara, draf, dan foto

| | |
|---|---|
| Status | selesai |
| Tahap | 4 — Pendaftaran |
| Dibuat | 12 November 2026 |

## Tujuan

Official bisa mengisi data sekolah, penanggung jawab, dan atlet di ponsel tanpa kehilangan isian saat halaman dimuat ulang.

## Hasil

- [x] Halaman `/pendaftaran` dengan lima pil langkah bercentang; keterangan ramah bila pendaftaran belum terbuka
- [x] Langkah 1: jenjang, kabupaten, NPSN dengan pencarian (F4-02) atau nama manual
- [x] Langkah 2: penanggung jawab sesuai batas pengaturan, nomor WA, foto wajib diperkecil di peramban (800 piksel, JPEG)
- [x] Langkah 3: formulir atlet (nama huruf besar, NISN, tanggal lahir, jenis kelamin), kelompok umur dan usia otomatis, pilihan nomor sesuai kelompok umur, jenjang, dan jenis kelamin; daftar sementara individu dan tim beregu dengan ubah dan hapus; kelengkapan tim dan kuota diperiksa sebelum lanjut
- [x] Draf di `localStorage` pada setiap perubahan
- [x] `public/aset/js/pendaftaran.js` dan `public/aset/css/pendaftaran.css`

## Selesai bila

`composer uji` lokal dan CI lulus, dan alur langkah 1–3 diperiksa di Chromium pada lebar 390 dan 1440 piksel tanpa gulir mendatar, termasuk muat ulang di tengah isian.

## Catatan

Validasi peramban hanya untuk kenyamanan; aturan yang berlaku tetap di server (F4-01).

- Data wizard disematkan di `#data-pendaftaran` (`DataIndukPendaftaran::untukWizard()` ditambah `kunci_draf` dan `alamat`), ditulis `json_encode` dengan `JSON_HEX_*` dan huruf non-ASCII di-escape, supaya teks admin tidak bisa menutup tag `<script>`.
- `pendaftaran.js` membuka `window.WizardPendaftaran` (draf, langkah, galat, rupiah) untuk langkah 4–5 di F4-04. Aturan kelompok umur, kelengkapan tim, dan kuota disalin dari `KelompokUmurAtlet`, `KelengkapanTim`, dan `PemeriksaPendaftaran` hanya untuk kenyamanan.
- Draf `localStorage` (`milklife-pendaftaran-draf`): versi, langkah, sekolah (termasuk hasil pencarian NPSN dan data susulan), penanggung jawab (foto data URL JPEG ≤ 800 piksel, ≤ 450 KB), atlet, dan keadaan tersimpan. Bila penyimpanan peramban ditolak, tampil peringatan dan wizard tetap jalan.
- NPSN 8 angka langsung dicari saat diketik. Bila ditemukan, jenjang dan kabupaten mengikuti data referensi dan pilihannya dikunci (asumsi 1 rencana).
- Di luar berkas wizard: `tata_letak/publik.php` mendapat bagian `kepala` di `<head>` untuk CSS dan `<meta>` CSRF halaman ini (berkas milik Tahap 4). `tests/_support/PendaftaranContoh.php` ditambah `bukaPendaftaranUji()`.
- Diperiksa di Chromium 390 piksel dengan data karangan: pencarian NPSN, galat langkah 1, foto diperkecil, tiga atlet, kelompok umur ditolak jenjang, tim belum lengkap, muat ulang di langkah 3 (draf tetap). Lebar halaman 390 piksel, tanpa galat skrip.
