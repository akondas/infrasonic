<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Architecture;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The runtime path must never touch reflection or the compiler. This is what
 * keeps request handling fast, so it is enforced as a test rather than left to
 * convention.
 */
#[CoversNothing]
final class RuntimeIsolationTest extends TestCase
{
    private const array RUNTIME_DIRS = ['src/Runtime', 'src/Http'];

    public function testRuntimeReferencesNoReflectionOrCompiler(): void
    {
        $offenders = [];

        foreach ($this->runtimeFiles() as $file) {
            foreach ($this->identifiers($file) as $identifier) {
                if (str_contains($identifier, 'Reflection') || str_starts_with(ltrim($identifier, '\\'), 'Infrasonic\\Compiler')) {
                    $offenders[] = basename($file).' → '.$identifier;
                }
            }
        }

        self::assertSame([], $offenders, 'Runtime code must not use reflection or the compiler.');
    }

    /**
     * @return list<string>
     */
    private function runtimeFiles(): array
    {
        $root = \dirname(__DIR__, 2);
        $files = [];

        foreach (self::RUNTIME_DIRS as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root.'/'.$dir, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                \assert($file instanceof \SplFileInfo);
                if ($file->isFile() && 'php' === $file->getExtension()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        self::assertNotEmpty($files);

        return $files;
    }

    /**
     * Returns identifier tokens only, so words inside doc comments (e.g. the
     * phrase "no reflection") never trigger a false positive.
     *
     * @return list<string>
     */
    private function identifiers(string $file): array
    {
        $code = (string) file_get_contents($file);
        $identifiers = [];

        foreach (token_get_all($code) as $token) {
            if (\is_array($token) && \in_array($token[0], [\T_STRING, \T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED], true)) {
                $identifiers[] = $token[1];
            }
        }

        return $identifiers;
    }
}
