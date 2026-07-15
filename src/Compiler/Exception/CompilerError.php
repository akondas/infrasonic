<?php

declare(strict_types=1);

namespace Infrasonic\Compiler\Exception;

/**
 * A build-time error. These are meant to be actionable: they name the class,
 * parameter, or route that caused the failure so the developer can fix it
 * before deploying — never at runtime.
 */
final class CompilerError extends \RuntimeException
{
    public static function unresolvableDependency(string $service, string $parameter, string $type): self
    {
        return new self(\sprintf(
            'Cannot autowire %s: parameter "$%s" expects "%s", which is not a registered service and has no binding. '
            .'Add #[Service] to a concrete implementation or bind it in config/services.php.',
            $service,
            $parameter,
            $type,
        ));
    }

    public static function unsupportedDependency(string $service, string $parameter, string $reason): self
    {
        return new self(\sprintf(
            'Cannot autowire %s: parameter "$%s" is not supported (%s). '
            .'Constructor dependencies must be class or interface typed.',
            $service,
            $parameter,
            $reason,
        ));
    }

    public static function dependencyCycle(string $chain): self
    {
        return new self(\sprintf('Dependency cycle detected: %s.', $chain));
    }

    public static function ambiguousBinding(string $interface, string $service, string $parameter): self
    {
        return new self(\sprintf(
            'Ambiguous binding for "%s" (needed by %s::$%s): multiple implementations found. '
            .'Bind one explicitly in config/services.php.',
            $interface,
            $service,
            $parameter,
        ));
    }

    public static function invalidRouteArgument(string $controller, string $action, string $parameter, string $reason): self
    {
        return new self(\sprintf(
            'Invalid route action %s::%s(): parameter "$%s" %s.',
            $controller,
            $action,
            $parameter,
            $reason,
        ));
    }

    public static function missingRouteParameter(string $controller, string $action, string $param): self
    {
        return new self(\sprintf(
            'Route action %s::%s() declares path parameter "{%s}" but has no matching method parameter.',
            $controller,
            $action,
            $param,
        ));
    }

    public static function duplicateRoute(string $method, string $path, string $first, string $second): self
    {
        return new self(\sprintf(
            'Duplicate route %s %s is declared by both %s and %s.',
            $method,
            $path,
            $first,
            $second,
        ));
    }
}
