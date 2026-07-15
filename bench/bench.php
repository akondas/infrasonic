<?php

declare(strict_types=1);

/*
 * In-process micro-benchmark of the compiled kernel.
 *
 * This measures the framework's own per-request overhead (routing, container
 * resolution, middleware, response building) with the network and SAPI removed,
 * exactly the work that repeats inside a FrankenPHP worker loop.
 *
 * Usage:
 *   php bin/infra build
 *   php bench/bench.php [iterations]
 */

use Infrasonic\Http\Request;
use Infrasonic\Runtime\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$bootstrap = dirname(__DIR__).'/var/compiled/bootstrap.php';
if (!is_file($bootstrap)) {
    fwrite(\STDERR, "Not compiled. Run: php bin/infra build\n");
    exit(1);
}

/** @var Kernel $kernel */
$kernel = require $bootstrap;

$iterations = isset($argv[1]) ? max(1, (int) $argv[1]) : 100_000;

$requests = [
    new Request('GET', '/'),
    new Request('GET', '/hello/Ada'),
    new Request('GET', '/add/20/22'),
];

// Warm up caches (route tables, singletons).
foreach ($requests as $request) {
    $kernel->handle($request);
}

$start = hrtime(true);
for ($i = 0; $i < $iterations; ++$i) {
    $kernel->handle($requests[$i % 3]);
}
$elapsed = (hrtime(true) - $start) / 1_000_000_000;

$perSecond = $iterations / $elapsed;
$microsEach = ($elapsed / $iterations) * 1_000_000;

printf("Infrasonic in-process benchmark\n");
printf("  iterations : %s\n", number_format($iterations));
printf("  total time : %.3f s\n", $elapsed);
printf("  throughput : %s req/s\n", number_format($perSecond));
printf("  latency    : %.2f µs/req\n", $microsEach);
printf("  peak memory: %.2f MB\n", memory_get_peak_usage(true) / 1_048_576);
