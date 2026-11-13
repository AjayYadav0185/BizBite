<?php

namespace Tests\Unit;

use App\Services\OrderReceipt;
use App\Support\India;
use PHPUnit\Framework\TestCase;

class IndiaTest extends TestCase
{
    public function test_indian_digit_grouping(): void
    {
        $this->assertSame('0.00', India::number(0));
        $this->assertSame('999.00', India::number(999));
        $this->assertSame('1,000.00', India::number(1000));
        $this->assertSame('12,34,567.50', India::number('1234567.5'));
        $this->assertSame('1,00,00,000', India::number(10000000, 0));
        $this->assertSame('-₹450.00', India::money(-450));
    }

    public function test_amount_in_words(): void
    {
        $this->assertSame('Rupees Zero Only', India::words(0));
        $this->assertSame('Rupees Five Hundred Fifty Only', India::words(550));
        $this->assertSame('Rupees One Thousand Two Hundred Ten and Fifty Paise Only', India::words('1210.50'));
        $this->assertSame('Rupees Two Lakh Fifteen Thousand Only', India::words(215000));
        $this->assertSame('Rupees One Crore Five Lakh Only', India::words(10500000));
    }

    public function test_gstin_validation_checks_format_state_and_check_digit(): void
    {
        $this->assertTrue(India::isValidGstin('27AAPFU0939F1ZV'));
        $this->assertTrue(India::isValidGstin('07abcde1234f1z2'));   // case-insensitive
        $this->assertFalse(India::isValidGstin('27AAPFU0939F1ZA'));  // wrong check digit
        $this->assertFalse(India::isValidGstin('99AAPFU0939F1ZV'));  // no such state
        $this->assertFalse(India::isValidGstin('27AAPFU0939F1Z'));   // too short
        $this->assertFalse(India::isValidGstin(null));
        $this->assertSame('Delhi', India::stateFromGstin('07ABCDE1234F1Z2'));
        $this->assertSame('Haryana', India::stateFromGstin('06ABCDE1234F1Z2'));
    }

    public function test_mobile_normalisation(): void
    {
        $this->assertSame('9876543210', India::normaliseMobile('+91 98765-43210'));
        $this->assertSame('9876543210', India::normaliseMobile('09876543210'));
        $this->assertNull(India::normaliseMobile('12345'));
        $this->assertNull(India::normaliseMobile('5876543210'));  // Indian mobiles start 6-9
    }

    public function test_upi_link_keeps_vpa_readable_and_fixes_amount(): void
    {
        $link = India::upiLink('shop@okaxis', 'Apna Zaika', 345, 'Bill 7');

        $this->assertStringStartsWith('upi://pay?pa=shop@okaxis&', $link);
        $this->assertStringContainsString('am=345.00', $link);
        $this->assertStringContainsString('cu=INR', $link);
    }

    public function test_tax_breakup_matches_the_stored_tax_exactly(): void
    {
        // ₹105 @5% (tax 5.00) + ₹118 @18% (tax 18.00), ₹23 discount → factor 0.9
        $items = [
            ['subtotal' => '105.00', 'gst_rate' => 5, 'gst_amount' => '5.00'],
            ['subtotal' => '118.00', 'gst_rate' => 18, 'gst_amount' => '18.00'],
        ];
        $tax = '20.63'; // what OrderService stores: 23.00 × 200/223

        $rows = OrderReceipt::taxBreakup($items, '223.00', '23.00', $tax);

        $this->assertCount(2, $rows);
        $this->assertSame([5, 18], array_column($rows, 'rate'));
        $sum = array_reduce($rows, fn ($c, $r) => bcadd($c, bcadd($r['cgst'], $r['sgst'], 2), 2), '0');
        $this->assertSame($tax, $sum);
        foreach ($rows as $row) {
            $this->assertSame($row['tax'], bcadd($row['cgst'], $row['sgst'], 2));
        }
    }

    public function test_tax_breakup_is_empty_for_zero_rated_bills(): void
    {
        $this->assertSame([], OrderReceipt::taxBreakup([['subtotal' => '50.00', 'gst_rate' => 0]], '50.00', '0', '0.00'));
    }
}
