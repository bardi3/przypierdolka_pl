<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Seo;
use App\Models\Story;

/**
 * Bazowy kontroler panelu admina - ustawia layout i wspólne dane nawigacji.
 */
abstract class AdminController extends Controller
{
    protected string $layout = 'layout/admin';

    public function __construct(App $app)
    {
        parent::__construct($app);

        // Liczba historii oczekujących na moderację (do badge'a w menu)
        $pending = $this->cache->remember('admin:pending_count', function (): int {
            return (new Story($this->db))->countByStatus(Story::STATUS_PENDING);
        }, 30);

        $this->view->share([
            'pendingCount' => $pending,
        ]);
    }

    protected function adminSeo(string $title): Seo
    {
        return (new Seo((string)Config::get('app.name') . ' — Panel', (string)Config::get('app.url')))
            ->setTitle($title)
            ->setRobots('noindex, nofollow');
    }

    protected function adminUrl(string $path = ''): string
    {
        $prefix = (string)Config::get('app.admin_prefix', '/admineu');
        return $prefix . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}
