<?php

declare(strict_types=1);

if (! function_exists('require_all_in')) {
    function require_all_in(string $path): void
    {
        collect(glob($path))
            ->each(function ($path) {
                if (basename($path) !== basename(__FILE__)) {
                    require $path;
                }
            });
    }
}

require_all_in(__DIR__.'/*.php');
