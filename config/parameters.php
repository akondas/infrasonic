<?php

declare(strict_types=1);

/*
 * Configuration parameters, frozen into the compiled config at build time.
 * Environment variables are read here (build time) so the runtime pays no
 * parsing cost. Use dot-notation keys.
 */
return [
    'app.name' => 'Infrasonic',
    'app.host' => getenv('APP_HOST') ?: '127.0.0.1',
    'app.port' => (int) (getenv('APP_PORT') ?: 8080),
    'app.debug' => filter_var(getenv('APP_DEBUG') ?: 'false', \FILTER_VALIDATE_BOOL),
];
