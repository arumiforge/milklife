# Arsip Tahap 5 — Bukti dan cek

Selesai: 12 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-5.md`](../../dokumen/draf/rencana-implementasi-tahap-5.md), termasuk bagian "Perubahan saat pengerjaan". Dikerjakan bersamaan dengan Tahap 4 di sesi lain (K48), dengan `main` sebagai satu-satunya titik temu.

| Tugas | Status | PR |
|---|---|---|
| [F5-01 Pustaka data pendaftaran](../tugas/F5-01-data-pendaftaran.md) | selesai | #34 |
| [F5-02 Halaman cek pendaftaran](../tugas/F5-02-halaman-cek.md) | selesai | #35 |
| [F5-03 Bukti pendaftaran PDF](../tugas/F5-03-bukti-pdf.md) | selesai | #37 |
| [F5-04 PDF dokumen](../tugas/F5-04-pdf-dokumen.md) | selesai | #38 |
| [F5-05 Uji gabungan dan penutupan](../tugas/F5-05-penutupan-tahap-5.md) | selesai | PR penutupan |

Catatan: PR #32 berisi rencana, K48 (Tahap 4 dan 5 bersamaan), dan K49 (jawaban wawancara Tahap 5). Tinjauan pemilik atas bukti setelah F5-03: K52, dibawa PR penutupan.

## Hasil

- `/cek-pendaftaran`: NPSN menampilkan semua pendaftaran sekolah itu secara berurutan, satu kartu per pendaftaran, dengan total keseluruhan (K49). ID pendaftaran menampilkan satu kartu. Data tampil tanpa foto penanggung jawab, tanggal lahir, dan nomor dada (K52). Halaman hanya bisa dipakai bila pengaturan lengkap. Batas laju 10 pencarian per menit per alamat IP. Pesan galat menyebut cara memperbaikinya.
- Tombol Cek di halaman depan aktif bila pengaturan lengkap; menu Cek selalu menaut.
- `/bukti/{id}`: PDF A4 mendatar sesuai rancangan 8, K49, dan K52: logo transparan, penanggung jawab (official) sebaris dengan ikon WA, tabel individu tanpa tanggal lahir dan nomor dada, anggota tim sebaris, status berbingkai warna. Nama berkas `BuktiPendaftaran_<NamaAcara>Seri<seri>_<NamaSekolah>_<tahun>.pdf` dari pengaturan. Batas laju 10 bukti per menit per alamat IP; 404 untuk ID yang tidak ada.
- Semua angka biaya dibaca dari nilai tersimpan (6.7) lewat `App\Libraries\DataPendaftaran` (baca-saja, tanpa model Tahap 4).
- `php spark dokumen:pdf` membuat `dokumen/pdf/` dari `dokumen/final/`; PDF rancangan sudah dibuat.
- `mpdf/mpdf` 8.3 terpasang.
- `composer uji` di CI (MySQL 8) lulus pada setiap PR.

## Menunggu Tahap 4

Tahap 5 ditutup lebih dulu. Sesuai kesepakatan dua sesi nomor 7, **Tahap 4 menjalankan uji gabungan wizard → cek → bukti** pada satu salinan `main` dan memperbaiki sambungannya. Yang perlu diperiksa:

- Pendaftaran yang disimpan wizard tampil di `/cek-pendaftaran` lewat NPSN dan ID, dan tombol "cetak bukti" di langkah 5 membuka PDF yang benar.
- Tim susulan disimpan Tahap 4 sebagai baris `pendaftaran_beregu` dengan `biaya_tim` 0. Cek dan bukti menampilkannya "Rp0". Tim susulan tanpa baris `pendaftaran_beregu` juga sudah ditangani: tampil "Tanpa biaya (tim sudah terdaftar)".
- Kolom baru yang mungkin ditambahkan Tahap 4 tidak dibaca `DataPendaftaran`. Tambahkan bila perlu tampil di cek atau bukti.
- Bila pembaca pendaftaran Tahap 4 lebih lengkap, `DataPendaftaran` boleh diganti dengan model Tahap 4, asalkan bentuk lariknya sama (lihat komentar kelas).

## Yang dibawa ke tahap berikutnya

- **Revisi rancangan berikutnya:** bagian 8 (cek dan bukti) disesuaikan dengan K49 dan K52: cek dan bukti tidak menampilkan tanggal lahir dan nomor dada.

- **Tahap 6:** rekap PDF panel admin bisa memakai mPDF dan pola `BuktiPendaftaran`. Pendaftaran yang diubah admin langsung tampil di cek dan bukti, karena keduanya membaca nilai tersimpan.
- **Tahap 7:** keluaran PDF tidak boleh ikut diperkecil oleh filter HTML produksi. Uji lokal di lingkungan tanpa MySQL 8 (MariaDB) menggagalkan 4 uji struktur migrasi karena penulisan tipe kolom berbeda; CI tetap menjadi acuan.
- Uji yang memanggil `php spark data:awal` menulis gambar bawaan ke folder unggahan. Uji Tahap 5 memakai `UnggahanSementara`, tetapi `public/unggahan/halaman-depan/` di komputer pengembang bisa berisi sisa salinan dari uji lain (folder ini diabaikan git).
