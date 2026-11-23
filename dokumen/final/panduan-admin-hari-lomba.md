# Panduan Admin Hari Lomba

| | |
|---|---|
| Status | **Disetujui pemilik pada 23 November 2026** (K178) |
| Tanggal | 23 November 2026 |
| Dasar | Rancangan web bagian 9 dan 18; K124, K140, K141, K172–K176, K178; tugas F11-01 sampai F11-12 dan F11-14 |

## Tujuan

Panduan ini untuk admin panitia yang memakai panel admin dari pertemuan teknik 6 Desember sampai lomba selesai 12 Desember 2026. Setelah membacanya, Anda tahu:

- apa yang disiapkan sebelum hari lomba;
- apa yang dikerjakan pagi hari, selama lomba, dan di akhir hari;
- cara memperbaiki kesalahan yang sering terjadi.

Panduan ini tidak membahas pendaftaran dan pembayaran. Untuk memasang atau memperbarui server, baca Panduan Pemasangan Produksi.

## Cara memakai panduan ini

- Semua menu yang disebut ada di sidebar panel admin. Di ponsel, buka sidebar dengan tombol menu di kiri atas.
- Anda juga bisa mencari nama menu di kotak **Cari menu** di kepala halaman. Tekan `/` di papan ketik untuk langsung mengetik. Di ponsel, kotak ini muncul setelah tombol kaca pembesar ditekan.
- Penulisan **Pengaturan → Hasil** berarti: buka menu **Pengaturan**, lalu pilih **Hasil**.
- Setiap bagian diakhiri daftar periksa. Centang di kertas atau salin ke catatan panitia.

Menu yang paling sering dipakai saat lomba ada di dua kelompok sidebar:

| Kelompok | Menu |
|---|---|
| Hari lomba | Daftar ulang, Cetak BIB, Daftar peserta, Jadwal pertandingan, Seri dan lintasan, Lembar juri |
| Hasil | Hasil lomba, Klasemen, Rekor, Videotron |

## Ringkasan alur

| Kapan | Yang dikerjakan | Menu |
|---|---|---|
| Sebelum hari lomba | Cadangan awal | Pengaturan → Cadangan |
| | Pelaksanaan tiap nomor | Pengaturan → Nomor lomba |
| | Susunan klasemen dan metode | Pengaturan → Hasil |
| | Babak tiap nomor | Hasil lomba |
| | Jadwal dan nomor acara | Jadwal pertandingan |
| | Rekor awal dan sakelar Rekor | Rekor |
| | Sakelar dan latar videotron, alamat `/layar` untuk operator | Videotron |
| | Cetak BIB dan daftar peserta | Cetak BIB, Daftar peserta |
| Pagi hari lomba | Daftar ulang dan ganti atlet | Daftar ulang, Pendaftaran |
| | Bagi seri dan kunci daftar seri | Seri dan lintasan |
| Selama lomba | Juri mengisi prestasi | Lembar juri |
| | Periksa, koreksi, terbitkan hasil | Hasil lomba |
| | Operator memilih tampilan videotron | Halaman `/layar` |
| Akhir hari | Periksa klasemen, unduh hasil, cadangan | Klasemen, Hasil lomba, Pengaturan → Cadangan |

## 1. Sebelum hari lomba

Kerjakan bagian ini sebelum pertemuan teknik 6 Desember 2026. Urutannya penting: babak dibuat dulu, baru jadwal, baru seri.

### 1.1 Cadangan awal

1. Buka **Pengaturan → Cadangan**.
2. Tekan **Unduh cadangan**. Berkas ZIP terunduh.
3. Simpan berkas di luar komputer server, misalnya di laptop panitia dan penyimpanan awan panitia.

Cadangan berisi semua data, pengaturan, foto, bukti bayar, dan berkas unggahan. Akun admin dan kata sandinya tidak ikut. Berkas ini berisi data pribadi siswa, jadi jangan dibagikan.

### 1.2 Pelaksanaan tiap nomor lomba

1. Buka **Pengaturan → Nomor lomba**.
2. Tekan **Ubah** di baris nomor yang diperiksa. Di layar kecil, tombol ini berupa ikon pensil.
3. Periksa isian di bawah ini, lalu tekan **Simpan**.

