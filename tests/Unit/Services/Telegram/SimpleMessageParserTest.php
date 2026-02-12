<?php

namespace Tests\Unit\Services\Telegram;

use App\Services\Telegram\SimpleMessageParser;
use PHPUnit\Framework\TestCase;

class SimpleMessageParserTest extends TestCase
{
    private SimpleMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SimpleMessageParser();
    }

    public function test_parses_expense_format()
    {
        $result = $this->parser->parse('expense 50 lunch at restaurant');

        $this->assertEquals(50.0, $result['amount']);
        $this->assertEquals('lunch at restaurant', $result['brand_name']);
        $this->assertEquals('EXPENSES', $result['category_type']);
        $this->assertEquals('expense 50 lunch at restaurant', $result['raw_message']);
    }

    public function test_parses_income_format()
    {
        $result = $this->parser->parse('income 1000 salary');

        $this->assertEquals(1000.0, $result['amount']);
        $this->assertEquals('salary', $result['brand_name']);
        $this->assertEquals('INCOME', $result['category_type']);
    }

    public function test_parses_minus_sign_format()
    {
        $result = $this->parser->parse('-50 groceries');

        $this->assertEquals(50.0, $result['amount']);
        $this->assertEquals('groceries', $result['brand_name']);
        $this->assertEquals('EXPENSES', $result['category_type']);
    }

    public function test_parses_plus_sign_format()
    {
        $result = $this->parser->parse('+500 freelance work');

        $this->assertEquals(500.0, $result['amount']);
        $this->assertEquals('freelance work', $result['brand_name']);
        $this->assertEquals('INCOME', $result['category_type']);
    }

    public function test_returns_false_for_unparseable_messages()
    {
        $this->assertFalse($this->parser->canParse('hello world'));
        $this->assertFalse($this->parser->canParse(''));
        $this->assertFalse($this->parser->canParse('just text without numbers'));
    }

    public function test_handles_decimal_amounts()
    {
        $result = $this->parser->parse('expense 45.50 coffee');

        $this->assertEquals(45.5, $result['amount']);
        $this->assertEquals('coffee', $result['brand_name']);
    }

    public function test_handles_decimal_amounts_with_two_decimals()
    {
        $result = $this->parser->parse('expense 1234.56 monthly rent');

        $this->assertEquals(1234.56, $result['amount']);
        $this->assertEquals('monthly rent', $result['brand_name']);
    }

    public function test_handles_amount_at_different_positions()
    {
        $result = $this->parser->parse('spent 25.00 on transport');

        $this->assertEquals(25.0, $result['amount']);
    }

    public function test_defaults_to_expense_when_no_type_specified()
    {
        $result = $this->parser->parse('25 taxi');

        $this->assertEquals(25.0, $result['amount']);
        $this->assertEquals('EXPENSES', $result['category_type']);
    }

    public function test_trims_whitespace_from_description()
    {
        $result = $this->parser->parse('expense  100   dinner   with   friends  ');

        $this->assertEquals(100.0, $result['amount']);
        $this->assertEquals('dinner   with   friends', $result['brand_name']);
    }

    public function test_uses_telegram_as_default_brand_when_empty()
    {
        $result = $this->parser->parse('expense 100');

        $this->assertEquals(100.0, $result['amount']);
        $this->assertEquals('Telegram', $result['brand_name']);
    }

    public function test_can_parse_returns_true_for_valid_messages()
    {
        $this->assertTrue($this->parser->canParse('expense 50 lunch'));
        $this->assertTrue($this->parser->canParse('income 1000 salary'));
        $this->assertTrue($this->parser->canParse('-25 coffee'));
        $this->assertTrue($this->parser->canParse('+500 freelance'));
        $this->assertTrue($this->parser->canParse('100 random expense'));
    }

    public function test_handles_large_amounts()
    {
        $result = $this->parser->parse('income 1000000 jackpot');

        $this->assertEquals(1000000.0, $result['amount']);
    }

    public function test_handles_amount_with_currency_symbol_ignored()
    {
        // The parser extracts the first number it finds
        $result = $this->parser->parse('expense RM50 lunch');

        $this->assertEquals(50.0, $result['amount']);
    }
}
