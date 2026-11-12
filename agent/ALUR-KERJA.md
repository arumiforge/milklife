# Alur Kerja

Aturan kerja untuk semua sesi, baik manusia maupun agen.

## Mulai sesi

Baca berurutan:

1. `agent/KONTEKS.md` — apa proyek ini.
2. `agent/PROGRES.md` — tahap mana yang sedang berjalan.
3. Berkas tugas yang sedang dikerjakan di `agent/tugas/`.
4. `agent/KEPUTUSAN.md` bila menyentuh aturan yang pernah diputuskan.

## Struktur repo

| Folder | Isi |
|---|---|
| `agent/` | Konteks, progres, keputusan, dan alur kerja |
| `agent/tugas/` | Satu berkas per tugas: `<ID>-<slug>.md` |
| `agent/arsip/` | Tahap selesai yang dipindahkan dari `PROGRES.md` |
| `dokumen/draf/` | Dokumen yang masih dikerjakan |
| `dokumen/final/` | Dokumen yang sudah disetujui pemilik |
| `dokumen/pdf/` | PDF dari dokumen final, untuk dibaca pengembang |
| `dokumen/templat/` | Templat tugas dan dokumen |
| `sumber/` | Bahan dari pemilik dan dokumen resmi, persis seperti diterima. Hanya masukan, tidak diubah |
| `data/` | Data siap impor. Kunci JSON berbahasa Indonesia, isi sesuai sumber |
| `app/`, `public/`, `writable/`, `tests/` | Aplikasi CodeIgniter 4 (ada mulai Tahap 1) |

## Bahasa

- Semua dokumen, teks antarmuka, URL, pesan commit, dan judul PR berbahasa Indonesia.
- Nama tabel, kolom, rute, dan istilah domain di kode berbahasa Indonesia (`pendaftaran`, `nomor_lomba`).
- Akhiran dan konvensi kerangka kerja tetap seperti aslinya (`PendaftaranController`, `AtletModel`).
- Teks antarmuka ditulis untuk orang non-teknis: tanpa istilah teknis, pesan galat menyebut cara memperbaikinya.

## Tugas

- ID tugas: `F<tahap>-<urut dua digit>`, contoh `F2-03`. Nama berkas: `F2-03-biaya-kelompok-umur.md`.
- Buat berkas dari `dokumen/templat/tugas.md`.
- Status: `rencana` → `dikerjakan` → `menunggu tinjauan` → `selesai`.
- Satu tugas, satu PR. Perbarui status di berkas tugas dan di `PROGRES.md` dalam PR yang sama.
- Tahap yang selesai dipindahkan dari `PROGRES.md` ke `agent/arsip/tahap-<n>.md`.

## Dokumen

- Dokumen baru ditulis di `dokumen/draf/` dari `dokumen/templat/dokumen.md`.
- Hanya pemilik yang menyetujui dokumen. Setelah disetujui, berkas dipindahkan ke `dokumen/final/` dengan nama yang sama.
- Dokumen final tidak diubah diam-diam. Perubahan dimulai lagi dari draf dan disetujui ulang.
- PDF di `dokumen/pdf/` dibuat dari dokumen final dengan `php spark dokumen:pdf` dan tidak disunting tangan. Setiap dokumen final berubah, PDF dibuat ulang dalam PR yang sama.

## Keputusan

Setiap keputusan pemilik dicatat di `agent/KEPUTUSAN.md` pada hari yang sama: tanggal, keputusan, dan alasannya. Keputusan lama tidak dihapus; bila berubah, tambahkan baris baru yang menyebut keputusan yang digantikan.

## Wawancara

Bila sebuah tahap punya pilihan yang hanya bisa dijawab pemilik, tanyakan di awal sesi sebelum menulis kode. Yang bisa dijawab dari dokumen, kode, atau kebiasaan umum tidak perlu ditanyakan.

## Git

- Cabang utama adalah `main` dan selalu dalam keadaan bisa dipasang. Semua perubahan masuk lewat PR.
- Satu-satunya penulis commit adalah **Arumi Studios** (`226232558+arumiforge@users.noreply.github.com`). Atur dengan `git config user.name` dan `git config user.email` sebelum commit pertama di setiap sesi.
- Tanpa atribusi alat AI di mana pun: tidak ada baris `Co-Authored-By`, tautan sesi, atau tulisan "dibuat oleh …" pada commit, PR, kode, maupun dokumen.
- Nama cabang: `fitur/<ID>-<slug>`, `perbaikan/<slug>`, atau `dokumen/<slug>`. Cabang yang dibuat otomatis oleh sesi agen boleh memakai nama bawaannya.
- Pesan commit: `<jenis>(<lingkup>): <ringkasan>`. Jenis: `fitur`, `perbaikan`, `dokumen`, `data`, `uji`, `rapikan`, `alat`.
  Contoh: `fitur(pendaftaran): hitung biaya individu per kelompok umur`.
- CI GitHub boleh dipakai (K46). Uji dijalankan sendiri di komputer pengembang (`composer uji`) sebelum PR dibuka, lalu GitHub Actions (`.github/workflows/uji.yml`) menjalankan uji yang sama di setiap PR dan push ke `main`. PR digabung dengan *squash* setelah uji lokal dan CI lulus.

## Kode (mulai Tahap 1)

- Aturan domain (usia, biaya, nomor dada, kelengkapan tim) ditulis sebagai kelas tersendiri tanpa ketergantungan pada permintaan HTTP, dan wajib punya uji unit.
- Setiap masukan divalidasi di server. Validasi di peramban hanya untuk kenyamanan.
- Angka uang disimpan sebagai bilangan bulat rupiah.
- Aturan yang bisa berubah antar-seri disimpan sebagai pengaturan admin, bukan ditulis mati di kode.
- Tidak menambah pustaka untuk hal yang bisa diselesaikan beberapa baris kode atau fitur bawaan CodeIgniter.
- Berkas CSS/JS asli tetap disimpan; versi kecil dibuat dengan `php spark aset:minify`.

## Rahasia dan data pribadi

- `.env` tidak pernah masuk git. Kunci baru ditambahkan ke `.env.example` tanpa nilai rahasia.
- Repo ini publik. Data siswa, foto, dan cadangan basis data tidak pernah masuk git, termasuk sebagai contoh atau data uji. Data uji memakai nama dan NISN karangan.