Isian yang dipakai saat lomba:

- **Cara pencatatan waktu** (hanya nomor berjenis hasil waktu). Pilih **Elektronik** bila waktu diambil dari foto finis. Pilih **Tangan** bila juri memakai stopwatch. Waktu tangan dibulatkan ke atas ke 0,1 detik, jadi 12,34 menjadi 12,4. Cara ini berlaku untuk semua babak nomor itu dan masih bisa diubah setelah ada prestasi.
- **Percobaan lompat/lempar** (hanya nomor lompat dan lempar). **Banyak percobaan** bawaannya 3 bila dikosongkan.
- **Batas peserta minimal** di bagian **Pelaksanaan lomba**. Isi sesuai petunjuk teknis, 1 sampai 99. Kosongkan bila tidak ada batas. Untuk nomor individu dihitung per atlet, untuk nomor beregu per regu.
- **Tidak dilombakan** di bagian **Pelaksanaan lomba**. Nyalakan bila panitia memutuskan nomor itu batal, lalu tulis **Alasan**, misalnya "Peserta kurang dari 3". Alasan wajib diisi dan tampil di halaman Hasil publik.
- **Spesifikasi alat** (nomor lempar), misalnya "Peluru 3 kg". Rekor dengan alat berbeda dicatat terpisah.

Yang terjadi bila nomor tidak dilombakan:

- nomor itu keluar dari jadwal, daftar seri, lembar juri, videotron, dan klasemen;
- halaman Hasil publik tetap mencantumkannya beserta alasannya;
- pendaftarannya tidak berubah. Sakelar **Aktif: muncul di pendaftaran** terpisah dan tidak perlu diubah.

Bila keliru, matikan lagi **Tidak dilombakan** dan tekan **Simpan**. Alasannya terhapus dan nomor itu kembali ke jadwal, seri, dan klasemen.

Peringatan batas peserta tampil di **Hasil lomba** dan **Seri dan lintasan** dengan tanda **Peserta kurang dari batas**. Sebelum daftar ulang, yang dihitung adalah peserta Lunas. Setelah ada atlet yang ditandai hadir, yang dihitung adalah peserta hadir. Web tidak mengurangi medali otomatis; keputusan tetap di tangan panitia.

### 1.3 Susunan klasemen dan metode

1. Buka **Pengaturan → Hasil**.
2. Di **Metode klasemen juara umum**, pilih **Medali** (bawaan) atau **Poin**. Bila memilih Poin, periksa **Poin per peringkat**.
3. Di **Susunan klasemen**, beri setiap kelompok umur nomor klasemen. Kelompok umur bernomor sama digabung dalam satu klasemen. Contoh: KU 8 dan KU 10 sama-sama diberi nomor 1, jadi klasemennya bernama "KU 8 dan KU 10".
4. Tekan **Simpan pengaturan hasil**. Baris **Berlaku sekarang** di bawah isian menunjukkan susunan yang tersimpan.
5. Tekan **Lihat klasemen** untuk memeriksa hasilnya.

Satu sekolah (satu NPSN) dihitung satu kontingen. Susunan ini boleh diubah kapan saja; klasemen langsung dihitung ulang.

### 1.4 Babak tiap nomor

Jadwal, seri, dan lembar juri disusun dari babak. Karena itu, setiap nomor perlu punya babak dulu.

1. Buka **Hasil lomba**, lalu pilih nomornya.
2. Di bagian **Tambah babak**, isi **Nama babak**, misalnya "Penyisihan" atau "Final".
3. Nyalakan **Babak Final: juara I–III diambil dari sini** hanya untuk babak terakhir. Satu nomor hanya punya satu Final.
4. Pilih cara mengisi **Peserta** (lihat tabel di bawah).
5. Tekan **Tambah babak**.

| Pilihan Peserta | Dipakai untuk |
|---|---|
| Semua peserta nomor ini | Babak pertama, atau nomor yang langsung final |
| Peringkat teratas dari babak lain | Final yang diisi dari Penyisihan |
| Kosong, pilih peserta nanti | Babak yang pesertanya belum pasti |

