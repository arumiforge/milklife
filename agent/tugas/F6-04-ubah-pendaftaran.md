# F6-04 Ubah pendaftaran

| | |
|---|---|
| Status | rencana |
| Tahap | 6 — Panel data |
| Dibuat | 12 November 2026 |

## Tujuan

Admin memperbaiki pendaftaran yang sudah tersimpan: data sekolah, penanggung jawab, atlet, dan nomor lomba, dengan aturan dan biaya yang tetap benar.

## Hasil

- [ ] Formulir ubah dari rincian pendaftaran (tombol Ubah), memakai `PemeriksaPendaftaran` dan `KelengkapanTim` dengan atlet dan tim pendaftaran ini dikeluarkan dari data "sudah ada"
- [ ] Tambah, ubah, dan hapus atlet dan penanggung jawab (termasuk ganti foto); batas minimal dan maksimal tetap berlaku
- [ ] Biaya dihitung ulang: baris yang tidak berubah mempertahankan biaya tersimpan, yang baru memakai tarif saat ini
- [ ] K32: pendaftaran Lunas yang totalnya berubah kembali ke Menunggu; jendela konfirmasi menyebutnya sebelum simpan
- [ ] K59: pendaftaran Lunas yang totalnya tetap langsung memberi nomor dada kepada atlet baru (`StatusPembayaran::beriNomorDada()`)
- [ ] Atlet yang sudah punya nomor dada mempertahankannya
- [ ] Simpan dalam satu transaksi dengan kunci per NPSN; foto baru dihapus bila gagal, foto lama dihapus setelah berhasil

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: NISN milik pendaftaran lain ditolak, NISN sendiri diterima, tim susulan tetap tanpa biaya tim, K32 naik dan turun, K59, kuota dan batas nomor individu, nomor dada dipertahankan, transaksi gagal tidak meninggalkan berkas.

## Catatan

Bergantung pada F6-02 (rincian, `StatusPembayaran`) dan F6-05 (parameter konfirmasi di `PemeriksaPendaftaran`). Konfirmasi tim susulan (K57) tidak diminta dari admin. Buka-tutup pendaftaran tidak berlaku untuk admin. Asumsi 4 rencana Tahap 6.
