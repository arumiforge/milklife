<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Panel\TimTanpaPembayar;
use App\Libraries\Panel\TolakAtlet;
use App\Libraries\SesiAdmin;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Tolak atlet dari satu pendaftaran: `admin/pendaftaran/<id>/tolak-atlet`
 * (K116, `TolakAtlet`).
 *
 * - `index`: daftar atlet dengan kotak centang dan alasan. Atlet yang
 *   kelompok umurnya tidak cocok lagi dengan pengaturan sekarang ditandai dan
 *   langsung tercentang, dan alasannya diisi kalimat bawaan.
 * - `tolak`: simpan, lalu kembali ke rincian pendaftaran.
 */
class TolakAtletController extends BaseController
{
    private const ALAMAT = 'admin/pendaftaran';

    public const ALASAN_BAWAAN = 'Tahun lahir atlet tidak sesuai dengan kelompok umur yang didaftarkan. '
        . 'Daftarkan ulang atlet ini lewat menu Pendaftaran pada kelompok umur yang sesuai.';

    public function index(int $id): string
    {
        $form = $this->formulirAtau404($id);
        $ada  = array_values(array_filter($form['atlet'], static fn (array $a): bool => ! $a['cocok']));

        return $this->tampilkan($form, array_column($ada, 'id'), $ada === [] ? '' : self::ALASAN_BAWAAN, []);
    }

    public function tolak(int $id): RedirectResponse|string
    {
        $form   = $this->formulirAtau404($id);
        $pilih  = $this->request->getPost('atlet');
        $pilih  = is_array($pilih) ? array_values(array_filter($pilih, is_string(...))) : [];
        $alasan = $this->request->getPost('alasan');
        $alasan = is_string($alasan) ? $alasan : '';

        try {
            $hasil = (new TolakAtlet())->tolak($id, $pilih, $alasan, (string) (session(SesiAdmin::KUNCI_NAMA) ?? 'Admin'));
        } catch (DatabaseException $e) {
            log_message('error', 'Tolak atlet pendaftaran {id} gagal: {pesan}', ['id' => $id, 'pesan' => $e->getMessage()]);

            return $this->tampilkan($form, array_map('intval', $pilih), $alasan, [
                'Atlet belum ditolak. Data sedang dipakai admin lain pada saat yang sama. Tunggu beberapa detik, lalu coba lagi.',
            ]);
        }

        if ($hasil === null) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.');
        }

        if (! $hasil['tersimpan']) {
            return $this->tampilkan($this->formulirAtau404($id), array_map('intval', $pilih), $alasan, $hasil['galat']);
        }

        $pesan = $hasil['jumlah'] . ' atlet ditolak dari pendaftaran ' . $form['pendaftaran']['id_pendaftaran'] . '. NISN-nya bisa didaftarkan lagi. '
            . ($hasil['total'] === $hasil['total_lama']
                ? 'Total tetap ' . rupiah($hasil['total']) . '.'
                : 'Total berubah dari ' . rupiah($hasil['total_lama']) . ' menjadi ' . rupiah($hasil['total']) . '.')
            . ($hasil['semua'] ? ' Pendaftaran ini sekarang tanpa atlet. Ofisial dan bukti bayarnya tetap disimpan.' : '')
            . ' Alasan dan rinciannya tampil di Cek Pendaftaran sampai atlet itu didaftarkan lagi.'
            . ($hasil['tim_ditinggalkan'] === [] ? '' : ' ' . TimTanpaPembayar::kalimatSesudah($hasil['tim_ditinggalkan']));

        return redirect()->to(self::ALAMAT . '/' . $id)->with('pesan_sukses', $pesan);
    }

    /**
     * @return array<string, mixed>
     */
    private function formulirAtau404(int $id): array
    {
        $form = (new TolakAtlet())->formulir($id);

        if ($form === null) {
            throw PageNotFoundException::forPageNotFound('Pendaftaran tidak ditemukan. Mungkin sudah dihapus.');
        }

        return $form;
    }

    /**
     * @param array<string, mixed> $form
     * @param list<int>            $pilih
     * @param list<string>         $galat
     */
    private function tampilkan(array $form, array $pilih, string $alasan, array $galat): string
    {
        return view('admin/pendaftaran/tolak_atlet', [
            'form'      => $form,
            'pilih'     => $pilih,
            'alasan'    => $alasan,
            'galat'     => $galat,
            'alasanMin' => TolakAtlet::PANJANG_ALASAN_MIN,
            'alasanMax' => TolakAtlet::PANJANG_ALASAN_MAKS,
        ]);
    }
}
