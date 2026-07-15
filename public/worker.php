<?php

declare(strict_types=1);

use Infrasonic\Http\Request;
use Infrasonic\Runtime\Kernel;

/*
 * FrankenPHP worker entry point.
 *
 * The application is booted ONCE below. FrankenPHP then hands us requests in a
 * loop; each request only builds a Request, dispatches through the already-warm
 * Kernel, and emits the Response. No re-boot, no re-compile, no reflection.
 */

ignore_user_abort(true);

require dirname(__DIR__).'/vendor/autoload.php';

/** @var Kernel $kernel */
$kernel = require dirname(__DIR__).'/var/compiled/bootstrap.php';

$handler = static function () use ($kernel): void {
    $kernel->handle(Request::fromGlobals())->send();
};

// Serve requests until FrankenPHP asks the worker to shut down.
$running = true;
while ($running) {
    $running = frankenphp_handle_request($handler);
    gc_collect_cycles();
}
