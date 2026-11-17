# Rencana Implementasi Tahap 8 — Hari Lomba dan Hasil

| | |
|---|---|
| Status | Draf, **disetujui pemilik 17 November 2026 termasuk delapan asumsinya (K125)** |
| Tanggal | 17 November 2026 |
| Dasar | Analisis 14 November 2026 (`dokumen/draf/analisis-bug-dan-saran-pengembangan.md`, saran S2, S3, S7); keputusan K118–K124, terutama K124 (cakupan hari lomba, hasil, dan klasemen); juknis `sumber/juknis/petunjuk-teknis-seri2-2026.pdf` bagian 11 |

## Tujuan

Untuk pelaksana tiap tugas Tahap 8 (sub-sesi) dan pemilik yang meninjau. Setelah membaca, pelaksana tahu isi tugasnya, berkas yang boleh disentuh, urutan dengan tugas lain, dan aturan yang berlaku.

Batas waktu: pertemuan teknik **6 Desember 2026**, lomba **9–12 Desember 2026**. F8-01 sampai F8-03 siap jauh sebelum 6 Desember; F8-04 dan F8-05 sebelum 9 Desember.

## Hasil Tahap 8

1. **Riwayat tindakan admin (S2)**: setiap tindakan admin pada pendaftaran tercatat (siapa, kapan, apa, ringkasan perubahan) dan tampil di rincian pendaftaran.
2. **Bukti bayar baru (S3)**: daftar pendaftaran menandai pendaftaran yang punya bukti bayar belum diperiksa, bisa disaring, dan jumlahnya tampil di dasbor.
3. **Hari lomba (S7)**: admin memindai kode QR bukti pendaftaran untuk daftar ulang (status, nomor atlet, daftar atlet, tandai hadir); nomor atlet (BIB) dicetak massal per kelompok umur; daftar peserta per nomor lomba dicetak untuk pemanggilan atlet (roll call) dan juri.
4. **Hasil lomba**: admin membuat babak per nomor lomba, mengisi prestasi semua babak, web mengurutkan peringkat; juara I–III dari babak Final; hasil terbit per nomor lomba.
5. **Klasemen sekolah**: juara umum per jenjang dihitung dari jumlah juara I, lalu II, lalu III; tampil di halaman depan untuk nomor yang sudah terbit; unduhan PDF dan Excel.

## Tugas dan urutan

| Tugas | Isi singkat | Bergantung pada | Gelombang |
|---|---|---|---|
| F8-01 Riwayat tindakan admin | Tabel `riwayat_admin`, pencatat, tampilan di rincian | F7-24 dan F7-25 masuk `main` (berkas pengendali yang sama) | 2 (sub-sesi d) |
| F8-02 Penanda bukti bayar baru | Kolom diperiksa di `bukti_bayar`, penanda, saringan, kartu dasbor | — | 2 (sub-sesi d, sesudah F8-01) |
| F8-03 Fitur hari lomba | Daftar ulang lewat QR, tandai hadir, BIB massal, daftar peserta per nomor | — | 2 (sub-sesi e) |
| F8-04 Hasil lomba | Jenis hasil per nomor, babak, isian prestasi, peringkat, Terbitkan, halaman hasil publik | Wawancara (K124) | 2 (sub-sesi f) |
| F8-05 Klasemen sekolah | Klasemen per jenjang, halaman publik, unduhan PDF dan Excel | F8-04 (sub-sesi yang sama) | 2 (sub-sesi f) |
| F8-06 Penutupan Tahap 8 | Uji alur hari lomba dan hasil di peramban, arsip, progres | Semua; dijalankan bersama F7-06 | Akhir (koordinator) |

Paling banyak 3 sub-sesi berjalan bersamaan (K38, K65). F7-30 (pecah berkas besar) baru dimulai setelah F8-01 masuk `main`.

## Aturan untuk semua sub-sesi

