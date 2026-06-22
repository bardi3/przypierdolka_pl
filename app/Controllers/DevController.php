<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;

/**
 * Narzędzia deweloperskie — tylko środowisko local.
 */
final class DevController extends Controller
{
    public function resetRateLimits(): Response
    {
        $this->assertLocal();

        $this->rateLimiter->clearAll();
        $this->session->flash('success', 'Limity prób (logowanie, rejestracja, oceny itd.) zostały zresetowane.');

        $redirect = (string)$this->input('redirect', '/logowanie');
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/logowanie';
        }

        return $this->redirect($redirect);
    }

    private function assertLocal(): void
    {
        if ((string)Config::get('app.env', 'production') !== 'local') {
            throw HttpException::notFound('Niedostępne.');
        }
    }
}
