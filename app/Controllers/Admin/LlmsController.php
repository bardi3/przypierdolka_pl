<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Category;
use App\Models\LlmsEntry;
use App\Models\Setting;
use App\Models\Story;
use App\Services\LlmsTxtService;

/**
 * Zarządzanie plikiem /llms.txt w panelu admina.
 */
final class LlmsController extends AdminController
{
    private LlmsEntry $entries;
    private Setting $settings;
    private LlmsTxtService $llms;

    public function __construct(\App\Core\App $app)
    {
        parent::__construct($app);
        $this->entries = new LlmsEntry($this->db);
        $this->settings = new Setting($this->db);
        $this->llms = new LlmsTxtService(
            $this->entries,
            $this->settings,
            new Story($this->db),
            new Category($this->db),
            $this->cache
        );
    }

    public function index(): Response
    {
        if ($this->entries->allOrdered() === []) {
            $this->llms->syncSystemEntries(false);
        }

        return $this->view('admin/llms/index', [
            'seo'     => $this->adminSeo('llms.txt'),
            'entries' => $this->entries->allOrdered(),
            'meta'    => [
                'summary'      => $this->settings->get('llms_summary', ''),
                'body'         => $this->settings->get('llms_body', ''),
                'stories_limit'=> $this->settings->get('llms_stories_limit', '200'),
            ],
            'previewUrl' => rtrim((string)Config::get('app.url'), '/') . '/llms.txt',
        ]);
    }

    public function updateMeta(): Response
    {
        $this->verifyCsrf();

        $data = [
            'llms_summary'       => trim((string)$this->input('llms_summary', '')),
            'llms_body'          => trim((string)$this->input('llms_body', '')),
            'llms_stories_limit' => trim((string)$this->input('llms_stories_limit', '200')),
        ];

        $validator = new Validator($data, [
            'llms_summary'       => 'podsumowanie',
            'llms_body'          => 'treść wprowadzenia',
            'llms_stories_limit' => 'limit historii',
        ]);
        $validator->validate([
            'llms_summary'       => 'required|max:500',
            'llms_body'          => 'max:3000',
            'llms_stories_limit' => 'required|int',
        ]);

        $limit = (int)$data['llms_stories_limit'];
        if ($limit < 1 || $limit > 2000) {
            $validator->addError('llms_stories_limit', 'Limit historii musi być między 1 a 2000.');
        }

        if ($validator->fails()) {
            $this->session->flash('error', 'Popraw błędy w ustawieniach llms.txt.');
            return $this->view('admin/llms/index', [
                'seo'     => $this->adminSeo('llms.txt'),
                'entries' => $this->entries->allOrdered(),
                'meta'    => [
                    'summary'       => $data['llms_summary'],
                    'body'          => $data['llms_body'],
                    'stories_limit' => $data['llms_stories_limit'],
                ],
                'previewUrl' => rtrim((string)Config::get('app.url'), '/') . '/llms.txt',
                'errors'     => $validator->errors(),
            ], 422);
        }

        $this->settings->setMany([
            'llms_summary'       => $data['llms_summary'],
            'llms_body'          => $data['llms_body'],
            'llms_stories_limit' => (string)$limit,
        ]);
        $this->llms->clearCache();

        $this->session->flash('success', 'Ustawienia llms.txt zapisane.');
        return $this->redirect($this->adminUrl('llms'));
    }

    public function sync(): Response
    {
        $this->verifyCsrf();
        $overwrite = (string)$this->input('overwrite_descriptions', '0') === '1';
        $count = $this->llms->syncSystemEntries($overwrite);

        $this->session->flash(
            'success',
            $count > 0
                ? "Synchronizacja zakończona — zaktualizowano {$count} wpis(ów)."
                : 'Synchronizacja zakończona — brak zmian.'
        );

        return $this->redirect($this->adminUrl('llms'));
    }

    public function create(): Response
    {
        return $this->view('admin/llms/edit', [
            'seo'   => $this->adminSeo('Nowy wpis llms.txt'),
            'entry' => null,
            'errors'=> [],
        ]);
    }

    public function store(): Response
    {
        $this->verifyCsrf();
        return $this->saveEntry(null);
    }

    public function edit(string $id): Response
    {
        $entry = $this->entries->findEntry((int)$id);
        if ($entry === null) {
            throw HttpException::notFound('Wpis nie istnieje.');
        }

        return $this->view('admin/llms/edit', [
            'seo'   => $this->adminSeo('Edycja wpisu llms.txt'),
            'entry' => $entry,
            'errors'=> [],
        ]);
    }

    public function update(string $id): Response
    {
        $this->verifyCsrf();
        $entry = $this->entries->findEntry((int)$id);
        if ($entry === null) {
            throw HttpException::notFound('Wpis nie istnieje.');
        }

        return $this->saveEntry($entry);
    }

    public function delete(string $id): Response
    {
        $this->verifyCsrf();
        $entry = $this->entries->findEntry((int)$id);
        if ($entry === null) {
            throw HttpException::notFound('Wpis nie istnieje.');
        }

        $this->entries->deleteEntry((int)$id);
        $this->llms->clearCache();

        $this->session->flash('success', 'Wpis usunięty.');
        return $this->redirect($this->adminUrl('llms'));
    }

    /**
     * @param array<string, mixed>|null $entry
     */
    private function saveEntry(?array $entry): Response
    {
        $data = [
            'section'      => trim((string)$this->input('section', '')),
            'title'        => trim((string)$this->input('title', '')),
            'url'          => trim((string)$this->input('url', '')),
            'description'  => trim((string)$this->input('description', '')),
            'sort_order'   => (int)$this->input('sort_order', 0),
            'is_optional'  => (string)$this->input('is_optional', '0') === '1' ? 1 : 0,
            'is_active'    => (string)$this->input('is_active', '1') === '1' ? 1 : 0,
        ];

        $validator = new Validator($data, [
            'section'     => 'sekcja',
            'title'       => 'tytuł',
            'url'         => 'URL',
            'description' => 'opis',
        ]);
        $validator->validate([
            'section'     => 'required|max:80',
            'title'       => 'required|max:200',
            'url'         => 'required|max:500',
            'description' => 'max:500',
        ]);

        if ($data['url'] !== ''
            && !str_starts_with($data['url'], '/')
            && !filter_var($data['url'], FILTER_VALIDATE_URL)
        ) {
            $validator->addError('url', 'Podaj ścieżkę (/strona) lub pełny URL (https://…).');
        }

        if ($validator->fails()) {
            $merged = $entry !== null ? array_merge($entry, $data) : $data;
            return $this->view('admin/llms/edit', [
                'seo'    => $this->adminSeo($entry === null ? 'Nowy wpis llms.txt' : 'Edycja wpisu llms.txt'),
                'entry'  => $merged,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($entry === null) {
            $data['entry_key'] = null;
            $data['is_system'] = 0;
            $this->entries->createEntry($data);
            $this->session->flash('success', 'Wpis dodany.');
        } else {
            $this->entries->updateEntry((int)$entry['id'], $data);
            $this->session->flash('success', 'Wpis zaktualizowany.');
        }

        $this->llms->clearCache();
        return $this->redirect($this->adminUrl('llms'));
    }
}
