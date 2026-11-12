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

- Rancangan: `dokumen/final/rancangan-web-pendaftaran.md` (disetujui pemilik, K17; revisi 1 dan 2 disetujui, K27 dan K35).
- Rencana implementasi Tahap 1: `dokumen/draf/rencana-implementasi-tahap-1.md`.
- Rencana implementasi Tahap 2: `dokumen/draf/rencana-implementasi-tahap-2.md`.
- Rencana implementasi Tahap 3: `dokumen/draf/rencana-implementasi-tahap-3.md`.
- Rencana implementasi Tahap 4: `dokumen/draf/rencana-implementasi-tahap-4.md`.
- Rencana implementasi Tahap 5: `dokumen/draf/rencana-implementasi-tahap-5.md`.
- Rencana implementasi Tahap 6: `dokumen/draf/rencana-implementasi-tahap-6.md`.
- Arsip tahap selesai: `agent/arsip/`.
- Keputusan pemilik: `agent/KEPUTUSAN.md`.
- Bahan asli: `sumber/flyer/`.
- Data sekolah: `data/sekolah/sekolah.json`.

## Hal yang mudah keliru

- Biaya individu mengikuti **kelompok umur**, bukan jenjang sekolah seperti tertulis di flyer.
- Nomor dada dibuat saat pendaftaran **ditandai lunas**, bukan saat mendaftar.
- NISN yang sudah terdaftar **ditolak**, termasuk pada pendaftaran susulan.
- Kelompok umur ditentukan **tahun lahir**, bukan usia (K36); usia hanya ditampilkan. Tidak ada tanggal patokan usia (K37).
- Tahun lahir menentukan kelompok umur, tetapi **jenjang sekolah membatasinya**: atlet ditolak bila kelompok umurnya tidak dibuka untuk jenjang sekolahnya (K23). Jenjang yang boleh ikut diatur admin, tidak tertulis di kode.
- Halaman cek menampilkan data siswa penuh atas persetujuan MoU. Repo ini publik, jadi data siswa tidak boleh masuk git.
- Nomor dada dibuat saat ditandai Lunas dan **tidak pernah dipakai ulang**, juga setelah pendaftaran dihapus atau dikembalikan ke Menunggu.
- Admin yang mengubah pendaftaran Lunas: bila total berubah, status kembali ke Menunggu (K32); bila tetap, atlet baru langsung mendapat nomor dada (K59). ID pendaftaran tidak berubah (K63).
- Pendaftaran susulan ke tim beregu sekolah yang sudah terdaftar wajib dikonfirmasi official di wizard (K57), tanpa biaya tim. Admin tidak membuat pendaftaran baru dari panel (K58).
- Foto penanggung jawab dan bukti bayar disimpan di `writable/unggahan/` (di luar `public/`) dan hanya disajikan lewat rute admin.
