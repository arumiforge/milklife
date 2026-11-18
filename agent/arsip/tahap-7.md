# Arsip Tahap 7 — Penyelesaian

Selesai: 18 November 2026

Rencana: [`dokumen/draf/rencana-implementasi-tahap-7.md`](../../dokumen/draf/rencana-implementasi-tahap-7.md), disetujui pemilik beserta delapan asumsinya (K69), termasuk bagian "Perubahan saat pengerjaan". Dikerjakan sesi koordinator yang membuat sub-sesi sendiri (K65), paling banyak 3 sekaligus (K38). PR digabung *squash* oleh koordinator setelah uji lokal dan CI lulus (K70, lalu K126).

| Tugas | Status | Gelombang | PR |
|---|---|---|---|
| [F7-01 Minify dan pengecilan gambar](../tugas/F7-01-minify-pengecilan-gambar.md) | selesai | 1 | #60 |
| [F7-02 Keamanan dan mode produksi](../tugas/F7-02-keamanan-mode-produksi.md) | selesai | 1 | #58 |
| [F7-04 Rancangan revisi 4](../tugas/F7-04-rancangan-revisi-4.md) | selesai | 1 | #57, #59 |
| [F7-03 Tim tanpa pembayar](../tugas/F7-03-tim-tanpa-pembayar.md) | selesai | 2 | #62 |
| [F7-05 Cadangan dan panduan produksi](../tugas/F7-05-cadangan-panduan-produksi.md) | selesai | 2 | #63 |
| [F7-06 Uji menyeluruh](../tugas/F7-06-uji-menyeluruh.md) | selesai | 3 | #116, bersama F8-06 |
| [F7-07 Penutupan](../tugas/F7-07-penutupan-tahap-7.md) | selesai | 4 | PR penutupan |
| [F7-08 Data sekolah dan menu Sekolah](../tugas/F7-08-data-sekolah-panel.md) | selesai | sisipan | #81 |
| [F7-09 Usulan nama sekolah dan dokumen final](../tugas/F7-09-usulan-nama-sekolah.md) | selesai | sisipan | #81 |
| [F7-10 Audit bug dan dokumen](../tugas/F7-10-audit-bug-dokumen.md) | selesai | sisipan | #82 |
| [F7-11 Monitoring, pengunjung, Pengecekan, dasbor bergrafik](../tugas/F7-11-pemantau-pengunjung-dasbor.md) | selesai | sisipan | #80, #83 |
| [F7-12 Lebih dari satu regu per nomor beregu](../tugas/F7-12-regu-beregu.md) | selesai | sisipan | #84 |
| [F7-13 Regu tanpa batas bawaan, awalan regu, bukti PDF beregu per baris](../tugas/F7-13-regu-tanpa-batas-awalan.md) | selesai | sisipan | #85 |
| [F7-14 Regu tunggal tanpa nama regu, bukti PDF lebih formal](../tugas/F7-14-regu-tunggal-bukti-formal.md) | selesai | sisipan | #86 |
| [F7-15 Gabung pendaftaran Lunas ber-NPSN sama](../tugas/F7-15-gabung-pendaftaran-npsn.md) | selesai | sisipan | #87 |
| [F7-16 Urutkan ulang nomor atlet (BIB) per sekolah](../tugas/F7-16-urutkan-nomor-dada.md) | selesai | sisipan | #87 |
| [F7-17 Cocokkan aturan web dengan juknis resmi](../tugas/F7-17-cocokkan-juknis.md) | selesai | sisipan | #87 |
| [F7-18 Istilah seragam mengikuti juknis](../tugas/F7-18-istilah-juknis.md) | selesai | sisipan | #87 |
| [F7-19 Tambahkan nama desa ke sekolah bernama sama](../tugas/F7-19-nama-desa-sekolah-kembar.md) | selesai | sisipan | #88 |
| [F7-20 Pemeriksaan setelah git pull](../tugas/F7-20-periksa-pembaruan.md) | selesai | sisipan | #89 |
| [F7-21 Uji lengkap di MariaDB dan pemeriksaan peramban](../tugas/F7-21-uji-mariadb-peramban.md) | selesai | sisipan | #90 |
| [F7-22 Tolak atlet per atlet](../tugas/F7-22-tolak-atlet.md) | selesai | sisipan | #91 |
| [F7-23 Tampilan penolakan atlet dan jejak navigasi admin](../tugas/F7-23-tampilan-penolakan-jejak.md) | selesai | sisipan | #92 |
| [F7-24 Galat CSRF terlihat](../tugas/F7-24-csrf-pesan-galat.md) | selesai | sisipan 1a | #97 |
| [F7-25 Backup data tanpa memuat ke memori](../tugas/F7-25-backup-data-tanpa-memori.md) | selesai | sisipan 1a | #100 |
| [F7-26 Kirim pendaftaran idempoten](../tugas/F7-26-kirim-pendaftaran-idempoten.md) | selesai | sisipan 1b | #98 |
| [F7-27 Cloudflare Turnstile](../tugas/F7-27-turnstile.md) | selesai | sisipan 1b | #101 |
| [F7-28 Rancangan revisi K116–K124](../tugas/F7-28-rancangan-revisi-k116-k124.md) | selesai | sisipan 1c | #96 |
| [F7-29 Susunan dokumen agen](../tugas/F7-29-susunan-dokumen-agen.md) | selesai | sisipan 1c | #99 |
| [F7-30 Pecah berkas besar](../tugas/F7-30-pecah-berkas-besar.md) | selesai | sisipan 3 | #108 |
| [F7-31 PHPStan](../tugas/F7-31-phpstan.md) | selesai | sisipan 3 | #111 |
| [F7-33 Kelompok umur pilihan dalam satu jenjang](../tugas/F7-33-kelompok-umur-pilihan.md) | selesai | sisipan | #109 |
| [F7-32 CSP menyala](../tugas/F7-32-csp-menyala.md) | selesai | sisipan 3 | #115 |

