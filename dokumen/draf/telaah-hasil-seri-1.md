# Telaah hasil lomba Seri 1 dan rekomendasi untuk web

| | |
|---|---|
| Status | Draf |
| Tanggal | 22 November 2026 |
| Dasar | Empat berkas hasil Seri 1 dari pemilik (KU 8, KU 8–10, KU 12, KU 18; lomba 15–18 Juli 2026), kode `main`, riset aturan World Athletics (Technical Rules versi 1 Juli 2026, Kids' Athletics) dan pedoman O2SN 2023–2024, wawancara pemilik 22 November 2026 (K172) |

## Tujuan

Dokumen ini untuk pemilik, panitia, dan sesi agen Tahap 11. Isinya: apa yang terjadi di Seri 1 menurut lembar hasilnya, masalah yang muncul karena hasil dikerjakan di Excel, mana yang sudah dicegah web Seri 2, dan fitur yang dikerjakan sebelum lomba 9 Desember.

Dokumen ini tidak memuat nama atlet atau NISN. Berkas aslinya tidak masuk repo. Materi atletik dasar sampai lanjut ditulis terpisah sebagai handbook pengembang di Claude Docs milik pemilik.

## Ringkasan

- Seri 1 diikuti 2.272 atlet dari 197 nama sekolah. Sebanyak 1.887 atlet (83%) adalah KU 8, KU 10, dan KU 12. Hari lomba KU 8 dan KU 10 saja berisi lebih dari 190 seri.
- Hasil dikerjakan di Excel, satu lembar per nomor dan babak, lalu direkap dengan tangan. Cara ini menghasilkan kesalahan yang mengubah medali dan juara umum.
- Sebagian besar masalah itu sudah dicegah web Seri 2: nomor lomba dari daftar, NISN 10 angka yang unik, BIB otomatis, peringkat dan medali dihitung web, dan kontingen dikenali dengan NPSN.
- Yang belum ada di web, padahal terjadi di Seri 1: atlet pengganti di hari lomba, nomor yang batal atau pesertanya sangat sedikit, catatan rekor, cara pencatatan waktu, kode NM, dan nomor acara.
- Pemilik memutuskan semua rekomendasi dikerjakan sebelum lomba (K172). Rinciannya di bagian 5.

## 1. Gambaran Seri 1

### Peserta

Data dari lembar `Data_Mentah`, dikirim 8 Juni sampai 10 Juli 2026.

| Kelompok umur | Putra | Putri | Jumlah | Nama sekolah |
|---|---:|---:|---:|---:|
| KU 8 | 203 | 161 | 364 | 108 |
| KU 10 | 493 | 338 | 831 | 134 |
| KU 12 | 391 | 301 | 692 | 113 |
| KU 15 | 118 | 126 | 244 | 30 |
| KU 18 | 88 | 53 | 141 | 20 |
| Jumlah | 1.293 | 979 | 2.272 | 197 |

Hal yang terlihat dari data peserta:

- 1.345 atlet ikut satu nomor individu, 644 ikut dua, dan 283 hanya ikut nomor beregu; batas dua nomor individu (K53) sesuai kebiasaan peserta.
- Nomor individu terpadat adalah Lari 60 m KU 10 putra (260 atlet), Lari 40 m KU 8 putra (203), Frog Jump KU 10 putra (201), dan Lari 60 m KU 12 putra (191).
- Nomor beregu terpadat adalah Kanga's Escape KU 10 (488 atlet) dan Formula 1 KU 10 (420).
- Nomor KU 15 dan KU 18 kecil, 3 sampai 20 atlet per nomor; Lompat Tinggi KU 18 hanya 3 atlet per jenis kelamin.
- Berkas hasil KU 15 tidak termasuk kiriman, jadi nomor KU 15 hanya ditelaah dari data pendaftaran.

### Jadwal dan beban seri

| Hari | Kelompok umur | Isi |
|---|---|---|
| Rabu, 15 Juli | KU 8 dan KU 10 | lebih dari 190 seri: 40 m (21 dan 26 seri), 60 m KU 10 (22 dan 33), Kanga's Escape (14 dan 20), Formula 1 (18 dan 13), Estafet 8 × 50 m (6), lalu 8 final |
| Kamis, 16 Juli | KU 12 | kira-kira 100 seri: 60 m (18 dan 24), Kanga's Escape (11 dan 13), Formula 1 (11 dan 9), Estafet (5), 1000 m, Lompat Jauh, Tolak Peluru |
| Jumat, 17 Juli | KU 15 (diperkirakan) | berkas tidak ada |
| Sabtu, 18 Juli | KU 18 | 19 nomor, kebanyakan final langsung |

Seri 2 berlangsung empat hari (9 sampai 12 Desember) dengan peserta dari lima kabupaten, jadi beban seri per hari bisa lebih besar. Pembagian seri otomatis, daftar seri PDF, dan lembar juri ponsel dari Tahap 9 cocok untuk beban ini.

### Format lomba yang dipakai

| Pola | Contoh di Seri 1 | Di web sekarang |
|---|---|---|
| penyisihan, lalu final 8 waktu terbaik dari semua seri | Lari 40 m KU 8, Lari 60 m KU 10 dan KU 12 | ada: Q 0, q 8 |
| juara 1 dan 2 tiap seri, ditambah 2 waktu terbaik | Lari 100 m KU 18 putra | ada: Q 2, q 2 |
| 7 waktu terbaik ke final | Lari 400 m KU 18 putra | ada: q 7 |
| final langsung dalam beberapa seri, peringkat dari waktu | Kanga's Escape, Formula 1, Estafet 8 × 50 m, 1000 m | babak final dengan beberapa seri; perlu dicoba sebelum lomba |
| final langsung satu seri | 1500 m, Jalan Cepat, Estafet KU 18 | ada |
| lompat dan lempar: 2 percobaan (KU 10, KU 12) dan 4 percobaan (KU 18) | Frog Jump, Turbo Throw, Lompat Jauh, Tolak Peluru, Lembing, Cakram | ada: 1 sampai 6 percobaan per nomor |
| lompat tinggi: O, X, atau – per ketinggian | KU 18 (135 sampai 165 cm putra, 95 sampai 120 cm putri) | hanya ketinggian terbaik (K146) |
| regu 4 per seri untuk nomor anak | Kanga's Escape, Formula 1 | ada: tempat per seri per nomor |

Medali hanya untuk juara 1 sampai 3 di babak final. Juara umum dihitung per berkas, yaitu KU 8–10 digabung, KU 12, dan KU 18, dengan urutan emas, perak, lalu perunggu.

## 2. Masalah di lembar hasil

Tingkat: Tinggi berarti mengubah medali atau juara umum. Sedang berarti mengubah peringkat atau data atlet. Rendah berarti kerapian.

| No | Tingkat | Temuan | Sudah dicegah web Seri 2? |
|---|---|---|---|
| M1 | Tinggi | Lari 1000 m KU 12 putri punya dua emas tanpa perak. Lembar hasil memberi nomor urut 1 kepada atlet yang finis ke-1 dan ke-2, lalu rekap memberi keduanya emas. | Ya. Peringkat dan medali dihitung dari prestasi (`Peringkat`, `Klasemen`). |
| M2 | Tinggi | Tiga medali Formula 1 KU 10 putri ikut masuk rekap KU 12. | Ya. Klasemen dihitung dari nomor dan kelompoknya. |
| M3 | Tinggi | Satu sekolah tercatat sebagai dua kontingen karena nama regu ikut ke nama sekolah, misalnya "SD 7 Bulungcangkring" dan "SD 7 Bulungcangkring A". | Ya. Kontingen dikenali dengan NPSN (K124). |
| M4 | Tinggi | Rekap medali KU 8–10 punya dua kolom sekolah yang diurutkan terpisah dan tidak sejajar, jadi angka klasemen tidak bisa ditelusuri. | Ya. Klasemen dihitung langsung dari hasil. |
| M5 | Sedang | 19 BIB di hasil tidak ada di data pendaftaran, 18 BIB tercatat atas nama atlet lain, dan ada atlet tanpa BIB dengan nama singkat. Salah satunya masuk 6 besar Tolak Peluru KU 12. | Belum. Web hanya menerima atlet terdaftar Lunas, dan belum ada cara resmi untuk atlet pengganti (F11-01). |
| M6 | Sedang | Waktu ditulis sebagai teks dengan bentuk berbeda-beda (`1'21"68`, `01' 48" 12`, `01 '10 "93`, `1'08''23`), atau angka `10419` untuk 1:04,19. Rekap Formula 1 KU 10 putra jadi menaruh 1:41,10 di bawah 1:41,66. Medali tidak berubah, tetapi peringkat 8 sampai 14 salah. | Ya. Waktu diurai ke satu bentuk dan disimpan dalam perseratus detik. |
| M7 | Sedang | Kondisi tanpa prestasi ditulis berbeda-beda: `0`, `-0,01`, `-`, `DNS`, `NH`, "TDK JADI MAIN", dan sel kosong. | Sebagian. Ada DNS, DNF, dan DQ; belum ada NM dan r (F11-05). |
| M8 | Sedang | Lembar Tolak Peluru KU 12 putra memuat 173 nama, padahal pendaftarnya 84. Dari 94 baris tanpa prestasi, sebagian ber-BIB KU 10. | Ya. Daftar peserta diambil dari pendaftaran nomor itu. |
| M9 | Sedang | Di data pendaftaran, 793 NISN kehilangan angka 0 di depan, 38 NISN berisi `#VALUE!`, 12 kosong, dan satu NISN dipakai dua atlet. Nama nomor diketik bebas ("Frog jump", "Kanga escape tim A"). Ada atlet KU 12 di Frog Jump KU 10, satu atlet SMP di KU 18, dua atlet SD di KU 15, dan satu atlet terdaftar dua kali dengan dua BIB. | Ya. NISN tepat 10 angka dan unik, nomor lomba dari daftar, jenjang membatasi kelompok umur (K23). |
| M10 | Sedang | Estafet 4 × 100 m putri KU 18 batal karena hanya 2 regu. Lompat Tinggi KU 18 diikuti 3 atlet: putra mendapat 3 medali, putri 2. | Belum. Web belum punya status nomor tidak dilombakan atau batas peserta (F11-03). |
| M11 | Rendah | Kepala lembar salah salin: "SERI II", "HASIL HASIL", tanggal "16/7/27", dan lembar rekap 1000 m yang berjudul Lari 60 m. | Ya. Kepala PDF dibuat web. |
| M12 | Rendah | Lembar "CATATAN REKOR MLAC SERI 1 2026" sudah disiapkan, tetapi kosong. Panitia ingin mencatat rekor. | Belum (F11-06). |

Di nomor individu, 227 dari 2.451 baris (9%) tanpa prestasi. Bila lembar Tolak Peluru KU 12 putra yang janggal tidak dihitung, angkanya 6%. Pilihan "hanya yang sudah hadir" saat membagi seri (F9-08) menghemat seri kosong.

## 3. Temuan riset yang berkaitan dengan web

- Cara pencatatan waktu (TR 19): waktu tangan dibulatkan ke atas ke 0,1 detik, waktu elektronik ke 0,01 detik. Lolos karena waktu (q) hanya sah bila semua seri memakai satu cara pencatatan (TR 20). Waktu di Seri 1 ditulis sampai perseratus detik, padahal kemungkinan dicatat dengan stopwatch.
- Kode hasil (CR 25): DNS, DNF, DQ dengan pasal, NM (tanpa percobaan sah), r (mundur), qR, dan qJ. PB dan SB adalah konvensi statistik, bukan kode buku aturan.
- Rekor dan spesifikasi alat: CR 25.2 meminta perbedaan spesifikasi alat ditandai di hasil. Rekor waktu tangan dipisah dari rekor waktu elektronik.
- Urutan lintasan babak lanjutan (TR 20, versi Juli 2026): lari lurus 3, 4, 5, 6, lalu 2 dan 7, lalu 1 dan 8; untuk 200 m, 400 m, dan estafet 4, 5, 6, 7, lalu 3 dan 8, lalu 1 dan 2. Bawaan web (K140) adalah `3,4,5,6;7,8;1,2` untuk semua nomor. Pemilik memutuskan bawaan tidak diubah; admin mengisi urutan per nomor di Pengaturan bila perlu (K172).
- Lintasan (TR 17.3): menyentuh garis kiri di tikungan satu kali belum diskualifikasi; pelanggaran kedua berarti diskualifikasi. World Athletics menyarankan pelanggaran ini dicatat di sistem data.
- Estafet 4 × 400 m campuran (TR 24, versi 2026): urutan pelari putra, putri, putra, putri. Web memeriksa jumlah putra dan putri saja (K09). Tanggal aturan ini belum pasti, jadi urutan tetap mengikuti juknis.
- Kids' Athletics resmi dinilai beregu dengan poin peringkat; Frog Jump dan Turbo Throw resmi berupa jumlah prestasi anggota regu. MilkLife menilainya per individu (K21). Ini pilihan panitia, bukan kesalahan.
- Belum ada basis data hasil atau rekor pelajar nasional yang terbuka. Web MilkLife bisa menjadi catatan hasil pelajar Karesidenan Pati yang berkelanjutan bila identitas atlet (NISN) dan rekor tersimpan rapi.

## 4. Keputusan pemilik (K172)

Wawancara 22 November 2026:

1. Atlet pengganti: admin bisa mengganti atlet dengan atlet lain dari sekolah yang sama sebelum babak dikunci. Tidak ada atlet tanpa pendaftaran di hasil.
2. Juara umum dihitung per kelompok umur, menggantikan per jenjang (K124). Admin bisa mengatur kelompok umur mana yang digabung dalam satu klasemen, misalnya KU 8 dan KU 10 seperti Seri 1.
3. Rekor kejuaraan dicatat mulai Seri 2. Hasil Seri 1 tidak diimpor.
4. Nomor dengan peserta sedikit mengikuti juknis. Web menandai nomor yang tidak dilombakan dan memberi peringatan bila peserta di bawah batas yang diisi admin. Medali tetap sejumlah atlet atau regu yang berprestasi.
5. Cara pencatatan waktu dipilih admin per nomor: tangan atau elektronik. Waktu tangan dibulatkan ke atas ke 0,1 detik dan ditandai di hasil dan rekor.
6. Bawaan urutan lintasan tidak diubah. Admin mengubahnya per nomor bila perlu.
7. Semua rekomendasi dikerjakan sebelum lomba 9 Desember.

## 5. Rekomendasi: Tahap 11

Urutan di bawah adalah urutan prioritas. Tugas bertanda P1 dipakai saat pertemuan teknik 6 Desember atau sebelum babak pertama, jadi dikerjakan lebih dulu. Semua tugas mengikuti K139 dan K140: setiap PR aman langsung dipasang di produksi.

| Tugas | Prioritas | Isi | Masalah yang diatasi |
|---|---|---|---|
| F11-01 Ganti atlet oleh admin | P1 | menu Ganti atlet di rincian pendaftaran Lunas: atlet lama diganti atlet baru dari sekolah yang sama, kelompok umur dan jenis kelamin cocok, NISN baru belum terdaftar; nomor lomba dan regu diwariskan; atlet baru mendapat BIB baru (K11); babak yang dikunci menolak penggantian; riwayat dicatat | M5 |
| F11-02 Klasemen per kelompok umur | P1 | klasemen dihitung per kelompok umur; admin membuat gabungan klasemen di Pengaturan > Hasil (bawaan: satu klasemen per kelompok umur); berlaku di halaman admin, publik, PDF, Excel, dan videotron | M2, M3, K172 butir 2 |
| F11-03 Status nomor dan batas peserta | P1 | status nomor "Tidak dilombakan" (hilang dari jadwal, hasil, klasemen, dan videotron, dengan keterangan); isian batas peserta minimal per nomor yang memunculkan peringatan di Hasil dan Seri | M10 |
| F11-04 Cara pencatatan waktu | P1 | pilihan tangan atau elektronik per nomor; waktu tangan dibulatkan ke atas ke 0,1 detik saat disimpan dan ditulis dengan satu angka desimal; tanda di lembar hasil | M6, temuan TR 19 |
| F11-05 Kode NM dan r | P1 | keterangan NM (tanpa percobaan sah) dan r (mundur, prestasi sebelumnya tetap dinilai) di lembar juri, hasil, PDF, dan Excel; isian pasal untuk DQ | M7 |
| F11-06 Rekor kejuaraan | P2 | tabel rekor per nomor, kelompok umur, jenis kelamin, berat alat, dan cara pencatatan waktu; rekor baru terdeteksi saat hasil final diterbitkan; tanda CR di hasil dan videotron; halaman Rekor publik bersakelar; admin bisa mengisi rekor awal dengan tangan | M12 |
| F11-07 Nomor acara | P2 | nomor acara per babak (Event 101, 102, dan seterusnya) diisi otomatis dari urutan jadwal dan bisa diubah admin; tampil di jadwal, daftar seri, lembar juri, dan hasil | M11, kebiasaan Seri 1 |
| F11-08 Penutupan Tahap 11 | P2 | uji menyeluruh, uji peramban, panduan admin hari lomba, pembaruan dokumen | - |

Yang tidak masuk Tahap 11, beserta alasannya:

- impor hasil Seri 1 dan PB lintas seri: data Seri 1 belum rapi (K172 butir 3)
- rincian lompat tinggi per ketinggian: tetap K146
- klasemen regu Kids' Athletics berbasis poin: belum diminta panitia
- masa protes sebagai status hasil: alur Terbitkan sudah memisahkan hasil sementara dan hasil resmi.

## Asumsi yang perlu ditinjau

- F11-01 memberi atlet pengganti BIB baru dan tidak memakai ulang BIB atlet lama (K11). Bila panitia ingin BIB lama dipakai pengganti, K11 perlu pengecualian.
- F11-01 tidak mengubah total biaya: pengganti mengambil tempat atlet lama di nomor yang sama, jadi pendaftaran tetap Lunas.
- F11-02 menyimpan gabungan klasemen sebagai pengaturan, bukan di kode. Bila pengaturan kosong, setiap kelompok umur punya klasemen sendiri.
- F11-04 tidak mengubah prestasi yang sudah tersimpan. Pembulatan berlaku saat prestasi disimpan setelah cara pencatatan dipilih.
- F11-06 menulis berat alat sebagai teks bebas per nomor (misalnya "peluru 3 kg"), karena juknis belum menyebut berat alat semua kelompok umur.
