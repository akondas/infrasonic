<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

/**
 * Finds concrete class names declared under a set of directories.
 *
 * Uses the tokenizer (not `include` + get_declared_classes) so scanning is
 * side-effect free and deterministic. Interfaces, traits, enums and abstract
 * classes are ignored — only instantiable classes can be services.
 */
final class ClassFinder
{
    /**
     * @param list<string> $directories absolute paths
     *
     * @return list<class-string>
     */
    public function find(array $directories): array
    {
        $classes = [];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            foreach ($this->phpFiles($directory) as $file) {
                $class = $this->classInFile($file);
                if (null !== $class) {
                    $classes[] = $class;
                }
            }
        }

        $classes = array_values(array_unique($classes));
        sort($classes);

        return $classes;
    }

    /**
     * @return iterable<string>
     */
    private function phpFiles(string $directory): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                yield $file->getPathname();
            }
        }
    }

    /**
     * @return class-string|null
     */
    private function classInFile(string $file): ?string
    {
        $code = file_get_contents($file);
        if (false === $code) {
            return null;
        }

        $tokens = token_get_all($code);
        $namespace = '';
        $count = \count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i];
            if (!\is_array($token)) {
                continue;
            }

            if (\T_NAMESPACE === $token[0]) {
                $namespace = $this->readName($tokens, $i + 1);

                continue;
            }

            if (\T_CLASS === $token[0]) {
                if ($this->isAnonymousOrModifier($tokens, $i)) {
                    continue;
                }

                $name = $this->readClassName($tokens, $i + 1);
                if (null === $name) {
                    return null;
                }

                /** @var class-string $fqcn */
                $fqcn = '' === $namespace ? $name : $namespace.'\\'.$name;

                return $fqcn;
            }
        }

        return null;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function readName(array $tokens, int $start): string
    {
        $name = '';
        $count = \count($tokens);

        for ($i = $start; $i < $count; ++$i) {
            $token = $tokens[$i];
            if (\is_array($token) && \in_array($token[0], [\T_STRING, \T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED], true)) {
                $name .= $token[1];

                continue;
            }
            if (\is_array($token) && \T_WHITESPACE === $token[0]) {
                if ('' !== $name) {
                    break;
                }

                continue;
            }
            break;
        }

        return trim($name, '\\');
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function readClassName(array $tokens, int $start): ?string
    {
        $count = \count($tokens);
        for ($i = $start; $i < $count; ++$i) {
            $token = $tokens[$i];
            if (\is_array($token) && \T_WHITESPACE === $token[0]) {
                continue;
            }
            if (\is_array($token) && \T_STRING === $token[0]) {
                return $token[1];
            }

            return null;
        }

        return null;
    }

    /**
     * True when the T_CLASS token is `new class` (anonymous) or the `::class`
     * constant, which must not be treated as a declaration.
     *
     * @param list<array{int, string, int}|string> $tokens
     */
    private function isAnonymousOrModifier(array $tokens, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; --$i) {
            $token = $tokens[$i];
            if (\is_array($token) && \T_WHITESPACE === $token[0]) {
                continue;
            }

            // `::class` -> preceded by T_DOUBLE_COLON; `new class` -> T_NEW.
            if (\is_array($token) && \in_array($token[0], [\T_DOUBLE_COLON, \T_NEW], true)) {
                return true;
            }

            return false;
        }

        return false;
    }
}
