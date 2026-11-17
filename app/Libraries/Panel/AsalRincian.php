<?php

namespace App\Libraries\Panel;

/**
 * Asal halaman rincian pendaftaran (K117): dari daftar Pendaftaran, Atlet,
 * atau Atlet ditolak.
 *
 * Rincian pendaftaran bisa dibuka dari beberapa menu. Supaya admin tidak
 * "tersesat" (misalnya dari menu Atlet tiba-tiba jejaknya Pendaftaran), tautan
 * ke rincian membawa `?dari=<asal>`. Asal itu disimpan di sesi per id
 * pendaftaran, sehingga halaman turunannya (Ubah, Tolak atlet) dan halaman
 * sesudah menyimpan tetap memakai asal yang sama tanpa membawa parameter.
 * Asal dipakai untuk jejak navigasi dan butir menu yang aktif.
 *
 *     $asal = AsalRincian::catat($id, $this->request->getGet('dari')); // ['label' => 'Atlet', 'alamat' => 'admin/atlet']
 *     AsalRincian::untuk($id);                                         // asal tersimpan, bawaannya Pendaftaran
 *     AsalRincian::menuAktif();                                        // 'admin/atlet' di halaman rincian, atau null
 *
 * Nilai `dari` yang tidak dikenal diabaikan.
 */
final class AsalRincian
{
    /**
     * @var array<string, array{label: string, alamat: string}>
     */
    public const ASAL = [
        'pendaftaran'   => ['label' => 'Pendaftaran', 'alamat' => 'admin/pendaftaran'],
        'atlet'         => ['label' => 'Atlet', 'alamat' => 'admin/atlet'],
        'atlet-ditolak' => ['label' => 'Atlet ditolak', 'alamat' => 'admin/atlet-ditolak'],
    ];

    private const KUNCI_SESI = 'asal_rincian';

    /**
     * Banyak id pendaftaran yang diingat asalnya; yang paling lama dibuang.
     */
    private const BATAS = 30;

    /**
     * Simpan asal bila `dari` dikenal, lalu kembalikan asal untuk id ini.
     *
     * @return array{label: string, alamat: string}
     */
    public static function catat(int $id, mixed $dari): array
    {
        if (is_string($dari) && isset(self::ASAL[$dari])) {
            $simpan = session(self::KUNCI_SESI);
            $simpan = is_array($simpan) ? $simpan : [];
            unset($simpan[$id]);
            $simpan[$id] = $dari;
            session()->set(self::KUNCI_SESI, array_slice($simpan, -self::BATAS, null, true));
        }

        return self::untuk($id);
    }

    /**
     * Asal tersimpan untuk id ini; Pendaftaran bila belum ada.
     *
     * @return array{label: string, alamat: string}
     */
    public static function untuk(int $id): array
    {
        $simpan = session(self::KUNCI_SESI);
        $kunci  = is_array($simpan) ? ($simpan[$id] ?? 'pendaftaran') : 'pendaftaran';

        return self::ASAL[$kunci] ?? self::ASAL['pendaftaran'];
    }

    /**
     * Alamat butir menu yang aktif di halaman rincian pendaftaran dan turunannya,
     * atau `null` di halaman lain (menu memakai alamat halaman seperti biasa).
     */
    public static function menuAktif(): ?string
    {
        if (preg_match('#^admin/pendaftaran/(\d+)(/|$)#', trim(uri_string(), '/'), $cocok) !== 1) {
            return null;
        }

        return self::untuk((int) $cocok[1])['alamat'];
    }
}
