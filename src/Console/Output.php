<?php

declare(strict_types=1);

namespace Infrasonic\Console;

/**
 * Minimal ANSI console output.
 */
final class Output
{
    /** @var resource */
    private $stream;

    private readonly bool $decorated;

    /**
     * @param resource|null $stream
     */
    public function __construct($stream = null)
    {
        $this->stream = $stream ?? \STDOUT;
        $this->decorated = \function_exists('posix_isatty') && @posix_isatty($this->stream);
    }

    public function writeln(string $line = ''): void
    {
        fwrite($this->stream, $line."\n");
    }

    public function title(string $text): void
    {
        $this->writeln();
        $this->writeln($this->style($text, '1;36'));
        $this->writeln($this->style(str_repeat('─', mb_strlen($text)), '36'));
    }

    public function success(string $text): void
    {
        $this->writeln($this->style('✓ ', '32').$text);
    }

    public function error(string $text): void
    {
        $this->writeln($this->style('✗ '.$text, '31'));
    }

    public function info(string $text): void
    {
        $this->writeln($this->style('› ', '34').$text);
    }

    public function comment(string $text): void
    {
        $this->writeln($this->style($text, '90'));
    }

    private function style(string $text, string $code): string
    {
        if (!$this->decorated) {
            return $text;
        }

        return "\033[".$code.'m'.$text."\033[0m";
    }
}
