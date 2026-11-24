# F11-19 Salinan cadangan Cloudflare R2 diatur di panel admin

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 25 November 2026 |

## Tujuan

Admin mengatur salinan cadangan harian ke Cloudflare R2 dari **Pengaturan > Cadangan**, termasuk token, dan bisa melihat isi bucket untuk memastikan cadangan sampai (K185). Tidak ada lagi isian R2 di `docker/.env` dan container rclone.

## Hasil

- [x] Tabel `salinan_r2` (satu baris): ID akun, bucket, Access Key ID, Secret Access Key terenkripsi `encryption.key`, folder, hasil kiriman terakhir. Masuk `CadanganLengkap::TABEL_DILEWATI`.
- [x] `Cadangan/KlienR2`: tanda tangan AWS SigV4 tanpa pustaka tambahan (dicocokkan dengan contoh resmi Amazon S3), tulis objek dari berkas tanpa dimuat ke memori, daftar isi folder, alamat unduh bertanda tangan, pesan galat yang menyebut perbaikannya.
- [x] `Cadangan/SalinanR2`: simpan, periksa (tulis `uji-sambungan.txt` lalu baca isi folder), isi bucket untuk halaman, kirim dan catat hasilnya.
- [x] Pengaturan > Cadangan: bagian "Salinan di Cloudflare R2" dengan status, Periksa sambungan, isi folder bertombol Unduh, formulir atur atau ubah (dengan kata sandi admin, hanya disimpan bila sambungan lulus), dan Matikan salinan. Perubahan dicatat di riwayat (`RiwayatAdmin::ATUR_R2`) tanpa rahasia.
- [x] `cadangan:buat` mengirim zip baru ke R2 bila sudah diatur; gagal kirim = kode keluar 1, zip tetap di server.
- [x] Layanan `salin-r2` dan variabel `R2_*` dihapus dari `docker/compose.yaml` dan `docker/.env.example`.
- [x] Panduan produksi revisi 15 (bagian 7: dasbor Cloudflare untuk bucket, lifecycle, dan token; panel untuk pengaturannya; cron satu perintah), rancangan revisi 42, K185, ATURAN, PETA-KODE.

## Berkas dan uji

`app/Libraries/Cadangan/{KlienR2,SalinanR2,CadanganLengkap}.php`, `app/Controllers/Admin/Pengaturan/CadanganController.php`, `app/Views/admin/pengaturan/cadangan.php`, `app/Commands/BuatCadangan.php`, migrasi `2026-11-25-000200_BuatTabelSalinanR2`, `app/Config/Routes.php`, `app/Libraries/Panel/RiwayatAdmin.php`, `docker/`. Uji u `KlienR2Test`, f `PengaturanSalinanR2Test`, `PengaturanCadanganTest`, b `CadanganBuatTest`.

## Selesai bila

Uji lulus dan job CI hijau. Di server, admin mengisi pengaturan R2 di panel, `spark cadangan:buat` menampilkan "Dikirim ke Cloudflare R2", dan zip itu tampil di Isi folder.

## Catatan

- R2 dihubungi dengan gaya jalur `https://<id-akun>.r2.cloudflarestorage.com/<bucket>/<kunci>` dan wilayah `auto`.
- Tombol Unduh mengarah langsung ke R2 dengan alamat bertanda tangan 10 menit, jadi berkas besar tidak lewat server web dan batas 100 detik Cloudflare.
- Web tidak pernah menghapus objek di bucket; masa simpan diatur aturan lifecycle di dasbor Cloudflare.
