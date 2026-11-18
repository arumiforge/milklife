# F7-33 Kelompok umur pilihan dalam satu jenjang

| | |
|---|---|
| Tahap | 7 — Penyelesaian |
| Dibuat | 18 November 2026 |

## Tujuan

Panitia bisa membuat rentang tahun lahir dua kelompok umur dalam satu jenjang beririsan (misalnya KU 10 lahir 2016–2018 dan KU 12 lahir 2014–2016 untuk SD/MI). Atlet yang lahir di tahun irisan dipilihkan kelompok umurnya oleh ofisial saat mendaftar; atlet tetap tidak bisa masuk kelompok umur jenjang lain (K134).

## Hasil

- [x] Aturan inti `KelompokUmurAtlet::tentukan()` menerima pilihan pendaftar dan mengembalikan daftar `pilihan`; `irisan()` meringkas tahun lahir yang masuk dua kelompok umur per jenjang; `gabungNama()`
- [x] Pemeriksa server (`PeriksaAtlet`, `PeriksaKelompokUmur`) membaca `atlet[i][kelompok_umur_id]`; tanpa pilihan yang sah, galat di `atlet.<i>.kelompok_umur_id`. Atlet tersimpan tanpa pilihan tetap di kelompok umur tersimpannya; K98 tetap berlaku
- [x] Sakelar "Pendaftar memilih kelompok umur" (`Pendaftaran.pilih_kelompok_umur`, bawaan menyala) di halaman Kelompok Umur, rute `POST admin/pengaturan/kelompok-umur/pilihan`; tidak bisa dimatikan selama ada irisan; saat mati, `KelompokUmurModel::periksa()` menolak irisan seperti K95
- [x] Wizard langkah Atlet: kotak "Pilih kelompok umur" (tombol per kelompok umur dan tahun lahirnya), nomor lomba menunggu pilihan, galat bila belum memilih, pilihan tersimpan di draf dan ikut dikirim
- [x] Ubah pendaftaran admin: kotak Kelompok umur per atlet bila ada pilihan, terisi kelompok umur tersimpan; pindah KU memilih ulang nomor
- [x] Tolak atlet: kelompok umur tersimpan yang termasuk pilihan tetap cocok; bila tidak, "Seharusnya KU 10 … atau KU 12 …"
- [x] Keterangan: halaman Kelompok Umur (penjelasan, sakelar, daftar tahun pilihan), formulir kelompok umur, halaman depan ("SD/MI lahir 2016 boleh memilih KU 10 atau KU 12, dipilih saat mendaftar"), keterangan langkah Atlet
- [x] Dokumen: K134, `ATURAN.md`, `KONTEKS.md`, `PETA-KODE.md`, rancangan revisi 19 dan PDF-nya

## Berkas dan uji

- `app/Libraries/Pendaftaran/KelompokUmurAtlet.php`, `Pemeriksa/PeriksaKelompokUmur.php`, `Pemeriksa/PeriksaAtlet.php`, `app/Libraries/Panel/TolakAtlet.php`, `Panel/Ubah/IsianUbah.php`, `app/Libraries/IsiHalamanDepan.php`
- `app/Models/KelompokUmurModel.php`, `app/Config/Pendaftaran.php`, `app/Config/Routes.php`, `app/Controllers/Admin/Pengaturan/KelompokUmurController.php`
- View: `pendaftaran/_langkah_atlet.php`, `admin/pendaftaran/ubah.php`, `admin/pengaturan/kelompok_umur/{index,formulir}.php`, `beranda/_lomba.php`; JS `pendaftaran/{atlet,atlet-formulir,atlet-daftar,biaya}.js`, `admin-ubah-pendaftaran.js`; CSS `pendaftaran.css`, `tema.css`
- Uji: `vendor/bin/phpunit --filter 'PendaftaranAturanTest|PendaftaranPemeriksaTest|KelompokUmurModelTest|PengaturanKelompokUmurTest|PanelUbahPendaftaranTest|PanelTolakAtletTest'`

## Selesai bila

`composer uji` lulus, dan setelah admin mengubah KU 12 menjadi 2014–2016 di panel, wizard meminta ofisial SD memilih KU 10 atau KU 12 untuk atlet lahir 2016, sedangkan atlet SMP lahir 2016 tetap ditolak.

## Catatan

- Di server produksi: `git pull` lalu `php spark pembaruan:periksa`; tidak ada migrasi. Sakelar langsung menyala karena nilai awalnya di `Config\Pendaftaran`.
- Rentang di server tidak diubah otomatis (K134 butir 6): ubah KU 12 di Pengaturan > Kelompok Umur ketika panitia mengumumkannya, lalu periksa daftar "Tahun lahir yang dipilihkan" di halaman yang sama.
- Diperiksa di peramban (Playwright, Chromium, lebar 412 dan 1280): wizard sampai kirim pendaftaran dengan atlet lahir 2016 di KU 12, halaman Kelompok Umur, dan Ubah pendaftaran (pindah KU 12 → KU 10).
