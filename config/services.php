<?php

declare(strict_types=1);

use App\Clock\Clock;
use App\Clock\SystemClock;
use App\Middleware\RequestTimer;

return [
    // Directories scanned for #[Service] classes and #[Route] controllers.
    'scan' => ['app'],

    // Explicit interface => implementation bindings. Single-implementation
    // interfaces are auto-wired, so this is only needed to disambiguate.
    'bindings' => [
        Clock::class => SystemClock::class,
    ],

    // Middleware pipeline, outermost first.
    'middleware' => [
        RequestTimer::class,
    ],
];
