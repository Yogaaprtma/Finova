<?php

namespace Tests\Unit;

use App\Services\Ai\Adapters\LocalRegexFallbackAdapter;
use PHPUnit\Framework\TestCase;

class AiParserServiceTest extends TestCase
{
    protected LocalRegexFallbackAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new LocalRegexFallbackAdapter;
    }

    public function test_parses_simple_expense_transaction(): void
    {
        $result = $this->adapter->parseTransaction('beli nasi goreng 15rb');

        $this->assertEquals('expense', $result->transactionType);
        $this->assertEquals(15000, $result->amount);
        $this->assertEquals('food', $result->categorySlug);
        $this->assertTrue($result->confidence >= 0.85);
        $this->assertTrue($result->isHighConfidence());
    }

    public function test_parses_income_transaction(): void
    {
        $result = $this->adapter->parseTransaction('gajian 8jt');

        $this->assertEquals('income', $result->transactionType);
        $this->assertEquals(8000000, $result->amount);
        $this->assertEquals('salary', $result->categorySlug);
        $this->assertTrue($result->confidence >= 0.85);
    }

    public function test_parses_transfer_transaction(): void
    {
        $result = $this->adapter->parseTransaction('transfer bca ke mandiri 1jt');

        $this->assertEquals('transfer', $result->transactionType);
        $this->assertEquals(1000000, $result->amount);
        $this->assertEquals('bca', $result->fromAccountHint);
        $this->assertEquals('mandiri', $result->toAccountHint);
        $this->assertTrue($result->confidence >= 0.85);
    }

    public function test_parses_ewallet_topup(): void
    {
        $result = $this->adapter->parseTransaction('isi gopay 200rb');

        $this->assertEquals('transfer', $result->transactionType);
        $this->assertEquals(200000, $result->amount);
        $this->assertEquals('gopay', $result->toAccountHint);
    }

    public function test_handles_empty_or_non_financial_message(): void
    {
        $emptyResult = $this->adapter->parseTransaction('');
        $this->assertEquals(0.0, $emptyResult->confidence);
        $this->assertTrue($emptyResult->isLowConfidence());

        $nonFinResult = $this->adapter->parseTransaction('halo');
        $this->assertNull($nonFinResult->amount);
        $this->assertTrue($nonFinResult->isLowConfidence());
    }
}
