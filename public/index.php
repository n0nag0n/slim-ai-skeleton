<?php

declare(strict_types=1);

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

// CSRF must run after body parsing so it can read form data
$app->add(\App\Security\CsrfMiddleware::class);

$errorMiddleware = $app->addErrorMiddleware($debug, true, true);

if ($debug) {
    new \App\Debug\Tracy\ExtensionLoader($app);
}

$errorMiddleware->setDefaultErrorHandler(
    function (
        Psr\Http\Message\ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use (
        $app
    ) {
        // X-Dev: 1 in debug mode → always JSON with trace, bypass Tracy
        $xDev = $request->getHeaderLine('X-Dev');

        if ($displayErrorDetails && $xDev !== '1') {
            throw $exception;
        }

        // Log the error server-side regardless
        if ($logErrors) {
            error_log(sprintf(
                '[%s] %s in %s:%d',
                (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }

        $code = $exception instanceof \Slim\Exception\HttpException
            ? $exception->getCode()
            : 500;

        if ($code < 400 || $code > 599) {
            $code = 500;
        }

        // X-Dev always gets compact JSON; otherwise content-negotiate
        $accept = $request->getHeaderLine('Accept');
        $wantsHtml = str_contains($accept, 'text/html');

        if ($xDev !== '1' && $wantsHtml) {
            $twig = $app->getContainer()->get(\Slim\Views\Twig::class);

            if ($code === 404) {
                return $twig->render(
                    $app->getResponseFactory()->createResponse(404),
                    'error/404.twig'
                );
            }

            return $twig->render(
                $app->getResponseFactory()->createResponse(500),
                'error/500.twig'
            );
        }

        $data = ['error' => 'Internal Server Error'];

        if ($displayErrorDetails) {
            $data = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'type' => $exception::class,
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        $body = json_encode($data, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            $body = '{"error":"Internal Server Error"}';
        }

        $response = $app->getResponseFactory()->createResponse($code);
        $response->getBody()->write($body);
        return $response->withHeader('Content-Type', 'application/json');
    }
);

$app->run();
