# F7-12 Lebih dari satu regu per nomor beregu

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 15 November 2026 |

## Tujuan

Official bisa mendaftarkan lebih dari satu regu sekolahnya di satu nomor beregu, menandai setiap anggota inti atau cadangan, dan membayar biaya beregu per regu. Pendaftaran susulan memilih regu dan boleh mengganti inti (K105, menggantikan K07). Nama regu mengikuti pengaturan admin: dari daftar admin atau diketik official (K106).

## Hasil

- [x] Migrasi `TambahReguBeregu`: `nomor_lomba.regu_maksimal` (nomor beregu lama diberi 3), `atlet_nomor.regu` dan `peran`, `pendaftaran_beregu.regu` dengan kunci unik pendaftaran + nomor + regu. Tim lama menjadi Regu A, inti menurut urutan daftar (`PeranAwalRegu`)
- [x] Pengaturan nomor lomba: isian "Regu per sekolah paling banyak" (kosong = tidak dibatasi); data awal 3
- [x] Aturan server (`Regu`, `KelengkapanTim`, `PemeriksaPendaftaran`): pilihan regu dan peran wajib untuk setiap nomor beregu, label regu dibatasi, inti tepat sejumlah inti, cadangan sampai batas anggota per regu, syarat mix pada inti, biaya per regu, konfirmasi regu susulan per regu ("nomor:regu"), inti lama yang diturunkan (`turun_cadangan`)
- [x] `SimpanPendaftaran` menyimpan regu dan peran, satu baris `pendaftaran_beregu` per regu, dan menurunkan inti lama di pendaftaran lain (waktu ubahnya ikut diperbarui)
- [x] Pencarian NPSN di wizard mengembalikan regu sekolah beserta anggotanya (nama, jenis kelamin, peran; tanpa NISN dan tanggal lahir)
- [x] Wizard: pilihan regu dan Inti/Cadangan di bawah nomor beregu yang dicentang (bawaan: regu yang intinya belum lengkap, lalu regu baru), kartu per regu, anggota regu lama dengan kotak "Jadikan cadangan", konfirmasi per regu, rincian biaya per regu; draf lama tetap terbaca
- [x] Admin Ubah: pilihan regu dan Inti/Cadangan per nomor beregu; regu tersimpan boleh kurang inti (tidak lebih); biaya regu tersimpan dipertahankan; "Bebankan biaya regu" per regu
- [x] Rincian admin: kartu per regu dengan Inti/Cadangan dan tanda "regu kurang inti" (dihitung dari seluruh regu sekolah)
- [x] Tim tanpa pembayar, rekap (tim beregu per regu dengan jumlah inti dan "Inti kurang"; jumlah tim per sekolah dan kelompok umur dihitung per regu), dasbor
- [x] Cek pendaftaran dan bukti PDF menampilkan regu dan peran
- [x] Backup data (`PaketPendaftaran`) membawa regu dan peran; paket lama diisi seperti migrasi
- [x] Rancangan final revisi 8 dan 9, KONTEKS, KEPUTUSAN (K105, K106)
- [x] Uji unit dan basis data untuk aturan regu, simpan, susulan dengan penurunan inti, batas regu, migrasi data lama
- [x] K106: Pengaturan > Pendaftaran punya pilihan nama regu **Ketentuan admin** (daftar nama dari admin, bawaan "A, B, C") atau **Bebas** (diketik official); migrasi `PerpanjangNamaRegu` (nama regu sampai 30 huruf); pemeriksaan server, Ubah admin, dan wizard mengikuti mode itu
- [x] K106: tampilan wizard dirapikan: kartu nomor beregu dengan judul dan keping keterangan (inti, cadangan, syarat mix, batas regu, biaya per regu), tombol Inti/Cadangan, kartu regu dengan pil nama regu dan ringkasan, tanda Inti/Cadangan di setiap anggota

## Selesai bila

`composer uji` lulus lokal dan di CI; wizard (dua regu, susulan dengan penurunan inti) dan halaman admin diperiksa di peramban.

## Catatan

- Setelah memasang versi ini di server: `php spark migrate`, lalu `php spark aset:minify`. Migrasi mengisi regu dan peran semua tim yang sudah terdaftar; periksa rekap Tim Beregu dan betulkan inti lewat Ubah bila urutan daftar tidak sesuai.
- Admin tidak bisa menurunkan inti pendaftaran lain dari halaman Ubah; tukar inti antarpendaftaran dilakukan dua kali Ubah (turunkan dulu, lalu naikkan), karena regu tersimpan boleh kurang inti.
- Draf wizard yang tersimpan di peramban sebelum versi ini: atlet di nomor beregu diminta memilih regu lagi lewat Ubah, dan centang konfirmasi susulan perlu diulang.
