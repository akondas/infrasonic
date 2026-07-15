<?php

declare(strict_types=1);

namespace App\Middleware;

use Infrasonic\Http\Attribute\Service;
use Infrasonic\Http\Handler;
use Infrasonic\Http\Middleware;
use Infrasonic\Http\Request;
use Infrasonic\Http\Response;

/**
 * Measures handling time and stamps identifying headers on every response.
 */
#[Service]
final class RequestTimer implements Middleware
{
    public function process(Request $request, Handler $next): Response
    {
        $start = hrtime(true);

        $response = $next->handle($request);

        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        return $response
            ->withHeader('X-Response-Time', \sprintf('%.3fms', $elapsedMs))
            ->withHeader('X-Powered-By', 'Infrasonic');
    }
}
