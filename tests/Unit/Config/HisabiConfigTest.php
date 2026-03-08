<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\Locale;
use Tests\TestCase;

uses(TestCase::class);

describe('Hisabi Config', function (): void {
    test('default locale is set to malay', function (): void {
        $defaultLocale = config('hisabi.default_locale');

        expect($defaultLocale)->toBe(Locale::MALAY->value);
    });

    test('default currency is set to MYR', function (): void {
        $currency = config('hisabi.currency');

        expect($currency)->toBe(Currency::MYR->value);
    });

    test('available locales includes english and malay', function (): void {
        $availableLocales = config('hisabi.available_locales');

        expect($availableLocales)->toContain(Locale::ENGLISH->value)
            ->toContain(Locale::MALAY->value);
    });

    test('default locale can be overridden via config', function (): void {
        // Store original value
        $originalValue = config('hisabi.default_locale');

        // Override config
        config()->set('hisabi.default_locale', Locale::ENGLISH->value);

        expect(config('hisabi.default_locale'))->toBe(Locale::ENGLISH->value);

        // Restore original
        config()->set('hisabi.default_locale', $originalValue);
    });

    test('default currency can be overridden via config', function (): void {
        // Store original value
        $originalValue = config('hisabi.currency');

        // Override config
        config()->set('hisabi.currency', Currency::USD->value);

        expect(config('hisabi.currency'))->toBe(Currency::USD->value);

        // Restore original
        config()->set('hisabi.currency', $originalValue);
    });
});
