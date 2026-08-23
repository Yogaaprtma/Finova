<?php

namespace Tests\Unit;

use App\Services\Ai\Normalizers\AmountNormalizer;
use PHPUnit\Framework\TestCase;

class AmountNormalizerTest extends TestCase
{
    public function test_normalizes_rb_and_ribu(): void
    {
        $this->assertEquals(15000, AmountNormalizer::extract('beli nasi goreng 15rb'));
        $this->assertEquals(15000, AmountNormalizer::extract('bayar parkir 15ribu'));
        $this->assertEquals(500000, AmountNormalizer::extract('baju 500rb'));
    }

    public function test_normalizes_k(): void
    {
        $this->assertEquals(15000, AmountNormalizer::extract('kopi 15k'));
        $this->assertEquals(50000, AmountNormalizer::extract('makan 50k'));
    }

    public function test_normalizes_jt_and_juta(): void
    {
        $this->assertEquals(2000000, AmountNormalizer::extract('gajian 2jt'));
        $this->assertEquals(2000000, AmountNormalizer::extract('gaji 2juta'));
        $this->assertEquals(1500000, AmountNormalizer::extract('cicilan motor 1.5jt'));
        $this->assertEquals(1500000, AmountNormalizer::extract('sewa kontrakan 1,5juta'));
        $this->assertEquals(8000000, AmountNormalizer::extract('gajian 8jt'));
    }

    public function test_normalizes_thousand_separator_dots(): void
    {
        $this->assertEquals(2000000, AmountNormalizer::extract('beli hp 2.000.000'));
        $this->assertEquals(150000, AmountNormalizer::extract('tagihan listrik Rp 150.000'));
    }

    public function test_normalizes_thousand_separator_commas(): void
    {
        $this->assertEquals(2000000, AmountNormalizer::extract('2,000,000'));
        $this->assertEquals(75000, AmountNormalizer::extract('Rp 75,000'));
    }

    public function test_normalizes_special_indonesian_phrases(): void
    {
        $this->assertEquals(1000000, AmountNormalizer::extract('dapat bonus sejuta'));
        $this->assertEquals(1000000, AmountNormalizer::extract('se-juta'));
        $this->assertEquals(500000, AmountNormalizer::extract('setengah juta'));
        $this->assertEquals(100000, AmountNormalizer::extract('seratus ribu'));
        $this->assertEquals(10000, AmountNormalizer::extract('sepuluh ribu'));
    }

    public function test_normalizes_indonesian_words(): void
    {
        $this->assertEquals(200000, AmountNormalizer::extract('dua ratus ribu'));
        $this->assertEquals(50000, AmountNormalizer::extract('lima puluh ribu'));
    }

    public function test_returns_null_for_text_without_amount(): void
    {
        $this->assertNull(AmountNormalizer::extract('halo apa kabar'));
        $this->assertNull(AmountNormalizer::extract(''));
    }
}
