<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Ładowanie i dostęp do konfiguracji z katalogu config/.
 * Obsługuje notację kropkową: Config::get('app.url').
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    private static string $configPath = '';

    public static function load(string $configPath): void
    {
        self::$configPath = rtrim($configPath, '/');

        foreach (glob(self::$configPath . '/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            if ($name === 'routes' || $name === 'local') {
                continue; // routes ładowane osobno; local nadpisuje niżej
            }
            self::$items[$name] = require $file;
        }

        // Nadpisania lokalne (nie commitowane)
        $local = self::$configPath . '/local.php';
        if (is_file($local)) {
            $overrides = require $local;
            if (is_array($overrides)) {
                self::$items = array_replace_recursive(self::$items, $overrides);
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &self::$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
                break;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return self::$items;
    }
}
