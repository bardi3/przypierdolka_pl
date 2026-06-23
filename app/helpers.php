<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Globalne helpery dostępne w kontrolerach i szablonach.
 */

if (!function_exists('e')) {
    /**
     * Escape HTML - używany w KAŻDYM miejscu wyświetlania danych w szablonach.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Buduje absolutny URL na podstawie app.url.
     */
    function url(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $base = rtrim((string)Config::get('app.url'), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * URL do assetu (ścieżka względem roota serwera) z cache bustingiem.
     */
    function asset(string $path): string
    {
        $version = (string)Config::get('app.assets_version', '1');
        return '/assets/' . ltrim($path, '/') . '?v=' . $version;
    }
}

if (!function_exists('generated_image_url')) {
    /**
     * URL wygenerowanego obrazka historii z cache bustingiem (filemtime).
     */
    function generated_image_url(string $relativePath): string
    {
        $publicPath = (string)Config::get('app.paths.public');
        $abs = $publicPath . '/' . ltrim($relativePath, '/');
        if (!is_file($abs)) {
            return url($relativePath);
        }

        return url($relativePath . '?v=' . filemtime($abs));
    }
}

if (!function_exists('user_avatar_url')) {
    /**
     * URL awatara użytkownika z cache bustingiem (filemtime).
     */
    function user_avatar_url(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }
        $publicPath = (string)Config::get('app.paths.public');
        $abs = $publicPath . '/' . ltrim($relativePath, '/');
        if (!is_file($abs)) {
            return null;
        }

        return url($relativePath . '?v=' . filemtime($abs));
    }
}

if (!function_exists('user_avatar_dimensions')) {
    /**
     * Rozmiar wyświetlania awatara (px) dla wariantu sm|md|lg|xl.
     *
     * @return array{width:int,height:int,sizes:string}
     */
    function user_avatar_dimensions(string $size = 'md'): array
    {
        $map = [
            'sm' => 28,
            'md' => 40,
            'lg' => 48,
            'xl' => 88,
        ];
        $px = $map[$size] ?? $map['md'];

        return [
            'width'  => $px,
            'height' => $px,
            'sizes'  => $px . 'px',
        ];
    }
}

if (!function_exists('user_avatar_srcset')) {
    /**
     * srcset awatara (1x + opcjonalny @2x) z cache bustingiem.
     */
    function user_avatar_srcset(?string $relativePath): ?string
    {
        $url1x = user_avatar_url($relativePath);
        if ($url1x === null) {
            return null;
        }

        $publicPath = (string)Config::get('app.paths.public');
        $abs = $publicPath . '/' . ltrim((string)$relativePath, '/');
        if (!is_file($abs)) {
            return null;
        }

        $w1 = (int)(@getimagesize($abs)[0] ?? 80);
        $parts = [e($url1x) . ' ' . $w1 . 'w'];

        $path2x = preg_replace('/\.webp$/', '@2x.webp', (string)$relativePath);
        $abs2x = $publicPath . '/' . ltrim($path2x, '/');
        if ($path2x !== (string)$relativePath && is_file($abs2x)) {
            $w2 = (int)(@getimagesize($abs2x)[0] ?? ($w1 * 2));
            $parts[] = e(url($path2x . '?v=' . filemtime($abs2x))) . ' ' . $w2 . 'w';
        }

        return implode(', ', $parts);
    }
}

if (!function_exists('iso8601')) {
    /**
     * Data w formacie ISO 8601 (atrybut datetime, schema.org).
     */
    function iso8601(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return date('c');
        }
        $ts = strtotime($datetime);

        return $ts !== false ? date('c', $ts) : date('c');
    }
}

if (!function_exists('rating_aria_label')) {
    /**
     * Opis oceny dla czytników ekranu i semantyki HTML.
     */
    function rating_aria_label(float $avg, int $count): string
    {
        if ($count <= 0) {
            return 'Brak ocen';
        }

        return sprintf(
            'Średnia ocena %.2f na 5 gwiazdek, na podstawie %d %s',
            $avg,
            $count,
            $count === 1 ? 'oceny' : ($count < 5 ? 'ocen' : 'ocen')
        );
    }
}

if (!function_exists('old')) {
    /**
     * Zwraca poprzednią wartość pola formularza (po błędzie walidacji).
     *
     * @param array<string, mixed> $old
     */
    function old(array $old, string $key, string $default = ''): string
    {
        return e($old[$key] ?? $default);
    }
}

if (!function_exists('active')) {
    /**
     * Zwraca klasę 'active', gdy bieżący URI pasuje do ścieżki.
     */
    function active(string $path, string $class = 'active'): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return rtrim($uri, '/') === rtrim($path, '/') ? $class : '';
    }
}

if (!function_exists('star_rating')) {
    /**
     * Renderuje gwiazdki dla średniej oceny (tylko do odczytu, bez zewn. fontów).
     */
    function star_rating(float $avg): string
    {
        $full = (int)floor($avg);
        $half = ($avg - $full) >= 0.5;
        $html = '<span class="rating-stars" aria-hidden="true">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $full) {
                $html .= '<span style="color:var(--pp-accent,#f5b301)">&#9733;</span>';
            } elseif ($half && $i === $full + 1) {
                $html .= '<span style="color:var(--pp-accent,#f5b301);opacity:.55">&#9733;</span>';
            } else {
                $html .= '<span>&#9734;</span>';
            }
        }
        return $html . '</span>';
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'przed chwilą';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' min temu';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' godz. temu';
        }
        if ($diff < 2592000) {
            return floor($diff / 86400) . ' dni temu';
        }
        return date('Y-m-d', $ts);
    }
}

if (!function_exists('ranking_min_accusative')) {
    /**
     * Fraza „co najmniej X ocen/y” (biernik) do naturalnych zdań po polsku.
     */
    function ranking_min_accusative(int $min): string
    {
        return match ($min) {
            1       => 'co najmniej jedną ocenę',
            2       => 'co najmniej dwie oceny',
            3       => 'co najmniej trzy oceny',
            4       => 'co najmniej cztery oceny',
            default => "co najmniej {$min} ocen",
        };
    }
}

if (!function_exists('ranking_lead')) {
    /**
     * Lead pod nagłówkiem strony rankingu — naturalny, przyjazny SEO.
     */
    function ranking_lead(int $min, ?string $period = null, ?string $categoryName = null): string
    {
        $minPhrase = ranking_min_accusative($min);

        if ($categoryName !== null && $categoryName !== '') {
            return "Najciekawsze historie z kategorii {$categoryName} — w rankingu są tylko opowieści, które zebrały {$minPhrase}.";
        }

        return match ($period) {
            'top_month' => "Miesięczny ranking najlepiej ocenianych historii — tu trafiają opowieści, które mają {$minPhrase}.",
            'top_all'   => "Ranking wszech czasów — najwyżej oceniane opowieści, które zebrały {$minPhrase}.",
            'top_week'  => "Tygodniowy ranking najlepiej ocenianych historii — tu trafiają opowieści, które mają {$minPhrase}.",
            default     => "Najlepiej oceniane historie — w rankingu są tylko opowieści, które zebrały {$minPhrase}.",
        };
    }
}

if (!function_exists('ranking_empty_lead')) {
    /**
     * Tekst gdy ranking jest pusty (bez linków).
     */
    function ranking_empty_lead(int $min): string
    {
        return 'W tym rankingu nie ma jeszcze historii, które zebrały ' . ranking_min_accusative($min) . '.';
    }
}
