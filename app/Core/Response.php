<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pomocnik do generowania odpowiedzi HTTP (HTML / JSON / redirect).
 */
final class Response
{
    private int $status = 200;
    private string $body = '';
    /** @var array<string, string> */
    private array $headers = [];

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public static function html(string $html, int $status = 200): self
    {
        return (new self())
            ->setStatus($status)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($html);
    }

    /**
     * @param mixed $data
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return (new self())
            ->setStatus($status)
            ->header('Content-Type', 'application/json; charset=UTF-8')
            ->setBody($json === false ? '{}' : $json);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return (new self())
            ->setStatus($status)
            ->header('Location', $url);
    }

    public static function text(string $text, int $status = 200, string $contentType = 'text/plain'): self
    {
        return (new self())
            ->setStatus($status)
            ->header('Content-Type', $contentType . '; charset=UTF-8')
            ->setBody($text);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $this->body;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
