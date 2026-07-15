<?php

declare(strict_types=1);

use Infrasonic\Http\Request;
use Infrasonic\Runtime\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$bootstrap = dirname(__DIR__).'/var/compiled/bootstrap.php';

if (!is_file($bootstrap)) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['status' => 503, 'error' => 'Application is not compiled. Run: vendor/bin/infra build']);

    return;
}

/** @var Kernel $kernel */
$kernel = require $bootstrap;

$kernel->handle(Request::fromGlobals())->send();
