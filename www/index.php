<?php

declare(strict_types=1);

use Apitte\Core\Application\IApplication as ApiApplication;
use App\Bootstrap;
use Nette\Application\Application as UIApplication;

require __DIR__ . '/../vendor/autoload.php';

$isApi = substr($_SERVER['REQUEST_URI'], 0, 4) === '/api';
$container = Bootstrap::boot()->createContainer();

if ($isApi) {
    $container->getByType(ApiApplication::class)->run();
} else {
    $container->getByType(UIApplication::class)->run();
}