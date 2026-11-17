# F7-29 Susunan dokumen agen dirampingkan

| | |
|---|---|
| Status | rencana |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Sesi agen membaca lebih sedikit di awal dan tahu di mana mencari aturan, kode, dan status (analisis bagian 3).

## Hasil

- [ ] `CLAUDE.md` ringkas (≤ 3 KB) dengan aturan wajib dan tabel "baca bila perlu"
- [ ] `agent/ATURAN.md`: aturan yang masih berlaku per domain, satu baris per aturan dengan nomor K
- [ ] `agent/PETA-KODE.md`: fitur → rute → pengendali → pustaka → view → JS → uji, invarian kritis, perintah uji per area
- [ ] `agent/SETUP-CLOUD.md`: skrip setup dipindah dari `ALUR-KERJA.md`
- [ ] `agent/ALUR-KERJA.md` ≤ 4 KB; status tugas hanya di `agent/PROGRES.md` (baris Status berkas tugas diganti tautan atau dihapus sesuai susunan baru)
- [ ] Uji penjaga dokumen: setiap tugas di `PROGRES.md` punya berkas di `agent/tugas/`, setiap nomor K di `ATURAN.md` ada di `KEPUTUSAN.md`

## Selesai bila

Berkas baru ada, uji penjaga lulus, `composer uji` lulus.

## Catatan

K120 (termasuk persetujuan asumsi 5 analisis). Hook dan perintah `.claude/` opsional, tidak wajib. Rebase sebelum PR karena `PROGRES.md` diubah tugas lain.
