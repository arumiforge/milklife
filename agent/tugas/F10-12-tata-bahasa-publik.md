# F10-12 Perombakan tata bahasa halaman publik

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |

## Tujuan

Pengunjung, guru, dan ofisial memahami setiap teks di sisi publik sejak pertama kali membaca. Teks ditulis ulang dengan skill `bahasa-indonesia-plain` (permintaan pemilik 22 November 2026). Panel admin menyusul di F10-13.

## Hasil

- [ ] Skill tersimpan di `.claude/skills/bahasa-indonesia-plain/SKILL.md` dan tercantum di tabel "Baca bila perlu" `CLAUDE.md`
- [ ] Teks view publik ditulis ulang: halaman depan, tata letak publik dan potongannya, wizard pendaftaran dan laci bantuan, Cek Pendaftaran, bukti (termasuk PDF), Jadwal, Seri, Hasil, Peserta, Galeri, Bantuan, Unduhan, Videotron, halaman galat, teks bawaan komponen
- [ ] Pesan server untuk publik ditulis ulang: pengendali publik, pemeriksa wizard, `Validation`, teks WA bawaan, JS publik, FAQ bawaan (bahasa saja)
- [ ] Kalimat aktif dan pendek, pesan galat = apa yang terjadi + apa yang perlu dilakukan, tombol berupa kata kerja, sentence case, tanggal "30 November 2026", jam "06.30–10.00 WIB", tanpa singkatan Latin dan "&"; sapaan "Anda", organisasi "panitia"
- [ ] Keputusan pemilik yang menang atas skill tetap: `Rp 150.000` (K80), Bantuan/FAQ (K143), istilah K113 dan K90, "Cek Pendaftaran", judul editorial Inggris (K153, K157), "Born to Run"
- [ ] Perilaku tidak berubah: `name`, `id`, `data-*`, `value`, rute, dan logika tetap
- [ ] Uji yang memeriksa teks diperbarui tanpa menghapus asersi

## Berkas dan uji

View di `app/Views/` (kecuali `admin/` dan `tata_letak/admin_tw.php`), pengendali publik di `app/Controllers/`, `app/Libraries/Pendaftaran/Pemeriksa/`, `app/Config/Validation.php`, `IsiHalamanDepan`, `Bantuan/FaqBawaan`, dan JS publik di `public/aset/js/`. Uji: `composer uji` penuh.

## Selesai bila

`composer uji` lulus, alur daftar → cek → bukti diuji di Chromium 360/390/1280 (mode produksi + minify) tanpa galat konsol/CSP dan tanpa gulir mendatar, PR terbuka dengan tabel sebelum → sesudah, daftar konflik skill vs keputusan, dan evaluasi RASA.

## Catatan

Konflik skill vs keputusan pemilik dan pertanyaan untuk pemilik ditulis di deskripsi PR.
