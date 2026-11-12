# F7-02 Keamanan dan mode produksi

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Web siap dijalankan di mode produksi: kata sandi admin yang diganti langsung mengeluarkan sesi lain, kepala keamanan aktif, dan pengunjung tidak pernah melihat rincian teknis saat terjadi galat.

## Hasil

- [ ] Sesi admin menyimpan sidik pendek hash kata sandi; `AdminFilter` mengakhiri sesi yang sidiknya tidak cocok dan mengarahkan ke halaman masuk dengan pesan ramah (K67)
- [ ] Sesi yang mengganti kata sandi tetap masuk; `php spark admin:buat` mengeluarkan semua sesi akun itu
- [ ] Filter `secureheaders` aktif; PDF, Excel, gambar admin, dan pratinjau tetap terbuka
- [ ] Halaman 404, 400, dan galat umum di `CI_ENVIRONMENT = production` ramah dan tanpa jejak teknis, dibuktikan uji
- [ ] Bagian Produksi di `.env.example` (berkomentar, tanpa rahasia): mode produksi, `app.baseURL` HTTPS, `aplikasi.minify = true`, kuki aman, dan nilai yang benar untuk `app.forceGlobalSecureRequests` di balik Cloudflare Tunnel beserta alasannya

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup: dua sesi akun yang sama, ganti sandi di satu sesi, sesi lain diminta masuk lagi; sesi pengganti tetap masuk; ganti lewat `admin:buat`; kepala keamanan pada halaman publik dan admin; halaman galat produksi tanpa jejak teknis.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Keamanan dan mode produksi". Asumsi 3 dan 5. Gelombang 1. Bawaan Tahap 2–3: ganti kata sandi belum mengeluarkan sesi lain.
