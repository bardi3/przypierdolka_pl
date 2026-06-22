<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Buduje struktury schema.org (JSON-LD @graph) dla stron serwisu.
 */
final class SeoSchemaService
{
    private string $baseUrl;
    private string $siteName;
    private string $orgId;
    private string $websiteId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string)Config::get('app.url'), '/');
        $this->siteName = (string)Config::get('app.name');
        $this->orgId = $this->baseUrl . '/#organization';
        $this->websiteId = $this->baseUrl . '/#website';
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return array<string, mixed>
     */
    public function graph(array $nodes): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph'   => $nodes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationNode(): array
    {
        return [
            '@type' => 'Organization',
            '@id'   => $this->orgId,
            'name'  => $this->siteName,
            'url'   => $this->baseUrl,
            'logo'  => [
                '@type'      => 'ImageObject',
                '@id'        => $this->baseUrl . '/#logo',
                'url'        => $this->baseUrl . '/assets/img/logo.png',
                'contentUrl' => $this->baseUrl . '/assets/img/logo.png',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function websiteNode(?string $description = null): array
    {
        $node = [
            '@type'     => 'WebSite',
            '@id'       => $this->websiteId,
            'url'       => $this->baseUrl,
            'name'      => $this->siteName,
            'publisher' => ['@id' => $this->orgId],
            'inLanguage'=> 'pl-PL',
        ];

        if ($description !== null && $description !== '') {
            $node['description'] = $description;
        }

        return $node;
    }

    /**
     * BreadcrumbList powiązany ze stroną (@id = {pageUrl}#breadcrumb).
     *
     * @param array<int, array{name:string, url:string}> $items
     * @return array<string, mixed>
     */
    public function breadcrumbNode(string $pageUrl, array $items): array
    {
        $list = [];
        foreach ($items as $i => $item) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $item['name'],
                'item'     => $item['url'],
            ];
        }

        return [
            '@type'           => 'BreadcrumbList',
            '@id'             => $pageUrl . '#breadcrumb',
            'itemListElement' => $list,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function webPageNode(
        string $pageUrl,
        string $name,
        ?string $description = null,
        ?string $breadcrumbId = null,
        ?string $mainEntityId = null
    ): array {
        $node = [
            '@type'      => 'WebPage',
            '@id'        => $pageUrl . '#webpage',
            'url'        => $pageUrl,
            'name'       => $name,
            'isPartOf'   => ['@id' => $this->websiteId],
            'inLanguage' => 'pl-PL',
        ];

        if ($description !== null && $description !== '') {
            $node['description'] = $description;
        }
        if ($breadcrumbId !== null) {
            $node['breadcrumb'] = ['@id' => $breadcrumbId];
        }
        if ($mainEntityId !== null) {
            $node['mainEntity'] = ['@id' => $mainEntityId];
        }

        return $node;
    }

    /**
     * Strona historii — BlogPosting + WebPage + BreadcrumbList.
     *
     * @param array<string, mixed> $story
     * @param array<int, array{name:string, url:string}> $breadcrumbItems
     * @param array{width?:int, height?:int}|null $imageDimensions
     * @return array<string, mixed>
     */
    public function storyPage(
        array $story,
        string $pageUrl,
        ?string $imageUrl,
        array $breadcrumbItems,
        ?array $imageDimensions = null
    ): array {
        $articleId = $pageUrl . '#article';
        $breadcrumbId = $pageUrl . '#breadcrumb';

        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode(),
            $this->breadcrumbNode($pageUrl, $breadcrumbItems),
            $this->webPageNode(
                $pageUrl,
                (string)$story['title'],
                (string)($story['excerpt'] ?? ''),
                $breadcrumbId,
                $articleId
            ),
            $this->blogPostingNode($story, $pageUrl, $imageUrl, $articleId, $imageDimensions),
        ]);
    }

    /**
     * @param array<string, mixed> $story
     * @return array<string, mixed>
     */
    public function blogPostingNode(
        array $story,
        string $pageUrl,
        ?string $imageUrl,
        ?string $articleId = null,
        ?array $imageDimensions = null
    ): array {
        $authorName = (string)($story['author_username'] ?? $story['author_name'] ?? 'Anonim');
        $authorUsername = $story['author_username'] ?? null;

        $author = [
            '@type' => 'Person',
            'name'  => $authorName,
        ];
        if ($authorUsername !== null && $authorUsername !== '') {
            $author['url'] = $this->baseUrl . '/profil/' . $authorUsername;
        }

        $published = $this->isoDate($story['published_at'] ?? $story['created_at'] ?? null);
        $modified = $this->isoDate($story['published_at'] ?? $story['created_at'] ?? null);

        $node = [
            '@type'              => 'BlogPosting',
            '@id'                => $articleId ?? ($pageUrl . '#article'),
            'headline'           => (string)$story['title'],
            'description'        => (string)($story['excerpt'] ?? ''),
            'url'                => $pageUrl,
            'mainEntityOfPage'   => ['@id' => $pageUrl . '#webpage'],
            'datePublished'      => $published,
            'dateModified'       => $modified,
            'author'             => $author,
            'publisher'          => [
                '@type' => 'Organization',
                '@id'   => $this->orgId,
                'name'  => $this->siteName,
                'logo'  => ['@id' => $this->baseUrl . '/#logo'],
            ],
            'inLanguage'         => 'pl-PL',
            'isAccessibleForFree'=> true,
            'articleBody'        => mb_substr(trim(strip_tags((string)($story['content'] ?? ''))), 0, 5000),
        ];

        if (!empty($story['category_name'])) {
            $node['articleSection'] = (string)$story['category_name'];
        }

        if ($imageUrl !== null) {
            $image = [
                '@type'      => 'ImageObject',
                'url'        => $imageUrl,
                'contentUrl' => $imageUrl,
            ];
            $width = (int)($imageDimensions['width'] ?? 1200);
            $height = (int)($imageDimensions['height'] ?? 630);
            if ($width > 0 && $height > 0) {
                $image['width'] = $width;
                $image['height'] = $height;
            }
            $node['image'] = [$image];
        }

        $rating = $this->aggregateRatingNode($story);
        if ($rating !== null) {
            $node['aggregateRating'] = $rating;
        }

        $views = (int)($story['views'] ?? 0);
        if ($views > 0) {
            $node['interactionStatistic'] = [
                '@type'                => 'InteractionCounter',
                'interactionType'      => 'https://schema.org/ReadAction',
                'userInteractionCount' => $views,
            ];
        }

        return $node;
    }

    /**
     * Lista historii (strona główna, kategoria, ranking, profil).
     *
     * @param array<int, array<string, mixed>> $stories
     * @return array<string, mixed>
     */
    public function itemListNode(string $pageUrl, array $stories, int $page = 1, int $perPage = 12): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $elements = [];

        foreach ($stories as $i => $story) {
            $slug = (string)($story['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $storyUrl = $this->baseUrl . '/historia/' . $slug;
            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $offset + $i + 1,
                'url'      => $storyUrl,
                'name'     => (string)($story['title'] ?? ''),
            ];
        }

        return [
            '@type'           => 'ItemList',
            '@id'             => $pageUrl . '#itemlist',
            'url'             => $pageUrl,
            'numberOfItems'   => count($elements),
            'itemListOrder'   => 'https://schema.org/ItemListOrderDescending',
            'itemListElement' => $elements,
        ];
    }

    /**
     * Strona główna / listing.
     *
     * @param array<int, array<string, mixed>> $stories
     * @return array<string, mixed>
     */
    public function listingPage(
        string $pageUrl,
        string $name,
        ?string $description,
        array $stories,
        int $page = 1,
        int $perPage = 12,
        ?array $breadcrumbItems = null
    ): array {
        $nodes = [
            $this->organizationNode(),
            $this->websiteNode($description),
            $this->webPageNode(
                $pageUrl,
                $name,
                $description,
                $breadcrumbItems !== null ? $pageUrl . '#breadcrumb' : null,
                $pageUrl . '#itemlist'
            ),
            $this->itemListNode($pageUrl, $stories, $page, $perPage),
        ];

        if ($breadcrumbItems !== null && $breadcrumbItems !== []) {
            array_splice($nodes, 2, 0, [$this->breadcrumbNode($pageUrl, $breadcrumbItems)]);
        }

        return $this->graph($nodes);
    }

    /**
     * Strona kategorii.
     *
     * @param array<string, mixed> $category
     * @param array<int, array<string, mixed>> $stories
     * @param array<int, array{name:string, url:string}> $breadcrumbItems
     * @return array<string, mixed>
     */
    public function categoryPage(
        array $category,
        string $pageUrl,
        array $stories,
        array $breadcrumbItems,
        int $page = 1,
        int $perPage = 12
    ): array {
        $name = (string)$category['name'];
        $description = (string)($category['description'] ?? '');

        $collection = [
            '@type'      => 'CollectionPage',
            '@id'        => $pageUrl . '#webpage',
            'url'        => $pageUrl,
            'name'       => $name,
            'isPartOf'   => ['@id' => $this->websiteId],
            'inLanguage' => 'pl-PL',
            'breadcrumb' => ['@id' => $pageUrl . '#breadcrumb'],
            'about'      => [
                '@type' => 'Thing',
                'name'  => $name,
            ],
            'mainEntity' => ['@id' => $pageUrl . '#itemlist'],
        ];
        if ($description !== '') {
            $collection['description'] = $description;
        }

        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode(),
            $this->breadcrumbNode($pageUrl, $breadcrumbItems),
            $collection,
            $this->itemListNode($pageUrl, $stories, $page, $perPage),
        ]);
    }

    /**
     * Publiczny profil użytkownika.
     *
     * @param array<string, mixed> $profile
     * @param array<int, array<string, mixed>> $stories
     * @param array<int, array{name:string, url:string}> $breadcrumbItems
     * @return array<string, mixed>
     */
    public function profilePage(
        array $profile,
        string $pageUrl,
        array $stories,
        array $breadcrumbItems,
        int $page = 1,
        int $perPage = 12
    ): array {
        $personId = $pageUrl . '#person';

        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode(),
            $this->breadcrumbNode($pageUrl, $breadcrumbItems),
            [
                '@type'      => 'ProfilePage',
                '@id'        => $pageUrl . '#webpage',
                'url'        => $pageUrl,
                'name'       => 'Profil: ' . $profile['username'],
                'isPartOf'   => ['@id' => $this->websiteId],
                'inLanguage' => 'pl-PL',
                'breadcrumb' => ['@id' => $pageUrl . '#breadcrumb'],
                'mainEntity' => ['@id' => $personId],
            ],
            [
                '@type' => 'Person',
                '@id'   => $personId,
                'name'  => (string)$profile['username'],
                'url'   => $pageUrl,
            ],
            $this->itemListNode($pageUrl, $stories, $page, $perPage),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function website(?string $siteName = null, ?string $description = null): array
    {
        return $this->graph([
            $this->organizationNode(),
            $this->websiteNode($description),
            $this->webPageNode(
                $this->baseUrl,
                $siteName ?? $this->siteName,
                $description,
                null,
                $this->websiteId
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $story
     * @return array<string, mixed>
     * @deprecated Użyj storyPage() — zachowane dla kompatybilności wewnętrznej.
     */
    public function story(array $story, string $url, ?string $imageUrl = null): array
    {
        return $this->blogPostingNode($story, $url, $imageUrl);
    }

    /**
     * @param array<int, array{name:string, url:string}> $items
     * @return array<string, mixed>
     * @deprecated Użyj breadcrumbNode() w @graph.
     */
    public function breadcrumbs(array $items): array
    {
        $pageUrl = $items !== [] ? (string)end($items)['url'] : $this->baseUrl;

        return $this->breadcrumbNode($pageUrl, $items);
    }

    /**
     * @param array<string, mixed> $story
     * @return array<string, mixed>|null
     */
    private function aggregateRatingNode(array $story): ?array
    {
        $count = (int)($story['ratings_count'] ?? 0);
        if ($count <= 0) {
            return null;
        }

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => round((float)($story['rating_avg'] ?? 0), 2),
            'ratingCount' => $count,
            'bestRating'  => 5,
            'worstRating' => 1,
        ];
    }

    private function isoDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return date('c');
        }
        $ts = strtotime($date);

        return $ts !== false ? date('c', $ts) : date('c');
    }
}
