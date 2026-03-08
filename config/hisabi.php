<?php

use App\Enums\Currency;
use App\Enums\Locale;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | This value is the default currency that will be used by the application.
    | You can change this value to any of the available currencies defined
    | in the App\Enums\Currency enum.
    |
    */
    'currency' => env('HISABI_DEFAULT_CURRENCY', Currency::default()->value),

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | This value is the default locale that will be used by the application.
    | You can change this value to any of the available locales defined
    | in the App\Enums\Locale enum.
    |
    */
    'default_locale' => env('HISABI_DEFAULT_LOCALE', Locale::default()->value),

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | These are the locales that are supported by the application. Users can
    | switch between these locales if they are available in the resources/lang
    | directory.
    |
    */
    'available_locales' => [
        Locale::ENGLISH->value,
        Locale::MALAY->value,
    ],
    'sms_templates' => [
        'Purchase of AED {amount} with {card} at {brand},',
        'Payment of AED {amount} to {brand} with {card}.',
        '{brand} of AED {amount} has been credited into ',
        'AED {amount} has been debited from {account} using {card} at {brand} on {date} {time}.',
        '{brand} of AED {amount} has been credited to your {account} on {date} {time}.',
        'Your {brand} of AED {amount} has been credited to your {account} on {date} {time}.',
        'Outward {brand} of AED {amount} is debited from your {account}. Your {card} as of {date} {time}.',
        'An ATM cash {brand} of AED{amount} has been debited from your {account} on {date} {time}.',
        '{brand} PAYMENT for {card} via MOBAPP of AED {amount} was debited from {date} {time}.',
        'Your Cr.Card {card} was used for AED{amount} on {date} {time} at {brand},{ignore}. {ignore}',
    ]
];
