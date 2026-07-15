<?php

declare(strict_types=1);

namespace Infrasonic\Http;

/**
 * Middleware wraps the request/response flow.
 *
 * Call $next->handle($request) to continue the pipeline, or short-circuit by
 * returning a Response directly. Middleware instances are resolved from the
 * compiled container and their order is fixed at build time.
 */
interface Middleware
{
    public function process(Request $request, Handler $next): Response;
}
