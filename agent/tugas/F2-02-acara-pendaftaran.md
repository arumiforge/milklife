# F2-02 Pengaturan acara dan pendaftaran

| | |
|---|---|
| Status | dikerjakan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin bisa mengisi data acara serta jadwal dan aturan pendaftaran, dan nama acara di seluruh tampilan mengikuti pengaturan.

## Hasil

- [ ] `/admin/pengaturan/acara`: nama, seri, tahun, tempat, tanggal mulai dan selesai acara (selesai tidak sebelum mulai)
- [ ] `/admin/pengaturan/pendaftaran`: waktu mulai dan akhir (akhir setelah mulai), buka manual dan batasnya, tampilan usia (keterangan saja, dihitung pada tanggal mulai acara; K36, K37), batas penanggung jawab (minimal 1, maksimal tidak kurang dari minimal), batas nomor individu
- [ ] Nama, seri, tahun, tempat, dan tanggal acara di tata letak publik, admin, polos, dan `beranda.php` diambil dari pengaturan, dengan teks pengganti yang wajar bila belum diisi
- [ ] Kunci `tanggal_patokan_usia` dihapus dari `Config\Pendaftaran` (K37)
- [ ] Uji fitur kedua halaman dan uji tata letak yang terdampak

## Selesai bila

`composer uji` lulus; kedua halaman bisa diisi dan disimpan; mengubah nama acara langsung terlihat di beranda dan kepala admin.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.
