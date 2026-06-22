<?php

declare(strict_types=1);

/**
 * Główna konfiguracja aplikacji.
 * Wartości można nadpisać w config/local.php (nie commitowanym).
 */
return [
    'name'        => 'przypierdolka.pl',
    'tagline'     => 'Krótkie historie, które przypierdalają',
    'env'         => 'local',          // local | production
    'debug'       => true,             // false na produkcji
    'url'         => 'http://localhost:8000',
    'timezone'    => 'Europe/Warsaw',
    'locale'      => 'pl_PL',
    'charset'     => 'UTF-8',

    // Paginacja
    'per_page'    => 12,

    // Ścieżki (absolutne, ustawiane w bootstrapie)
    'paths' => [
        'base'      => dirname(__DIR__),
        'app'       => dirname(__DIR__) . '/app',
        'templates' => dirname(__DIR__) . '/templates',
        'storage'   => dirname(__DIR__) . '/storage',
        'cache'     => dirname(__DIR__) . '/storage/cache',
        'logs'      => dirname(__DIR__) . '/storage/logs',
        'public'    => dirname(__DIR__) . '/public',
        'uploads'   => dirname(__DIR__) . '/public/assets/uploads',
    ],

    // Cache plikowy
    'cache' => [
        'enabled' => true,
        'ttl'     => 300,  // domyślny TTL list (5 min)
        'ttl_rankings' => 600, // rankingi: 10 min (zakres 5–15 min)
    ],

    // Rankingi
    'rankings' => [
        'min_ratings' => 2, // minimalna liczba ocen, by trafić do rankingu
    ],

    // Limity dodawania historii
    'stories' => [
        'guest_max_per_window' => 2, // gość: max 2 historie / okno rate limitera
    ],

    // Upload
    'uploads' => [
        'max_size'      => 4 * 1024 * 1024, // 4 MB
        'allowed_mime'  => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'allowed_ext'   => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    ],

    // Wersja assetów (do cache-bustingu ?v=)
    'assets_version' => '2.10.3',

    // Wyszukiwarka
    'search' => [
        'min_length'  => 2,
        'max_results' => 8,
        'cache_ttl'   => 120,
    ],

    // Panel admina - bazowy prefiks URL
    'admin_prefix' => '/admineu',
];
