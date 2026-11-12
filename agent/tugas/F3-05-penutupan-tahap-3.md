# F3-05 Uji gabungan dan penutupan Tahap 3

| | |
|---|---|
| Status | selesai |
| Tahap | 3 — Halaman depan |
| Dibuat | 12 November 2026 |

## Tujuan

Semua tugas Tahap 3 bekerja bersama di `main`, dan Tahap 4 bisa segera mulai.

## Hasil

- [x] Semua cabang diuji bersama sebelum digabung; `main` setelah digabung sama dengan salinan yang diuji
- [x] Salinan baru `main`: pemasangan dari nol, `composer uji` lulus, halaman depan dan `/unduhan` diperiksa di peramban (390 dan 1440 piksel) dengan data karangan
- [x] Bagian "Perubahan saat pengerjaan" di rencana Tahap 3
- [x] Arsip `agent/arsip/tahap-3.md`; `PROGRES.md` menunjuk Tahap 4

## Selesai bila

PR penutupan digabung atas izin pemilik.

## Catatan

Dikerjakan koordinator.

- Cabang F3-01 + F3-04 diuji bersama sebelum digabung (776 uji lulus), lalu F3-02 + F3-03 di atas `main` baru (798 uji lulus). Tidak ada konflik gabungan; wilayah CSS terpisah bekerja. Isi `main` setelah tiap pasangan digabung sama persis dengan salinan gabungan yang diuji.
- Salinan baru `main` (5d78a16) dengan basis data baru: `migrate --all`, `sekolah:impor` 4.729 sekolah, `data:awal` 5 kelompok umur dan 69 nomor lomba, `composer uji` JUMLAH_UJI uji lulus.
- Peramban (Chromium, 390 dan 1440 piksel): pemasangan baru tanpa isi pengaturan (kepala tanpa gambar, lima kelompok umur dengan "segera diumumkan", hanya tombol bagikan di bagian kontak, `/unduhan` kosong dengan pesan ramah) dan dengan data karangan lengkap (gambar, biaya, jadwal, unduhan PDF, narahubung, media sosial, WA tanya admin); tanpa gulir mendatar.
- Sambungan yang dirapikan: keterangan kotak "Teks pengantar" di panel Halaman Depan menjadi "Tekan Enter dua kali untuk membuat paragraf baru." (laporan F3-01); contoh judul di komentar `BerandaPengaturanController` (laporan F3-04); baris status `README.md`.
- K44 (tinjauan pemilik): gambar kepala menjadi latar di belakang judul (`_kepala.php` kelas `pembuka-dengan-gambar`, gaya `.pembuka-gambar` di `tema.css`), keterangan unggah gambar kepala di `HalamanDepanController::GAMBAR` disesuaikan, `HalamanDepanKepalaTest` ditambah satu pemeriksaan. Diperiksa di Chromium 390 dan 1440 piksel dengan foto contoh: tulisan tetap terbaca, tanpa gulir mendatar.
- K45 (permintaan pemilik langsung ke sub-sesi F3-02 setelah PR #27 digabung, diverifikasi dari riwayat sesi): commit sakelar biaya dari cabang F3-02 dipetik (`cherry-pick`) ke cabang penutupan; konflik hanya di berkas tugas F3-02.
