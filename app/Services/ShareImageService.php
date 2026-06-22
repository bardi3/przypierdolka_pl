<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\Story;

/**
 * Generowanie obrazka historii (WebP) z pełną treścią i brandingiem przypierdolka.pl.
 * Wysokość canvasu dopasowuje się do długości tekstu (min. 630 px).
 */
final class ShareImageService
{
    private const WIDTH = 1200;
    private const WEBP_QUALITY = 88;
    /** Podbij przy zmianie layoutu — wymusza regenerację starych plików. */
    private const FORMAT_VERSION = 4;
    private const MIN_HEIGHT = 630;
    private const MAX_HEIGHT = 4000;
    /** Szerokości wariantów responsywnych (WebP) — główny plik ma WIDTH. */
    private const RESPONSIVE_WIDTHS = [480, 800];

    private string $generatedDir;
    private string $publicBase;
    private string $publicPath;
    private Story $stories;

    public function __construct(?Story $stories = null)
    {
        $this->generatedDir = (string)Config::get('app.paths.uploads') . '/generated';
        $this->publicBase = 'assets/uploads/generated';
        $this->publicPath = (string)Config::get('app.paths.public');
        $this->stories = $stories ?? new Story();

        if (!is_dir($this->generatedDir)) {
            @mkdir($this->generatedDir, 0775, true);
        }
    }

    /**
     * @param array<string, mixed> $story
     */
    public function generateAndStore(array $story, bool $force = false): string
    {
        $storyId = (int)($story['id'] ?? 0);
        if ($storyId <= 0) {
            return $this->fallbackPath();
        }

        $storedPath = !empty($story['generated_image_path']) ? (string)$story['generated_image_path'] : null;
        if (!$force && $storedPath !== null && $this->isCurrentFormat($storedPath)) {
            $abs = $this->absolutePath($storedPath);
            if (is_file($abs)) {
                return $storedPath;
            }
        }

        if (!$this->canGenerate()) {
            return $this->fallbackPath();
        }

        $relative = $this->buildRelativePath($story);
        $absolute = $this->generatedDir . '/' . basename($relative);

        $this->render(
            $absolute,
            (string)($story['content'] ?? $story['excerpt'] ?? '')
        );

        if (!is_file($absolute)) {
            return $this->fallbackPath();
        }

        $this->ensureResponsiveVariants($relative);

        $this->stories->setGeneratedImagePath($storyId, $relative);

        if ($storedPath !== null && $storedPath !== $relative) {
            $this->deleteGeneratedFile($storedPath);
        }

        return $relative;
    }

    /**
     * Usuwa stary plik i generuje obrazek od nowa (np. po edycji treści).
     *
     * @param array<string, mixed> $story
     */
    public function regenerateAndStore(array $story): string
    {
        $storedPath = !empty($story['generated_image_path']) ? (string)$story['generated_image_path'] : null;
        if ($storedPath !== null) {
            $this->deleteGeneratedFile($storedPath);
        }

        return $this->generateAndStore($story, true);
    }

    /**
     * URL obrazka z cache bustingiem (filemtime) — po regeneracji przeglądarka ładuje nowy plik.
     */
    public function versionedUrl(string $relativePath): string
    {
        $abs = $this->absolutePath($relativePath);
        if (!is_file($abs)) {
            return url($relativePath);
        }

        return url($relativePath . '?v=' . filemtime($abs));
    }

    /**
     * @param array<string, mixed> $story
     */
    public function getOrGenerate(array $story): string
    {
        $storedPath = !empty($story['generated_image_path']) ? (string)$story['generated_image_path'] : null;
        if ($storedPath !== null && $this->isCurrentFormat($storedPath)) {
            $abs = $this->absolutePath($storedPath);
            if (is_file($abs)) {
                return $storedPath;
            }
        }

        return $this->generateAndStore($story);
    }

    public function canGenerate(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagewebp')
            && function_exists('imagettftext')
            && $this->fontPath('bold') !== null;
    }

    public function fallbackPath(): string
    {
        $fallback = 'assets/img/og-default.webp';
        $abs = $this->absolutePath($fallback);
        if (!is_file($abs) && $this->canGenerate()) {
            $this->ensureDefaultImage($abs);
        }
        return $fallback;
    }

