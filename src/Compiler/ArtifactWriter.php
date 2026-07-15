<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

/**
 * Writes generated artifacts to the output directory, creating it if needed.
 */
final class ArtifactWriter
{
    /**
     * @param array<string, string> $files filename => contents
     *
     * @return list<string> absolute paths written, in the given order
     */
    public function write(string $directory, array $files): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Unable to create output directory "%s".', $directory));
        }

        $written = [];
        foreach ($files as $name => $contents) {
            $path = $directory.\DIRECTORY_SEPARATOR.$name;
            if (false === file_put_contents($path, $contents)) {
                throw new \RuntimeException(\sprintf('Unable to write artifact "%s".', $path));
            }
            $written[] = $path;
        }

        return $written;
    }
}
