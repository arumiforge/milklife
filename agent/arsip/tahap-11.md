# Arsip Tahap 11 — Pelajaran hasil Seri 1

Selesai: 23 November 2026

Telaah dan rekomendasi: [`dokumen/draf/telaah-hasil-seri-1.md`](../../dokumen/draf/telaah-hasil-seri-1.md) (K172). Masukan pemilik berikutnya: videotron (K173), panel admin dan cadangan (K174), halaman depan dan publik (K175). Dikerjakan paralel oleh sub-sesi dengan koordinator Tahap 11 (K145, K149). Setiap PR aman langsung dipasang di produksi (K139): kolom baru berbawaan perilaku lama, fitur publik baru bersakelar bawaan mati. Usulan keputusan sub-sesi yang diterima koordinator: K176.

| Tugas | Status | Gelombang | PR |
|---|---|---|---|
| [F11-01 Ganti atlet oleh admin](../tugas/F11-01-ganti-atlet.md) | selesai | 1 | #211 |
| [F11-02 Klasemen per kelompok umur](../tugas/F11-02-klasemen-kelompok-umur.md) | selesai | 1 | #212 |
| [F11-03 Status nomor dan batas peserta](../tugas/F11-03-status-nomor-batas-peserta.md) | selesai | 1 | #213 |
| [F11-04 Cara pencatatan waktu](../tugas/F11-04-cara-pencatatan-waktu.md) | selesai | 1 | #214 |
| [F11-05 Kode NM dan r](../tugas/F11-05-kode-nm-r.md) | selesai | 1 | #215 |
| [F11-07 Nomor acara](../tugas/F11-07-nomor-acara.md) | selesai | 1 | #216 |
| [F11-09 Daftar tampilan videotron](../tugas/F11-09-daftar-tampilan-videotron.md) | selesai | 1 | #218 |
| [F11-06 Rekor kejuaraan](../tugas/F11-06-rekor-kejuaraan.md) | selesai | 2 | #220 |
| [F11-11 Cadangan di Pengaturan](../tugas/F11-11-cadangan-pengaturan.md) | selesai | 2 | #223 |
| [F11-12 Halaman depan dan publik dirapikan](../tugas/F11-12-halaman-depan-publik.md) | selesai | 2 | #221 |
| [F11-10 Panel admin ringkas](../tugas/F11-10-panel-admin-ringkas.md) | selesai | 2 | #222, #224 |
| [F11-08 Penutupan Tahap 11](../tugas/F11-08-penutupan-tahap-11.md) | selesai | akhir | PR penutupan |
| [F11-14 Panduan admin hari lomba](../tugas/F11-14-panduan-admin-hari-lomba.md) | selesai | sisipan setelah penutupan | PR F11-14 |

PR koordinator dan pendukung: #210 (telaah hasil Seri 1, tugas F11-01 sampai F11-08, K172), #217 (K174, K175, tugas F11-10 sampai F11-12), #219 (brief gambar G11-01 dan G11-02).

## Gambar (Codex)

