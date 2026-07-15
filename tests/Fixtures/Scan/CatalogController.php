<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures\Scan;

use Infrasonic\Http\Attribute\Route;
use Infrasonic\Http\Method;
use Infrasonic\Http\Request;
use Infrasonic\Http\Response;

final class CatalogController
{
    #[Route(Method::GET, '/catalog')]
    public function index(): Response
    {
        return Response::json(['items' => []]);
    }

    #[Route(Method::GET, '/catalog/{sku}/stock/{qty}')]
    public function stock(string $sku, int $qty): Response
    {
        return Response::json(['sku' => $sku, 'qty' => $qty]);
    }

    #[Route(Method::POST, '/catalog')]
    public function create(Request $request): Response
    {
        return Response::json(['created' => $request->body], 201);
    }
}
