<?php

declare(strict_types=1);

/**
 * Front controller — jedyny publiczny punkt wejścia (Apache: .htaccess, dev: router.php).
 *
 * Dev: php -S localhost:8000 -t public public/router.php
 */

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($uriPath !== '/' && !str_starts_with($uriPath, '/index.php')) {
    $file = __DIR__ . rawurldecode($uriPath);

    if (is_file($file)) {
        $publicRoot = str_replace('\\', '/', __DIR__ . '/');
        $fileNorm = str_replace('\\', '/', $file);

        if (str_starts_with($fileNorm, $publicRoot)) {
            // php -S z routerem: false = serwuj plik statyczny z dysku
            if (PHP_SAPI === 'cli-server') {
                return false;
            }

            // Apache (gdy rewrite trafi tu mimo istnienia pliku)
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'css'          => 'text/css; charset=UTF-8',
                'js'           => 'application/javascript; charset=UTF-8',
                'png'          => 'image/png',
                'jpg', 'jpeg'  => 'image/jpeg',
                'gif'          => 'image/gif',
                'webp'         => 'image/webp',
                'svg'          => 'image/svg+xml',
                'ico'          => 'image/x-icon',
                default        => 'application/octet-stream',
            };
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . (string)filesize($file));
            header('Cache-Control: public, max-age=2592000, immutable');
            readfile($file);
            exit;
        }
    }
}

use App\Core\App;

require dirname(__DIR__) . '/bootstrap.php';

App::boot(BASE_PATH)->run();