Nomor yang langsung final cukup punya satu babak Final. Bila nama babak salah, buka babak itu dengan **Isi hasil**, lalu ubah di bagian **Ubah babak**. Tombol hapus babak ada di halaman yang sama. Menghapus babak ikut menghapus semua prestasinya dan tidak bisa dibatalkan.

### 1.5 Jadwal dan nomor acara

1. Buka **Jadwal pertandingan**. Satu baris berisi satu babak.
2. Isi **Tanggal**, **Jam mulai**, dan **Lokasi** setiap babak. Untuk mengisi banyak baris sekaligus, centang babaknya, lalu pakai **Isi berurutan**: isi **Jam mulai pertama** dan **Jeda (menit)**. Jam terisi dari atas ke bawah.
3. Tekan **Simpan jadwal**. Waktu memakai WIB.
4. Setelah semua tanggal dan jam benar, isi **Nomor awal** (bawaan 101) di bagian **Nomor acara**, lalu tekan **Beri nomor acara**.
5. Periksa sakelar **Tampil untuk umum**. Nyalakan setelah jadwal diperiksa, supaya halaman Jadwal publik terbuka.
6. Unduh PDF jadwal tiap hari di **Unduh jadwal per hari** untuk grup ofisial.

Yang terjadi saat **Beri nomor acara** ditekan:

- semua babak bertanggal diberi nomor berurutan menurut tanggal dan jam: Acara 101, 102, dan seterusnya;
- nomor acara lama diganti semua;
- babak tanpa tanggal tidak diberi nomor.

Nomor acara tampil di jadwal, daftar seri, lembar juri, dan hasil. Untuk mengubah satu nomor, ketik di kotak **Nomor acara** barisnya, lalu tekan **Simpan jadwal**. Nomor tidak boleh sama dengan babak lain. Bila jadwal berubah besar setelah nomor diberi, tekan **Beri nomor acara** lagi.

### 1.6 Rekor awal dan sakelar Rekor

Rekor kejuaraan dicatat mulai Seri 2. Hasil Seri 1 tidak diimpor.

1. Buka **Rekor**.
2. Bila panitia punya rekor awal, tekan **Tambah rekor**. Isi nomor lomba, prestasi, nama atlet, sekolah, seri, dan tanggal, lalu tekan **Simpan rekor**. Untuk nomor beregu, tulis nama anggota regu dipisah koma.
3. Setelah daftar rekor diperiksa, nyalakan **Tampilkan halaman Rekor**, lalu tekan **Simpan sakelar Rekor**. Halaman `/rekor` publik bawaannya mati.

Rekor isian admin bisa diubah atau dihapus dengan **Ubah** dan **Hapus**. Rekor bertanda **Dari hasil** hanya berubah lewat hasil lombanya (bagian 3.6).

### 1.7 Videotron

Videotron arena dijalankan operator vendor. Web hanya menyediakan tampilan yang dipilih operator.

1. Buka **Videotron** (kelompok Hasil).
2. Nyalakan **Tampilkan videotron di /layar**. Bawaannya mati, dan saat mati semua alamat videotron tidak bisa dibuka.
3. Bila perlu, unggah **Gambar latar**: JPG, PNG, atau WEBP, sebaiknya gelap dan tenang, misalnya 1920 × 1080 piksel. Kosongkan untuk memakai latar bawaan yang bergerak pelan.
4. Tekan **Simpan pengaturan videotron**.
5. Kirim alamat `https://<alamat situs>/layar` kepada operator videotron. Alamat lengkapnya tertulis di halaman Videotron.
6. Coba buka `/layar` di peramban komputer videotron sebelum hari lomba, bersama operator.

Cara operator memakainya ada di bagian 3.7.

### 1.8 Cetak BIB dan daftar peserta

1. Buka **Cetak BIB**. Atur **Tampilan BIB** (latar, kepala, warna tulisan) bila perlu, lalu tekan **Simpan tampilan**.
2. Unduh BIB **Per kelompok umur (PDF)** untuk dicetak sendiri, atau **Per sekolah** (PDF atau ZIP gambar) untuk percetakan. **Unduh semua sekolah (ZIP)** bisa butuh beberapa menit.
3. Buka **Daftar peserta** untuk mengunduh PDF atau Excel peserta tiap nomor, misalnya untuk pertemuan teknik.

