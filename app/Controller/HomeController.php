<?php

declare(strict_types=1);

namespace App\Controller;

use Infrasonic\Console\Application;
use Infrasonic\Http\Attribute\Route;
use Infrasonic\Http\Method;
use Infrasonic\Http\Response;

final class HomeController
{
    #[Route(Method::GET, '/')]
    public function index(): Response
    {
        return Response::json([
            'framework' => 'Infrasonic',
            'version' => Application::VERSION,
            'message' => 'Build-time compiled. Reflection-free at runtime.',
        ]);
    }
}
