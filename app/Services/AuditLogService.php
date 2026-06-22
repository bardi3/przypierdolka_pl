<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;

/**
 * Serwis logowania akcji moderacji i admina.
 */
final class AuditLogService
{
    private AuditLog $logs;

    public function __construct(AuditLog $logs)
    {
        $this->logs = $logs;
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public function record(
        ?int $userId,
        string $action,
        string $entityType,
        int $entityId,
        ?array $details = null,
        ?string $ipAddress = null
    ): void {
        $this->logs->log($userId, $action, $entityType, $entityId, $details, $ipAddress);
    }
}