    /**
     * Metadane obrazka responsywnego (ścieżki względne public/).
     *
     * @return array{src:string, width:int, height:int, sizes:string, variants:list<array{path:string, width:int}>}
     */
    public function responsiveMeta(string $relativePath): array
    {
        $sizes = '(max-width: 640px) 100vw, (max-width: 1024px) min(100vw, 800px), 1200px';
        $width = self::WIDTH;
        $height = self::MIN_HEIGHT;

        if (str_starts_with($relativePath, $this->publicBase . '/')) {
            $this->ensureResponsiveVariants($relativePath);
            $abs = $this->absolutePath($relativePath);
            if (is_file($abs)) {
                $info = @getimagesize($abs);
                if ($info !== false) {
                    $width = (int)$info[0];
                    $height = (int)$info[1];
                }
            }
        }

        if (!str_starts_with($relativePath, $this->publicBase . '/')) {
            return [
                'src'      => $relativePath,
                'width'    => $width,
                'height'   => $height,
                'sizes'    => $sizes,
                'variants' => [['path' => $relativePath, 'width' => $width]],
            ];
        }

        $variants = [];
        foreach (self::RESPONSIVE_WIDTHS as $variantWidth) {
            $variantPath = $this->variantPath($relativePath, $variantWidth);
            if (is_file($this->absolutePath($variantPath))) {
                $variants[] = ['path' => $variantPath, 'width' => $variantWidth];
            }
        }
        $variants[] = ['path' => $relativePath, 'width' => $width];

        return [
            'src'      => $relativePath,
            'width'    => $width,
            'height'   => $height,
            'sizes'    => $sizes,
            'variants' => $variants,
        ];
    }

