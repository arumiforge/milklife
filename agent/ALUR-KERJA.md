# Alur Kerja

Prosedur kerja untuk semua sesi, manusia maupun agen. Aturan wajib ringkasnya ada di `CLAUDE.md`.

## Mulai sesi

1. `agent/PROGRES.md`: tahap berjalan dan status tugas.
2. Berkas tugas yang dikerjakan di `agent/tugas/`.
3. Bila perlu: `agent/KONTEKS.md` (proyek), `agent/ATURAN.md` (aturan berlaku), `agent/PETA-KODE.md` (letak kode), `agent/SETUP-CLOUD.md` (lingkungan cloud belum siap).

## Struktur repo

| Folder | Isi |
|---|---|
| `agent/` | Konteks, progres, aturan, peta kode, keputusan, alur kerja |
| `agent/tugas/` | Satu berkas per tugas: `<ID>-<slug>.md` |
| `agent/arsip/` | Tahap selesai yang dipindahkan dari `PROGRES.md` |
| `dokumen/draf/`, `dokumen/final/`, `dokumen/pdf/` | Dokumen dikerjakan, disetujui, dan PDF-nya |
| `dokumen/templat/` | Templat tugas dan dokumen |
| `sumber/` | Bahan pemilik dan dokumen resmi, persis seperti diterima; tidak diubah. Foto acara sebelumnya dan contoh desain di `sumber/foto-acara/` (K140) |
| `desain/` | Brief dan catatan gambar Codex (K140, K150) |
| `data/` | Data siap impor, kunci JSON berbahasa Indonesia |
| `app/`, `public/`, `writable/`, `tests/` | Aplikasi CodeIgniter 4 |

## Bahasa

- Dokumen, teks antarmuka, URL, pesan commit, dan judul PR berbahasa Indonesia.
- Nama tabel, kolom, rute, dan istilah domain di kode berbahasa Indonesia; akhiran kerangka kerja tetap (`PendaftaranController`, `AtletModel`).
- Teks antarmuka untuk orang non-teknis: tanpa istilah teknis, pesan galat menyebut cara memperbaikinya.

## Tugas

- ID `F<tahap>-<urut dua digit>`, berkas `agent/tugas/F2-03-biaya-kelompok-umur.md` dari `dokumen/templat/tugas.md`.
- **Status hanya di `agent/PROGRES.md`**: `rencana` → `dikerjakan` → `menunggu tinjauan` → `selesai`, diperbarui di PR tugas itu. Berkas tugas tidak menulis status.
- Berkas tugas berisi tujuan, hasil (centang), berkas yang disentuh, uji terkait, dan nomor K. Riwayat pengerjaan ditulis di deskripsi PR.
- Tahap selesai dipindahkan dari `PROGRES.md` ke `agent/arsip/tahap-<n>.md`.

## Dokumen

- Dokumen baru di `dokumen/draf/` dari `dokumen/templat/dokumen.md`; setelah disetujui pemilik dipindah ke `dokumen/final/`.
- Dokumen final boleh diubah langsung sesuai kondisi terbaru (K101), dengan baris revisi di kepalanya.
- PDF dibuat dengan `php spark dokumen:pdf`, tidak disunting tangan, dan dibuat ulang di PR yang sama setiap dokumen final berubah.

## Keputusan

- Keputusan pemilik dicatat di `agent/KEPUTUSAN.md` pada hari yang sama: tanggal, keputusan, alasan. Keputusan lama tidak dihapus; yang berubah ditambah baris baru yang menyebut yang digantikan.
- Bila aturan berubah, baris di `agent/ATURAN.md` diganti dalam PR yang sama. Uji `DokumenAgenTest` memastikan setiap nomor K di sana ada di `KEPUTUSAN.md`.
- Pilihan yang hanya bisa dijawab pemilik ditanyakan di awal sesi sebelum menulis kode.

## Git

- `main` selalu bisa dipasang; semua perubahan lewat PR, digabung *squash* oleh koordinator setelah uji lokal dan CI lulus (K46, K126).
- Penulis commit hanya **Arumi Studios** (`226232558+arumiforge@users.noreply.github.com`); atur `git config user.name` dan `user.email` di setiap sesi.
- Tanpa atribusi alat AI di mana pun: tanpa `Co-Authored-By`, tautan sesi, atau "dibuat oleh …" pada commit, PR, kode, dan dokumen.
- Cabang `fitur/<ID>-<slug>`, `perbaikan/<slug>`, atau `dokumen/<slug>`; cabang otomatis sesi agen boleh memakai nama bawaannya.
- Pesan commit `<jenis>(<lingkup>): <ringkasan>`, jenis `fitur`, `perbaikan`, `dokumen`, `data`, `uji`, `rapikan`, `alat`.

## Uji

- Selama bekerja: `vendor/bin/phpunit --filter <NamaUji>` (lihat `agent/PETA-KODE.md`). Sebelum PR: `composer uji`. CI mengulanginya.

## Kode

- Aturan domain sebagai kelas tanpa ketergantungan HTTP, dengan uji unit. Masukan selalu divalidasi di server.
- Uang disimpan bilangan bulat rupiah. Aturan yang bisa berubah antar-seri disimpan sebagai pengaturan admin.
- Tidak menambah pustaka untuk hal yang cukup beberapa baris kode atau fitur CodeIgniter.
- Berkas CSS/JS asli tetap disimpan; versi kecil dengan `php spark aset:minify`.
- `.env` tidak masuk git; kunci baru ditambahkan ke `.env.example` tanpa nilai rahasia.
