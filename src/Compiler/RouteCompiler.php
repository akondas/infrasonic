<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\Metadata\RouteArgument;
use Infrasonic\Compiler\Metadata\RouteDefinition;

/**
 * Generates the compiled router source.
 *
 * Routes with no parameters go into a per-method hash map (one array lookup).
 * Parameterised routes are compiled to anchored regular expressions grouped by
 * method. All of this is produced ahead of time; matching at runtime touches no
 * reflection and no attribute parsing.
 */
final class RouteCompiler
{
    public const string NAMESPACE = 'Infrasonic\\Generated';
    public const string CLASS_NAME = 'CompiledRouter';
    public const string FQCN = self::NAMESPACE.'\\'.self::CLASS_NAME;

    /**
     * @param list<RouteDefinition> $routes
     */
    public function compile(array $routes): string
    {
        $seen = [];
        $static = [];
        $dynamic = [];

        foreach ($routes as $route) {
            $key = $route->httpMethod.' '.$route->path;
            if (isset($seen[$key])) {
                throw CompilerError::duplicateRoute($route->httpMethod, $route->path, $seen[$key], $route->controller.'::'.$route->action);
            }
            $seen[$key] = $route->controller.'::'.$route->action;

            if ($route->isStatic()) {
                $static[$route->httpMethod][$route->path] = $route;
            } else {
                $dynamic[$route->httpMethod][] = $route;
            }
        }

        return $this->render($this->renderStatic($static), $this->renderDynamic($dynamic));
    }

    /**
     * @param array<string, array<string, RouteDefinition>> $static
     */
    private function renderStatic(array $static): string
    {
        if ([] === $static) {
            return '        return [];';
        }

        $lines = ['        return ['];
        foreach ($static as $method => $paths) {
            $lines[] = \sprintf("            '%s' => [", $method);
            foreach ($paths as $path => $route) {
                $lines[] = \sprintf("                '%s' => %s,", $this->escape($path), $this->renderEntry($route));
            }
            $lines[] = '            ],';
        }
        $lines[] = '        ];';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, list<RouteDefinition>> $dynamic
     */
    private function renderDynamic(array $dynamic): string
    {
        if ([] === $dynamic) {
            return '        return [];';
        }

        $lines = ['        return ['];
        foreach ($dynamic as $method => $routes) {
            $lines[] = \sprintf("            '%s' => [", $method);
            foreach ($routes as $route) {
                $entry = \sprintf("'regex' => '%s', %s", $this->escape($this->toRegex($route->path)), $this->entryBody($route));
                $lines[] = \sprintf('                [%s],', $entry);
            }
            $lines[] = '            ],';
        }
        $lines[] = '        ];';

        return implode("\n", $lines);
    }

    private function renderEntry(RouteDefinition $route): string
    {
        return '['.$this->entryBody($route).']';
    }

    private function entryBody(RouteDefinition $route): string
    {
        return \sprintf(
            "'controller' => \\%s::class, 'action' => '%s', 'args' => [%s]",
            $route->controller,
            $this->escape($route->action),
            $this->renderArgs($route->arguments),
        );
    }

    /**
     * @param list<RouteArgument> $arguments
     */
    private function renderArgs(array $arguments): string
    {
        $parts = [];
        foreach ($arguments as $argument) {
            $descriptor = $argument->toArray();
            if ('request' === $descriptor['source']) {
                $parts[] = "['source' => 'request']";

                continue;
            }
            $parts[] = \sprintf(
                "['source' => 'route', 'name' => '%s', 'cast' => '%s']",
                $this->escape($descriptor['name']),
                $descriptor['cast'],
            );
        }

        return implode(', ', $parts);
    }

    private function toRegex(string $path): string
    {
        $segments = preg_split('/(\{\w+\})/', $path, -1, \PREG_SPLIT_DELIM_CAPTURE);
        \assert(\is_array($segments));

        $regex = '';
        foreach ($segments as $segment) {
            if ('' === $segment) {
                continue;
            }
            if (1 === preg_match('/^\{(\w+)\}$/', $segment, $m)) {
                $regex .= '(?P<'.$m[1].'>[^/]+)';
            } else {
                $regex .= preg_quote($segment, '#');
            }
        }

        return '#^'.$regex.'$#';
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    private function render(string $staticBody, string $dynamicBody): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$this->classNamespace()};

            /**
             * Compiled by Infrasonic. Do not edit.
             */
            final class {$this->className()} extends \\Infrasonic\\Runtime\\CompiledRouter
            {
                protected function staticRoutes(): array
                {
            {$staticBody}
                }

                protected function dynamicRoutes(): array
                {
            {$dynamicBody}
                }
            }

            PHP;
    }

    private function classNamespace(): string
    {
        return self::NAMESPACE;
    }

    private function className(): string
    {
        return self::CLASS_NAME;
    }
}
