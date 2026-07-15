<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Compiler;

use App\Clock\Clock;
use App\Clock\SystemClock;
use App\Middleware\RequestTimer;
use Infrasonic\Compiler\Compiler;
use Infrasonic\Compiler\CompilerConfig;
use Infrasonic\Http\Request;
use Infrasonic\Runtime\Kernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Compiler::class)]
final class CompilerTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            foreach ((array) glob($dir.'/*') as $file) {
                if (\is_string($file)) {
                    @unlink($file);
                }
            }
            @rmdir($dir);
        }
    }

    private function config(string $outputDir): CompilerConfig
    {
        return new CompilerConfig(
            outputDir: $outputDir,
            scanPaths: [\dirname(__DIR__, 2).'/app'],
            bindings: [Clock::class => SystemClock::class],
            middleware: [RequestTimer::class],
            parameters: ['app.debug' => true],
        );
    }

    private function tempDir(): string
    {
        $dir = sys_get_temp_dir().'/infra_'.uniqid('', true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    public function testReportsWhatWasCompiledAndWritesArtifacts(): void
    {
        $dir = $this->tempDir();
        $report = (new Compiler())->compile($this->config($dir));

        self::assertSame(5, $report->services);
        self::assertSame(2, $report->controllers);
        self::assertSame(4, $report->routes);

        foreach (['CompiledContainer.php', 'CompiledRouter.php', 'config.php', 'bootstrap.php'] as $file) {
            self::assertFileExists($dir.'/'.$file);
        }
    }

    public function testCompiledKernelServesRealRequests(): void
    {
        $dir = $this->tempDir();
        (new Compiler())->compile($this->config($dir));

        /** @var Kernel $kernel */
        $kernel = require $dir.'/bootstrap.php';
        self::assertInstanceOf(Kernel::class, $kernel);

        $home = $kernel->handle(new Request('GET', '/'));
        self::assertSame(200, $home->status);
        self::assertStringContainsString('"framework":"Infrasonic"', $home->body);
        self::assertSame('Infrasonic', $home->headers['X-Powered-By']);

        $hello = $kernel->handle(new Request('GET', '/hello/Ada'));
        self::assertStringContainsString('Hello Ada', $hello->body);

        $add = $kernel->handle(new Request('GET', '/add/2/3'));
        self::assertSame('{"a":2,"b":3,"sum":5}', $add->body);

        $missing = $kernel->handle(new Request('GET', '/nope'));
        self::assertSame(404, $missing->status);
    }
}
