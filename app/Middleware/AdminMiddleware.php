<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Permissions;
use App\Core\Response;
use App\Core\Session;

/**
 * Chroni panel admina - wymaga roli admin.
 */
final class AdminMiddleware implements MiddlewareInterface
{
    public function handle(App $app): ?Response
    {
        /** @var Auth $auth */
        $auth = $app->get('auth');

        if (!$auth->check()) {
            /** @var Session $session */
            $session = $app->get('session');
            $session->flash('error', 'Zaloguj się, aby uzyskać dostęp do panelu.');
            return Response::redirect('/logowanie');
        }

        if (!Permissions::isAdmin($auth->role())) {
            throw HttpException::forbidden('Brak uprawnień administratora.');
        }

        return null;
    }
}
