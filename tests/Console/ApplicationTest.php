<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Console;

use Infrasonic\Console\Application;
use Infrasonic\Console\Output;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
final class ApplicationTest extends TestCase
{
    /**
     * @param list<string> $argv
     *
     * @return array{int, string}
     */
    private function execute(array $argv): array
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);

        $application = new Application(\dirname(__DIR__, 2), new Output($stream));
        $exitCode = $application->run($argv);

        rewind($stream);
        $output = (string) stream_get_contents($stream);
        fclose($stream);

        return [$exitCode, $output];
    }

    public function testListsCommands(): void
    {
        [$exitCode, $output] = $this->execute(['infra']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('build', $output);
        self::assertStringContainsString('routes', $output);
    }

    public function testRoutesCommandListsApplicationRoutes(): void
    {
        [$exitCode, $output] = $this->execute(['infra', 'routes']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('/hello/{name}', $output);
        self::assertStringContainsString('GreetingController', $output);
    }

    public function testContainerDebugListsServices(): void
    {
        [$exitCode, $output] = $this->execute(['infra', 'container:debug']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('GreetingService', $output);
        self::assertStringContainsString('App\Clock\Clock', $output);
    }

    public function testUnknownCommandFails(): void
    {
        [$exitCode, $output] = $this->execute(['infra', 'nonsense']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Unknown command', $output);
    }
}
