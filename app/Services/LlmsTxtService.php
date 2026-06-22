<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Config;
use App\Models\Category;
use App\Models\LlmsEntry;
use App\Models\Setting;
use App\Models\Story;

/**
 * Budowa pliku /llms.txt (Markdown) oraz synchronizacja wpisów systemowych.
 */
final class LlmsTxtService
{
    private const CACHE_KEY = 'llms:document';
    private const SECTION_ORDER = ['Serwis', 'Rankingi', 'Kategorie', 'Historie'];

    private LlmsEntry $entries;
    private Setting $settings;
    private Story $stories;
    private Category $categories;
    private Cache $cache;
    private string $baseUrl;

    public function __construct(
        LlmsEntry $entries,
        Setting $settings,
        Story $stories,
        Category $categories,
        Cache $cache
    ) {
        $this->entries = $entries;
        $this->settings = $settings;
        $this->stories = $stories;
        $this->categories = $categories;
        $this->cache = $cache;
        $this->baseUrl = rtrim((string)Config::get('app.url'), '/');
    }

    public function render(): string
    {
        if ($this->entries->allOrdered() === []) {
            $this->syncSystemEntries(false);
        }

        return $this->cache->remember(self::CACHE_KEY, function (): string {
            return $this->buildDocument();
        }, 600);
    }

