<?php

use Slim\App;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;
use Cake\Database\Connection;
use App\Factory\LoggerFactory;
use Twig\Loader\FilesystemLoader;
use Odan\Twig\TwigAssetsExtension;
use App\Handler\DefaultErrorHandler;
use Slim\Middleware\ErrorMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\Interfaces\RouteParserInterface;
use Selective\BasePath\BasePathMiddleware;
use Symfony\Component\Console\Application;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\Console\Input\InputOption;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

return [
    // Application settings
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);

        // Register routes
        (require __DIR__ . '/routes.php')($app);

        // Register middleware
        (require __DIR__ . '/middleware.php')($app);

        return $app;
    },

    // HTTP factories
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    StreamFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    UriFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    // The Slim RouterParser
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    // The logger factory
    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get('settings')['logger']);
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        return new BasePathMiddleware($container->get(App::class));
    },

    // Database connection
    Connection::class => function (ContainerInterface $container) {
        return new Connection($container->get('settings')['db']);
    },

    PDO::class => function (ContainerInterface $container) {
        $db = $container->get(Connection::class);
        $driver = $db->getDriver();
        $driver->connect();

        return $driver->getConnection();
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['error'];
        $app = $container->get(App::class);

        $logger = $container->get(LoggerFactory::class)
            ->addFileHandler('error.log')
            ->createLogger();

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings['display_error_details'],
            (bool)$settings['log_errors'],
            (bool)$settings['log_error_details'],
            $logger
        );

        $errorMiddleware->setDefaultErrorHandler($container->get(DefaultErrorHandler::class));

        return $errorMiddleware;
    },

    Application::class => function (ContainerInterface $container) {
        $application = new Application();

        $application->getDefinition()->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev')
        );

        foreach ($container->get('settings')['commands'] as $class) {
            $application->add($container->get($class));
        }

        return $application;
    },
    Twig::class => function ( ContainerInterface $container ) {
        $settings     = $container->get('settings');
        $twigSettings = $settings['twig'];

        $twig = Twig::create(
            $twigSettings['path'],
            [
                'cache' => $twigSettings['cache_enabled'] ? $twigSettings['cache_path'] : false,
                'debug' => $twigSettings['debug']
            ]
        );

        $loader = $twig->getLoader();
        if ( $loader instanceof FilesystemLoader ) {
            $loader->addPath($twigSettings['loader_path'], $twigSettings['loader_name']);
        }

        $enviroment = $twig->getEnvironment();

        $twig->addExtension( new TwigAssetsExtension($enviroment, (array)$settings['assets']) );
        $twig->addExtension( new \Twig\Extension\DebugExtension() );

        return $twig;
    }
];
