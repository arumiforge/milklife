# Catatan Keputusan

Keputusan pemilik, terbaru di bawah. Keputusan lama tidak dihapus.

## 10 November 2026 — wawancara sesi 1

| No | Keputusan | Catatan |
|---|---|---|
| K01 | Wilayah: 5 kabupaten (Blora, Rembang, Pati, Kudus, Jepara) | Grobogan dan Demak tidak termasuk |
| K02 | Biaya individu dihitung per kelompok umur | Flyer menulis per jenjang; yang berlaku keputusan ini |
| K03 | Halaman cek menampilkan data penuh, termasuk NISN dan tanggal lahir | Atas dasar MoU dengan sekolah. Risiko tercatat di rancangan bagian 13 |
| K04 | Struktur repo dikelompokkan (`agent/`, `dokumen/`, `sumber/`, `data/`), PDF dokumen disimpan di git | |
| K05 | Nomor dada: awalan per kelompok umur, satu kelompok umur boleh punya banyak awalan | Satu awalan hanya menampung 999 atlet |
| K06 | NISN yang sudah terdaftar ditolak | Perubahan atlet terdaftar lewat admin |
| K07 | Satu tim per nomor beregu per sekolah | Susulan hanya menambah anggota, tanpa biaya beregu lagi |
| K08 | Laragon dipakai juga untuk produksi | Karena keterbatasan teknis tim |
| K09 | Nomor mix: sistem hanya memeriksa jumlah putra dan putri | Urutan lari diatur di lapangan |
| K10 | Tim beregu wajib lengkap saat didaftarkan | Minimal sejumlah anggota inti |
| K11 | Nomor dada dibuat setelah admin menandai Lunas | Pendaftar yang tidak membayar tidak memakan nomor |
| K12 | Batas usia kelompok umur kosong pada awalnya | Admin mengisi sesuai juknis; pendaftaran belum bisa dibuka sebelum terisi |
| K13 | Tanpa atribusi alat AI pada commit, PR, kode, dan dokumen; satu-satunya kontributor adalah Arumi Studios | |
| K14 | Cabang utama adalah `main` | |
| K15 | Tanpa CI GitHub; uji dijalankan sendiri | Membatalkan rencana GitHub Actions di rancangan awal |

## 10 November 2026 — wawancara sesi 2 (awal Tahap 1)

| No | Keputusan | Catatan |
|---|---|---|
| K16 | "Pakai CI sendiri, jangan GitHub" berarti dua hal: CodeIgniter 4 dipasang baru dari sumber resmi lewat Composer, dan uji dijalankan sendiri tanpa GitHub Actions | Mempertegas K15. Tidak ada `.github/workflows` |
| K17 | Rancangan web disetujui dan dipindahkan ke `dokumen/final/` | Delapan asumsi di bagian 17 belum dikonfirmasi; wajib dikonfirmasi sebelum Tahap 2 dan Tahap 4 |
| K18 | Laragon pemilik memakai PHP 8.3 dan MySQL 8.x | Versi minimal di `composer.json`: PHP 8.2, sesuai syarat CodeIgniter 4.7 |
| K19 | Tahap 2–7 dikerjakan sesuai urutan di rancangan | Pendaftaran di flyer ditutup 30 November 2026; urutan tidak diubah walau waktu sempit |

## 11 November 2026

| No | Keputusan | Catatan |
|---|---|---|
| K20 | Lisensi repo diputuskan nanti | Sementara `composer.json` menulis `"license": "proprietary"` dan repo belum punya berkas lisensi |

## Dari permintaan awal pemilik

- Satu peran admin; nama dan kata sandi bisa diganti.
- Semua teks antarmuka dan URL berbahasa Indonesia, ramah non-teknis.
- Satu tema terang. Panel admin lebar penuh tanpa teks footer.
- Pembayaran transfer dengan konfirmasi manual lewat WA; tanpa gerbang pembayaran.
- Maksimal 2 nomor individu per atlet; minimal 1 nomor (individu atau beregu).
- Usia dihitung per 30 November.
- Kode jenjang pada ID pendaftaran: A (SD/MI), B (SMP/MTs), C (SMA/SMK/MA).
- Gambar yang belum ada ditampilkan sebagai ikon "tanpa gambar", bukan gambar tiruan.
