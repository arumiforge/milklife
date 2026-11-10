# F1-03 Impor sekolah

| | |
|---|---|
| Status | rencana |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Daftar sekolah referensi masuk ke basis data dengan satu perintah, sehingga nanti formulir pendaftaran bisa menampilkan nama sekolah dari NPSN.

Acuan: `data/README.md`; rancangan bagian 6.2 dan 11; rencana implementasi Tahap 1.

## Hasil

- [ ] Migrasi `2026-11-11-000100_BuatTabelSekolah`: `npsn` (8 karakter, unik), `nama`, `bentuk`, `jenjang` (`SD`/`SMP`/`SMA`), `status`, `alamat`, `desa_kelurahan`, `kecamatan`, `kabupaten`, `dibuat_pada`, `diubah_pada`; indeks pada (`jenjang`, `kabupaten`)
- [ ] `App\Models\SekolahModel` dengan pencarian lewat NPSN
- [ ] Perintah `php spark sekolah:impor [berkas]`; tanpa argumen membaca `data/sekolah/sekolah.json`
- [ ] Impor aman diulang: NPSN yang sudah ada diperbarui, bukan digandakan; sekolah yang tidak ada di berkas tidak dihapus
- [ ] `kabupaten` disimpan tanpa awalan "Kab." (`Kab. Blora` → `Blora`)
- [ ] Baris yang tidak sah (NPSN bukan 8 digit, nama kosong, jenjang tak dikenal) dilewati dan dihitung; perintah tidak berhenti di tengah
- [ ] Berkas tidak ada atau bukan JSON yang benar: pesan galat berbahasa Indonesia yang menyebut cara memperbaikinya, kode keluar bukan nol
- [ ] Ringkasan di akhir: jumlah dibaca, ditambah, diperbarui, dilewati, dan jumlah per kabupaten
- [ ] Uji memakai berkas JSON kecil karangan yang dibuat sendiri: impor berhasil; impor ulang tidak menggandakan; baris tidak sah dilewati; berkas rusak ditolak; pencarian NPSN di model

## Selesai bila

Pada basis data kosong, `php spark migrate --all` lalu `php spark sekolah:impor` menghasilkan 4.729 baris dengan jumlah per kabupaten sama seperti `data/README.md` (Blora 925, Rembang 624, Pati 1.225, Kudus 799, Jepara 1.156). Menjalankannya sekali lagi tetap 4.729 baris. `composer uji` lulus.

## Catatan

- `data/sekolah/sekolah.json` dan `sumber/` tidak diubah.
- Kode wilayah Dapodik tidak disimpan; belum ada yang memakainya.
