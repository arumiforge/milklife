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

## Sesi agen di cloud (K57)

Kontainer sesi cloud selalu baru, jadi pustaka dan MySQL dipasang sekali di awal setiap sesi oleh **Setup script** di setelan lingkungan cloud, bukan oleh agen di tengah kerja.

- Setelah persiapan, agen langsung menjalankan `composer uji`. `composer install` tidak dijalankan lagi selama `vendor/` ada dan `composer.lock` tidak berubah.
- Bila MySQL mati di awal sesi, cukup `service mysql start`.
- Composer berjalan sebagai root, jadi variabel lingkungan `COMPOSER_ALLOW_SUPERUSER=1` diisi di setelan lingkungan (Environment variables). Tanpa itu `composer uji` menolak jalan.
- Bila sesi mulai tanpa persiapan, jalankan isi skrip di bawah ini sekali dari folder di atas repo.

Isi Setup script yang dipakai:

```bash
#!/bin/bash
set -e

# 1. MySQL 8 untuk uji
if ! command -v mysqld >/dev/null 2>&1; then
  apt-get update -qq
  DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mysql-server
fi
mkdir -p /var/run/mysqld && chown mysql:mysql /var/run/mysqld
service mysql start
for i in $(seq 1 30); do mysqladmin ping --silent && break; sleep 1; done

# root tanpa sandi lewat 127.0.0.1, sesuai .env.example
mysql -u root <<'SQL'
ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '';
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
CREATE DATABASE IF NOT EXISTS milklife_uji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

# 2. Pustaka PHP dan .env
cd milklife
[ -f .env ] || cp .env.example .env
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-progress --prefer-dist
```

Basis data dan sandi di atas hanya untuk uji sekali pakai di kontainer, bukan rahasia.

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