Catatan: tugas sisipan F7-08 sampai F7-33 menambah cakupan atas keputusan pemilik selama Tahap 7 (K97–K117, lalu K118–K123, K127, dan K134). Di antara #64 dan #79, sejumlah PR langsung atas permintaan pemilik tanpa berkas tugas (K72–K96, misalnya #71, #72, #79) mengubah tampilan publik, bukti PDF, wizard, dan kelompok umur. Tahap 8 (K124) berjalan bersamaan dan diarsipkan di [`tahap-8.md`](tahap-8.md).

## Hasil

- **Aset kecil** (F7-01, K72, K86): `php spark aset:minify` membuat `.min.css`, `.min.js`, dan `.min.svg` dari aset aplikasi; berkas asli tetap ada dan berkas `.min` tidak masuk git. Bila `aplikasi.minify = true`, `aset()` memakai versi kecil dengan penanda versi `?v=`, dan filter `perkecilhtml` memperkecil HTML (PDF, Excel, JSON, dan unduhan tidak). Gambar unggahan admin dikecilkan ke 1920 piksel.
- **Keamanan produksi** (F7-02, F7-24, F7-27, F7-32): ganti sandi mengeluarkan sesi lain; kepala keamanan aktif; halaman galat produksi ramah tanpa jejak teknis; galat CSRF terlihat dan tidak merusak banyak tab (K118); Cloudflare Turnstile di kirim pendaftaran, Cek Pendaftaran, dan masuk admin (K122, K128); CSP menyala tanpa skrip dan gaya sebaris tanpa nonce (K127).
- **Tim tanpa pembayar** (F7-03, K68): ditandai di rincian, dasbor, dan rekap keuangan, dan bisa dibebankan lewat Ubah. Batasan yang dibawa dari Tahap 6 ini selesai.
- **Cadangan dan panduan** (F7-05, F7-25): `php spark cadangan:buat` (zip harian, simpan 14 hari, K66); Backup data dikirim tanpa dimuat ke memori; [`dokumen/final/panduan-pemasangan-produksi.md`](../../dokumen/final/panduan-pemasangan-produksi.md) sampai revisi 5; `php spark pembaruan:periksa` setelah `git pull` (F7-20, K115).
- **Rancangan**: revisi 4 (F7-04, K71) lalu diubah langsung sesuai K101 sampai revisi 20 (antara lain F7-28, F7-32, F7-33), PDF dibuat ulang setiap kali.
- **Data sekolah** (F7-08, F7-09, F7-19): 4.766 sekolah dari Sekolah Kita, menu Sekolah, nama pembeda, usulan nama dari pendaftar, dan nama desa untuk sekolah bernama sama (K97, K99, K114).
- **Regu beregu** (F7-12 sampai F7-14): beberapa regu per nomor beregu, biaya per regu, nama dan awalan regu diatur admin, regu tunggal tanpa nama regu (K105–K108).
- **Pendaftaran** (F7-15 sampai F7-18, F7-22, F7-23, F7-26, F7-33): pendaftaran Lunas ber-NPSN sama digabung (K109, K110); nomor atlet (BIB) bisa diurutkan per sekolah (K111); aturan dan istilah mengikuti juknis (K112, K113); tolak atlet per atlet dan menu Atlet ditolak (K116, K117); kirim pendaftaran idempoten (K118); kelompok umur pilihan bila tahun lahir masuk dua KU dalam satu jenjang (K134).
- **Monitoring dan dasbor** (F7-11): halaman `arumi.php` (K102, K104), catatan pengunjung, dasbor bergrafik dengan Bootstrap tetap (K103).
- **Kualitas kode** (F7-10, F7-21, F7-29 sampai F7-31): audit bug dan dokumen; uji lulus di MariaDB; susunan dokumen agen ringkas dengan `DokumenAgenTest` (K120); berkas besar dipecah tanpa mengubah perilaku (K121); PHPStan tingkat 1 dengan `composer analisis` di CI (K123).

