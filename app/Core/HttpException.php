<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Wyjątek HTTP z kodem statusu - mapowany na stronę błędu.
 */
class HttpException extends RuntimeException
{
    private int $statusCode;
    private bool $exposeMessage;

    public function __construct(
        int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        bool $exposeMessage = true
    ) {
        $this->statusCode = $statusCode;
        $this->exposeMessage = $exposeMessage;
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode), 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** Komunikat bezpieczny do wyświetlenia użytkownikowi (bez ścieżek, tras, klas). */
    public function getDisplayMessage(): string
    {
        if ($this->exposeMessage) {
            return $this->getMessage();
        }

        return self::defaultMessage($this->statusCode);
    }

    public static function notFound(string $message = '', bool $expose = true): self
    {
        return new self(404, $message, null, $expose);
    }

    public static function forbidden(string $message = '', bool $expose = true): self
    {
        return new self(403, $message, null, $expose);
    }

    public static function tooManyRequests(string $message = '', bool $expose = true): self
    {
        return new self(429, $message, null, $expose);
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'Nieprawidłowe żądanie.',
            403 => 'Brak dostępu.',
            404 => 'Strona nie została znaleziona.',
            429 => 'Zbyt wiele żądań.',
            default => 'Błąd serwera.',
        };
    }
}
