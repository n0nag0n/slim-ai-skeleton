<?php

$rootPath = dirname(__DIR__);

require_once $rootPath . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

$debug = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    Tracy\Debugger::enable(Tracy\Debugger::Development, $rootPath . '/var/log');
}

$containerBuilder = new DI\ContainerBuilder;
$containerBuilder->addDefinitions($rootPath . '/config/dependencies.php');

if (!$debug) {
    $containerBuilder->enableCompilation($rootPath . '/var/cache/container');
}

$container = $containerBuilder->build();

$app = DI\Bridge\Slim\Bridge::create($container);

(require $rootPath . '/config/middleware.php')($app);

(require $rootPath . '/config/routes.php')($app);

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware($debug, true, true);

$errorMiddleware->setDefaultErrorHandler(
    function (
        $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app, $debug) {
        if ($debug) {
            throw $exception;
        }

        $code = $exception instanceof \Slim\Exception\HttpException
            ? $exception->getCode()
            : 500;

        $accept = $request->getHeaderLine('Accept');

        if (str_contains($accept, 'text/html')) {
            $twig = $app->getContainer()->get(\Slim\Views\Twig::class);
            $template = $code === 404 ? 'error/404.twig' : 'error/500.twig';
            return $twig->render(
                $app->getResponseFactory()->createResponse($code),
                $template
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
