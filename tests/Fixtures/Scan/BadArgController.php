<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures\Scan;

use Infrasonic\Http\Attribute\Route;
use Infrasonic\Http\Method;
use Infrasonic\Http\Response;

final class BadArgController
{
    #[Route(Method::GET, '/reports')]
    public function report(int $year): Response
    {
        return Response::json(['year' => $year]);
    }
}
