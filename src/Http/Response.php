<?php

declare(strict_types=1);

namespace Infrasonic\Http;

/**
 * Immutable HTTP response value object.
 */
final class Response
{
    /**
     * @param array<string, string> $headers header names are stored as given
     */
    public function __construct(
        public readonly int $status = 200,
        public readonly string $body = '',
        public readonly array $headers = [],
    ) {
    }

    /**
     * @param array<array-key, mixed>|\JsonSerializable $data
     */
    public static function json(array|\JsonSerializable $data, int $status = 200): self
    {
        $encoded = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        return new self(
            status: $status,
            body: $encoded,
            headers: ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    public static function text(string $text, int $status = 200): self
    {
        return new self(
            status: $status,
            body: $text,
            headers: ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self(
            status: $status,
            body: $html,
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }

    public static function noContent(int $status = 204): self
    {
        return new self(status: $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self($this->status, $this->body, $headers);
    }

    public function withStatus(int $status): self
    {
        return new self($status, $this->body, $this->headers);
    }

    /**
     * Emit the response to the SAPI (headers + body).
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name.': '.$value, true);
            }
        }

        echo $this->body;
    }
}