Hanya atlet dari pendaftaran Lunas yang punya nomor atlet (BIB). Bila urutan nomor perlu dirapikan, pakai **Atlet → Urutkan nomor atlet (BIB)** sebelum mencetak.

### Daftar periksa sebelum hari lomba

- [ ] Cadangan awal sudah diunduh dan disimpan di luar server.
- [ ] Setiap nomor sudah diperiksa: cara pencatatan waktu, batas peserta, alat, dan status tidak dilombakan.
- [ ] Susunan klasemen dan metode sudah disimpan dan dicek di Klasemen.
- [ ] Setiap nomor punya babak, dan tepat satu babak Final.
- [ ] Semua babak punya tanggal, jam, dan lokasi; nomor acara sudah diberi.
- [ ] Halaman Jadwal publik sudah dinyalakan.
- [ ] Rekor awal (bila ada) sudah diisi; sakelar Rekor dinyalakan bila siap.
- [ ] Videotron sudah dinyalakan, dicoba, dan alamat `/layar` sudah di tangan operator.
- [ ] BIB dan daftar peserta sudah dicetak.

## 2. Pagi hari lomba

### 2.1 Daftar ulang

1. Buka **Daftar ulang**.
2. Tekan **Pindai dengan kamera**, lalu arahkan kamera ke kode QR bukti pendaftaran. Bila pemindai tidak muncul, ketik **ID pendaftaran** lalu tekan **Cari**.
3. Tekan **Tandai hadir** di baris setiap atlet yang datang, atau **Tandai semua atlet hadir** bila semuanya datang.

Pemindai paling mudah dipakai di Chrome pada Android. Di ponsel lain, pindai kode QR dengan aplikasi kamera; halaman bukti lalu menampilkan tombol **Buka daftar ulang**.

Bila salah tandai, tekan **Batal hadir** di baris atlet itu. Pendaftaran yang belum Lunas tidak bisa ditandai hadir; periksa pembayarannya di rincian pendaftaran.

### 2.2 Ganti atlet

Sekolah boleh mengganti atlet yang berhalangan dengan siswa lain dari sekolah yang sama. Siapkan nama atlet lama, alasan penggantian, serta nama lengkap, NISN, dan tanggal lahir atlet pengganti.

1. Buka **Pendaftaran**, cari sekolahnya, lalu buka rinciannya. Pendaftaran harus **Lunas**.
2. Tekan **Ganti** di baris atlet yang diganti. Halaman **Ganti atlet** terbuka.
3. Isi **Nama lengkap**, **NISN** (tepat 10 angka), **Tanggal lahir**, dan **Alasan penggantian**.
4. Tekan **Ganti atlet**.

Hasilnya:

- atlet pengganti memakai nomor lomba, regu, dan status Inti/Cadangan atlet lama;
- biaya dan status Lunas tidak berubah;
- atlet pengganti mendapat nomor atlet (BIB) baru, dan BIB atlet lama tidak dipakai lagi;
- bila atlet lama sudah masuk seri yang belum dikunci, tempatnya pindah ke atlet pengganti;
- atlet lama tercatat di bagian **Atlet diganti** di rincian pendaftaran, bukan sebagai atlet ditolak.

Penggantian ditolak bila:

- daftar seri babak atlet lama sudah dikunci;
- atlet lama sudah punya hasil (untuk nomor beregu: regunya sudah punya hasil);
- kelompok umur atau jenis kelamin atlet pengganti tidak cocok dengan nomor lombanya;
- NISN atlet pengganti sudah terdaftar.

Pesan di layar menyebut alasannya. Bila daftar seri sudah dikunci, buka kuncinya dulu (bagian 2.4), ganti atletnya, lalu kunci lagi.

### 2.3 Cetak BIB atlet pengganti

Buka **Cetak BIB**, lalu unduh PDF sekolah atlet pengganti di **Per sekolah**. Serahkan BIB baru sebelum atlet masuk lapangan.

### 2.4 Daftar seri dan kunci seri

Bagi seri boleh dikerjakan sebelum pertemuan teknik, tetapi kunci daftar seri sebaiknya setelah daftar ulang supaya atlet yang tidak datang tidak mendapat lintasan.

