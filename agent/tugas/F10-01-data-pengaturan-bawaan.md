# F10-01 Data dan pengaturan bawaan

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P1 |

## Tujuan

Data bawaan memakai nama dan kontak terbaru (permintaan B10–B13), dan server yang sudah terisi bisa disamakan dengan satu perintah aman sehingga impor backup pendaftaran berhasil.

## Hasil

- [ ] `DataAwal.php` menulis `KU 8`, bukan `KU 8 (Seneng Playon)`; `beranda/_kepala.php` dan semua teks/uji yang menyebut nama lama diperbarui (`grep -rn "Seneng Playon"`), termasuk rancangan final
- [ ] Nomor WA bawaan `6288215277227` (tampil `0882-1527-7227`) untuk `Pembayaran.nomor_wa` dan `HalamanDepan.nomor_wa_tanya`
- [ ] Pesan "Tanya admin" dan "Bagikan ke WA" memuat `#MilkLifeAthleticsChallengeSeri2`
- [ ] Media sosial bawaan: Instagram `https://www.instagram.com/milklife.id`, TikTok `https://www.tiktok.com/@milklife_id`, YouTube `https://www.youtube.com/@milklife_id`
- [ ] Logo Djarum bawaan dari `sumber/logo/logo-djarum-foundation.png` (disalin utuh ke `public/aset/gambar/logo-djarum.png`), tampil bila admin belum mengunggah logo Djarum, seperti `IsiHalamanDepan::logoMerek()` (K152)
- [ ] Perintah `php spark data:sesuaikan` (idempoten, hanya mengubah nilai bawaan lama): ganti nama KU 8 bila `KU 8` belum ada, WA contoh `628123456789`, media sosial bawaan lama; mencetak ringkasan diubah/dilewati; tidak menyentuh data pendaftaran
- [ ] `pendaftaran:impor`: nama kelompok umur yang tidak cocok disebut di pesan galat beserta saran menyamakan nama di Pengaturan > Kelompok Umur atau menjalankan `php spark data:sesuaikan`
- [ ] Bagian 10 panduan produksi dan `PETA-KODE.md` menyebut `data:sesuaikan`

## Berkas dan uji

`app/Commands/DataAwal.php`, perintah baru `app/Commands/SesuaikanData.php`, `app/Commands/ImporPendaftaran*` (pesan galat), `app/Libraries/IsiHalamanDepan.php` (WA, logo Djarum), `app/Views/beranda/_kepala.php`, `public/aset/gambar/logo-djarum.png`, uji yang menyebut nama lama. Uji: `DataAwalTest`, uji baru `SesuaikanDataTest` (basisdata: jalankan dua kali, nilai yang sudah diubah admin tidak ditimpa), `HalamanDepan*`, `Pengaturan*`, uji impor.

## Selesai bila

Zip backup karangan dengan `KU 8` bisa diimpor ke basis data `data:awal` lama setelah `data:sesuaikan`; semua uji lulus; halaman depan menampilkan logo Djarum baru dan WA baru.

## Catatan

Backup asli pemilik tidak pernah masuk git (K138, K153); uji memakai zip karangan buatan uji. Perintah ditambahkan ke daftar perintah sekali jalan di ringkasan koordinator.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
