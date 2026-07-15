<?php

declare(strict_types=1);

namespace Infrasonic\Runtime;

use Infrasonic\Http\Handler;
use Infrasonic\Http\Middleware;
use Infrasonic\Http\Request;
use Infrasonic\Http\Response;
use Infrasonic\Runtime\Exception\BadRouteParameter;
use Infrasonic\Runtime\Exception\HttpException;
use Infrasonic\Runtime\Route\MatchedRoute;

/**
 * The runtime heart of the framework. Booted once per worker; handle() is
 * called for every request.
 *
 * The dispatch path performs no reflection: routing is a compiled table lookup,
 * services come from the compiled container, and action arguments are resolved
 * from descriptors baked at build time.
 */
final class Kernel
{
    private readonly MiddlewarePipeline $pipeline;

    /**
     * @param list<class-string<Middleware>> $middlewareIds outermost first
     */
    public function __construct(
        private readonly CompiledRouter $router,
        private readonly Container $container,
        array $middlewareIds = [],
        private readonly bool $debug = false,
    ) {
        $middleware = [];
        foreach ($middlewareIds as $id) {
            $service = $this->container->get($id);
            if (!$service instanceof Middleware) {
                throw new \LogicException(\sprintf('Middleware "%s" must implement %s.', $id, Middleware::class));
            }
            $middleware[] = $service;
        }

        $kernel = $this;
        $core = new readonly class($kernel) implements Handler {
            public function __construct(private Kernel $kernel)
            {
            }

            public function handle(Request $request): Response
            {
                return $this->kernel->dispatch($request);
            }
        };

        $this->pipeline = new MiddlewarePipeline($middleware, $core);
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->pipeline->handle($request);
        } catch (BadRouteParameter $e) {
            return $this->clientError(400, $e->getMessage());
        } catch (HttpException $e) {
            return $this->clientError($e->status, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * @internal invoked as the terminal handler of the middleware pipeline
     */
    public function dispatch(Request $request): Response
    {
        $match = $this->router->match($request->method, $request->path);

        if (null === $match) {
            if ($this->router->pathExists($request->path)) {
                throw HttpException::methodNotAllowed($request->method, $request->path);
            }

            throw HttpException::notFound($request->path);
        }

        $controller = $this->container->get($match->controller);
        $args = $this->resolveArguments($match, $request);

        $result = $controller->{$match->action}(...$args);

        if (!$result instanceof Response) {
            throw new \LogicException(\sprintf('Action %s::%s() must return %s, got %s.', $match->controller, $match->action, Response::class, get_debug_type($result)));
        }

        return $result;
    }

    /**
     * @return list<mixed>
     */
    private function resolveArguments(MatchedRoute $match, Request $request): array
    {
        $args = [];
        foreach ($match->args as $descriptor) {
            if ('request' === $descriptor['source']) {
                $args[] = $request;

                continue;
            }

            $name = $descriptor['name'];
            if (!\array_key_exists($name, $match->params)) {
                throw new \LogicException(\sprintf('Compiled route is missing parameter "%s".', $name));
            }

            $args[] = Cast::scalar($match->params[$name], $descriptor['cast'], $name);
        }

        return $args;
    }

    private function clientError(int $status, string $message): Response
    {
        return Response::json(['status' => $status, 'error' => $message], $status);
    }

    private function serverError(\Throwable $e): Response
    {
        if ($this->debug) {
            return Response::json([
                'status' => 500,
                'error' => $e->getMessage(),
                'type' => $e::class,
                'trace' => explode("\n", $e->getTraceAsString()),
            ], 500);
        }

        return Response::json(['status' => 500, 'error' => 'Internal Server Error'], 500);
    }
}
