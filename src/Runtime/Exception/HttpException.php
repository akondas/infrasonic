<?php

declare(strict_types=1);

namespace Infrasonic\Runtime\Exception;

/**
 * An exception carrying an explicit HTTP status. The message is safe to expose
 * to clients.
 */
final class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $path): self
    {
        return new self(404, \sprintf('No route matches "%s".', $path));
    }

    public static function methodNotAllowed(string $method, string $path): self
    {
        return new self(405, \sprintf('Method "%s" is not allowed for "%s".', $method, $path));
    }
}