1. **`main` adalah sumber kebenaran.** Di awal tugas dan sebelum PR: `git fetch origin main`, `git rebase origin/main`, uji lagi. Satu tugas, satu PR, judul berbahasa Indonesia. Sub-sesi tidak menggabung PR; pemilik yang menggabung.
2. **Uji**: selama bekerja `vendor/bin/phpunit --filter <NamaUji>`; sebelum PR `composer uji` lulus.
3. **Kepemilikan berkas** mengikuti tabel "Pembagian berkas". Berkas bersama (`app/Config/Routes.php`, menu admin, `agent/PROGRES.md`) hanya ditambah di blok sendiri, seminimal mungkin.
4. **Tanpa pustaka baru.** mPDF dan PhpSpreadsheet sudah ada. Pemindai QR di peramban memakai fitur bawaan peramban, bukan pustaka (lihat F8-03).
5. **`agent/KEPUTUSAN.md`**: sub-sesi tidak menambah keputusan; pertanyaan untuk pemilik ditulis di deskripsi PR dan berkas tugas, lalu dicatat koordinator.
6. **Data uji** memakai nama, NISN, dan NPSN karangan. Data siswa, foto, cadangan, dan rahasia tidak masuk git.
7. **Bahasa dan atribusi**: semua tulisan berbahasa Indonesia; commit atas nama Arumi Studios (`226232558+arumiforge@users.noreply.github.com`) tanpa baris `Co-Authored-By`, tautan sesi, atau tulisan "Generated with …" di commit, PR, kode, dan dokumen. Berkas `sumber/` tidak diubah.
8. **Ekspor/impor pindah server (K76)** dan **cadangan** ikut membawa tabel baru; **penggabungan (K109)** memindahkan baris yang menunjuk pendaftaran; **hapus pendaftaran** menangani baris yang menunjuknya.

## Kesepakatan teknis

### Riwayat tindakan admin (F8-01)

- Migrasi tabel `riwayat_admin`: `id`, `pendaftaran_id` (boleh kosong setelah pendaftaran dihapus), `id_pendaftaran` (teks ID pendaftaran, tetap ada setelah dihapus), `admin_id`, `nama_admin` (salinan saat itu), `tindakan` (kode pendek), `ringkasan` (teks berbahasa Indonesia), `dibuat_pada`.
- Tindakan yang dicatat: Lunas (satu per satu dan massal), kembali ke Menunggu, Ubah (ringkasan perubahan: atlet ditambah/dihapus/diubah, total lama → baru, status berubah karena K32), Tolak atlet, Hapus pendaftaran, hapus bukti bayar, unggah bukti bayar oleh admin, Urutkan nomor atlet (dicatat sekali per pendaftaran yang nomornya berubah, atau satu baris umum), gabung (di pendaftaran tujuan: "Digabung dari MAC…").
- Satu pustaka pencatat (`app/Libraries/Panel/RiwayatAdmin.php`) dipanggil di dalam transaksi tindakan yang sama.
- Rincian pendaftaran menampilkan bagian "Riwayat" (terbaru di atas). Riwayat pendaftaran yang sudah dihapus bisa dicari di halaman kecil `admin/riwayat` (pencarian ID).

### Penanda bukti bayar baru (F8-02)

- Kolom `bukti_bayar.diperiksa_pada` dan `diperiksa_oleh`. Bukti yang diunggah admin langsung dianggap diperiksa; bukti dari ofisial (wizard, Cek Pendaftaran) belum.
- Bukti ditandai diperiksa saat pendaftaran ditandai Lunas, atau saat admin menekan "Sudah diperiksa" di rincian. Membuka rincian saja tidak menandai.
- Daftar pendaftaran: tanda "Bukti baru" dan saringan "Bukti baru belum diperiksa". Dasbor: kartu jumlah pendaftaran dengan bukti baru (pendaftaran "Semua atlet ditolak" tetap tidak dihitung, K117).
- Bila F8-01 sudah ada, "Sudah diperiksa" ikut dicatat di riwayat.

### Hari lomba (F8-03)

