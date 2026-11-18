# Arsip Tahap 8 — Hari lomba dan hasil

Selesai: 18 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-8.md`](../../dokumen/draf/rencana-implementasi-tahap-8.md), disetujui pemilik beserta delapan asumsinya (K125), termasuk bagian "Perubahan saat pengerjaan". Cakupan dari K124; menu admin K130–K133; tandai hadir hanya Lunas K129. Koordinator menggabung PR setelah CI hijau (K126).

| Tugas | Status | Gelombang | PR |
|---|---|---|---|
| [F8-01 Riwayat tindakan admin](../tugas/F8-01-riwayat-tindakan-admin.md) | selesai | 2d | #102 |
| [F8-02 Penanda bukti bayar baru](../tugas/F8-02-penanda-bukti-baru.md) | selesai | 2d | #103 |
| [F8-03 Fitur hari lomba](../tugas/F8-03-hari-lomba.md) | selesai | 2e | #104 |
| [F8-07 Menu ponsel: sidebar dan 5 butir bawah](../tugas/F8-07-menu-ponsel-sidebar.md) | selesai | 2 | #107 |
| [F8-04 Hasil lomba](../tugas/F8-04-hasil-lomba.md) | selesai | 2f | #113 |
| [F8-05 Klasemen sekolah](../tugas/F8-05-klasemen-sekolah.md) | selesai | 2f | #114 |
| [F8-06 Penutupan Tahap 8](../tugas/F8-06-penutupan-tahap-8.md) | selesai | akhir | #116, bersama F7-06 |

## Hasil

- **Riwayat tindakan admin** (F8-01): tabel `riwayat_admin`; Lunas, Menunggu, Ubah, Tolak atlet, Hapus, bukti bayar, Urutkan nomor atlet, dan gabung dicatat di transaksi yang sama, tampil di rincian; riwayat pendaftaran yang dihapus dicari di `admin/riwayat`.
- **Bukti bayar baru** (F8-02): `bukti_bayar.diperiksa_pada/oleh`, tanda dan saringan "Bukti baru", kartu dasbor, tombol "Sudah diperiksa"; Lunas ikut menandai diperiksa.
- **Hari lomba** (F8-03): daftar ulang lewat kode QR bukti (pemindai bawaan peramban atau ketik ID), tandai hadir per atlet dan semua (hanya Lunas, K129); BIB massal setengah A4; daftar peserta per nomor dan per kelompok umur (PDF, Excel).
- **Menu ponsel** (F8-07): semua butir di sidebar yang dibuka tombol menu di kepala; menu bawah 5 butir pendaftaran (K131–K133).
- **Hasil lomba** (F8-04): jenis hasil per nomor (waktu, jarak, poin), babak per nomor dengan satu Final, peserta dari atlet/regu Lunas atau yang lolos dari babak lain, prestasi `ss,cc`/`m:ss,cc`/`m,cc`/poin, peringkat otomatis dengan seri, koreksi peringkat Final ("diubah admin"), Terbitkan/Tarik kembali, halaman publik `hasil`, PDF dan Excel.
- **Klasemen sekolah** (F8-05): pustaka murni `Hasil/Klasemen` (juara I, lalu II, lalu III, lalu bersama; kontingen NPSN; per jenjang), `admin/klasemen` (semua nomor, tanda belum terbit) dan `hasil/klasemen` (hanya terbit), PDF dan Excel.
- Tabel baru (`riwayat_admin`, `babak`, `hasil`) dan kolom baru ikut Backup data/impor (K76), penggabungan (K109), dan hapus pendaftaran.

## Uji penutupan (F8-06, bersama F7-06)

- Otomatis: `tests/fitur/AlurMenyeluruhTest.php` dengan minify aktif menjalankan alur hari lomba dan hasil sesudah pendaftaran: daftar ulang dan hadir semua, BIB massal, daftar peserta, Final dan Terbitkan, halaman publik hasil dan klasemen (juara umum I), lalu hapus pendaftaran yang ikut menghapus hasilnya.
- Peramban (Chromium 390 dan 1280 piksel) terhadap pemasangan bersih mode produksi dengan `aplikasi.minify = true` dan CSP menyala: daftar ulang (cari ID, hadir semua), cetak BIB, daftar peserta PDF/Excel, hasil (Final, isi prestasi, Terbitkan, PDF/Excel), klasemen admin dan publik (PDF/Excel). Tanpa galat konsol dan tanpa pelanggaran CSP. Rincian di `agent/tugas/F7-06-uji-menyeluruh.md`.

## Yang dibawa ke tahap berikutnya

- Hasil Tri Lomba diisi poin akhir dari panitia; web tidak menghitung konversi (K124).
- Pemindai QR bawaan peramban hanya di Chrome Android; peramban lain mengetik ID atau memakai aplikasi kamera (asumsi 3 K125).
- Jenis hasil nomor lama diisi dari nama nomor; admin memeriksanya di Pengaturan > Nomor Lomba sebelum lomba.
