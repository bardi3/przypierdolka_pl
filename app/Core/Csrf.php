<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Generowanie i weryfikacja tokenów CSRF (synchronizer token pattern).
 */
final class Csrf
{
    private Session $session;
    private string $tokenName;
    private string $header;
    private int $lifetime;

    /**
     * @param array<string, mixed> $config sekcja security.csrf
     */
    public function __construct(Session $session, array $config = [])
    {
        $this->session   = $session;
        $this->tokenName = $config['token_name'] ?? '_csrf';
        $this->header    = $config['header'] ?? 'X-CSRF-Token';
        $this->lifetime  = (int)($config['lifetime'] ?? 7200);
    }

    public function tokenName(): string
    {
        return $this->tokenName;
    }

    public function token(): string
    {
        $data = $this->session->get('_csrf');
        if (
            is_array($data)
            && isset($data['value'], $data['expires'])
            && $data['expires'] > time()
        ) {
            return $data['value'];
        }

        $token = bin2hex(random_bytes(32));
        $this->session->set('_csrf', [
            'value'   => $token,
            'expires' => time() + $this->lifetime,
        ]);
        return $token;
    }

    /**
     * Ukryte pole formularza z tokenem.
     */
    public function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($this->tokenName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8')
        );
    }

    public function verify(?string $token): bool
    {
        $data = $this->session->get('_csrf');
        if (!is_array($data) || empty($data['value']) || ($data['expires'] ?? 0) < time()) {
            return false;
        }
        if (!is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($data['value'], $token);
    }

    /**
     * Weryfikacja na podstawie aktualnego żądania (POST lub nagłówek).
     */
    public function verifyRequest(): bool
    {
        $token = $_POST[$this->tokenName] ?? null;

        if ($token === null) {
            $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $this->header));
            $token = $_SERVER[$headerKey] ?? null;
        }

        return $this->verify(is_string($token) ? $token : null);
    }
}
