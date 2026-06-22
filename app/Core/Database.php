<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Cienki wrapper na PDO z pomocniczymi metodami.
 * Wszystkie zapytania używają prepared statements.
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function init(array $config): self
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'] ?? 'mysql',
            $config['host'] ?? '127.0.0.1',
            (int)($config['port'] ?? 3306),
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                $config['options'] ?? []
            );
        } catch (\PDOException $e) {
            throw new RuntimeException('Błąd połączenia z bazą danych: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }

        self::$instance = new self($pdo);
        return self::$instance;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Database nie zostało zainicjalizowane. Wywołaj Database::init().');
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param array<string|int, mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Pojedynczy wiersz.
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Wszystkie wiersze.
     * @param array<string|int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Pojedyncza wartość (pierwsza kolumna pierwszego wiersza).
     * @param array<string|int, mixed> $params
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * @param array<string|int, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
