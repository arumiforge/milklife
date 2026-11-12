# Arsip Tahap 3 — Halaman depan

Selesai: 12 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-3.md`](../../dokumen/draf/rencana-implementasi-tahap-3.md), termasuk bagian "Perubahan saat pengerjaan".

| Tugas | Status | PR |
|---|---|---|
| [F3-01 Fondasi halaman depan](../tugas/F3-01-fondasi-halaman-depan.md) | selesai | #25 |
| [F3-02 Bagian lomba](../tugas/F3-02-bagian-lomba.md) | selesai | #27 |
| [F3-03 Jadwal, unduhan, dan kontak](../tugas/F3-03-jadwal-unduhan-kontak.md) | selesai | #28 |
| [F3-04 Perapian sisa Tahap 2](../tugas/F3-04-perapian-tahap-2.md) | selesai | #26 |
| [F3-05 Uji gabungan dan penutupan](../tugas/F3-05-penutupan-tahap-3.md) | selesai | PR penutupan |

Catatan koordinator: PR #24 (rencana, K39–K43).

## Hasil

- Halaman depan (`/`) memakai isi pengaturan, berurutan sesuai K42: kepala (gambar kepala, logo, judul, teks, data acara), keadaan pendaftaran yang sebenarnya, tombol Pendaftaran dan Cek Pendaftaran nonaktif "Segera" (K39), kelompok umur dengan tahun lahir, jenjang, dan biaya, nomor lomba individu dan beregu, jadwal, unduhan, narahubung, media sosial, tombol tanya admin dan bagikan ke WA, dan footer.
- Gambar dan bagian yang belum diisi tidak ditampilkan ke pengunjung (K41); bagian kontak selalu ada karena tombol bagikan.
- Halaman `/unduhan` dan menu Unduhan aktif (K40).
- `App\Libraries\IsiHalamanDepan`: satu-satunya pengumpul data halaman publik; hanya data yang boleh dilihat pengunjung.
- Perapian: kiriman di atas `post_max_size` mendapat pesan ramah; judul tab admin dan halaman masuk memakai nama acara; alamat IP pengunjung dibaca dari `CF-Connecting-IP` hanya bila datang dari komputer server sendiri (K31).
- `composer uji`: JUMLAH_UJI uji lulus pada salinan baru `main` setelah penutupan.

## Yang dibawa ke tahap berikutnya

- **Tahap 4:** isi `IsiHalamanDepan::ALAMAT_PENDAFTARAN`; tombol dan menu Pendaftaran aktif hanya bila `bukaTutup()->terbuka()` (rancangan 6.1). Tata letak publik belum menampilkan pesan kilat (`pesan_galat` dari filter batas unggah ikut terlihat setelah ini). Pembatasan laju memakai `$this->request->getIPAddress()` (sudah membaca `CF-Connecting-IP`). SLB ditolak (K28); kelompok umur dari tahun lahir dan jenjang (K36, K23). Mengubah jumlah inti atau batas anggota nomor beregu yang sudah dipakai belum dicegah.
- **Tahap 5:** isi `IsiHalamanDepan::ALAMAT_CEK_PENDAFTARAN`; Cek hanya bisa dipakai bila pengaturan lengkap.
- **Tahap 6:** bukti bayar diunggah admin, paling banyak 5 gambar (K34); pendaftaran Lunas yang totalnya berubah kembali ke Menunggu (K32).
- **Tahap 7:** pengecilan gambar dan minify; ganti kata sandi belum mengeluarkan sesi lain milik akun yang sama.
- Halaman masuk (tata letak polos) tidak menampilkan pesan kilat; kiriman kebesaran ke halaman masuk hanya kembali ke formulir (tidak ada unggahan di sana).
- Juknis resmi masih menyusul (K30).
