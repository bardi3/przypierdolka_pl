<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bazowa klasa modelu. Cienka warstwa nad Database, bez ORM.
 * Modele potomne definiują $table i ewentualne metody domenowe.
 */
abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>|null
     */
    public function findBy(array $conditions): ?array
    {
        [$where, $params] = $this->buildWhere($conditions);
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` {$where} LIMIT 1",
            $params
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'id DESC', int $limit = 100, int $offset = 0): array
    {
        $orderBy = $this->sanitizeOrderBy($orderBy);
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
        $cols = implode(', ', array_map(static fn ($c) => "`{$c}`", $columns));
        $vals = implode(', ', $placeholders);

        $this->db->query(
            "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$vals})",
            $this->bindable($data)
        );
        return $this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): int
    {
        if ($data === []) {
            return 0;
        }
        $set = implode(', ', array_map(static fn ($c) => "`{$c}` = :{$c}", array_keys($data)));
        $params = $this->bindable($data);
        $params['_pk'] = $id;

        return $this->db->execute(
            "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = :_pk",
            $params
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    public function count(string $where = '', array $params = []): int
    {
        if ($where !== '' && !preg_match('/^[a-z0-9_`.=<>?\sAND]+$/i', $where)) {
            throw new \InvalidArgumentException('Niedozwolony warunek WHERE.');
        }
        $clause = $where !== '' ? "WHERE {$where}" : '';
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `{$this->table}` {$clause}",
            $params
        );
    }

    /**
     * Whitelist dla ORDER BY — ochrona przed SQL injection w przyszłych wywołaniach.
     */
    protected function sanitizeOrderBy(string $orderBy): string
    {
        if (!preg_match('/^[a-z0-9_`.]+(?:\s+(?:ASC|DESC))?(?:,\s*[a-z0-9_`.]+(?:\s+(?:ASC|DESC))?)*$/i', $orderBy)) {
            return '`id` DESC';
        }
        return $orderBy;
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array{0:string, 1:array<int, mixed>}
     */
    protected function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        foreach ($conditions as $col => $value) {
            $parts[] = "`{$col}` = ?";
            $params[] = $value;
        }
        return ['WHERE ' . implode(' AND ', $parts), $params];
    }

    /**
     * Prefiksuje klucze dwukropkiem do nazwanych placeholderów.
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function bindable(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[$key] = $value;
        }
        return $out;
    }

    public function db(): Database
    {
        return $this->db;
    }
}
