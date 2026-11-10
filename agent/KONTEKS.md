# Konteks Proyek

## Apa ini

Web pendaftaran **MilkLife Athletics Challenge Seri 2 – 2026** untuk sekolah se-Karesidenan Pati (Blora, Rembang, Pati, Kudus, Jepara). Acara berada di bawah Bakti Olahraga Djarum Foundation, berlangsung 9–12 Desember 2026 di Supersoccer Arena, Kudus.

Web ini mengurus pendaftaran, cek pendaftaran, dan rekap panitia. Pelaksanaan lomba di luar cakupan.

## Pengguna

- **Official sekolah**: tanpa akun, mendaftar lewat wizard, memeriksa rekap lewat NPSN atau ID pendaftaran.
- **Admin**: satu peran, mengatur seluruh aturan dan memeriksa pembayaran.

## Teknologi

CodeIgniter 4.7 (dipasang lewat Composer dari sumber resmi), PHP 8.2+ (Laragon pemilik: PHP 8.3), MySQL 8, Nginx dari Laragon (pengembangan dan produksi), Bootstrap 5.3, DataTables, mPDF, PhpSpreadsheet.

## Dokumen utama

- Rancangan: `dokumen/final/rancangan-web-pendaftaran.md` (disetujui pemilik, K17).
- Rencana implementasi Tahap 1: `dokumen/draf/rencana-implementasi-tahap-1.md`.
- Keputusan pemilik: `agent/KEPUTUSAN.md`.
- Bahan asli: `sumber/flyer/`.
- Data sekolah: `data/sekolah/sekolah.json`.

## Hal yang mudah keliru

- Biaya individu mengikuti **kelompok umur**, bukan jenjang sekolah seperti tertulis di flyer.
- Nomor dada dibuat saat pendaftaran **ditandai lunas**, bukan saat mendaftar.
- NISN yang sudah terdaftar **ditolak**, termasuk pada pendaftaran susulan.
- Batas usia kelompok umur **tidak punya nilai awal**; admin mengisinya dari juknis.
- Halaman cek menampilkan data siswa penuh atas persetujuan MoU. Repo ini publik, jadi data siswa tidak boleh masuk git.