## Uji penutupan (F7-06, F7-07)

- F7-06 (#116, bersama F8-06): pemasangan bersih mode produksi dengan minify dan CSP menyala, Chromium 390 dan 1280 piksel untuk semua halaman publik dan admin serta fitur Tahap 8, skenario B1 dan B2, Turnstile dengan kunci uji, dan `tests/fitur/AlurMenyeluruhTest.php`. Tanpa galat konsol dan tanpa pelanggaran CSP. Rincian di [`F7-06-uji-menyeluruh.md`](../tugas/F7-06-uji-menyeluruh.md).
- F7-07: `DokumenAgenTest` kini memeriksa tautan tugas di `PROGRES.md` dan `agent/arsip/` sekaligus, dan tidak lagi gagal saat tidak ada tahap yang berjalan.
- `composer uji` pada cabang penutupan di atas `main` setelah F7-06 digabung (285d0dc): 1589 uji, 11.314 asersi, lulus; `composer analisis` tanpa galat.

## Yang dibawa ke tahap berikutnya

- **Pemilik, di server:** pemasangan nyata di Laragon Windows mengikuti panduan; Turnstile dicoba sekali dengan kunci asli setelah Cloudflare Tunnel berjalan (kirim pendaftaran, Cek Pendaftaran, masuk admin); kesiapan server (`/sehat`, pemantauan uptime, tembolok halaman depan, `client_max_body_size`, `memory_limit`, uji pulih cadangan) (K119).
- **Temuan kecil F7-06, belum diperbaiki:** di mode produksi halaman galat 404 dikirim tanpa kepala CSP (tampilannya benar); rincian admin untuk pendaftaran yang sudah digabung menampilkan halaman tidak ditemukan, bukan mengalihkan ke pendaftaran gabungannya.
- **Tidak dikerjakan sekarang (K119):** data per seri (S8), percepat uji (S10), dan notifikasi WA. Pemulihan cadangan tetap manual mengikuti panduan.
- Konfirmasi tim susulan beregu dicoba lewat uji fitur; di peramban baru susulan nomor individu.
