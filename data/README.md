# Data

Data siap impor. Kunci JSON berbahasa Indonesia, isi sesuai sumber.

## `sekolah/sekolah.json`

Daftar satuan pendidikan formal tingkat dasar sampai menengah di 5 kabupaten Karesidenan Pati.

| | |
|---|---|
| Sumber | https://sekolah.data.kemendikdasmen.go.id/sekolah (Sekolah Kita, data verifikasi Kemendikdasmen, memuat sekolah dan madrasah), digabung dengan data sebelumnya dari https://referensi.data.kemendikdasmen.go.id |
| Diambil | 10 November 2026, diperbarui 14 November 2026 |
| Jumlah | 4.766 sekolah, NPSN unik |

| Kabupaten | SD sederajat | SMP sederajat | SMA sederajat | Jumlah |
|---|---|---|---|---|
| Kab. Blora | 676 | 163 | 105 | 944 |
| Kab. Rembang | 435 | 117 | 75 | 627 |
| Kab. Pati | 826 | 242 | 160 | 1.228 |
| Kab. Kudus | 577 | 132 | 98 | 807 |
| Kab. Jepara | 773 | 233 | 154 | 1.160 |

Kunci tiap sekolah: `npsn`, `nama`, `bentuk`, `jenjang`, `status`, `alamat`, `desa_kelurahan`, `kecamatan`, `kode_kecamatan`, `kabupaten_kota`, `kode_kabupaten_kota`.

- `bentuk` sesuai sumber: SD, MI, SMP, MTs, SMA, SMK, MA, dan bentuk lain yang lebih jarang (SLB, MAK, SDTK, PDF, SPM).
- `jenjang` ditambahkan saat pengambilan: `SD`, `SMP`, atau `SMA`. Untuk SLB nilainya mengikuti halaman sumber dan belum tentu tepat.
- Kode wilayah memakai kode Dapodik, bukan kode Kemendagri.
- **Nama kembar.** Data resmi memakai nama umum untuk banyak madrasah: 501 sekolah bernama sama dengan sekolah lain (misalnya enam "MIS MUHAMMADIYAH" di desa berbeda). Sekolah Kita memakai nama yang sama persis, jadi pembaruan data tidak menghilangkannya. Admin memberi nama pembeda di panel, menu **Sekolah** (K97); sekolah yang sudah disunting admin tidak ditimpa `php spark sekolah:impor`.

### Pembaruan 14 November 2026

Dibandingkan dengan data 10 November: 37 sekolah aktif yang belum ada ditambahkan (17 di antaranya SD negeri di Blora), 125 nama mengikuti ejaan Sekolah Kita (hanya beda huruf besar-kecil), dan 3 madrasah ber-NPSN baru yang belum tampil di Sekolah Kita tetap disimpan. Tidak ada nama yang benar-benar berganti, dan tidak ada perubahan bentuk, status, atau kabupaten.

Hasil penelusuran apa adanya ada di `sekolah/mentah/sekolah-kita-2026-11-14.json` (satu baris per kartu sekolah: NPSN, nama, bentuk, status, akreditasi, alamat, kabupaten).

## `halaman-depan/`

Gambar bawaan halaman depan dari pemilik (K47), dipasang `php spark data:awal` bila pengaturannya masih kosong. Admin bisa mengganti atau menghapusnya di Pengaturan > Halaman Depan.

| Berkas | Dipakai sebagai | Ukuran |
|---|---|---|
| `gambar-kepala.webp` | Gambar kepala (foto di sisi kanan bagian pembuka, K81) | 1874 × 839 piksel |
| `logo-persegi.webp` | Logo persegi MilkLife Athletics Challenge | 657 × 682 piksel |

## `pembayaran/`

Logo bank bawaan dari pemilik (K73), dipasang `php spark data:awal` sebagai logo bank bila pengaturannya masih kosong.

| Berkas | Dipakai sebagai | Ukuran |
|---|---|---|
| `logo-bank.png` | Logo bank (Bank Jateng) di langkah pembayaran | 628 × 318 piksel |

## `unduhan/`

Contoh berkas unduhan (K73), **berkas kosong** yang dipasang `php spark data:awal` bila tabel unduhan masih kosong, bersama flyer dan banner asli dari `sumber/flyer/`. Admin mengganti isinya lewat Pengaturan > Unduhan sebelum produksi.

| Berkas | Judul unduhan |
|---|---|
| `petunjuk-teknis.pdf` | Petunjuk teknis lomba |
| `surat-keterangan-sekolah.docx` | Surat keterangan sekolah |
| `daftar-atlet.xlsx` | Daftar atlet sekolah |
| `denah-lokasi.png` | Denah lokasi |

## Memperbarui

### Dari Sekolah Kita (cara 14 November 2026)

1. Buka https://sekolah.data.kemendikdasmen.go.id/sekolah di peramban. Saringan halaman itu tersimpan di alamatnya, jadi tiap kombinasi kabupaten dan bentuk pendidikan bisa dibuka langsung, 100 kartu per halaman:
   `/sekolah?keyword=&kabupaten_kota=Kab.%20Kudus&bentuk_pendidikan=MI&status_sekolah=&page=0&size=100`
2. Untuk kelima kabupaten dan bentuk SD, MI, SMP, MTs, SMA, SMK, MA, MAK, SLB, SDTK, PDF Ulya, PDF Wustha, SPM Ula, SPM Ulya, SPM Wustha, catat isi setiap kartu (NPSN, nama, status, akreditasi, alamat) ke satu berkas JSON berbentuk sama dengan `sekolah/mentah/sekolah-kita-2026-11-14.json`. Pastikan jumlah kartu sama dengan angka "… Sekolah" di halaman.
3. Gabungkan dengan data lama, lalu impor:

```bash
python3 -I data/sekolah/gabung_sekolahkita.py data/sekolah/mentah/<berkas>.json data/sekolah/sekolah.json > sekolah-baru.json
mv sekolah-baru.json data/sekolah/sekolah.json
php spark sekolah:impor
```

Aturan penggabungan ditulis di awal `gabung_sekolahkita.py`: desa dan kecamatan sekolah lama dipertahankan, nama/bentuk/status mengikuti Sekolah Kita, NPSN baru ditambahkan, dan tidak ada sekolah yang dihapus.

### Dari Data Referensi (cara 10 November 2026)

Butuh Python 3. Memakan waktu sekitar 10 menit (152 halaman, dengan jeda antar-permintaan).

```bash
python3 data/sekolah/ambil_sekolah.py 031600:"Kab. Blora" 031700:"Kab. Rembang" 031800:"Kab. Pati" 031900:"Kab. Kudus" 032000:"Kab. Jepara" > data/sekolah/sekolah.json
```

Skrip membaca halaman HTML situs sumber. Bila tata letak situs berubah, skrip berhenti dengan galat dan perlu disesuaikan. Cara ini menimpa berkas, jadi sekolah yang hanya ada di Sekolah Kita akan hilang dari berkas (tetapi tidak dari basis data, karena impor tidak menghapus).
