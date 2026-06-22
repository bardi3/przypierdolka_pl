<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Response;

/**
 * Middleware uruchamiane przed kontrolerem.
 * Zwrócenie Response przerywa łańcuch (np. przekierowanie/403).
 * Zwrócenie null pozwala kontynuować.
 */
interface MiddlewareInterface
{
    public function handle(App $app): ?Response;
}