1. Buka **Seri dan lintasan**, lalu tekan nama babaknya.
2. Di **Bagi otomatis**, centang **Hanya yang sudah hadir di daftar ulang** bila daftar ulang sudah selesai. Tekan **Bagi otomatis** (atau **Bagi ulang** bila sudah pernah dibagi).
3. Periksa hasilnya. Untuk memindah peserta, ubah seri dan lintasannya di tabel, lalu tekan **Simpan seri dan lintasan** (untuk lompat dan lempar: **Simpan seri dan urutan**).
4. Tekan **Kunci daftar seri**.
5. Unduh PDF untuk juri dan pengeras suara di **Unduh daftar seri per hari** atau **Unduh daftar seri** per nomor.

Yang perlu diketahui:

- pembagian memisahkan atlet satu sekolah ke seri berbeda bila bisa, lalu diacak;
- daftar start tampil di halaman publik dan videotron hanya setelah dikunci, dan hanya bila sakelar **Tampil untuk umum** di halaman **Seri dan lintasan** menyala;
- untuk babak berikutnya (misalnya Final), peserta diambil dari yang lolos (Q dan q) setelah hasil babak sebelumnya lengkap. Atur jumlah Q dan q di **Pilihan lanjutan**, atau kosongkan supaya dihitung otomatis;
- bila daftar perlu diubah setelah dikunci, tekan **Buka kunci**, ubah, lalu kunci lagi. Babak yang sudah berisi prestasi tidak bisa dibagi ulang; geser pesertanya satu per satu.

### Daftar periksa pagi hari lomba

- [ ] Daftar ulang berjalan; atlet yang datang sudah ditandai hadir.
- [ ] Atlet pengganti sudah dicatat dan BIB barunya sudah diserahkan.
- [ ] Babak hari ini sudah dibagi ulang dari peserta hadir dan dikunci.
- [ ] Daftar seri hari ini sudah dicetak untuk juri.
- [ ] Sakelar Tampil untuk umum di Seri dan lintasan menyala.

## 3. Selama lomba

### 3.1 Lembar juri di ponsel

1. Juri masuk ke panel admin di ponsel dengan akun admin.
2. Buka **Lembar juri**. Babak dikelompokkan per hari; hari ini paling atas dan terbuka.
3. Tekan babaknya, lalu pilih seri.
4. Isi prestasi setiap peserta. Untuk lompat dan lempar, isi jarak tiap percobaan dalam meter, atau tekan tombol X untuk percobaan gagal.

Setiap kartu peserta tersimpan sendiri sekitar satu detik setelah berhenti mengetik. Status di bawah kartu berubah dari **Belum tersimpan…** ke **Menyimpan…** lalu **Tersimpan**. Bila sinyal lemah, tombol **Simpan** di kartu tetap bisa ditekan.

Bila muncul pesan bahwa baris baru saja diubah admin lain, muat ulang halaman, periksa isiannya, lalu ubah lagi bila perlu. Ini mencegah dua juri saling menimpa.

### 3.2 Kode hasil

Pilih kode di kotak **Keterangan** untuk peserta yang tidak punya prestasi biasa.

| Kode | Arti | Dapat peringkat |
|---|---|---|
| DNS | Tidak start | Tidak |
| DNF | Tidak finis | Tidak |
| DQ | Diskualifikasi. Tulis pasalnya di **Pasal DQ**, misalnya `TR16.8` (paling banyak 12 huruf) | Tidak |
| NM | Tanpa lompatan atau lemparan sah. Hanya nomor lompat dan lempar | Tidak |
| r | Mundur. Hanya nomor lompat dan lempar | Ya, bila sudah punya prestasi |

Catatan:

- di Lembar juri, NM terisi sendiri bila semua percobaan X, dan hilang sendiri bila ada percobaan sah;
- prestasi sebelum mundur (r) tetap dinilai, boleh lolos, dan boleh juara;
- pasal DQ tampil di hasil, PDF, dan Excel.

### 3.3 Waktu tangan

Di nomor yang memakai waktu tangan, juri menulis waktu stopwatch seperti biasa, misalnya 12,34. Web membulatkannya ke atas ke 0,1 detik saat disimpan, jadi tersimpan 12,4. Hasilnya ditandai sebagai waktu tangan di halaman hasil.

