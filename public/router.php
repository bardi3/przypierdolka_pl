<?php

declare(strict_types=1);

/**
 * Router dla wbudowanego serwera PHP (dev).
 *
 * Uruchom:
 *   php -S localhost:8000 -t public public/router.php
 *
 * albo (index.php też obsługuje assety):
 *   php -S localhost:8000 -t public public/index.php
 */

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . rawurldecode($uriPath);

if ($uriPath !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
