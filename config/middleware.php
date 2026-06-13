<?php

declare(strict_types=1);

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Util\SessionInterface;
use App\Util\Csrf;
use App\Security\SecurityHeadersMiddleware;
use App\Security\CorsMiddleware;

return function (App $app) {
    $container = $app->getContainer();

    if ($container === null) {
        return;
    }

    $debug = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // When TRUSTED_PROXIES is set, a bootstrap patch in public/index.php has
    // already normalized $_SERVER for Session cookies and Slim request creation.
    // The PSR-7 request object will usually be born correct; add client_ip
    // attribute here for logging / future use if headers are present.
    if (($_ENV['TRUSTED_PROXIES'] ?? '') !== '') {
        $app->add(function ($request, $handler) {
            $for = $request->getHeaderLine('X-Forwarded-For');
            if ($for !== '') {
                $client = trim(explode(',', $for)[0]);
                if ($client !== '') {
                    $request = $request->withAttribute('client_ip', $client);
                }
            }
            return $handler->handle($request);
        });
    }

    // CORS outermost — handles OPTIONS preflight before anything else
    $app->add($container->get(CorsMiddleware::class));

    // Security headers wrap every response
    $app->add(new SecurityHeadersMiddleware($debug));

    // Session must start before CSRF and Twig
    $app->add(function ($request, $handler) use ($container) {
        $session = $container->get(SessionInterface::class);
        $session->start();

        // Inject csrf_token as a Twig global so forms can use it
        $csrf = $container->get(Csrf::class);
        $twig = $container->get(Twig::class);
        $twig->getEnvironment()->addGlobal('csrf_token', $csrf->generate());

        $response = $handler->handle($request);
        $session->save();
        return $response;
    });

    $twig = $container->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));
};
