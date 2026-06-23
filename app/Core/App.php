<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Friendship;
use App\Models\User;
use Throwable;

/**
 * Rdzeń aplikacji - bootstrap, kontener usług i obsługa żądania.
 */
final class App
{
    private static ?App $instance = null;

    /** @var array<string, object> */
    private array $services = [];

    private Router $router;

    private function __construct()
    {
    }

    public static function boot(string $basePath): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $app = new self();
        self::$instance = $app;

        // Konfiguracja
        Config::load($basePath . '/config');
        Config::set('app.paths.base', $basePath);
        self::syncAppUrlScheme();

        date_default_timezone_set((string)Config::get('app.timezone', 'Europe/Warsaw'));
        mb_internal_encoding((string)Config::get('app.charset', 'UTF-8'));

        $app->registerErrorHandling();
        $app->registerServices($basePath);

        return $app;
    }

    /** Gdy żądanie jest po HTTPS, a app.url ma http:// — naprawia mixed content (obrazki, OG). */
    private static function syncAppUrlScheme(): void
    {
        $url = (string)Config::get('app.url', '');
        if ($url === '' || !str_starts_with($url, 'http://')) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');

        if ($isHttps) {
            Config::set('app.url', 'https://' . substr($url, 7));
        }
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Aplikacja nie została zbootowana.');
        }
        return self::$instance;
    }

    private function registerServices(string $basePath): void
    {
        // Baza danych
        $db = Database::init((array)Config::get('database', []));
        $this->set('db', $db);

        // Sesja
        $session = new Session();
        $session->start((array)Config::get('security.session', []));
        $this->set('session', $session);

        // Cache
        $cache = new Cache(
            (string)Config::get('app.paths.cache'),
            (bool)Config::get('app.cache.enabled', true),
            (int)Config::get('app.cache.ttl', 300)
        );
        $this->set('cache', $cache);

        // CSRF
        $csrf = new Csrf($session, (array)Config::get('security.csrf', []));
        $this->set('csrf', $csrf);

        // Rate limiter
        $rateLimiter = new RateLimiter($cache, (array)Config::get('security.rate_limits', []));
        $this->set('rateLimiter', $rateLimiter);

        // Auth
        $auth = new Auth($session, new User($db));
        $this->set('auth', $auth);

        // Widok
        $view = new View((string)Config::get('app.paths.templates'));
        $this->shareViewGlobals($view, $auth, $csrf, $session);
        $this->set('view', $view);

        // Router
        $this->router = new Router($this);
        $this->router->loadRoutes($basePath . '/config/routes.php');
    }

    private function shareViewGlobals(View $view, Auth $auth, Csrf $csrf, Session $session): void
    {
        $view->share([
            'auth'        => $auth,
            'csrf'        => $csrf,
            'session'     => $session,
            'app_name'    => (string)Config::get('app.name'),
            'app_tagline' => (string)Config::get('app.tagline'),
            'app_env'     => (string)Config::get('app.env', 'production'),
            'base_url'    => rtrim((string)Config::get('app.url'), '/'),
            'admin_prefix'=> (string)Config::get('app.admin_prefix', '/admineu'),
            'assets_ver'  => (string)Config::get('app.assets_version', '1'),
            'pending_friend_invites' => 0,
        ]);
    }

    private function refreshViewGlobals(Auth $auth): void
    {
        $pendingFriendInvites = 0;
        if ($auth->check()) {
            /** @var Database $db */
            $db = $this->get('db');
            $pendingFriendInvites = (new Friendship($db))->countPendingIncoming((int)$auth->id());
        }

        /** @var View $view */
        $view = $this->get('view');
        $view->share(['pending_friend_invites' => $pendingFriendInvites]);
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = '/' . trim(rawurldecode($uri), '/');
        if ($uri === '/' . '') {
            $uri = '/';
        }

        try {
            /** @var Auth $auth */
            $auth = $this->get('auth');
            $auth->refreshFromDatabase();
            $this->refreshViewGlobals($auth);

            $response = $this->router->dispatch($method, $uri);
        } catch (Throwable $e) {
            $response = $this->handleException($e);
        }

        $response->send();
    }

    private function handleException(Throwable $e): Response
    {
        $this->log($e);

        $status = $e instanceof HttpException ? $e->getStatusCode() : 500;
        $message = $e instanceof HttpException
            ? $e->getDisplayMessage()
            : 'Wystąpił nieoczekiwany błąd. Spróbuj ponownie później.';

        try {
            /** @var View $view */
            $view = $this->get('view');
            $html = $view->render('errors/error', [
                'status'  => $status,
                'message' => $message,
            ], 'layout/main');
            return Response::html($html, $status);
        } catch (Throwable) {
            return Response::html('<h1>' . $status . '</h1><p>Błąd serwera.</p>', $status);
        }
    }

    private function registerErrorHandling(): void
    {
        $debug = (bool)Config::get('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', (string)Config::get('app.paths.logs') . '/php-error.log');
    }

    public function log(Throwable $e): void
    {
        $logFile = (string)Config::get('app.paths.logs') . '/app-' . date('Y-m-d') . '.log';
        $line = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    // --- Kontener usług ---

    public function set(string $key, object $service): void
    {
        $this->services[$key] = $service;
    }

    public function get(string $key): object
    {
        if (!isset($this->services[$key])) {
            throw new \RuntimeException("Usługa nieznana: {$key}");
        }
        return $this->services[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->services[$key]);
    }

    public function router(): Router
    {
        return $this->router;
    }
}
