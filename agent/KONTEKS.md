# Konteks Proyek

## Apa ini

Web pendaftaran **MilkLife Athletics Challenge Seri 2 – 2026** untuk sekolah se-Karesidenan Pati (Blora, Rembang, Pati, Kudus, Jepara). Acara berada di bawah Bakti Olahraga Djarum Foundation, berlangsung 9–12 Desember 2026 di Supersoccer Arena, Kudus.

Web ini mengurus pendaftaran, cek pendaftaran, rekap panitia, dan sejak K124 juga hari lomba (daftar ulang lewat kode QR, cetak nomor atlet (BIB), daftar peserta per nomor), hasil lomba semua babak, dan klasemen juara umum sekolah per jenjang. Aturan teknis lomba di lapangan (urutan pelari, penjurian) tetap di luar cakupan.

## Pengguna

- **Ofisial sekolah**: tanpa akun, mendaftar lewat wizard, memeriksa rekap lewat NPSN atau ID pendaftaran.
- **Admin**: satu peran, mengatur seluruh aturan, memeriksa pembayaran, dan (Tahap 8) mencatat hasil lomba.
- **Pengunjung umum**: melihat hasil dan klasemen yang sudah diterbitkan (K124).

## Teknologi

CodeIgniter 4.7 (dipasang lewat Composer dari sumber resmi), PHP 8.2+ (Laragon pemilik: PHP 8.3), MySQL 8, Nginx dari Laragon (pengembangan dan produksi), Bootstrap 5.3, DataTables, mPDF, PhpSpreadsheet.

## Dokumen utama

- Rancangan: `dokumen/final/rancangan-web-pendaftaran.md` (disetujui pemilik, K17; revisi 17 dan 18 memuat K116–K125).
- Rencana implementasi per tahap: `dokumen/draf/rencana-implementasi-tahap-<n>.md` (Tahap 1–8).
- Aturan yang berlaku: `agent/ATURAN.md`; letak kode: `agent/PETA-KODE.md`.
- Arsip tahap selesai: `agent/arsip/`.
- Keputusan pemilik: `agent/KEPUTUSAN.md`.
- Bahan asli: `sumber/flyer/` dan juknis resmi `sumber/juknis/` (K112).
- Data sekolah: `data/sekolah/sekolah.json` (sumber dan cara memperbarui di `data/README.md`).

## Hal yang mudah keliru

- Biaya individu mengikuti **kelompok umur**, bukan jenjang sekolah seperti tertulis di flyer.
- Nomor atlet (BIB) dibuat saat pendaftaran **ditandai lunas**, bukan saat mendaftar.
- NISN yang sudah terdaftar **ditolak**, termasuk pada pendaftaran susulan.
- Kelompok umur ditentukan **tahun lahir**, bukan usia (K36); usia hanya ditampilkan. Tidak ada tanggal patokan usia (K37).
- Tahun lahir menentukan kelompok umur, tetapi **jenjang sekolah membatasinya**: atlet ditolak bila kelompok umurnya tidak dibuka untuk jenjang sekolahnya (K23). Jenjang yang boleh ikut diatur admin, tidak tertulis di kode.
- Halaman cek menampilkan data siswa penuh atas persetujuan MoU. Repo ini publik, jadi data siswa tidak boleh masuk git.
- Nomor atlet dibuat saat ditandai Lunas dan **tidak pernah dipakai ulang**, juga setelah pendaftaran dihapus atau dikembalikan ke Menunggu. Dua pengecualian, keduanya hanya sebelum nomor dibagikan saat pertemuan teknik (technical meeting): `nomor-dada:reset` (K94) dan tombol **Urutkan nomor atlet** di halaman Atlet yang membuat ulang semua nomor berurutan per sekolah (K111).
- Admin yang mengubah pendaftaran Lunas: bila total berubah, status kembali ke Menunggu (K32); bila tetap, atlet baru langsung mendapat nomor atlet (K59). ID pendaftaran tidak berubah (K63).
- Satu sekolah boleh punya **beberapa regu** per nomor beregu (Regu A, B, C, …; bawaannya tidak dibatasi, batas bisa diisi per nomor lomba, K107). Setiap anggota regu ditandai inti atau cadangan; inti tepat sejumlah inti nomor; biaya beregu **per regu** (K105, menggantikan K07). Nama regu dari daftar admin (kosong = huruf otomatis) atau diketik ofisial, dan kata awalannya ("Regu", "Tim") diatur di Pengaturan > Pendaftaran (K106, K107).
- Bila sekolah hanya punya **satu regu** di satu nomor, tampilan untuk ofisial (wizard, cek, bukti) **tidak menulis nama regunya**, dan wizard menyembunyikan pilihan regu sampai ofisial menekan "Daftarkan regu lain"; data tetap menyimpan nama regu "A", dan panel admin serta rekap tetap menampilkannya (K108).
- Pendaftaran susulan ke regu sekolah yang sudah terdaftar wajib dikonfirmasi ofisial di wizard (K57), tanpa biaya regu; atlet susulan boleh menjadi inti dengan menurunkan inti lama. Admin tidak membuat pendaftaran baru dari panel (K58).
- Foto ofisial dan bukti bayar disimpan di `writable/unggahan/` (di luar `public/`) dan hanya disajikan lewat rute admin.
- Nama sekolah **tidak unik**: data resmi memakai nama umum untuk banyak madrasah. Sekolah selalu dikenali lewat NPSN; nama pembeda diberi admin di menu Sekolah (K97), sekaligus untuk semua nama kembar lewat "Tambahkan nama desa" (K114), dan sekolah yang disunting tidak ditimpa impor ulang.
- Pendaftar boleh **mengusulkan nama sekolah** yang lebih lengkap; pendaftarannya langsung memakai nama itu sampai admin menyetujui atau menolak (K99).
- Saat admin mengubah pendaftaran, kelompok umur dan nomor lomba yang sudah dimatikan **tetap boleh dipertahankan** untuk atlet yang sudah memakainya, tidak untuk atlet atau nomor baru (K98).
- Admin bisa **menolak atlet** dari pendaftaran Menunggu (K116): atletnya dikeluarkan sehingga NISN bisa didaftarkan lagi, salinannya disimpan di `atlet_ditolak` dan tampil di Cek Pendaftaran sampai NISN itu terdaftar lagi. Pendaftaran yang semua atletnya ditolak tetap berstatus `menunggu` di basis data, tetapi tampil **"Semua atlet ditolak"** tanpa tagihan (K117); atlet ditolak punya menu admin sendiri, **Atlet ditolak**.
- Pendaftaran Lunas dengan NPSN sama **digabung otomatis** ke pendaftaran yang Lunas paling dulu (K109); pendaftaran yang pindah dihapus, tetapi ID dan kode bukti lamanya dicatat di `pendaftaran_gabungan` sehingga cek dan kode QR lama tetap sampai. Jadi satu NPSN paling banyak punya satu pendaftaran Lunas; yang Menunggu dan yang tanpa NPSN tidak digabung.
- **Cloudflare Turnstile** (K122) menjaga kirim pendaftaran, Cek Pendaftaran, dan masuk admin; mati bila kunci di `.env` kosong. Kunci rahasia tidak pernah masuk git.
- **Juara umum** dihitung per jenjang dari jumlah juara I, lalu II, lalu III, hanya dari babak Final; kontingen = sekolah per NPSN; regu juara = satu piala (K124).
