<?php

declare(strict_types=1);

namespace Infrasonic\Runtime;

use Infrasonic\Http\Handler;
use Infrasonic\Http\Middleware;
use Infrasonic\Http\Request;
use Infrasonic\Http\Response;

/**
 * Composes an ordered list of middleware around a terminal handler.
 *
 * The handler chain is nested once at construction. Because the middleware set
 * and the terminal handler are fixed for the lifetime of the worker, no
 * per-request allocation is needed to dispatch through the pipeline.
 */
final class MiddlewarePipeline implements Handler
{
    private readonly Handler $entry;

    /**
     * @param list<Middleware> $middleware outermost first
     */
    public function __construct(array $middleware, Handler $core)
    {
        $entry = $core;
        foreach (array_reverse($middleware) as $mw) {
            $entry = new readonly class($mw, $entry) implements Handler {
                public function __construct(private Middleware $mw, private Handler $next)
                {
                }

                public function handle(Request $request): Response
                {
                    return $this->mw->process($request, $this->next);
                }
            };
        }

        $this->entry = $entry;
    }

    public function handle(Request $request): Response
    {
        return $this->entry->handle($request);
    }
}
