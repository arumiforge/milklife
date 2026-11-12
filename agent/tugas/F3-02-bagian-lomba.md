# F3-02 Bagian lomba di halaman depan

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Sekolah bisa melihat di halaman depan siapa yang boleh ikut tiap kelompok umur, nomor lomba apa saja, dan berapa biayanya, langsung dari data induk.

## Hasil

- [x] `app/Views/beranda/_lomba.php`: tiap kelompok umur aktif dengan tahun lahir, jenjang yang boleh ikut, dan biaya individu per nomor ("Biaya segera diumumkan" bila kosong, "Gratis" bila 0)
- [x] Nomor individu dan nomor beregu per kelompok umur; nomor beregu dengan jumlah inti, batas anggota, jumlah putra/putri (mix), dan biaya per tim
- [x] Ringkas di ponsel: daftar nomor bisa dilipat atau ditampilkan per tab; tanpa tabel lebar
- [x] Bagian tidak tampil bila belum ada kelompok umur aktif
- [x] Gaya di wilayah lomba `tema.css` saja
- [x] Uji `tests/fitur/HalamanDepanLombaTest.php`

## Selesai bila

`composer uji` lulus; setelah `php spark data:awal`, bagian lomba di lebar 390 dan 1440 piksel menampilkan kelima kelompok umur dan nomornya tanpa gulir mendatar.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Data diterima dari `IsiHalamanDepan::kelompokUmur()` (F3-01); tampilan tidak membaca basis data sendiri.

### Keputusan kecil saat pengerjaan

- **Susunan**: satu kartu per kelompok umur (`article#lomba-ku-<id>` dengan `<h3>`), satu kolom di ponsel, dua di layar sedang, tiga di layar lebar (kelas grid Bootstrap, `align-items-start` supaya kartu yang dibuka tidak meregangkan kartu sebelahnya). Tahun lahir, jenjang, dan biaya individu selalu terlihat dalam `<dl>`; daftar nomor dilipat dalam `<details>` (asumsi rencana 5), tertutup saat halaman dibuka, tanpa skrip dan tanpa tabel. Tulisan ringkasan "Lihat nomor lomba" berganti "Sembunyikan nomor lomba" saat dibuka, dengan jumlah nomor ("6 individu · 5 beregu").
- **Penggabungan**: nomor individu dengan nama sama digabung menjadi satu baris dengan kategorinya ("Lari 60 m — Putra, Putri"). Nomor beregu digabung hanya bila nama, jumlah inti, batas anggota, dan biaya timnya sama, dan bukan mix; bila berbeda tetap dua baris. Urutan mengikuti panel admin (kemunculan pertama). Tanda "—" hanya untuk pembaca layar (`visually-hidden`). Jumlah di ringkasan menghitung nomor, bukan baris (data awal: 69).
- **Teks kosong**: tahun lahir kosong → "Tahun lahir segera diumumkan"; jenjang kosong → "Jenjang sekolah segera diumumkan"; biaya individu `null` → "Biaya segera diumumkan", `0` → "Gratis", angka → "Rp50.000 per atlet per nomor". Biaya tim `null` → "Biaya tim segera diumumkan", `0` → "Gratis", angka → "Rp100.000 per tim". Kelompok umur aktif tanpa nomor aktif → "Nomor lomba segera diumumkan."
- **Keterangan tim**: jumlah inti dan batas anggota → "8 atlet inti, paling banyak 10 anggota"; batas kosong atau sama dengan inti → "4 atlet per tim"; hanya batas → "Paling banyak 6 anggota per tim". Mix: "Minimal 4 putra dan 4 putri" (atau salah satunya bila hanya satu yang diisi). Nilai yang belum diisi tidak ditulis.
- **Gaya**: latar putih untuk bagian, kartu latar biru pucat dengan garis oranye di atas (seperti kartu masuk), hanya variabel `--ml-…` yang ada; semuanya di wilayah lomba `tema.css`.
- Uji `tests/fitur/HalamanDepanLombaTest.php`: 8 uji (bagian kosong, data awal lima KU dan 69 nomor, KU dan nomor mati, KU tanpa nomor aktif, tahun lahir–jenjang–biaya, beregu dan mix, mix dengan satu jumlah, escape). `composer uji`: 784 uji, 5.042 asersi, lulus.
- Diperiksa di Chromium pada lebar 390 dan 1440 piksel setelah `php spark data:awal`, dengan jenjang dan biaya KU 10, KU 15 (gratis), dan KU 18 diisi lewat `/admin/pengaturan/kelompok-umur`: kelima kelompok umur dan 69 nomor tampil (daftar tertutup dan terbuka), tanpa gulir mendatar.
