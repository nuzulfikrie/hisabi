<?php

declare(strict_types=1);

use App\Enums\Currency;

describe('Currency Enum', function (): void {
    test('has MYR currency', function (): void {
        $currency = Currency::MYR;

        expect($currency->value)->toBe('MYR');
    });

    test('has AED currency', function (): void {
        $currency = Currency::AED;

        expect($currency->value)->toBe('AED');
    });

    test('has USD currency', function (): void {
        $currency = Currency::USD;

        expect($currency->value)->toBe('USD');
    });

    test('has SGD currency', function (): void {
        $currency = Currency::SGD;

        expect($currency->value)->toBe('SGD');
    });

    test('default currency is MYR', function (): void {
        $default = Currency::default();

        expect($default)->toBe(Currency::MYR);
        expect($default->value)->toBe('MYR');
    });

    test('returns all currency values', function (): void {
        $values = Currency::values();

        expect($values)->toBe(['MYR', 'AED', 'USD', 'SGD']);
    });

    test('returns correct display names', function (): void {
        expect(Currency::MYR->displayName())->toBe('Malaysian Ringgit')
            ->and(Currency::AED->displayName())->toBe('UAE Dirham')
            ->and(Currency::USD->displayName())->toBe('US Dollar')
            ->and(Currency::SGD->displayName())->toBe('Singapore Dollar');
    });

    test('returns correct symbols', function (): void {
        expect(Currency::MYR->symbol())->toBe('RM')
            ->and(Currency::AED->symbol())->toBe('AED')
            ->and(Currency::USD->symbol())->toBe('$')
            ->and(Currency::SGD->symbol())->toBe('S$');
    });

    test('returns two decimal places for all currencies', function (): void {
        expect(Currency::MYR->decimalPlaces())->toBe(2)
            ->and(Currency::AED->decimalPlaces())->toBe(2)
            ->and(Currency::USD->decimalPlaces())->toBe(2)
            ->and(Currency::SGD->decimalPlaces())->toBe(2);
    });

    test('can be created from string value', function (): void {
        $currency = Currency::tryFrom('MYR');

        expect($currency)->toBe(Currency::MYR);
    });

    test('returns null for invalid currency string', function (): void {
        $currency = Currency::tryFrom('INVALID');

        expect($currency)->toBeNull();
    });
});
