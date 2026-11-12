# Arsip Tahap 2 — Pengaturan

Selesai: 12 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-2.md`](../../dokumen/draf/rencana-implementasi-tahap-2.md), termasuk bagian "Perubahan saat pengerjaan".

| Tugas | Status | PR |
|---|---|---|
| [F2-01 Fondasi pengaturan](../tugas/F2-01-fondasi-pengaturan.md) | selesai | #13 |
| [F2-02 Acara dan pendaftaran](../tugas/F2-02-acara-pendaftaran.md) | selesai | #16 |
| [F2-03 Kelompok umur](../tugas/F2-03-kelompok-umur.md) | selesai | #18 |
| [F2-04 Nomor lomba](../tugas/F2-04-nomor-lomba.md) | selesai | #15 |
| [F2-05 Pembayaran dan halaman depan](../tugas/F2-05-pembayaran-halaman-depan.md) | selesai | #17 |
| [F2-06 Jadwal dan kontak](../tugas/F2-06-jadwal-kontak.md) | selesai | #20 |
| [F2-07 Unduhan](../tugas/F2-07-unduhan.md) | selesai | #21 |
| [F2-08 Akun admin](../tugas/F2-08-akun-admin.md) | selesai | #22 |
| [F2-09 Kelengkapan dan dasbor](../tugas/F2-09-kelengkapan-dasbor.md) | selesai | #19 |
| [F2-10 Uji gabungan dan penutupan](../tugas/F2-10-penutupan-tahap-2.md) | selesai | PR penutupan |

Catatan koordinator: PR #11 (rencana, K24–K27), #12 (rancangan revisi 2, K28–K35), #14 (rancangan revisi 3, K36–K38).

## Hasil

- Menu **Pengaturan** dengan sembilan halaman pengaturan dan halaman **Akun**; tanda lengkap atau belum pada lima halaman wajib.
- Kelompok umur ditentukan tahun lahir (K36), dibatasi jenjang sekolah yang dicentang admin (K23, K24).
- `php spark data:awal`: data acara, jadwal pendaftaran, lima kelompok umur dengan batas tahun lahir dan awalan nomor dada, 69 nomor lomba dari flyer.
- Dasbor: daftar kelengkapan dengan tautan ke halaman yang perlu dilengkapi, dan keadaan pendaftaran (sedang disiapkan, belum dibuka, terbuka, ditutup).
- Kelas murni `KelengkapanPengaturan` dan `BukaTutupPendaftaran` siap dipakai halaman depan (Tahap 3) dan wizard (Tahap 4).
- `composer uji`: 704 uji lulus pada `main` setelah penutupan.

## Yang dibawa ke tahap berikutnya

- **Tahap 3:** halaman depan masih statis ("Pendaftaran sedang disiapkan") walau pendaftaran sudah terbuka di dasbor; Tahap 3 memakai `BukaTutupPendaftaran` dan isi pengaturan halaman depan, jadwal, unduhan, dan kontak.
- **Tahap 4:** SLB ditolak (K28); kelompok umur dari tahun lahir dan jenjang (K36, K23); usia hanya ditampilkan, dihitung pada tanggal mulai acara (K37). Mengubah jumlah inti atau batas anggota nomor beregu yang sudah dipakai belum dicegah.
- **Tahap 6:** bukti bayar diunggah admin, paling banyak 5 gambar (K34); pendaftaran Lunas yang totalnya berubah kembali ke Menunggu (K32).
- Unggahan yang melebihi `post_max_size` PHP ditolak pemeriksaan CSRF dengan halaman galat umum, bukan pesan ramah; di peramban sudah dicegah `data-ukuran-maksimal`. Perlu penanganan di filter.
- Ganti kata sandi tidak mengeluarkan sesi lain milik akun yang sama (sesi lain berakhir sendiri setelah 2 jam); perlu penanda versi kata sandi bila diinginkan.
- Judul tab admin dan halaman masuk masih menulis "MilkLife" langsung.
- Cloudflare Tunnel (K31): alamat IP pengunjung dibaca dari `CF-Connecting-IP` pada tahap pemasangan.
- Batas unggah PHP Laragon: `upload_max_filesize = 10M`, `post_max_size = 12M` (sudah di `README.md`).
- Juknis resmi masih menyusul (K30).
