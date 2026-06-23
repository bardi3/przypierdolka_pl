<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Validator;
use App\Models\Setting;

/**
 * Ustawienia serwisu w panelu admina.
 */
final class SettingsController extends AdminController
{
    private Setting $settings;

    /** Klucze ustawień obsługiwane w formularzu. */
    private const KEYS = [
        'site_title',
        'site_description',
        'meta_keywords',
        'home_feed_per_page',
        'stories_require_moderation',
        'social_facebook',
        'social_instagram',
    ];

    public function __construct(\App\Core\App $app)
    {
        parent::__construct($app);
        $this->settings = new Setting($this->db);
    }

    public function index(): Response
    {
        return $this->view('admin/settings/index', [
            'seo'      => $this->adminSeo('Ustawienia'),
            'settings' => $this->settings->allAsMap(),
            'keys'     => self::KEYS,
        ]);
    }

    public function update(): Response
    {
        $this->verifyCsrf();

        $pairs = [];
        foreach (self::KEYS as $key) {
            $pairs[$key] = trim((string)$this->input($key, ''));
        }

        $validator = new Validator($pairs, [
            'site_title'               => 'tytuł strony',
            'site_description'         => 'opis strony',
            'meta_keywords'            => 'słowa kluczowe',
            'home_feed_per_page'       => 'historie na stronie głównej',
            'stories_require_moderation' => 'moderacja historii',
            'social_facebook'          => 'Facebook',
            'social_instagram'         => 'Instagram',
        ]);
        $validator->validate([
            'site_title'               => 'required|max:100',
            'site_description'         => 'max:300',
            'meta_keywords'            => 'max:255',
            'home_feed_per_page'       => 'required|int',
            'stories_require_moderation' => 'required|in:0,1',
            'social_facebook'          => 'max:255',
            'social_instagram'         => 'max:255',
        ]);

        if ($pairs['social_facebook'] !== '' && !filter_var($pairs['social_facebook'], FILTER_VALIDATE_URL)) {
            $validator->addError('social_facebook', 'Podaj prawidłowy URL (https://…).');
        }
        if ($pairs['social_instagram'] !== '' && !filter_var($pairs['social_instagram'], FILTER_VALIDATE_URL)) {
            $validator->addError('social_instagram', 'Podaj prawidłowy URL (https://…).');
        }

        $feedPerPage = (int)$pairs['home_feed_per_page'];
        if ($feedPerPage < 3 || $feedPerPage > 30) {
            $validator->addError('home_feed_per_page', 'Liczba historii musi być od 3 do 30.');
        }

        if ($validator->fails()) {
            $this->session->flash('error', 'Popraw błędy w formularzu ustawień.');
            return $this->view('admin/settings/index', [
                'seo'      => $this->adminSeo('Ustawienia'),
                'settings' => array_merge($this->settings->allAsMap(), $pairs),
                'keys'     => self::KEYS,
                'errors'   => $validator->errors(),
            ], 422);
        }

        $this->settings->setMany($pairs);

        foreach (['stories', 'home', 'rankings', 'categories', 'admin'] as $prefix) {
            $this->cache->clearByPrefix($prefix);
        }

        $this->session->flash('success', 'Ustawienia zapisane.');
        return $this->redirect($this->adminUrl('settings'));
    }
}
