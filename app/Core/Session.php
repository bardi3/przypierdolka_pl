<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Wrapper na sesję PHP z bezpiecznymi ustawieniami cookie,
 * obsługą flash messages i regeneracją ID (ochrona przed session fixation).
 */
final class Session
{
    private bool $started = false;

    /**
     * @param array<string, mixed> $config sekcja security.session
     */
    public function start(array $config = []): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name($config['name'] ?? 'PRZYPID');
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => (int)($config['lifetime'] ?? 0),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => (bool)($config['cookie_httponly'] ?? true),
            'samesite' => $config['cookie_samesite'] ?? 'Lax',
        ]);

        session_start();
        $this->started = true;

        // Czyszczenie flash z poprzedniego żądania
        $this->ageFlash();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * Ochrona przed session fixation - po logowaniu.
     */
    public function regenerate(bool $deleteOld = true): void
    {
        if ($this->started) {
            session_regenerate_id($deleteOld);
        }
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $this->started = false;
    }

    // --- Flash messages ---

    public function flash(string $type, string $message): void
    {
        $_SESSION['_flash']['new'][$type][] = $message;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getFlashes(): array
    {
        return $_SESSION['_flash']['old'] ?? [];
    }

    private function ageFlash(): void
    {
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['new'] = [];
    }
}
