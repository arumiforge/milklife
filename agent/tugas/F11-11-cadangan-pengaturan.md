# F11-11 Cadangan di Pengaturan

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 |
| Dibuat | 22 November 2026 |

## Tujuan

Admin bisa mencadangkan dan memulihkan seluruh web dari satu menu, Pengaturan > Cadangan, tanpa perintah server (K174 butir 10–12).

## Hasil

- [ ] Menu Pengaturan > Cadangan. Tombol "Backup data" di Pendaftaran (`admin/pendaftaran/ekspor`) pindah ke sana; alamat lama mengarah ke menu baru.
- [ ] Cadangan mencakup semua data (pendaftaran, atlet, atlet diganti, hasil, babak, seri, rekor bila sudah ada), semua pengaturan (termasuk `Klasemen.gabungan`, `Videotron.latar`), dan semua berkas unggahan (`App\Libraries\Unggahan::FOLDER`, termasuk folder `videotron` dari F11-09). Tetap dialirkan tanpa dimuat ke memori (K118 B4).
- [ ] Impor lewat web mengganti semua data dan pengaturan dengan isi cadangan. Sebelum impor, web otomatis membuat cadangan data lama di server; admin mengetik ulang sandinya untuk konfirmasi. Berkas rusak atau versi tak dikenal ditolak tanpa mengubah apa pun.
- [ ] `pendaftaran:impor` tetap bisa memakai berkas cadangan baru; riwayat tindakan admin mencatat ekspor dan impor.
- [ ] Batas ukuran unggahan dan waktu proses dijelaskan di halaman, dengan saran memakai perintah server bila berkas terlalu besar.

## Berkas dan uji

`App\Libraries\PaketPendaftaran`, `GabungPendaftaran`, perintah `pendaftaran:impor`, pengendali dan tampilan baru Pengaturan > Cadangan, sidebar admin. Uji `--filter PaketPendaftaran`, `ImporPendaftaran`, `JejakHalamanAdmin`; uji baru impor web (ganti semua, cadangan otomatis, sandi salah ditolak). Status tugas hanya ditulis di `agent/PROGRES.md`.

## Selesai bila

Cadangan diekspor lalu diimpor lewat web menghasilkan isi yang sama; sandi salah dan berkas rusak ditolak tanpa perubahan; cadangan otomatis tersimpan. `composer uji` lulus.

## Catatan

Dasar: K174. Data siswa dan berkas cadangan asli tidak masuk git; uji memakai data karangan. Bentrok dengan F11-10 (sidebar dan menu Pengaturan); gabung berurutan dengan rebase. Setiap PR aman langsung dipasang di produksi (K139).
