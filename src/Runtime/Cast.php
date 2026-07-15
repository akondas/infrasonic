<?php

declare(strict_types=1);

namespace Infrasonic\Runtime;

use Infrasonic\Runtime\Exception\BadRouteParameter;

/**
 * Casts raw string route parameters to the scalar type declared by the action.
 *
 * The set of supported types is validated at build time by the RouteCompiler,
 * so the only failure possible here is a value that does not fit its type
 * (e.g. "abc" for an int), which is a client error.
 */
final class Cast
{
    public static function scalar(string $raw, string $type, string $name): string|int|float|bool
    {
        return match ($type) {
            'string' => $raw,
            'int' => self::toInt($raw, $name),
            'float' => self::toFloat($raw, $name),
            'bool' => self::toBool($raw, $name),
            default => throw new \LogicException(\sprintf('Unsupported route parameter type "%s".', $type)),
        };
    }

    private static function toInt(string $raw, string $name): int
    {
        if (1 !== preg_match('/^-?\d+$/', $raw)) {
            throw BadRouteParameter::create($name, $raw, 'int');
        }

        return (int) $raw;
    }

    private static function toFloat(string $raw, string $name): float
    {
        if (!is_numeric($raw)) {
            throw BadRouteParameter::create($name, $raw, 'float');
        }

        return (float) $raw;
    }

    private static function toBool(string $raw, string $name): bool
    {
        return match (strtolower($raw)) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => throw BadRouteParameter::create($name, $raw, 'bool'),
        };
    }
}
