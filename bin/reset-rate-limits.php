#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Reset limitów prób (logowanie, rejestracja, dodawanie historii, oceny).
 *
 * Użycie: php bin/reset-rate-limits.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\App;
use App\Core\RateLimiter;

$app = App::boot(BASE_PATH);
/** @var RateLimiter $limiter */
$limiter = $app->get('rateLimiter');
$limiter->clearAll();

echo "Rate limits cleared.\n";
echo "Możesz też otworzyć: http://localhost:8000/dev/reset-rate-limits\n";
