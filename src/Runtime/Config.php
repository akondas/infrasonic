<?php

declare(strict_types=1);

namespace Infrasonic\Runtime;

/**
 * Read-only access to configuration frozen at build time.
 *
 * The compiled config is a plain PHP array (OPcache keeps it resident, so
 * there is no parse cost at runtime). Values are addressed by dot-notation
 * keys, e.g. Config::int('app.port').
 */
final class Config
{
    /** @var array<string, scalar|null> */
    private static array $values = [];

    /**
     * @param array<string, scalar|null> $values
     */
    public static function init(array $values): void
    {
        self::$values = $values;
    }

    public static function has(string $key): bool
    {
        return \array_key_exists($key, self::$values);
    }

    public static function string(string $key, ?string $default = null): string
    {
        $value = self::$values[$key] ?? $default;
        if (null === $value) {
            throw new \RuntimeException(\sprintf('Missing string config key "%s".', $key));
        }

        return (string) $value;
    }

    public static function int(string $key, ?int $default = null): int
    {
        $value = self::$values[$key] ?? $default;
        if (null === $value) {
            throw new \RuntimeException(\sprintf('Missing int config key "%s".', $key));
        }

        return (int) $value;
    }

    public static function bool(string $key, ?bool $default = null): bool
    {
        $value = self::$values[$key] ?? $default;
        if (null === $value) {
            throw new \RuntimeException(\sprintf('Missing bool config key "%s".', $key));
        }

        return (bool) $value;
    }

    /**
     * @return array<string, scalar|null>
     */
    public static function all(): array
    {
        return self::$values;
    }
}
