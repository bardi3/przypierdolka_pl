<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Response;
use App\Models\Category;
use App\Models\LlmsEntry;
use App\Models\Setting;
use App\Models\Story;
use App\Services\LlmsTxtService;

/**
 * Publiczny endpoint /llms.txt dla modeli językowych (Markdown).
 */
final class LlmsTxtController extends Controller
{
    private LlmsTxtService $llms;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->llms = new LlmsTxtService(
            new LlmsEntry($this->db),
            new Setting($this->db),
            new Story($this->db),
            new Category($this->db),
            $this->cache
        );
    }

    public function show(): Response
    {
        $body = $this->llms->render();

        return Response::text($body, 200, 'text/plain')
            ->header('Cache-Control', 'public, max-age=600');
    }
}
