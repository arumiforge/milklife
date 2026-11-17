# F7-22 Tolak atlet per atlet

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Admin bisa menolak atlet tertentu dari pendaftaran yang belum Lunas, misalnya karena tahun lahirnya tidak cocok dengan kelompok umur. Sekolah melihat alasannya, kelompok umur yang sesuai, dan perubahan biaya di Cek Pendaftaran, lalu mendaftarkan atletnya ulang (K116).

## Hasil

- [x] Tabel `atlet_ditolak` (migrasi `2026-11-17-000100`), kunci tamu `CASCADE` ke `pendaftaran`
- [x] `App\Libraries\Panel\TolakAtlet`: formulir dengan kecocokan kelompok umur, tolak sebagian lewat `UbahPendaftaran` (biaya dan regu dihitung ulang, catatan ditulis di transaksi yang sama lewat parameter baru `$dalamTransaksi`), tolak semua (pendaftaran tetap ada, total Rp 0)
- [x] `App\Libraries\AtletDitolak`: pembaca catatan; Cek Pendaftaran hanya menampilkan yang NISN-nya belum terdaftar lagi
- [x] Halaman `admin/pendaftaran/<id>/tolak-atlet`, tombol "Tolak atlet" dan tanda "Tidak cocok" di rincian, daftar "Atlet ditolak" di rincian
- [x] Cek Pendaftaran: ringkasan dan bagian "Atlet ditolak panitia" di kartu pendaftaran
- [x] Penggabungan NPSN (K109) memindahkan catatannya; ekspor/impor pindah server (K76) membawanya
- [x] Uji: `tests/basisdata/PanelTolakAtletTest.php`, `tests/fitur/PanelTolakAtletHalamanTest.php`, tambahan di `MigrasiStrukturTest` dan `PanelPaketPendaftaranTest`

## Selesai bila

`composer uji` lulus, dan di server produksi setelah `php spark migrate --all` admin bisa menolak atlet lahir 2018 yang salah masuk KU 8, sementara Cek Pendaftaran sekolahnya menampilkan keterangan sampai atlet itu didaftarkan lagi.

## Catatan

- Di server produksi: `git pull`, lalu `php spark pembaruan:periksa` (akan meminta `php spark migrate --all`).
- Betulkan dulu batas tahun lahir kelompok umur di Pengaturan, supaya tanda "Tidak cocok" dan "kelompok umur yang sesuai" dihitung dari pengaturan yang benar.
- Bukti PDF tidak memuat atlet yang ditolak.
- Rancangan final belum diubah (perlu revisi lewat draf).