    /**
     * @param array<string, mixed> $story
     */
    private function buildRelativePath(array $story): string
    {
        $storyId = (int)($story['id'] ?? 0);
        $slug = (string)($story['slug'] ?? 'historia');
        $safeSlug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug)) ?? 'historia';
        $safeSlug = trim($safeSlug, '-') ?: 'historia';

        return $this->publicBase . '/' . $storyId . '-' . $safeSlug . '-v' . self::FORMAT_VERSION . '.webp';
    }

    private function isCurrentFormat(string $relativePath): bool
    {
        return str_ends_with(strtolower($relativePath), '-v' . self::FORMAT_VERSION . '.webp');
    }

    private function absolutePath(string $relativePath): string
    {
        return $this->publicPath . '/' . ltrim($relativePath, '/');
    }

    private function deleteGeneratedFile(string $relativePath): void
    {
        if (!str_starts_with($relativePath, $this->publicBase . '/')) {
            return;
        }
        $abs = $this->absolutePath($relativePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
        foreach (self::RESPONSIVE_WIDTHS as $width) {
            $variantAbs = $this->absolutePath($this->variantPath($relativePath, $width));
            if (is_file($variantAbs)) {
                @unlink($variantAbs);
            }
        }
    }

    private function variantPath(string $mainRelativePath, int $width): string
    {
        return preg_replace('/\.webp$/i', '-' . $width . 'w.webp', $mainRelativePath) ?? $mainRelativePath;
    }

    private function ensureResponsiveVariants(string $mainRelativePath): void
    {
        if (!function_exists('imagecreatefromwebp')) {
            return;
        }

        $mainAbs = $this->absolutePath($mainRelativePath);
        if (!is_file($mainAbs)) {
            return;
        }

        $source = @imagecreatefromwebp($mainAbs);
        if ($source === false) {
            return;
        }

        $sourceW = imagesx($source);
        $sourceH = imagesy($source);
        if ($sourceW <= 0 || $sourceH <= 0) {
            return;
        }

        foreach (self::RESPONSIVE_WIDTHS as $targetWidth) {
            $variantRel = $this->variantPath($mainRelativePath, $targetWidth);
            $variantAbs = $this->absolutePath($variantRel);
            if (is_file($variantAbs)) {
                continue;
            }

            $targetHeight = (int)round($targetWidth * $sourceH / $sourceW);
            $dest = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($dest === false) {
                continue;
            }

            imagecopyresampled(
                $dest,
                $source,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $sourceW,
                $sourceH
            );
            imagewebp($dest, $variantAbs, self::WEBP_QUALITY);
        }
    }

    private function render(string $path, string $content): void
    {
        $w = self::WIDTH;
        $body = $this->sanitizeText($content);
        if ($body === '') {
            $body = 'przypierdolka.pl';
        }

        $layout = $this->calculateBodyLayout($body, $w);
        $h = min(self::MAX_HEIGHT, max(self::MIN_HEIGHT, $layout['height']));

        $img = imagecreatetruecolor($w, $h);
        if ($img === false) {
            return;
        }

        $bg = $this->color($img, 15, 23, 42);
        $panel = $this->color($img, 30, 41, 59);
        $panelLight = $this->color($img, 51, 65, 85);
        $accent = $this->color($img, 245, 179, 1);
        $white = $this->color($img, 255, 255, 255);

        imagefilledrectangle($img, 0, 0, $w, $h, $bg);
        imagefilledrectangle($img, 0, 0, 14, $h, $accent);

        $panelTop = 28;
        $panelBottom = $h - 72;
        $this->fillRoundedRect($img, 40, $panelTop, $w - 80, $panelBottom - $panelTop, 20, $panel);
        $this->fillRoundedRect($img, 56, $panelTop + 12, $w - 112, $panelBottom - $panelTop - 24, 14, $panelLight);

        $textX = 88;
        $textMaxWidth = $w - 176;
        $textY = $panelTop + 56;
        $lineHeight = $layout['lineHeight'];
        $fontSize = $layout['fontSize'];

        foreach ($layout['lines'] as $line) {
            if ($textY + $lineHeight > $panelBottom - 16) {
                break;
            }
            if ($line === '') {
                $textY += (int)round($lineHeight * 0.45);
                continue;
            }
            $this->drawText($img, $line, $textX, $textY, $white, 'regular', $fontSize);
            $textY += $lineHeight;
        }

        $footerTop = $h - 64;
        imagefilledrectangle($img, 56, $footerTop, $w - 56, $h - 28, $panel);
        $this->drawCenteredBrand($img, (int)($w / 2), $h - 38, $white, $accent, 22);

        imagewebp($img, $path, self::WEBP_QUALITY);
    }

    /**
     * Dobiera rozmiar czcionki i wysokość canvasu tak, by zmieścić całą treść.
     *
     * @return array{fontSize:int, lineHeight:int, lines:list<string>, height:int}
     */
    private function calculateBodyLayout(string $content, int $canvasWidth): array
    {
        $maxWidth = $canvasWidth - 176;
        $topPad = 84;
        $bottomPad = 96;
        $best = null;

        for ($fontSize = 30; $fontSize >= 15; $fontSize--) {
            $lineHeight = (int)max(20, round($fontSize * 1.42));
            $lines = $this->wrapParagraphs($content, 'regular', $fontSize, $maxWidth);
            $blankLines = count(array_filter($lines, static fn (string $l): bool => $l === ''));
            $textLines = count($lines) - $blankLines;
            $contentHeight = ($textLines * $lineHeight) + (int)round($blankLines * $lineHeight * 0.45);
            $totalHeight = $topPad + $contentHeight + $bottomPad;

            $candidate = [
                'fontSize'   => $fontSize,
                'lineHeight' => $lineHeight,
                'lines'      => $lines,
                'height'     => $totalHeight,
            ];

            if ($totalHeight <= self::MIN_HEIGHT) {
                return $candidate;
            }

            $best = $candidate;
            if ($totalHeight <= self::MAX_HEIGHT) {
                return $candidate;
            }
        }

        return $best ?? [
            'fontSize'   => 15,
            'lineHeight' => 22,
            'lines'      => $this->wrapParagraphs($content, 'regular', 15, $maxWidth),
            'height'     => self::MAX_HEIGHT,
        ];
    }

    /**
     * @return list<string>
     */
    private function wrapParagraphs(string $text, string $weight, int $size, int $maxWidth): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $parts = preg_split('/\n+/u', $normalized) ?: [];
        $lines = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $wrapped = $this->wrapText($part, $weight, $size, $maxWidth, 0);
            foreach ($wrapped as $line) {
                $lines[] = $line;
            }
            $lines[] = '';
        }

        if ($lines !== [] && $lines[count($lines) - 1] === '') {
            array_pop($lines);
        }

        return $lines !== [] ? $lines : [''];
    }

    /**
     * @return int Y po ostatniej linii
     */
    private function drawWrappedText(
        \GdImage $img,
        string $text,
        int $x,
        int $y,
        int $color,
        string $weight,
        int $size,
        int $maxWidth,
        int $maxLines,
        int $lineHeight
    ): int {
        $lines = $this->wrapText($text, $weight, $size, $maxWidth, $maxLines);
        foreach ($lines as $i => $line) {
            $this->drawText($img, $line, $x, $y + ($i * $lineHeight), $color, $weight, $size);
        }

        return $y + (max(1, count($lines)) * $lineHeight);
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, string $weight, int $size, int $maxWidth, int $maxLines): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return [''];
        }

        $unlimited = $maxLines <= 0;
        $limit = $unlimited ? PHP_INT_MAX : $maxLines;

        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($this->textWidth($candidate, $weight, $size) <= $maxWidth) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;

            if (count($lines) >= $limit) {
                break;
            }
        }

        if ($current !== '' && count($lines) < $limit) {
            $lines[] = $current;
        }

        if (!$unlimited && count($lines) === $maxLines && count($words) > 0) {
            $last = $lines[$maxLines - 1] ?? '';
            if ($this->textWidth($last . '…', $weight, $size) > $maxWidth) {
                $last = mb_strimwidth($last, 0, max(8, mb_strlen($last) - 4), '');
            }
            $lines[$maxLines - 1] = rtrim($last) . '…';
        }

        return $lines !== [] ? $lines : [''];
    }

    private function drawText(
        \GdImage $img,
        string $text,
        int $x,
        int $y,
        int $color,
        string $weight,
        int $size
    ): void {
        $font = $this->fontPath($weight);
        if ($font === null || $text === '') {
            return;
        }

        imagettftext($img, (float)$size, 0, $x, $y, $color, $font, $this->utf8($text));
    }

    private function drawCenteredText(
        \GdImage $img,
        string $text,
        int $centerX,
        int $y,
        int $color,
        string $weight,
        int $size
    ): void {
        $width = $this->textWidth($text, $weight, $size);
        $this->drawText($img, $text, $centerX - (int)($width / 2), $y, $color, $weight, $size);
    }

    private function drawCenteredBrand(
        \GdImage $img,
        int $centerX,
        int $y,
        int $whiteColor,
        int $accentColor,
        int $size = 22
    ): void {
        $name = 'przypierdolka';
        $tld = '.pl';
        $nameWidth = $this->textWidth($name, 'bold', $size);
        $tldWidth = $this->textWidth($tld, 'bold', $size);
        $startX = $centerX - (int)(($nameWidth + $tldWidth) / 2);

        $this->drawText($img, $name, $startX, $y, $whiteColor, 'bold', $size);
        $this->drawText($img, $tld, $startX + $nameWidth, $y, $accentColor, 'bold', $size);
    }

    private function textWidth(string $text, string $weight, int $size): int
    {
        $font = $this->fontPath($weight);
        if ($font === null || $text === '') {
            return 0;
        }

        $box = imagettfbbox((float)$size, 0, $font, $this->utf8($text));
        if ($box === false) {
            return 0;
        }

        return (int)abs($box[2] - $box[0]);
    }

    private function fillRoundedRect(
        \GdImage $img,
        int $x,
        int $y,
        int $width,
        int $height,
        int $radius,
        int $color
    ): void {
        $radius = min($radius, (int)floor(min($width, $height) / 2));
        imagefilledrectangle($img, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
        imagefilledrectangle($img, $x, $y + $radius, $x + $width, $y + $height - $radius, $color);
        imagefilledellipse($img, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
    }

    private function color(\GdImage $img, int $r, int $g, int $b): int
    {
        return imagecolorallocate($img, $r, $g, $b) ?: 0;
    }

    private function sanitizeText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n /u", "\n", $text) ?? $text;

        return trim($this->utf8($text));
    }

    private function utf8(string $text): string
    {
        if ($text === '' || mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $converted = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-2, Windows-1250');

        return is_string($converted) ? $converted : $text;
    }

    private function fontPath(string $weight): ?string
    {
        static $cache = [];

        $key = $weight === 'bold' ? 'bold' : 'regular';
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $file = $weight === 'bold' ? 'pp-bold.ttf' : 'pp-regular.ttf';
        $dejaVu = $weight === 'bold' ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf';
        // DejaVu i pp-* (Open Sans Latin Extended) obsługują polskie znaki diakrytyczne.
        $candidates = [
            $this->publicPath . '/assets/fonts/' . $file,
            '/usr/share/fonts/truetype/dejavu/' . $dejaVu,
            '/usr/share/fonts/TTF/' . $dejaVu,
            '/usr/share/fonts/dejavu/' . $dejaVu,
            '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
            '/System/Library/Fonts/Supplemental/Arial' . ($weight === 'bold' ? ' Bold' : '') . '.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $cache[$key] = $path;
                return $path;
            }
        }

        return null;
    }

    private function ensureDefaultImage(string $absPath): void
    {
        $dir = dirname($absPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $this->render($absPath, (string)Config::get('app.name', 'przypierdolka.pl'));
    }
}