Bila ternyata nomor itu memakai foto finis, ubah **Cara pencatatan waktu** di **Pengaturan → Nomor lomba**. Prestasi yang sudah tersimpan langsung dibaca ulang dengan cara yang baru.

### 3.4 Memeriksa dan mengoreksi hasil

1. Buka **Hasil lomba**, pilih nomornya, lalu tekan **Isi hasil** di babak yang diperiksa.
2. Periksa prestasi dan keterangan. Peringkat dihitung otomatis setelah disimpan; prestasi yang sama mendapat peringkat yang sama.
3. Bila juri memutuskan peringkat berbeda (khusus babak Final), isi kolom **Koreksi per.**. Peringkat yang dikoreksi diberi tanda diubah admin.
4. Tekan **Simpan hasil**.

Peserta babak bisa ditambah atau dikeluarkan di **Peserta babak**, lalu **Simpan peserta**. Peserta yang dikeluarkan kehilangan prestasinya di babak itu.

Setelah hasil babak yang dibagi seri lengkap, babak berikutnya terisi sendiri dari peserta yang lolos. Buka **Seri dan lintasan** untuk membagi dan mengunci seri babak berikutnya.

### 3.5 Terbitkan dan tarik kembali

Hasil satu nomor tampil di halaman publik hanya setelah diterbitkan.

1. Buka **Hasil lomba**, lalu pilih nomornya. Lencana **Belum terbit** tampil di daftar.
2. Periksa semua babak.
3. Tekan **Terbitkan**, lalu konfirmasi.

Yang terjadi setelah terbit:

- hasil semua babak nomor itu tampil di halaman Hasil publik dan videotron;
- juara nomor itu dihitung di klasemen publik;
- rekor diperbarui dari babak Final (bagian 3.6).

Bila ada salah isi setelah terbit, ubah hasilnya seperti biasa. Perubahan langsung tampil di halaman publik, dan rekor serta klasemen ikut menyesuaikan.

Bila hasil perlu disembunyikan, misalnya menunggu keputusan protes, tekan **Tarik kembali**. Hasil hilang dari halaman publik dan klasemen publik sampai diterbitkan lagi. Rekor dari hasil itu ikut dihapus dan rekor sebelumnya berlaku lagi.

### 3.6 Rekor kejuaraan dan tanda CR

- Saat hasil diterbitkan, prestasi terbaik babak **Final** yang lebih baik dari rekor yang berlaku menjadi rekor baru dan ditandai **CR**.
- Bila nomor itu belum punya rekor, prestasi terbaik Final menjadi rekor pertama. Karena itu, hampir semua nomor Seri 2 akan bertanda CR.
- Prestasi yang sama dengan rekor tidak memecahkannya. Prestasi terbaik yang sama di satu Final dicatat sebagai rekor bersama.
- Rekor dicatat per nomor lomba, spesifikasi alat, dan cara pencatatan waktu. Waktu tangan dan elektronik tidak saling memecahkan.
- NM, DNS, DNF, dan DQ tidak membuat rekor. Babak selain Final juga tidak.
- Tanda CR di hasil dan videotron tetap tampil walau sakelar halaman Rekor mati.

Lihat rekor yang berlaku dan riwayatnya di **Rekor**. Rekor bertanda **Dari hasil** punya tombol **Lihat hasil** untuk membuka hasil asalnya.

### 3.7 Videotron untuk operator vendor

Bagian ini bisa diteruskan kepada operator videotron.

1. Buka alamat `/layar` di peramban komputer videotron. Halaman ini tidak perlu masuk.
2. Pilih tautan tampilan. Tautan dikelompokkan per hari dan sesi, lalu klasemen, kelompok umur, dan nomor lomba. Setiap tautan punya tombol **Salin** untuk menyalin alamat lengkapnya.
3. Tampilan terbuka layar penuh dan tidak berganti sendiri. Untuk tampilan lain, kembali ke `/layar` dan pilih tautan lain.

Tampilan yang tersedia:

