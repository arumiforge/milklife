# Arsip Tahap 4 — Pendaftaran

Selesai: 12 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-4.md`](../../dokumen/draf/rencana-implementasi-tahap-4.md), termasuk bagian "Perubahan saat pengerjaan". Dikerjakan bersamaan dengan Tahap 5 di sesi lain (K48), dengan `main` sebagai satu-satunya titik temu. Tahap 5 ditutup lebih dulu, jadi uji gabungan wizard → cek → bukti dijalankan di penutupan ini (kesepakatan dua sesi, butir 7).

| Tugas | Status | PR |
|---|---|---|
| [F4-01 Model, aturan, dan penyimpanan](../tugas/F4-01-model-aturan-simpan.md) | selesai | #36 |
| [F4-02 Pencarian NPSN](../tugas/F4-02-pencarian-npsn.md) | selesai | #40 |
| [F4-03 Langkah 1–3, daftar sementara, draf, dan foto](../tugas/F4-03-langkah-1-3.md) | selesai | #41 |
| [F4-04 Langkah 4–5](../tugas/F4-04-langkah-4-5.md) | selesai | #42 |
| [F4-05 Tombol, menu, pesan kilat, dan penutupan](../tugas/F4-05-penutupan-tahap-4.md) | selesai | #43 |

Catatan: PR #33 berisi rencana dan keputusan wawancara awal Tahap 4, K50 (foto penanggung jawab wajib) dan K51 (batas atlet per sekolah per nomor individu). Nomornya digeser dari K49–K50 karena PR Tahap 5 lebih dulu memakai K48 dan K49.

## Hasil

- `/pendaftaran`: wizard satu halaman dengan lima pil langkah bercentang (rancangan 7). Isian tersimpan di `localStorage` setiap ada perubahan; muat ulang tidak menghilangkan isian. Bila pendaftaran tidak terbuka, halaman menampilkan keterangan ramah.
  1. Data Sekolah: jenjang, kabupaten, NPSN yang langsung dicari (`GET pendaftaran/sekolah`, 60 per menit per alamat IP). SLB ditolak (K28). Data referensi menentukan nama, jenjang, dan kabupaten.
  2. Penanggung Jawab: 1–5 orang sesuai pengaturan, nomor WA, foto wajib (K50) yang diperkecil di peramban (800 piksel, JPEG).
  3. Atlet: kelompok umur dari tahun lahir dan dibatasi jenjang (K36, K23), usia sebagai keterangan, nomor sesuai kelompok umur dan jenis kelamin, daftar sementara individu dan tim beregu dengan ubah dan hapus, kelengkapan tim dan kuota diperiksa sebelum lanjut.
  4. Biaya: rincian dihitung server (`POST pendaftaran/periksa`), selalu tampil (K45); jendela konfirmasi; simpan (`POST pendaftaran/simpan`) dalam satu transaksi beserta ID pendaftaran (6.8).
  5. Pembayaran: logo bank, rekening, pemilik, total, tombol WA konfirmasi dengan `Pembayaran.templat_pesan`, "Pendaftaran Selesai" yang mengosongkan draf dan menampilkan tombol cetak bukti `/bukti/<ID>`.
- Semua aturan bagian 6 diperiksa ulang di server (`App\Libraries\Pendaftaran\PemeriksaPendaftaran`, aturan murni dengan uji unit): NISN unik (juga kunci unik basis data), satu tim per nomor beregu per sekolah dengan susulan tanpa biaya tim (6.6, K07), kuota atlet per sekolah per nomor individu (K51, pengaturan baru `Pendaftaran.atlet_per_nomor_maksimal`, nilai awal 2).
- Penyimpanan `SimpanPendaftaran`: kunci `GET_LOCK` per NPSN, satu transaksi, foto di `writable/unggahan/penanggung-jawab/` (di luar `public/`, diabaikan git), foto dihapus bila gagal.
- Tombol Pendaftaran di halaman depan dan menu Pendaftaran menaut hanya saat pendaftaran terbuka; selain itu nonaktif dengan keterangan "Segera", "Belum dibuka", atau "Ditutup".
- Tata letak publik menampilkan pesan kilat `pesan_sukses` dan `pesan_galat`.
- Uji gabungan wizard → cek → bukti: otomatis (`tests/fitur/PendaftaranGabunganTest.php`) dan di Chromium 390 dan 1440 piksel. Sambungan yang dirapikan: nama nomor di cek dan bukti memakai kategori ("Lari 60 m putra"), nomor WA penanggung jawab ditulis "0812-0000-0001".
- `composer uji` pada salinan `main` terbaru (4b40a54) bersama cabang penutupan: 929 uji, 5.992 asersi, lulus.

## Yang dibawa ke tahap berikutnya

- **Tahap 6:** foto penanggung jawab disajikan ke admin lewat `FotoPenanggungJawab::jalurPenuh()` (jalur di `penanggung_jawab.foto`, relatif terhadap `writable/unggahan/`). Hapus pendaftaran sebaiknya ikut menghapus berkas foto. Ubah pendaftaran oleh admin bisa memakai `PemeriksaPendaftaran` dan `KelengkapanTim` (atlet yang sedang diubah dikeluarkan dari data "sudah ada"). Mengubah jumlah inti atau batas anggota nomor beregu yang sudah dipakai belum dicegah (catatan Tahap 3).
- **Tahap 7:** minify `pendaftaran.css` dan `pendaftaran.js`. Tata letak publik kini membaca keadaan pendaftaran di setiap halaman (beberapa kueri kecil).
- **Revisi rancangan berikutnya:** bagian 6.3 foto wajib (K50); bagian 6 kuota (K51, K53); bagian 8 nama nomor dengan kategori di cek dan bukti.
- Setelah penutupan: kuota atlet per sekolah per nomor individu bawaannya tidak dibatasi; batas yang berlaku adalah 2 nomor individu per atlet (K53). Juknis tidak ditunggu (K55).
