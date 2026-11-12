# Arsip Tahap 6 — Panel data

Selesai: 12 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-6.md`](../../dokumen/draf/rencana-implementasi-tahap-6.md), disetujui pemilik beserta delapan asumsinya (K61), termasuk bagian "Perubahan saat pengerjaan". F6-01 dijalankan sesi koordinator (K56); gelombang 2–4 dijalankan pemilik di sesi berbeda (K64), paling banyak 3 sekaligus (K38).

| Tugas | Status | Gelombang | PR |
|---|---|---|---|
| [F6-01 Fondasi panel data](../tugas/F6-01-fondasi-panel-data.md) | selesai | 1 | #47 |
| [F6-02 Daftar, rincian, lunas, dan hapus](../tugas/F6-02-daftar-rincian-lunas-hapus.md) | selesai | 2 | #50 |
| [F6-03 Bukti bayar dan daftar atlet](../tugas/F6-03-bukti-bayar-daftar-atlet.md) | selesai | 2 | #49 |
| [F6-05 Konfirmasi tim susulan](../tugas/F6-05-konfirmasi-tim-susulan.md) | selesai | 2 | #51 |
| [F6-04 Ubah pendaftaran](../tugas/F6-04-ubah-pendaftaran.md) | selesai | 3 | #52 |
| [F6-06 Rekap dan dasbor](../tugas/F6-06-rekap-dasbor.md) | selesai | 3 | #54 |
| [F6-07 Pengaturan terlindung](../tugas/F6-07-pengaturan-terlindung.md) | selesai | 3 | #53 |
| [F6-08 Penutupan](../tugas/F6-08-penutupan-tahap-6.md) | selesai | 4 | PR penutupan |

Catatan: keputusan wawancara awal Tahap 6 K57–K60 dan persetujuan rencana K61. K63 (ID pendaftaran tidak berubah saat diubah admin) dikonfirmasi pemilik di sesi F6-04 dan dicatat koordinator di #52.

## Hasil

- **Pendaftaran** (`/admin/pendaftaran`): tabel DataTables 2 sisi-server (25 baris per halaman, pencarian, saringan status, jenjang, kabupaten, urut) dan Excel yang mengikuti saringan aktif. Rincian menampilkan sekolah, penanggung jawab dengan foto (hanya lewat rute admin), atlet dengan kelompok umur, nomor lomba, dan nomor dada, tim beregu, biaya, dan status.
- **Tandai Lunas** dengan jendela konfirmasi: nomor dada 4 digit dibuat berurutan menurut urutan simpan, berlanjut ke awalan berikutnya, dan seluruh penandaan batal bila awalan penuh. Kunci baris awalan mencegah nomor ganda. Kembali ke Menunggu tidak menghapus nomor dada.
- **Bukti bayar** (K34): paling banyak 5 gambar per pendaftaran, dikecilkan ke 1600 piksel JPEG di `writable/unggahan/bukti-bayar/`, bisa dilihat, diunduh, dan dihapus.
- **Ubah pendaftaran**: sekolah, penanggung jawab (termasuk ganti foto), atlet, dan nomor lomba, dengan aturan bagian 6 dan biaya dihitung ulang (baris lama mempertahankan biaya tersimpan). K32 dijaga di peramban dan server; K59 memberi nomor dada langsung bila total tetap. ID pendaftaran tidak berubah (K63).
- **Hapus pendaftaran** dengan konfirmasi; foto dan bukti bayar ikut terhapus setelah transaksi, NISN bisa didaftarkan lagi, nomor dada tidak dipakai ulang.
- **Atlet** (`/admin/atlet`): tabel semua atlet dengan saringan kelompok umur, jenis kelamin, nomor lomba, status, dan Excel.
- **Rekap** (`/admin/rekap/<jenis>`): peserta per nomor lomba, tim beregu, per sekolah, per kelompok umur, dan keuangan, dengan saringan status (K60), masing-masing Excel dan PDF A4 mendatar.
- **Dasbor**: angka pendaftaran, sekolah, atlet, tim, tagihan, lunas, dan belum dibayar di atas daftar kelengkapan.
- **Pengaturan terlindung**: isian tim nomor lomba yang sudah dipakai terkunci; batas tahun lahir kelompok umur tidak bisa mengeluarkan atlet terdaftar.
- **Wizard**: official yang mendaftar susulan wajib mencentang konfirmasi per tim susulan; server menolak kiriman tanpa konfirmasi (K57).
- Paket baru: `phpoffice/phpspreadsheet`. Aset lokal: DataTables 2 dan jQuery 3.7.1 (hanya di halaman bertabel).

## Uji gabungan (F6-08)

- Otomatis: `tests/fitur/PanelGabunganTest.php`, satu alur pada data karangan dan gambar buatan GD: wizard (susulan dari NPSN yang sama ditolak tanpa konfirmasi, diterima dengan konfirmasi) → daftar dan Excel dengan saringan → rincian dan foto → unggah bukti bayar → tandai Lunas tiga pendaftaran (nomor dada 2001–2005, 4001) → ubah dengan K59 (anggota tim susulan baru langsung mendapat 2006) dan K32 (total naik, kembali Menunggu, nomor dada tetap) → hapus (NISN didaftarkan lagi, nomor 4002, bukan 4001) → cek NPSN dan bukti PDF membaca nama dan total yang sudah diubah → atlet JSON dan Excel → rekap JSON, Excel, dan PDF kelima jenis → angka dasbor.
- Chromium terhadap `php spark serve` (mode production supaya bilah debug tidak ikut), 1440 dan 390 piksel: masuk, daftar, rincian, unggah bukti bayar, tandai Lunas dan hapus lewat jendela konfirmasi, formulir ubah, atlet, rekap, dasbor. Tanpa galat konsol.
- Sambungan yang diperbaiki (dua baris CSS di wilayah Tahap 6 `admin.css`, milik F6-01): di 390 piksel halaman Pendaftaran bergulir mendatar karena teks `visually-hidden` judul kolom Tindakan (berposisi absolut) lolos dari wadah gulir tabel, dan tombol Unduh Excel keluar dari kartu. Wadah gulir kini `position: relative` dan tombol saringan boleh membungkus.
- `composer uji` pada salinan `main` terbaru (fd10055) bersama cabang penutupan: 1135 uji, 7.646 asersi, lulus.

## Penomoran keputusan

`agent/KEPUTUSAN.md` sempat punya dua entri bernomor K62: persiapan sesi agen di cloud dan pelaksanaan gelombang Tahap 6. Pemilik memutuskan di penutupan ini: entri kedua (pelaksanaan gelombang) menjadi **K64**, dengan catatan nomor lamanya. K62 kini hanya untuk persiapan sesi agen di cloud.

## Yang dibawa ke tahap berikutnya

- **Tahap 7:** minify aset Tahap 6 lewat `php spark aset:minify`: `tabel-admin.js`, `admin-pendaftaran.js`, `admin-ubah-pendaftaran.js`, dan `admin.css` (wilayah Tahap 6). DataTables dan jQuery sudah disimpan dalam versi kecil (`*.min.*`).
- **Revisi rancangan berikutnya:** 6.3 foto penanggung jawab wajib (K50); 6 kuota atlet per nomor dan batas nomor individu (K51, K53); 8 cek dan bukti tanpa tanggal lahir dan nomor dada, nama nomor dengan kategori (K49, K52); 6.6 konfirmasi tim susulan (K57); 9 panel tanpa pendaftaran baru, nomor dada K59, saringan status rekap (K58–K60); 6.8 ID pendaftaran tidak berubah saat diubah admin (K63).
- **Batasan yang diketahui (F6-04):** bila pendaftaran pemilik tim pertama menghapus timnya (lewat ubah atau hapus pendaftaran), tim susulan di pendaftaran lain dengan NPSN yang sama tetap Rp0, jadi tim itu tidak lagi punya pembayar biaya tim. Admin perlu memeriksanya sendiri sampai ada aturan untuk kasus ini.
- Panel admin butuh JavaScript untuk tabel, jendela konfirmasi, dan formulir ubah, seperti wizard.