| Tampilan | Isi |
|---|---|
| Per seri | Daftar start urut lintasan setelah daftar seri dikunci, lalu hasil seri itu setelah terbit |
| Per sesi | Semua hasil terbit satu sesi: Pagi sebelum 11.00, Siang 11.00 sampai sebelum 15.00, Sore mulai 15.00 |
| Klasemen | Juara umum satu klasemen, sesuai susunan di Pengaturan → Hasil |
| Per kelompok umur | Semua hasil terbit satu kelompok umur |
| Per nomor lomba | Semua babak terbit satu nomor, Final di atas |

Yang perlu diketahui operator:

- isi tampilan diperbarui sendiri setiap 30 detik;
- tabel yang panjang dibagi beberapa halaman. Pindah halaman dengan tombol panah kiri dan kanan di papan ketik;
- videotron hanya menampilkan hasil yang sudah terbit dan daftar seri yang sudah dikunci;
- bila tulisan terlalu besar atau kecil, tambahkan `?skala=1.3` (huruf 30% lebih besar) atau `?baris=10` (10 baris per halaman) di belakang alamat. Contoh lain ada di halaman Videotron panel admin.

Papan lama yang berganti sendiri masih ada di `/layar/putar`, hanya untuk videotron tanpa operator.

### Daftar periksa selama lomba

- [ ] Setiap babak selesai, Lembar juri sudah berstatus lengkap.
- [ ] Hasil sudah diperiksa di Hasil lomba sebelum diterbitkan.
- [ ] DQ sudah berisi pasal.
- [ ] Babak berikutnya sudah dibagi seri dan dikunci.
- [ ] Nomor yang selesai sudah diterbitkan; operator videotron diberi tahu.

## 4. Akhir hari lomba

### 4.1 Klasemen

1. Buka **Klasemen**.
2. Periksa juara umum setiap klasemen.

Klasemen di panel admin menghitung juara dari semua babak Final, termasuk nomor yang belum terbit. Bila ada nomor yang belum terbit, halaman menampilkan peringatan bahwa klasemen publik masih berbeda. Terbitkan nomor itu bila hasilnya sudah benar.

Satu regu juara dihitung satu piala untuk sekolahnya. Sekolah dikenali dari NPSN.

### 4.2 Unduhan PDF dan Excel

Tombol unduh berupa ikon: ikon PDF merah dan ikon lembar kerja hijau untuk Excel. Arahkan kursor ke ikon, atau tekan di ponsel, untuk melihat namanya.

| Yang diunduh | Menu |
|---|---|
| Hasil satu nomor | Hasil lomba → pilih nomor, ikon di kanan atas |
| Klasemen | Klasemen, ikon di kanan atas |
| Jadwal per hari | Jadwal pertandingan → Unduh jadwal per hari |
| Daftar seri per hari atau per nomor | Seri dan lintasan |
| Peserta per nomor | Daftar peserta |

### 4.3 Cadangan akhir hari

Unduh cadangan di **Pengaturan → Cadangan** setiap akhir hari lomba, lalu simpan di luar server. Beri nama berkas dengan tanggalnya bila perlu, misalnya "cadangan-9-desember.zip".

### Daftar periksa akhir hari

- [ ] Semua nomor yang selesai hari ini sudah terbit.
- [ ] Klasemen sudah diperiksa dan tidak ada peringatan nomor belum terbit.
- [ ] PDF hasil dan klasemen sudah diunduh untuk arsip panitia.
- [ ] Cadangan hari ini sudah diunduh dan disimpan di luar server.

## 5. Bila terjadi masalah

