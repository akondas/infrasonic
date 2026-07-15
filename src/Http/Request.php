<?php

declare(strict_types=1);

namespace Infrasonic\Http;

/**
 * Immutable, allocation-light representation of an incoming HTTP request.
 *
 * Attributes are carried immutably: withAttribute() returns a copy so a
 * request can flow through the middleware pipeline without shared state.
 */
final class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers    header names are lower-cased
     * @param array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $headers = [],
        public readonly string $body = '',
        private readonly array $attributes = [],
    ) {
    }

    /**
     * Build a request from PHP superglobals (php-fpm / FrankenPHP worker loop).
     */
    public static function fromGlobals(): self
    {
        $method = isset($_SERVER['REQUEST_METHOD']) && \is_string($_SERVER['REQUEST_METHOD'])
            ? $_SERVER['REQUEST_METHOD']
            : 'GET';

        $uri = isset($_SERVER['REQUEST_URI']) && \is_string($_SERVER['REQUEST_URI'])
            ? $_SERVER['REQUEST_URI']
            : '/';
        $path = parse_url($uri, \PHP_URL_PATH);
        $path = \is_string($path) ? $path : '/';

        /** @var array<string, string> $query */
        $query = [];
        foreach ($_GET as $key => $value) {
            if (\is_string($key) && \is_scalar($value)) {
                $query[$key] = (string) $value;
            }
        }

        $headers = self::extractHeaders();
        $body = file_get_contents('php://input');

        return new self(
            method: strtoupper($method),
            path: '' === $path ? '/' : $path,
            query: $query,
            headers: $headers,
            body: false === $body ? '' : $body,
        );
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $attributes = $this->attributes;
        $attributes[$name] = $value;

        return new self(
            method: $this->method,
            path: $this->path,
            query: $this->query,
            headers: $this->headers,
            body: $this->body,
            attributes: $attributes,
        );
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    private static function extractHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!\is_string($key) || !\is_scalar($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            } elseif ('CONTENT_TYPE' === $key || 'CONTENT_LENGTH' === $key) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = (string) $value;
            }
        }

        return $headers;
    }
}
