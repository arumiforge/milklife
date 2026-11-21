# F9-04 Bantuan dan FAQ

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Ofisial dan guru menemukan jawaban sendiri untuk pertanyaan apa pun tentang pendaftaran sampai hasil lomba, tanpa harus menghubungi panitia.

## Hasil

- [ ] Tabel `faq` dan kelola di Pengaturan > Bantuan (tambah, ubah, urutkan, nonaktifkan; Markdown sederhana)
- [ ] Data bawaan 40–60 tanya-jawab per kategori, disusun dari rancangan final, `agent/ATURAN.md`, dan juknis, dipasang `data:awal` dan perintah `faq:awal` (tidak menimpa isian admin)
- [ ] `/bantuan`: pencarian langsung, kategori dengan ilustrasi G9-07, panduan bergambar G9-03, tombol WA panitia
- [ ] Tombol "?" di tiap langkah wizard membuka laci bantuan berisi FAQ kategori langkah itu (tampilan wizard lama tetap Bootstrap sampai F9-05)
- [ ] Ikut Backup data/impor (K76)

## Berkas dan uji

Migrasi `faq`, `app/Models/FaqModel.php`, `app/Controllers/BantuanController.php`, `app/Controllers/Admin/Pengaturan/FaqController.php`, `app/Views/bantuan/`, `app/Views/admin/pengaturan/faq/`, `public/aset/js/bantuan.js`, `app/Commands/FaqAwal.php`, blok wizard. Uji baru `BantuanFaqTest`, `PengaturanFaqTest`.

## Selesai bila

`/bantuan` dan laci bantuan wizard berjalan di ponsel, admin bisa mengubah FAQ, uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 12. Daftar pertanyaan dilampirkan di deskripsi PR untuk ditinjau pemilik.