| Masalah | Yang dilakukan |
|---|---|
| Nomor tidak ada di Jadwal, Seri, atau Lembar juri | Periksa bahwa nomor punya babak di Hasil lomba dan tidak ditandai **Tidak dilombakan**. Halaman Jadwal publik hanya memuat babak bertanggal; babak tanpa tanggal ada di Lembar juri di bawah **Belum dijadwalkan**. |
| Daftar start tidak tampil di halaman publik atau videotron | Kunci daftar seri babak itu, lalu nyalakan **Tampil untuk umum** di Seri dan lintasan. |
| Hasil tidak tampil di halaman publik atau videotron | Tekan **Terbitkan** di nomor itu. Untuk videotron, pastikan juga **Tampilkan videotron di /layar** menyala. |
| Videotron menampilkan halaman kosong atau tidak bisa dibuka | Nyalakan **Tampilkan videotron di /layar** di menu Videotron. Periksa sambungan internet komputer videotron. |
| Tombol **Ganti** tidak ada atau penggantian ditolak | Pastikan pendaftaran Lunas. Buka kunci daftar seri babak atlet itu, ganti atletnya, lalu kunci lagi. Bila atlet sudah punya hasil, atlet tidak bisa diganti. |
| Juri melihat pesan baris diubah admin lain | Muat ulang Lembar juri, periksa isian terbaru, lalu ubah lagi bila perlu. |
| Status kartu juri tetap **Belum tersimpan** | Sinyal lemah. Tekan **Simpan** di kartu itu, atau pindah ke tempat bersinyal lalu muat ulang halaman. |
| Waktu stopwatch tersimpan berbeda dari yang ditulis | Itu pembulatan waktu tangan ke 0,1 detik. Bila nomor itu memakai foto finis, ubah **Cara pencatatan waktu** ke **Elektronik**. |
| Peringkat Final perlu diubah karena keputusan juri | Isi **Koreksi per.** di babak Final, lalu **Simpan hasil**. |
| Hasil terbit ternyata salah | Ubah hasilnya; publik, klasemen, dan rekor ikut menyesuaikan. Bila perlu disembunyikan dulu, tekan **Tarik kembali**. |
| CR muncul di semua nomor | Wajar di Seri 2 karena rekor baru mulai dicatat. Bila panitia punya rekor awal, isi lewat **Tambah rekor** sebelum hasil terbit. |
| Klasemen admin berbeda dari klasemen publik | Ada nomor yang belum terbit. Terbitkan nomor itu. |
| Kelompok umur yang harus digabung tampil terpisah di klasemen | Beri nomor klasemen yang sama di **Pengaturan → Hasil → Susunan klasemen**. |
| Peringatan **Peserta kurang dari batas** | Panitia memutuskan sesuai petunjuk teknis. Bila nomor batal, nyalakan **Tidak dilombakan** dan tulis alasannya. |
| Data terhapus atau salah impor | Buka **Pengaturan → Cadangan**. Impor cadangan terakhir yang benar, atau unduh **Cadangan otomatis** yang dibuat sebelum impor terakhir, lalu impor lagi. |

Tentang impor cadangan:

- impor mengganti semua data, pengaturan, dan berkas unggahan dengan isi berkas;
- sebelum impor, web menyimpan cadangan otomatis data lama. Lima yang terakhir bisa diunduh di halaman yang sama;
- Anda harus mengetik ulang kata sandi untuk memulai impor, dan halaman jangan ditutup sampai selesai;
- bila berkas terlalu besar untuk diunggah, minta pengelola server menjalankan `php spark cadangan:pulihkan <berkas.zip>`.

Bila masalah tidak ada di tabel ini, catat jam, halaman, dan pesan di layar, lalu hubungi pengelola web.

## 6. Jawaban untuk sekolah di halaman Bantuan

Halaman Bantuan publik sudah memuat jawaban tentang ganti atlet, nomor acara, arti kode DNS/DNF/DQ/NM/r/CR, waktu tangan, nomor tidak dilombakan, rekor, dan cara juara umum dihitung. Arahkan ofisial sekolah ke halaman itu. Sunting atau sembunyikan jawabannya di **Bantuan** (kelompok Konten) bila panitia ingin jawaban lain, misalnya prosedur ganti atlet lewat meja pertemuan teknik, bukan lewat WA.

## Asumsi yang perlu ditinjau

1. Juri memakai akun admin yang sama dengan panitia karena panel belum punya akun khusus juri. Bagikan kata sandinya hanya kepada juri yang bertugas, dan ganti di **Akun** setelah lomba.
2. Panduan ini belum memuat tangkapan layar karena PDF dokumen dibuat dari teks saja. Nama menu dan tombol ditulis persis seperti di panel per 23 November 2026.
3. Pembagian sesi videotron (Pagi, Siang, Sore) mengikuti jam mulai babak (K173) dan bisa diganti bila panitia memakai pembagian sesi resmi.
