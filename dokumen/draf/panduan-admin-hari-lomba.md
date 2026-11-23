# Panduan Admin Hari Lomba

| | |
|---|---|
| Status | Draf |
| Tanggal | 23 November 2026 |
| Dasar | Rancangan web bagian 9 dan 18; K124, K140, K141, K172–K176; tugas F11-01 sampai F11-12 |

## Tujuan

Panduan ini untuk admin panitia yang memakai panel admin dari pertemuan teknik 6 Desember sampai lomba selesai 12 Desember 2026. Setelah membacanya, Anda tahu apa yang perlu disiapkan sebelum lomba, apa yang dikerjakan selama lomba, dan cara memperbaiki kesalahan yang sering terjadi.

Semua menu yang disebut ada di sidebar panel admin. Di ponsel, buka sidebar dengan tombol menu di kiri atas, atau cari nama menunya di kotak **Cari menu** (tekan `/` di papan ketik untuk langsung mengetik).

## 1. Sebelum pertemuan teknik

1. **Unduh cadangan lengkap** di **Pengaturan → Cadangan**. Simpan berkasnya di luar komputer server.
2. **Periksa nomor lomba** di **Pengaturan → Nomor lomba**. Buka setiap nomor, lalu periksa bagian **Pelaksanaan lomba**:
   - **Batas peserta minimal**: isi sesuai petunjuk teknis. Kosongkan bila tidak ada batas. Batas dihitung per atlet untuk nomor individu dan per regu untuk nomor beregu.
   - **Tidak dilombakan**: nyalakan bila panitia memutuskan nomor itu tidak dilombakan, lalu tulis alasannya. Alasan ini tampil di halaman Hasil publik. Nomor itu keluar dari jadwal, seri, lembar juri, videotron, dan klasemen. Sakelar ini terpisah dari sakelar **Aktif** pendaftaran.
   - **Cara pencatatan waktu** (nomor lari): pilih **Elektronik** bila memakai foto finis, atau **Tangan** bila memakai stopwatch. Waktu tangan dibulatkan ke atas ke 0,1 detik, misalnya 12,34 menjadi 12,4. Cara ini masih bisa diubah setelah ada prestasi.
   - **Spesifikasi alat** (nomor lempar): misalnya "Peluru 3 kg". Rekor dengan alat berbeda dicatat terpisah.
3. **Atur susunan klasemen** di **Pengaturan → Hasil → Susunan klasemen**. Setiap kelompok umur diberi nomor klasemen. Kelompok umur bernomor sama digabung dalam satu klasemen, misalnya KU 8 dan KU 10 diberi nomor 1. Pilih juga metode **Medali** atau **Poin**.
4. **Susun jadwal** di **Jadwal pertandingan**: isi tanggal, jam mulai, dan lokasi setiap babak. Setelah jadwal lengkap, isi nomor awal (bawaan 101) lalu tekan **Beri nomor acara**. Semua babak bertanggal diberi nomor berurutan (Acara 101, 102, …), dan nomor lama diganti. Ubah nomor satu babak langsung di barisnya bila perlu; nomor tidak boleh sama dengan babak lain.
5. **Cetak BIB** di **Cetak BIB**, per kelompok umur atau per sekolah.

## 2. Mengganti atlet

Sekolah boleh mengganti atlet yang berhalangan dengan siswa lain dari sekolah yang sama. Siapkan nama atlet lama serta nama, NISN, dan tanggal lahir atlet pengganti.

1. Buka **Pendaftaran**, cari sekolahnya, lalu buka rinciannya. Pendaftaran harus **Lunas**.
2. Tekan **Ganti** di baris atlet yang diganti. Halaman **Ganti atlet** terbuka.
3. Isi data atlet pengganti, lalu simpan. Jenis kelamin mengikuti atlet lama.

Hasilnya:

- Atlet pengganti memakai nomor lomba, regu, dan status Inti/Cadangan atlet lama. Biaya dan status Lunas tidak berubah.
- Atlet pengganti mendapat **nomor atlet (BIB) baru**. Cetak BIB-nya dari **Cetak BIB**. BIB atlet lama tidak dipakai lagi.
- Bila atlet lama sudah masuk seri yang belum dikunci, tempatnya pindah ke atlet pengganti.

Penggantian ditolak bila:

- seri babak atlet lama sudah dikunci, atau atlet lama sudah punya hasil (untuk nomor beregu: regunya sudah punya hasil);
- kelompok umur atau jenis kelamin atlet pengganti tidak cocok dengan nomor lombanya;
- NISN atlet pengganti sudah terdaftar.

Pesan di layar menyebut alasannya. Atlet lama tetap tercatat di riwayat dan tidak tampil sebagai atlet ditolak.

## 3. Hari lomba

### Daftar ulang

Buka **Daftar ulang**, pindai kode QR bukti pendaftaran atau ketik ID pendaftaran, lalu tekan **Tandai hadir**. Setelah ada atlet yang ditandai hadir, peringatan batas peserta memakai jumlah peserta hadir.

### Seri dan lintasan

Buka **Seri dan lintasan**, buat seri, lalu tekan **Kunci daftar seri** sebelum babak dimulai. Daftar start tampil di halaman publik dan videotron hanya setelah dikunci. Bila jumlah peserta kurang dari batas minimal, halaman menampilkan peringatan. Medali tidak dikurangi otomatis; keputusan tetap di tangan panitia.

### Lembar juri

Juri membuka **Lembar juri** di ponsel dengan akun admin. Setiap baris tersimpan sendiri begitu diisi. Kode yang bisa dipilih:

