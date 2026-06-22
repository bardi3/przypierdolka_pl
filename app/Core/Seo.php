<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Kontener metadanych SEO przekazywanych do layoutu.
 * Renderuje meta title/description, canonical, Open Graph, Twitter Card oraz JSON-LD.
 */
final class Seo
{
    private string $siteName;
    private string $baseUrl;

    private string $title = '';
    private bool $standaloneTitle = false;
    private string $description = '';
    private ?string $canonical = null;
    private string $robots = 'index, follow';
    private string $ogType = 'website';
    private ?string $ogImage = null;
    private ?int $ogImageWidth = null;
    private ?int $ogImageHeight = null;
    private string $ogImageAlt = '';

    private ?string $articlePublished = null;
    private ?string $articleModified = null;
    private ?string $articleAuthor = null;
    private ?string $articleSection = null;

    /** @var array<int, array<string, mixed>> */
    private array $jsonLd = [];

    private ?string $prevUrl = null;
    private ?string $nextUrl = null;
    private string $keywords = '';

    public function __construct(string $siteName, string $baseUrl)
    {
        $this->siteName = $siteName;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->standaloneTitle = false;
        return $this;
    }

    /**
     * Pełny tytuł dokumentu bez dopisywania nazwy serwisu (np. strona główna z ustawień).
     */
    public function setStandaloneTitle(string $title): self
    {
        $this->title = trim($title);
        $this->standaloneTitle = true;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = mb_substr(trim($description), 0, 300);
        return $this;
    }

    public function setCanonical(string $url): self
    {
        $this->canonical = $this->absolute($url);
        return $this;
    }

    public function setRobots(string $robots): self
    {
        $this->robots = $robots;
        return $this;
    }

    public function setOgType(string $type): self
    {
        $this->ogType = $type;
        return $this;
    }

    public function setOgImage(?string $url, ?int $width = null, ?int $height = null): self
    {
        $this->ogImage = $url ? $this->absolute($url) : null;
        $this->ogImageWidth = $width;
        $this->ogImageHeight = $height;
        return $this;
    }

    public function setOgImageAlt(string $alt): self
    {
        $this->ogImageAlt = trim($alt);
        return $this;
    }

    public function setArticleTimes(?string $published, ?string $modified = null): self
    {
        $this->articlePublished = $published;
        $this->articleModified = $modified ?? $published;
        return $this;
    }

    public function setArticleAuthor(?string $author): self
    {
        $this->articleAuthor = $author;
        return $this;
    }

    public function setArticleSection(?string $section): self
    {
        $this->articleSection = $section;
        return $this;
    }

    public function setKeywords(string $keywords): self
    {
        $this->keywords = mb_substr(trim($keywords), 0, 255);
        return $this;
    }

    public function setPrevUrl(?string $url): self
    {
        $this->prevUrl = $url !== null ? $this->absolute($url) : null;
        return $this;
    }

    public function setNextUrl(?string $url): self
    {
        $this->nextUrl = $url !== null ? $this->absolute($url) : null;
        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addJsonLd(array $data): self
    {
        $this->jsonLd[] = $data;
        return $this;
    }

    public function fullTitle(): string
    {
        if ($this->title === '') {
            return $this->siteName;
        }
        if ($this->standaloneTitle) {
            return $this->title;
        }
        return $this->title . ' — ' . $this->siteName;
    }

    private function absolute(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        return $this->baseUrl . '/' . ltrim($url, '/');
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Renderuje wszystkie tagi <head> związane z SEO.
     */
    public function renderHead(): string
    {
        $out = [];
        $title = $this->fullTitle();
        $desc = $this->description;
        $canonical = $this->canonical ?? $this->baseUrl;
        $image = $this->ogImage;

        $out[] = '<title>' . $this->esc($title) . '</title>';
        if ($desc !== '') {
            $out[] = '<meta name="description" content="' . $this->esc($desc) . '">';
        }
        if ($this->keywords !== '') {
            $out[] = '<meta name="keywords" content="' . $this->esc($this->keywords) . '">';
        }
        $out[] = '<meta name="robots" content="' . $this->esc($this->robots) . '">';
        $out[] = '<link rel="canonical" href="' . $this->esc($canonical) . '">';
        if ($this->prevUrl !== null) {
            $out[] = '<link rel="prev" href="' . $this->esc($this->prevUrl) . '">';
        }
        if ($this->nextUrl !== null) {
            $out[] = '<link rel="next" href="' . $this->esc($this->nextUrl) . '">';
        }

        // Open Graph
        $out[] = '<meta property="og:locale" content="pl_PL">';
        $out[] = '<meta property="og:site_name" content="' . $this->esc($this->siteName) . '">';
        $out[] = '<meta property="og:type" content="' . $this->esc($this->ogType) . '">';
        $out[] = '<meta property="og:title" content="' . $this->esc($title) . '">';
        if ($desc !== '') {
            $out[] = '<meta property="og:description" content="' . $this->esc($desc) . '">';
        }
        $out[] = '<meta property="og:url" content="' . $this->esc($canonical) . '">';
        if ($image !== null) {
            $out[] = '<meta property="og:image" content="' . $this->esc($image) . '">';
            if ($this->ogImageWidth !== null && $this->ogImageWidth > 0) {
                $out[] = '<meta property="og:image:width" content="' . $this->ogImageWidth . '">';
            }
            if ($this->ogImageHeight !== null && $this->ogImageHeight > 0) {
                $out[] = '<meta property="og:image:height" content="' . $this->ogImageHeight . '">';
            }
            $imageAlt = $this->ogImageAlt !== ''
                ? $this->ogImageAlt
                : ($this->title !== '' ? $this->title : $this->siteName);
            $out[] = '<meta property="og:image:alt" content="' . $this->esc($imageAlt) . '">';
        }
        if ($this->ogType === 'article') {
            if ($this->articlePublished !== null) {
                $out[] = '<meta property="article:published_time" content="' . $this->esc($this->articlePublished) . '">';
            }
            if ($this->articleModified !== null) {
                $out[] = '<meta property="article:modified_time" content="' . $this->esc($this->articleModified) . '">';
            }
            if ($this->articleAuthor !== null && $this->articleAuthor !== '') {
                $out[] = '<meta property="article:author" content="' . $this->esc($this->articleAuthor) . '">';
            }
            if ($this->articleSection !== null && $this->articleSection !== '') {
                $out[] = '<meta property="article:section" content="' . $this->esc($this->articleSection) . '">';
            }
        }

        // Twitter Card
        $out[] = '<meta name="twitter:card" content="' . ($image !== null ? 'summary_large_image' : 'summary') . '">';
        $out[] = '<meta name="twitter:title" content="' . $this->esc($title) . '">';
        if ($desc !== '') {
            $out[] = '<meta name="twitter:description" content="' . $this->esc($desc) . '">';
        }
        if ($image !== null) {
            $out[] = '<meta name="twitter:image" content="' . $this->esc($image) . '">';
        }

        // JSON-LD — bezpieczne kodowanie (ochrona przed breakout z </script>)
        $jsonFlags = JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT;

        foreach ($this->jsonLd as $schema) {
            $json = json_encode($schema, $jsonFlags);
            if ($json !== false) {
                $out[] = '<script type="application/ld+json">' . $json . '</script>';
            }
        }

        return implode("\n    ", $out);
    }
}
