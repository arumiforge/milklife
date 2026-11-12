# F7-03 Tim tanpa pembayar

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Admin tahu bila sebuah tim beregu kehilangan pendaftaran yang membayar biaya timnya, dan bisa menagihkannya ke salah satu pendaftaran (K68).

## Hasil

- [ ] `App\Libraries\Panel\TimTanpaPembayar`: tim (NPSN + nomor beregu) tanpa satu pun baris `biaya_tim` > 0 sementara tarif beregu saat ini > 0
- [ ] Peringatan di rincian pendaftaran yang memuat tim itu
- [ ] Jumlah tim tanpa pembayar di dasbor (hanya bila ada)
- [ ] Daftar tim tanpa pembayar di rekap keuangan, ikut di Excel dan PDF
- [ ] Ubah: kotak centang "Bebankan biaya tim ke pendaftaran ini" pada tim yang ditandai; tarif saat ini, total dihitung ulang, K32 berlaku
- [ ] Jendela hapus pendaftaran dan simpan Ubah yang menghapus tim memperingatkan bila tindakannya meninggalkan tim tanpa pembayar

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: tim ditandai setelah pendaftaran pembayar dihapus dan setelah timnya dihapus lewat Ubah; tidak ditandai bila tarif beregu 0 atau sekolah tanpa NPSN; angka sama di rincian, dasbor, dan rekap; tanda hilang setelah dibebankan lewat Ubah; K32 saat membebankan ke pendaftaran Lunas.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Tim tanpa pembayar". Asumsi 6. Mengisi tempat kosong gelombang 1, atau gelombang 2. Menjawab batasan F6-04 di `agent/arsip/tahap-6.md`.
