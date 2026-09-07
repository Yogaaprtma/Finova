<?php

namespace Tests\Unit;

use App\Services\Messaging\PhoneNumberNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNumberNormalizerTest extends TestCase
{
    public function test_normalizes_indonesian_phone_starting_with_08(): void
    {
        $this->assertSame('6281234567890', PhoneNumberNormalizer::normalize('081234567890'));
    }

    public function test_normalizes_indonesian_phone_with_plus_sign(): void
    {
        $this->assertSame('6281234567890', PhoneNumberNormalizer::normalize('+6281234567890'));
    }

    public function test_normalizes_indonesian_phone_with_spaces_and_dashes(): void
    {
        $this->assertSame('6281234567890', PhoneNumberNormalizer::normalize('0812-3456-7890'));
        $this->assertSame('6281234567890', PhoneNumberNormalizer::normalize('+62 812-3456-7890'));
    }

    public function test_normalizes_number_starting_with_8(): void
    {
        $this->assertSame('6281234567890', PhoneNumberNormalizer::normalize('81234567890'));
    }

    public function test_validates_correct_indonesian_mobile_numbers(): void
    {
        $this->assertTrue(PhoneNumberNormalizer::isValid('081234567890'));
        $this->assertTrue(PhoneNumberNormalizer::isValid('+6281234567890'));
        $this->assertTrue(PhoneNumberNormalizer::isValid('6281234567890'));
        $this->assertTrue(PhoneNumberNormalizer::isValid('085712345678'));

        $this->assertFalse(PhoneNumberNormalizer::isValid('0217654321')); // Landline
        $this->assertFalse(PhoneNumberNormalizer::isValid('12345')); // Too short
        $this->assertFalse(PhoneNumberNormalizer::isValid(''));
    }
}

