<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\AuditLog;
use App\Models\Story;

/**
 * Pulpit panelu admina - statystyki ogólne.
 */
final class DashboardController extends AdminController
{
    public function index(): Response
    {
        $stories = new Story($this->db);

        $stats = [
            'stories_total'     => $stories->countByStatus(null),
            'stories_published' => $stories->countByStatus(Story::STATUS_PUBLISHED),
            'stories_pending'   => $stories->countByStatus(Story::STATUS_PENDING),
            'stories_rejected'  => $stories->countByStatus(Story::STATUS_REJECTED),
            'users_total'       => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `users`"),
            'ratings_total'     => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `ratings`"),
            'categories_total'  => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `categories`"),
        ];

        $latest = $stories->forAdmin(null, 8, 0);
        $auditLogs = (new AuditLog($this->db))->recent(10);

        return $this->view('admin/dashboard', [
            'seo'       => $this->adminSeo('Pulpit'),
            'stats'     => $stats,
            'latest'    => $latest,
            'auditLogs' => $auditLogs,
        ]);
    }
}
