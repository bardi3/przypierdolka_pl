<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Auth;
use App\Core\Response;

/**
 * Tylko dla niezalogowanych (np. strony logowania/rejestracji).
 * Zalogowani są przekierowywani na stronę główną.
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(App $app): ?Response
    {
        /** @var Auth $auth */
        $auth = $app->get('auth');
        if ($auth->check()) {
            return Response::redirect('/');
        }
        return null;
    }
}
