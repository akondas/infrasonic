<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Compiler;

use App\Clock\Clock;
use App\Clock\SystemClock;
use App\Controller\GreetingController;
use Infrasonic\Compiler\ClassFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassFinder::class)]
final class ClassFinderTest extends TestCase
{
    public function testFindsConcreteClassesButNotInterfaces(): void
    {
        $classes = (new ClassFinder())->find([\dirname(__DIR__, 2).'/app']);

        self::assertContains(SystemClock::class, $classes);
        self::assertContains(GreetingController::class, $classes);
        self::assertNotContains(Clock::class, $classes, 'Interfaces must not be discovered.');
    }

    public function testIgnoresMissingDirectories(): void
    {
        self::assertSame([], (new ClassFinder())->find(['/does/not/exist']));
    }

    public function testResultIsSortedAndUnique(): void
    {
        $dir = \dirname(__DIR__, 2).'/app';
        $classes = (new ClassFinder())->find([$dir, $dir]);

        $sorted = $classes;
        sort($sorted);

        self::assertSame($sorted, $classes);
        self::assertSame(array_values(array_unique($classes)), $classes);
    }
}
