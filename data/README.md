# Data

Data siap impor. Kunci JSON berbahasa Indonesia, isi sesuai sumber.

## `sekolah/sekolah.json`

Daftar satuan pendidikan formal tingkat dasar sampai menengah di 5 kabupaten Karesidenan Pati.

| | |
|---|---|
| Sumber | https://referensi.data.kemendikdasmen.go.id (Data Referensi Kemendikdasmen, memuat sekolah dan madrasah) |
| Diambil | 10 November 2026 |
| Jumlah | 4.729 sekolah, NPSN unik |

| Kabupaten | SD sederajat | SMP sederajat | SMA sederajat | Jumlah |
|---|---|---|---|---|
| Kab. Blora | 659 | 162 | 104 | 925 |
| Kab. Rembang | 432 | 117 | 75 | 624 |
| Kab. Pati | 826 | 240 | 159 | 1.225 |
| Kab. Kudus | 570 | 131 | 98 | 799 |
| Kab. Jepara | 773 | 230 | 153 | 1.156 |

Kunci tiap sekolah: `npsn`, `nama`, `bentuk`, `jenjang`, `status`, `alamat`, `desa_kelurahan`, `kecamatan`, `kode_kecamatan`, `kabupaten_kota`, `kode_kabupaten_kota`.

- `bentuk` sesuai sumber: SD, MI, SMP, MTs, SMA, SMK, MA, dan bentuk lain yang lebih jarang (SLB, MAK, SDTK, PDF, SPM).
- `jenjang` ditambahkan saat pengambilan: `SD`, `SMP`, atau `SMA`. Untuk SLB (19 sekolah) nilainya mengikuti halaman sumber dan belum tentu tepat.
- Kode wilayah memakai kode Dapodik, bukan kode Kemendagri.

## `halaman-depan/`

Gambar bawaan halaman depan dari pemilik (K47), dipasang `php spark data:awal` bila pengaturannya masih kosong. Admin bisa mengganti atau menghapusnya di Pengaturan > Halaman Depan.

| Berkas | Dipakai sebagai | Ukuran |
|---|---|---|
| `gambar-kepala.webp` | Gambar kepala (latar di belakang judul) | 1874 × 839 piksel |
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

Butuh Python 3. Memakan waktu sekitar 10 menit (152 halaman, dengan jeda antar-permintaan).

```bash
python3 data/sekolah/ambil_sekolah.py 031600:"Kab. Blora" 031700:"Kab. Rembang" 031800:"Kab. Pati" 031900:"Kab. Kudus" 032000:"Kab. Jepara" > data/sekolah/sekolah.json
```

Skrip membaca halaman HTML situs sumber. Bila tata letak situs berubah, skrip berhenti dengan galat dan perlu disesuaikan.
