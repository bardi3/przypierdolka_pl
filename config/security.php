<?php

declare(strict_types=1);

/**
 * Konfiguracja bezpieczeństwa.
 * UWAGA: zmień 'ip_salt' i 'app_key' na produkcji!
 */
return [
    // Sól do hashowania IP (anonimizacja gości przy ocenach)
    'ip_salt'  => 'ZMIEN_TO_NA_PRODUKCJI_losowy_ciag_znakow_1234567890',

    // Klucz aplikacji (np. do podpisów)
    'app_key'  => 'ZMIEN_TO_app_key_losowy_ciag_znakow_0987654321',

    // Sesja
    'session' => [
        'name'            => 'PRZYPID',
        'lifetime'        => 60 * 60 * 24 * 7, // 7 dni
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        // 'secure' jest ustawiane dynamicznie gdy HTTPS
    ],

    // CSRF
    'csrf' => [
        'token_name' => '_csrf',
        'header'     => 'X-CSRF-Token',
        'lifetime'   => 60 * 60 * 2, // 2h
    ],

    // Rate limiting (akcja => [limit, okno_w_sekundach])
    'rate_limits' => [
        'login'      => ['max' => 5,  'window' => 300],   // 5 prób / 5 min (login)
        'login_ip'   => ['max' => 20, 'window' => 300],   // 20 prób / 5 min (IP)
        'register'   => ['max' => 3,  'window' => 600],
        'story_add'       => ['max' => 5,  'window' => 600],   // zalogowany: 5 / 10 min
        'story_add_guest' => ['max' => 2,  'window' => 3600],  // gość: 2 / 1 h
        'rating'          => ['max' => 30, 'window' => 600],
        'search'          => ['max' => 40, 'window' => 60],
        'search_ip'       => ['max' => 80, 'window' => 60],
    ],

    // Hashowanie haseł
    'password' => [
        'algo'    => PASSWORD_DEFAULT,
        'options' => ['cost' => 12],
    ],
];
