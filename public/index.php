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
        $xDev = $request->getHeaderLine('X-Dev');

        // Debug mode (no X-Dev) → let Tracy render the full debug page
        if ($displayErrorDetails && $xDev !== '1') {
            throw $exception;
        }

        $code = $exception instanceof \Slim\Exception\HttpException
            ? $exception->getCode()
            : 500;

        if ($code < 400 || $code > 599) {
            $code = 500;
        }

        if ($logErrors) {
            error_log(sprintf(
                '[%s] %s in %s:%d',
                (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }

        $accept = $request->getHeaderLine('Accept');
        $wantsHtml = str_contains($accept, 'text/html');

        // X-Dev always returns JSON; otherwise content-negotiate
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

        // JSON response
        $data = ['error' => 'Internal Server Error'];

        if ($displayErrorDetails) {
            $data['error'] = $exception->getMessage();
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
            $data['trace'] = array_map(
                fn(array $t) => [
                    'file' => $t['file'] ?? null,
                    'line' => $t['line'] ?? null,
                    'function' => $t['function'] ?? null,
                    'class' => $t['class'] ?? null,
                ],
                $exception->getTrace()
            );
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
