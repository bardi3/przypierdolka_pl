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

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode), 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function notFound(string $message = 'Strona nie została znaleziona.'): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = 'Brak dostępu.'): self
    {
        return new self(403, $message);
    }

    public static function tooManyRequests(string $message = 'Zbyt wiele żądań. Spróbuj później.'): self
    {
        return new self(429, $message);
    }

    private static function defaultMessage(int $status): string
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