Brief sudah di `desain/tugas/` (#219); gambarnya **belum dibuat Codex**. Sampai berkas tiba, halaman depan memakai gradien cadangan, lalu gambar tampil otomatis begitu digabung (`GambarOpsional`).

| Tugas gambar | Berkas | Perlu sebelum |
|---|---|---|
| G11-01 Kartu kelompok umur | `public/aset/gambar/ku/ku-<n>-art.webp`, `ku/ku-<n>-isian.webp` (KU 8, 10, 12, 15, 18) | 28 November 2026 |
| G11-02 Latar hero kiri | `public/aset/gambar/beranda/hero-kiri.webp`, `beranda/hero-kiri-ponsel.webp` | 28 November 2026 |

## Hasil

- **Ganti atlet** (F11-01): admin mengganti atlet di pendaftaran Lunas dengan siswa lain dari sekolah yang sama sebelum babak dikunci; nomor lomba, regu, dan Inti/Cadangan diwariskan, atlet baru mendapat BIB baru; atlet lama di tabel `atlet_diganti`.
- **Klasemen per kelompok umur** (F11-02): menggantikan per jenjang (K124); gabungan kelompok umur diatur di Pengaturan > Hasil > Susunan klasemen (`Klasemen.gabungan`).
- **Status nomor dan batas peserta** (F11-03): nomor Tidak dilombakan dengan alasan keluar dari jadwal, seri, videotron, dan klasemen; peringatan peserta minimal di Hasil dan Seri admin.
- **Cara pencatatan waktu** (F11-04): elektronik atau tangan per nomor; waktu tangan dibulatkan ke atas ke 0,1 detik (TR 19) dan ditandai.
- **Kode NM, r, dan pasal DQ** (F11-05, CR 25): NM otomatis di Lembar juri, r tetap dinilai, pasal DQ tampil di hasil, PDF, dan Excel.
- **Rekor kejuaraan** (F11-06): tabel `rekor`, CR otomatis dari Final yang terbit, tarik terbitan mengembalikan rekor lama, rekor isian admin, halaman `/rekor` bersakelar bawaan mati.
- **Nomor acara** (F11-07): Acara 101, 102, … per babak, diberi satu tombol di Jadwal pertandingan dan tampil di jadwal, seri, lembar juri, dan hasil.
- **Videotron** (F11-09, K173): `/layar` daftar tautan tampilan tetap untuk operator vendor (per seri, sesi, klasemen, kelompok umur, nomor); latar bisa diunggah; papan putar di `/layar/putar`.
- **Panel admin ringkas** (F11-10, K174): kotak cari menu, jejak diawali Dasbor, menu bawah dengan Pengaturan, teks panjang dipotong dengan Selengkapnya, saringan dua per baris, tombol aksi ponsel tiga per baris, tombol unduh ikon.
- **Cadangan di Pengaturan** (F11-11, K174): cadangan semua data, pengaturan, dan berkas; impor web mengganti semua setelah cadangan otomatis dan sandi admin; `php spark cadangan:pulihkan`.
- **Halaman depan dan publik** (F11-12, K175): nomor lomba per KU dengan kolom Putra/Putri, kartu KU berangka isian, pita fakta tanpa ikon, menu bawah publik dengan Daftar/Hasil di tengah, unduh hasil ikon saja, jejak halaman hasil, deret tombol ponsel.
- **Penutupan** (F11-08): K176; tanya-jawab bawaan menjadi 59 (ganti atlet diperbarui, lima baru: nomor acara, arti kode hasil, waktu tangan, tidak dilombakan, rekor) dan `data:sesuaikan` menambahkannya sekali ke server yang berjalan tanpa menimpa jawaban admin (penanda `Bantuan.faqTahap11`); draf panduan admin hari lomba `dokumen/draf/panduan-admin-hari-lomba.md`; rancangan final revisi 37; panduan produksi revisi 12; kedua PDF dibuat ulang.

## Perintah sekali jalan di server

Server yang sudah menjalankan perintah Tahap 10, sekali setelah `git pull` dan `composer install --no-dev --optimize-autoloader` (panduan produksi bagian 10):

```
php spark cadangan:buat
php spark migrate --all
php spark data:sesuaikan
php spark aset:minify
php spark pembaruan:periksa
```

Server yang belum menjalankan perintah Tahap 9 atau Tahap 10 menjalankan ringkasan Tahap 10 dulu. Setelah pembaruan admin mengatur susunan klasemen, pelaksanaan nomor lomba (batas peserta, tidak dilombakan, spesifikasi alat, cara pencatatan waktu), nomor acara, lalu menyalakan sakelar Rekor dan Videotron bila siap, dan memberikan alamat `/layar` kepada operator videotron vendor.

## Uji penutupan (F11-08)

Dicatat di deskripsi PR penutupan.

## Yang dibawa ke tahap berikutnya

- Gambar G11-01 dan G11-02 dari Codex, perlu sebelum 28 November 2026.
- Pembaruan server produksi dengan perintah di atas, lalu sakelar Rekor dan Videotron dinyalakan admin setelah dicoba di staging.
- Draf panduan admin hari lomba ditinjau pemilik, lalu dipindah ke `dokumen/final/`.
- Usulan lanjutan dari K176: nomor acara di videotron, cara pencatatan waktu per babak, NM dan r untuk poin multi-lomba; dikerjakan bila panitia meminta.
- Lomba 9–12 Desember 2026; pertemuan teknik 6 Desember.
