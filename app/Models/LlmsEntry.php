<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Wpisy do pliku /llms.txt (ręczne i synchronizowane z serwisem).
 */
final class LlmsEntry extends Model
{
    protected string $table = 'llms_entries';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allOrdered(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `llms_entries`
             ORDER BY `is_optional` ASC, `section` ASC, `sort_order` ASC, `id` ASC"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeOrdered(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `llms_entries`
             WHERE `is_active` = 1
             ORDER BY `is_optional` ASC, `section` ASC, `sort_order` ASC, `id` ASC"
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findEntry(int $id): ?array
    {
        return $this->find($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByKey(string $entryKey): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `llms_entries` WHERE `entry_key` = ? LIMIT 1",
            [$entryKey]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createEntry(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateEntry(int $id, array $data): void
    {
        $this->update($id, $data);
    }

    public function deleteEntry(int $id): void
    {
        $this->delete($id);
    }

    /**
     * @param array<int, string> $activeKeys
     */
    public function deactivateSystemExcept(array $activeKeys): void
    {
        if ($activeKeys === []) {
            $this->db->execute("UPDATE `llms_entries` SET `is_active` = 0 WHERE `is_system` = 1");
            return;
        }

        $placeholders = implode(',', array_fill(0, count($activeKeys), '?'));
        $params = $activeKeys;
        $this->db->execute(
            "UPDATE `llms_entries` SET `is_active` = 0
             WHERE `is_system` = 1 AND (`entry_key` IS NULL OR `entry_key` NOT IN ({$placeholders}))",
            $params
        );
    }
}
