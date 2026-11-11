# F1-03 Impor sekolah

| | |
|---|---|
| Status | selesai |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Daftar sekolah referensi masuk ke basis data dengan satu perintah, sehingga nanti formulir pendaftaran bisa menampilkan nama sekolah dari NPSN.

Acuan: `data/README.md`; rancangan bagian 6.2 dan 11; rencana implementasi Tahap 1.

## Hasil

- [x] Migrasi `2026-11-11-000100_BuatTabelSekolah`: `npsn` (8 karakter, unik), `nama`, `bentuk`, `jenjang` (`SD`/`SMP`/`SMA`), `status`, `alamat`, `desa_kelurahan`, `kecamatan`, `kabupaten`, `dibuat_pada`, `diubah_pada`; indeks pada (`jenjang`, `kabupaten`)
- [x] `App\Models\SekolahModel` dengan pencarian lewat NPSN
- [x] Perintah `php spark sekolah:impor [berkas]`; tanpa argumen membaca `data/sekolah/sekolah.json`
- [x] Impor aman diulang: NPSN yang sudah ada diperbarui, bukan digandakan; sekolah yang tidak ada di berkas tidak dihapus
- [x] `kabupaten` disimpan tanpa awalan "Kab." (`Kab. Blora` → `Blora`)
- [x] Baris yang tidak sah (NPSN bukan 8 digit, nama kosong, jenjang tak dikenal) dilewati dan dihitung; perintah tidak berhenti di tengah
- [x] Berkas tidak ada atau bukan JSON yang benar: pesan galat berbahasa Indonesia yang menyebut cara memperbaikinya, kode keluar bukan nol
- [x] Ringkasan di akhir: jumlah dibaca, ditambah, diperbarui, dilewati, dan jumlah per kabupaten
- [x] Uji memakai berkas JSON kecil karangan yang dibuat sendiri: impor berhasil; impor ulang tidak menggandakan; baris tidak sah dilewati; berkas rusak ditolak; pencarian NPSN di model

## Selesai bila

Pada basis data kosong, `php spark migrate --all` lalu `php spark sekolah:impor` menghasilkan 4.729 baris dengan jumlah per kabupaten sama seperti `data/README.md` (Blora 925, Rembang 624, Pati 1.225, Kudus 799, Jepara 1.156). Menjalankannya sekali lagi tetap 4.729 baris. `composer uji` lulus.

## Catatan

- `data/sekolah/sekolah.json` dan `sumber/` tidak diubah.
- Kode wilayah Dapodik tidak disimpan; belum ada yang memakainya.
- Logika impor ada di `SekolahModel::impor()` (beserta `periksaBaris()`, `npsnSah()`, `rapikanKabupaten()`); perintah `App\Commands\ImporSekolah` hanya membaca berkas (`bacaBerkas()`) dan menampilkan ringkasan. Tidak ada kelas di folder lain.
- Panjang kolom dari data 10 November 2026 ditambah kelonggaran: `nama` 150 (terpanjang 90), `bentuk` 20 (10), `status` 10 (6), `alamat` 200 (80), `desa_kelurahan` 60 (17), `kecamatan` 60 (14), `kabupaten` 40. `jenjang` berupa `VARCHAR(3)`, nilainya dijaga aplikasi. `npsn`, `nama`, `jenjang`, `kabupaten` wajib; kolom lain boleh kosong (`NULL`), dan isian kosong di berkas disimpan sebagai `NULL`.
- Data dirapikan sebelum disimpan: spasi di awal dan akhir dibuang, `jenjang` dijadikan huruf besar, awalan `Kab.`, `Kabupaten`, atau `Kota` dibuang dari `kabupaten`. Isian yang lebih panjang dari kolomnya dilewati dengan alasan, bukan dipotong.
- NPSN yang muncul lebih dari sekali dalam satu berkas: yang pertama dipakai, berikutnya dilewati dan dihitung.
- Simpan per 500 data dalam satu transaksi. NPSN yang sudah ada dicari dulu supaya "ditambah" dan "diperbarui" terhitung benar, lalu ditulis dengan `INSERT ... ON DUPLICATE KEY UPDATE`; `dibuat_pada` tidak ikut diperbarui. "Diperbarui" berarti NPSN sudah ada dan datanya ditulis ulang, walau isinya sama.
- Kabupaten tidak dibatasi pada lima kabupaten K01; yang lain tetap disimpan dan terlihat di ringkasan per kabupaten.
- Jalur berkas relatif diukur dari folder proyek; jalur mutlak (termasuk `C:\...` di Windows) dipakai apa adanya. BOM UTF-8 di awal berkas diabaikan.
- Bila basis data galat (misalnya tabel belum dibuat), perintah berhenti dengan pesan yang menyarankan `php spark migrate --all`, kode keluar 1. Kelompok yang sudah tersimpan tetap ada; menjalankan ulang melengkapinya.
- Hasil pada basis data kosong: 4.729 ditambah, 0 dilewati, Blora 925, Rembang 624, Pati 1.225, Kudus 799, Jepara 1.156. Impor kedua: 0 ditambah, 4.729 diperbarui. Waktu sekitar 0,3 detik per impor.
- Uji: `tests/unit/PemeriksaanSekolahTest.php`, `tests/basisdata/SekolahModelTest.php`, `tests/basisdata/ImporSekolahTest.php`, dengan berkas karangan `tests/_support/data/sekolah-*.json` (NPSN `99xxxxxx`). Nama uji migrasi tidak memakai awalan `Migrasi*` karena awalan itu milik F1-02.
- Digabung lewat PR #5 pada 11 November 2026.
