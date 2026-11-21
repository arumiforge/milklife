<?php

use App\Libraries\Bantuan\FaqBawaan;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\UjiBasisData;

/**
 * Perintah `php spark faq:awal` (F9-04): tanya-jawab bawaan untuk server
 * yang dipasang sebelum ada halaman Bantuan.
 *
 * @internal
 */
final class FaqAwalTest extends UjiBasisData
{
    use StreamFilterTrait;

    public function testMemasangBawaanBilaKosong(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('faq:awal', []));

        $jumlah = count(FaqBawaan::DAFTAR);
        $this->seeNumRecords($jumlah, 'faq', []);
        $this->assertStringContainsString($jumlah . ' tanya-jawab bawaan dipasang.', $this->getStreamFilterBuffer());

        $pertama = $this->db->table('faq')->where('kategori', 'sebelum')->orderBy('urutan')->get()->getRowArray();
        $this->assertSame(FaqBawaan::DAFTAR[0][1], $pertama['pertanyaan']);
        $this->assertSame('1', (string) $pertama['urutan']);
        $this->assertNotNull($pertama['dibuat_pada']);
    }

    public function testTidakMenimpaIsianAdmin(): void
    {
        $this->db->table('faq')->insert(['kategori' => 'hasil', 'pertanyaan' => 'Pertanyaan admin?', 'jawaban' => 'Jawaban admin.', 'urutan' => 3, 'aktif' => 0]);

        $this->assertSame(EXIT_SUCCESS, service('commands')->run('faq:awal', []));

        $this->seeNumRecords(1, 'faq', []);
        $this->seeInDatabase('faq', ['pertanyaan' => 'Pertanyaan admin?', 'aktif' => 0]);
        $this->assertStringContainsString('sudah ada, jadi tidak ada yang diubah', $this->getStreamFilterBuffer());
    }
}
