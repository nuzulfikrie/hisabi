<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

describe('SetLocale Middleware', function (): void {
    beforeEach(function (): void {
        $this->middleware = new SetLocale();
        // Set a non-default locale first to ensure the middleware actually changes it
        app()->setLocale('en');
    });

    afterEach(function (): void {
        // Reset locale to default after each test
        app()->setLocale(Locale::default()->value);
    });

    test('sets default locale when no preference provided', function (): void {
        $request = Request::create('/');

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        // Should set locale to Malay (default) as defined in config
        expect(app()->getLocale())->toBeIn(Locale::values());
    });

    test('sets locale from session when available', function (): void {
        $request = Request::create('/');
        $request->setLaravelSession(app('session')->driver('array'));
        $request->session()->put('locale', Locale::ENGLISH->value);

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        expect(app()->getLocale())->toBe(Locale::ENGLISH->value);
    });

    test('sets locale from accept-language header', function (): void {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        expect(app()->getLocale())->toBe(Locale::ENGLISH->value);
    });

    test('falls back to default when session locale is invalid', function (): void {
        $request = Request::create('/');
        $request->setLaravelSession(app('session')->driver('array'));
        $request->session()->put('locale', 'invalid');

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        // Should fall back to a valid locale from the config
        expect(app()->getLocale())->toBeIn(Locale::values());
    });

    test('falls back to default when header locale is not available', function (): void {
        config()->set('hisabi.available_locales', [Locale::MALAY->value]);
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9');

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        expect(app()->getLocale())->toBe(Locale::MALAY->value);
        
        // Reset config
        config()->set('hisabi.available_locales', Locale::values());
    });

    test('malay language code from header resolves to malay locale', function (): void {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ms-MY,ms;q=0.9');

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        expect(app()->getLocale())->toBe(Locale::MALAY->value);
    });

    test('base language code is used when full locale not available', function (): void {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'en-GB,en;q=0.9');

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        expect(app()->getLocale())->toBe(Locale::ENGLISH->value);
    });

    test('session takes precedence over header', function (): void {
        $request = Request::create('/');
        $request->setLaravelSession(app('session')->driver('array'));
        $request->session()->put('locale', Locale::MALAY->value);
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $next = function ($req): Response {
            return new Response('OK');
        };

        $this->middleware->handle($request, $next);

        expect(app()->getLocale())->toBe(Locale::MALAY->value);
    });
});
