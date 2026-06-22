<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Log audytu akcji moderatora/admina.
 */
final class AuditLog extends Model
{
    protected string $table = 'audit_logs';

    /**
     * @param array<string, mixed>|null $details
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        int $entityId,
        ?array $details = null,
        ?string $ipAddress = null
    ): int {
        return $this->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'details'     => $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'  => $ipAddress,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, u.username
             FROM `audit_logs` a
             LEFT JOIN `users` u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }
}
