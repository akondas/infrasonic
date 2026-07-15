<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GreetingService;
use Infrasonic\Http\Attribute\Route;
use Infrasonic\Http\Method;
use Infrasonic\Http\Request;
use Infrasonic\Http\Response;

final class GreetingController
{
    public function __construct(private readonly GreetingService $greetings)
    {
    }

    #[Route(Method::GET, '/hello/{name}')]
    public function hello(string $name): Response
    {
        return Response::json(['message' => $this->greetings->greet($name)]);
    }

    #[Route(Method::GET, '/add/{a}/{b}')]
    public function add(int $a, int $b): Response
    {
        return Response::json(['a' => $a, 'b' => $b, 'sum' => $a + $b]);
    }

    #[Route(Method::POST, '/echo')]
    public function echoBody(Request $request): Response
    {
        return Response::json([
            'method' => $request->method,
            'body' => $request->body,
            'contentType' => $request->header('content-type'),
        ]);
    }
}
