<?php

declare(strict_types=1);

use App\Enums\Locale;

describe('Locale Enum', function (): void {
    test('has english locale', function (): void {
        $locale = Locale::ENGLISH;

        expect($locale->value)->toBe('en');
    });

    test('has malay locale', function (): void {
        $locale = Locale::MALAY;

        expect($locale->value)->toBe('ms');
    });

    test('default locale is malay', function (): void {
        $default = Locale::default();

        expect($default)->toBe(Locale::MALAY);
        expect($default->value)->toBe('ms');
    });

    test('returns all locale values', function (): void {
        $values = Locale::values();

        expect($values)->toBe(['en', 'ms']);
    });

    test('returns correct display names', function (): void {
        expect(Locale::ENGLISH->displayName())->toBe('English')
            ->and(Locale::MALAY->displayName())->toBe('Bahasa Malaysia');
    });

    test('malay locale is not rtl', function (): void {
        expect(Locale::MALAY->isRtl())->toBeFalse();
    });

    test('english locale is not rtl', function (): void {
        expect(Locale::ENGLISH->isRtl())->toBeFalse();
    });

    test('can be created from string value', function (): void {
        $locale = Locale::tryFrom('en');

        expect($locale)->toBe(Locale::ENGLISH);
    });

    test('returns null for invalid locale string', function (): void {
        $locale = Locale::tryFrom('invalid');

        expect($locale)->toBeNull();
    });
});
