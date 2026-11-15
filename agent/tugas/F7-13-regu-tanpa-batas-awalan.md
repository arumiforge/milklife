# F7-13 Regu tanpa batas bawaan, awalan regu, dan bukti PDF beregu per baris

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 15 November 2026 |

## Tujuan

Sekolah bisa mendaftarkan regu sebanyak yang dibutuhkan tanpa batas bawaan, admin bisa mengganti kata "Regu" (misalnya "Tim"), bukti PDF menampilkan anggota regu satu per baris dengan nomor dada dan peran, dan daftar sementara beregu di wizard lebih ringkas (K107).

## Hasil

- [x] Bawaan tidak dibatasi: `DataAwal` dan formulir nomor lomba baru mengisi "Regu per sekolah paling banyak" kosong; `Config\Pendaftaran::$label_regu` kosong (huruf A, B, C, … otomatis); daftar nama regu boleh dikosongkan di Pengaturan > Pendaftaran
- [x] Migrasi `ReguTanpaBatas`: batas regu yang masih 3 dikosongkan dan daftar nama regu yang masih "A, B, C" dihapus dari pengaturan
- [x] Awalan nama regu (`Pendaftaran.awalan_regu`, bawaan "Regu") di Pengaturan > Pendaftaran; `Regu::awalan()`, `Regu::label()`, dan `Regu::nama()` memakainya; `Regu::rapikan()` juga membuang awalan itu; dipakai wizard, pesan pemeriksaan, Ubah dan rincian admin, rekap, cek, dan bukti
- [x] Bukti PDF beregu satu baris per anggota: No, Nomor Lomba, Regu, Nama Atlet, NISN, No. Dada, Peran, Biaya (nomor, regu, biaya di baris pertama tiap regu); `DataPendaftaran` menambah `nama_nomor` di setiap regu
- [x] Daftar sementara beregu: kartu regu dengan kepala berwarna (nama nomor, pil regu, status, jumlah atlet, biaya), anggota dikelompokkan Inti dan Cadangan dengan hitungan, satu baris ringkas bernomor per atlet; di ponsel tombol Ubah/Hapus berikon saja
- [x] Pilihan regu di wizard pada huruf otomatis: regu yang sudah dipakai ditambah satu regu baru, dengan keterangan jumlah atlet di daftar
- [x] Draf wizard dengan konfirmasi susulan bernama regu panjang (mode Bebas) tidak lagi dibuang saat dibaca ulang
- [x] Rancangan final revisi 10, KONTEKS, KEPUTUSAN (K107)
- [x] Uji diperbarui dan ditambah

## Selesai bila

`composer uji` lulus lokal dan di CI; daftar sementara (layar lebar dan ponsel) dan bukti PDF diperiksa.

## Catatan

- Setelah memasang versi ini di server: `php spark migrate`, lalu `php spark aset:minify`.
- Batas teknis tetap 26 regu per nomor per sekolah (huruf A–Z, `Regu::PALING_BANYAK`).
