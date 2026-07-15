<?php

declare(strict_types=1);

namespace Infrasonic\Http;

/**
 * Terminal request handler: turns a request into a response.
 */
interface Handler
{
    public function handle(Request $request): Response;
}
