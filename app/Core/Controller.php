<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bazowy kontroler. Pobiera dane i przekazuje je do warstwy widoku.
 * Kontrolery NIE renderują HTML samodzielnie - tylko poprzez View.
 */
abstract class Controller
{
    protected App $app;
    protected View $view;
    protected Auth $auth;
    protected Session $session;
    protected Csrf $csrf;
    protected Cache $cache;
    protected Database $db;
    protected RateLimiter $rateLimiter;

    /** Domyślny layout widoku (frontend). Admin nadpisuje na 'layout/admin'. */
    protected string $layout = 'layout/main';

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->view = $app->get('view');
        $this->auth = $app->get('auth');
        $this->session = $app->get('session');
        $this->csrf = $app->get('csrf');
        $this->cache = $app->get('cache');
        $this->db = $app->get('db');
        $this->rateLimiter = $app->get('rateLimiter');
    }

    /**
     * Renderuje widok w layoucie i zwraca Response HTML.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $data['flashes'] = $this->session->getFlashes();
        $html = $this->view->render($template, $data, $this->layout);
        return Response::html($html, $status);
    }

    /**
     * @param mixed $data
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($this->url($url), $status);
    }

    protected function back(string $fallback = '/'): Response
    {
        $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($ref !== '' && $this->isSafeRedirect($ref)) {
            return Response::redirect($ref);
        }
        return $this->redirect($fallback);
    }

    /**
     * Zezwala tylko na przekierowania w obrębie tej samej aplikacji.
     */
    protected function isSafeRedirect(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return !str_starts_with($url, '//');
        }

        $base = rtrim((string)Config::get('app.url'), '/');
        if (!str_starts_with($url, $base)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        return !str_starts_with($path, '//');
    }

    /**
     * Buduje absolutny URL względem konfiguracji aplikacji.
     */
    protected function url(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $base = rtrim((string)Config::get('app.url'), '/');
        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Pobiera dane wejściowe (POST/GET) z domyślną wartością.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Czy żądanie jest AJAX-owe / oczekuje JSON.
     */
    protected function wantsJson(): bool
    {
        $xhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return $xhr || str_contains($accept, 'application/json');
    }

    /**
     * Weryfikuje token CSRF lub rzuca wyjątek 403.
     */
    protected function verifyCsrf(): void
    {
        if (!$this->csrf->verifyRequest()) {
            throw HttpException::forbidden('Nieprawidłowy token CSRF.');
        }
    }

    protected function clientIp(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** Odrzuca żądania bez nagłówka AJAX / Accept JSON (endpointy /ajax/*). */
    protected function rejectUnlessAjax(): ?Response
    {
        if (!$this->wantsJson()) {
            return $this->json(['success' => false, 'error' => 'Niedozwolone.'], 403);
        }

        return null;
    }

    protected function searchRateLimitMessage(): ?string
    {
        $ip = $this->clientIp();
        if (!$this->rateLimiter->attempt('search_ip', $ip)) {
            return 'Zbyt wiele zapytań. Spróbuj za chwilę.';
        }

        $identifier = $ip . '|' . session_id();
        if (!$this->rateLimiter->attempt('search', $identifier)) {
            $retry = $this->rateLimiter->retryAfter('search', $identifier);

            return "Limit wyszukiwania. Spróbuj za {$retry} s.";
        }

        return null;
    }

    protected function feedRateLimitMessage(): ?string
    {
        $ip = $this->clientIp();
        if (!$this->rateLimiter->attempt('feed_ip', $ip)) {
            return 'Zbyt wiele żądań. Spróbuj za chwilę.';
        }

        $identifier = $ip . '|' . session_id();
        if (!$this->rateLimiter->attempt('feed', $identifier)) {
            $retry = $this->rateLimiter->retryAfter('feed', $identifier);

            return "Limit ładowania tablicy. Spróbuj za {$retry} s.";
        }

        return null;
    }

    protected function isValidSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[a-z0-9-]{1,80}$/', $slug) === 1;
    }

    /**
     * @param array<int, array<string, mixed>> $stories
     * @return array<int, int> story_id => ocena użytkownika
     */
    protected function loadUserRatings(array $stories): array
    {
        if ($stories === []) {
            return [];
        }

        $ids = array_map(static fn (array $s): int => (int)$s['id'], $stories);
        $userId = $this->auth->id();
        $ipHash = null;
        if ($userId === null) {
            $salt = (string)Config::get('security.ip_salt', '');
            $ipHash = hash('sha256', $salt . '|' . $this->clientIp());
        }

        return (new \App\Models\Rating($this->db))->mapUserRatings($ids, $userId, $ipHash);
    }
}
