<?php

namespace Tests\Unit\Services\Ocr;

use App\Services\Ocr\ReceiptParser;
use PHPUnit\Framework\TestCase;

class ReceiptParserTest extends TestCase
{
    private ReceiptParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ReceiptParser();
    }

    public function test_it_parses_malaysian_grocery_receipt(): void
    {
        $text = <<<TEXT
LOTUS'S AMPANG
TEL: 03-4287 8888
12/02/2024 14:30

ITEM QTY PRICE
Beras 5kg 1 25.00
Minyak Masak 2L 2 15.00
Milo 1kg 1 35.50

SUBTOTAL: 75.50
GST 6%: 4.53
TOTAL: RM 80.03
CASH: RM 100.00
CHANGE: RM 19.97

TERIMA KASIH
TEXT;

        $result = $this->parser->parse($text);

        $this->assertEquals("LOTUS'S AMPANG", $result['merchant']);
        $this->assertEquals(80.03, $result['amount']);
        $this->assertEquals('2024-02-12', $result['date']);
        $this->assertNotEmpty($result['items']);
    }

    public function test_it_parses_restaurant_receipt(): void
    {
        $text = <<<TEXT
OLDTOWN WHITE COFFEE
No. 12, Jalan SS15/4D
47500 Subang Jaya

Date: 15-03-2024
Time: 19:45

Table: A5

1x Nasi Lemak Special RM 18.90
1x White Coffee Ice RM 6.50
1x Kaya Toast RM 8.90

Subtotal RM 34.30
Service Charge 10% RM 3.43
GST 6% RM 2.26

GRAND TOTAL: RM 39.99
Payment: CASH

Thank you for dining with us!
TEXT;

        $result = $this->parser->parse($text);

        $this->assertEquals('OLDTOWN WHITE COFFEE', $result['merchant']);
        $this->assertEquals(39.99, $result['amount']);
        $this->assertEquals('2024-03-15', $result['date']);
    }

    public function test_it_parses_fuel_receipt_with_rm_format(): void
    {
        $text = <<<TEXT
PETRONAS
Jalan Universiti, PJ

Date: 20/04/2024
Time: 08:30 AM

Pump No: 03
Fuel: RON 95
Volume: 30.00 L
Price/L: RM 2.05

TOTAL AMOUNT: RM 61.50

Payment: CREDIT CARD

Thank you for choosing PETRONAS
TEXT;

        $result = $this->parser->parse($text);

        $this->assertEquals('PETRONAS', $result['merchant']);
        $this->assertEquals(61.50, $result['amount']);
        $this->assertEquals('2024-04-20', $result['date']);
    }

    public function test_it_extracts_date_in_various_formats(): void
    {
        // DD/MM/YYYY format
        $result1 = $this->parser->parse("Merchant\n12/02/2024\nTotal: RM 50.00");
        $this->assertEquals('2024-02-12', $result1['date']);

        // DD-MM-YYYY format
        $result2 = $this->parser->parse("Merchant\n15-03-2024\nTotal: RM 50.00");
        $this->assertEquals('2024-03-15', $result2['date']);

        // YYYY-MM-DD format
        $result3 = $this->parser->parse("Merchant\n2024-04-20\nTotal: RM 50.00");
        $this->assertEquals('2024-04-20', $result3['date']);

        // DD MMM YYYY format
        $result4 = $this->parser->parse("Merchant\n25 May 2024\nTotal: RM 50.00");
        $this->assertEquals('2024-05-25', $result4['date']);

        // DD MMMM YYYY format
        $result5 = $this->parser->parse("Merchant\n30 June 2024\nTotal: RM 50.00");
        $this->assertEquals('2024-06-30', $result5['date']);
    }

    public function test_it_extracts_merchant_name(): void
    {
        $text = <<<TEXT
AEON BIG
Mid Valley Megamall

Date: 01/01/2024
Total: RM 100.00
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals('AEON BIG', $result['merchant']);
    }

    public function test_it_extracts_total_amount_from_jumlah_line(): void
    {
        $text = <<<TEXT
MYDIN WHOLESALE
Jalan Tun Razak

Tarikh: 10/02/2024

Barang: RM 150.00
GST: RM 9.00
JUMLAH: RM 159.00
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals(159.00, $result['amount']);
    }

    public function test_it_extracts_total_amount_from_grand_total_line(): void
    {
        $text = <<<TEXT
GIANT HYPERMARKET
Shah Alam

Date: 20/03/2024

Items Subtotal: RM 250.00
Discount: -RM 25.00

GRAND TOTAL: RM 225.00
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals(225.00, $result['amount']);
    }

    public function test_it_returns_empty_array_for_unrecognizable_text(): void
    {
        $result = $this->parser->parse('This is just random text with no receipt information');

        $this->assertNull($result['merchant']);
        $this->assertNull($result['amount']);
        $this->assertNull($result['date']);
        $this->assertEmpty($result['items']);
    }

    public function test_it_handles_receipt_with_items_list(): void
    {
        $text = <<<TEXT
99 SPEEDMART
Taman Tun Dr Ismail

Date: 05/02/2024

Qty Item Amount
1 Susu Dutch Lady 5.50
2 Roti Gardenia 6.00
1 Telur 10.50
3 Minuman Soda 4.50

Total: RM 26.50
TEXT;

        $result = $this->parser->parse($text);

        $this->assertNotEmpty($result['items']);
        $this->assertGreaterThan(0, count($result['items']));
        $this->assertEquals(26.50, $result['amount']);
    }

    public function test_it_extracts_myr_currency(): void
    {
        $text = <<<TEXT
KFC MALAYSIA
KLCC

Date: 12/03/2024

Zinger Meal MYR 25.90
Drink MYR 4.50

TOTAL: MYR 30.40
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals(30.40, $result['amount']);
    }

    public function test_it_handles_shell_fuel_receipt(): void
    {
        $text = <<<TEXT
SHELL
Jalan Ampang

Date: 18/04/2024 10:30

Pump: 02
Product: Diesel
Quantity: 40.00 L
Unit Price: RM 2.15/L

Total: RM 86.00
Payment: Credit Card
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals('SHELL', $result['merchant']);
        $this->assertEquals(86.00, $result['amount']);
        $this->assertEquals('2024-04-18', $result['date']);
    }

    public function test_it_handles_tnb_utility_bill(): void
    {
        $text = <<<TEXT
TNB
Tenaga Nasional Berhad

Bill Date: 15/02/2024
Due Date: 05/03/2024

Account: 1234567890

Current Charges:
Usage: 450 kWh
Rate: RM 0.509/kWh

Total Amount Due: RM 229.05
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals('TNB', $result['merchant']);
        $this->assertEquals(229.05, $result['amount']);
        $this->assertEquals('2024-02-15', $result['date']);
    }

    public function test_it_prioritizes_grand_total_over_subtotal(): void
    {
        $text = <<<TEXT
STORE NAME

Date: 01/01/2024

Item 1: RM 10.00
Item 2: RM 20.00

SUBTOTAL: RM 30.00
Tax: RM 2.00
TOTAL: RM 32.00
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals(32.00, $result['amount']);
    }

    public function test_it_extracts_items_with_quantity(): void
    {
        $text = <<<TEXT
TESCO EXTRA

Date: 10/02/2024

2 x Apple Juice @ RM 5.00 = RM 10.00
1 x Bread @ RM 4.50 = RM 4.50

Total: RM 14.50
TEXT;

        $result = $this->parser->parse($text);

        $this->assertNotEmpty($result['items']);
        $this->assertEquals(14.50, $result['amount']);
    }

    public function test_it_handles_mcdonalds_receipt(): void
    {
        $text = <<<TEXT
McDONALD'S
KL Sentral

Order: 123
Date: 15/03/2024 18:30

2 McChicken RM 19.80
1 Fries L RM 5.50
2 Coke M RM 7.80

Total: RM 33.10
TEXT;

        $result = $this->parser->parse($text);
        $this->assertStringContainsString("McDONALD'S", $result['merchant'] ?? '');
        $this->assertEquals(33.10, $result['amount']);
    }

    public function test_it_cleans_merchant_name(): void
    {
        $text = <<<TEXT
LOTUS'S SDN BHD
Ampang Point

Date: 20/01/2024
Total: RM 50.00
TEXT;

        $result = $this->parser->parse($text);
        $this->assertEquals("LOTUS'S", $result['merchant']);
    }
}