| Kode | Arti | Peringkat |
|---|---|---|
| DNS | Tidak start | Tidak |
| DNF | Tidak finis | Tidak |
| DQ | Diskualifikasi. Isi **pasal** di kotak sebelahnya, misalnya `TR16.8` (paling banyak 12 huruf) | Tidak |
| NM | Tanpa lompatan atau lemparan sah. Hanya nomor lompat dan lempar. Terisi sendiri bila semua percobaan X, dan hilang sendiri bila ada percobaan sah | Tidak |
| r | Mundur. Hanya nomor lompat dan lempar. Prestasi sebelum mundur tetap dinilai, boleh lolos, dan boleh juara | Ya, bila punya prestasi |

Di nomor waktu tangan, isi waktu seperti biasa; web membulatkannya ke atas ke 0,1 detik.

### Hasil dan terbit

Buka **Hasil lomba**, periksa peringkat, lalu tekan **Terbitkan**. Setelah terbit:

- hasil tampil di halaman Hasil publik dan videotron;
- juara dan klasemen dihitung ulang;
- **rekor** diperbarui otomatis dari babak Final (lihat bagian 4).

Bila ada salah isi, ubah hasilnya; rekor dan klasemen ikut menyesuaikan. Bila perlu menarik hasil, tekan **Tarik kembali**; rekor dari hasil itu ikut dihapus dan rekor lama berlaku lagi.

### Klasemen

**Klasemen** menampilkan juara umum per klasemen sesuai susunan di Pengaturan → Hasil, hanya dari nomor yang sudah terbit. Satu sekolah (satu NPSN) dihitung satu kontingen. Unduh PDF atau Excel dengan tombol ikon di kanan atas.

## 4. Rekor kejuaraan

- Rekor dicatat mulai Seri 2, per nomor lomba, spesifikasi alat, dan cara pencatatan waktu. Waktu tangan dan elektronik tidak saling memecahkan.
- Saat hasil diterbitkan, prestasi terbaik babak **Final** yang **lebih baik** dari rekor yang berlaku menjadi rekor baru dan ditandai **CR**. Bila belum ada rekor, prestasi terbaik Final menjadi rekor pertama, jadi hampir semua nomor Seri 2 akan bertanda CR. Prestasi yang sama dengan rekor tidak memecahkannya; prestasi terbaik yang sama di satu Final dicatat sebagai rekor bersama.
- NM, DNS, DNF, dan DQ tidak membuat rekor.
- Di **Rekor** Anda bisa melihat rekor yang berlaku dan riwayatnya, serta menambah rekor dengan tangan (misalnya rekor awal dari panitia sebelum hasil terbit). Rekor dari hasil hanya berubah lewat hasilnya.
- Halaman **Rekor** publik (`/rekor`) bawaan mati. Nyalakan sakelarnya di **Rekor** setelah isinya diperiksa. Tanda CR di hasil dan videotron tetap tampil tanpa sakelar ini.

## 5. Videotron untuk operator vendor

Videotron arena dijalankan operator vendor. Web hanya menyediakan tampilan yang dipilih operator.

1. Buka **Videotron** (kelompok Hasil). Nyalakan sakelar tampil; bawaannya mati untuk semua alamat.
2. Bila perlu, unggah **gambar latar**. Kosongkan untuk memakai latar bawaan beranimasi.
3. Berikan alamat **`/layar`** situs (misalnya `https://<alamat situs>/layar`) kepada operator. Halaman itu berisi daftar tautan tanpa perlu masuk, dikelompokkan per hari dan sesi, lalu klasemen, kelompok umur, dan nomor lomba. Alamat lengkap setiap tautan tertulis dan bisa disalin.
4. Setiap tautan membuka **satu tampilan tetap** layar penuh yang tidak berganti sendiri: per seri (daftar start, lalu hasil seri setelah terbit), per sesi (Pagi sebelum 11.00, Siang 11.00–15.00, Sore mulai 15.00), per klasemen, per kelompok umur, atau per nomor lomba. Isinya diperbarui sendiri setiap 30 detik. Tabel yang panjang dibagi halaman; operator berpindah halaman dengan panah kiri dan kanan.
5. Tampilan putar lama masih ada di `/layar/putar`.

Videotron hanya menampilkan hasil yang sudah terbit dan daftar seri yang sudah dikunci.

## 6. Cadangan selama lomba

- Unduh cadangan di **Pengaturan → Cadangan** setiap akhir hari lomba. Cadangan berisi semua data, pengaturan, dan berkas unggahan, kecuali akun admin.
- **Impor cadangan mengganti semua isi web** dengan isi berkas itu. Sebelum impor, web otomatis menyimpan cadangan data lama (lima terakhir ada di halaman yang sama). Anda harus mengetik ulang kata sandi untuk memulai impor.
- Bila berkas terlalu besar untuk diunggah, minta pengelola server menjalankan `php spark cadangan:pulihkan <berkas.zip>`.

## 7. Tanya-jawab yang sering muncul

Halaman Bantuan sudah memuat tanya-jawab tentang ganti atlet, nomor acara, arti kode DNS/DNF/DQ/NM/r/CR, waktu tangan, nomor tidak dilombakan, rekor, dan cara juara umum dihitung. Sunting atau sembunyikan di **Bantuan** (kelompok Konten) bila panitia ingin jawaban lain.

## Asumsi yang perlu ditinjau

- Panduan ini ditulis di draf karena belum disetujui pemilik. Setelah disetujui, panduan dipindah ke `dokumen/final/` dan PDF-nya dibuat.
- Langkah ganti atlet di FAQ meminta sekolah menghubungi panitia lewat WA; panitia bisa menggantinya dengan prosedur di meja pertemuan teknik.
