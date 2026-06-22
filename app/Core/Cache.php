<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Prosty cache plikowy (serializowane wartości + TTL).
 * Używany dla list strony głównej, kategorii i rankingów.
 */
final class Cache
{
    private string $path;
    private bool $enabled;
    private int $defaultTtl;

    public function __construct(string $path, bool $enabled = true, int $defaultTtl = 300)
    {
        $this->path = rtrim($path, '/');
        $this->enabled = $enabled;
        $this->defaultTtl = $defaultTtl;

        if (!is_dir($this->path)) {
            @mkdir($this->path, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->enabled) {
            return $default;
        }

        $file = $this->file($key);
        if (!is_file($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $data = @unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            return $default;
        }

        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $payload = serialize([
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value'   => $value,
        ]);

        return @file_put_contents($this->file($key), $payload, LOCK_EX) !== false;
    }

    /**
     * Pobierz z cache lub policz i zapisz (memoizacja).
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $sentinel = "\0__MISS__\0";
        $value = $this->get($key, $sentinel);
        if ($value !== $sentinel) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function forget(string $key): bool
    {
        $file = $this->file($key);
        return is_file($file) ? @unlink($file) : true;
    }

    /**
     * Czyści pliki cache pasujące do prefiksu klucza (np. "stories", "rankings").
     */
    public function clearByPrefix(string $prefix): void
    {
        $this->flush($prefix);
    }

    /**
     * Czyści cały cache lub pliki pasujące do prefiksu klucza.
     * @deprecated Użyj clearByPrefix()
     */
    public function flush(?string $prefix = null): void
    {
        foreach (glob($this->path . '/*.cache') ?: [] as $file) {
            if ($prefix === null) {
                @unlink($file);
                continue;
            }
            // Nie znamy oryginalnego klucza po hashu, więc prefiks-flush
            // realizujemy przez znaczniki w nazwie pliku (patrz file()).
            if (str_starts_with(basename($file), $this->safePrefix($prefix))) {
                @unlink($file);
            }
        }
    }

    private function file(string $key): string
    {
        // Zachowujemy czytelny prefiks do flush() + hash dla unikalności
        return $this->path . '/' . $this->safePrefix($key) . md5($key) . '.cache';
    }

    private function safePrefix(string $key): string
    {
        $prefix = preg_replace('/[^a-z0-9_]+/i', '_', explode(':', $key)[0]);
        return strtolower((string)$prefix) . '_';
    }
}
