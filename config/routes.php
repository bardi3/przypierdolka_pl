<?php

declare(strict_types=1);

/**
 * Tabela routingu.
 */
return [
    // --- Frontend ---
    ['GET',  '/',                          'HomeController@index'],
    ['GET',  '/strona/{page}',             'HomeController@index'],
    ['GET',  '/tablica/znajomi',           'HomeController@friendsFeed', 'auth'],

    // Rankingi
    ['GET',  '/top/tydzien',               'HomeController@topWeek'],
    ['GET',  '/top/miesiac',               'HomeController@topMonth'],
    ['GET',  '/top/wszystkie',             'HomeController@topAll'],
    ['GET',  '/top/{period}',              'HomeController@top'], // legacy alias

    // Historie
    ['GET',  '/dodaj',                     'StoryController@create'],
    ['POST', '/dodaj',                     'StoryController@store'],
    ['GET',  '/historia/dodaj',            'StoryController@createLegacy'],
    ['POST', '/historia/dodaj',            'StoryController@store'],
    ['GET',  '/historia/losowa',            'StoryController@random'],
    ['GET',  '/historia/{slug}',           'StoryController@show'],

    // Kategorie
    ['GET',  '/kategorie',                 'CategoryController@index'],
    ['GET',  '/kategoria/{slug}/top',      'CategoryController@top'],
    ['GET',  '/kategoria/{slug}',          'CategoryController@show'],

    // Profile publiczne
    ['GET',  '/profil/{username}',         'ProfileController@show'],
    ['POST', '/profil/{username}/zapros',  'FriendController@sendRequest',   'auth'],
    ['POST', '/profil/{username}/akceptuj','FriendController@accept',        'auth'],
    ['POST', '/profil/{username}/odrzuc',  'FriendController@reject',        'auth'],
    ['POST', '/profil/{username}/usun',    'FriendController@remove',        'auth'],

    // Tablica (AJAX)
    ['GET',  '/ajax/feed',                 'FeedController@load'],
    ['POST', '/ajax/story',                'StoryController@ajaxStore'],
    ['POST', '/ajax/story/{id}/edit',      'StoryController@ajaxUpdate', 'auth'],

    // Wyszukiwarka
    ['GET',  '/szukaj',                    'SearchController@index'],
    ['GET',  '/ajax/search',               'SearchController@ajax'],

    // Oceny (AJAX)
    ['POST', '/ajax/rate',                 'RatingController@ajaxRate'],
    ['POST', '/ocena/{id}',                'RatingController@rateLegacy'],

    // Dev (tylko app.env = local)
    ['GET',  '/dev/reset-rate-limits',     'DevController@resetRateLimits'],

    // Udostępnianie
    ['GET',  '/udostepnij/{slug}',         'ShareController@go'],
    ['GET',  '/share-image/{slug}.webp',   'ShareController@image'],
    ['GET',  '/share-image/{slug}.png',    'ShareController@imageLegacy'],

    // Auth
    ['GET',  '/logowanie',                 'AuthController@showLogin',    'guest'],
    ['POST', '/logowanie',                 'AuthController@login',        'guest'],
    ['GET',  '/rejestracja',               'AuthController@showRegister', 'guest'],
    ['POST', '/rejestracja',               'AuthController@register',     'guest'],
    ['POST', '/wyloguj',                   'AuthController@logout',       'auth'],

    // Panel użytkownika
    ['GET',  '/konto',                     'AccountController@index',     'auth'],
    ['GET',  '/konto/profil',              'AccountController@profile',   'auth'],
    ['POST', '/konto/profil',              'AccountController@updateProfile', 'auth'],
    ['GET',  '/konto/prywatnosc',          'AccountController@privacy',   'auth'],
    ['POST', '/konto/prywatnosc',          'AccountController@updatePrivacy', 'auth'],
    ['GET',  '/konto/znajomi',             'AccountController@friends',   'auth'],
    ['POST', '/konto/znajomi/akceptuj/{id}','AccountController@acceptFriend', 'auth'],
    ['POST', '/konto/znajomi/odrzuc/{id}', 'AccountController@rejectFriend', 'auth'],
    ['POST', '/konto/znajomi/usun/{id}',   'AccountController@removeFriend', 'auth'],
    ['GET',  '/konto/haslo',               'AccountController@password',  'auth'],
    ['POST', '/konto/haslo',               'AccountController@updatePassword', 'auth'],
    ['GET',  '/konto/historie',            'AccountController@stories',   'auth'],
    ['POST', '/konto/historie/cofnij/{id}','AccountController@withdrawStory', 'auth'],

    // SEO
    ['GET',  '/sitemap.xml',               'HomeController@sitemap'],
    ['GET',  '/robots.txt',                'HomeController@robots'],
    ['GET',  '/llms.txt',                  'LlmsTxtController@show'],

    // --- Panel admina (/admineu) ---
    ['GET',  '/admineu',                       'Admin\\DashboardController@index',     'admin'],
    ['GET',  '/admineu/users',                 'Admin\\UsersController@index',         'admin'],
    ['GET',  '/admineu/users/add',             'Admin\\UsersController@create',        'admin'],
    ['POST', '/admineu/users/add',             'Admin\\UsersController@store',         'admin'],
    ['GET',  '/admineu/users/edit/{id}',       'Admin\\UsersController@edit',          'admin'],
    ['POST', '/admineu/users/edit/{id}',       'Admin\\UsersController@update',        'admin'],
    ['POST', '/admineu/users/delete/{id}',     'Admin\\UsersController@delete',        'admin'],

    ['POST', '/admineu/ajax/stories/refresh/{id}', 'Admin\\StoriesController@refreshCache', 'moderator'],

    ['GET',  '/admineu/stories',               'Admin\\StoriesController@index',       'moderator'],
    ['GET',  '/admineu/stories/pending',       'Admin\\StoriesController@pending',     'moderator'],
    ['GET',  '/admineu/stories/edit/{id}',     'Admin\\StoriesController@edit',        'moderator'],
    ['POST', '/admineu/stories/edit/{id}',     'Admin\\StoriesController@update',      'moderator'],
    ['POST', '/admineu/stories/approve/{id}',  'Admin\\StoriesController@approve',     'moderator'],
    ['POST', '/admineu/stories/reject/{id}',   'Admin\\StoriesController@reject',      'moderator'],
    ['POST', '/admineu/stories/delete/{id}',   'Admin\\StoriesController@delete',      'moderator'],

    ['GET',  '/admineu/categories',            'Admin\\CategoriesController@index',    'admin'],
    ['GET',  '/admineu/categories/add',        'Admin\\CategoriesController@create',   'admin'],
    ['POST', '/admineu/categories/add',        'Admin\\CategoriesController@store',    'admin'],
    ['GET',  '/admineu/categories/edit/{id}',  'Admin\\CategoriesController@edit',     'admin'],
    ['POST', '/admineu/categories/edit/{id}',  'Admin\\CategoriesController@update',   'admin'],
    ['POST', '/admineu/categories/sort',       'Admin\\CategoriesController@sort',     'admin'],
    ['POST', '/admineu/categories/delete/{id}','Admin\\CategoriesController@delete',   'admin'],

    ['GET',  '/admineu/settings',              'Admin\\SettingsController@index',      'admin'],
    ['POST', '/admineu/settings',              'Admin\\SettingsController@update',     'admin'],

    ['GET',  '/admineu/llms',                  'Admin\\LlmsController@index',          'admin'],
    ['POST', '/admineu/llms/meta',             'Admin\\LlmsController@updateMeta',     'admin'],
    ['POST', '/admineu/llms/sync',             'Admin\\LlmsController@sync',           'admin'],
    ['GET',  '/admineu/llms/add',              'Admin\\LlmsController@create',         'admin'],
    ['POST', '/admineu/llms/add',              'Admin\\LlmsController@store',          'admin'],
    ['GET',  '/admineu/llms/edit/{id}',        'Admin\\LlmsController@edit',           'admin'],
    ['POST', '/admineu/llms/edit/{id}',        'Admin\\LlmsController@update',         'admin'],
    ['POST', '/admineu/llms/delete/{id}',      'Admin\\LlmsController@delete',         'admin'],
];
