<?php

declare(strict_types=1);

/**
 * Bootstrap aplikacji: autoloading + helpery.
 *
 * Używa Composera, jeśli dostępny (vendor/autoload.php).
 * W przeciwnym razie rejestruje prosty autoloader PSR-4 (App\ => app/),
 * dzięki czemu projekt działa również bez `composer install`.
 */

define('BASE_PATH', __DIR__);

$composerAutoload = BASE_PATH . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        $baseDir = BASE_PATH . '/app/';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }
    });
}

// Helpery globalne (e(), url(), asset(), ...)
require BASE_PATH . '/app/helpers.php';