    public function clearCache(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * Uzupełnia brakujące wpisy z serwisu; opcjonalnie nadpisuje opisy systemowe.
     */
    public function syncSystemEntries(bool $overwriteDescriptions = false): int
    {
        $items = $this->collectSystemEntries();
        $activeKeys = [];
        $changed = 0;

        foreach ($items as $item) {
            $key = (string)$item['entry_key'];
            $activeKeys[] = $key;
            $existing = $this->entries->findByKey($key);

            if ($existing === null) {
                $this->entries->createEntry($item);
                $changed++;
                continue;
            }

            $update = [
                'section'     => $item['section'],
                'title'       => $item['title'],
                'url'         => $item['url'],
                'sort_order'  => $item['sort_order'],
                'is_optional' => $item['is_optional'],
                'is_active'   => 1,
                'is_system'   => 1,
            ];

            if ($overwriteDescriptions || ($existing['description'] ?? '') === '') {
                $update['description'] = $item['description'];
            }

            $needsUpdate = false;
            foreach (['section', 'title', 'url', 'sort_order', 'is_optional'] as $field) {
                if ((string)($existing[$field] ?? '') !== (string)$update[$field]) {
                    $needsUpdate = true;
                    break;
                }
            }
            if ($overwriteDescriptions && (string)($existing['description'] ?? '') !== (string)($update['description'] ?? '')) {
                $needsUpdate = true;
            }
            if ((int)($existing['is_active'] ?? 0) !== 1) {
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $this->entries->updateEntry((int)$existing['id'], $update);
                $changed++;
            }
        }

        $this->entries->deactivateSystemExcept($activeKeys);
        $this->clearCache();

        return $changed;
    }

    private function buildDocument(): string
    {
        $siteName = $this->settings->get('site_title') ?: (string)Config::get('app.name', 'przypierdolka.pl');
        $summary = trim((string)($this->settings->get('llms_summary')
            ?: $this->settings->get('site_description')
            ?: Config::get('app.tagline', '')));
        $body = trim((string)($this->settings->get('llms_body') ?? ''));

        $lines = ['# ' . $this->escapeMarkdownInline($siteName), ''];

        if ($summary !== '') {
            $lines[] = '> ' . $this->escapeMarkdownInline($summary);
            $lines[] = '';
        }

        if ($body !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $paragraph) {
                $paragraph = trim($paragraph);
                if ($paragraph === '') {
                    continue;
                }
                $lines[] = $this->escapeMarkdownInline($paragraph);
                $lines[] = '';
            }
        }

        $optional = [];
        $sections = [];

        foreach ($this->entries->activeOrdered() as $entry) {
            if ((int)($entry['is_optional'] ?? 0) === 1) {
                $optional[] = $entry;
                continue;
            }
            $section = trim((string)$entry['section']);
            if ($section === '') {
                $section = 'Inne';
            }
            $sections[$section][] = $entry;
        }

        $orderedSections = [];
        foreach (self::SECTION_ORDER as $name) {
            if (!empty($sections[$name])) {
                $orderedSections[$name] = $sections[$name];
                unset($sections[$name]);
            }
        }
        ksort($sections);
        foreach ($sections as $name => $items) {
            $orderedSections[$name] = $items;
        }

        foreach ($orderedSections as $sectionName => $items) {
            $lines[] = '## ' . $this->escapeMarkdownInline($sectionName);
            $lines[] = '';
            foreach ($items as $item) {
                $lines[] = $this->formatListItem($item);
            }
            $lines[] = '';
        }

        if ($optional !== []) {
            $lines[] = '## Optional';
            $lines[] = '';
            foreach ($optional as $item) {
                $lines[] = $this->formatListItem($item);
            }
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = '';
        $lines[] = '- [Sitemap](' . $this->absoluteUrl('/sitemap.xml') . '): mapa XML wszystkich publicznych URL';
        $lines[] = '- [Robots.txt](' . $this->absoluteUrl('/robots.txt') . '): reguły indeksowania';

        return rtrim(implode("\n", $lines)) . "\n";
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function formatListItem(array $entry): string
    {
        $title = $this->escapeMarkdownInline((string)$entry['title']);
        $url = $this->absoluteUrl((string)$entry['url']);
        $line = '- [' . $title . '](' . $url . ')';

        $description = trim((string)($entry['description'] ?? ''));
        if ($description !== '') {
            $line .= ': ' . $this->escapeMarkdownInline($description);
        }

        return $line;
    }

    private function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return $this->baseUrl . '/' . ltrim($url, '/');
    }

    private function escapeMarkdownInline(string $text): string
    {
        return str_replace(["\r", "\n"], ' ', $text);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectSystemEntries(): array
    {
        $items = [];
        $sort = 0;

        $static = [
            ['nav:home', 'Serwis', 'Strona główna', '/', 'Najnowsze opublikowane historie i nawigacja po serwisie.', 0],
            ['nav:categories', 'Serwis', 'Kategorie', '/kategorie', 'Lista kategorii tematycznych.', 1],
            ['nav:add', 'Serwis', 'Dodaj historię', '/dodaj', 'Formularz dodawania własnej historii (gość lub zalogowany).', 2],
            ['nav:top-week', 'Rankingi', 'Top tygodnia', '/top/tydzien', 'Najlepiej oceniane historie z ostatnich 7 dni.', 0],
            ['nav:top-month', 'Rankingi', 'Top miesiąca', '/top/miesiac', 'Najlepiej oceniane historie z ostatnich 30 dni.', 1],
            ['nav:top-all', 'Rankingi', 'Top wszystkich czasów', '/top/wszystkie', 'Ranking wszech czasów — wymaga minimum ocen.', 2],
            ['nav:random', 'Serwis', 'Losowa historia', '/historia/losowa', 'Przekierowanie do losowej opublikowanej historii.', 3],
            ['nav:login', 'Optional', 'Logowanie', '/logowanie', 'Logowanie użytkownika.', 0, true],
            ['nav:register', 'Optional', 'Rejestracja', '/rejestracja', 'Założenie konta użytkownika.', 1, true],
        ];

        foreach ($static as $row) {
            $items[] = $this->systemItem(
                $row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
                $row[5],
                (bool)($row[6] ?? false)
            );
        }

        $categories = $this->categories->allWithCounts();
        foreach ($categories as $i => $cat) {
            $slug = (string)$cat['slug'];
            $desc = trim((string)($cat['description'] ?? ''));
            if ($desc === '') {
                $desc = 'Historie w kategorii ' . (string)$cat['name'] . '.';
            }
            $items[] = $this->systemItem(
                'category:' . $slug,
                'Kategorie',
                (string)$cat['name'],
                '/kategoria/' . $slug,
                $desc,
                $i,
                false
            );
            $items[] = $this->systemItem(
                'category-top:' . $slug,
                'Rankingi',
                'Top kategorii: ' . (string)$cat['name'],
                '/kategoria/' . $slug . '/top',
                'Najlepiej oceniane historie w kategorii ' . (string)$cat['name'] . '.',
                $i,
                false
            );
        }

        $limit = max(1, (int)$this->settings->get('llms_stories_limit', '200'));
        $stories = $this->stories->published('newest', $limit, 0);
        foreach ($stories as $i => $story) {
            $excerpt = trim((string)($story['excerpt'] ?? ''));
            if ($excerpt === '') {
                $excerpt = mb_strimwidth(trim(strip_tags((string)($story['content'] ?? ''))), 0, 160, '…');
            }
            $cat = !empty($story['category_name']) ? ' Kategoria: ' . (string)$story['category_name'] . '.' : '';
            $items[] = $this->systemItem(
                'story:' . (int)$story['id'],
                'Historie',
                (string)$story['title'],
                '/historia/' . (string)$story['slug'],
                $excerpt . $cat,
                $i,
                false
            );
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function systemItem(
        string $key,
        string $section,
        string $title,
        string $url,
        string $description,
        int $sortOrder,
        bool $optional
    ): array {
        return [
            'entry_key'    => $key,
            'section'      => $section,
            'title'        => $title,
            'url'          => $url,
            'description'  => $description,
            'sort_order'   => $sortOrder,
            'is_optional'  => $optional ? 1 : 0,
            'is_active'    => 1,
            'is_system'    => 1,
        ];
    }
}
