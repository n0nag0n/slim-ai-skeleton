<?php

$rootPath = dirname(__DIR__);

require_once $rootPath . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

$debug = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    Tracy\Debugger::enable(Tracy\Debugger::Development, $rootPath . '/var/log');
}

$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions($rootPath . '/config/dependencies.php');

if (!$debug) {
    $containerBuilder->enableCompilation($rootPath . '/var/cache/container');
}

$container = $containerBuilder->build();

$app = DI\Bridge\Slim\Bridge::create($container);

(require $rootPath . '/config/middleware.php')($app);

(require $rootPath . '/config/routes.php')($app);

if ($debug) {
    $app->add(\App\Debug\TracyMiddleware::class);
}

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware($debug, true, true);

if ($debug) {
    new \App\Debug\Tracy\ExtensionLoader($app);
}

$errorMiddleware->setDefaultErrorHandler(
    function (
        $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use (
        $app,
        $debug
    ) {
        if ($debug) {
            throw $exception;
        }

        $code = $exception instanceof \Slim\Exception\HttpException
            ? $exception->getCode()
            : 500;

        $accept = $request->getHeaderLine('Accept');

        if (str_contains($accept, 'text/html')) {
            $twig = $app->getContainer()->get(\Slim\Views\Twig::class);

            if ($code === 404) {
                return $twig->render(
                    $app->getResponseFactory()->createResponse(404),
                    'error/404.twig'
                );
            }

            return $twig->render(
                $app->getResponseFactory()->createResponse(500),
                'error/500.twig',
                ['message' => $exception->getMessage()]
            );
        }

        $response = $app->getResponseFactory()->createResponse($code);
        $response->getBody()->write(json_encode([
            'error' => $exception->getMessage(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
);

$app->run();