- **Daftar ulang**: halaman `admin/daftar-ulang`. Admin memindai kode QR bukti pendaftaran dengan kamera (fitur `BarcodeDetector` bawaan peramban, tersedia di Chrome Android) atau mengetik ID pendaftaran bila peramban tidak mendukung. Hasilnya: nama sekolah, status (Lunas, Menunggu, Semua atlet ditolak), daftar atlet dengan nomor atlet (BIB), nomor lomba, dan regu, serta tombol **Tandai hadir** per atlet dan untuk semua atlet. Kode QR lama yang sudah digabung (K109) diarahkan ke pendaftaran gabungan.
- Bila admin yang sedang masuk membuka alamat kode QR dengan aplikasi kamera ponsel, halaman bukti menampilkan tautan ke daftar ulang pendaftaran itu (atau langsung mengalihkan; lihat asumsi 3).
- Kolom baru: `atlet.hadir_pada` dan `atlet.hadir_oleh`. Batal hadir tersedia untuk salah tekan.
- **BIB massal**: halaman `admin/nomor-atlet/cetak`: pilih kelompok umur (atau semua), PDF lewat mPDF, satu BIB per setengah A4 mendatar (dua per halaman A4): nomor atlet besar, nama atlet, nama sekolah, kelompok umur. Urut nomor atlet. Hanya atlet pendaftaran Lunas.
- **Daftar peserta per nomor lomba**: pilih nomor lomba, PDF dan Excel: No, No. Atlet, nama, sekolah, tanda hadir, kolom kosong untuk catatan juri; nomor beregu per regu dengan anggota inti dan cadangan. Satu PDF bisa memuat semua nomor satu kelompok umur, satu nomor per halaman.

### Hasil lomba (F8-04)

- Kolom `nomor_lomba.jenis_hasil`: `waktu` (makin kecil makin baik), `jarak` (makin besar), `poin` (makin besar). Diatur di Pengaturan > Nomor lomba; data awal mengikuti juknis (lari dan estafet = waktu, Turbo Throw dan lompat = jarak, Tri Lomba = poin). Satuan disimpan bilangan bulat: waktu dalam perseratus detik, jarak dalam sentimeter, poin apa adanya.
- Tabel `babak`: `id`, `nomor_lomba_id`, `nama` (misalnya "Penyisihan seri 1", "Final"), `final` (ya/tidak, satu Final per nomor), `urutan`. Admin menambah babak per nomor; nomor yang langsung final (1000 m, Turbo Throw) cukup satu babak Final.
- Tabel `hasil`: `id`, `babak_id`, peserta (atlet untuk nomor individu; regu untuk nomor beregu), `prestasi` (boleh kosong), `keterangan` (kosong, DNS tidak start, DNF tidak finis, DQ diskualifikasi), `peringkat`. Peserta babak dipilih dari atlet Lunas di nomor itu (babak berikutnya bisa disalin dari yang lolos).
- Peringkat dihitung otomatis dari prestasi menurut jenis hasil; prestasi sama = peringkat sama; DNS/DNF/DQ tanpa peringkat. Admin bisa mengoreksi peringkat Final secara manual (misalnya foto finis), dengan tanda "diubah admin".
- **Tri Lomba**: admin mengisi poin akhir yang sudah dikonversi panitia (K124 butir 3).
- **Terbitkan**: kolom `nomor_lomba.hasil_terbit_pada`. Tombol Terbitkan dan Tarik kembali per nomor. Hanya nomor terbit yang tampil di halaman publik `hasil` (tautan dari halaman depan).
- Masukan prestasi divalidasi di server: format waktu `m:ss,cc` atau `ss,cc`, jarak `m,cc`; pesan galat menyebut contoh yang benar.

### Klasemen sekolah (F8-05)

- Pustaka murni `app/Libraries/Hasil/Klasemen.php` (tanpa ketergantungan HTTP, wajib uji unit): masukan daftar juara Final (peringkat 1–3, sekolah, jenjang), keluaran klasemen per jenjang.
- Juara dihitung dari babak Final: atlet individu = satu piala sesuai peringkatnya; regu juara = satu piala untuk sekolahnya (K124 butir 4). Peringkat sama di Final memberi piala yang sama kepada masing-masing.
- Kontingen = NPSN; pendaftaran tanpa NPSN = kontingen sendiri. Jenjang kontingen = jenjang pendaftaran.
- Urutan: juara I terbanyak, lalu juara II, lalu juara III; bila masih sama, peringkat bersama (K124 butir 5). Juara umum I–III per jenjang ditandai.
- Halaman admin `admin/klasemen` (semua nomor, termasuk yang belum terbit, dengan tanda) dan halaman publik `hasil/klasemen` (hanya nomor terbit). Unduhan PDF dan Excel untuk hasil per nomor dan klasemen.

