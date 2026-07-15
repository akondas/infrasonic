<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures;

use Infrasonic\Http\Request;
use Infrasonic\Http\Response;

final class KernelController
{
    public function ping(): Response
    {
        return Response::text('pong');
    }

    public function user(int $id): Response
    {
        return Response::json(['id' => $id]);
    }

    public function whoami(Request $request): Response
    {
        return Response::json(['method' => $request->method, 'path' => $request->path]);
    }

    public function broken(): string
    {
        return 'not a response';
    }
}
