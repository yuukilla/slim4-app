<?php

namespace App\Action\Home;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HomeAction
{

    private $twig;

    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->twig->render(
            $response, 
            'home.twig',
            array(
                'title' => 'Slim4 App',
                'data' => "Hello, world!"
            )
        );
    }
}
