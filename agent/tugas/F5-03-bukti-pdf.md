# F5-03 Bukti pendaftaran PDF

| | |
|---|---|
| Status | selesai |
| Tahap | 5 — Bukti dan cek |
| Dibuat | 12 November 2026 |

## Tujuan

Official bisa mengunduh bukti pendaftaran PDF A4 mendatar di `/bukti/{id-pendaftaran}`.

## Hasil

- [x] Rute, pengendali, dan pustaka `BuktiPendaftaran`
- [x] Kepala (logo, nama acara, seri, tahun, empat baris kepala), penanggung jawab, tabel individu, tabel beregu dengan anggota, total, status (K49)
- [x] Nama berkas dari pengaturan acara (K49)
- [x] Pembatasan laju (kunci `bukti-`), 404 untuk ID yang tidak ada
- [x] `tests/fitur/BuktiPendaftaranTest.php`

## Selesai bila

`composer uji` dan CI lulus; PDF contoh dengan data karangan diperiksa di luar repo.

## Catatan

Rincian di `dokumen/draf/rencana-implementasi-tahap-5.md`.

- `App\Libraries\BuktiPendaftaran`: `namaBerkas()`, `html()` (`app/Views/bukti/pdf.php`), `pdf()` (mPDF `A4-L`, folder sementara `writable/cache/mpdf`). PDF dikirim `inline`, `Cache-Control: no-store`, tidak disimpan di disk.
- Logo persegi WebP dibaca mPDF dari jalur berkas; jalur di `src` di-escape untuk HTML biasa, bukan `attr` (garis miring yang disandikan membuat mPDF gagal memuat gambar).
- Anggota tim ditulis "NAMA (nomor dada)", "–" sebelum Lunas. Tim susulan tanpa baris `pendaftaran_beregu`: "Tanpa biaya (tim sudah terdaftar)".
- Batas laju 10 bukti per menit per alamat IP (kunci `bukti-`), dihitung sebelum ID diperiksa; lewat batas: 429 dengan halaman pesan. ID tidak ada atau bukan berbentuk ID: 404.
- Bukti tidak dibatasi kelengkapan pengaturan (asumsi 2 rencana).
- PDF contoh dengan data karangan diperiksa sebagai gambar di folder sementara, tidak masuk repo.
