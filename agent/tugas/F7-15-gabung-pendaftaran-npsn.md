# F7-15 Gabung pendaftaran Lunas ber-NPSN sama

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 16 November 2026 |

## Tujuan

Sekolah yang mendaftar lebih dari sekali (pendaftaran susulan) punya satu pendaftaran dan satu bukti PDF setelah pembayarannya dikonfirmasi; ID dan kode QR bukti lama tetap mengarah ke pendaftaran gabungan (K109).

## Hasil

- [x] Tabel `pendaftaran_gabungan` (ID dan kode bukti lama → pendaftaran gabungan), terhapus bersama pendaftaran gabungannya
- [x] `GabungPendaftaran`: gabung semua pendaftaran Lunas satu NPSN ke yang Lunas paling dulu; atlet, regu (biaya dijumlahkan), bukti bayar, penanggung jawab (nomor WA sama sekali saja), usulan nama, subtotal dan total; catatan lama ikut pindah bila gabungan digabung lagi
- [x] `StatusPembayaran::tandaiLunas()` memegang kunci sekolah (`GET_LOCK` sama dengan wizard dan Ubah) dan menggabungkan dalam transaksi yang sama; tombol Lunas dan Lunas massal menyebut penggabungan dan mengalihkan ke pendaftaran gabungan
- [x] Ubah pendaftaran: NPSN pendaftaran Lunas diganti ke sekolah yang sudah punya pendaftaran Lunas → langsung digabung
- [x] Alamat bukti lama (`/bukti/<kode lama>/<ID lama>`) dialihkan ke alamat bukti gabungan; kode salah tetap 404
- [x] Cek Pendaftaran dengan ID lama menampilkan pendaftaran gabungan dengan keterangan; kartu cek, bukti PDF, dan rincian admin menulis "Gabungan dari"; pencarian daftar pendaftaran admin menemukan ID lama
- [x] Backup data (paket pindah server) membawa ID lama
- [x] Perintah `php spark pendaftaran:gabung` untuk data yang sudah ada (konfirmasi GABUNG, `--ya`)
- [x] Rancangan revisi 12, panduan produksi revisi 2, PDF dibuat ulang, KEPUTUSAN (K109), KONTEKS
- [x] Uji `PanelGabungPendaftaranTest`, `PanelGabungPendaftaranHalamanTest`, satu uji Ubah, dan satu uji Backup data; uji alur `PanelGabunganTest` dan `MigrasiStrukturTest` disesuaikan (pendaftaran susulan B kini tergabung ke A saat Lunas)

## Selesai bila

`composer uji` lulus lokal dan di CI; di server, `php spark migrate --all` lalu `php spark pendaftaran:gabung` sekali.

## Catatan

- Migrasi baru: `2026-11-16-000100_BuatTabelPendaftaranGabungan`.
- Penggabungan tidak bisa dipisah. Pendaftaran gabungan yang dikembalikan ke Menunggu, diubah, atau dihapus berlaku untuk seluruh isinya.
- Penanggung jawab gabungan bisa melebihi batas pengaturan (bawaan 5); admin yang membuka Ubah diminta mengurangi dulu.
- Bukti bayar gabungan bisa lebih dari 5 gambar; tidak masalah karena pendaftaran Lunas tidak menerima unggahan.
- Foto penanggung jawab ganda (nomor WA sama) dihapus setelah transaksi berhasil; bila tujuan belum punya foto, foto susulan dipakai.
- Bila dua pendaftaran ditandai Lunas pada detik yang sama, yang ID-nya lebih kecil menjadi tujuan.
