# F6-07 Pengaturan terlindung

| | |
|---|---|
| Status | rencana |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Admin tidak bisa tanpa sengaja merusak pendaftaran yang sudah ada dengan mengubah aturan nomor lomba atau kelompok umur yang sudah dipakai (catatan Tahap 3 dan 4).

## Hasil

- [ ] Nomor lomba yang sudah dipakai atlet atau tim: kelompok umur, jenis, kategori, jumlah inti, batas anggota, dan jumlah putra/putri minimal tampil terkunci dengan keterangan, dan server menolak perubahannya. Nama, biaya beregu, urutan, dan aktif tetap bisa diubah
- [ ] Kelompok umur yang sudah dipakai atlet tidak bisa dihapus (sudah dijaga kunci asing; pesan ramah) dan batas tahun lahirnya tidak bisa diubah sehingga ada atlet terdaftar yang keluar dari rentang

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup setiap isian terkunci (ditolak) dan isian bebas (diterima) untuk nomor lomba yang dipakai dan tidak dipakai.

## Catatan

Tidak bergantung pada tugas lain. Asumsi 8 rencana Tahap 6.
