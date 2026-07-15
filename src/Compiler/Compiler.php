<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\Metadata\ScanResult;

/**
 * Orchestrates the whole build: scan source, compile the container, router and
 * config, generate the runtime bootstrap, and write the artifacts.
 *
 * This is the "ahead-of-time" step. Everything expensive (reflection, wiring,
 * route analysis) happens here so the runtime path can stay reflection-free.
 */
final class Compiler
{
    public function __construct(
        private readonly ClassFinder $finder = new ClassFinder(),
        private readonly SourceScanner $scanner = new SourceScanner(),
        private readonly ContainerCompiler $containerCompiler = new ContainerCompiler(),
        private readonly RouteCompiler $routeCompiler = new RouteCompiler(),
        private readonly ConfigCompiler $configCompiler = new ConfigCompiler(),
        private readonly ArtifactWriter $writer = new ArtifactWriter(),
    ) {
    }

    public function compile(CompilerConfig $config): CompileReport
    {
        $classes = $this->finder->find($config->scanPaths);
        $scan = $this->scanner->scan($classes);

        $this->assertMiddlewareRegistered($scan, $config->middleware);

        $files = $this->writer->write($config->outputDir, [
            'CompiledContainer.php' => $this->containerCompiler->compile($scan->services, $config->bindings),
            'CompiledRouter.php' => $this->routeCompiler->compile($scan->routes),
            'config.php' => $this->configCompiler->compile($config->parameters),
            'bootstrap.php' => $this->renderBootstrap($config->middleware),
        ]);

        return new CompileReport(
            services: \count($scan->services),
            controllers: \count(array_filter($scan->services, static fn ($s) => $s->isController)),
            routes: \count($scan->routes),
            files: $files,
        );
    }

    /**
     * @param list<class-string> $middleware
     */
    private function assertMiddlewareRegistered(ScanResult $scan, array $middleware): void
    {
        $serviceIds = array_map(static fn ($s) => $s->id, $scan->services);

        foreach ($middleware as $id) {
            if (!\in_array($id, $serviceIds, true)) {
                throw new CompilerError(\sprintf('Middleware "%s" is listed in config but is not a registered service. Add #[Service] to it.', $id));
            }
        }
    }

    /**
     * @param list<class-string> $middleware
     */
    private function renderBootstrap(array $middleware): string
    {
        $middlewareList = implode(', ', array_map(static fn (string $id) => '\\'.$id.'::class', $middleware));

        $containerFqcn = '\\'.ContainerCompiler::FQCN;
        $routerFqcn = '\\'.RouteCompiler::FQCN;

        return <<<PHP
            <?php

            declare(strict_types=1);

            /*
             * Compiled by Infrasonic. Do not edit.
             *
             * Returns a ready-to-serve Kernel. Requiring this file is the entire
             * runtime boot: no scanning, no reflection, no container build.
             */
            require __DIR__ . '/CompiledContainer.php';
            require __DIR__ . '/CompiledRouter.php';

            \\Infrasonic\\Runtime\\Config::init(require __DIR__ . '/config.php');

            return new \\Infrasonic\\Runtime\\Kernel(
                new {$routerFqcn}(),
                new {$containerFqcn}(),
                [{$middlewareList}],
                \\Infrasonic\\Runtime\\Config::bool('app.debug', false),
            );

            PHP;
    }
}
