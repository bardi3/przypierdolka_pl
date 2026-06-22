<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;

/**
 * Wymaga zalogowanego użytkownika. W przeciwnym razie przekierowuje do logowania.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(App $app): ?Response
    {
        /** @var Auth $auth */
        $auth = $app->get('auth');
        if ($auth->check()) {
            return null;
        }

        /** @var Session $session */
        $session = $app->get('session');
        $session->flash('error', 'Musisz być zalogowany, aby kontynuować.');

        return Response::redirect('/logowanie');
    }
}
