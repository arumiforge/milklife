<?php

namespace App\Controllers;

use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;

/**
 * Daftar seri dan lintasan publik (`/jadwal/<id babak>`, F9-08): seri dan
 * lintasan babak yang daftarnya sudah dikunci admin, ditambah bagan alur
 * babak nomornya (Penyisihan seri 1…n → Semifinal → Final) dengan status dan
 * siapa yang lolos.
 *
 * - 404 selama sakelar `HalamanDepan.tampil_seri` mati (bawaan) dan untuk
 *   babak yang daftar serinya belum dikunci.
 * - Tanda lolos (Q/q) dan prestasi hanya tampil setelah hasil nomornya terbit.
 */
class SeriController extends BaseController
{
    public function babak(int $id): string
    {
        if (! SeriLintasan::tampil()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $seri  = new SeriLintasan();
        $babak = $seri->babak($id);

        // Nomor yang tidak dilombakan keluar dari daftar seri (F11-03).
        if ($babak === null || ! $babak['dikunci'] || ! ($babak['nomor']['dilombakan'] ?? true)) {
            throw PageNotFoundException::forPageNotFound('Daftar seri babak ini belum diumumkan.');
        }

        $kini  = new DateTimeImmutable('now');
        $bagan = $seri->bagan($babak['nomor']['id'], true, $kini);
        $ini   = array_values(array_filter($bagan, static fn (array $b): bool => $b['id'] === $id))[0] ?? null;

        return view('seri/babak', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'babak'     => $babak,
            'perSeri'   => $ini['seri'] ?? [],
            'tanda'     => $ini['tanda'] ?? [],
            'lolos'     => $seri->aturanLolos($babak),
            'bagan'     => $bagan,
            'terbit'    => $babak['nomor']['terbit_pada'] !== null,
            'tampilJadwal' => JadwalController::tampil(),
        ]);
    }
}
