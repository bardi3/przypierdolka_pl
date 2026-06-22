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
 * Chroni sekcje moderacji - wymaga roli moderator lub wyższej.
 */
final class ModeratorMiddleware implements MiddlewareInterface
{
    public function handle(App $app): ?Response
    {
        /** @var Auth $auth */
        $auth = $app->get('auth');

        if (!$auth->check()) {
            /** @var Session $session */
            $session = $app->get('session');
            $session->flash('error', 'Zaloguj się, aby uzyskać dostęp.');
            return Response::redirect('/logowanie');
        }

        if (!Permissions::canModerate($auth->role())) {
            throw HttpException::forbidden('Brak uprawnień moderatora.');
        }

        return null;
    }
}
