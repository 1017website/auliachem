<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/helpers.php';

class NumberFormattingTest extends TestCase
{
    public function test_quantities_keep_significant_decimals_without_padding(): void
    {
        $this->assertSame('350', format_number(350));
        $this->assertSame('1.000,125', format_number(1000.125));
        $this->assertSame('1,000.125', format_number(1000.125, 0, 'en'));
        $this->assertSame('0,001', format_number(0.001));
        $this->assertSame('-1.234,5', format_number(-1234.5));
    }

    public function test_money_uses_document_language_and_preserves_precision(): void
    {
        $this->assertSame('Rp 4.550,00', idr(4550));
        $this->assertSame('USD 4.550,00', format_money(4550, 'USD'));
        $this->assertSame('USD 4,550.00', format_money(4550, 'USD', 'en'));
        $this->assertSame('Rp 4,550.00', format_money(4550, 'IDR', 'en'));
        $this->assertSame('Rp 1.000,125', idr(1000.125));
        $this->assertSame('Rp 0,00', idr(null));
        $this->assertSame('', idr_input(null));
        $this->assertSame('1.234,50', idr_input(1234.5));
        $this->assertSame('Rp 1,5 Jt', idrm(1500000));
    }
}