## Pembagian berkas

| Tugas | Boleh disentuh | Tidak disentuh |
|---|---|---|
| F8-01, F8-02 (sub-sesi d) | `app/Libraries/Panel/*` yang mencatat tindakan, `app/Controllers/Admin/PendaftaranController.php`, `BuktiBayarController.php`, `TolakAtletController.php`, `NomorDadaController.php`, `DasborController.php`, `RingkasanDasbor.php`, `TabelSisiServer.php`, `DataPanelPendaftaran.php`, view `admin/pendaftaran/*` dan `admin/dasbor*`, migrasi baru, `PaketPendaftaran.php` (ekspor/impor) | `public/aset/js/pendaftaran.js`, wizard dan Cek Pendaftaran publik, berkas hari lomba dan hasil |
| F8-03 (sub-sesi e) | Pengendali baru `Admin/DaftarUlangController.php`, `Admin/CetakController.php` (nama boleh disesuaikan), pustaka baru `app/Libraries/HariLomba/*`, view baru, JS baru `public/aset/js/daftar-ulang.js`, `BuktiController.php` (tautan admin), migrasi baru, menu admin (satu blok) | Berkas milik sub-sesi d dan f |
| F8-04, F8-05 (sub-sesi f) | Pengendali baru `Admin/HasilController.php`, `Admin/KlasemenController.php`, `HasilController.php` publik, pustaka baru `app/Libraries/Hasil/*`, view baru, pengaturan nomor lomba (kolom jenis hasil), data awal nomor lomba, migrasi baru, halaman depan (satu tautan), menu admin (satu blok) | Berkas milik sub-sesi d dan e |

`app/Config/Routes.php`: setiap sub-sesi menambah rutenya di blok sendiri dengan komentar ID tugas. `PaketPendaftaran.php` dan `HapusPendaftaran.php`/`GabungPendaftaran.php` milik sub-sesi d; sub-sesi e dan f yang perlu menambah tabel ke ekspor/impor atau penggabungan melakukannya setelah d masuk `main`, dengan rebase.

## Uji

- Uji unit untuk aturan murni: urutan peringkat dan seri, klasemen dan pemecah seri, penguraian format prestasi.
- Uji fitur untuk setiap halaman dan tindakan baru, termasuk pencatatan riwayat dan saringan bukti baru.
- F8-06 menjalankan alur hari lomba dan hasil di Chromium 390 dan 1280 piksel bersama F7-06.

## Asumsi yang perlu ditinjau

1. **Riwayat pendaftaran yang dihapus** dicari di halaman kecil `admin/riwayat`, karena rinciannya sudah tidak ada.
2. **Bukti dianggap diperiksa** saat Lunas atau saat admin menekan "Sudah diperiksa"; membuka rincian saja tidak.
3. **Pemindai QR** memakai fitur bawaan peramban (Chrome Android); di peramban lain admin mengetik ID, atau memindai dengan aplikasi kamera ponsel lalu halaman bukti menampilkan tautan "Buka daftar ulang" bila admin sedang masuk (bukti PDF tetap bisa dibuka).
4. **Tandai hadir per atlet**, bukan hanya per pendaftaran, supaya daftar peserta per nomor bisa menandai yang hadir.
5. **Ukuran BIB**: setengah A4 mendatar, dua per halaman; panitia bisa memotongnya. Bila panitia memakai BIB cetakan sponsor, PDF ini dipakai sebagai label tempel.
6. **Satuan prestasi**: waktu perseratus detik, jarak sentimeter; jenis hasil per nomor diatur admin.
7. **Peringkat sama di Final** memberi piala yang sama kepada masing-masing (misalnya dua juara III).
8. **Halaman publik** hasil dan klasemen ada di alamat `hasil` dan `hasil/klasemen`, ditautkan dari halaman depan setelah ada nomor yang terbit.

## Perubahan saat pengerjaan

Belum ada.
