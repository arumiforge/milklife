# F3-02 Bagian lomba di halaman depan

| | |
|---|---|
| Status | rencana |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Sekolah bisa melihat di halaman depan siapa yang boleh ikut tiap kelompok umur, nomor lomba apa saja, dan berapa biayanya, langsung dari data induk.

## Hasil

- [ ] `app/Views/beranda/_lomba.php`: tiap kelompok umur aktif dengan tahun lahir, jenjang yang boleh ikut, dan biaya individu per nomor ("Biaya segera diumumkan" bila kosong, "Gratis" bila 0)
- [ ] Nomor individu dan nomor beregu per kelompok umur; nomor beregu dengan jumlah inti, batas anggota, jumlah putra/putri (mix), dan biaya per tim
- [ ] Ringkas di ponsel: daftar nomor bisa dilipat atau ditampilkan per tab; tanpa tabel lebar
- [ ] Bagian tidak tampil bila belum ada kelompok umur aktif
- [ ] Gaya di wilayah lomba `tema.css` saja
- [ ] Uji `tests/fitur/HalamanDepanLombaTest.php`

## Selesai bila

`composer uji` lulus; setelah `php spark data:awal`, bagian lomba di lebar 390 dan 1440 piksel menampilkan kelima kelompok umur dan nomornya tanpa gulir mendatar.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-3.md`. Data diterima dari `IsiHalamanDepan::kelompokUmur()` (F3-01); tampilan tidak membaca basis data sendiri.
