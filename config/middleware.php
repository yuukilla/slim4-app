<?php

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Selective\BasePath\BasePathMiddleware;
use App\Middleware\ValidationExceptionMiddleware;

return function (App $app) {
    $app->addBodyParsingMiddleware();
    $app->add(ValidationExceptionMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(BasePathMiddleware::class);
    $app->add(ErrorMiddleware::class);
    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));
};
