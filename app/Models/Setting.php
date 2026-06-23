<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Model ustawień serwisu (klucz => wartość).
 */
final class Setting extends Model
{
    protected string $table = 'settings';

    /** @var array<string, string>|null */
    private ?array $cache = null;

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->allAsMap();
        return $all[$key] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function allAsMap(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM `settings`");
        $map = [];
        foreach ($rows as $row) {
            $map[(string)$row['key']] = (string)$row['value'];
        }
        return $this->cache = $map;
    }

    public function set(string $key, string $value): void
    {
        $this->db->execute(
            "INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [$key, $value]
        );
        $this->cache = null;
    }

    /**
     * @param array<string, string> $pairs
     */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value);
        }
    }

    /** Liczba historii na stronie głównej (pierwsze wczytanie + kolejne partie AJAX). */
    public function homeFeedPerPage(): int
    {
        $value = (int)$this->get('home_feed_per_page', '5');

        return max(3, min(30, $value > 0 ? $value : 5));
    }
}
