# Petunjuk untuk agen

Web pendaftaran MilkLife Athletics Challenge Seri 2 – 2026 (CodeIgniter 4.7, PHP 8.2+, MySQL 8). Mulai sesi: baca `agent/PROGRES.md`, lalu berkas tugasmu di `agent/tugas/`.

## Wajib

- Semua tulisan berbahasa Indonesia: dokumen, antarmuka, URL, pesan commit, judul dan isi PR.
- Repo ini publik. Data siswa, foto, cadangan, `.env`, dan rahasia tidak pernah masuk git; data uji memakai nama dan NISN karangan.
- Commit atas nama **Arumi Studios** saja (`226232558+arumiforge@users.noreply.github.com`), tanpa atribusi alat AI: tanpa `Co-Authored-By`, tautan sesi, atau "Generated with …" di commit, PR, kode, dan dokumen.
- Keputusan pemilik dicatat di `agent/KEPUTUSAN.md` pada hari yang sama; bila mengubah aturan, baris di `agent/ATURAN.md` ikut diganti di PR yang sama.
- Berkas di `sumber/` tidak diubah.
- Status tugas hanya ditulis di `agent/PROGRES.md`.
- Satu tugas, satu PR. Sebelum PR: `git fetch origin main && git rebase origin/main`, lalu `composer uji` lulus.

## Baca bila perlu

| Mengerjakan | Baca |
|---|---|
| Apa pun | `agent/PROGRES.md` |
| Memahami proyek | `agent/KONTEKS.md` |
| Aturan domain (kelompok umur, biaya, regu, nomor atlet, cek, bukti) | `agent/ATURAN.md` |
| Mengubah kode | `agent/PETA-KODE.md`, berkas tugas di `agent/tugas/` |
| Membuat tugas, memindah dokumen, menutup tahap, aturan git | `agent/ALUR-KERJA.md` |
| Lingkungan cloud belum siap (MySQL, `vendor/`) | `agent/SETUP-CLOUD.md` |
| Alasan sebuah keputusan | `grep K<nomor> agent/KEPUTUSAN.md` |
| Perilaku lengkap web | `dokumen/final/rancangan-web-pendaftaran.md` |
