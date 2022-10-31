<?php

use Slim\App;
use App\Factory\ContainerFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Build DI Container instance
$container = (new ContainerFactory())->createInstance();

// Create App instance
return $container->get(App::class);
