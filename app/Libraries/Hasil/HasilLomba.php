<?php

namespace App\Libraries\Hasil;

use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * Hasil lomba per nomor (F8-04, K124 butir 2–4 dan 7).
 *
 * - Babak diatur admin per nomor; paling banyak satu Final, yang selalu
 *   tampil paling akhir. Juara I–III diambil dari Final.
 * - Peserta babak dipilih dari peserta Lunas nomor itu (`PesertaNomor`).
 * - Prestasi ditulis admin (`Prestasi`), peringkat dihitung otomatis
 *   (`Peringkat`). Di Final admin bisa mengoreksi peringkat (misalnya dari
 *   foto finis); baris itu ditandai "diubah admin" dan tidak dihitung ulang.
 * - Terbitkan / Tarik kembali per nomor (`nomor_lomba.hasil_terbit_pada`).
 *
 * Semua perubahan satu babak berjalan dalam satu transaksi dengan baris babak
 * dikunci (`FOR UPDATE`), supaya peringkat selalu cocok dengan prestasinya.
 */
final class HasilLomba
{
    public const NAMA_BABAK_MAKSIMAL = 60;
    public const PERINGKAT_MAKSIMAL  = 999;

    private BaseConnection $db;
    private PesertaNomor $peserta;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db      = $db ?? db_connect();
        $this->peserta = new PesertaNomor($this->db);
    }

    /**
     * Satu nomor lomba beserta nama kelompok umurnya, atau `null`.
     *
     * @return array<string, mixed>|null
     */
    public function nomor(int $id): ?array
    {
        $nomor = $this->db->table('nomor_lomba n')
            ->select('n.*, ku.nama AS nama_kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = n.kelompok_umur_id')
            ->where('n.id', $id)
            ->get()
            ->getRowArray();

        return $nomor === null ? null : $nomor + ['nama_lengkap' => NomorLombaModel::namaLengkap($nomor)];
    }

    /**
     * Semua nomor lomba per kelompok umur dengan keadaan hasilnya, untuk
     * daftar di panel dan halaman publik.
     *
     * @param bool $hanyaTerbit Hanya nomor yang hasilnya sudah terbit.
     *
     * @return list<array{id: int, nama: string, nomor: list<array<string, mixed>>}>
     */
    public function daftarNomor(bool $hanyaTerbit = false): array
    {
        $kueri = $this->db->table('nomor_lomba n')
            ->select('n.*, ku.nama AS nama_kelompok_umur')
            ->select('(SELECT COUNT(*) FROM babak b WHERE b.nomor_lomba_id = n.id) AS jumlah_babak', false)
            ->select('(SELECT COUNT(*) FROM babak b JOIN hasil h ON h.babak_id = b.id WHERE b.nomor_lomba_id = n.id AND b.final = 1) AS peserta_final', false)
            ->select('(SELECT COUNT(*) FROM babak b JOIN hasil h ON h.babak_id = b.id WHERE b.nomor_lomba_id = n.id AND b.final = 1 AND (h.prestasi IS NOT NULL OR h.keterangan IS NOT NULL)) AS terisi_final', false)
            ->select('(SELECT COUNT(*) FROM babak b WHERE b.nomor_lomba_id = n.id AND b.final = 1) AS ada_final', false)
            ->join('kelompok_umur ku', 'ku.id = n.kelompok_umur_id')
            ->orderBy('ku.urutan', 'ASC')
            ->orderBy('ku.id', 'ASC')
            ->orderBy('n.urutan', 'ASC')
            ->orderBy('n.id', 'ASC');

        if ($hanyaTerbit) {
            $kueri->where('n.hasil_terbit_pada IS NOT NULL');
        } else {
            // Nomor tidak aktif tanpa babak tidak perlu tampil di panel hasil.
            $kueri->groupStart()->where('n.aktif', 1)->orWhere('(SELECT COUNT(*) FROM babak b WHERE b.nomor_lomba_id = n.id) >', 0, false)->groupEnd();
        }

        $kelompok = [];

        foreach ($kueri->get()->getResultArray() as $n) {
            $ku = (int) $n['kelompok_umur_id'];
            $kelompok[$ku] ??= ['id' => $ku, 'nama' => (string) $n['nama_kelompok_umur'], 'nomor' => []];
            $n['nama_lengkap']          = NomorLombaModel::namaLengkap($n);
            $kelompok[$ku]['nomor'][]   = $n;
        }

        return array_values($kelompok);
    }

    /**
     * Ada nomor yang hasilnya sudah terbit? (tautan di halaman depan)
     */
    public function adaTerbit(): bool
    {
        return $this->db->table('nomor_lomba')->where('hasil_terbit_pada IS NOT NULL')->countAllResults() > 0;
    }

    /**
     * Juara I–III babak Final semua nomor (peringkat 1–3, tanpa DNS/DNF/DQ),
     * dikelompokkan per nomor lomba. Peringkat sama memberi juara yang sama
     * kepada masing-masing (asumsi 7 rencana Tahap 8).
     *
     * @param bool $hanyaTerbit Hanya nomor yang hasilnya sudah terbit.
     *
     * @return array<int, list<array{peringkat: int, nama: string, nomor_dada: string, sekolah: string, jenjang: string, kontingen: string, regu: string|null, prestasi_teks: string, anggota: list<array<string, string>>}>> id nomor => juara urut peringkat
     */
    public function juara(bool $hanyaTerbit): array
    {
        $kueri = $this->db->table('hasil h')
            ->select('h.*, b.nomor_lomba_id, n.id AS n_id, n.jenis, n.jenis_hasil')
            ->join('babak b', 'b.id = h.babak_id AND b.final = 1')
            ->join('nomor_lomba n', 'n.id = b.nomor_lomba_id')
            ->where('h.peringkat >=', 1)
            ->where('h.peringkat <=', 3)
            ->where('h.keterangan', null)
            ->orderBy('h.peringkat', 'ASC');

        if ($hanyaTerbit) {
            $kueri->where('n.hasil_terbit_pada IS NOT NULL');
        }

        $peserta = [];
        $hasil   = [];

        foreach ($kueri->get()->getResultArray() as $h) {
            $nomorId = (int) $h['nomor_lomba_id'];
            $peserta[$nomorId] ??= $this->peserta->semua(['id' => $nomorId, 'jenis' => $h['jenis']]);
            $baris               = $this->lengkapi($h, $peserta[$nomorId], (string) $h['jenis_hasil']);

            if ($baris['sekolah'] === '') {
                continue; // peserta sudah tidak ada
            }

            $hasil[$nomorId][] = [
                'peringkat'     => (int) $baris['peringkat'],
                'nama'          => $baris['nama'],
                'nomor_dada'    => $baris['nomor_dada'],
                'sekolah'       => $baris['sekolah'],
                'jenjang'       => $baris['jenjang'],
                'kontingen'     => $baris['kontingen'],
                'regu'          => $baris['regu'],
                'prestasi_teks' => $baris['prestasi_teks'],
                'anggota'       => $baris['anggota'],
            ];
        }

        foreach ($hasil as $nomorId => $daftar) {
            usort($daftar, static fn (array $a, array $b): int => [$a['peringkat'], $a['sekolah'], $a['nama']] <=> [$b['peringkat'], $b['sekolah'], $b['nama']]);
            $hasil[$nomorId] = $daftar;
        }

        return $hasil;
    }

    /**
     * Babak satu nomor: babak biasa urut dibuat, Final paling akhir.
     *
     * @return list<array<string, mixed>>
     */
    public function daftarBabak(int $nomorId): array
    {
        return $this->db->table('babak b')
            ->select('b.*')
            ->select('(SELECT COUNT(*) FROM hasil h WHERE h.babak_id = b.id) AS jumlah_peserta', false)
            ->select('(SELECT COUNT(*) FROM hasil h WHERE h.babak_id = b.id AND (h.prestasi IS NOT NULL OR h.keterangan IS NOT NULL)) AS jumlah_terisi', false)
            ->where('b.nomor_lomba_id', $nomorId)
            ->orderBy('b.final', 'ASC')
            ->orderBy('b.urutan', 'ASC')
            ->orderBy('b.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function babak(int $id): ?array
    {
        return $this->db->table('babak')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Susunan lengkap hasil satu nomor: setiap babak dengan barisnya yang
     * sudah diurutkan dan dilengkapi nama peserta. Untuk halaman, PDF, dan Excel.
     *
     * @param array<string, mixed> $nomor Hasil `nomor()`.
     *
     * @return list<array<string, mixed>> babak dengan kunci `baris`
     */
    public function susunan(array $nomor): array
    {
        $babak = $this->daftarBabak((int) $nomor['id']);

        if ($babak === []) {
            return [];
        }

        $peserta = $this->peserta->semua($nomor);
        $baris   = $this->db->table('hasil')->whereIn('babak_id', array_column($babak, 'id'))->get()->getResultArray();
        $perBabak = [];

        foreach ($baris as $h) {
            $perBabak[(int) $h['babak_id']][] = $this->lengkapi($h, $peserta, (string) $nomor['jenis_hasil']);
        }

        foreach ($babak as $i => $b) {
            $babak[$i]['baris'] = Peringkat::urutkan($this->urutNama($perBabak[(int) $b['id']] ?? []));
        }

        return $babak;
    }

    /**
     * Baris satu babak (untuk formulir isian), urut seperti tampilan.
     *
     * @param array<string, mixed> $nomor
     * @param array<string, mixed> $babak
     *
     * @return list<array<string, mixed>>
     */
    public function barisBabak(array $nomor, array $babak): array
    {
        $peserta = $this->peserta->semua($nomor);
        $baris   = $this->db->table('hasil')->where('babak_id', (int) $babak['id'])->get()->getResultArray();

        return Peringkat::urutkan($this->urutNama(array_map(fn (array $h): array => $this->lengkapi($h, $peserta, (string) $nomor['jenis_hasil']), $baris)));
    }

    /**
     * Peserta Lunas yang belum ada di babak itu, dengan catatan hasilnya di
     * babak lain (untuk memilih yang lolos ke babak berikutnya).
     *
     * @param array<string, mixed> $nomor
     * @param array<string, mixed> $babak
     *
     * @return list<array<string, mixed>> peserta dengan kunci `catatan` (teks)
     */
    public function pesertaBisaDitambah(array $nomor, array $babak): array
    {
        $sudahAda = array_map(
            static fn (array $h): string => PesertaNomor::kunciHasil($h),
            $this->db->table('hasil')->where('babak_id', (int) $babak['id'])->get()->getResultArray(),
        );

        $catatan = [];

        foreach ($this->susunan($nomor) as $b) {
            if ((int) $b['id'] === (int) $babak['id']) {
                continue;
            }

            foreach ($b['baris'] as $h) {
                $nilai = $h['keterangan'] ?? '';

                if ($nilai === '' && $h['prestasi'] !== null) {
                    $nilai = Prestasi::tulis((int) $h['prestasi'], (string) $nomor['jenis_hasil']) . ($h['peringkat'] !== null ? ' (ke-' . $h['peringkat'] . ')' : '');
                }

                $catatan[$h['kunci']][] = $b['nama'] . ': ' . ($nilai === '' ? 'belum diisi' : $nilai);
            }
        }

        $hasil = [];

        foreach ($this->peserta->bisaDitambah($nomor, $sudahAda) as $kunci => $p) {
            $hasil[] = $p + ['catatan' => implode(' · ', $catatan[$kunci] ?? [])];
        }

        return $hasil;
    }

    /**
     * Tambah babak. Nama wajib dan unik di nomor itu; Final paling banyak satu.
     *
     * @return array{id: int|null, galat: array<string, string>}
     */
    public function tambahBabak(int $nomorId, string $nama, bool $final): array
    {
        return $this->dalamKunciNomor($nomorId, function () use ($nomorId, $nama, $final): array {
            $nama  = self::rapikanNama($nama);
            $galat = $this->periksaBabak($nomorId, $nama, $final, null);

            if ($galat !== []) {
                return ['id' => null, 'galat' => $galat];
            }

            $urutan = (int) ($this->db->table('babak')->selectMax('urutan', 'terbesar')->where('nomor_lomba_id', $nomorId)->get()->getRowArray()['terbesar'] ?? 0) + 1;
            $waktu  = Time::now()->format('Y-m-d H:i:s');

            $this->db->table('babak')->insert([
                'nomor_lomba_id' => $nomorId,
                'nama'           => $nama,
                'final'          => $final ? 1 : 0,
                'urutan'         => $urutan,
                'dibuat_pada'    => $waktu,
                'diubah_pada'    => $waktu,
            ]);

            return ['id' => (int) $this->db->insertID(), 'galat' => []];
        });
    }

    /**
     * Ganti nama babak atau tandanya sebagai Final. Babak yang berhenti
     * menjadi Final kehilangan koreksi peringkat admin.
     *
     * @param array<string, mixed> $babak
     *
     * @return array<string, string> galat
     */
    public function ubahBabak(array $babak, string $nama, bool $final): array
    {
        $nomorId = (int) $babak['nomor_lomba_id'];

        return $this->dalamKunciNomor($nomorId, function () use ($babak, $nomorId, $nama, $final): array {
            $nama  = self::rapikanNama($nama);
            $galat = $this->periksaBabak($nomorId, $nama, $final, (int) $babak['id']);

            if ($galat !== []) {
                return $galat;
            }

            $this->db->table('babak')->where('id', (int) $babak['id'])->update([
                'nama'        => $nama,
                'final'       => $final ? 1 : 0,
                'diubah_pada' => Time::now()->format('Y-m-d H:i:s'),
            ]);

            if (! $final && (int) $babak['final'] === 1) {
                $this->db->table('hasil')->where('babak_id', (int) $babak['id'])->update(['peringkat_manual' => 0]);
                $this->hitungUlang((int) $babak['id']);
            }

            return [];
        });
    }

    public function hapusBabak(int $babakId): void
    {
        $this->db->table('babak')->where('id', $babakId)->delete();
    }

    /**
     * Tambahkan peserta ke babak. Kunci yang bukan peserta Lunas nomor itu,
     * atau yang sudah ada, dilewati.
     *
     * @param array<string, mixed> $nomor
     * @param array<string, mixed> $babak
     * @param list<string>         $kunci
     *
     * @return int jumlah peserta yang ditambahkan
     */
    public function tambahPeserta(array $nomor, array $babak, array $kunci): int
    {
        $bisa   = [];
        $jumlah = 0;

        foreach ($this->pesertaBisaDitambah($nomor, $babak) as $p) {
            $bisa[$p['kunci']] = $p;
        }

        $waktu = Time::now()->format('Y-m-d H:i:s');

        foreach (array_unique($kunci) as $k) {
            if (! isset($bisa[$k])) {
                continue;
            }

            $p = $bisa[$k];
            $this->db->table('hasil')->insert([
                'babak_id'    => (int) $babak['id'],
                'atlet_id'    => $p['atlet_id'],
                'kontingen'   => $p['atlet_id'] === null ? $p['kontingen'] : null,
                'regu'        => $p['regu'],
                'dibuat_pada' => $waktu,
                'diubah_pada' => $waktu,
            ]);
            $jumlah++;
        }

        return $jumlah;
    }

    /**
     * Periksa dan simpan isian hasil satu babak sekaligus.
     *
     * Isian per ID baris `hasil`: `prestasi` (teks), `keterangan` (kosong, DNS,
     * DNF, DQ), `peringkat` (hanya Final; kosong = otomatis), dan `keluarkan`
     * ('1' = keluarkan peserta dari babak). Bila ada yang salah, tidak ada
     * yang tersimpan.
     *
     * @param array<string, mixed>                       $nomor
     * @param array<string, mixed>                       $babak
     * @param array<int|string, array<string, mixed>>    $isian
     *
     * @return array<int, array<string, string>> galat per ID baris dan kotak; kosong bila tersimpan
     */
    public function simpanHasil(array $nomor, array $babak, array $isian): array
    {
        $jenis = (string) $nomor['jenis_hasil'];
        $final = (int) $babak['final'] === 1;
        $galat = [];
        $ubah  = [];
        $buang = [];

        $ada = array_column($this->db->table('hasil')->select('id')->where('babak_id', (int) $babak['id'])->get()->getResultArray(), 'id', 'id');

        foreach ($isian as $id => $kotak) {
            $id = (int) $id;

            if (! isset($ada[$id]) || ! is_array($kotak)) {
                continue;
            }

            if (($kotak['keluarkan'] ?? '') === '1') {
                $buang[] = $id;

                continue;
            }

            $teks       = is_scalar($kotak['prestasi'] ?? null) ? (string) $kotak['prestasi'] : '';
            $keterangan = is_scalar($kotak['keterangan'] ?? null) ? strtoupper(trim((string) $kotak['keterangan'])) : '';
            $prestasi   = Prestasi::urai($teks, $jenis);

            if ($prestasi['galat'] !== null) {
                $galat[$id]['prestasi'] = $prestasi['galat'];
            }

            if ($keterangan !== '' && ! isset(Peringkat::KETERANGAN[$keterangan])) {
                $galat[$id]['keterangan'] = 'Keterangan tidak dikenal. Pilih kosong, DNS, DNF, atau DQ.';
            }

            $peringkat = null;
            $manual    = is_scalar($kotak['peringkat'] ?? null) ? trim((string) $kotak['peringkat']) : '';

            if ($final && $manual !== '' && $keterangan === '') {
                if (preg_match('/^\d{1,3}$/D', $manual) !== 1 || (int) $manual < 1) {
                    $galat[$id]['peringkat'] = 'Peringkat harus angka 1 sampai ' . self::PERINGKAT_MAKSIMAL . ', misalnya 3. Kosongkan supaya dihitung otomatis.';
                } elseif ($prestasi['nilai'] === null) {
                    $galat[$id]['peringkat'] = 'Isi prestasinya dulu sebelum mengubah peringkat, misalnya ' . Prestasi::tulis($jenis === JenisHasil::WAKTU ? 845 : 345, $jenis) . '.';
                } else {
                    $peringkat = (int) $manual;
                }
            }

            $ubah[$id] = [
                'prestasi'         => $prestasi['nilai'],
                'keterangan'       => $keterangan === '' ? null : $keterangan,
                'peringkat_manual' => $peringkat === null ? 0 : 1,
                'peringkat'        => $peringkat,
            ];
        }

        if ($galat !== []) {
            return $galat;
        }

        $this->db->transBegin();

        try {
            $this->db->query('SELECT id FROM ' . $this->db->prefixTable('babak') . ' WHERE id = ? FOR UPDATE', [(int) $babak['id']]);
            $waktu = Time::now()->format('Y-m-d H:i:s');

            foreach ($ubah as $id => $data) {
                $this->db->table('hasil')->where('id', $id)->update($data + ['diubah_pada' => $waktu]);
            }

            if ($buang !== []) {
                $this->db->table('hasil')->whereIn('id', $buang)->delete();
            }

            $this->hitungUlang((int) $babak['id'], $jenis);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Hasil babak gagal disimpan.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return [];
    }

    /**
     * Hitung ulang peringkat otomatis satu babak; baris yang dikoreksi admin
     * dibiarkan.
     */
    public function hitungUlang(int $babakId, ?string $jenis = null): void
    {
        if ($jenis === null) {
            $jenis = (string) ($this->db->table('babak b')->select('n.jenis_hasil')->join('nomor_lomba n', 'n.id = b.nomor_lomba_id')->where('b.id', $babakId)->get()->getRowArray()['jenis_hasil'] ?? JenisHasil::WAKTU);
        }

        $baris = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babakId)->get()->getResultArray() as $h) {
            $baris[(int) $h['id']] = [
                'prestasi'   => $h['prestasi'] === null ? null : (int) $h['prestasi'],
                'keterangan' => $h['keterangan'],
                'manual'     => (int) $h['peringkat_manual'] === 1,
                'lama'       => $h['peringkat'] === null ? null : (int) $h['peringkat'],
            ];
        }

        foreach (Peringkat::hitung($baris, $jenis) as $id => $peringkat) {
            if ($baris[$id]['manual'] || $peringkat === $baris[$id]['lama']) {
                continue;
            }

            $this->db->table('hasil')->where('id', $id)->update(['peringkat' => $peringkat]);
        }
    }

    /**
     * Terbitkan hasil nomor ke halaman depan. Butuh setidaknya satu babak.
     *
     * @return string|null pesan galat, atau `null` bila terbit
     */
    public function terbitkan(int $nomorId): ?string
    {
        if ($this->db->table('babak')->where('nomor_lomba_id', $nomorId)->countAllResults() === 0) {
            return 'Belum ada babak. Tambahkan babak Final dan isi hasilnya dulu, lalu terbitkan.';
        }

        $this->db->table('nomor_lomba')->where('id', $nomorId)->update(['hasil_terbit_pada' => Time::now()->format('Y-m-d H:i:s')]);

        return null;
    }

    public function tarikKembali(int $nomorId): void
    {
        $this->db->table('nomor_lomba')->where('id', $nomorId)->update(['hasil_terbit_pada' => null]);
    }

    public static function rapikanNama(string $nama): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $nama));
    }

    /**
     * @return array<string, string>
     */
    private function periksaBabak(int $nomorId, string $nama, bool $final, ?int $kecualiId): array
    {
        $galat = [];

        if ($nama === '') {
            $galat['nama'] = 'Nama babak belum diisi. Tulis misalnya Penyisihan seri 1 atau Final.';
        } elseif (mb_strlen($nama) > self::NAMA_BABAK_MAKSIMAL) {
            $galat['nama'] = 'Nama babak terlalu panjang. Persingkat menjadi paling banyak ' . self::NAMA_BABAK_MAKSIMAL . ' huruf.';
        } else {
            $kembar = $this->db->table('babak')->where('nomor_lomba_id', $nomorId)->where('LOWER(nama)', mb_strtolower($nama));

            if ($kecualiId !== null) {
                $kembar->where('id !=', $kecualiId);
            }

            if ($kembar->countAllResults() > 0) {
                $galat['nama'] = "Babak {$nama} sudah ada di nomor ini. Pakai nama lain, misalnya tambahkan nomor serinya.";
            }
        }

        if ($final) {
            $finalLain = $this->db->table('babak')->where('nomor_lomba_id', $nomorId)->where('final', 1);

            if ($kecualiId !== null) {
                $finalLain->where('id !=', $kecualiId);
            }

            if ($finalLain->countAllResults() > 0) {
                $galat['final'] = 'Nomor ini sudah punya babak Final. Satu nomor hanya punya satu Final; hilangkan tanda Final di babak itu dulu bila perlu.';
            }
        }

        return $galat;
    }

    /**
     * Jalankan dalam transaksi dengan baris nomor lomba dikunci, supaya dua
     * admin tidak membuat dua Final bersamaan.
     *
     * @template T
     *
     * @param callable(): T $kerja
     *
     * @return T
     */
    private function dalamKunciNomor(int $nomorId, callable $kerja): mixed
    {
        $this->db->transBegin();

        try {
            $this->db->query('SELECT id FROM ' . $this->db->prefixTable('nomor_lomba') . ' WHERE id = ? FOR UPDATE', [$nomorId]);
            $hasil = $kerja();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Babak gagal disimpan.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $hasil;
    }

    /**
     * Lengkapi baris `hasil` dengan data peserta dan prestasi tertulis.
     *
     * @param array<string, mixed>                $h
     * @param array<string, array<string, mixed>> $peserta
     *
     * @return array<string, mixed>
     */
    private function lengkapi(array $h, array $peserta, string $jenis): array
    {
        $kunci = PesertaNomor::kunciHasil($h);
        $p     = $peserta[$kunci] ?? null;

        return [
            'id'               => (int) $h['id'],
            'kunci'            => $kunci,
            'atlet_id'         => $h['atlet_id'] === null ? null : (int) $h['atlet_id'],
            'kontingen'        => $p['kontingen'] ?? (string) $h['kontingen'],
            'regu'             => $h['regu'],
            'nama'             => $p['nama'] ?? 'Peserta tidak ditemukan',
            'nomor_dada'       => $p['nomor_dada'] ?? '',
            'sekolah'          => $p['sekolah'] ?? '',
            'jenjang'          => $p['jenjang'] ?? '',
            'anggota'          => $p['anggota'] ?? [],
            'prestasi'         => $h['prestasi'] === null ? null : (int) $h['prestasi'],
            'prestasi_teks'    => Prestasi::tulis($h['prestasi'] === null ? null : (int) $h['prestasi'], $jenis),
            'keterangan'       => $h['keterangan'],
            'peringkat'        => $h['peringkat'] === null ? null : (int) $h['peringkat'],
            'peringkat_manual' => (int) $h['peringkat_manual'] === 1,
        ];
    }

    /**
     * @param list<array<string, mixed>> $baris
     *
     * @return list<array<string, mixed>>
     */
    private function urutNama(array $baris): array
    {
        usort($baris, static fn (array $a, array $b): int => [$a['sekolah'], $a['nama']] <=> [$b['sekolah'], $b['nama']]);

        return $baris;
    }
}
