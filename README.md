# Infrasonic

A modern, **build-time compiled** PHP framework for persistent-worker runtimes.

Infrasonic borrows [Quarkus](https://quarkus.io/)'s core idea and brings it to PHP:
do the expensive work **at build time**, so the runtime stays lean. Routing, the
dependency-injection container, and configuration are all compiled ahead of time
into plain PHP. At request time there is **no reflection, no attribute parsing,
and no container graph-walking** — just array lookups and direct instantiation.

Run under [FrankenPHP](https://frankenphp.dev/) worker mode, the application boots
**once** and serves every request from a warm, pre-compiled kernel.

```php
#[Service]
final class GreetingService
{
    public function __construct(private Clock $clock) {}

    public function greet(string $name): string
    {
        return "Hello {$name}, it is {$this->clock->now()->format('H:i')}";
    }
}

final class GreetingController
{
    public function __construct(private GreetingService $greetings) {}

    #[Route(Method::GET, '/hello/{name}')]
    public function hello(string $name): Response
    {
        return Response::json(['message' => $this->greetings->greet($name)]);
    }
}
```

## Why it's fast

| Concern           | Symfony / typical                  | Infrasonic                                  |
| ----------------- | ---------------------------------- | ------------------------------------------- |
| Routing           | Matcher built/cached, some runtime | Static hash lookup + pre-compiled regex     |
| DI container      | Compiled, but reflection on edges  | 100% generated `new` calls, zero reflection |
| Config            | Parsed / normalized                | Frozen PHP array, resident in OPcache       |
| Per-request boot  | Framework bootstrap each request   | Booted once in the worker                   |
| Attribute parsing | Cached, still resolved             | Done at build time, never at runtime        |

The runtime layer is **architecturally forbidden** from touching reflection or the
compiler — enforced by a test (`tests/Architecture`). That guarantee is what keeps
the request path predictable and quick.

### Benchmark

In-process overhead of the compiled kernel (routing + DI + middleware + response),
network and SAPI excluded:

```
$ php bin/infra build && php bench/bench.php 200000
  throughput : ~170,000 req/s
  latency    : ~5.9 µs/req
  peak memory: 4 MB
```

## Quickstart

```bash
composer install

# Compile the app into runtime artifacts (var/compiled/).
php bin/infra build

# Start a development server (PHP built-in server).
php bin/infra serve            # http://127.0.0.1:8080

# Inspect what was discovered.
php bin/infra routes
php bin/infra container:debug
```

## How it works

Everything splits cleanly into **build time** and **run time**:

```
src/
├── Compiler/   # BUILD-TIME ONLY — reflection lives here
│   ├── SourceScanner        reads #[Service] / #[Route] attributes
│   ├── ContainerCompiler    emits a reflection-free container
│   ├── RouteCompiler        emits static + regex route tables
│   ├── ConfigCompiler       freezes config into a PHP array
│   └── Compiler             orchestrates + writes var/compiled/
└── Runtime/    # RUN-TIME ONLY — no reflection, ever
    ├── Kernel               dispatches a Request to a Response
    ├── CompiledContainer    base for the generated container
    ├── CompiledRouter       base for the generated router
    └── MiddlewarePipeline   PSR-15-style, prebuilt at boot
```

`php bin/infra build` produces `var/compiled/`:

- `CompiledContainer.php` — one factory method per service, e.g.
  `new GreetingService($this->createSystemClock())`.
- `CompiledRouter.php` — static routes as a hash map, dynamic routes as
  pre-compiled regexes grouped by method.
- `config.php` — the frozen configuration array.
- `bootstrap.php` — requires the above and returns a ready-to-serve `Kernel`.

Build errors are actionable and **fail fast**: unresolvable or ambiguous
dependencies, dependency cycles, duplicate routes, and route parameters that don't
match the action signature are all rejected at build time, never at runtime.

## Application layout

```
app/            your services, controllers, middleware
config/
├── services.php     scan paths, interface bindings, middleware order
└── parameters.php   dot-keyed config (env read at build time)
public/
├── index.php        classic SAPI entry (dev / php-fpm)
└── worker.php       FrankenPHP worker loop
```

### Configuration

`config/services.php`:

```php
return [
    'scan' => ['app'],
    'bindings' => [Clock::class => SystemClock::class], // only needed to disambiguate
    'middleware' => [RequestTimer::class],              // outermost first
];
```

Single-implementation interfaces are auto-wired; you only add a binding to resolve
ambiguity.

## Production deployment (FrankenPHP)

```bash
docker compose up --build          # worker on http://127.0.0.1:8080
make smoke                         # probe the running worker
docker compose down
```

The image installs dependencies, runs `infra build`, and serves `public/worker.php`
in FrankenPHP worker mode (see `Dockerfile` and `Caddyfile`). The app is compiled
into the image, so containers start already warm.

To iterate on your code against a real worker, use the dev profile — it mounts your
source, rebuilds artifacts on start, and runs the worker on port **8081**:

```bash
docker compose --profile dev up --build     # or: make dev
```

## Development

A `Makefile` wraps the common tasks (`make help` lists them all):

```bash
make install      # composer install
make build        # infra build
make serve        # PHP built-in dev server
make check        # cs + stan + test
make bench        # in-process benchmark
make up / down    # FrankenPHP worker via Docker
make smoke        # probe the running worker
```

Or call the tools directly:

```bash
composer test     # PHPUnit
composer stan     # PHPStan (level 8)
composer cs       # PHP-CS-Fixer (dry-run)
composer check    # all of the above
```

## Requirements

- PHP 8.4+
- OPcache (strongly recommended in production)
- FrankenPHP (production runtime; the dev server needs only PHP)

## License

MIT
